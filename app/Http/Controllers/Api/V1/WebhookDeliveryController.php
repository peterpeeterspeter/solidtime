<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookDeliveryController extends Controller
{
    public function __construct(
        protected WebhookDispatcher $dispatcher
    ) {
    }

    /**
     * List deliveries for a webhook
     */
    public function index(Request $request, string $webhookId): JsonResponse
    {
        $webhook = Webhook::find($webhookId);

        if (! $webhook) {
            return response()->json([
                'error' => 'Webhook not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $status = $request->input('status');

        $query = $webhook->deliveries()->orderByDesc('attempted_at');

        if ($status) {
            $query->where('status', $status);
        }

        $deliveries = $query->paginate($perPage);

        return response()->json([
            'data' => $deliveries->items(),
            'pagination' => [
                'current_page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }

    /**
     * Show a specific delivery
     */
    public function show(Request $request, string $webhookId, string $deliveryId): JsonResponse
    {
        $webhook = Webhook::find($webhookId);

        if (! $webhook) {
            return response()->json([
                'error' => 'Webhook not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $delivery = WebhookDelivery::where('webhook_id', $webhookId)
            ->where('id', $deliveryId)
            ->first();

        if (! $delivery) {
            return response()->json([
                'error' => 'Delivery not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $delivery->id,
                'delivery_id' => $delivery->delivery_id,
                'event_type' => $delivery->event_type,
                'payload' => $delivery->payload,
                'status' => $delivery->status,
                'http_status_code' => $delivery->http_status_code,
                'response_body' => $delivery->response_body,
                'error_message' => $delivery->error_message,
                'attempted_at' => $delivery->attempted_at->toIso8601String(),
                'completed_at' => $delivery->completed_at?->toIso8601String(),
                'duration_ms' => $delivery->duration_ms,
                'attempt_number' => $delivery->attempt_number,
                'max_attempts' => $delivery->max_attempts,
                'next_retry_at' => $delivery->next_retry_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retry a failed delivery
     */
    public function retry(Request $request, string $webhookId, string $deliveryId): JsonResponse
    {
        $webhook = Webhook::find($webhookId);

        if (! $webhook) {
            return response()->json([
                'error' => 'Webhook not found',
            ], 404);
        }

        $user = $request->user();

        if (! $this->userHasAccessToOrganization($user->id, $webhook->organization_id)) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $delivery = WebhookDelivery::where('webhook_id', $webhookId)
            ->where('id', $deliveryId)
            ->first();

        if (! $delivery) {
            return response()->json([
                'error' => 'Delivery not found',
            ], 404);
        }

        if (! in_array($delivery->status, ['failed', 'retrying'])) {
            return response()->json([
                'error' => 'Cannot retry',
                'message' => 'Only failed or retrying deliveries can be retried',
            ], 400);
        }

        $this->dispatcher->retry($delivery);

        $delivery->refresh();

        return response()->json([
            'data' => [
                'id' => $delivery->id,
                'delivery_id' => $delivery->delivery_id,
                'status' => $delivery->status,
                'attempt_number' => $delivery->attempt_number,
                'error_message' => $delivery->error_message,
            ],
            'message' => 'Delivery retry initiated',
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
