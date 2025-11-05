<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const exportingData = ref(false);
const viewingAuditLog = ref(false);

const exportPersonalData = () => {
    exportingData.value = true;

    // Trigger download of personal data export
    router.post('/user/data/export', {}, {
        onSuccess: () => {
            exportingData.value = false;
        },
        onError: () => {
            exportingData.value = false;
        }
    });
};

const downloadAuditLog = () => {
    viewingAuditLog.value = true;

    router.post('/user/audit-log/export', {}, {
        onSuccess: () => {
            viewingAuditLog.value = false;
        },
        onError: () => {
            viewingAuditLog.value = false;
        }
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Privacy Dashboard
            </div>
        </template>

        <template #description>
            <p class="text-sm text-text-secondary mb-4">
                Manage your personal data and exercise your GDPR rights. All your data is stored exclusively in EU data centers.
            </p>

            <!-- EU Data Residency Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg text-sm">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium text-cyan-700 dark:text-cyan-300">🇪🇺 EU Data Residency • GDPR Compliant</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Data Export Section -->
                <div class="bg-card-background border border-card-border rounded-lg p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <h3 class="text-base font-semibold text-text-primary">
                                    Export Your Data
                                </h3>
                                <span class="px-2 py-0.5 text-xs font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded">
                                    GDPR Article 20
                                </span>
                            </div>
                            <p class="text-sm text-text-secondary mb-4">
                                Download all your personal data in machine-readable formats (CSV, JSON). Includes time entries, projects, clients, invoices, and account information.
                            </p>
                            <ul class="text-xs text-text-tertiary space-y-1 mb-4">
                                <li>• Time entries and tracking data</li>
                                <li>• Projects, tasks, and clients</li>
                                <li>• Invoices and billable rates</li>
                                <li>• Account settings and preferences</li>
                                <li>• Audit log of account changes</li>
                            </ul>
                        </div>
                    </div>
                    <SecondaryButton
                        @click="exportPersonalData"
                        :disabled="exportingData"
                        class="w-full sm:w-auto"
                    >
                        <svg v-if="!exportingData" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span v-if="exportingData">Preparing Export...</span>
                        <span v-else>Download My Data</span>
                    </SecondaryButton>
                </div>

                <!-- Audit Log Section -->
                <div class="bg-card-background border border-card-border rounded-lg p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <h3 class="text-base font-semibold text-text-primary">
                                    Audit Log
                                </h3>
                                <span class="px-2 py-0.5 text-xs font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded">
                                    GDPR Article 15
                                </span>
                            </div>
                            <p class="text-sm text-text-secondary mb-4">
                                View and download a complete history of all changes to your account and data. Shows who made changes, when, and what was modified.
                            </p>
                            <ul class="text-xs text-text-tertiary space-y-1 mb-4">
                                <li>• Account settings changes</li>
                                <li>• Time entry modifications</li>
                                <li>• Project and client updates</li>
                                <li>• Login and authentication events</li>
                                <li>• Last 90 days of activity</li>
                            </ul>
                        </div>
                    </div>
                    <SecondaryButton
                        @click="downloadAuditLog"
                        :disabled="viewingAuditLog"
                        class="w-full sm:w-auto"
                    >
                        <svg v-if="!viewingAuditLog" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span v-if="viewingAuditLog">Generating...</span>
                        <span v-else">Download Audit Log</span>
                    </SecondaryButton>
                </div>

                <!-- Data Residency Info -->
                <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg p-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-cyan-900 dark:text-cyan-100 mb-2">
                                Your Data Storage Location
                            </h4>
                            <p class="text-sm text-cyan-800 dark:text-cyan-200 mb-3">
                                All your data is stored exclusively in European Union data centers:
                            </p>
                            <ul class="text-sm text-cyan-800 dark:text-cyan-200 space-y-1.5">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span><strong>Primary:</strong> Germany (Frankfurt)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span><strong>Backup:</strong> Netherlands (Amsterdam)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span><strong>No data transfers</strong> outside EU/EEA</span>
                                </li>
                            </ul>
                            <p class="text-xs text-cyan-700 dark:text-cyan-300 mt-3">
                                Learn more: <a href="/legal/privacy" class="underline hover:text-cyan-900 dark:hover:text-cyan-100">Privacy Policy</a> | <a href="https://github.com/solidtime-io/solidtime/blob/main/docs/EU_DATA_RESIDENCY.md" class="underline hover:text-cyan-900 dark:hover:text-cyan-100" target="_blank">EU Data Residency</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- GDPR Rights Summary -->
                <div class="bg-card-background border border-card-border rounded-lg p-6">
                    <h3 class="text-base font-semibold text-text-primary mb-4">Your GDPR Rights</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Access</p>
                                    <p class="text-xs text-text-tertiary">View all personal data we hold</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Rectification</p>
                                    <p class="text-xs text-text-tertiary">Correct inaccurate data</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Erasure</p>
                                    <p class="text-xs text-text-tertiary">Delete your account and data</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Portability</p>
                                    <p class="text-xs text-text-tertiary">Export data in standard formats</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Restrict</p>
                                    <p class="text-xs text-text-tertiary">Limit data processing</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">Right to Object</p>
                                    <p class="text-xs text-text-tertiary">Object to certain processing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-text-tertiary mt-4">
                        Questions? Contact our Data Protection Officer: <a href="mailto:privacy@timeclocker.app" class="text-cyan-600 dark:text-cyan-400 hover:underline">privacy@timeclocker.app</a>
                    </p>
                </div>
            </div>
        </template>
    </ActionSection>
</template>
