import { onMounted, onUnmounted } from 'vue';
import {
    notificationService,
    useNotificationState,
    notificationHelpers,
    type NotificationPreferences,
    NotificationPermission,
    NotificationType
} from '@/utils/notificationService';

/**
 * Composable for notification functionality
 */
export function useNotifications() {
    const state = useNotificationState();

    /**
     * Initialize notifications
     */
    const initialize = async () => {
        if (!state.isSupported.value) {
            console.warn('Notifications not supported in this browser');
            return;
        }

        // If permission already granted, set up reminders
        if (state.hasPermission.value && state.preferences.value.enabled) {
            setupReminders();
        }
    };

    /**
     * Request notification permission from user
     */
    const requestPermission = async (): Promise<NotificationPermission> => {
        const permission = await notificationHelpers.requestPermission();

        if (permission === NotificationPermission.GRANTED) {
            await setupReminders();
        }

        return permission;
    };

    /**
     * Subscribe to push notifications
     */
    const enablePushNotifications = async (): Promise<boolean> => {
        try {
            // VAPID public key should be retrieved from backend
            const response = await fetch('/api/v1/vapid-public-key');
            const { publicKey } = await response.json();

            const subscription = await notificationHelpers.subscribeToPush(publicKey);
            return subscription !== null;
        } catch (error) {
            console.error('Failed to enable push notifications:', error);
            return false;
        }
    };

    /**
     * Disable push notifications
     */
    const disablePushNotifications = async (): Promise<boolean> => {
        return await notificationHelpers.unsubscribeFromPush();
    };

    /**
     * Setup all notification reminders
     */
    const setupReminders = () => {
        if (!state.hasPermission.value || !state.preferences.value.enabled) {
            return;
        }

        // Schedule all enabled reminders
        if (state.preferences.value.breakReminders) {
            notificationService.scheduleBreakReminder();
        }
        if (state.preferences.value.dailySummary) {
            notificationService.scheduleDailySummary();
        }
        if (state.preferences.value.weeklySummary) {
            notificationService.scheduleWeeklySummary();
        }
        if (state.preferences.value.endOfDayReminder) {
            notificationService.scheduleEndOfDayReminder();
        }
    };

    /**
     * Update notification preferences
     */
    const updatePreferences = (preferences: Partial<NotificationPreferences>) => {
        notificationHelpers.updatePreferences(preferences);
    };

    /**
     * Show a test notification
     */
    const showTestNotification = async () => {
        await notificationHelpers.showNotification('Test Notification', {
            body: 'Notifications are working! You\'ll receive reminders and summaries based on your preferences.',
            icon: '/images/pwa-192x192.png',
            badge: '/images/pwa-192x192-maskable.png'
        });
    };

    /**
     * Start timer with reminders
     */
    const startTimerWithReminders = () => {
        if (state.hasPermission.value && state.preferences.value.timerReminders) {
            notificationHelpers.startTimerReminders();
        }
    };

    /**
     * Stop timer and clear reminders
     */
    const stopTimerWithReminders = () => {
        notificationHelpers.stopTimerReminders();
    };

    /**
     * Send a custom notification
     */
    const notify = async (title: string, body: string, type?: NotificationType) => {
        if (!state.hasPermission.value) {
            console.warn('Cannot send notification: permission not granted');
            return;
        }

        await notificationHelpers.showNotification(title, {
            body,
            type,
            icon: '/images/pwa-192x192.png',
            badge: '/images/pwa-192x192-maskable.png'
        });
    };

    // Lifecycle
    onMounted(() => {
        initialize();
    });

    onUnmounted(() => {
        // Cleanup is handled by the service
    });

    return {
        // State
        notificationPermission: state.notificationPermission,
        hasPermission: state.hasPermission,
        isSupported: state.isSupported,
        isPushEnabled: state.isPushEnabled,
        preferences: state.preferences,

        // Methods
        initialize,
        requestPermission,
        enablePushNotifications,
        disablePushNotifications,
        updatePreferences,
        showTestNotification,
        startTimerWithReminders,
        stopTimerWithReminders,
        notify
    };
}
