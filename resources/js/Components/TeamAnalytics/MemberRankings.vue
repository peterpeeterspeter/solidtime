<script setup lang="ts">
import { computed } from 'vue';
import type { MemberRanking } from '../../composables/useTeamAnalytics';

interface Props {
    rankings: MemberRanking[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const getRankBadgeColor = (rank: number): string => {
    switch (rank) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 2:
            return 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    }
};

const getRankIcon = (rank: number): string => {
    switch (rank) {
        case 1:
            return '🥇';
        case 2:
            return '🥈';
        case 3:
            return '🥉';
        default:
            return '';
    }
};

const getScoreColor = (score: number): string => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const topThree = computed(() => props.rankings.slice(0, 3));
const restOfRankings = computed(() => props.rankings.slice(3));
</script>

<template>
    <div class="member-rankings bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Team Leaderboard
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Ranked by average focus score
            </p>
        </div>

        <div v-if="loading" class="p-6 space-y-4">
            <div v-for="i in 5" :key="i" class="animate-pulse flex space-x-4">
                <div class="flex-1 space-y-3 py-1">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="rankings.length > 0">
            <!-- Top 3 Podium -->
            <div v-if="topThree.length > 0" class="p-6 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-end justify-center space-x-4">
                    <!-- 2nd Place -->
                    <div v-if="topThree[1]" class="flex flex-col items-center">
                        <div class="text-4xl mb-2">🥈</div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md w-32">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ topThree[1].member_name }}
                            </p>
                            <p class="text-2xl font-bold mt-1" :class="getScoreColor(topThree[1].average_focus_score)">
                                {{ Math.round(topThree[1].average_focus_score) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ topThree[1].total_sessions }} sessions
                            </p>
                        </div>
                    </div>

                    <!-- 1st Place -->
                    <div v-if="topThree[0]" class="flex flex-col items-center -mt-4">
                        <div class="text-5xl mb-2">🥇</div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-5 text-center shadow-lg w-36 border-2 border-yellow-400">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ topThree[0].member_name }}
                            </p>
                            <p class="text-3xl font-bold mt-1" :class="getScoreColor(topThree[0].average_focus_score)">
                                {{ Math.round(topThree[0].average_focus_score) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ topThree[0].total_sessions }} sessions
                            </p>
                        </div>
                    </div>

                    <!-- 3rd Place -->
                    <div v-if="topThree[2]" class="flex flex-col items-center">
                        <div class="text-4xl mb-2">🥉</div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md w-32">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ topThree[2].member_name }}
                            </p>
                            <p class="text-2xl font-bold mt-1" :class="getScoreColor(topThree[2].average_focus_score)">
                                {{ Math.round(topThree[2].average_focus_score) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ topThree[2].total_sessions }} sessions
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rest of Rankings -->
            <div v-if="restOfRankings.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="member in restOfRankings"
                    :key="member.rank"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <!-- Rank Badge -->
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                    #{{ member.rank }}
                                </span>
                            </div>

                            <!-- Member Info -->
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ member.member_name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ member.total_sessions }} sessions · {{ member.total_focus_hours }}h focus time
                                </p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center space-x-6">
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Focus Score</p>
                                <p class="text-lg font-bold" :class="getScoreColor(member.average_focus_score)">
                                    {{ Math.round(member.average_focus_score) }}<span class="text-sm">/100</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Deep Work</p>
                                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ member.deep_work_sessions }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Show all rankings in simple list if no top 3 -->
            <div v-if="topThree.length === 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="member in rankings"
                    :key="member.rank"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <span
                                class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-medium"
                                :class="getRankBadgeColor(member.rank)"
                            >
                                {{ getRankIcon(member.rank) }} #{{ member.rank }}
                            </span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ member.member_name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ member.total_sessions }} sessions · {{ member.total_focus_hours }}h
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Score</p>
                                <p class="text-lg font-bold" :class="getScoreColor(member.average_focus_score)">
                                    {{ Math.round(member.average_focus_score) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No member rankings available for this period
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Members need to have at least one focus session to appear in rankings
            </p>
        </div>
    </div>
</template>

<style scoped>
.member-rankings {
    position: relative;
}
</style>
