<script setup lang="ts">
import { computed } from 'vue';
import type { FocusHeatmapData } from '../../composables/useFocusSessions';

interface Props {
    heatmap: FocusHeatmapData;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const hours = Array.from({ length: 24 }, (_, i) => i);
const dates = computed(() => Object.keys(props.heatmap).sort());

const getScoreColor = (score: number | null): string => {
    if (score === null) return 'bg-gray-100 dark:bg-gray-800';
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-blue-500';
    if (score >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getScoreOpacity = (score: number | null): string => {
    if (score === null) return '';
    if (score >= 80) return 'opacity-100';
    if (score >= 60) return 'opacity-75';
    if (score >= 40) return 'opacity-50';
    return 'opacity-30';
};

const formatHour = (hour: number): string => {
    if (hour === 0) return '12am';
    if (hour < 12) return `${hour}am`;
    if (hour === 12) return '12pm';
    return `${hour - 12}pm`;
};

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <div class="activity-heatmap bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Focus Activity Heatmap
            </h3>
            <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <span>Less</span>
                <div class="flex space-x-1">
                    <div class="w-3 h-3 bg-gray-100 dark:bg-gray-800 rounded"></div>
                    <div class="w-3 h-3 bg-red-500 opacity-30 rounded"></div>
                    <div class="w-3 h-3 bg-yellow-500 opacity-50 rounded"></div>
                    <div class="w-3 h-3 bg-blue-500 opacity-75 rounded"></div>
                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                </div>
                <span>More</span>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else-if="dates.length > 0" class="overflow-x-auto">
            <div class="inline-block min-w-full">
                <div class="flex">
                    <!-- Hour labels -->
                    <div class="flex flex-col pr-2">
                        <div class="h-6"></div> <!-- Spacer for date labels -->
                        <div
                            v-for="hour in hours"
                            :key="hour"
                            class="h-6 flex items-center justify-end text-xs text-gray-600 dark:text-gray-400"
                        >
                            <span v-if="hour % 3 === 0">{{ formatHour(hour) }}</span>
                        </div>
                    </div>

                    <!-- Heatmap grid -->
                    <div class="flex space-x-1">
                        <div
                            v-for="date in dates"
                            :key="date"
                            class="flex flex-col"
                        >
                            <!-- Date label -->
                            <div class="h-6 text-xs text-gray-600 dark:text-gray-400 text-center mb-1">
                                {{ formatDate(date) }}
                            </div>

                            <!-- Hour cells -->
                            <div
                                v-for="hour in hours"
                                :key="`${date}-${hour}`"
                                class="w-6 h-6 rounded mb-1 transition-all hover:scale-110 cursor-pointer"
                                :class="[
                                    getScoreColor(heatmap[date][hour]),
                                    getScoreOpacity(heatmap[date][hour])
                                ]"
                                :title="`${formatDate(date)} at ${formatHour(hour)}: ${
                                    heatmap[date][hour] !== null
                                        ? `Focus score ${heatmap[date][hour]}`
                                        : 'No focus session'
                                }`"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No heatmap data available for this period
            </p>
        </div>

        <!-- Legend -->
        <div v-if="dates.length > 0" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                <p><span class="inline-block w-3 h-3 bg-green-500 rounded mr-2"></span> Excellent focus (80-100)</p>
                <p><span class="inline-block w-3 h-3 bg-blue-500 rounded mr-2"></span> Good focus (60-79)</p>
                <p><span class="inline-block w-3 h-3 bg-yellow-500 rounded mr-2"></span> Fair focus (40-59)</p>
                <p><span class="inline-block w-3 h-3 bg-red-500 rounded mr-2"></span> Poor focus (0-39)</p>
                <p><span class="inline-block w-3 h-3 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded mr-2"></span> No session</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.activity-heatmap {
    position: relative;
}
</style>
