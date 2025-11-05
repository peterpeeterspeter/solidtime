import Dexie, { type EntityTable } from 'dexie';

// Define our offline time entry interface
export interface OfflineTimeEntry {
    id?: number;
    uuid: string; // Temporary UUID for offline entries
    organizationId: string;
    projectId?: string | null;
    taskId?: string | null;
    description: string;
    start: string; // ISO date string
    end?: string | null; // ISO date string
    tags: string[];
    billable: boolean;
    synced: boolean;
    createdAt: string;
    updatedAt: string;
    deleted?: boolean;
}

export interface OfflineSyncQueue {
    id?: number;
    action: 'create' | 'update' | 'delete';
    entityType: 'timeEntry';
    entityId: string; // UUID for offline entries, actual ID for synced entries
    data: any;
    timestamp: string;
    retryCount: number;
    lastError?: string;
}

// Create the Dexie database
const db = new Dexie('TimeclockerOfflineDB') as Dexie & {
    timeEntries: EntityTable<OfflineTimeEntry, 'id'>;
    syncQueue: EntityTable<OfflineSyncQueue, 'id'>;
};

// Define schema
db.version(1).stores({
    timeEntries: '++id, uuid, organizationId, synced, start, end',
    syncQueue: '++id, timestamp, synced, action, entityType'
});

export { db };

// Helper functions
export const offlineDb = {
    /**
     * Add a time entry to offline storage
     */
    async addTimeEntry(entry: Omit<OfflineTimeEntry, 'id'>): Promise<number> {
        return await db.timeEntries.add(entry);
    },

    /**
     * Get all unsynced time entries
     */
    async getUnsyncedEntries(): Promise<OfflineTimeEntry[]> {
        return await db.timeEntries.where('synced').equals(false).toArray();
    },

    /**
     * Get all time entries for an organization
     */
    async getEntriesByOrganization(orgId: string): Promise<OfflineTimeEntry[]> {
        return await db.timeEntries.where('organizationId').equals(orgId).toArray();
    },

    /**
     * Mark entry as synced
     */
    async markEntrySynced(uuid: string, serverId?: string): Promise<void> {
        await db.timeEntries.where('uuid').equals(uuid).modify({
            synced: true,
            updatedAt: new Date().toISOString()
        });
    },

    /**
     * Update a time entry
     */
    async updateTimeEntry(uuid: string, updates: Partial<OfflineTimeEntry>): Promise<void> {
        await db.timeEntries.where('uuid').equals(uuid).modify({
            ...updates,
            updatedAt: new Date().toISOString()
        });
    },

    /**
     * Delete a time entry (soft delete)
     */
    async deleteTimeEntry(uuid: string): Promise<void> {
        await db.timeEntries.where('uuid').equals(uuid).modify({
            deleted: true,
            synced: false,
            updatedAt: new Date().toISOString()
        });
    },

    /**
     * Add item to sync queue
     */
    async addToSyncQueue(item: Omit<OfflineSyncQueue, 'id'>): Promise<void> {
        await db.syncQueue.add(item);
    },

    /**
     * Get pending sync queue items
     */
    async getSyncQueue(): Promise<OfflineSyncQueue[]> {
        return await db.syncQueue.toArray();
    },

    /**
     * Remove item from sync queue
     */
    async removeFromSyncQueue(id: number): Promise<void> {
        await db.syncQueue.delete(id);
    },

    /**
     * Clear all offline data (use with caution)
     */
    async clearAll(): Promise<void> {
        await db.timeEntries.clear();
        await db.syncQueue.clear();
    },

    /**
     * Get database statistics
     */
    async getStats() {
        const totalEntries = await db.timeEntries.count();
        const unsyncedEntries = await db.timeEntries.where('synced').equals(false).count();
        const queuedItems = await db.syncQueue.count();

        return {
            totalEntries,
            unsyncedEntries,
            queuedItems,
            hasPendingSync: unsyncedEntries > 0 || queuedItems > 0
        };
    }
};

export default db;
