<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { WebhookEvents } from '../../composables/useWebhooks';

interface Props {
    show: boolean;
    events: WebhookEvents;
    organizationId: string;
    loading?: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'create', data: {
        name: string;
        description: string;
        url: string;
        events: string[];
        secret?: string;
    }): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<Emits>();

const name = ref('');
const description = ref('');
const url = ref('');
const selectedEvents = ref<string[]>([]);
const generateSecret = ref(true);
const errors = ref<Record<string, string>>({});
const searchQuery = ref('');

const eventCategories = computed(() => {
    const categories: Record<string, string[]> = {
        'Time Entries': [],
        'Focus Sessions': [],
        'Projects': [],
        'Tasks': [],
        'Members': [],
        'Reports': [],
        'Timesheets': [],
        'Invoices': [],
    };

    Object.keys(props.events).forEach(event => {
        if (event.startsWith('time_entry')) {
            categories['Time Entries'].push(event);
        } else if (event.startsWith('focus_session')) {
            categories['Focus Sessions'].push(event);
        } else if (event.startsWith('project')) {
            categories['Projects'].push(event);
        } else if (event.startsWith('task')) {
            categories['Tasks'].push(event);
        } else if (event.startsWith('member')) {
            categories['Members'].push(event);
        } else if (event.startsWith('report')) {
            categories['Reports'].push(event);
        } else if (event.startsWith('timesheet')) {
            categories['Timesheets'].push(event);
        } else if (event.startsWith('invoice')) {
            categories['Invoices'].push(event);
        }
    });

    return categories;
});

const filteredCategories = computed(() => {
    if (!searchQuery.value) return eventCategories.value;

    const filtered: Record<string, string[]> = {};
    const query = searchQuery.value.toLowerCase();

    Object.entries(eventCategories.value).forEach(([category, events]) => {
        const matchingEvents = events.filter(event =>
            event.toLowerCase().includes(query) ||
            props.events[event].toLowerCase().includes(query)
        );
        if (matchingEvents.length > 0) {
            filtered[category] = matchingEvents;
        }
    });

    return filtered;
});

const isValid = computed(() => {
    return name.value.trim().length > 0 &&
           url.value.trim().length > 0 &&
           selectedEvents.value.length > 0 &&
           isValidUrl(url.value);
});

const isValidUrl = (urlString: string): boolean => {
    try {
        const urlObj = new URL(urlString);
        return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
    } catch {
        return false;
    }
};

const toggleCategory = (category: string) => {
    const categoryEvents = eventCategories.value[category];
    const allSelected = categoryEvents.every(event => selectedEvents.value.includes(event));

    if (allSelected) {
        // Deselect all
        selectedEvents.value = selectedEvents.value.filter(event => !categoryEvents.includes(event));
    } else {
        // Select all
        const newEvents = categoryEvents.filter(event => !selectedEvents.value.includes(event));
        selectedEvents.value = [...selectedEvents.value, ...newEvents];
    }
};

const isCategorySelected = (category: string): boolean => {
    const categoryEvents = eventCategories.value[category];
    return categoryEvents.every(event => selectedEvents.value.includes(event));
};

const isCategoryPartiallySelected = (category: string): boolean => {
    const categoryEvents = eventCategories.value[category];
    const selectedCount = categoryEvents.filter(event => selectedEvents.value.includes(event)).length;
    return selectedCount > 0 && selectedCount < categoryEvents.length;
};

const handleSubmit = () => {
    errors.value = {};

    if (!name.value.trim()) {
        errors.value.name = 'Name is required';
        return;
    }

    if (!url.value.trim()) {
        errors.value.url = 'URL is required';
        return;
    }

    if (!isValidUrl(url.value)) {
        errors.value.url = 'Please enter a valid HTTP or HTTPS URL';
        return;
    }

    if (selectedEvents.value.length === 0) {
        errors.value.events = 'At least one event is required';
        return;
    }

    emit('create', {
        name: name.value.trim(),
        description: description.value.trim() || '',
        url: url.value.trim(),
        events: selectedEvents.value,
        secret: generateSecret.value ? undefined : '', // undefined = auto-generate, '' = no secret
    });
};

const handleClose = () => {
    name.value = '';
    description.value = '';
    url.value = '';
    selectedEvents.value = [];
    generateSecret.value = true;
    searchQuery.value = '';
    errors.value = {};
    emit('close');
};

// Reset form when modal is closed
watch(() => props.show, (newValue) => {
    if (!newValue) {
        setTimeout(() => {
            name.value = '';
            description.value = '';
            url.value = '';
            selectedEvents.value = [];
            generateSecret.value = true;
            searchQuery.value = '';
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
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full"
            >
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Create Webhook
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Create a new webhook to receive real-time event notifications
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
                                        placeholder="e.g., n8n Workflow Integration"
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
                                        placeholder="Purpose of this webhook"
                                    ></textarea>
                                </div>

                                <!-- URL -->
                                <div>
                                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Webhook URL *
                                    </label>
                                    <input
                                        id="url"
                                        v-model="url"
                                        type="url"
                                        required
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                                        placeholder="https://n8n.example.com/webhook/..."
                                    />
                                    <p v-if="errors.url" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.url }}
                                    </p>
                                    <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        The endpoint that will receive webhook events
                                    </p>
                                </div>

                                <!-- Events -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Events * (Select at least one)
                                    </label>

                                    <!-- Search -->
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search events..."
                                        class="mb-3 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />

                                    <div class="space-y-4 max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-4">
                                        <div v-for="(eventList, category) in filteredCategories" :key="category">
                                            <div v-if="eventList.length > 0">
                                                <!-- Category Header with Select All -->
                                                <div class="flex items-center mb-2">
                                                    <input
                                                        :id="`category-${category}`"
                                                        type="checkbox"
                                                        :checked="isCategorySelected(category)"
                                                        :indeterminate="isCategoryPartiallySelected(category)"
                                                        @change="toggleCategory(category)"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                                    />
                                                    <label :for="`category-${category}`" class="ml-2 text-sm font-medium text-gray-900 dark:text-white cursor-pointer">
                                                        {{ category }}
                                                    </label>
                                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                        ({{ eventList.filter(e => selectedEvents.includes(e)).length }}/{{ eventList.length }})
                                                    </span>
                                                </div>

                                                <!-- Events in Category -->
                                                <div class="ml-6 space-y-2">
                                                    <div v-for="event in eventList" :key="event" class="flex items-start">
                                                        <input
                                                            :id="`event-${event}`"
                                                            v-model="selectedEvents"
                                                            :value="event"
                                                            type="checkbox"
                                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                                        />
                                                        <label :for="`event-${event}`" class="ml-3 text-sm cursor-pointer">
                                                            <span class="font-medium text-gray-900 dark:text-white font-mono">{{ event }}</span>
                                                            <span class="text-gray-500 dark:text-gray-400 block">
                                                                {{ events[event] }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="errors.events" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.events }}
                                    </p>
                                    <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ selectedEvents.length }} event(s) selected
                                    </p>
                                </div>

                                <!-- Secret Generation -->
                                <div>
                                    <div class="flex items-center">
                                        <input
                                            id="generate-secret"
                                            v-model="generateSecret"
                                            type="checkbox"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                        />
                                        <label for="generate-secret" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Generate webhook secret (recommended)
                                        </label>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 ml-6">
                                        A secret will be used to sign webhook payloads with HMAC-SHA256 for security
                                    </p>
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
                        {{ loading ? 'Creating...' : 'Create Webhook' }}
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
