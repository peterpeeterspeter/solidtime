<script setup lang="ts">
import { ref, computed } from 'vue';
import type { WebhookDelivery } from '../../composables/useWebhooks';

interface Props {
    deliveries: WebhookDelivery[];
    loading?: boolean;
    pagination?: {
        current_page: number;
        total_pages: number;
        per_page: number;
        total: number;
    };
}

interface Emits {
    (e: 'retry', deliveryId: string): void;
    (e: 'loadMore'): void;
    (e: 'refresh'): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    pagination: undefined,
});

const emit = defineEmits<Emits>();

const expandedRows = ref<Set<string>>(new Set());

const toggleRow = (deliveryId: string) => {
    if (expandedRows.value.has(deliveryId)) {
        expandedRows.value.delete(deliveryId);
    } else {
        expandedRows.value.add(deliveryId);
    }
};

const isRowExpanded = (deliveryId: string): boolean => {
    return expandedRows.value.has(deliveryId);
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'success':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'retrying':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'pending':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getStatusIcon = (status: string): string => {
    switch (status) {
        case 'success': return '✓';
        case 'failed': return '✗';
        case 'retrying': return '↻';
        case 'pending': return '○';
        default: return '?';
    }
};

const formatDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString();
};

const formatDuration = (ms: number | null): string => {
    if (ms === null) return 'N/A';
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(2)}s`;
};

const formatHttpStatus = (status: number | null): string => {
    if (status === null) return 'N/A';
    return status.toString();
};

const getHttpStatusColor = (status: number | null): string => {
    if (status === null) return 'text-gray-500 dark:text-gray-400';
    if (status >= 200 && status < 300) return 'text-green-600 dark:text-green-400';
    if (status >= 400 && status < 500) return 'text-yellow-600 dark:text-yellow-400';
    if (status >= 500) return 'text-red-600 dark:text-red-400';
    return 'text-gray-600 dark:text-gray-400';
};

const canRetry = (delivery: WebhookDelivery): boolean => {
    return delivery.status === 'failed' && delivery.attempt_number < delivery.max_attempts;
};

const formatPayload = (payload: any): string => {
    try {
        return JSON.stringify(payload, null, 2);
    } catch {
        return String(payload);
    }
};
</script>

<template>
    <div class="webhook-delivery-logs bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delivery Logs</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Detailed logs of webhook delivery attempts
                </p>
            </div>
            <button
                @click="emit('refresh')"
                :disabled="loading"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
                {{ loading ? 'Refreshing...' : 'Refresh' }}
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && deliveries.length === 0" class="px-6 py-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading delivery logs...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="deliveries.length === 0" class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No deliveries</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                This webhook hasn't been triggered yet
            </p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="w-8 px-6 py-3"></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Delivery ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Event Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            HTTP Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Duration
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Attempted At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-for="delivery in deliveries" :key="delivery.id">
                        <!-- Main Row -->
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    @click="toggleRow(delivery.id)"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                                >
                                    <svg
                                        class="h-5 w-5 transform transition-transform"
                                        :class="{ 'rotate-90': isRowExpanded(delivery.id) }"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs font-mono text-gray-900 dark:text-white">
                                    {{ delivery.delivery_id }}
                                </code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-white font-mono">
                                    {{ delivery.event_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="getStatusColor(delivery.status)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    <span class="mr-1">{{ getStatusIcon(delivery.status) }}</span>
                                    {{ delivery.status }}
                                </span>
                                <span v-if="delivery.attempt_number > 1" class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                    (Attempt {{ delivery.attempt_number }}/{{ delivery.max_attempts }})
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getHttpStatusColor(delivery.http_status_code)" class="text-sm font-medium">
                                    {{ formatHttpStatus(delivery.http_status_code) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ formatDuration(delivery.duration_ms) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(delivery.attempted_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    v-if="canRetry(delivery)"
                                    @click="emit('retry', delivery.id)"
                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    Retry
                                </button>
                            </td>
                        </tr>

                        <!-- Expanded Details Row -->
                        <tr v-if="isRowExpanded(delivery.id)" class="bg-gray-50 dark:bg-gray-900">
                            <td colspan="8" class="px-6 py-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Payload -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Payload</h4>
                                        <pre class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md p-3 text-xs overflow-x-auto"><code>{{ formatPayload(delivery.payload) }}</code></pre>
                                    </div>

                                    <!-- Response & Error -->
                                    <div class="space-y-4">
                                        <!-- Response Body -->
                                        <div v-if="delivery.response_body">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Response Body</h4>
                                            <pre class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md p-3 text-xs overflow-x-auto max-h-48"><code>{{ delivery.response_body }}</code></pre>
                                        </div>

                                        <!-- Error Message -->
                                        <div v-if="delivery.error_message">
                                            <h4 class="text-sm font-medium text-red-900 dark:text-red-400 mb-2">Error Message</h4>
                                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-600 rounded-md p-3 text-xs text-red-800 dark:text-red-300">
                                                {{ delivery.error_message }}
                                            </div>
                                        </div>

                                        <!-- Retry Info -->
                                        <div v-if="delivery.next_retry_at">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Next Retry</h4>
                                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-600 rounded-md p-3 text-xs text-yellow-800 dark:text-yellow-300">
                                                Scheduled for {{ formatDate(delivery.next_retry_at) }}
                                            </div>
                                        </div>

                                        <!-- Timestamps -->
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Timestamps</h4>
                                            <dl class="space-y-1 text-xs">
                                                <div class="flex justify-between">
                                                    <dt class="text-gray-500 dark:text-gray-400">Attempted:</dt>
                                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(delivery.attempted_at) }}</dd>
                                                </div>
                                                <div v-if="delivery.completed_at" class="flex justify-between">
                                                    <dt class="text-gray-500 dark:text-gray-400">Completed:</dt>
                                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(delivery.completed_at) }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="pagination && pagination.total_pages > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Page {{ pagination.current_page }} of {{ pagination.total_pages }} ({{ pagination.total }} total)
                </div>
                <button
                    v-if="pagination.current_page < pagination.total_pages"
                    @click="emit('loadMore')"
                    :disabled="loading"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    {{ loading ? 'Loading...' : 'Load More' }}
                </button>
            </div>
        </div>
    </div>
</template>
