import { ref, type Ref } from 'vue';

/**
 * API Keys Composable
 * 
 * Provides state management and API calls for API key management
 */

export interface ApiKey {
    id: string;
    name: string;
    description: string | null;
    key_prefix: string;
    scopes: string[];
    is_active: boolean;
    last_used_at: string | null;
    last_used_ip: string | null;
    usage_count: number;
    expires_at: string | null;
    created_at: string;
    created_by: {
        id: string;
        name: string;
    };
}

export interface CreateApiKeyRequest {
    organization_id: string;
    name: string;
    description?: string;
    scopes: string[];
    expires_at?: string;
}

export interface CreateApiKeyResponse {
    id: string;
    name: string;
    description: string | null;
    key: string; // Plain key - only shown once!
    key_prefix: string;
    scopes: string[];
    expires_at: string | null;
    created_at: string;
}

export interface Scope {
    [key: string]: string;
}

export function useApiKeys() {
    const apiKeys: Ref<ApiKey[]> = ref([]);
    const scopes: Ref<Scope> = ref({});
    const loading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);

    /**
     * Fetch all API keys for organization
     */
    async function fetchApiKeys(organizationId: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(
                `/api/v1/api-keys?organization_id=${organizationId}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch API keys: ${response.statusText}`);
            }

            const data = await response.json();
            apiKeys.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching API keys:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch available scopes
     */
    async function fetchScopes(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/api-keys/scopes', {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch scopes: ${response.statusText}`);
            }

            const data = await response.json();
            scopes.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching scopes:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Create a new API key
     */
    async function createApiKey(
        request: CreateApiKeyRequest
    ): Promise<CreateApiKeyResponse | null> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/api-keys', {
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
                throw new Error(errorData.message || 'Failed to create API key');
            }

            const data = await response.json();
            
            // Refresh the list
            await fetchApiKeys(request.organization_id);
            
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error creating API key:', err);
            return null;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Update API key scopes
     */
    async function updateApiKey(
        apiKeyId: string,
        scopes: string[],
        organizationId: string
    ): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/api-keys/${apiKeyId}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ scopes }),
            });

            if (!response.ok) {
                throw new Error('Failed to update API key');
            }

            // Refresh the list
            await fetchApiKeys(organizationId);
            
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error updating API key:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Revoke (delete) an API key
     */
    async function revokeApiKey(
        apiKeyId: string,
        organizationId: string
    ): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/api-keys/${apiKeyId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to revoke API key');
            }

            // Refresh the list
            await fetchApiKeys(organizationId);
            
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error revoking API key:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Reset state
     */
    function reset(): void {
        apiKeys.value = [];
        scopes.value = {};
        loading.value = false;
        error.value = null;
    }

    return {
        // State
        apiKeys,
        scopes,
        loading,
        error,

        // Methods
        fetchApiKeys,
        fetchScopes,
        createApiKey,
        updateApiKey,
        revokeApiKey,
        reset,
    };
}
