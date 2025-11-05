import { ref, computed } from 'vue';

// Keyboard shortcut interface
export interface KeyboardShortcut {
    key: string;
    ctrl?: boolean;
    alt?: boolean;
    shift?: boolean;
    meta?: boolean; // Command on Mac, Windows key on Windows
    description: string;
    category: 'timer' | 'navigation' | 'actions' | 'general';
    handler: () => void | Promise<void>;
    enabled?: boolean;
}

// Platform detection
export const isMac = /Mac|iPhone|iPod|iPad/i.test(navigator.platform);
export const modifierKey = isMac ? '⌘' : 'Ctrl';

// Shortcut categories
export enum ShortcutCategory {
    TIMER = 'timer',
    NAVIGATION = 'navigation',
    ACTIONS = 'actions',
    GENERAL = 'general'
}

// Reactive state
const shortcuts = ref<Map<string, KeyboardShortcut>>(new Map());
const isEnabled = ref(true);

/**
 * Keyboard Shortcuts Service
 * Manages global keyboard shortcuts for the application
 */
export class KeyboardShortcutsService {
    private boundHandler: ((e: KeyboardEvent) => void) | null = null;

    /**
     * Initialize the keyboard shortcuts service
     */
    initialize(): void {
        this.boundHandler = this.handleKeyDown.bind(this);
        document.addEventListener('keydown', this.boundHandler);
        console.log('[KeyboardShortcuts] Service initialized');
    }

    /**
     * Cleanup event listeners
     */
    cleanup(): void {
        if (this.boundHandler) {
            document.removeEventListener('keydown', this.boundHandler);
            this.boundHandler = null;
        }
        console.log('[KeyboardShortcuts] Service cleaned up');
    }

    /**
     * Register a keyboard shortcut
     */
    register(id: string, shortcut: KeyboardShortcut): void {
        shortcuts.value.set(id, shortcut);
        console.log(`[KeyboardShortcuts] Registered: ${id}`, shortcut);
    }

    /**
     * Unregister a keyboard shortcut
     */
    unregister(id: string): void {
        shortcuts.value.delete(id);
        console.log(`[KeyboardShortcuts] Unregistered: ${id}`);
    }

    /**
     * Enable keyboard shortcuts
     */
    enable(): void {
        isEnabled.value = true;
    }

    /**
     * Disable keyboard shortcuts
     */
    disable(): void {
        isEnabled.value = false;
    }

    /**
     * Get all registered shortcuts
     */
    getShortcuts(): KeyboardShortcut[] {
        return Array.from(shortcuts.value.values());
    }

    /**
     * Get shortcuts by category
     */
    getShortcutsByCategory(category: ShortcutCategory): KeyboardShortcut[] {
        return this.getShortcuts().filter(s => s.category === category);
    }

    /**
     * Handle keydown event
     */
    private handleKeyDown(event: KeyboardEvent): void {
        if (!isEnabled.value) return;

        // Don't trigger shortcuts when typing in inputs, textareas, or contenteditable
        const target = event.target as HTMLElement;
        if (
            target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable
        ) {
            // Exception: Allow Cmd/Ctrl+K even in inputs for command palette
            if (!(event.key === 'k' && (event.metaKey || event.ctrlKey))) {
                return;
            }
        }

        // Find matching shortcut
        for (const [id, shortcut] of shortcuts.value.entries()) {
            if (shortcut.enabled === false) continue;

            // Check if the key matches
            const keyMatches = event.key.toLowerCase() === shortcut.key.toLowerCase();
            const ctrlMatches = shortcut.ctrl === undefined || shortcut.ctrl === event.ctrlKey;
            const altMatches = shortcut.alt === undefined || shortcut.alt === event.altKey;
            const shiftMatches = shortcut.shift === undefined || shortcut.shift === event.shiftKey;
            const metaMatches = shortcut.meta === undefined || shortcut.meta === event.metaKey;

            if (keyMatches && ctrlMatches && altMatches && shiftMatches && metaMatches) {
                event.preventDefault();
                console.log(`[KeyboardShortcuts] Triggered: ${id}`);
                shortcut.handler();
                return;
            }
        }
    }

    /**
     * Format shortcut for display
     */
    formatShortcut(shortcut: KeyboardShortcut): string {
        const parts: string[] = [];

        if (shortcut.ctrl) parts.push(isMac ? '⌃' : 'Ctrl');
        if (shortcut.alt) parts.push(isMac ? '⌥' : 'Alt');
        if (shortcut.shift) parts.push(isMac ? '⇧' : 'Shift');
        if (shortcut.meta) parts.push(isMac ? '⌘' : 'Win');

        // Format the key nicely
        let key = shortcut.key;
        if (key === ' ') key = 'Space';
        if (key === 'Escape') key = 'Esc';
        if (key === 'ArrowUp') key = '↑';
        if (key === 'ArrowDown') key = '↓';
        if (key === 'ArrowLeft') key = '←';
        if (key === 'ArrowRight') key = '→';

        parts.push(key.toUpperCase());

        return parts.join(isMac ? '' : '+');
    }
}

// Export singleton instance
export const keyboardShortcutsService = new KeyboardShortcutsService();

// Export reactive state for components
export const useKeyboardShortcuts = () => ({
    shortcuts: computed(() => Array.from(shortcuts.value.values())),
    isEnabled: computed(() => isEnabled.value),
    getShortcutsByCategory: (category: ShortcutCategory) =>
        keyboardShortcutsService.getShortcutsByCategory(category),
    formatShortcut: (shortcut: KeyboardShortcut) =>
        keyboardShortcutsService.formatShortcut(shortcut)
});

// Export helper functions
export const keyboardShortcutHelpers = {
    register: (id: string, shortcut: KeyboardShortcut) =>
        keyboardShortcutsService.register(id, shortcut),
    unregister: (id: string) =>
        keyboardShortcutsService.unregister(id),
    enable: () => keyboardShortcutsService.enable(),
    disable: () => keyboardShortcutsService.disable(),
    initialize: () => keyboardShortcutsService.initialize(),
    cleanup: () => keyboardShortcutsService.cleanup()
};

// Default shortcuts configuration
export const DEFAULT_SHORTCUTS = {
    // Command Palette
    COMMAND_PALETTE: {
        key: 'k',
        meta: true,
        description: 'Open command palette',
        category: ShortcutCategory.GENERAL
    },

    // Timer shortcuts
    START_TIMER: {
        key: 't',
        meta: true,
        description: 'Start/stop timer',
        category: ShortcutCategory.TIMER
    },
    QUICK_TIMER: {
        key: 't',
        meta: true,
        shift: true,
        description: 'Quick start timer with last project',
        category: ShortcutCategory.TIMER
    },

    // Navigation shortcuts
    GO_TO_DASHBOARD: {
        key: 'd',
        meta: true,
        shift: true,
        description: 'Go to dashboard',
        category: ShortcutCategory.NAVIGATION
    },
    GO_TO_TIME_ENTRIES: {
        key: 'e',
        meta: true,
        shift: true,
        description: 'Go to time entries',
        category: ShortcutCategory.NAVIGATION
    },
    GO_TO_PROJECTS: {
        key: 'p',
        meta: true,
        shift: true,
        description: 'Go to projects',
        category: ShortcutCategory.NAVIGATION
    },
    GO_TO_REPORTS: {
        key: 'r',
        meta: true,
        shift: true,
        description: 'Go to reports',
        category: ShortcutCategory.NAVIGATION
    },

    // Action shortcuts
    NEW_PROJECT: {
        key: 'n',
        meta: true,
        shift: true,
        description: 'Create new project',
        category: ShortcutCategory.ACTIONS
    },
    SEARCH: {
        key: 'f',
        meta: true,
        description: 'Search',
        category: ShortcutCategory.ACTIONS
    },

    // General shortcuts
    HELP: {
        key: '?',
        shift: true,
        description: 'Show keyboard shortcuts',
        category: ShortcutCategory.GENERAL
    },
    CLOSE: {
        key: 'Escape',
        description: 'Close modal/dialog',
        category: ShortcutCategory.GENERAL
    }
} as const;
