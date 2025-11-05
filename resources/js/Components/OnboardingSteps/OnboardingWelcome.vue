<template>
    <div class="flex flex-col items-center text-center space-y-6">
        <!-- Hero Illustration -->
        <div class="w-32 h-32 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-3xl flex items-center justify-center shadow-xl">
            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <!-- Welcome Message -->
        <div class="max-w-xl">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Welcome to Timeclocker! 🎉
            </h3>
            <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                The privacy-first time tracking platform built specifically for European freelancers and small teams.
            </p>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full mt-8">
            <div class="p-6 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/40 rounded-lg flex items-center justify-center mb-4 mx-auto">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">GDPR Compliant</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    All your data stays in the EU. Full control over your information.
                </p>
            </div>

            <div class="p-6 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/40 rounded-lg flex items-center justify-center mb-4 mx-auto">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Lightning Fast</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    One-click timer start, keyboard shortcuts, and offline support.
                </p>
            </div>

            <div class="p-6 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/40 rounded-lg flex items-center justify-center mb-4 mx-auto">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Easy Invoicing</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Generate professional PDF invoices in seconds.
                </p>
            </div>
        </div>

        <!-- What You'll Set Up -->
        <div class="w-full mt-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 text-left">
                What we'll set up together:
            </h4>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/40 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Your workspace and organization settings</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/40 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Your first project (optional)</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-cyan-100 dark:bg-cyan-900/40 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Quick tips and keyboard shortcuts</span>
                </div>
            </div>
        </div>

        <!-- CTA Button -->
        <button
            @click="handleGetStarted"
            class="mt-8 px-8 py-4 bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
        >
            Let's Get Started →
        </button>

        <!-- Time Estimate -->
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            ⏱️ Takes about 2 minutes
        </p>
    </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{
    (e: 'next'): void;
}>();

const handleGetStarted = () => {
    emit('next');
};
</script>
