<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { usePrivacySettings, type TrackingLevel } from '@/composables/usePrivacySettings';
import ActionSection from '@/Components/ActionSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';

const {
    settings,
    loading,
    error,
    trackingLevels,
    currentTrackingLevel,
    canEnableFeature,
    fetchSettings,
    updateSettings,
    fetchDataCollectionStatus,
    dataCollectionStatus
} = usePrivacySettings();

const showConsentModal = ref(false);
const consentReason = ref('');
const pendingChanges = ref<any>(null);

onMounted(async () => {
    await fetchSettings();
    await fetchDataCollectionStatus();
});

/**
 * Select a tracking level
 */
const selectTrackingLevel = async (level: number) => {
    if (settings.value && settings.value.tracking_level === level) {
        return; // Already selected
    }

    // Show consent modal for confirmation
    pendingChanges.value = { tracking_level: level };
    showConsentModal.value = true;
};

/**
 * Toggle a privacy feature
 */
const toggleFeature = async (feature: string, value: boolean) => {
    if (!settings.value) return;

    // Check if feature can be enabled
    if (value && !canEnableFeature.value(feature)) {
        error.value = `This feature requires ${feature === 'screenshot_enabled' ? 'Monitoring (Level 2)' : 'Idle Detection (Level 1)'} or higher.`;
        return;
    }

    try {
        await updateSettings({ [feature]: value });
        error.value = null;
    } catch (err) {
        // Error handled in composable
    }
};

/**
 * Confirm and apply pending changes
 */
const confirmChanges = async () => {
    if (!pendingChanges.value) return;

    try {
        await updateSettings(pendingChanges.value, consentReason.value || undefined);
        showConsentModal.value = false;
        consentReason.value = '';
        pendingChanges.value = null;
        error.value = null;
    } catch (err) {
        // Error handled in composable
    }
};

/**
 * Cancel pending changes
 */
const cancelChanges = () => {
    showConsentModal.value = false;
    consentReason.value = '';
    pendingChanges.value = null;
};

/**
 * Update data retention
 */
const updateDataRetention = async (days: number) => {
    try {
        await updateSettings({ data_retention_days: days });
        error.value = null;
    } catch (err) {
        // Error handled in composable
    }
};

/**
 * Get icon SVG for tracking level
 */
const getIcon = (iconName: string) => {
    const icons: Record<string, string> = {
        hand: 'M7 11.5V14m0-2.5v-6a1.5 1.5 0 1 1 3 0m-3 6a1.5 1.5 0 0 0-3 0v2a7.5 7.5 0 0 0 15 0v-5a1.5 1.5 0 0 0-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 0 1 3 0v1m0 0V11m0-5.5a1.5 1.5 0 0 1 3 0v3m0 0V11',
        clock: 'M12 6v6l4 2m6-6a9 9 0 11-18 0 9 9 0 0118 0z',
        eye: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        camera: 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'
    };
    return icons[iconName] || icons.hand;
};

const statusClass = computed(() => {
    if (!settings.value) return 'bg-gray-100';
    const level = settings.value.tracking_level;
    return level === 0 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' :
           level === 1 ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' :
           level === 2 ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' :
           'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800';
});

const statusIconColor = computed(() => {
    if (!settings.value) return 'text-gray-600';
    const level = settings.value.tracking_level;
    return level === 0 ? 'text-green-600 dark:text-green-400' :
           level === 1 ? 'text-blue-600 dark:text-blue-400' :
           level === 2 ? 'text-yellow-600 dark:text-yellow-400' :
           'text-orange-600 dark:text-orange-400';
});
</script>

<template>
    <ActionSection>
        <template #title>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Privacy & Tracking Settings
            </div>
        </template>

        <template #description>
            <p class="text-sm text-text-secondary">
                Control what data is collected and how your activity is tracked. All features are opt-in and can be disabled at any time.
            </p>
        </template>

        <template #content>
            <div v-if="loading && !settings" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-cyan-600"></div>
                <p class="mt-3 text-sm text-text-secondary">Loading privacy settings...</p>
            </div>

            <div v-else-if="error && !settings" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                <SecondaryButton @click="fetchSettings" class="mt-4">Try Again</SecondaryButton>
            </div>

            <div v-else-if="settings" class="space-y-6">
                <!-- Current Status -->
                <div :class="['border rounded-lg p-6 transition-colors', statusClass]">
                    <div class="flex items-center gap-3">
                        <svg :class="['w-8 h-8', statusIconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path :d="getIcon(currentTrackingLevel.icon)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-semibold text-text-primary">{{ currentTrackingLevel.label }}</h3>
                                <span v-if="currentTrackingLevel.badge" class="px-2 py-0.5 text-xs font-medium bg-white/50 dark:bg-black/20 rounded">
                                    {{ currentTrackingLevel.badge }}
                                </span>
                            </div>
                            <p class="text-sm text-text-secondary mt-1">{{ currentTrackingLevel.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tracking Level Selector -->
                <div>
                    <h3 class="text-base font-semibold text-text-primary mb-4">Choose Your Tracking Level</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <button
                            v-for="level in trackingLevels"
                            :key="level.value"
                            @click="selectTrackingLevel(level.value)"
                            :class="[
                                'text-left border-2 rounded-lg p-4 transition-all hover:shadow-md',
                                settings.tracking_level === level.value
                                    ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20'
                                    : 'border-card-border bg-card-background hover:border-cyan-300'
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path :d="getIcon(level.icon)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-text-primary">{{ level.label }}</span>
                                        <span v-if="level.badge" class="px-2 py-0.5 text-xs font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded">
                                            {{ level.badge }}
                                        </span>
                                        <svg v-if="settings.tracking_level === level.value" class="w-5 h-5 text-cyan-600 dark:text-cyan-400 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-text-secondary mt-1">{{ level.description }}</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Feature Toggles -->
                <div>
                    <h3 class="text-base font-semibold text-text-primary mb-4">Advanced Controls</h3>
                    <div class="space-y-3">
                        <!-- Screenshot Toggle -->
                        <div class="flex items-center justify-between p-4 border border-card-border rounded-lg bg-card-background">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <label class="font-medium text-text-primary cursor-pointer">Screenshot Capture</label>
                                    <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded">
                                        Off by Default
                                    </span>
                                </div>
                                <p class="text-xs text-text-secondary mt-1">Take periodic screenshots (requires Monitoring level or higher)</p>
                            </div>
                            <button
                                @click="toggleFeature('screenshot_enabled', !settings.screenshot_enabled)"
                                :disabled="!canEnableFeature('screenshot_enabled') && !settings.screenshot_enabled"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                                    settings.screenshot_enabled ? 'bg-cyan-600' : 'bg-gray-200 dark:bg-gray-700',
                                    !canEnableFeature('screenshot_enabled') && !settings.screenshot_enabled ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            >
                                <span :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    settings.screenshot_enabled ? 'translate-x-5' : 'translate-x-0'
                                ]"></span>
                            </button>
                        </div>

                        <!-- App Tracking Toggle -->
                        <div class="flex items-center justify-between p-4 border border-card-border rounded-lg bg-card-background">
                            <div class="flex-1">
                                <label class="font-medium text-text-primary cursor-pointer">Application Tracking</label>
                                <p class="text-xs text-text-secondary mt-1">Track which apps you use (encrypted)</p>
                            </div>
                            <button
                                @click="toggleFeature('app_tracking_enabled', !settings.app_tracking_enabled)"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                                    settings.app_tracking_enabled ? 'bg-cyan-600' : 'bg-gray-200 dark:bg-gray-700'
                                ]"
                            >
                                <span :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    settings.app_tracking_enabled ? 'translate-x-5' : 'translate-x-0'
                                ]"></span>
                            </button>
                        </div>

                        <!-- URL Tracking Toggle -->
                        <div class="flex items-center justify-between p-4 border border-card-border rounded-lg bg-card-background">
                            <div class="flex-1">
                                <label class="font-medium text-text-primary cursor-pointer">URL Tracking</label>
                                <p class="text-xs text-text-secondary mt-1">Track websites you visit (encrypted, domain only)</p>
                            </div>
                            <button
                                @click="toggleFeature('url_tracking_enabled', !settings.url_tracking_enabled)"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                                    settings.url_tracking_enabled ? 'bg-cyan-600' : 'bg-gray-200 dark:bg-gray-700'
                                ]"
                            >
                                <span :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    settings.url_tracking_enabled ? 'translate-x-5' : 'translate-x-0'
                                ]"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Retention -->
                <div>
                    <h3 class="text-base font-semibold text-text-primary mb-4">Data Retention</h3>
                    <div class="p-4 border border-card-border rounded-lg bg-card-background">
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Automatically delete activity data after:
                        </label>
                        <select
                            :value="settings.data_retention_days"
                            @change="updateDataRetention(parseInt(($event.target as HTMLSelectElement).value))"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option :value="30">30 days</option>
                            <option :value="60">60 days</option>
                            <option :value="90">90 days (recommended)</option>
                            <option :value="180">180 days</option>
                            <option :value="365">1 year</option>
                        </select>
                        <p class="text-xs text-text-tertiary mt-2">Activity data will be automatically deleted after this period to protect your privacy.</p>
                    </div>
                </div>

                <!-- What's Being Collected -->
                <div v-if="dataCollectionStatus" class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-cyan-900 dark:text-cyan-100 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        What Data Is Currently Being Collected
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-2 text-sm">
                        <div v-for="(status, key) in dataCollectionStatus" :key="key" class="flex items-center gap-2">
                            <svg v-if="status" class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span :class="status ? 'text-cyan-800 dark:text-cyan-200' : 'text-gray-600 dark:text-gray-400'">
                                {{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Error Display -->
                <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
                </div>
            </div>

            <!-- Consent Modal -->
            <div v-if="showConsentModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="cancelChanges"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-cyan-100 dark:bg-cyan-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        Confirm Privacy Setting Change
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            You're about to change your tracking level. This change will be logged for GDPR compliance.
                                        </p>
                                        <div class="mt-4">
                                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Reason for change (optional)
                                            </label>
                                            <textarea
                                                id="reason"
                                                v-model="consentReason"
                                                rows="3"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm"
                                                placeholder="e.g., Need app tracking for productivity insights"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                            <PrimaryButton @click="confirmChanges" :disabled="loading">
                                <span v-if="loading">Saving...</span>
                                <span v-else>Confirm</span>
                            </PrimaryButton>
                            <SecondaryButton @click="cancelChanges" :disabled="loading">
                                Cancel
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </ActionSection>
</template>
