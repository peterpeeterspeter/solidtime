<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-cyan-100 dark:bg-cyan-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Set Up Your Workspace
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Let's configure your organization and preferences
            </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6 max-w-2xl mx-auto">
            <!-- Organization Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Organization Name <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.organizationName"
                    type="text"
                    placeholder="e.g., My Freelance Business"
                    required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    This will appear on your invoices and reports
                </p>
            </div>

            <!-- Timezone -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Timezone <span class="text-red-500">*</span>
                </label>
                <select
                    v-model="form.timezone"
                    required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select your timezone</option>
                    <optgroup label="Western Europe">
                        <option value="Europe/London">London (GMT)</option>
                        <option value="Europe/Lisbon">Lisbon (WET)</option>
                    </optgroup>
                    <optgroup label="Central Europe">
                        <option value="Europe/Berlin">Berlin (CET)</option>
                        <option value="Europe/Paris">Paris (CET)</option>
                        <option value="Europe/Amsterdam">Amsterdam (CET)</option>
                        <option value="Europe/Brussels">Brussels (CET)</option>
                        <option value="Europe/Vienna">Vienna (CET)</option>
                        <option value="Europe/Rome">Rome (CET)</option>
                        <option value="Europe/Madrid">Madrid (CET)</option>
                        <option value="Europe/Stockholm">Stockholm (CET)</option>
                    </optgroup>
                    <optgroup label="Eastern Europe">
                        <option value="Europe/Athens">Athens (EET)</option>
                        <option value="Europe/Helsinki">Helsinki (EET)</option>
                        <option value="Europe/Bucharest">Bucharest (EET)</option>
                        <option value="Europe/Warsaw">Warsaw (CET)</option>
                        <option value="Europe/Prague">Prague (CET)</option>
                    </optgroup>
                </select>
            </div>

            <!-- Currency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Currency <span class="text-red-500">*</span>
                </label>
                <select
                    v-model="form.currency"
                    required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select your currency</option>
                    <option value="EUR">Euro (€)</option>
                    <option value="GBP">British Pound (£)</option>
                    <option value="CHF">Swiss Franc (CHF)</option>
                    <option value="SEK">Swedish Krona (kr)</option>
                    <option value="NOK">Norwegian Krone (kr)</option>
                    <option value="DKK">Danish Krone (kr)</option>
                    <option value="PLN">Polish Złoty (zł)</option>
                    <option value="CZK">Czech Koruna (Kč)</option>
                </select>
            </div>

            <!-- Language -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Preferred Language
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button
                        v-for="lang in languages"
                        :key="lang.code"
                        type="button"
                        @click="form.language = lang.code"
                        class="flex items-center gap-2 px-4 py-3 border-2 rounded-lg transition-all hover:border-cyan-400"
                        :class="form.language === lang.code
                            ? 'border-cyan-600 bg-cyan-50 dark:bg-cyan-900/20'
                            : 'border-gray-300 dark:border-gray-600'"
                    >
                        <span class="text-2xl">{{ lang.flag }}</span>
                        <span class="text-sm font-medium">{{ lang.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Week Start Day -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Week Starts On
                </label>
                <select
                    v-model="form.weekStartDay"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                >
                    <option value="monday">Monday</option>
                    <option value="sunday">Sunday</option>
                </select>
            </div>

            <!-- EU Data Info -->
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-1">
                            🇪🇺 Your Data Stays in Europe
                        </h4>
                        <p class="text-xs text-green-800 dark:text-green-200">
                            All your data is stored on EU servers (Frankfurt & Amsterdam) and never leaves the European Economic Area. Full GDPR compliance guaranteed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    {{ error }}
                </p>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-6">
                <button
                    type="button"
                    @click="handlePrevious"
                    class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    ← Back
                </button>
                <button
                    type="submit"
                    :disabled="!isFormValid || loading"
                    class="px-8 py-3 bg-cyan-600 hover:bg-cyan-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors flex items-center gap-2"
                >
                    <span v-if="loading">Saving...</span>
                    <span v-else>Continue →</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits<{
    (e: 'next'): void;
    (e: 'previous'): void;
}>();

const form = ref({
    organizationName: '',
    timezone: '',
    currency: 'EUR',
    language: 'en',
    weekStartDay: 'monday'
});

const languages = [
    { code: 'en', name: 'English', flag: '🇬🇧' },
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'nl', name: 'Nederlands', flag: '🇳🇱' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' }
];

const loading = ref(false);
const error = ref('');

const isFormValid = computed(() => {
    return form.value.organizationName.trim() !== '' &&
           form.value.timezone !== '' &&
           form.value.currency !== '';
});

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    loading.value = true;
    error.value = '';

    try {
        // Update organization settings
        await axios.patch('/api/v1/organizations/current', {
            name: form.value.organizationName,
            timezone: form.value.timezone,
            currency: form.value.currency
        });

        // Update user preferences
        await axios.patch('/api/v1/users/me/preferences', {
            language: form.value.language,
            week_start_day: form.value.weekStartDay
        });

        emit('next');
    } catch (err: any) {
        console.error('Failed to save workspace settings:', err);
        error.value = err.response?.data?.message || 'Failed to save settings. Please try again.';
    } finally {
        loading.value = false;
    }
};

const handlePrevious = () => {
    emit('previous');
};

// Auto-detect timezone
const detectTimezone = () => {
    try {
        const detected = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (detected && detected.startsWith('Europe/')) {
            form.value.timezone = detected;
        }
    } catch (error) {
        console.error('Failed to detect timezone:', error);
    }
};

// Auto-detect on mount
detectTimezone();
</script>
