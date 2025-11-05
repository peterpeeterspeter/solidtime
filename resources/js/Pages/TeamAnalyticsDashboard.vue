<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TeamStatsOverview from '@/Components/TeamAnalytics/TeamStatsOverview.vue';
import MemberRankings from '@/Components/TeamAnalytics/MemberRankings.vue';
import TeamInsights from '@/Components/TeamAnalytics/TeamInsights.vue';
import ActivityHeatmap from '@/Components/FocusSession/ActivityHeatmap.vue';
import { useTeamAnalytics } from '@/composables/useTeamAnalytics';

interface Props {
    organizationId: string;
}

const props = defineProps<Props>();

const {
    teamStats,
    memberRankings,
    teamHeatmap,
    productiveHours,
    focusDistribution,
    insights,
    loading,
    error,
    fetchTeamStats,
    fetchMemberRankings,
    fetchTeamHeatmap,
    fetchProductiveHours,
    fetchFocusDistribution,
    fetchInsights,
} = useTeamAnalytics();

// Date range selection
const dateRange = ref<'week' | 'month' | 'quarter' | 'custom'>('week');
const customStartDate = ref<string>('');
const customEndDate = ref<string>('');

const getDateRange = () => {
    const now = new Date();
    let startDate: string, endDate: string;

    if (dateRange.value === 'custom' && customStartDate.value && customEndDate.value) {
        startDate = customStartDate.value;
        endDate = customEndDate.value;
    } else if (dateRange.value === 'quarter') {
        // Last 3 months
        const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
        startDate = threeMonthsAgo.toISOString().split('T')[0];
        endDate = now.toISOString().split('T')[0];
    } else if (dateRange.value === 'month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDate = firstDay.toISOString().split('T')[0];
        endDate = lastDay.toISOString().split('T')[0];
    } else {
        // Default: week
        const firstDay = new Date(now.setDate(now.getDate() - now.getDay()));
        const lastDay = new Date(now.setDate(now.getDate() - now.getDay() + 6));
        startDate = firstDay.toISOString().split('T')[0];
        endDate = lastDay.toISOString().split('T')[0];
    }

    return { startDate, endDate };
};

const loadData = async () => {
    const { startDate, endDate } = getDateRange();

    await Promise.all([
        fetchTeamStats(props.organizationId, startDate, endDate),
        fetchMemberRankings(props.organizationId, startDate, endDate),
        fetchTeamHeatmap(props.organizationId, startDate, endDate),
        fetchProductiveHours(props.organizationId, startDate, endDate),
        fetchFocusDistribution(props.organizationId, startDate, endDate),
        fetchInsights(props.organizationId, startDate, endDate),
    ]);
};

const handleDateRangeChange = () => {
    loadData();
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <AppLayout title="Team Analytics Dashboard" data-testid="team_analytics_dashboard_view">
        <!-- Header -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Team Analytics Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Organization-level focus analytics and team performance insights
                    </p>
                </div>

                <!-- Date Range Selector -->
                <div class="flex items-center space-x-4">
                    <select
                        v-model="dateRange"
                        @change="handleDateRangeChange"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">Last 3 Months</option>
                        <option value="custom">Custom Range</option>
                    </select>

                    <!-- Custom Date Inputs (shown when custom is selected) -->
                    <template v-if="dateRange === 'custom'">
                        <input
                            v-model="customStartDate"
                            type="date"
                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-gray-500 dark:text-gray-400">to</span>
                        <input
                            v-model="customEndDate"
                            type="date"
                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button
                            @click="handleDateRangeChange"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors"
                        >
                            Apply
                        </button>
                    </template>
                </div>
            </div>
        </MainContainer>

        <!-- Error Message -->
        <MainContainer v-if="error" class="pt-5 sm:pt-8 pb-4 sm:pb-6">
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded">
                <p>{{ error }}</p>
            </div>
        </MainContainer>

        <!-- Team Statistics Cards -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <TeamStatsOverview :stats="teamStats" :loading="loading" />
        </MainContainer>

        <!-- Team Insights -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <TeamInsights :insights="insights" :loading="loading" />
        </MainContainer>

        <!-- Two Column Layout: Heatmap + Rankings -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Team Heatmap -->
                <div>
                    <ActivityHeatmap :heatmap="teamHeatmap" :loading="loading" />
                </div>

                <!-- Member Rankings -->
                <div>
                    <MemberRankings :rankings="memberRankings" :loading="loading" />
                </div>
            </div>
        </MainContainer>

        <!-- Productive Hours & Distribution -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Productive Hours -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Team Productive Hours
                    </h3>
                    <div v-if="loading" class="space-y-2">
                        <div v-for="i in 5" :key="i" class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    </div>
                    <div v-else-if="productiveHours.length > 0" class="space-y-2">
                        <div
                            v-for="hour in productiveHours.slice(0, 10)"
                            :key="hour.hour"
                            class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white w-16">
                                    {{ hour.hour }}:00
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ hour.session_count }} sessions
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full transition-all"
                                        :class="{
                                            'bg-green-500': hour.average_score >= 80,
                                            'bg-blue-500': hour.average_score >= 60 && hour.average_score < 80,
                                            'bg-yellow-500': hour.average_score >= 40 && hour.average_score < 60,
                                            'bg-red-500': hour.average_score < 40
                                        }"
                                        :style="{ width: `${hour.average_score}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                                    {{ Math.round(hour.average_score) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No productive hours data available</p>
                    </div>
                </div>

                <!-- Focus Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Focus Distribution by Time of Day
                    </h3>
                    <div v-if="loading" class="space-y-4">
                        <div v-for="i in 4" :key="i" class="h-16 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    </div>
                    <div v-else-if="focusDistribution" class="space-y-4">
                        <div
                            v-for="(period, key) in focusDistribution"
                            :key="key"
                            class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ period.label }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ period.sessions }} sessions
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div
                                        class="bg-blue-500 h-3 rounded-full transition-all"
                                        :style="{ width: `${(period.hours / (teamStats?.total_team_focus_hours || 1)) * 100}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ period.hours }}h
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No distribution data available</p>
                    </div>
                </div>
            </div>
        </MainContainer>
    </AppLayout>
</template>
