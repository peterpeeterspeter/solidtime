<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { Scope } from '../../composables/useApiKeys';

interface Props {
    show: boolean;
    scopes: Scope;
    organizationId: string;
    loading?: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'create', data: {
        name: string;
        description: string;
        scopes: string[];
        expires_at?: string;
    }): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<Emits>();

const name = ref('');
const description = ref('');
const selectedScopes = ref<string[]>([]);
const expiresEnabled = ref(false);
const expiresAt = ref('');
const errors = ref<Record<string, string>>({});

const scopeCategories = computed(() => {
    const categories: Record<string, string[]> = {
        'Full Access': [],
        'Time Entries': [],
        'Projects': [],
        'Tasks': [],
        'Focus Sessions': [],
        'Reports': [],
        'Webhooks': [],
    };

    Object.keys(props.scopes).forEach(scope => {
        if (scope === '*') {
            categories['Full Access'].push(scope);
        } else if (scope.startsWith('time_entries')) {
            categories['Time Entries'].push(scope);
        } else if (scope.startsWith('projects')) {
            categories['Projects'].push(scope);
        } else if (scope.startsWith('tasks')) {
            categories['Tasks'].push(scope);
        } else if (scope.startsWith('focus_sessions')) {
            categories['Focus Sessions'].push(scope);
        } else if (scope.startsWith('reports')) {
            categories['Reports'].push(scope);
        } else if (scope.startsWith('webhooks')) {
            categories['Webhooks'].push(scope);
        }
    });

    return categories;
});

const isValid = computed(() => {
    return name.value.trim().length > 0 && selectedScopes.value.length > 0;
});

const handleSubmit = () => {
    errors.value = {};

    if (!name.value.trim()) {
        errors.value.name = 'Name is required';
        return;
    }

    if (selectedScopes.value.length === 0) {
        errors.value.scopes = 'At least one scope is required';
        return;
    }

    emit('create', {
        name: name.value.trim(),
        description: description.value.trim() || '',
        scopes: selectedScopes.value,
        expires_at: expiresEnabled.value && expiresAt.value ? expiresAt.value : undefined,
    });
};

const handleClose = () => {
    name.value = '';
    description.value = '';
    selectedScopes.value = [];
    expiresEnabled.value = false;
    expiresAt.value = '';
    errors.value = {};
    emit('close');
};

// Reset form when modal is closed
watch(() => props.show, (newValue) => {
    if (!newValue) {
        // Reset after animation
        setTimeout(() => {
            name.value = '';
            description.value = '';
            selectedScopes.value = [];
            expiresEnabled.value = false;
            expiresAt.value = '';
            errors.value = {};
        }, 300);
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"
                aria-hidden="true"
                @click="handleClose"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
            >
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Create API Key
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Create a new API key for external integrations like n8n. The key will be shown once.
                            </p>

                            <form @submit.prevent="handleSubmit" class="mt-6 space-y-6">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Name *
                                    </label>
                                    <input
                                        id="name"
                                        v-model="name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="e.g., n8n Production"
                                    />
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="description"
                                        rows="2"
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="Purpose of this API key"
                                    ></textarea>
                                </div>

                                <!-- Scopes -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Scopes * (Select at least one)
                                    </label>
                                    <div class="space-y-4 max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-4">
                                        <div v-for="(scopeList, category) in scopeCategories" :key="category">
                                            <div v-if="scopeList.length > 0">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                                    {{ category }}
                                                </h4>
                                                <div class="space-y-2">
                                                    <div v-for="scope in scopeList" :key="scope" class="flex items-start">
                                                        <input
                                                            :id="`scope-${scope}`"
                                                            v-model="selectedScopes"
                                                            :value="scope"
                                                            type="checkbox"
                                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                                        />
                                                        <label :for="`scope-${scope}`" class="ml-3 text-sm">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ scope }}</span>
                                                            <span class="text-gray-500 dark:text-gray-400 block">
                                                                {{ scopes[scope] }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="errors.scopes" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.scopes }}
                                    </p>
                                </div>

                                <!-- Expiration -->
                                <div>
                                    <div class="flex items-center">
                                        <input
                                            id="expires-enabled"
                                            v-model="expiresEnabled"
                                            type="checkbox"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                        />
                                        <label for="expires-enabled" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Set expiration date
                                        </label>
                                    </div>
                                    <input
                                        v-if="expiresEnabled"
                                        v-model="expiresAt"
                                        type="datetime-local"
                                        class="mt-2 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="handleSubmit"
                        :disabled="!isValid || loading"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ loading ? 'Creating...' : 'Create API Key' }}
                    </button>
                    <button
                        @click="handleClose"
                        :disabled="loading"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
