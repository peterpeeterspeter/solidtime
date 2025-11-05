import { onMounted, onUnmounted } from 'vue';
import {
    keyboardShortcutHelpers,
    type KeyboardShortcut
} from '@/utils/keyboardShortcuts';

/**
 * Composable for registering keyboard shortcuts in Vue components
 */
export function useKeyboardShortcut(
    id: string,
    shortcut: KeyboardShortcut
) {
    onMounted(() => {
        keyboardShortcutHelpers.register(id, shortcut);
    });

    onUnmounted(() => {
        keyboardShortcutHelpers.unregister(id);
    });

    return {
        register: () => keyboardShortcutHelpers.register(id, shortcut),
        unregister: () => keyboardShortcutHelpers.unregister(id)
    };
}

/**
 * Composable for registering multiple keyboard shortcuts
 */
export function useKeyboardShortcuts(
    shortcuts: Record<string, KeyboardShortcut>
) {
    onMounted(() => {
        Object.entries(shortcuts).forEach(([id, shortcut]) => {
            keyboardShortcutHelpers.register(id, shortcut);
        });
    });

    onUnmounted(() => {
        Object.keys(shortcuts).forEach(id => {
            keyboardShortcutHelpers.unregister(id);
        });
    });

    return {
        register: (id: string, shortcut: KeyboardShortcut) =>
            keyboardShortcutHelpers.register(id, shortcut),
        unregister: (id: string) =>
            keyboardShortcutHelpers.unregister(id)
    };
}
