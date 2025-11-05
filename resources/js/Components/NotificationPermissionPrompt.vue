<template>
    <div v-if="shouldShowPrompt && !dismissed">
        <!-- Card Style Prompt -->
        <Transition name="slide-up">
            <div
                v-if="!hasPermission"
                class="fixed bottom-4 left-4 z-50 max-w-md w-full mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Icon Header -->
                <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 p-4">
                    <div class="flex items-center gap-3 text-white">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Enable Notifications</h3>
                            <p class="text-sm text-cyan-100">Stay on top of your time tracking</p>
                        </div>
                        <button
                            @click="handleDismiss"
                            class="ml-auto p-1 hover:bg-white/20 rounded transition-colors"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4 space-y-4">
                    <p class="text-gray-700 dark:text-gray-300">
                        Get notified about:
                    </p>

                    <!-- Features List -->
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Timer Reminders</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Gentle reminders when you've been tracking for a while</p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Daily Summaries</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Review your productivity at the end of each day</p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Break Reminders</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Stay healthy with regular break notifications</p>
                            </div>
                        </li>
                    </ul>

                    <!-- Privacy Note -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <p>
                                🇪🇺 <strong>Privacy First:</strong> Notifications are processed locally.
                                No personal data is sent to third-party services.
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-2">
                        <button
                            @click="handleAllow"
                            :disabled="isRequesting"
                            class="flex-1 px-4 py-2.5 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                        >
                            <svg v-if="isRequesting" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ isRequesting ? 'Requesting...' : 'Enable Notifications' }}</span>
                        </button>
                        <button
                            @click="handleDismiss"
                            :disabled="isRequesting"
                            class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                        >
                            Not Now
                        </button>
                    </div>

                    <!-- Settings Link -->
                    <p class="text-xs text-gray-500 dark:text-gray-500 text-center">
                        You can customize notification preferences in
                        <a href="/profile" class="text-cyan-600 dark:text-cyan-400 hover:underline">Settings</a>
                    </p>
                </div>
            </div>
        </Transition>

        <!-- Permission Denied Message -->
        <Transition name="fade">
            <div
                v-if="showDeniedMessage"
                class="fixed bottom-4 left-4 z-50 max-w-md w-full mx-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-medium text-red-900 dark:text-red-200 mb-1">Notifications Blocked</h4>
                        <p class="text-sm text-red-800 dark:text-red-300 mb-2">
                            To enable notifications, please update your browser settings.
                        </p>
                        <button
                            @click="showDeniedMessage = false"
                            class="text-sm text-red-700 dark:text-red-300 underline hover:no-underline"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Success Message -->
        <Transition name="fade">
            <div
                v-if="showSuccessMessage"
                class="fixed bottom-4 left-4 z-50 max-w-md w-full mx-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg shadow-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-medium text-green-900 dark:text-green-200 mb-1">Notifications Enabled!</h4>
                        <p class="text-sm text-green-800 dark:text-green-300">
                            You'll receive reminders and summaries based on your preferences.
                        </p>
                    </div>
                    <button
                        @click="showSuccessMessage = false"
                        class="p-1 hover:bg-green-100 dark:hover:bg-green-900/40 rounded transition-colors"
                        aria-label="Close"
                    >
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import { NotificationPermission } from '@/utils/notificationService';

const props = withDefaults(defineProps<{
    autoShow?: boolean;
    showDelay?: number; // Delay in ms before showing prompt
}>(), {
    autoShow: true,
    showDelay: 5000 // 5 seconds default
});

const emit = defineEmits<{
    (e: 'allowed'): void;
    (e: 'denied'): void;
    (e: 'dismissed'): void;
}>();

const { notificationPermission, hasPermission, isSupported, requestPermission } = useNotifications();

const dismissed = ref(false);
const isRequesting = ref(false);
const showDeniedMessage = ref(false);
const showSuccessMessage = ref(false);

const shouldShowPrompt = computed(() => {
    return (
        isSupported.value &&
        notificationPermission.value === NotificationPermission.DEFAULT &&
        !wasDismissedRecently() &&
        props.autoShow
    );
});

/**
 * Check if prompt was dismissed recently
 */
function wasDismissedRecently(): boolean {
    const dismissedAt = localStorage.getItem('notification-prompt-dismissed');
    if (!dismissedAt) return false;

    const dismissedDate = new Date(dismissedAt);
    const daysSinceDismissed = (Date.now() - dismissedDate.getTime()) / (1000 * 60 * 60 * 24);

    // Show again after 7 days
    return daysSinceDismissed < 7;
}

/**
 * Handle allow notifications
 */
async function handleAllow() {
    isRequesting.value = true;

    try {
        const permission = await requestPermission();

        if (permission === NotificationPermission.GRANTED) {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 5000);
            emit('allowed');
        } else if (permission === NotificationPermission.DENIED) {
            showDeniedMessage.value = true;
            emit('denied');
        }
    } catch (error) {
        console.error('Failed to request notification permission:', error);
    } finally {
        isRequesting.value = false;
        dismissed.value = true;
    }
}

/**
 * Handle dismiss
 */
function handleDismiss() {
    dismissed.value = true;
    localStorage.setItem('notification-prompt-dismissed', new Date().toISOString());
    emit('dismissed');
}

/**
 * Manually show the prompt
 */
function show() {
    if (isSupported.value && notificationPermission.value === NotificationPermission.DEFAULT) {
        dismissed.value = false;
    }
}

// Expose methods for parent components
defineExpose({
    show
});

onMounted(() => {
    // Auto-show with delay
    if (shouldShowPrompt.value && props.autoShow) {
        setTimeout(() => {
            dismissed.value = false;
        }, props.showDelay);
    }
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from {
    transform: translateY(100%);
    opacity: 0;
}

.slide-up-leave-to {
    transform: translateY(100%);
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
