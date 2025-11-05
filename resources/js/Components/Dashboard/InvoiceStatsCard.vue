<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { LoadingSpinner } from '@/packages/ui/src';
import axios from 'axios';

const organizationId = computed(() => getCurrentOrganizationId());

// Fetch invoice statistics
const { data: statsData, isLoading } = useQuery({
    queryKey: ['invoiceStats', organizationId],
    queryFn: async () => {
        try {
            const response = await axios.get(`/api/v1/organizations/${organizationId.value}/invoices/stats`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch invoice stats:', error);
            return {
                total_revenue: 0,
                total_paid: 0,
                total_outstanding: 0,
                count_paid: 0,
                count_outstanding: 0,
                count_overdue: 0,
            };
        }
    },
    enabled: computed(() => !!organizationId.value),
    placeholderData: {
        total_revenue: 0,
        total_paid: 0,
        total_outstanding: 0,
        count_paid: 0,
        count_outstanding: 0,
        count_overdue: 0,
    },
});

const formatCurrency = (amount: number, currency: string = 'EUR') => {
    return new Intl.NumberFormat('en-EU', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const stats = computed(() => [
    {
        label: 'Total Revenue',
        value: formatCurrency(statsData.value?.total_revenue || 0),
        subtext: `${statsData.value?.count_paid || 0} paid`,
        color: 'text-green-600 dark:text-green-400',
    },
    {
        label: 'Outstanding',
        value: formatCurrency(statsData.value?.total_outstanding || 0),
        subtext: `${statsData.value?.count_outstanding || 0} pending`,
        color: 'text-blue-600 dark:text-blue-400',
    },
    {
        label: 'Overdue',
        value: statsData.value?.count_overdue || 0,
        subtext: 'invoices',
        color: 'text-red-600 dark:text-red-400',
    },
]);
</script>

<template>
    <DashboardCard title="Invoice Stats" :icon="ChartBarIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else class="p-4">
            <div class="space-y-4">
                <div
                    v-for="(stat, index) in stats"
                    :key="stat.label"
                    :class="{ 'border-b border-card-border pb-4': index < stats.length - 1 }">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs font-medium text-text-tertiary uppercase tracking-wide">
                            {{ stat.label }}
                        </span>
                        <div class="text-right">
                            <div :class="stat.color" class="text-lg font-bold">
                                {{ stat.value }}
                            </div>
                            <div class="text-xs text-text-tertiary">
                                {{ stat.subtext }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Insight -->
            <div class="mt-4 pt-4 border-t border-card-border">
                <div
                    v-if="(statsData?.count_overdue || 0) > 0"
                    class="text-xs text-center text-red-600 dark:text-red-400 font-medium">
                    ⚠️ {{ statsData.count_overdue }} invoice{{ statsData.count_overdue > 1 ? 's' : '' }} overdue
                </div>
                <div
                    v-else-if="(statsData?.count_outstanding || 0) > 0"
                    class="text-xs text-center text-text-tertiary">
                    💼 All invoices are on track
                </div>
                <div
                    v-else
                    class="text-xs text-center text-text-tertiary">
                    ✨ No pending invoices
                </div>
            </div>
        </div>
    </DashboardCard>
</template>
