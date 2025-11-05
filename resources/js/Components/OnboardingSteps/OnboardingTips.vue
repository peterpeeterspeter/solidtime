<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                You're All Set! 🎉
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Here are some tips to get the most out of Timeclocker
            </p>
        </div>

        <!-- Tips Grid -->
        <div class="max-w-3xl mx-auto space-y-4">
            <!-- Keyboard Shortcuts -->
            <div class="p-5 bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 border border-cyan-200 dark:border-cyan-800 rounded-xl">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-cyan-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Master Keyboard Shortcuts</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Start/Stop Timer</span>
                                <kbd class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded font-mono text-xs">{{ modKey }}+T</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Command Palette</span>
                                <kbd class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded font-mono text-xs">{{ modKey }}+K</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Show All Shortcuts</span>
                                <kbd class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded font-mono text-xs">?</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Offline Support -->
            <div class="p-5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Works Offline</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Track time even without internet. Your entries will automatically sync when you're back online.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="p-5 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Smart Notifications</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                            Get reminders for breaks, daily summaries, and more. Configure in Settings → Notifications.
                        </p>
                        <button
                            @click="enableNotifications"
                            class="text-sm text-purple-600 dark:text-purple-400 hover:underline font-medium"
                        >
                            Enable notifications →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile PWA -->
            <div class="p-5 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Install on Mobile</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Add Timeclocker to your home screen for quick access. Look for "Install App" or "Add to Home Screen" in your browser menu.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="max-w-3xl mx-auto mt-8">
            <h4 class="text-center text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                What would you like to do first?
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <button
                    @click="handleStartTimer"
                    class="p-4 border-2 border-cyan-200 dark:border-cyan-800 hover:border-cyan-400 rounded-lg transition-all group"
                >
                    <div class="text-3xl mb-2">⏱️</div>
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-cyan-600">Start Timer</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Begin tracking time</div>
                </button>

                <button
                    @click="handleViewProjects"
                    class="p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 rounded-lg transition-all group"
                >
                    <div class="text-3xl mb-2">📁</div>
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-cyan-600">View Projects</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Manage your work</div>
                </button>

                <button
                    @click="handleViewReports"
                    class="p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 rounded-lg transition-all group"
                >
                    <div class="text-3xl mb-2">📊</div>
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-cyan-600">View Reports</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Analyze your time</div>
                </button>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between pt-6 max-w-3xl mx-auto">
            <button
                type="button"
                @click="handlePrevious"
                class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                ← Back
            </button>
            <button
                @click="handleComplete"
                class="px-8 py-4 bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
            >
                Get Started with Timeclocker! 🚀
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { isMac } from '@/utils/keyboardShortcuts';

const emit = defineEmits<{
    (e: 'complete'): void;
    (e: 'previous'): void;
}>();

const modKey = computed(() => isMac ? '⌘' : 'Ctrl');

const handleComplete = () => {
    emit('complete');
    // Redirect to dashboard
    router.visit('/dashboard');
};

const handlePrevious = () => {
    emit('previous');
};

const handleStartTimer = () => {
    emit('complete');
    // Trigger timer start (would need global event or state management)
    router.visit('/dashboard?action=start-timer');
};

const handleViewProjects = () => {
    emit('complete');
    router.visit('/projects');
};

const handleViewReports = () => {
    emit('complete');
    router.visit('/reporting');
};

const enableNotifications = () => {
    if ('Notification' in window) {
        Notification.requestPermission();
    }
};
</script>
