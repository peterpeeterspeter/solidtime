<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import FocusSessionStats from '@/Components/FocusSession/FocusSessionStats.vue';
import ActivityHeatmap from '@/Components/FocusSession/ActivityHeatmap.vue';
import FocusSessionList from '@/Components/FocusSession/FocusSessionList.vue';
import { useFocusSessions } from '@/composables/useFocusSessions';

const {
    sessions,
    stats,
    heatmap,
    streaks,
    loading,
    error,
    fetchSessions,
    fetchStats,
    fetchHeatmap,
    fetchStreaks,
    deleteSession,
} = useFocusSessions();

// Date range selection
const dateRange = ref<'week' | 'month' | 'custom'>('week');
const customStartDate = ref<string>('');
const customEndDate = ref<string>('');

const getDateRange = () => {
    const now = new Date();
    let startDate: string, endDate: string;

    if (dateRange.value === 'custom' && customStartDate.value && customEndDate.value) {
        startDate = customStartDate.value;
        endDate = customEndDate.value;
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
        fetchSessions(startDate, endDate),
        fetchStats(startDate, endDate),
        fetchHeatmap(startDate, endDate),
        fetchStreaks(),
    ]);
};

const handleDateRangeChange = () => {
    loadData();
};

const handleDeleteSession = async (sessionId: string) => {
    const success = await deleteSession(sessionId);
    if (success) {
        // Refresh data
        loadData();
    }
};

onMounted(() => {
    loadData();
});

const streakMessage = computed(() => {
    if (!streaks.value) return '';
    if (streaks.value.current_streak === 0) return 'Start your focus streak today!';
    if (streaks.value.current_streak === 1) return 'Great start! Keep it going.';
    return `You're on a ${streaks.value.current_streak}-day focus streak! 🔥`;
});
</script>

<template>
    <AppLayout title="Activity Dashboard" data-testid="activity_dashboard_view">
        <!-- Header -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Activity Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Track your focus sessions and productivity insights
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

            <!-- Streak Banner -->
            <div
                v-if="streaks && streaks.current_streak > 0"
                class="mt-4 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg p-4 text-white"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="text-3xl">🔥</div>
                        <div>
                            <p class="font-semibold">{{ streakMessage }}</p>
                            <p class="text-sm opacity-90">
                                Longest streak: {{ streaks.longest_streak }} days
                            </p>
                        </div>
                    </div>
                    <div class="text-4xl font-bold">
                        {{ streaks.current_streak }}
                    </div>
                </div>
            </div>
        </MainContainer>

        <!-- Error Message -->
        <MainContainer v-if="error" class="pt-5 sm:pt-8 pb-4 sm:pb-6">
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded">
                <p>{{ error }}</p>
            </div>
        </MainContainer>

        <!-- Statistics Cards -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <FocusSessionStats :stats="stats" :loading="loading" />
        </MainContainer>

        <!-- Heatmap -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <ActivityHeatmap :heatmap="heatmap" :loading="loading" />
        </MainContainer>

        <!-- Focus Sessions List -->
        <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6">
            <FocusSessionList
                :sessions="sessions"
                :loading="loading"
                @delete="handleDeleteSession"
            />
        </MainContainer>
    </AppLayout>
</template>
