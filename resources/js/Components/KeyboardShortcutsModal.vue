<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click="close"
            >
                <!-- Modal -->
                <div
                    class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Keyboard Shortcuts
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Master Timeclocker with these keyboard shortcuts
                            </p>
                        </div>
                        <button
                            @click="close"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="max-h-[70vh] overflow-y-auto p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- General Shortcuts -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    General
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Command Palette</span>
                                        <kbd class="kbd">{{ modKey }}+K</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Search</span>
                                        <kbd class="kbd">{{ modKey }}+F</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Show Shortcuts</span>
                                        <kbd class="kbd">?</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Close Modal</span>
                                        <kbd class="kbd">Esc</kbd>
                                    </div>
                                </div>
                            </div>

                            <!-- Timer Shortcuts -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Timer
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Start/Stop Timer</span>
                                        <kbd class="kbd">{{ modKey }}+T</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Quick Start (Last Project)</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+T</kbd>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Shortcuts -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    Navigation
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Go to Dashboard</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+D</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Go to Time Entries</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+E</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Go to Projects</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+P</kbd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Go to Reports</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+R</kbd>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Shortcuts -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Actions
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">New Project</span>
                                        <kbd class="kbd">{{ modKey }}+Shift+N</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Section -->
                        <div class="mt-8 p-4 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-cyan-900 dark:text-cyan-100 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                Pro Tips
                            </h4>
                            <ul class="text-xs text-cyan-800 dark:text-cyan-200 space-y-1">
                                <li>• Use {{ modKey }}+K to quickly access any command without touching your mouse</li>
                                <li>• Press ? from anywhere to open this shortcuts guide</li>
                                <li>• {{ modKey }}+T is your friend - start and stop timers instantly</li>
                                <li>• Navigate between pages with {{ modKey }}+Shift+[key] for lightning-fast workflow</li>
                            </ul>
                        </div>

                        <!-- Platform Note -->
                        <div class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ platformNote }}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Press <kbd class="kbd-small">Esc</kbd> or click outside to close
                        </div>
                        <button
                            @click="close"
                            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import { ShortcutCategory, isMac } from '@/utils/keyboardShortcuts';

// State
const isOpen = ref(false);

// Computed
const modKey = computed(() => isMac ? '⌘' : 'Ctrl');
const platformNote = computed(() =>
    isMac
        ? '⌘ = Command key | ⌥ = Option key | ⇧ = Shift key'
        : 'Ctrl = Control key | Alt = Alt key | Shift = Shift key'
);

// Methods
const open = () => {
    isOpen.value = true;
};

const close = () => {
    isOpen.value = false;
};

// Register keyboard shortcut to open help
useKeyboardShortcut('keyboard-shortcuts-help', {
    key: '?',
    shift: true,
    description: 'Show keyboard shortcuts',
    category: ShortcutCategory.GENERAL,
    handler: () => {
        open();
    }
});

// Expose methods
defineExpose({
    open,
    close
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

.kbd {
    @apply inline-flex items-center px-3 py-1.5 text-sm font-mono font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm;
}

.kbd-small {
    @apply inline-flex items-center px-2 py-0.5 text-xs font-mono font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded;
}
</style>
