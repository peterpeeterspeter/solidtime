<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { User } from '@/types/models';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const page = usePage<{
    auth: {
        user: User;
    };
}>();

const user = computed(() => page?.props?.auth?.user);
</script>

<template>
    <Head title="Timeclocker - EU Time Tracking for Freelancers & Teams" />

    <div class="min-h-screen bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-950">
        <!-- Header -->
        <header class="border-b border-border-primary">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <nav class="flex items-center justify-between">
                    <div class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto text-accent-600 dark:text-accent-400" />
                    </div>

                    <div v-if="canLogin" class="flex items-center gap-4">
                        <Link
                            v-if="user"
                            :href="route('dashboard')"
                            class="px-4 py-2 text-sm font-medium text-text-primary hover:text-accent-600 dark:hover:text-accent-400 transition"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-sm font-medium text-text-primary hover:text-accent-600 dark:hover:text-accent-400 transition"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-6 py-2.5 text-sm font-semibold bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition shadow-sm"
                            >
                                Start Free
                            </Link>
                        </template>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent-50 dark:bg-accent-950/30 text-accent-700 dark:text-accent-300 text-sm font-medium mb-8">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    GDPR-Compliant • EU Data Residency • Open Source
                </div>

                <h1 class="text-5xl lg:text-6xl font-bold text-text-primary mb-6 leading-tight">
                    Time Tracking
                    <span class="text-accent-600 dark:text-accent-400">Built for Europe</span>
                </h1>

                <p class="text-xl text-text-secondary mb-10 leading-relaxed max-w-3xl mx-auto">
                    The privacy-first time tracking platform for EU freelancers and small teams.
                    One-click tracking, instant invoicing, and complete data ownership—all hosted in Europe.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-8 py-4 text-base font-semibold bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        Start Tracking for Free
                    </Link>
                    <a
                        href="#features"
                        class="px-8 py-4 text-base font-semibold text-text-primary bg-white dark:bg-gray-800 border border-border-tertiary rounded-lg hover:border-accent-500 transition"
                    >
                        See Features
                    </a>
                </div>

                <p class="mt-6 text-sm text-text-tertiary">
                    No credit card required • Free for solo users • 14-day team trial
                </p>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-t border-border-primary">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-text-primary mb-4">
                    Everything you need, nothing you don't
                </h2>
                <p class="text-lg text-text-secondary max-w-2xl mx-auto">
                    Built specifically for EU-based freelancers and agencies who demand speed, privacy, and compliance.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- One-Click Tracking -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 6v6l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">One-Click Tracking</h3>
                    <p class="text-text-secondary leading-relaxed">
                        Start tracking instantly with keyboard shortcuts. No project pre-selection required—just click and go.
                    </p>
                </div>

                <!-- Instant Invoicing -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">Instant Invoicing</h3>
                    <p class="text-text-secondary leading-relaxed">
                        Generate professional PDF invoices directly from tracked time. Brand them, send them, get paid faster.
                    </p>
                </div>

                <!-- GDPR Compliant -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">GDPR-First Privacy</h3>
                    <p class="text-text-secondary leading-relaxed">
                        All data stored in EU data centers. Export or delete your data anytime with one click. You own your data.
                    </p>
                </div>

                <!-- Team Collaboration -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">Team Management</h3>
                    <p class="text-text-secondary leading-relaxed">
                        Invite teammates, manage permissions, approve timesheets. Perfect for agencies and remote teams.
                    </p>
                </div>

                <!-- Powerful Reporting -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">Powerful Reports</h3>
                    <p class="text-text-secondary leading-relaxed">
                        Track billability, analyze projects, export to CSV/PDF. Real-time dashboards show what matters.
                    </p>
                </div>

                <!-- Easy Import -->
                <div class="p-6 bg-white dark:bg-gray-800/50 rounded-xl border border-border-secondary hover:border-accent-400 transition">
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">Easy Migration</h3>
                    <p class="text-text-secondary leading-relaxed">
                        Import from Toggl, Clockify, or Harvest in minutes. Bring your projects, clients, and time entries.
                    </p>
                </div>
            </div>
        </section>

        <!-- EU Focus Section -->
        <section class="bg-accent-50 dark:bg-accent-950/20 py-20 border-y border-border-primary">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <h2 class="text-3xl lg:text-4xl font-bold text-text-primary mb-6">
                        Built for the European Market
                    </h2>
                    <p class="text-lg text-text-secondary mb-12 leading-relaxed">
                        Timeclocker is designed from the ground up for EU-based professionals who care about privacy,
                        compliance, and owning their data. No compromises, no US-style data harvesting.
                    </p>

                    <div class="grid sm:grid-cols-3 gap-8 text-center">
                        <div>
                            <div class="text-4xl font-bold text-accent-600 dark:text-accent-400 mb-2">100%</div>
                            <div class="text-text-secondary">EU Data Centers</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-accent-600 dark:text-accent-400 mb-2">GDPR</div>
                            <div class="text-text-secondary">Full Compliance</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-accent-600 dark:text-accent-400 mb-2">Open</div>
                            <div class="text-text-secondary">Source (AGPL)</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="bg-gradient-to-r from-accent-600 to-accent-700 dark:from-accent-700 dark:to-accent-800 rounded-2xl p-12 text-center text-white shadow-xl">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">
                    Ready to take control of your time?
                </h2>
                <p class="text-xl text-accent-50 mb-8 max-w-2xl mx-auto">
                    Join European freelancers and teams who trust Timeclocker for fast, private time tracking.
                </p>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="inline-block px-8 py-4 text-base font-semibold bg-white text-accent-700 rounded-lg hover:bg-gray-100 transition shadow-lg"
                >
                    Start Free Today
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border-primary py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-text-tertiary">
                        © 2025 Timeclocker. Open source under AGPL-3.0.
                    </div>
                    <div class="flex items-center gap-6 text-sm text-text-tertiary">
                        <a href="/legal/privacy" class="hover:text-accent-600 dark:hover:text-accent-400 transition">Privacy Policy</a>
                        <a href="/legal/terms" class="hover:text-accent-600 dark:hover:text-accent-400 transition">Terms</a>
                        <a href="https://github.com/solidtime-io/solidtime" class="hover:text-accent-600 dark:hover:text-accent-400 transition">GitHub</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
