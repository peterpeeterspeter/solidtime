<script setup lang="ts">
import { computed } from 'vue';
import type { FocusSession } from '../../composables/useFocusSessions';

interface Props {
    sessions: FocusSession[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    delete: [sessionId: string];
}>();

const getQualityBadgeClass = (quality: string): string => {
    switch (quality) {
        case 'excellent':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'good':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'fair':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'poor':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const isDeepWork = (session: FocusSession): boolean => {
    return session.duration_minutes >= 40 && session.focus_score >= 70 && session.app_switches < 3;
};

const formatTime = (timestamp: string): string => {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const formatDate = (timestamp: string): string => {
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const handleDelete = (sessionId: string) => {
    if (confirm('Are you sure you want to delete this focus session?')) {
        emit('delete', sessionId);
    }
};
</script>

<template>
    <div class="focus-session-list bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Focus Sessions
            </h3>
        </div>

        <div v-if="loading" class="p-6 space-y-4">
            <div v-for="i in 3" :key="i" class="animate-pulse flex space-x-4">
                <div class="flex-1 space-y-3 py-1">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="sessions.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
                v-for="session in sessions"
                :key="session.id"
                class="p-6 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Header -->
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatDate(session.start_time) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}
                            </span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                :class="getQualityBadgeClass(session.focus_quality)"
                            >
                                {{ session.focus_quality }}
                            </span>
                            <span
                                v-if="isDeepWork(session)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300"
                            >
                                🔥 Deep Work
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-4 gap-4 mb-3">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Duration</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ session.formatted_duration }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Focus Score</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ session.focus_score }}/100
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">App Switches</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ session.app_switches }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Unique Apps</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ session.unique_apps_count }}
                                </p>
                            </div>
                        </div>

                        <!-- Apps Used -->
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Primary App:</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                {{ session.primary_app }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="app in session.apps_used.slice(0, 5)"
                                    :key="app"
                                    class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ app }}
                                </span>
                                <span
                                    v-if="session.apps_used.length > 5"
                                    class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    +{{ session.apps_used.length - 5 }} more
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ml-4">
                        <button
                            @click="handleDelete(session.id)"
                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                            title="Delete session"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No focus sessions found for this period
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Focus sessions are automatically detected from your activity data
            </p>
        </div>
    </div>
</template>
