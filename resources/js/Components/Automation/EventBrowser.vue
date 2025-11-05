<script setup lang="ts">
import { ref, computed } from 'vue';
import type { WebhookEvents } from '../../composables/useWebhooks';

interface Props {
    events: WebhookEvents;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const searchQuery = ref('');
const copiedEvent = ref<string | null>(null);

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
            props.events[event].toLowerCase().includes(query) ||
            category.toLowerCase().includes(query)
        );
        if (matchingEvents.length > 0) {
            filtered[category] = matchingEvents;
        }
    });

    return filtered;
});

const totalEvents = computed(() => {
    return Object.keys(props.events).length;
});

const totalFilteredEvents = computed(() => {
    return Object.values(filteredCategories.value).reduce((sum, events) => sum + events.length, 0);
});

const getCategoryIcon = (category: string): string => {
    switch (category) {
        case 'Time Entries': return '⏱️';
        case 'Focus Sessions': return '🎯';
        case 'Projects': return '📁';
        case 'Tasks': return '✓';
        case 'Members': return '👥';
        case 'Reports': return '📊';
        case 'Timesheets': return '📅';
        case 'Invoices': return '💰';
        default: return '📌';
    }
};

const getCategoryColor = (category: string): string => {
    switch (category) {
        case 'Time Entries': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'Focus Sessions': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        case 'Projects': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'Tasks': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'Members': return 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300';
        case 'Reports': return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300';
        case 'Timesheets': return 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300';
        case 'Invoices': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const copyEventName = async (eventName: string) => {
    try {
        await navigator.clipboard.writeText(eventName);
        copiedEvent.value = eventName;
        setTimeout(() => {
            copiedEvent.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const isCopied = (eventName: string): boolean => {
    return copiedEvent.value === eventName;
};
</script>

<template>
    <div class="event-browser bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Available Events</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Browse all {{ totalEvents }} webhook events you can subscribe to
            </p>
        </div>

        <!-- Search -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search events by name or description..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                />
            </div>
            <p v-if="searchQuery" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ totalFilteredEvents }} of {{ totalEvents }} events
            </p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="px-6 py-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading events...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="totalFilteredEvents === 0" class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No events found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Try adjusting your search query
            </p>
        </div>

        <!-- Event Categories -->
        <div v-else class="px-6 py-4 space-y-6 max-h-[600px] overflow-y-auto">
            <div v-for="(eventList, category) in filteredCategories" :key="category">
                <div v-if="eventList.length > 0">
                    <!-- Category Header -->
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-2">{{ getCategoryIcon(category) }}</span>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ category }}
                        </h4>
                        <span
                            :class="getCategoryColor(category)"
                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        >
                            {{ eventList.length }} events
                        </span>
                    </div>

                    <!-- Events List -->
                    <div class="ml-8 space-y-2">
                        <div
                            v-for="event in eventList"
                            :key="event"
                            class="group flex items-start justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center">
                                    <code class="text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ event }}
                                    </code>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ events[event] }}
                                </p>
                            </div>
                            <button
                                @click="copyEventName(event)"
                                class="ml-4 flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                                :title="isCopied(event) ? 'Copied!' : 'Copy event name'"
                            >
                                <svg
                                    v-if="!isCopied(event)"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-5 w-5 text-green-600 dark:text-green-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                💡 <strong>Tip:</strong> Click the copy icon to copy an event name to your clipboard.
                Use these event names when creating or editing webhooks.
            </p>
        </div>
    </div>
</template>
