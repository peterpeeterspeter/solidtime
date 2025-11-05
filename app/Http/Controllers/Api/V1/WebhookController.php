<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookDispatcher $dispatcher
    ) {
    }

    /**
     * List all webhooks for the organization
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->input('organization_id');

        if (! $this->userHasAccessToOrganization($user->id, $organizationId)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $webhooks = Webhook::forOrganization($organizationId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $webhooks->map(fn ($webhook) => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'description' => $webhook->description,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'filters' => $webhook->filters,
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'last_success_at' => $webhook->last_success_at?->toIso8601String(),
                'last_failure_at' => $webhook->last_failure_at?->toIso8601String(),
                'last_error' => $webhook->last_error,
                'verification_status' => $webhook->verification_status,
                'verified_at' => $webhook->verified_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
                'created_by' => [
                    'id' => $webhook->user->id,
                    'name' => $webhook->user->name,
                ],
            ]),
        ]);
    }

    /**
     * Create a new webhook
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|uuid|exists:organizations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => ['required', 'string', Rule::in(array_keys(Webhook::EVENTS))],
            'filters' => 'nullable|array',
            'secret' => 'nullable|string|min:32',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $organizationId = $request->input('organization_id');

        if (! $this->userHasAccessToOrganization($user->id, $organizationId)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        // Generate secret if not provided
        $secret = $request->input('secret') ?? Webhook::generateSecret();

        $webhook = Webhook::create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'url' => $request->input('url'),
            'secret' => $secret,
            'events' => $request->input('events'),
            'filters' => $request->input('filters'),
            'is_active' => true,
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'description' => $webhook->description,
                'url' => $webhook->url,
                'secret' => $secret, // Show secret once
                'events' => $webhook->events,
                'filters' => $webhook->filters,
                'is_active' => $webhook->is_active,
                'verification_status' => $webhook->verification_status,
                'created_at' => $webhook->created_at->toIso8601String(),
            ],
            'warning' => 'Save the secret securely. It is used to verify webhook signatures.',
        ], 201);
    }

    /**
     * Show a specific webhook
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'description' => $webhook->description,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'filters' => $webhook->filters,
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'last_success_at' => $webhook->last_success_at?->toIso8601String(),
                'last_failure_at' => $webhook->last_failure_at?->toIso8601String(),
                'last_error' => $webhook->last_error,
                'verification_status' => $webhook->verification_status,
                'verified_at' => $webhook->verified_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update webhook
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'url' => 'sometimes|url|max:500',
            'events' => 'sometimes|array|min:1',
            'events.*' => ['required_with:events', 'string', Rule::in(array_keys(Webhook::EVENTS))],
            'filters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $webhook->update($request->only(['name', 'description', 'url', 'events', 'filters', 'is_active']));

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'description' => $webhook->description,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'filters' => $webhook->filters,
                'is_active' => $webhook->is_active,
                'updated_at' => $webhook->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete webhook
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Test webhook by sending a test event
     */
    public function test(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $delivery = $this->dispatcher->send($webhook, 'webhook.test', [
            'test' => true,
            'message' => 'This is a test webhook from Solidtime',
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'data' => [
                'delivery_id' => $delivery->delivery_id,
                'status' => $delivery->status,
                'http_status_code' => $delivery->http_status_code,
                'response_body' => $delivery->response_body,
                'error_message' => $delivery->error_message,
                'duration_ms' => $delivery->duration_ms,
            ],
        ]);
    }

    /**
     * Get available webhook events
     */
    public function events(): JsonResponse
    {
        return response()->json([
            'data' => Webhook::EVENTS,
        ]);
    }

    /**
     * Check if user has access to organization
     */
    protected function userHasAccessToOrganization(string $userId, string $organizationId): bool
    {
        return \App\Models\Member::where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
