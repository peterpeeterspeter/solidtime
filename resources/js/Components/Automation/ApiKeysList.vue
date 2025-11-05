<script setup lang="ts">
import { computed } from 'vue';
import type { ApiKey } from '../../composables/useApiKeys';

interface Props {
    apiKeys: ApiKey[];
    loading?: boolean;
}

interface Emits {
    (e: 'revoke', apiKeyId: string): void;
    (e: 'create'): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<Emits>();

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const isExpired = (expiresAt: string | null): boolean => {
    if (!expiresAt) return false;
    return new Date(expiresAt) < new Date();
};

const getStatusColor = (apiKey: ApiKey): string => {
    if (!apiKey.is_active) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    if (isExpired(apiKey.expires_at)) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
};

const getStatusText = (apiKey: ApiKey): string => {
    if (!apiKey.is_active) return 'Revoked';
    if (isExpired(apiKey.expires_at)) return 'Expired';
    return 'Active';
};

const confirmRevoke = (apiKey: ApiKey) => {
    if (confirm(`Are you sure you want to revoke the API key "${apiKey.name}"? This action cannot be undone.`)) {
        emit('revoke', apiKey.id);
    }
};
</script>

<template>
    <div class="api-keys-list bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    API Keys
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Manage API keys for external integrations like n8n
                </p>
            </div>
            <button
                @click="emit('create')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                Create API Key
            </button>
        </div>

        <div v-if="loading" class="p-6">
            <div v-for="i in 3" :key="i" class="animate-pulse mb-4 last:mb-0">
                <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>

        <div v-else-if="apiKeys.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Key Prefix
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Scopes
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Last Used
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Usage
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="apiKey in apiKeys" :key="apiKey.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ apiKey.name }}
                            </div>
                            <div v-if="apiKey.description" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ apiKey.description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="px-2 py-1 bg-gray-100 dark:bg-gray-900 rounded text-sm font-mono text-gray-900 dark:text-white">
                                {{ apiKey.key_prefix }}...
                            </code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="scope in apiKey.scopes.slice(0, 3)"
                                    :key="scope"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    {{ scope }}
                                </span>
                                <span
                                    v-if="apiKey.scopes.length > 3"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    +{{ apiKey.scopes.length - 3 }} more
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusColor(apiKey)"
                            >
                                {{ getStatusText(apiKey) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(apiKey.last_used_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ apiKey.usage_count.toLocaleString() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                @click="confirmRevoke(apiKey)"
                                :disabled="!apiKey.is_active"
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Revoke
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No API keys</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first API key for n8n or other integrations.
            </p>
            <div class="mt-6">
                <button
                    @click="emit('create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                    Create API Key
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.api-keys-list {
    position: relative;
}
</style>
