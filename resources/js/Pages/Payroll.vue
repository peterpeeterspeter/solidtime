<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const organizationId = (page.props.auth as any)?.user?.current_organization_id;

interface PayrollItem {
    id: string;
    description: string;
    amount: number;
    quantity: number;
}

interface Payroll {
    id: string;
    employee_name: string;
    pay_period_start: string;
    pay_period_end: string;
    gross_amount: number;
    net_amount: number;
    status: 'draft' | 'processed' | 'paid';
    items: PayrollItem[];
    created_at: string;
}

const payrolls = ref<Payroll[]>([]);
const loading = ref(false);
const selectedStatus = ref<string>('all');

const statuses = [
    { value: 'all', label: 'All' },
    { value: 'draft', label: 'Draft' },
    { value: 'processed', label: 'Processed' },
    { value: 'paid', label: 'Paid' },
];

const fetchPayrolls = async () => {
    if (!organizationId) return;
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/organizations/${organizationId}/payrolls`);
        payrolls.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch payrolls:', error);
    } finally {
        loading.value = false;
    }
};

const filteredPayrolls = computed(() => {
    if (selectedStatus.value === 'all') return payrolls.value;
    return payrolls.value.filter(p => p.status === selectedStatus.value);
});

const totalPaid = computed(() => {
    return payrolls.value
        .filter(p => p.status === 'paid')
        .reduce((sum, p) => sum + p.net_amount, 0);
});

const pendingAmount = computed(() => {
    return payrolls.value
        .filter(p => p.status !== 'paid')
        .reduce((sum, p) => sum + p.net_amount, 0);
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount / 100);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadgeClass = (status: string) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        processed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return classes[status as keyof typeof classes] || classes.draft;
};

const handleCreatePayroll = () => {
    // TODO: Open create payroll modal
    alert('Create payroll functionality coming soon!');
};

const handleViewDetails = (payrollId: string) => {
    // TODO: Open payroll details modal
    alert(`View payroll details: ${payrollId}`);
};

onMounted(() => {
    fetchPayrolls();
});
</script>

<template>
    <AppLayout title="Payroll">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text-primary">
                Payroll Management
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="organizationId">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Payroll</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Manage employee payroll and compensation
                            </p>
                        </div>
                        <button
                            @click="handleCreatePayroll"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Payroll
                        </button>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Paid</div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(totalPaid) }}
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Pending</div>
                            <div class="text-2xl font-bold text-orange-600">
                                {{ formatCurrency(pendingAmount) }}
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Payrolls</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ payrolls.length }}
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="flex items-center gap-3 mb-6">
                        <button
                            v-for="status in statuses"
                            :key="status.value"
                            @click="selectedStatus = status.value"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="selectedStatus === status.value
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            {{ status.label }}
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-12">
                        <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Payroll Table -->
                    <div v-else-if="filteredPayrolls.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Pay Period
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Gross Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Net Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="payroll in filteredPayrolls" :key="payroll.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ payroll.employee_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(payroll.pay_period_start) }} - {{ formatDate(payroll.pay_period_end) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ formatCurrency(payroll.gross_amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatCurrency(payroll.net_amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="getStatusBadgeClass(payroll.status)"
                                        >
                                            {{ payroll.status.charAt(0).toUpperCase() + payroll.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <button
                                            @click="handleViewDetails(payroll.id)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                        >
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No payrolls</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new payroll.</p>
                        <div class="mt-6">
                            <button
                                @click="handleCreatePayroll"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Create Payroll
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">
                        Please select an organization to view payroll.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
