import { ref, onMounted, onUnmounted, computed } from 'vue';
import { offlineDb, type OfflineTimeEntry } from '@/utils/offlineDb';
import { offlineSyncService, useSyncState, syncHelpers, SyncStatus } from '@/utils/offlineSync';
import { v4 as uuidv4 } from 'uuid';

/**
 * Composable for offline time tracking functionality
 */
export function useOffline() {
    const syncState = useSyncState();
    const pendingEntries = ref<OfflineTimeEntry[]>([]);
    const stats = ref({
        totalEntries: 0,
        unsyncedEntries: 0,
        queuedItems: 0,
        hasPendingSync: false
    });

    /**
     * Initialize offline functionality
     */
    const initialize = async () => {
        await refreshStats();
        syncHelpers.startAutoSync();
        await syncHelpers.registerBackgroundSync();
    };

    /**
     * Create a new offline time entry
     */
    const createOfflineEntry = async (data: {
        organizationId: string;
        projectId?: string | null;
        taskId?: string | null;
        description: string;
        start: string;
        end?: string | null;
        tags?: string[];
        billable?: boolean;
    }): Promise<string> => {
        const uuid = uuidv4();
        const now = new Date().toISOString();

        const entry: Omit<OfflineTimeEntry, 'id'> = {
            uuid,
            organizationId: data.organizationId,
            projectId: data.projectId || null,
            taskId: data.taskId || null,
            description: data.description,
            start: data.start,
            end: data.end || null,
            tags: data.tags || [],
            billable: data.billable ?? false,
            synced: false,
            createdAt: now,
            updatedAt: now,
            deleted: false
        };

        await offlineDb.addTimeEntry(entry);
        await refreshStats();

        // Try to sync immediately if online
        if (syncState.isOnline.value) {
            syncHelpers.forceSyncNow();
        }

        return uuid;
    };

    /**
     * Update an offline time entry
     */
    const updateOfflineEntry = async (uuid: string, updates: Partial<OfflineTimeEntry>): Promise<void> => {
        await offlineDb.updateTimeEntry(uuid, {
            ...updates,
            synced: false // Mark as unsynced for next sync
        });
        await refreshStats();

        // Try to sync immediately if online
        if (syncState.isOnline.value) {
            syncHelpers.forceSyncNow();
        }
    };

    /**
     * Delete an offline time entry (soft delete)
     */
    const deleteOfflineEntry = async (uuid: string): Promise<void> => {
        await offlineDb.deleteTimeEntry(uuid);
        await refreshStats();

        // Try to sync immediately if online
        if (syncState.isOnline.value) {
            syncHelpers.forceSyncNow();
        }
    };

    /**
     * Get all pending (unsynced) entries
     */
    const getPendingEntries = async (): Promise<OfflineTimeEntry[]> => {
        pendingEntries.value = await offlineDb.getUnsyncedEntries();
        return pendingEntries.value;
    };

    /**
     * Get entries for a specific organization
     */
    const getEntriesByOrganization = async (organizationId: string): Promise<OfflineTimeEntry[]> => {
        return await offlineDb.getEntriesByOrganization(organizationId);
    };

    /**
     * Refresh offline statistics
     */
    const refreshStats = async (): Promise<void> => {
        stats.value = await offlineDb.getStats();
    };

    /**
     * Force sync now
     */
    const syncNow = async (): Promise<void> => {
        await syncHelpers.forceSyncNow();
        await refreshStats();
        await getPendingEntries();
    };

    /**
     * Clear all offline data (use with caution)
     */
    const clearAllOfflineData = async (): Promise<void> => {
        if (confirm('Are you sure you want to clear all offline data? This cannot be undone.')) {
            await offlineDb.clearAll();
            await refreshStats();
            pendingEntries.value = [];
        }
    };

    /**
     * Check if offline mode is available
     */
    const isOfflineModeAvailable = computed(() => {
        return 'indexedDB' in window && 'serviceWorker' in navigator;
    });

    /**
     * Get formatted sync status message
     */
    const syncStatusMessage = computed(() => {
        switch (syncState.syncStatus.value) {
            case SyncStatus.IDLE:
                return stats.value.hasPendingSync
                    ? `${stats.value.unsyncedEntries} entries pending sync`
                    : 'All data synced';
            case SyncStatus.SYNCING:
                return 'Syncing...';
            case SyncStatus.SUCCESS:
                return 'Sync complete';
            case SyncStatus.ERROR:
                return `Sync failed: ${syncState.syncError.value}`;
            case SyncStatus.OFFLINE:
                return 'Offline - changes saved locally';
            default:
                return '';
        }
    });

    /**
     * Get sync status color
     */
    const syncStatusColor = computed(() => {
        switch (syncState.syncStatus.value) {
            case SyncStatus.IDLE:
                return stats.value.hasPendingSync ? 'yellow' : 'green';
            case SyncStatus.SYNCING:
                return 'blue';
            case SyncStatus.SUCCESS:
                return 'green';
            case SyncStatus.ERROR:
                return 'red';
            case SyncStatus.OFFLINE:
                return 'gray';
            default:
                return 'gray';
        }
    });

    /**
     * Format last sync time
     */
    const lastSyncFormatted = computed(() => {
        if (!syncState.lastSyncTime.value) return 'Never';

        const now = new Date();
        const diff = now.getTime() - syncState.lastSyncTime.value.getTime();
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;

        return syncState.lastSyncTime.value.toLocaleDateString();
    });

    // Lifecycle hooks
    onMounted(() => {
        initialize();
    });

    onUnmounted(() => {
        syncHelpers.stopAutoSync();
    });

    return {
        // State
        isOnline: syncState.isOnline,
        isSyncing: syncState.isSyncing,
        syncStatus: syncState.syncStatus,
        syncError: syncState.syncError,
        lastSyncTime: syncState.lastSyncTime,
        pendingEntries,
        stats,

        // Computed
        isOfflineModeAvailable,
        syncStatusMessage,
        syncStatusColor,
        lastSyncFormatted,
        hasPendingSync: computed(() => stats.value.hasPendingSync),

        // Methods
        createOfflineEntry,
        updateOfflineEntry,
        deleteOfflineEntry,
        getPendingEntries,
        getEntriesByOrganization,
        refreshStats,
        syncNow,
        clearAllOfflineData,
        initialize
    };
}
