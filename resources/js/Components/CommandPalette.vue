<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click="close"
                @keydown.esc="close"
            >
                <!-- Command Palette -->
                <div
                    class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden mt-[10vh] transform transition-all"
                    @click.stop
                >
                    <!-- Search Input -->
                    <div class="relative border-b border-gray-200 dark:border-gray-700">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Type a command or search..."
                            class="w-full pl-12 pr-4 py-4 text-lg bg-transparent border-none focus:ring-0 focus:outline-none text-gray-900 dark:text-white placeholder-gray-400"
                            @keydown.down.prevent="selectNext"
                            @keydown.up.prevent="selectPrevious"
                            @keydown.enter.prevent="executeSelected"
                        />
                    </div>

                    <!-- Command List -->
                    <div class="max-h-[60vh] overflow-y-auto">
                        <!-- No Results -->
                        <div
                            v-if="filteredCommands.length === 0"
                            class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                        >
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>No commands found</p>
                            <p class="text-sm mt-1">Try a different search term</p>
                        </div>

                        <!-- Command Groups -->
                        <div v-else class="py-2">
                            <div
                                v-for="(group, category) in groupedCommands"
                                :key="category"
                                class="mb-2 last:mb-0"
                            >
                                <!-- Category Header -->
                                <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ category }}
                                </div>

                                <!-- Commands in Category -->
                                <div>
                                    <button
                                        v-for="(command, index) in group"
                                        :key="command.id"
                                        :ref="el => { if (el) commandRefs[command.id] = el as HTMLButtonElement }"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                        :class="selectedIndex === getGlobalIndex(command)
                                            ? 'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-900 dark:text-cyan-100'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 text-gray-900 dark:text-gray-100'"
                                        @click="execute(command)"
                                        @mouseenter="selectedIndex = getGlobalIndex(command)"
                                    >
                                        <!-- Icon -->
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                            :class="selectedIndex === getGlobalIndex(command)
                                                ? 'bg-cyan-100 dark:bg-cyan-900/50'
                                                : 'bg-gray-100 dark:bg-gray-800'"
                                        >
                                            <component
                                                :is="command.icon"
                                                class="w-5 h-5"
                                                :class="selectedIndex === getGlobalIndex(command)
                                                    ? 'text-cyan-600 dark:text-cyan-400'
                                                    : 'text-gray-600 dark:text-gray-400'"
                                            />
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium">{{ command.title }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                {{ command.description }}
                                            </div>
                                        </div>

                                        <!-- Shortcut -->
                                        <div
                                            v-if="command.shortcut"
                                            class="flex-shrink-0 flex items-center gap-1"
                                        >
                                            <kbd
                                                v-for="(key, i) in command.shortcut.split('+')"
                                                :key="i"
                                                class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"
                                            >
                                                {{ key }}
                                            </kbd>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-900/50">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-mono bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600">↑</kbd>
                                    <kbd class="px-1.5 py-0.5 font-mono bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600">↓</kbd>
                                    to navigate
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-mono bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600">↵</kbd>
                                    to select
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-mono bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600">esc</kbd>
                                    to close
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, h } from 'vue';
import { router } from '@inertiajs/vue3';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import { ShortcutCategory } from '@/utils/keyboardShortcuts';

// Command interface
interface Command {
    id: string;
    title: string;
    description: string;
    category: string;
    icon: any;
    shortcut?: string;
    action: () => void | Promise<void>;
    keywords?: string[];
}

// Props
const props = withDefaults(defineProps<{
    commands?: Command[];
}>(), {
    commands: () => []
});

// Emit
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'execute', command: Command): void;
}>();

// State
const isOpen = ref(false);
const searchQuery = ref('');
const selectedIndex = ref(0);
const searchInput = ref<HTMLInputElement | null>(null);
const commandRefs = ref<Record<string, HTMLButtonElement>>({});

// Default commands
const defaultCommands = computed<Command[]>(() => [
    // Timer commands
    {
        id: 'start-timer',
        title: 'Start Timer',
        description: 'Start tracking time for a new activity',
        category: 'Timer',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
        ]),
        shortcut: '⌘+T',
        action: () => {
            // Emit event to start timer
            close();
        },
        keywords: ['time', 'track', 'start']
    },
    {
        id: 'stop-timer',
        title: 'Stop Timer',
        description: 'Stop the currently running timer',
        category: 'Timer',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }),
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z' })
        ]),
        action: () => {
            close();
        },
        keywords: ['time', 'stop', 'end']
    },

    // Navigation commands
    {
        id: 'go-dashboard',
        title: 'Go to Dashboard',
        description: 'Navigate to the dashboard',
        category: 'Navigation',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
        ]),
        shortcut: '⌘+⇧+D',
        action: () => {
            router.visit('/dashboard');
            close();
        },
        keywords: ['home', 'overview']
    },
    {
        id: 'go-time-entries',
        title: 'Go to Time Entries',
        description: 'View all your time entries',
        category: 'Navigation',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })
        ]),
        shortcut: '⌘+⇧+E',
        action: () => {
            router.visit('/time');
            close();
        },
        keywords: ['entries', 'log', 'history']
    },
    {
        id: 'go-projects',
        title: 'Go to Projects',
        description: 'Manage your projects',
        category: 'Navigation',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' })
        ]),
        shortcut: '⌘+⇧+P',
        action: () => {
            router.visit('/projects');
            close();
        },
        keywords: ['folder', 'organize']
    },
    {
        id: 'go-reports',
        title: 'Go to Reports',
        description: 'View time tracking reports and analytics',
        category: 'Navigation',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
        ]),
        shortcut: '⌘+⇧+R',
        action: () => {
            router.visit('/reporting');
            close();
        },
        keywords: ['analytics', 'stats', 'charts']
    },
    {
        id: 'go-settings',
        title: 'Go to Settings',
        description: 'Manage your account settings',
        category: 'Navigation',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
        ]),
        action: () => {
            router.visit('/profile');
            close();
        },
        keywords: ['preferences', 'profile', 'account']
    },

    // Action commands
    {
        id: 'new-project',
        title: 'New Project',
        description: 'Create a new project',
        category: 'Actions',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4v16m8-8H4' })
        ]),
        shortcut: '⌘+⇧+N',
        action: () => {
            // Emit event to create new project
            close();
        },
        keywords: ['create', 'add']
    },
    {
        id: 'export-data',
        title: 'Export Data',
        description: 'Export your time tracking data',
        category: 'Actions',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10' })
        ]),
        action: () => {
            close();
        },
        keywords: ['download', 'backup', 'csv']
    },

    // Help commands
    {
        id: 'keyboard-shortcuts',
        title: 'Keyboard Shortcuts',
        description: 'View all keyboard shortcuts',
        category: 'Help',
        icon: h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' })
        ]),
        shortcut: '?',
        action: () => {
            // Show keyboard shortcuts modal
            close();
        },
        keywords: ['help', 'keys', 'hotkeys']
    }
]);

// All available commands
const allCommands = computed(() => [...defaultCommands.value, ...props.commands]);

// Filter commands based on search query
const filteredCommands = computed(() => {
    if (!searchQuery.value) {
        return allCommands.value;
    }

    const query = searchQuery.value.toLowerCase();
    return allCommands.value.filter(command => {
        const titleMatch = command.title.toLowerCase().includes(query);
        const descMatch = command.description.toLowerCase().includes(query);
        const keywordMatch = command.keywords?.some(k => k.toLowerCase().includes(query));
        return titleMatch || descMatch || keywordMatch;
    });
});

// Group commands by category
const groupedCommands = computed(() => {
    const groups: Record<string, Command[]> = {};
    filteredCommands.value.forEach(command => {
        if (!groups[command.category]) {
            groups[command.category] = [];
        }
        groups[command.category].push(command);
    });
    return groups;
});

// Get global index of command
const getGlobalIndex = (command: Command): number => {
    return filteredCommands.value.findIndex(c => c.id === command.id);
};

// Open command palette
const open = () => {
    isOpen.value = true;
    searchQuery.value = '';
    selectedIndex.value = 0;
    nextTick(() => {
        searchInput.value?.focus();
    });
};

// Close command palette
const close = () => {
    isOpen.value = false;
    emit('close');
};

// Select next command
const selectNext = () => {
    selectedIndex.value = Math.min(selectedIndex.value + 1, filteredCommands.value.length - 1);
    scrollToSelected();
};

// Select previous command
const selectPrevious = () => {
    selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
    scrollToSelected();
};

// Scroll to selected command
const scrollToSelected = () => {
    const selected = filteredCommands.value[selectedIndex.value];
    if (selected) {
        const el = commandRefs.value[selected.id];
        el?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
};

// Execute selected command
const executeSelected = () => {
    const command = filteredCommands.value[selectedIndex.value];
    if (command) {
        execute(command);
    }
};

// Execute command
const execute = async (command: Command) => {
    emit('execute', command);
    await command.action();
};

// Reset selection when search changes
watch(searchQuery, () => {
    selectedIndex.value = 0;
});

// Register keyboard shortcut to open palette
useKeyboardShortcut('command-palette', {
    key: 'k',
    meta: true,
    description: 'Open command palette',
    category: ShortcutCategory.GENERAL,
    handler: () => {
        if (isOpen.value) {
            close();
        } else {
            open();
        }
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

kbd {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>
