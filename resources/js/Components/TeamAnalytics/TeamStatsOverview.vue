<script setup lang="ts">
import { computed } from 'vue';
import type { TeamStats } from '../../composables/useTeamAnalytics';

interface Props {
    stats: TeamStats | null;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const focusHours = computed(() => {
    if (!props.stats) return '0h';
    const hours = Math.floor(props.stats.total_team_focus_hours);
    const minutes = Math.round((props.stats.total_team_focus_hours - hours) * 60);
    return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
});

const scoreColor = computed(() => {
    if (!props.stats) return 'text-gray-500';
    const score = props.stats.average_team_focus_score;
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-blue-600';
    if (score >= 40) return 'text-yellow-600';
    return 'text-red-600';
});

const deepWorkPercentage = computed(() => {
    if (!props.stats) return 0;
    return Math.round(props.stats.deep_work_percentage);
});
</script>

<template>
    <div class="team-stats-overview">
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in 4" :key="i" class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 animate-pulse">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-3"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
            </div>
        </div>

        <div v-else-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Total Team Sessions -->
            <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Team Sessions
                        </p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ stats.total_team_sessions }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Team Focus Time -->
            <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Team Focus Time
                        </p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ focusHours }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Across all members
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Team Average Focus Score -->
            <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Team Avg Score
                        </p>
                        <p class="text-3xl font-bold" :class="scoreColor">
                            {{ Math.round(stats.average_team_focus_score) }}<span class="text-lg">/100</span>
                        </p>
                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="{
                                    'bg-green-500': stats.average_team_focus_score >= 80,
                                    'bg-blue-500': stats.average_team_focus_score >= 60 && stats.average_team_focus_score < 80,
                                    'bg-yellow-500': stats.average_team_focus_score >= 40 && stats.average_team_focus_score < 60,
                                    'bg-red-500': stats.average_team_focus_score < 40
                                }"
                                :style="{ width: `${stats.average_team_focus_score}%` }"
                            ></div>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Team Deep Work Sessions -->
            <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Deep Work
                        </p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ stats.total_deep_work_sessions }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ deepWorkPercentage }}% of sessions
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Members -->
            <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Active Members
                        </p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ stats.active_members }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            With focus sessions
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Team Apps -->
        <div v-if="stats && stats.top_team_apps.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Team Apps</h3>
            <div class="space-y-3">
                <div
                    v-for="(app, index) in stats.top_team_apps.slice(0, 5)"
                    :key="app.app_name"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center space-x-3 flex-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-6">
                            #{{ index + 1 }}
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ app.app_name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                {{ app.session_count }} session{{ app.session_count !== 1 ? 's' : '' }} ({{ app.percentage }}%)
                            </p>
                        </div>
                    </div>
                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-blue-500 h-2 rounded-full transition-all"
                            :style="{ width: `${app.percentage}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Productivity Trend -->
        <div v-if="stats && stats.team_productivity_trend.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Team Productivity Trend</h3>
            <div class="space-y-2">
                <div
                    v-for="trend in stats.team_productivity_trend"
                    :key="trend.date"
                    class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0"
                >
                    <div class="flex items-center space-x-3 flex-1">
                        <span class="text-sm text-gray-900 dark:text-white font-medium">
                            {{ new Date(trend.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-500">
                            {{ trend.session_count }} session{{ trend.session_count !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="{
                                    'bg-green-500': trend.average_score >= 80,
                                    'bg-blue-500': trend.average_score >= 60 && trend.average_score < 80,
                                    'bg-yellow-500': trend.average_score >= 40 && trend.average_score < 60,
                                    'bg-red-500': trend.average_score < 40
                                }"
                                :style="{ width: `${trend.average_score}%` }"
                            ></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                            {{ Math.round(trend.average_score) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && !stats" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No team analytics data available for this period
            </p>
        </div>
    </div>
</template>

<style scoped>
.stat-card {
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}
</style>
