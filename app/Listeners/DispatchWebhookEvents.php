<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Log;

class DispatchWebhookEvents
{
    public function __construct(
        protected WebhookDispatcher $dispatcher
    ) {
    }

    /**
     * Handle model created events
     */
    public function handleCreated($event): void
    {
        $this->dispatch($event, 'created');
    }

    /**
     * Handle model updated events
     */
    public function handleUpdated($event): void
    {
        $this->dispatch($event, 'updated');
    }

    /**
     * Handle model deleted events
     */
    public function handleDeleted($event): void
    {
        $this->dispatch($event, 'deleted');
    }

    /**
     * Dispatch webhook for the event
     */
    protected function dispatch($event, string $action): void
    {
        $model = $event->model ?? $event;
        $modelClass = get_class($model);

        // Map model class to event type
        $eventType = $this->getEventType($modelClass, $action, $model);

        if (! $eventType) {
            return; // No webhook event for this model/action
        }

        // Get organization ID
        $organizationId = $this->getOrganizationId($model);

        if (! $organizationId) {
            Log::warning("Cannot dispatch webhook: no organization ID", [
                'model' => $modelClass,
                'action' => $action,
            ]);

            return;
        }

        // Build payload
        $payload = $this->buildPayload($model, $action);

        // Dispatch to all subscribed webhooks
        try {
            $this->dispatcher->dispatch($eventType, $payload, $organizationId);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch webhook", [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get event type from model class and action
     */
    protected function getEventType(string $modelClass, string $action, $model): ?string
    {
        $mapping = [
            \App\Models\TimeEntry::class => 'time_entry',
            \App\Models\Project::class => 'project',
            \App\Models\Task::class => 'task',
            \App\Models\FocusSession::class => 'focus_session',
            \App\Models\Member::class => 'member',
        ];

        $baseType = $mapping[$modelClass] ?? null;

        if (! $baseType) {
            return null;
        }

        // Special cases
        if ($baseType === 'time_entry' && $action === 'updated') {
            // Check if this is a start or stop event
            if ($model->start && ! $model->end) {
                return 'time_entry.started';
            }
            if ($model->end) {
                return 'time_entry.stopped';
            }
        }

        if ($baseType === 'task' && $action === 'updated' && $model->completed_at) {
            return 'task.completed';
        }

        if ($baseType === 'focus_session' && $action === 'created') {
            return 'focus_session.detected';
        }

        if ($baseType === 'member') {
            return $action === 'created' ? 'member.added' : 'member.removed';
        }

        return "{$baseType}.{$action}";
    }

    /**
     * Get organization ID from model
     */
    protected function getOrganizationId($model): ?string
    {
        if (isset($model->organization_id)) {
            return $model->organization_id;
        }

        if (isset($model->user_id) && method_exists($model, 'user')) {
            $user = $model->user;

            return $user?->organization_id ?? null;
        }

        return null;
    }

    /**
     * Build webhook payload from model
     */
    protected function buildPayload($model, string $action): array
    {
        $payload = [
            'id' => $model->id,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ];

        // Add model-specific data
        $payload = array_merge($payload, $this->getModelData($model));

        return $payload;
    }

    /**
     * Get model-specific data for payload
     */
    protected function getModelData($model): array
    {
        $data = $model->toArray();

        // Remove sensitive fields
        unset($data['deleted_at'], $data['password'], $data['remember_token']);

        return $data;
    }
}
