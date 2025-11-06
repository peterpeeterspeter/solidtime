<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const organizationId = (page.props.auth as any)?.user?.current_organization_id;

interface FocusSession {
    id: string;
    user_id: string;
    user_name: string;
    started_at: string;
    ended_at: string | null;
    duration: number;
    description: string;
    created_at: string;
}

const focusSessions = ref<FocusSession[]>([]);
const loading = ref(false);
const activeSession = ref<FocusSession | null>(null);
const selectedTimeRange = ref<string>('week');

const timeRanges = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'all', label: 'All Time' },
];

const fetchFocusSessions = async () => {
    if (!organizationId) return;
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/organizations/${organizationId}/focus-sessions`);
        focusSessions.value = response.data.data || [];

        // Find active session
        activeSession.value = focusSessions.value.find(s => s.ended_at === null) || null;
    } catch (error) {
        console.error('Failed to fetch focus sessions:', error);
    } finally {
        loading.value = false;
    }
};

const filteredSessions = computed(() => {
    const now = new Date();
    return focusSessions.value.filter(session => {
        const sessionDate = new Date(session.started_at);

        switch (selectedTimeRange.value) {
            case 'today':
                return sessionDate.toDateString() === now.toDateString();
            case 'week':
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                return sessionDate >= weekAgo;
            case 'month':
                const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                return sessionDate >= monthAgo;
            default:
                return true;
        }
    });
});

const totalFocusTime = computed(() => {
    return filteredSessions.value.reduce((sum, s) => sum + (s.duration || 0), 0);
});

const averageSessionDuration = computed(() => {
    if (filteredSessions.value.length === 0) return 0;
    return totalFocusTime.value / filteredSessions.value.length;
});

const formatDuration = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleStartSession = async () => {
    if (!organizationId) return;

    const description = prompt('Enter focus session description (optional):');

    try {
        await axios.post(`/api/v1/organizations/${organizationId}/focus-sessions`, {
            description: description || 'Focus Session',
        });
        await fetchFocusSessions();
        alert('Focus session started!');
    } catch (error) {
        console.error('Failed to start focus session:', error);
        alert('Failed to start focus session');
    }
};

const handleEndSession = async (sessionId: string) => {
    if (!organizationId) return;

    try {
        await axios.patch(`/api/v1/organizations/${organizationId}/focus-sessions/${sessionId}`, {
            ended_at: new Date().toISOString(),
        });
        await fetchFocusSessions();
        alert('Focus session ended!');
    } catch (error) {
        console.error('Failed to end focus session:', error);
        alert('Failed to end focus session');
    }
};

onMounted(() => {
    fetchFocusSessions();
});
</script>

<template>
    <AppLayout title="Focus Sessions">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text-primary">
                Focus Sessions
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="organizationId">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Focus Sessions</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Track your deep work and productivity sessions
                            </p>
                        </div>
                        <button
                            v-if="!activeSession"
                            @click="handleStartSession"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Start Focus Session
                        </button>
                        <button
                            v-else
                            @click="handleEndSession(activeSession.id)"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2 animate-pulse"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                            </svg>
                            End Session ({{ activeSession.description }})
                        </button>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Focus Time</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ formatDuration(totalFocusTime) }}
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Sessions</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ filteredSessions.length }}
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Average Duration</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ formatDuration(averageSessionDuration) }}
                            </div>
                        </div>
                    </div>

                    <!-- Time Range Filter -->
                    <div class="flex items-center gap-3 mb-6">
                        <button
                            v-for="range in timeRanges"
                            :key="range.value"
                            @click="selectedTimeRange = range.value"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="selectedTimeRange === range.value
                                ? 'bg-purple-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            {{ range.label }}
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-12">
                        <svg class="animate-spin h-8 w-8 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Sessions Table -->
                    <div v-else-if="filteredSessions.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Started
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Ended
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Duration
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="session in filteredSessions" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ session.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ formatDateTime(session.started_at) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ session.ended_at ? formatDateTime(session.ended_at) : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDuration(session.duration) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="!session.ended_at"
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 animate-pulse"
                                        >
                                            Active
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            Completed
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No focus sessions</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start your first focus session to track your productivity.</p>
                        <div class="mt-6">
                            <button
                                @click="handleStartSession"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Start Focus Session
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">
                        Please select an organization to view focus sessions.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
