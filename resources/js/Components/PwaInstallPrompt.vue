<template>
    <div v-if="showInstallPrompt && !dismissed">
        <!-- Banner Prompt (Top) -->
        <Transition name="slide-down">
            <div
                v-if="promptStyle === 'banner'"
                class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-cyan-600 to-cyan-700 text-white shadow-lg"
            >
                <div class="container mx-auto px-4 py-3 flex items-center justify-between gap-4">
                    <!-- Icon and Text -->
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Install Timeclocker</div>
                            <div class="text-sm text-cyan-100">
                                Get quick access and track time offline
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            @click="handleInstall"
                            class="px-4 py-2 bg-white text-cyan-700 font-medium rounded-lg hover:bg-cyan-50 transition-colors"
                        >
                            Install
                        </button>
                        <button
                            @click="handleDismiss"
                            class="p-2 hover:bg-cyan-600/50 rounded transition-colors"
                            aria-label="Dismiss"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Card Prompt (Bottom Right) -->
        <Transition name="slide-up">
            <div
                v-if="promptStyle === 'card'"
                class="fixed bottom-4 right-4 z-50 max-w-sm w-full mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Header with App Icon -->
                <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 p-4 text-white">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-16 h-16 bg-white rounded-lg p-2 shadow-md">
                            <img
                                src="/images/pwa-192x192.png"
                                alt="Timeclocker"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg">Timeclocker</h3>
                            <p class="text-sm text-cyan-100">timeclocker.app</p>
                        </div>
                        <button
                            @click="handleDismiss"
                            class="p-1 hover:bg-white/20 rounded transition-colors"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4 space-y-3">
                    <p class="text-gray-700 dark:text-gray-300">
                        Install Timeclocker for quick access and offline time tracking.
                    </p>

                    <!-- Features -->
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Works offline</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>One-click access</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Faster performance</span>
                        </li>
                    </ul>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-2">
                        <button
                            @click="handleInstall"
                            class="flex-1 px-4 py-2 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 transition-colors"
                        >
                            Install App
                        </button>
                        <button
                            @click="handleDismiss"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Not Now
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-500 text-center">
                        You can always install from your browser menu
                    </p>
                </div>
            </div>
        </Transition>

        <!-- Modal Prompt (Center) -->
        <Transition name="fade">
            <div
                v-if="promptStyle === 'modal'"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full overflow-hidden"
                    @click.stop
                >
                    <!-- Icon -->
                    <div class="bg-gradient-to-br from-cyan-500 to-cyan-700 p-8 text-center">
                        <div class="inline-block p-4 bg-white rounded-2xl shadow-lg">
                            <img
                                src="/images/pwa-192x192.png"
                                alt="Timeclocker"
                                class="w-20 h-20"
                            />
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-4">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                Install Timeclocker
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Get the full experience with offline tracking and faster performance
                            </p>
                        </div>

                        <!-- Features Grid -->
                        <div class="grid grid-cols-3 gap-4 py-4">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Fast</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Offline</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Reliable</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-2">
                            <button
                                @click="handleInstall"
                                class="w-full px-6 py-3 bg-cyan-600 text-white font-semibold rounded-lg hover:bg-cyan-700 transition-colors"
                            >
                                Install Now
                            </button>
                            <button
                                @click="handleDismiss"
                                class="w-full px-6 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                            >
                                Maybe Later
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const props = withDefaults(defineProps<{
    promptStyle?: 'banner' | 'card' | 'modal';
    autoShow?: boolean;
    showDelay?: number; // Delay in ms before showing the prompt
}>(), {
    promptStyle: 'card',
    autoShow: true,
    showDelay: 3000 // 3 seconds default
});

const emit = defineEmits<{
    (e: 'installed'): void;
    (e: 'dismissed'): void;
}>();

const showInstallPrompt = ref(false);
const dismissed = ref(false);
const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);

/**
 * Check if the app is already installed
 */
const isAppInstalled = (): boolean => {
    // Check if running as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return true;
    }

    // Check iOS standalone mode
    if ((window.navigator as any).standalone === true) {
        return true;
    }

    return false;
};

/**
 * Check if install prompt was dismissed recently
 */
const wasRecentlyDismissed = (): boolean => {
    const dismissedAt = localStorage.getItem('pwa-install-dismissed');
    if (!dismissedAt) return false;

    const dismissedDate = new Date(dismissedAt);
    const daysSinceDismissed = (Date.now() - dismissedDate.getTime()) / (1000 * 60 * 60 * 24);

    // Show again after 7 days
    return daysSinceDismissed < 7;
};

/**
 * Handle the beforeinstallprompt event
 */
const handleBeforeInstallPrompt = (e: Event) => {
    // Prevent the default browser install prompt
    e.preventDefault();

    const event = e as BeforeInstallPromptEvent;
    deferredPrompt.value = event;

    // Check if we should show the prompt
    if (props.autoShow && !isAppInstalled() && !wasRecentlyDismissed()) {
        // Show after delay
        setTimeout(() => {
            showInstallPrompt.value = true;
        }, props.showDelay);
    }
};

/**
 * Handle install button click
 */
const handleInstall = async () => {
    if (!deferredPrompt.value) {
        console.warn('Install prompt not available');
        return;
    }

    // Show the browser's install prompt
    await deferredPrompt.value.prompt();

    // Wait for the user's response
    const { outcome } = await deferredPrompt.value.userChoice;

    console.log(`User ${outcome} the install prompt`);

    if (outcome === 'accepted') {
        emit('installed');
    }

    // Clear the deferred prompt
    deferredPrompt.value = null;
    showInstallPrompt.value = false;
};

/**
 * Handle dismiss button click
 */
const handleDismiss = () => {
    dismissed.value = true;
    showInstallPrompt.value = false;

    // Store dismissal timestamp
    localStorage.setItem('pwa-install-dismissed', new Date().toISOString());

    emit('dismissed');
};

/**
 * Manually show the install prompt
 */
const show = () => {
    if (!isAppInstalled() && deferredPrompt.value) {
        dismissed.value = false;
        showInstallPrompt.value = true;
    }
};

/**
 * Check if install is supported
 */
const isInstallSupported = (): boolean => {
    return 'BeforeInstallPromptEvent' in window || deferredPrompt.value !== null;
};

// Lifecycle
onMounted(() => {
    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    // Listen for app installed event
    window.addEventListener('appinstalled', () => {
        console.log('PWA was installed');
        showInstallPrompt.value = false;
        emit('installed');
    });
});

// Expose methods for parent components
defineExpose({
    show,
    isInstallSupported,
    isAppInstalled
});
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
