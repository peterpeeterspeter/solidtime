import { ref, computed } from 'vue';
import { offlineDb, type OfflineTimeEntry, type OfflineSyncQueue } from './offlineDb';
import axios from 'axios';

// Sync status enum
export enum SyncStatus {
    IDLE = 'idle',
    SYNCING = 'syncing',
    SUCCESS = 'success',
    ERROR = 'error',
    OFFLINE = 'offline'
}

// Reactive sync state
const syncStatus = ref<SyncStatus>(SyncStatus.IDLE);
const lastSyncTime = ref<Date | null>(null);
const syncError = ref<string | null>(null);
const isOnline = ref(navigator.onLine);
const isSyncing = ref(false);

// Computed properties
const hasPendingSync = computed(() => syncStatus.value !== SyncStatus.IDLE);

/**
 * Offline Sync Service
 * Handles background synchronization of offline time entries with the server
 */
export class OfflineSyncService {
    private syncInterval: number | null = null;
    private retryTimeout: number | null = null;
    private readonly MAX_RETRIES = 5;
    private readonly BASE_RETRY_DELAY = 2000; // 2 seconds
    private readonly SYNC_INTERVAL = 30000; // 30 seconds

    constructor() {
        this.setupNetworkListeners();
        this.setupBackgroundSync();
    }

    /**
     * Set up network status listeners
     */
    private setupNetworkListeners(): void {
        window.addEventListener('online', () => {
            console.log('[OfflineSync] Network online - triggering sync');
            isOnline.value = true;
            syncStatus.value = SyncStatus.IDLE;
            this.sync();
        });

        window.addEventListener('offline', () => {
            console.log('[OfflineSync] Network offline');
            isOnline.value = false;
            syncStatus.value = SyncStatus.OFFLINE;
            this.stopAutoSync();
        });

        // Use Network Information API if available
        if ('connection' in navigator) {
            const connection = (navigator as any).connection;
            connection?.addEventListener('change', () => {
                console.log('[OfflineSync] Network connection changed:', {
                    effectiveType: connection.effectiveType,
                    downlink: connection.downlink,
                    rtt: connection.rtt
                });
            });
        }
    }

    /**
     * Set up Background Sync API if supported
     */
    private setupBackgroundSync(): void {
        if ('serviceWorker' in navigator && 'sync' in ServiceWorkerRegistration.prototype) {
            navigator.serviceWorker.ready.then((registration) => {
                // Listen for sync events
                registration.addEventListener('sync', (event: any) => {
                    if (event.tag === 'timeclocker-sync') {
                        console.log('[OfflineSync] Background sync triggered');
                        event.waitUntil(this.sync());
                    }
                });
            });
        }
    }

    /**
     * Start automatic sync interval
     */
    startAutoSync(): void {
        if (this.syncInterval) return;

        console.log('[OfflineSync] Starting auto-sync');
        this.syncInterval = window.setInterval(() => {
            if (isOnline.value && !isSyncing.value) {
                this.sync();
            }
        }, this.SYNC_INTERVAL);
    }

    /**
     * Stop automatic sync interval
     */
    stopAutoSync(): void {
        if (this.syncInterval) {
            window.clearInterval(this.syncInterval);
            this.syncInterval = null;
            console.log('[OfflineSync] Stopped auto-sync');
        }
    }

    /**
     * Main sync function - syncs all unsynced entries
     */
    async sync(): Promise<void> {
        if (!isOnline.value) {
            console.log('[OfflineSync] Cannot sync - offline');
            syncStatus.value = SyncStatus.OFFLINE;
            return;
        }

        if (isSyncing.value) {
            console.log('[OfflineSync] Sync already in progress');
            return;
        }

        try {
            isSyncing.value = true;
            syncStatus.value = SyncStatus.SYNCING;
            syncError.value = null;

            const stats = await offlineDb.getStats();
            if (!stats.hasPendingSync) {
                console.log('[OfflineSync] No pending items to sync');
                syncStatus.value = SyncStatus.SUCCESS;
                return;
            }

            console.log('[OfflineSync] Starting sync:', {
                unsyncedEntries: stats.unsyncedEntries,
                queuedItems: stats.queuedItems
            });

            // Process unsynced time entries
            await this.syncTimeEntries();

            // Process sync queue
            await this.processSyncQueue();

            syncStatus.value = SyncStatus.SUCCESS;
            lastSyncTime.value = new Date();
            console.log('[OfflineSync] Sync completed successfully');

        } catch (error: any) {
            console.error('[OfflineSync] Sync failed:', error);
            syncStatus.value = SyncStatus.ERROR;
            syncError.value = error.message || 'Sync failed';

            // Schedule retry with exponential backoff
            this.scheduleRetry();
        } finally {
            isSyncing.value = false;
        }
    }

    /**
     * Sync unsynced time entries to server
     */
    private async syncTimeEntries(): Promise<void> {
        const unsyncedEntries = await offlineDb.getUnsyncedEntries();

        for (const entry of unsyncedEntries) {
            try {
                if (entry.deleted) {
                    // Handle deleted entries
                    await this.deleteTimeEntry(entry);
                } else if (entry.synced === false && !entry.id) {
                    // New entry - create on server
                    await this.createTimeEntry(entry);
                } else {
                    // Updated entry - update on server
                    await this.updateTimeEntry(entry);
                }
            } catch (error: any) {
                console.error('[OfflineSync] Failed to sync entry:', entry.uuid, error);

                // Add to sync queue for retry
                await offlineDb.addToSyncQueue({
                    action: entry.deleted ? 'delete' : (entry.id ? 'update' : 'create'),
                    entityType: 'timeEntry',
                    entityId: entry.uuid,
                    data: entry,
                    timestamp: new Date().toISOString(),
                    retryCount: 0
                });
            }
        }
    }

    /**
     * Create a new time entry on the server
     */
    private async createTimeEntry(entry: OfflineTimeEntry): Promise<void> {
        console.log('[OfflineSync] Creating time entry:', entry.uuid);

        const response = await axios.post('/api/v1/time-entries', {
            organization_id: entry.organizationId,
            project_id: entry.projectId,
            task_id: entry.taskId,
            description: entry.description,
            start: entry.start,
            end: entry.end,
            tags: entry.tags,
            billable: entry.billable
        });

        // Mark as synced with server ID
        await offlineDb.markEntrySynced(entry.uuid, response.data.data.id);
        console.log('[OfflineSync] Created time entry:', response.data.data.id);
    }

    /**
     * Update an existing time entry on the server
     */
    private async updateTimeEntry(entry: OfflineTimeEntry): Promise<void> {
        console.log('[OfflineSync] Updating time entry:', entry.uuid);

        await axios.patch(`/api/v1/time-entries/${entry.id}`, {
            project_id: entry.projectId,
            task_id: entry.taskId,
            description: entry.description,
            start: entry.start,
            end: entry.end,
            tags: entry.tags,
            billable: entry.billable
        });

        await offlineDb.markEntrySynced(entry.uuid);
        console.log('[OfflineSync] Updated time entry:', entry.id);
    }

    /**
     * Delete a time entry on the server
     */
    private async deleteTimeEntry(entry: OfflineTimeEntry): Promise<void> {
        console.log('[OfflineSync] Deleting time entry:', entry.uuid);

        if (entry.id) {
            await axios.delete(`/api/v1/time-entries/${entry.id}`);
        }

        // Remove from offline DB
        await offlineDb.deleteTimeEntry(entry.uuid);
        console.log('[OfflineSync] Deleted time entry:', entry.id);
    }

    /**
     * Process items in the sync queue
     */
    private async processSyncQueue(): Promise<void> {
        const queueItems = await offlineDb.getSyncQueue();

        for (const item of queueItems) {
            try {
                await this.processQueueItem(item);
                await offlineDb.removeFromSyncQueue(item.id!);
            } catch (error: any) {
                console.error('[OfflineSync] Failed to process queue item:', item.id, error);

                // Increment retry count
                const newRetryCount = item.retryCount + 1;

                if (newRetryCount >= this.MAX_RETRIES) {
                    console.error('[OfflineSync] Max retries reached for queue item:', item.id);
                    // TODO: Move to failed queue or notify user
                    await offlineDb.removeFromSyncQueue(item.id!);
                }
            }
        }
    }

    /**
     * Process a single queue item
     */
    private async processQueueItem(item: OfflineSyncQueue): Promise<void> {
        switch (item.action) {
            case 'create':
                await this.createTimeEntry(item.data);
                break;
            case 'update':
                await this.updateTimeEntry(item.data);
                break;
            case 'delete':
                await this.deleteTimeEntry(item.data);
                break;
        }
    }

    /**
     * Schedule retry with exponential backoff
     */
    private scheduleRetry(retryCount: number = 0): void {
        if (retryCount >= this.MAX_RETRIES) {
            console.error('[OfflineSync] Max retries reached');
            return;
        }

        const delay = this.BASE_RETRY_DELAY * Math.pow(2, retryCount);
        console.log(`[OfflineSync] Scheduling retry ${retryCount + 1} in ${delay}ms`);

        if (this.retryTimeout) {
            window.clearTimeout(this.retryTimeout);
        }

        this.retryTimeout = window.setTimeout(() => {
            this.sync();
        }, delay);
    }

    /**
     * Force sync immediately
     */
    async forceSyncNow(): Promise<void> {
        console.log('[OfflineSync] Force sync requested');
        await this.sync();
    }

    /**
     * Register for background sync (if supported)
     */
    async registerBackgroundSync(): Promise<void> {
        if ('serviceWorker' in navigator && 'sync' in ServiceWorkerRegistration.prototype) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await (registration as any).sync.register('timeclocker-sync');
                console.log('[OfflineSync] Background sync registered');
            } catch (error) {
                console.error('[OfflineSync] Failed to register background sync:', error);
            }
        }
    }

    /**
     * Get current sync statistics
     */
    async getSyncStats() {
        return await offlineDb.getStats();
    }
}

// Export singleton instance
export const offlineSyncService = new OfflineSyncService();

// Export reactive state for UI components
export const useSyncState = () => ({
    syncStatus: computed(() => syncStatus.value),
    lastSyncTime: computed(() => lastSyncTime.value),
    syncError: computed(() => syncError.value),
    isOnline: computed(() => isOnline.value),
    isSyncing: computed(() => isSyncing.value),
    hasPendingSync,
});

// Export helper functions
export const syncHelpers = {
    /**
     * Start automatic synchronization
     */
    startAutoSync: () => offlineSyncService.startAutoSync(),

    /**
     * Stop automatic synchronization
     */
    stopAutoSync: () => offlineSyncService.stopAutoSync(),

    /**
     * Force immediate sync
     */
    forceSyncNow: () => offlineSyncService.forceSyncNow(),

    /**
     * Register background sync
     */
    registerBackgroundSync: () => offlineSyncService.registerBackgroundSync(),

    /**
     * Get sync statistics
     */
    getSyncStats: () => offlineSyncService.getSyncStats(),
};
