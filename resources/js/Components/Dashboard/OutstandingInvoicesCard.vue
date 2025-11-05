<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { DocumentTextIcon, ExclamationCircleIcon } from '@heroicons/vue/20/solid';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { LoadingSpinner } from '@/packages/ui/src';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const organizationId = computed(() => getCurrentOrganizationId());

// Fetch outstanding invoices (sent + overdue)
const { data: invoicesData, isLoading } = useQuery({
    queryKey: ['outstandingInvoices', organizationId],
    queryFn: async () => {
        try {
            const response = await axios.get(`/api/v1/organizations/${organizationId.value}/invoices`, {
                params: {
                    status: 'sent,overdue'
                }
            });
            return response.data;
        } catch (error) {
            console.error('Failed to fetch outstanding invoices:', error);
            return { invoices: [], total_amount: 0, overdue_count: 0 };
        }
    },
    enabled: computed(() => !!organizationId.value),
    placeholderData: { invoices: [], total_amount: 0, overdue_count: 0 },
});

const formatCurrency = (amount: number, currency: string = 'EUR') => {
    return new Intl.NumberFormat('en-EU', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const overdueCount = computed(() => invoicesData.value?.overdue_count || 0);
const outstandingAmount = computed(() => invoicesData.value?.total_amount || 0);
const invoicesList = computed(() => invoicesData.value?.invoices?.slice(0, 3) || []);
</script>

<template>
    <DashboardCard title="Outstanding Invoices" :icon="DocumentTextIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else class="p-4">
            <!-- Summary Stats -->
            <div class="mb-4 pb-4 border-b border-card-border">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-text-tertiary">Total Outstanding</span>
                    <span class="text-xl font-semibold text-text-primary">
                        {{ formatCurrency(outstandingAmount) }}
                    </span>
                </div>
                <div v-if="overdueCount > 0" class="flex items-center gap-1.5 text-red-600 dark:text-red-400">
                    <ExclamationCircleIcon class="w-4 h-4" />
                    <span class="text-sm font-medium">{{ overdueCount }} overdue invoice{{ overdueCount > 1 ? 's' : '' }}</span>
                </div>
            </div>

            <!-- Recent Outstanding Invoices -->
            <div v-if="invoicesList.length > 0" class="space-y-2">
                <div
                    v-for="invoice in invoicesList"
                    :key="invoice.id"
                    class="flex items-center justify-between py-2 hover:bg-card-background-hover rounded px-2 -mx-2 transition-colors">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-text-primary truncate">
                            {{ invoice.invoice_number }}
                        </div>
                        <div class="text-xs text-text-tertiary truncate">
                            {{ invoice.client_name || 'No client' }}
                        </div>
                    </div>
                    <div class="text-right ml-2">
                        <div class="text-sm font-semibold text-text-primary">
                            {{ formatCurrency(invoice.total, invoice.currency) }}
                        </div>
                        <div
                            :class="{
                                'text-red-600 dark:text-red-400': invoice.status === 'overdue',
                                'text-blue-600 dark:text-blue-400': invoice.status === 'sent'
                            }"
                            class="text-xs font-medium">
                            {{ invoice.status === 'overdue' ? 'Overdue' : 'Pending' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8">
                <DocumentTextIcon class="w-12 h-12 mx-auto text-text-tertiary mb-2 opacity-50" />
                <p class="text-sm text-text-tertiary">No outstanding invoices</p>
                <p class="text-xs text-text-tertiary mt-1">All invoices are paid 🎉</p>
            </div>

            <!-- View All Link -->
            <div class="mt-4 pt-3 border-t border-card-border">
                <Link
                    href="/invoices"
                    class="text-sm text-primary hover:text-primary-hover font-medium flex items-center justify-center transition-colors">
                    View all invoices →
                </Link>
            </div>
        </div>
    </DashboardCard>
</template>
