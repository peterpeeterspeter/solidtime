<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    public function __construct(
        protected ApiKeyService $apiKeyService
    ) {
    }

    /**
     * List all API keys for the authenticated user's organization
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $request->input('organization_id');

        // Verify user has access to organization
        if (! $this->userHasAccessToOrganization($user->id, $organizationId)) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'You do not have access to this organization',
            ], 403);
        }

        $apiKeys = $this->apiKeyService->getForOrganization($organizationId);

        return response()->json([
            'data' => $apiKeys->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'description' => $key->description,
                'key_prefix' => $key->key_prefix,
                'scopes' => $key->scopes,
                'is_active' => $key->is_active,
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'last_used_ip' => $key->last_used_ip,
                'usage_count' => $key->usage_count,
                'expires_at' => $key->expires_at?->toIso8601String(),
                'created_at' => $key->created_at->toIso8601String(),
                'created_by' => [
                    'id' => $key->user->id,
                    'name' => $key->user->name,
                ],
            ]),
        ]);
    }

    /**
     * Create a new API key
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|uuid|exists:organizations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'scopes' => 'required|array|min:1',
            'scopes.*' => ['required', 'string', Rule::in($this->getAvailableScopes())],
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $organizationId = $request->input('organization_id');

        // Verify user has access to organization
        if (! $this->userHasAccessToOrganization($user->id, $organizationId)) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'You do not have access to this organization',
            ], 403);
        }

        $result = $this->apiKeyService->create(
            userId: $user->id,
            organizationId: $organizationId,
            name: $request->input('name'),
            scopes: $request->input('scopes'),
            description: $request->input('description'),
            expiresAt: $request->input('expires_at') ? \Carbon\Carbon::parse($request->input('expires_at')) : null
        );

        return response()->json([
            'data' => [
                'id' => $result['api_key']->id,
                'name' => $result['api_key']->name,
                'description' => $result['api_key']->description,
                'key' => $result['plain_key'], // ONLY SHOWN ONCE!
                'key_prefix' => $result['api_key']->key_prefix,
                'scopes' => $result['api_key']->scopes,
                'expires_at' => $result['api_key']->expires_at?->toIso8601String(),
                'created_at' => $result['api_key']->created_at->toIso8601String(),
            ],
            'warning' => 'Save this API key securely. It will not be shown again.',
        ], 201);
    }

    /**
     * Show a specific API key
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (! $apiKey) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'API key not found',
            ], 404);
        }

        $user = $request->user();

        // Verify user has access
        if (! $this->userHasAccessToOrganization($user->id, $apiKey->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'description' => $apiKey->description,
                'key_prefix' => $apiKey->key_prefix,
                'scopes' => $apiKey->scopes,
                'is_active' => $apiKey->is_active,
                'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
                'last_used_ip' => $apiKey->last_used_ip,
                'usage_count' => $apiKey->usage_count,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
                'created_at' => $apiKey->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update API key (scopes only)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (! $apiKey) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'API key not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $apiKey->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'scopes' => 'required|array|min:1',
            'scopes.*' => ['required', 'string', Rule::in($this->getAvailableScopes())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->apiKeyService->updateScopes($id, $request->input('scopes'));

        $apiKey->refresh();

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'scopes' => $apiKey->scopes,
                'updated_at' => $apiKey->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Revoke (soft delete) an API key
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $apiKey = ApiKey::find($id);

        if (! $apiKey) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'API key not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $apiKey->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $this->apiKeyService->revoke($id);

        return response()->json([
            'message' => 'API key revoked successfully',
        ]);
    }

    /**
     * Get available scopes
     */
    public function scopes(): JsonResponse
    {
        return response()->json([
            'data' => [
                '*' => 'Full access to all resources',
                'time_entries:read' => 'Read time entries',
                'time_entries:write' => 'Create and update time entries',
                'projects:read' => 'Read projects',
                'projects:write' => 'Create and update projects',
                'tasks:read' => 'Read tasks',
                'tasks:write' => 'Create and update tasks',
                'focus_sessions:read' => 'Read focus sessions',
                'reports:read' => 'Read and generate reports',
                'webhooks:read' => 'Read webhooks',
                'webhooks:write' => 'Create and manage webhooks',
            ],
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

    /**
     * Get list of available scopes
     */
    protected function getAvailableScopes(): array
    {
        return [
            '*',
            'time_entries:read',
            'time_entries:write',
            'projects:read',
            'projects:write',
            'tasks:read',
            'tasks:write',
            'focus_sessions:read',
            'reports:read',
            'webhooks:read',
            'webhooks:write',
        ];
    }
}
