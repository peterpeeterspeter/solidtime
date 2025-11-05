import { ref, computed } from 'vue';
import axios from 'axios';

// NotificationAction interface (part of Web Notifications API)
interface NotificationAction {
    action: string;
    title: string;
    icon?: string;
}

// Extended NotificationOptions to include all properties we use
interface ExtendedNotificationOptions extends NotificationOptions {
    vibrate?: number[];
    actions?: NotificationAction[];
    type?: string;
}

// Notification permission states
export enum NotificationPermission {
    DEFAULT = 'default',
    GRANTED = 'granted',
    DENIED = 'denied'
}

// Notification types
export enum NotificationType {
    TIMER_REMINDER = 'timer_reminder',
    DAILY_SUMMARY = 'daily_summary',
    WEEKLY_SUMMARY = 'weekly_summary',
    BREAK_REMINDER = 'break_reminder',
    END_OF_DAY = 'end_of_day',
    TEAM_UPDATE = 'team_update'
}

// Notification preferences interface
export interface NotificationPreferences {
    enabled: boolean;
    timerReminders: boolean;
    timerReminderInterval: number; // in minutes
    breakReminders: boolean;
    breakReminderInterval: number; // in minutes
    dailySummary: boolean;
    dailySummaryTime: string; // HH:MM format
    weeklySummary: boolean;
    weeklySummaryDay: number; // 0-6 (Sunday-Saturday)
    weeklySummaryTime: string; // HH:MM format
    endOfDayReminder: boolean;
    endOfDayTime: string; // HH:MM format
    teamUpdates: boolean;
}

// Default notification preferences
const defaultPreferences: NotificationPreferences = {
    enabled: true,
    timerReminders: true,
    timerReminderInterval: 240, // 4 hours
    breakReminders: true,
    breakReminderInterval: 120, // 2 hours
    dailySummary: true,
    dailySummaryTime: '18:00',
    weeklySummary: true,
    weeklySummaryDay: 1, // Monday
    weeklySummaryTime: '09:00',
    endOfDayReminder: true,
    endOfDayTime: '17:30',
    teamUpdates: true
};

// Reactive state
const notificationPermission = ref<NotificationPermission>(
    ('Notification' in window ? Notification.permission : 'denied') as NotificationPermission
);
const pushSubscription = ref<PushSubscription | null>(null);
const isSupported = ref('Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window);
const preferences = ref<NotificationPreferences>(loadPreferences());

// Computed
const hasPermission = computed(() => notificationPermission.value === NotificationPermission.GRANTED);
const isPushEnabled = computed(() => hasPermission.value && pushSubscription.value !== null);

/**
 * Load notification preferences from localStorage
 */
function loadPreferences(): NotificationPreferences {
    try {
        const stored = localStorage.getItem('notification-preferences');
        if (stored) {
            return { ...defaultPreferences, ...JSON.parse(stored) };
        }
    } catch (error) {
        console.error('Failed to load notification preferences:', error);
    }
    return defaultPreferences;
}

/**
 * Save notification preferences to localStorage
 */
function savePreferences(prefs: NotificationPreferences): void {
    try {
        localStorage.setItem('notification-preferences', JSON.stringify(prefs));
        preferences.value = prefs;
    } catch (error) {
        console.error('Failed to save notification preferences:', error);
    }
}

/**
 * Notification Service
 * Handles Web Push notifications and local notifications
 */
export class NotificationService {
    private scheduledReminders: Map<string, number> = new Map();
    private timerStartTime: Date | null = null;

    /**
     * Check if notifications are supported
     */
    isNotificationSupported(): boolean {
        return isSupported.value;
    }

    /**
     * Request notification permission
     */
    async requestPermission(): Promise<NotificationPermission> {
        if (!isSupported.value) {
            console.warn('Notifications not supported');
            return NotificationPermission.DENIED;
        }

        try {
            const permission = await Notification.requestPermission();
            notificationPermission.value = permission as NotificationPermission;
            return permission as NotificationPermission;
        } catch (error) {
            console.error('Failed to request notification permission:', error);
            return NotificationPermission.DENIED;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPush(vapidPublicKey: string): Promise<PushSubscription | null> {
        if (!hasPermission.value) {
            console.warn('Notification permission not granted');
            return null;
        }

        try {
            const registration = await navigator.serviceWorker.ready;

            // Check if already subscribed
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                // Subscribe to push notifications
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });
            }

            pushSubscription.value = subscription;

            // Send subscription to backend
            await this.sendSubscriptionToBackend(subscription);

            console.log('Push subscription successful');
            return subscription;
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
            return null;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribeFromPush(): Promise<boolean> {
        try {
            if (pushSubscription.value) {
                await pushSubscription.value.unsubscribe();
                await this.removeSubscriptionFromBackend();
                pushSubscription.value = null;
                console.log('Push unsubscription successful');
                return true;
            }
            return false;
        } catch (error) {
            console.error('Failed to unsubscribe from push notifications:', error);
            return false;
        }
    }

    /**
     * Send push subscription to backend
     */
    private async sendSubscriptionToBackend(subscription: PushSubscription): Promise<void> {
        try {
            await axios.post('/api/v1/push-subscriptions', {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                    auth: this.arrayBufferToBase64(subscription.getKey('auth'))
                }
            });
        } catch (error) {
            console.error('Failed to send subscription to backend:', error);
            throw error;
        }
    }

    /**
     * Remove push subscription from backend
     */
    private async removeSubscriptionFromBackend(): Promise<void> {
        try {
            await axios.delete('/api/v1/push-subscriptions');
        } catch (error) {
            console.error('Failed to remove subscription from backend:', error);
        }
    }

    /**
     * Show a local notification
     */
    async showNotification(
        title: string,
        options: ExtendedNotificationOptions = {}
    ): Promise<void> {
        if (!hasPermission.value) {
            console.warn('Cannot show notification: permission not granted');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;

            const defaultOptions: ExtendedNotificationOptions = {
                icon: '/images/pwa-192x192.png',
                badge: '/images/pwa-192x192-maskable.png',
                vibrate: [200, 100, 200],
                tag: options.type || 'general',
                requireInteraction: false,
                ...options
            };

            await registration.showNotification(title, defaultOptions);
        } catch (error) {
            console.error('Failed to show notification:', error);
        }
    }

    /**
     * Start timer reminders
     */
    startTimerReminders(): void {
        if (!preferences.value.timerReminders || !hasPermission.value) {
            return;
        }

        this.timerStartTime = new Date();
        this.scheduleTimerReminder();
    }

    /**
     * Stop timer reminders
     */
    stopTimerReminders(): void {
        this.timerStartTime = null;
        this.clearScheduledReminder('timer');
    }

    /**
     * Schedule a timer reminder
     */
    private scheduleTimerReminder(): void {
        if (!preferences.value.timerReminders || !this.timerStartTime) {
            return;
        }

        const intervalMs = preferences.value.timerReminderInterval * 60 * 1000;

        const timeoutId = window.setTimeout(() => {
            const hours = Math.floor(preferences.value.timerReminderInterval / 60);
            const minutes = preferences.value.timerReminderInterval % 60;
            const timeStr = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

            this.showNotification('Time Tracking Reminder', {
                body: `You've been tracking time for ${timeStr}. Don't forget to take breaks!`,
                type: NotificationType.TIMER_REMINDER,
                icon: '/images/pwa-192x192.png',
                actions: [
                    { action: 'stop', title: 'Stop Timer' },
                    { action: 'view', title: 'View Details' }
                ]
            });

            // Schedule next reminder
            this.scheduleTimerReminder();
        }, intervalMs);

        this.scheduledReminders.set('timer', timeoutId);
    }

    /**
     * Schedule break reminder
     */
    scheduleBreakReminder(): void {
        if (!preferences.value.breakReminders || !hasPermission.value) {
            return;
        }

        const intervalMs = preferences.value.breakReminderInterval * 60 * 1000;

        const timeoutId = window.setTimeout(() => {
            this.showNotification('Break Reminder', {
                body: 'Time for a break! Taking regular breaks helps maintain productivity.',
                type: NotificationType.BREAK_REMINDER,
                icon: '/images/pwa-192x192.png',
                actions: [
                    { action: 'dismiss', title: 'Dismiss' },
                    { action: 'snooze', title: 'Snooze 15m' }
                ]
            });

            // Schedule next reminder
            this.scheduleBreakReminder();
        }, intervalMs);

        this.scheduledReminders.set('break', timeoutId);
    }

    /**
     * Schedule daily summary notification
     */
    scheduleDailySummary(): void {
        if (!preferences.value.dailySummary || !hasPermission.value) {
            return;
        }

        const nextTime = this.getNextScheduledTime(preferences.value.dailySummaryTime);
        const delay = nextTime.getTime() - Date.now();

        if (delay > 0) {
            const timeoutId = window.setTimeout(() => {
                this.sendDailySummary();
                // Schedule for next day
                this.scheduleDailySummary();
            }, delay);

            this.scheduledReminders.set('daily-summary', timeoutId);
        }
    }

    /**
     * Schedule weekly summary notification
     */
    scheduleWeeklySummary(): void {
        if (!preferences.value.weeklySummary || !hasPermission.value) {
            return;
        }

        const nextTime = this.getNextWeeklyTime(
            preferences.value.weeklySummaryDay,
            preferences.value.weeklySummaryTime
        );
        const delay = nextTime.getTime() - Date.now();

        if (delay > 0) {
            const timeoutId = window.setTimeout(() => {
                this.sendWeeklySummary();
                // Schedule for next week
                this.scheduleWeeklySummary();
            }, delay);

            this.scheduledReminders.set('weekly-summary', timeoutId);
        }
    }

    /**
     * Schedule end of day reminder
     */
    scheduleEndOfDayReminder(): void {
        if (!preferences.value.endOfDayReminder || !hasPermission.value) {
            return;
        }

        const nextTime = this.getNextScheduledTime(preferences.value.endOfDayTime);
        const delay = nextTime.getTime() - Date.now();

        if (delay > 0) {
            const timeoutId = window.setTimeout(() => {
                this.showNotification('End of Day Reminder', {
                    body: 'Remember to stop your timer and review your time entries for today.',
                    type: NotificationType.END_OF_DAY,
                    icon: '/images/pwa-192x192.png',
                    actions: [
                        { action: 'view', title: 'View Today' },
                        { action: 'dismiss', title: 'Dismiss' }
                    ]
                });

                // Schedule for next day
                this.scheduleEndOfDayReminder();
            }, delay);

            this.scheduledReminders.set('end-of-day', timeoutId);
        }
    }

    /**
     * Send daily summary notification
     */
    private async sendDailySummary(): Promise<void> {
        try {
            // Fetch today's time tracking data from backend
            const response = await axios.get('/api/v1/time-entries/today-summary');
            const { totalMinutes, entriesCount } = response.data;

            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            const timeStr = `${hours}h ${minutes}m`;

            this.showNotification('Daily Summary', {
                body: `Today you tracked ${timeStr} across ${entriesCount} ${entriesCount === 1 ? 'entry' : 'entries'}.`,
                type: NotificationType.DAILY_SUMMARY,
                icon: '/images/pwa-192x192.png',
                actions: [
                    { action: 'view', title: 'View Details' },
                    { action: 'dismiss', title: 'Dismiss' }
                ]
            });
        } catch (error) {
            console.error('Failed to send daily summary:', error);
        }
    }

    /**
     * Send weekly summary notification
     */
    private async sendWeeklySummary(): Promise<void> {
        try {
            // Fetch this week's time tracking data from backend
            const response = await axios.get('/api/v1/time-entries/week-summary');
            const { totalMinutes, entriesCount, billableMinutes } = response.data;

            const hours = Math.floor(totalMinutes / 60);
            const timeStr = `${hours}h`;

            this.showNotification('Weekly Summary', {
                body: `This week: ${timeStr} tracked, ${entriesCount} entries, ${billableMinutes} billable minutes.`,
                type: NotificationType.WEEKLY_SUMMARY,
                icon: '/images/pwa-192x192.png',
                actions: [
                    { action: 'view', title: 'View Report' },
                    { action: 'dismiss', title: 'Dismiss' }
                ]
            });
        } catch (error) {
            console.error('Failed to send weekly summary:', error);
        }
    }

    /**
     * Clear a scheduled reminder
     */
    private clearScheduledReminder(key: string): void {
        const timeoutId = this.scheduledReminders.get(key);
        if (timeoutId) {
            window.clearTimeout(timeoutId);
            this.scheduledReminders.delete(key);
        }
    }

    /**
     * Clear all scheduled reminders
     */
    clearAllReminders(): void {
        this.scheduledReminders.forEach((timeoutId) => {
            window.clearTimeout(timeoutId);
        });
        this.scheduledReminders.clear();
    }

    /**
     * Update notification preferences
     */
    updatePreferences(newPreferences: Partial<NotificationPreferences>): void {
        const updated = { ...preferences.value, ...newPreferences };
        savePreferences(updated);

        // Reschedule reminders based on new preferences
        this.rescheduleAll();
    }

    /**
     * Reschedule all reminders
     */
    private rescheduleAll(): void {
        this.clearAllReminders();

        if (preferences.value.enabled && hasPermission.value) {
            if (preferences.value.breakReminders) {
                this.scheduleBreakReminder();
            }
            if (preferences.value.dailySummary) {
                this.scheduleDailySummary();
            }
            if (preferences.value.weeklySummary) {
                this.scheduleWeeklySummary();
            }
            if (preferences.value.endOfDayReminder) {
                this.scheduleEndOfDayReminder();
            }
            if (preferences.value.timerReminders && this.timerStartTime) {
                this.scheduleTimerReminder();
            }
        }
    }

    /**
     * Get next scheduled time for HH:MM format
     */
    private getNextScheduledTime(timeStr: string): Date {
        const [hours, minutes] = timeStr.split(':').map(Number);
        const now = new Date();
        const scheduled = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes);

        // If time has passed today, schedule for tomorrow
        if (scheduled <= now) {
            scheduled.setDate(scheduled.getDate() + 1);
        }

        return scheduled;
    }

    /**
     * Get next weekly scheduled time
     */
    private getNextWeeklyTime(dayOfWeek: number, timeStr: string): Date {
        const [hours, minutes] = timeStr.split(':').map(Number);
        const now = new Date();
        const currentDay = now.getDay();

        let daysUntil = dayOfWeek - currentDay;
        if (daysUntil < 0) {
            daysUntil += 7;
        } else if (daysUntil === 0) {
            // If it's today, check if time has passed
            const scheduled = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes);
            if (scheduled <= now) {
                daysUntil = 7;
            }
        }

        const scheduled = new Date(now);
        scheduled.setDate(scheduled.getDate() + daysUntil);
        scheduled.setHours(hours, minutes, 0, 0);

        return scheduled;
    }

    /**
     * Utility: Convert URL-safe Base64 to Uint8Array
     */
    private urlBase64ToUint8Array(base64String: string): Uint8Array {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Utility: Convert ArrayBuffer to Base64
     */
    private arrayBufferToBase64(buffer: ArrayBuffer | null): string {
        if (!buffer) return '';
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
}

// Export singleton instance
export const notificationService = new NotificationService();

// Export reactive state for components
export const useNotificationState = () => ({
    notificationPermission: computed(() => notificationPermission.value),
    hasPermission,
    isSupported,
    isPushEnabled,
    preferences: computed(() => preferences.value)
});

// Export helper functions
export const notificationHelpers = {
    requestPermission: () => notificationService.requestPermission(),
    subscribeToPush: (vapidKey: string) => notificationService.subscribeToPush(vapidKey),
    unsubscribeFromPush: () => notificationService.unsubscribeFromPush(),
    updatePreferences: (prefs: Partial<NotificationPreferences>) => notificationService.updatePreferences(prefs),
    showNotification: (title: string, options?: ExtendedNotificationOptions) => notificationService.showNotification(title, options),
    startTimerReminders: () => notificationService.startTimerReminders(),
    stopTimerReminders: () => notificationService.stopTimerReminders()
};
