<template>
    <div v-if="show" class="offline-status-indicator">
        <!-- Network Status Badge -->
        <Transition name="slide-down">
            <div
                v-if="!isOnline || hasPendingSync || isSyncing"
                class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2 rounded-lg shadow-lg backdrop-blur-sm border transition-all duration-300"
                :class="statusClasses"
            >
                <!-- Icon -->
                <div class="flex items-center justify-center">
                    <svg
                        v-if="!isOnline"
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"
                        />
                    </svg>
                    <svg
                        v-else-if="isSyncing"
                        class="w-5 h-5 animate-spin"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    <svg
                        v-else-if="hasPendingSync"
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <!-- Status Text -->
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ statusMessage }}</span>
                    <span v-if="stats.unsyncedEntries > 0" class="text-xs opacity-75">
                        {{ stats.unsyncedEntries }} {{ stats.unsyncedEntries === 1 ? 'entry' : 'entries' }} pending
                    </span>
                </div>

                <!-- Sync Button -->
                <button
                    v-if="isOnline && hasPendingSync && !isSyncing"
                    @click="handleSyncNow"
                    class="ml-2 px-3 py-1 text-xs font-medium rounded hover:bg-white/20 transition-colors"
                >
                    Sync Now
                </button>

                <!-- Close Button -->
                <button
                    @click="dismiss"
                    class="ml-2 p-1 hover:bg-white/20 rounded transition-colors"
                    aria-label="Dismiss"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Detailed Status Panel (Optional) -->
        <Transition name="fade">
            <div
                v-if="showDetails"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 flex items-center justify-center p-4"
                @click="showDetails = false"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
                    @click.stop
                >
                    <h3 class="text-lg font-semibold mb-4">Offline Status</h3>

                    <div class="space-y-4">
                        <!-- Network Status -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Network</span>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded"
                                :class="isOnline ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                            >
                                {{ isOnline ? 'Online' : 'Offline' }}
                            </span>
                        </div>

                        <!-- Statistics -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Total Entries</span>
                                <span class="font-medium">{{ stats.totalEntries }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Unsynced</span>
                                <span class="font-medium text-yellow-600 dark:text-yellow-400">
                                    {{ stats.unsyncedEntries }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Queue Items</span>
                                <span class="font-medium">{{ stats.queuedItems }}</span>
                            </div>
                        </div>

                        <!-- Last Sync -->
                        <div class="border-t pt-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Last synced: <span class="font-medium">{{ lastSyncFormatted }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="border-t pt-4 flex gap-2">
                            <button
                                @click="handleSyncNow"
                                :disabled="!isOnline || isSyncing"
                                class="flex-1 px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ isSyncing ? 'Syncing...' : 'Sync Now' }}
                            </button>
                            <button
                                @click="showDetails = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useOffline } from '@/composables/useOffline';
import { SyncStatus } from '@/utils/offlineSync';

const props = withDefaults(defineProps<{
    show?: boolean;
    autoDismiss?: boolean;
    dismissTimeout?: number;
}>(), {
    show: true,
    autoDismiss: true,
    dismissTimeout: 5000
});

const {
    isOnline,
    isSyncing,
    syncStatus,
    stats,
    syncStatusMessage,
    lastSyncFormatted,
    hasPendingSync,
    syncNow,
    refreshStats
} = useOffline();

const showDetails = ref(false);
const isDismissed = ref(false);

// Computed status classes
const statusClasses = computed(() => {
    if (!isOnline.value) {
        return 'bg-red-500/90 text-white border-red-600';
    } else if (isSyncing.value) {
        return 'bg-blue-500/90 text-white border-blue-600';
    } else if (hasPendingSync.value) {
        return 'bg-yellow-500/90 text-white border-yellow-600';
    } else {
        return 'bg-green-500/90 text-white border-green-600';
    }
});

// Status message
const statusMessage = computed(() => {
    if (!isOnline.value) {
        return 'Offline Mode';
    } else if (isSyncing.value) {
        return 'Syncing...';
    } else if (hasPendingSync.value) {
        return 'Sync Pending';
    } else {
        return 'All Synced';
    }
});

// Handle sync now
const handleSyncNow = async () => {
    try {
        await syncNow();
    } catch (error) {
        console.error('Sync failed:', error);
    }
};

// Dismiss notification
const dismiss = () => {
    isDismissed.value = true;
};

// Watch for sync success and auto-dismiss
watch(() => syncStatus.value, (newStatus) => {
    if (newStatus === SyncStatus.SUCCESS && props.autoDismiss) {
        setTimeout(() => {
            isDismissed.value = true;
        }, props.dismissTimeout);
    }
});

// Reset dismissed state when going offline
watch(() => isOnline.value, (online) => {
    if (!online) {
        isDismissed.value = false;
    }
});

// Refresh stats periodically
setInterval(() => {
    refreshStats();
}, 10000); // Every 10 seconds
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from {
    transform: translateY(-100%);
    opacity: 0;
}

.slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
