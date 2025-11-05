<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Webhook } from '../../composables/useWebhooks';

interface Props {
    webhooks: Webhook[];
    loading?: boolean;
}

interface Emits {
    (e: 'edit', webhook: Webhook): void;
    (e: 'delete', webhookId: string): void;
    (e: 'toggle', webhookId: string, isActive: boolean): void;
    (e: 'viewDeliveries', webhookId: string): void;
    (e: 'test', webhookId: string): void;
    (e: 'create'): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<Emits>();

const deletingWebhookId = ref<string | null>(null);
const showDeleteConfirm = ref(false);
const webhookToDelete = ref<Webhook | null>(null);

const getStatusColor = (webhook: Webhook): string => {
    if (!webhook.is_active) {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
    if (webhook.failure_count >= 5) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    }
    if (webhook.verification_status === 'verified') {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    }
    if (webhook.verification_status === 'pending') {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    }
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
};

const getStatusText = (webhook: Webhook): string => {
    if (!webhook.is_active) return 'Disabled';
    if (webhook.failure_count >= 5) return 'Failing';
    if (webhook.verification_status === 'verified') return 'Active';
    if (webhook.verification_status === 'pending') return 'Pending';
    return 'Failed';
};

const getHealthColor = (webhook: Webhook): string => {
    if (webhook.failure_count === 0) {
        return 'text-green-600 dark:text-green-400';
    }
    if (webhook.failure_count < 5) {
        return 'text-yellow-600 dark:text-yellow-400';
    }
    return 'text-red-600 dark:text-red-400';
};

const getHealthIcon = (webhook: Webhook): string => {
    if (webhook.failure_count === 0) return '✓';
    if (webhook.failure_count < 5) return '⚠';
    return '✗';
};

const formatDate = (date: string | null): string => {
    if (!date) return 'Never';
    return new Date(date).toLocaleString();
};

const formatUrl = (url: string): string => {
    try {
        const urlObj = new URL(url);
        return urlObj.hostname;
    } catch {
        return url;
    }
};

const confirmDelete = (webhook: Webhook) => {
    webhookToDelete.value = webhook;
    showDeleteConfirm.value = true;
};

const handleDelete = () => {
    if (webhookToDelete.value) {
        deletingWebhookId.value = webhookToDelete.value.id;
        emit('delete', webhookToDelete.value.id);
        showDeleteConfirm.value = false;
        webhookToDelete.value = null;
        deletingWebhookId.value = null;
    }
};

const cancelDelete = () => {
    webhookToDelete.value = null;
    showDeleteConfirm.value = false;
};

const handleToggle = (webhook: Webhook) => {
    emit('toggle', webhook.id, !webhook.is_active);
};

const handleTest = (webhook: Webhook) => {
    emit('test', webhook.id);
};
</script>

<template>
    <div class="webhooks-list bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Webhooks</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage webhook subscriptions for real-time event notifications
                </p>
            </div>
            <button
                @click="emit('create')"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium"
            >
                Create Webhook
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="px-6 py-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading webhooks...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="webhooks.length === 0" class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No webhooks</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating a new webhook subscription
            </p>
            <div class="mt-6">
                <button
                    @click="emit('create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Create Webhook
                </button>
            </div>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            URL
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Events
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Health
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Last Triggered
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="webhook in webhooks" :key="webhook.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ webhook.name }}
                            </div>
                            <div v-if="webhook.description" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ webhook.description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white font-mono">
                                {{ formatUrl(webhook.url) }}
                            </div>
                            <a :href="webhook.url" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                View full URL ↗
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="event in webhook.events.slice(0, 2)"
                                    :key="event"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ event }}
                                </span>
                                <span
                                    v-if="webhook.events.length > 2"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    +{{ webhook.events.length - 2 }} more
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="getStatusColor(webhook)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ getStatusText(webhook) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center" :class="getHealthColor(webhook)">
                                <span class="text-lg mr-1">{{ getHealthIcon(webhook) }}</span>
                                <span class="text-sm font-medium">
                                    {{ webhook.failure_count }} failures
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(webhook.last_triggered_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button
                                @click="handleToggle(webhook)"
                                :class="webhook.is_active ? 'text-gray-600 dark:text-gray-400' : 'text-green-600 dark:text-green-400'"
                                class="hover:underline"
                                :title="webhook.is_active ? 'Disable' : 'Enable'"
                            >
                                {{ webhook.is_active ? 'Disable' : 'Enable' }}
                            </button>
                            <button
                                @click="handleTest(webhook)"
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                                title="Send test event"
                            >
                                Test
                            </button>
                            <button
                                @click="emit('viewDeliveries', webhook.id)"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                title="View delivery logs"
                            >
                                Logs
                            </button>
                            <button
                                @click="emit('edit', webhook)"
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                                title="Edit webhook"
                            >
                                Edit
                            </button>
                            <button
                                @click="confirmDelete(webhook)"
                                :disabled="deletingWebhookId === webhook.id"
                                class="text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                title="Delete webhook"
                            >
                                {{ deletingWebhookId === webhook.id ? 'Deleting...' : 'Delete' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"
                    aria-hidden="true"
                    @click="cancelDelete"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Delete Webhook
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to delete "{{ webhookToDelete?.name }}"? This action cannot be undone and all delivery history will be lost.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="handleDelete"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Delete
                        </button>
                        <button
                            @click="cancelDelete"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
