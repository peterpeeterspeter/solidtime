<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * WebhookController
 *
 * Manages webhook subscriptions and delivery history.
 *
 * @tags Webhooks
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {
    }

    /**
     * List all webhooks for the authenticated user.
     *
     * @operationId getWebhooks
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $webhooks->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                    'last_delivery_at' => $webhook->last_delivery_at?->toIso8601String(),
                    'delivery_success_count' => $webhook->delivery_success_count,
                    'delivery_failure_count' => $webhook->delivery_failure_count,
                    'success_rate' => $webhook->success_rate,
                    'created_at' => $webhook->created_at->toIso8601String(),
                ],
            ]),
        ]);
    }

    /**
     * Get a specific webhook by ID.
     *
     * @operationId getWebhook
     */
    public function show(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->findOrFail($webhookId);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'last_delivery_at' => $webhook->last_delivery_at?->toIso8601String(),
                'delivery_success_count' => $webhook->delivery_success_count,
                'delivery_failure_count' => $webhook->delivery_failure_count,
                'success_rate' => $webhook->success_rate,
                'created_at' => $webhook->created_at->toIso8601String(),
                'updated_at' => $webhook->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new webhook.
     *
     * @operationId createWebhook
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in(Webhook::AVAILABLE_EVENTS)],
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'url.starts_with' => 'Webhook URL must use HTTPS for security.',
            'events.*.in' => 'Invalid event type. See documentation for available events.',
        ]);

        // Generate secure random secret if not provided
        if (! isset($validated['secret'])) {
            $validated['secret'] = Str::random(64);
        }

        $webhook = Webhook::create([
            'user_id' => $request->user()->id,
            'url' => $validated['url'],
            'secret' => $validated['secret'],
            'events' => $validated['events'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'secret' => $webhook->secret, // Only shown on creation!
                'created_at' => $webhook->created_at->toIso8601String(),
            ],
            'message' => 'Webhook created successfully. Save the secret - it will not be shown again.',
        ], 201);
    }

    /**
     * Update an existing webhook.
     *
     * @operationId updateWebhook
     */
    public function update(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->findOrFail($webhookId);

        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:2048', 'starts_with:https://'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in(Webhook::AVAILABLE_EVENTS)],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'url.starts_with' => 'Webhook URL must use HTTPS for security.',
        ]);

        $webhook->update($validated);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'updated_at' => $webhook->updated_at->toIso8601String(),
            ],
            'message' => 'Webhook updated successfully',
        ]);
    }

    /**
     * Delete a webhook.
     *
     * @operationId deleteWebhook
     */
    public function destroy(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->findOrFail($webhookId);

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Get delivery history for a webhook.
     *
     * @operationId getWebhookDeliveries
     */
    public function deliveries(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->findOrFail($webhookId);

        $perPage = min((int) $request->get('per_page', 50), 100);

        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $deliveries->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'event_type' => $delivery->event_type,
                    'attempt' => $delivery->attempt,
                    'response_status' => $delivery->response_status,
                    'response_body' => $delivery->response_body,
                    'error_message' => $delivery->error_message,
                    'success' => $delivery->wasSuccessful(),
                    'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                    'created_at' => $delivery->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'from' => $deliveries->firstItem(),
                'to' => $deliveries->lastItem(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
            ],
            'links' => [
                'first' => $deliveries->url(1),
                'last' => $deliveries->url($deliveries->lastPage()),
                'prev' => $deliveries->previousPageUrl(),
                'next' => $deliveries->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Send a test webhook.
     *
     * @operationId testWebhook
     */
    public function test(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::where('user_id', $request->user()->id)
            ->findOrFail($webhookId);

        $result = $this->webhookService->sendTestWebhook($webhook);

        return response()->json([
            'data' => $result,
            'message' => $result['message'],
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Get available webhook events.
     *
     * @operationId getAvailableWebhookEvents
     */
    public function availableEvents(): JsonResponse
    {
        return response()->json([
            'data' => collect(Webhook::AVAILABLE_EVENTS)->map(function ($event) {
                [$resource, $action] = explode('.', $event);

                return [
                    'event' => $event,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $this->getEventDescription($event),
                ];
            })->values(),
        ]);
    }

    /**
     * Get human-readable description for event types.
     */
    private function getEventDescription(string $event): string
    {
        return match ($event) {
            'time_entry.created' => 'Triggered when a new time entry is created',
            'time_entry.updated' => 'Triggered when a time entry is modified',
            'time_entry.deleted' => 'Triggered when a time entry is deleted',
            'project.created' => 'Triggered when a new project is created',
            'project.updated' => 'Triggered when a project is modified',
            'project.archived' => 'Triggered when a project is archived',
            'invoice.created' => 'Triggered when a new invoice is generated',
            'invoice.sent' => 'Triggered when an invoice is sent to a client',
            'invoice.paid' => 'Triggered when an invoice is marked as paid',
            'invoice.overdue' => 'Triggered when an invoice becomes overdue',
            'payment.received' => 'Triggered when a payment is successfully processed',
            'payment.refunded' => 'Triggered when a payment is refunded',
            'payment.failed' => 'Triggered when a payment attempt fails',
            'member.added' => 'Triggered when a new team member is added',
            'member.removed' => 'Triggered when a team member is removed',
            'member.role_changed' => 'Triggered when a member\'s role is updated',
            default => 'Unknown event',
        };
    }
}
