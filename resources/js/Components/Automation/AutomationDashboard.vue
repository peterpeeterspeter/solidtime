<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useApiKeys } from '../../composables/useApiKeys';
import { useWebhooks } from '../../composables/useWebhooks';
import ApiKeysList from './ApiKeysList.vue';
import CreateApiKeyModal from './CreateApiKeyModal.vue';
import WebhooksList from './WebhooksList.vue';
import CreateWebhookModal from './CreateWebhookModal.vue';
import WebhookDeliveryLogs from './WebhookDeliveryLogs.vue';
import EventBrowser from './EventBrowser.vue';
import type { Webhook } from '../../composables/useWebhooks';

interface Props {
    organizationId: string;
}

const props = defineProps<Props>();

// Composables
const {
    apiKeys,
    scopes,
    loading: apiKeysLoading,
    error: apiKeysError,
    fetchApiKeys,
    fetchScopes,
    createApiKey,
    revokeApiKey,
} = useApiKeys();

const {
    webhooks,
    deliveries,
    availableEvents,
    loading: webhooksLoading,
    error: webhooksError,
    pagination,
    fetchWebhooks,
    fetchEvents,
    createWebhook,
    updateWebhook,
    deleteWebhook,
    testWebhook,
    fetchDeliveries,
    retryDelivery,
} = useWebhooks();

// UI State
const activeTab = ref<'api-keys' | 'webhooks' | 'events' | 'deliveries'>('api-keys');
const showCreateApiKeyModal = ref(false);
const showCreateWebhookModal = ref(false);
const newApiKey = ref<string | null>(null);
const showNewApiKeyModal = ref(false);
const notification = ref<{ type: 'success' | 'error'; message: string } | null>(null);
const selectedWebhookId = ref<string | null>(null);

// Initialize
onMounted(async () => {
    await Promise.all([
        fetchApiKeys(props.organizationId),
        fetchScopes(),
        fetchWebhooks(props.organizationId),
        fetchEvents(),
    ]);
});

// Watch organization changes
watch(() => props.organizationId, async (newOrgId) => {
    await Promise.all([
        fetchApiKeys(newOrgId),
        fetchWebhooks(newOrgId),
    ]);
});

// Notifications
const showNotification = (type: 'success' | 'error', message: string) => {
    notification.value = { type, message };
    setTimeout(() => {
        notification.value = null;
    }, 5000);
};

// API Keys Handlers
const handleCreateApiKey = async (data: any) => {
    const result = await createApiKey({
        organization_id: props.organizationId,
        ...data,
    });

    if (result) {
        newApiKey.value = result.key;
        showCreateApiKeyModal.value = false;
        showNewApiKeyModal.value = true;
        showNotification('success', 'API key created successfully');
    } else if (apiKeysError.value) {
        showNotification('error', apiKeysError.value);
    }
};

const handleRevokeApiKey = async (apiKeyId: string) => {
    const success = await revokeApiKey(apiKeyId);
    if (success) {
        showNotification('success', 'API key revoked successfully');
    } else if (apiKeysError.value) {
        showNotification('error', apiKeysError.value);
    }
};

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('success', 'Copied to clipboard');
    } catch (err) {
        showNotification('error', 'Failed to copy to clipboard');
    }
};

// Webhooks Handlers
const handleCreateWebhook = async (data: any) => {
    const result = await createWebhook({
        organization_id: props.organizationId,
        ...data,
    });

    if (result) {
        showCreateWebhookModal.value = false;
        showNotification('success', 'Webhook created successfully');
    } else if (webhooksError.value) {
        showNotification('error', webhooksError.value);
    }
};

const handleToggleWebhook = async (webhookId: string, isActive: boolean) => {
    const success = await updateWebhook(webhookId, { is_active: isActive });
    if (success) {
        showNotification('success', `Webhook ${isActive ? 'enabled' : 'disabled'} successfully`);
    } else if (webhooksError.value) {
        showNotification('error', webhooksError.value);
    }
};

const handleDeleteWebhook = async (webhookId: string) => {
    const success = await deleteWebhook(webhookId);
    if (success) {
        showNotification('success', 'Webhook deleted successfully');
    } else if (webhooksError.value) {
        showNotification('error', webhooksError.value);
    }
};

const handleTestWebhook = async (webhookId: string) => {
    const result = await testWebhook(webhookId);
    if (result) {
        showNotification('success', 'Test webhook sent successfully');
    } else if (webhooksError.value) {
        showNotification('error', webhooksError.value);
    }
};

const handleViewDeliveries = async (webhookId: string) => {
    selectedWebhookId.value = webhookId;
    activeTab.value = 'deliveries';
    await fetchDeliveries(webhookId);
};

const handleRetryDelivery = async (deliveryId: string) => {
    if (!selectedWebhookId.value) return;
    const success = await retryDelivery(selectedWebhookId.value, deliveryId);
    if (success) {
        showNotification('success', 'Delivery retry initiated');
        await fetchDeliveries(selectedWebhookId.value);
    } else if (webhooksError.value) {
        showNotification('error', webhooksError.value);
    }
};

const handleRefreshDeliveries = async () => {
    if (!selectedWebhookId.value) return;
    await fetchDeliveries(selectedWebhookId.value);
};

const handleLoadMoreDeliveries = async () => {
    if (!selectedWebhookId.value || !pagination.value) return;
    await fetchDeliveries(selectedWebhookId.value, pagination.value.current_page + 1);
};
</script>

<template>
    <div class="automation-dashboard">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Automation & Integrations</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Manage API keys, webhooks, and n8n integrations for workflow automation
            </p>
        </div>

        <!-- Notification Toast -->
        <div
            v-if="notification"
            class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg
                            v-if="notification.type === 'success'"
                            class="h-6 w-6 text-green-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg
                            v-else
                            class="h-6 w-6 text-red-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ notification.message }}
                        </p>
                    </div>
                    <button
                        @click="notification = null"
                        class="ml-4 inline-flex text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button
                    @click="activeTab = 'api-keys'"
                    :class="[
                        activeTab === 'api-keys'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                    ]"
                >
                    API Keys
                </button>
                <button
                    @click="activeTab = 'webhooks'"
                    :class="[
                        activeTab === 'webhooks'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                    ]"
                >
                    Webhooks
                </button>
                <button
                    @click="activeTab = 'events'"
                    :class="[
                        activeTab === 'events'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                    ]"
                >
                    Available Events
                </button>
                <button
                    v-if="selectedWebhookId"
                    @click="activeTab = 'deliveries'"
                    :class="[
                        activeTab === 'deliveries'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                    ]"
                >
                    Delivery Logs
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div>
            <!-- API Keys Tab -->
            <div v-show="activeTab === 'api-keys'">
                <ApiKeysList
                    :api-keys="apiKeys"
                    :loading="apiKeysLoading"
                    @revoke="handleRevokeApiKey"
                    @create="showCreateApiKeyModal = true"
                />
            </div>

            <!-- Webhooks Tab -->
            <div v-show="activeTab === 'webhooks'">
                <WebhooksList
                    :webhooks="webhooks"
                    :loading="webhooksLoading"
                    @edit="() => {}"
                    @delete="handleDeleteWebhook"
                    @toggle="handleToggleWebhook"
                    @view-deliveries="handleViewDeliveries"
                    @test="handleTestWebhook"
                    @create="showCreateWebhookModal = true"
                />
            </div>

            <!-- Events Tab -->
            <div v-show="activeTab === 'events'">
                <EventBrowser
                    :events="availableEvents"
                    :loading="webhooksLoading"
                />
            </div>

            <!-- Deliveries Tab -->
            <div v-show="activeTab === 'deliveries'">
                <WebhookDeliveryLogs
                    :deliveries="deliveries"
                    :loading="webhooksLoading"
                    :pagination="pagination"
                    @retry="handleRetryDelivery"
                    @load-more="handleLoadMoreDeliveries"
                    @refresh="handleRefreshDeliveries"
                />
            </div>
        </div>

        <!-- Create API Key Modal -->
        <CreateApiKeyModal
            :show="showCreateApiKeyModal"
            :scopes="scopes"
            :organization-id="organizationId"
            :loading="apiKeysLoading"
            @close="showCreateApiKeyModal = false"
            @create="handleCreateApiKey"
        />

        <!-- New API Key Display Modal -->
        <div
            v-if="showNewApiKeyModal && newApiKey"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    API Key Created Successfully
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-red-600 dark:text-red-400 font-semibold mb-2">
                                        ⚠️ Save this key securely. It will not be shown again!
                                    </p>
                                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md p-3">
                                        <code class="text-sm font-mono text-gray-900 dark:text-white break-all">
                                            {{ newApiKey }}
                                        </code>
                                    </div>
                                    <button
                                        @click="copyToClipboard(newApiKey)"
                                        class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Copy to Clipboard
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="showNewApiKeyModal = false; newApiKey = null"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            I've Saved the Key
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Webhook Modal -->
        <CreateWebhookModal
            :show="showCreateWebhookModal"
            :events="availableEvents"
            :organization-id="organizationId"
            :loading="webhooksLoading"
            @close="showCreateWebhookModal = false"
            @create="handleCreateWebhook"
        />
    </div>
</template>
