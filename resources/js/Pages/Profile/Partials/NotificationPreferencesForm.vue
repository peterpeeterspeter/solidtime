<template>
    <ActionSection>
        <template #title>
            Notification Preferences
        </template>

        <template #description>
            <div class="space-y-2">
                <p>Configure how and when you receive notifications.</p>
                <div
                    v-if="!isSupported"
                    class="text-yellow-600 dark:text-yellow-400 text-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Notifications are not supported in your browser
                </div>
            </div>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Permission Status -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center"
                                :class="{
                                    'bg-green-100 dark:bg-green-900/30': hasPermission,
                                    'bg-yellow-100 dark:bg-yellow-900/30': !hasPermission && notificationPermission === 'default',
                                    'bg-red-100 dark:bg-red-900/30': notificationPermission === 'denied'
                                }"
                            >
                                <svg
                                    v-if="hasPermission"
                                    class="w-6 h-6 text-green-600 dark:text-green-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg
                                    v-else
                                    class="w-6 h-6"
                                    :class="{
                                        'text-yellow-600 dark:text-yellow-400': notificationPermission === 'default',
                                        'text-red-600 dark:text-red-400': notificationPermission === 'denied'
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ permissionStatusText }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ permissionStatusDescription }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <SecondaryButton
                                v-if="!hasPermission && notificationPermission === 'default'"
                                @click="handleRequestPermission"
                                :disabled="isRequestingPermission"
                            >
                                Enable Notifications
                            </SecondaryButton>
                            <SecondaryButton
                                v-else-if="hasPermission"
                                @click="handleTestNotification"
                            >
                                Test Notification
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Settings (only show if permission granted) -->
                <div v-if="hasPermission" class="space-y-6">
                    <!-- Master Toggle -->
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Enable Notifications</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Master switch for all notifications</p>
                        </div>
                        <ToggleSwitch v-model="localPreferences.enabled" @update:modelValue="handleSave" />
                    </div>

                    <!-- Timer Reminders -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Timer Reminders</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Get reminded when tracking for extended periods</p>
                            </div>
                            <ToggleSwitch
                                v-model="localPreferences.timerReminders"
                                :disabled="!localPreferences.enabled"
                                @update:modelValue="handleSave"
                            />
                        </div>
                        <div v-if="localPreferences.timerReminders" class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Remind me every
                            </label>
                            <select
                                v-model.number="localPreferences.timerReminderInterval"
                                @change="handleSave"
                                class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option :value="60">1 hour</option>
                                <option :value="120">2 hours</option>
                                <option :value="180">3 hours</option>
                                <option :value="240">4 hours</option>
                                <option :value="300">5 hours</option>
                            </select>
                        </div>
                    </div>

                    <!-- Break Reminders -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Break Reminders</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Periodic reminders to take breaks</p>
                            </div>
                            <ToggleSwitch
                                v-model="localPreferences.breakReminders"
                                :disabled="!localPreferences.enabled"
                                @update:modelValue="handleSave"
                            />
                        </div>
                        <div v-if="localPreferences.breakReminders" class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Remind me every
                            </label>
                            <select
                                v-model.number="localPreferences.breakReminderInterval"
                                @change="handleSave"
                                class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option :value="30">30 minutes</option>
                                <option :value="60">1 hour</option>
                                <option :value="90">1.5 hours</option>
                                <option :value="120">2 hours</option>
                            </select>
                        </div>
                    </div>

                    <!-- Daily Summary -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Daily Summary</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Daily time tracking overview</p>
                            </div>
                            <ToggleSwitch
                                v-model="localPreferences.dailySummary"
                                :disabled="!localPreferences.enabled"
                                @update:modelValue="handleSave"
                            />
                        </div>
                        <div v-if="localPreferences.dailySummary" class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Send at
                            </label>
                            <input
                                v-model="localPreferences.dailySummaryTime"
                                type="time"
                                @change="handleSave"
                                class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Weekly Summary -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Weekly Summary</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Weekly time tracking overview</p>
                            </div>
                            <ToggleSwitch
                                v-model="localPreferences.weeklySummary"
                                :disabled="!localPreferences.enabled"
                                @update:modelValue="handleSave"
                            />
                        </div>
                        <div v-if="localPreferences.weeklySummary" class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Day of week
                                </label>
                                <select
                                    v-model.number="localPreferences.weeklySummaryDay"
                                    @change="handleSave"
                                    class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option :value="0">Sunday</option>
                                    <option :value="1">Monday</option>
                                    <option :value="2">Tuesday</option>
                                    <option :value="3">Wednesday</option>
                                    <option :value="4">Thursday</option>
                                    <option :value="5">Friday</option>
                                    <option :value="6">Saturday</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Time
                                </label>
                                <input
                                    v-model="localPreferences.weeklySummaryTime"
                                    type="time"
                                    @change="handleSave"
                                    class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- End of Day Reminder -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">End of Day Reminder</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Reminder to stop timer and review entries</p>
                            </div>
                            <ToggleSwitch
                                v-model="localPreferences.endOfDayReminder"
                                :disabled="!localPreferences.enabled"
                                @update:modelValue="handleSave"
                            />
                        </div>
                        <div v-if="localPreferences.endOfDayReminder" class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Remind me at
                            </label>
                            <input
                                v-model="localPreferences.endOfDayTime"
                                type="time"
                                @change="handleSave"
                                class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Team Updates -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Team Updates</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Notifications about team activities</p>
                        </div>
                        <ToggleSwitch
                            v-model="localPreferences.teamUpdates"
                            :disabled="!localPreferences.enabled"
                            @update:modelValue="handleSave"
                        />
                    </div>

                    <!-- Save Status -->
                    <div v-if="saveStatus" class="text-sm text-center" :class="{
                        'text-green-600 dark:text-green-400': saveStatus === 'saved',
                        'text-gray-600 dark:text-gray-400': saveStatus === 'saving'
                    }">
                        {{ saveStatus === 'saved' ? '✓ Preferences saved' : 'Saving...' }}
                    </div>
                </div>

                <!-- Denied Permission Help -->
                <div v-if="notificationPermission === 'denied'" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <h4 class="font-medium text-yellow-900 dark:text-yellow-200 mb-2">Notifications Blocked</h4>
                    <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-3">
                        You've previously blocked notifications. To enable them:
                    </p>
                    <ol class="text-sm text-yellow-800 dark:text-yellow-300 space-y-1 ml-4 list-decimal">
                        <li>Click the lock or information icon in your browser's address bar</li>
                        <li>Find "Notifications" in the permissions list</li>
                        <li>Change the setting to "Allow"</li>
                        <li>Reload this page</li>
                    </ol>
                </div>
            </div>
        </template>
    </ActionSection>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import ActionSection from '@/Components/ActionSection.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';

const {
    notificationPermission,
    hasPermission,
    isSupported,
    preferences,
    requestPermission,
    updatePreferences,
    showTestNotification
} = useNotifications();

const localPreferences = ref({ ...preferences.value });
const isRequestingPermission = ref(false);
const saveStatus = ref<'saving' | 'saved' | null>(null);

// Watch for changes from other sources
watch(preferences, (newPrefs) => {
    localPreferences.value = { ...newPrefs };
}, { deep: true });

const permissionStatusText = computed(() => {
    if (hasPermission.value) return 'Notifications Enabled';
    if (notificationPermission.value === 'denied') return 'Notifications Blocked';
    return 'Notifications Disabled';
});

const permissionStatusDescription = computed(() => {
    if (hasPermission.value) return 'You\'ll receive notifications based on your preferences';
    if (notificationPermission.value === 'denied') return 'Notifications are blocked in your browser settings';
    return 'Enable notifications to receive reminders and summaries';
});

async function handleRequestPermission() {
    isRequestingPermission.value = true;
    try {
        await requestPermission();
    } finally {
        isRequestingPermission.value = false;
    }
}

async function handleTestNotification() {
    await showTestNotification();
}

function handleSave() {
    saveStatus.value = 'saving';
    updatePreferences(localPreferences.value);

    setTimeout(() => {
        saveStatus.value = 'saved';
        setTimeout(() => {
            saveStatus.value = null;
        }, 2000);
    }, 300);
}
</script>
