<script setup lang="ts">
import type { TeamInsight } from '../../composables/useTeamAnalytics';

interface Props {
    insights: TeamInsight[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const getInsightIcon = (type: string): string => {
    switch (type) {
        case 'success':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'warning':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
        case 'tip':
            return 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z';
        case 'info':
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

const getInsightColor = (type: string): string => {
    switch (type) {
        case 'success':
            return 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800';
        case 'warning':
            return 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800';
        case 'tip':
            return 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800';
        case 'info':
            return 'bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-800';
        default:
            return 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-700';
    }
};

const getIconColor = (type: string): string => {
    switch (type) {
        case 'success':
            return 'text-green-600 dark:text-green-400';
        case 'warning':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'tip':
            return 'text-blue-600 dark:text-blue-400';
        case 'info':
            return 'text-purple-600 dark:text-purple-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getTitleColor = (type: string): string => {
    switch (type) {
        case 'success':
            return 'text-green-900 dark:text-green-300';
        case 'warning':
            return 'text-yellow-900 dark:text-yellow-300';
        case 'tip':
            return 'text-blue-900 dark:text-blue-300';
        case 'info':
            return 'text-purple-900 dark:text-purple-300';
        default:
            return 'text-gray-900 dark:text-gray-300';
    }
};

const getMessageColor = (type: string): string => {
    switch (type) {
        case 'success':
            return 'text-green-700 dark:text-green-400';
        case 'warning':
            return 'text-yellow-700 dark:text-yellow-400';
        case 'tip':
            return 'text-blue-700 dark:text-blue-400';
        case 'info':
            return 'text-purple-700 dark:text-purple-400';
        default:
            return 'text-gray-700 dark:text-gray-400';
    }
};
</script>

<template>
    <div class="team-insights bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Team Insights & Recommendations
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                AI-powered suggestions to improve team focus
            </p>
        </div>

        <div v-if="loading" class="p-6 space-y-4">
            <div v-for="i in 3" :key="i" class="animate-pulse">
                <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
            </div>
        </div>

        <div v-else-if="insights.length > 0" class="p-6 space-y-4">
            <div
                v-for="(insight, index) in insights"
                :key="index"
                class="border rounded-lg p-4 transition-all hover:shadow-md"
                :class="getInsightColor(insight.type)"
            >
                <div class="flex items-start space-x-3">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <svg
                            class="w-6 h-6"
                            :class="getIconColor(insight.type)"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="getInsightIcon(insight.type)"
                            />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold mb-1" :class="getTitleColor(insight.type)">
                            {{ insight.title }}
                        </h4>
                        <p class="text-sm" :class="getMessageColor(insight.type)">
                            {{ insight.message }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No insights available yet
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                We need more data to generate meaningful insights for your team
            </p>
        </div>

        <!-- Legend -->
        <div v-if="insights.length > 0" class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span>Success</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span>Tips</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span>Warnings</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                        <span>Info</span>
                    </div>
                </div>
                <span class="text-gray-500 dark:text-gray-500">
                    Insights are updated based on your team's activity patterns
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.team-insights {
    position: relative;
}
</style>
