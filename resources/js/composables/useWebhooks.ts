import { ref, type Ref } from 'vue';

/**
 * Webhooks Composable
 * 
 * Provides state management and API calls for webhook management
 */

export interface Webhook {
    id: string;
    name: string;
    description: string | null;
    url: string;
    events: string[];
    filters: any | null;
    is_active: boolean;
    failure_count: number;
    last_triggered_at: string | null;
    last_success_at: string | null;
    last_failure_at: string | null;
    last_error: string | null;
    verification_status: 'pending' | 'verified' | 'failed';
    verified_at: string | null;
    created_at: string;
    created_by: {
        id: string;
        name: string;
    };
}

export interface CreateWebhookRequest {
    organization_id: string;
    name: string;
    description?: string;
    url: string;
    events: string[];
    filters?: any;
    secret?: string;
}

export interface WebhookDelivery {
    id: string;
    delivery_id: string;
    event_type: string;
    payload: any;
    status: 'pending' | 'success' | 'failed' | 'retrying';
    http_status_code: number | null;
    response_body: string | null;
    error_message: string | null;
    attempted_at: string;
    completed_at: string | null;
    duration_ms: number | null;
    attempt_number: number;
    max_attempts: number;
    next_retry_at: string | null;
}

export interface WebhookEvents {
    [key: string]: string;
}

export function useWebhooks() {
    const webhooks: Ref<Webhook[]> = ref([]);
    const deliveries: Ref<WebhookDelivery[]> = ref([]);
    const availableEvents: Ref<WebhookEvents> = ref({});
    const loading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);

    /**
     * Fetch all webhooks for organization
     */
    async function fetchWebhooks(organizationId: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(
                `/api/v1/webhooks?organization_id=${organizationId}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch webhooks: ${response.statusText}`);
            }

            const data = await response.json();
            webhooks.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching webhooks:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch available webhook events
     */
    async function fetchEvents(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/webhooks/events', {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch events: ${response.statusText}`);
            }

            const data = await response.json();
            availableEvents.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching events:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Create a new webhook
     */
    async function createWebhook(
        request: CreateWebhookRequest
    ): Promise<any | null> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/webhooks', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(request),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create webhook');
            }

            const data = await response.json();
            
            // Refresh the list
            await fetchWebhooks(request.organization_id);
            
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error creating webhook:', err);
            return null;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Update webhook
     */
    async function updateWebhook(
        webhookId: string,
        updates: Partial<CreateWebhookRequest>,
        organizationId: string
    ): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/webhooks/${webhookId}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(updates),
            });

            if (!response.ok) {
                throw new Error('Failed to update webhook');
            }

            // Refresh the list
            await fetchWebhooks(organizationId);
            
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error updating webhook:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Delete webhook
     */
    async function deleteWebhook(
        webhookId: string,
        organizationId: string
    ): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/webhooks/${webhookId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to delete webhook');
            }

            // Refresh the list
            await fetchWebhooks(organizationId);
            
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error deleting webhook:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Test webhook
     */
    async function testWebhook(webhookId: string): Promise<any | null> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/webhooks/${webhookId}/test`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to test webhook');
            }

            const data = await response.json();
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error testing webhook:', err);
            return null;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch delivery logs for a webhook
     */
    async function fetchDeliveries(
        webhookId: string,
        page: number = 1,
        perPage: number = 50
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(
                `/api/v1/webhooks/${webhookId}/deliveries?page=${page}&per_page=${perPage}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch deliveries: ${response.statusText}`);
            }

            const data = await response.json();
            deliveries.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching deliveries:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Retry a failed delivery
     */
    async function retryDelivery(
        webhookId: string,
        deliveryId: string
    ): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(
                `/api/v1/webhooks/${webhookId}/deliveries/${deliveryId}/retry`,
                {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error('Failed to retry delivery');
            }

            // Refresh deliveries
            await fetchDeliveries(webhookId);
            
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error retrying delivery:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Reset state
     */
    function reset(): void {
        webhooks.value = [];
        deliveries.value = [];
        availableEvents.value = {};
        loading.value = false;
        error.value = null;
    }

    return {
        // State
        webhooks,
        deliveries,
        availableEvents,
        loading,
        error,

        // Methods
        fetchWebhooks,
        fetchEvents,
        createWebhook,
        updateWebhook,
        deleteWebhook,
        testWebhook,
        fetchDeliveries,
        retryDelivery,
        reset,
    };
}
