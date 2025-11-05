<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const page = usePage();
const saving = ref(false);
const selectedLocale = ref('en'); // Default, will be updated from user preferences

const availableLocales = {
    'en': { name: 'English', flag: '🇬🇧' },
    'de': { name: 'Deutsch', flag: '🇩🇪' },
    'nl': { name: 'Nederlands', flag: '🇳🇱' },
    'fr': { name: 'Français', flag: '🇫🇷' },
};

const updateLanguage = (locale: string) => {
    saving.value = true;
    selectedLocale.value = locale;

    router.post('/user/language', {
        locale: locale,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        }
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Language / Sprache / Taal / Langue
            </div>
        </template>

        <template #description>
            <p class="text-sm text-text-secondary mb-4">
                Choose your preferred language for the Timeclocker interface.
            </p>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg text-sm">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium text-cyan-700 dark:text-cyan-300">🇪🇺 Multi-language support for EU users</span>
            </div>
        </template>

        <template #content>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                    v-for="(locale, code) in availableLocales"
                    :key="code"
                    @click="updateLanguage(code)"
                    :disabled="saving"
                    :class="[
                        'relative p-4 border-2 rounded-lg transition-all duration-200',
                        'flex items-center justify-between',
                        'hover:border-cyan-400 dark:hover:border-cyan-600',
                        'focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                        selectedLocale === code
                            ? 'border-cyan-600 dark:border-cyan-400 bg-cyan-50 dark:bg-cyan-900/20'
                            : 'border-border-secondary bg-card-background'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ locale.flag }}</span>
                        <div class="text-left">
                            <div class="font-semibold text-text-primary">{{ locale.name }}</div>
                            <div class="text-xs text-text-tertiary">{{ code.toUpperCase() }}</div>
                        </div>
                    </div>

                    <svg
                        v-if="selectedLocale === code"
                        class="w-6 h-6 text-cyan-600 dark:text-cyan-400 flex-shrink-0"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <div class="mt-6 bg-card-background border border-card-border rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-text-secondary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-text-primary mb-1">
                            Interface translations
                        </h4>
                        <p class="text-sm text-text-secondary">
                            Changing your language will update all interface elements, buttons, and messages.
                            Your data (time entries, project names, etc.) will remain in the language you entered them.
                        </p>
                        <p class="text-xs text-text-tertiary mt-2">
                            More languages coming soon. Want to help translate? <a href="https://github.com/solidtime-io/solidtime" class="text-cyan-600 dark:text-cyan-400 hover:underline" target="_blank">Contribute on GitHub</a>
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="saving" class="mt-4 flex items-center gap-2 text-sm text-cyan-600 dark:text-cyan-400">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Updating language...</span>
            </div>
        </template>
    </ActionSection>
</template>
