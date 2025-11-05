<template>
    <div class="quick-timer">
        <!-- Timer Display (When Running) -->
        <div
            v-if="isRunning"
            class="flex items-center gap-2 px-3 py-2 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg transition-all hover:bg-cyan-100 dark:hover:bg-cyan-900/30"
        >
            <!-- Pulsing Dot -->
            <div class="relative flex items-center justify-center">
                <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-cyan-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
            </div>

            <!-- Timer Info -->
            <div class="flex flex-col min-w-0">
                <div class="text-xs font-medium text-cyan-900 dark:text-cyan-100 truncate">
                    {{ currentEntry?.description || 'Tracking time...' }}
                </div>
                <div class="text-lg font-mono font-bold text-cyan-700 dark:text-cyan-300 tabular-nums">
                    {{ formattedDuration }}
                </div>
            </div>

            <!-- Stop Button -->
            <button
                @click="stopTimer"
                class="ml-2 p-2 hover:bg-cyan-200 dark:hover:bg-cyan-800 rounded-md transition-colors"
                title="Stop timer (⌘+T)"
            >
                <svg class="w-5 h-5 text-cyan-700 dark:text-cyan-300" fill="currentColor" viewBox="0 0 20 20">
                    <rect x="6" y="6" width="8" height="8" rx="1" />
                </svg>
            </button>
        </div>

        <!-- Start Timer Button (When Not Running) -->
        <button
            v-else
            @click="showQuickStart"
            class="flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md"
            title="Start timer (⌘+T)"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Start Timer</span>
        </button>

        <!-- Quick Start Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click="closeModal"
                >
                    <div
                        class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-2xl"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Start Timer
                            </h3>
                            <button
                                @click="closeModal"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <div class="p-4 space-y-4">
                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    What are you working on?
                                </label>
                                <input
                                    ref="descriptionInput"
                                    v-model="newEntry.description"
                                    type="text"
                                    placeholder="e.g., Client meeting, Development work..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                                    @keydown.enter="startTimer"
                                />
                            </div>

                            <!-- Project Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Project (optional)
                                </label>
                                <select
                                    v-model="newEntry.projectId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option :value="null">No project</option>
                                    <option
                                        v-for="project in recentProjects"
                                        :key="project.id"
                                        :value="project.id"
                                    >
                                        {{ project.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tags (optional)
                                </label>
                                <input
                                    v-model="newEntry.tags"
                                    type="text"
                                    placeholder="Add tags separated by commas"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <!-- Billable Toggle -->
                            <div class="flex items-center">
                                <input
                                    v-model="newEntry.billable"
                                    type="checkbox"
                                    id="billable"
                                    class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500"
                                />
                                <label for="billable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Billable time
                                </label>
                            </div>

                            <!-- Recent Entries -->
                            <div v-if="recentEntries.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Recent Activities
                                </h4>
                                <div class="space-y-1">
                                    <button
                                        v-for="entry in recentEntries.slice(0, 3)"
                                        :key="entry.id"
                                        @click="useRecentEntry(entry)"
                                        class="w-full flex items-center gap-2 p-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ entry.description }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ entry.projectName || 'No project' }}
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                @click="closeModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="startTimer"
                                :disabled="!newEntry.description"
                                class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors"
                            >
                                Start Timer
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import { ShortcutCategory } from '@/utils/keyboardShortcuts';
import axios from 'axios';

// Interfaces
interface TimeEntry {
    id: string;
    description: string;
    projectId?: string | null;
    projectName?: string | null;
    tags?: string[];
    billable: boolean;
    start: string;
    end?: string | null;
}

interface Project {
    id: string;
    name: string;
    color?: string;
}

// State
const isRunning = ref(false);
const currentEntry = ref<TimeEntry | null>(null);
const currentDuration = ref(0);
const showModal = ref(false);
const descriptionInput = ref<HTMLInputElement | null>(null);

const newEntry = ref({
    description: '',
    projectId: null as string | null,
    tags: '',
    billable: false
});

const recentProjects = ref<Project[]>([]);
const recentEntries = ref<TimeEntry[]>([]);

// Timer interval
let timerInterval: number | null = null;

// Computed
const formattedDuration = computed(() => {
    const hours = Math.floor(currentDuration.value / 3600);
    const minutes = Math.floor((currentDuration.value % 3600) / 60);
    const seconds = currentDuration.value % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

// Methods
const showQuickStart = () => {
    showModal.value = true;
    nextTick(() => {
        descriptionInput.value?.focus();
    });
};

const closeModal = () => {
    showModal.value = false;
    newEntry.value = {
        description: '',
        projectId: null,
        tags: '',
        billable: false
    };
};

const startTimer = async () => {
    if (!newEntry.value.description) return;

    try {
        const response = await axios.post('/api/v1/time-entries', {
            description: newEntry.value.description,
            project_id: newEntry.value.projectId,
            tags: newEntry.value.tags ? newEntry.value.tags.split(',').map(t => t.trim()) : [],
            billable: newEntry.value.billable,
            start: new Date().toISOString()
        });

        currentEntry.value = response.data.data;
        isRunning.value = true;
        startDurationCounter();
        closeModal();

        // Fetch recent data
        await fetchRecentData();
    } catch (error) {
        console.error('Failed to start timer:', error);
        // TODO: Show error notification
    }
};

const stopTimer = async () => {
    if (!currentEntry.value) return;

    try {
        await axios.patch(`/api/v1/time-entries/${currentEntry.value.id}`, {
            end: new Date().toISOString()
        });

        isRunning.value = false;
        currentEntry.value = null;
        stopDurationCounter();

        // Fetch recent data
        await fetchRecentData();
    } catch (error) {
        console.error('Failed to stop timer:', error);
        // TODO: Show error notification
    }
};

const startDurationCounter = () => {
    if (timerInterval) return;

    currentDuration.value = 0;
    timerInterval = window.setInterval(() => {
        currentDuration.value++;
    }, 1000);
};

const stopDurationCounter = () => {
    if (timerInterval) {
        window.clearInterval(timerInterval);
        timerInterval = null;
    }
    currentDuration.value = 0;
};

const fetchRecentData = async () => {
    try {
        // Fetch recent projects
        const projectsRes = await axios.get('/api/v1/projects?limit=10');
        recentProjects.value = projectsRes.data.data || [];

        // Fetch recent entries
        const entriesRes = await axios.get('/api/v1/time-entries?limit=5&order=desc');
        recentEntries.value = entriesRes.data.data || [];
    } catch (error) {
        console.error('Failed to fetch recent data:', error);
    }
};

const useRecentEntry = (entry: TimeEntry) => {
    newEntry.value.description = entry.description;
    newEntry.value.projectId = entry.projectId ?? null;
    newEntry.value.tags = entry.tags?.join(', ') || '';
    newEntry.value.billable = entry.billable;
};

const checkRunningTimer = async () => {
    try {
        const response = await axios.get('/api/v1/time-entries/active');
        if (response.data.data) {
            currentEntry.value = response.data.data;
            isRunning.value = true;

            // Calculate duration
            const start = new Date(currentEntry.value!.start);
            const now = new Date();
            currentDuration.value = Math.floor((now.getTime() - start.getTime()) / 1000);

            startDurationCounter();
        }
    } catch (error) {
        console.error('Failed to check running timer:', error);
    }
};

// Lifecycle
onMounted(async () => {
    await checkRunningTimer();
    await fetchRecentData();
});

onUnmounted(() => {
    stopDurationCounter();
});

// Register keyboard shortcut
useKeyboardShortcut('quick-timer-toggle', {
    key: 't',
    meta: true,
    description: 'Start/stop timer',
    category: ShortcutCategory.TIMER,
    handler: () => {
        if (isRunning.value) {
            stopTimer();
        } else {
            showQuickStart();
        }
    }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
