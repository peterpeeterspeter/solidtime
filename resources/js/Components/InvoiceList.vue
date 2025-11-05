<template>
    <div class="invoice-list">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Invoices</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage and generate invoices for your clients
                </p>
            </div>
            <button
                @click="handleCreateInvoice"
                class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Invoice
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Revenue</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(totalRevenue) }}
                </div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Outstanding</div>
                <div class="text-2xl font-bold text-orange-600">
                    {{ formatCurrency(outstandingAmount) }}
                </div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Paid Invoices</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ paidInvoices.length }}
                </div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Overdue</div>
                <div class="text-2xl font-bold text-red-600">
                    {{ overdueInvoices.length }}
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
                    ? 'bg-cyan-600 text-white'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            >
                {{ status.label }}
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-cyan-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Invoice Table -->
        <div v-else-if="filteredInvoices.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Invoice
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Due Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Amount
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
                    <tr
                        v-for="invoice in filteredInvoices"
                        :key="invoice.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ invoice.invoiceNumber }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ invoice.to.name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ formatDate(invoice.issueDate) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ formatDate(invoice.dueDate) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ formatCurrency(invoice.total) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusClass(invoice.status)"
                            >
                                {{ invoice.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                @click="handleDownloadPDF(invoice.id)"
                                class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300 mr-3"
                                title="Download PDF"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                            </button>
                            <button
                                @click="handleViewInvoice(invoice.id)"
                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                title="View Invoice"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400">No invoices found</p>
            <button
                @click="handleCreateInvoice"
                class="mt-4 text-cyan-600 dark:text-cyan-400 hover:underline"
            >
                Create your first invoice
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useInvoices } from '@/composables/useInvoices';
import { InvoiceHelpers } from '@/types/invoice';
import type { InvoiceStatus } from '@/types/invoice';

const {
    invoices,
    loading,
    totalRevenue,
    outstandingAmount,
    paidInvoices,
    overdueInvoices,
    fetchInvoices,
    downloadPDF
} = useInvoices();

const selectedStatus = ref<InvoiceStatus | 'all'>('all');

const statuses: Array<{ value: 'all' | InvoiceStatus; label: string }> = [
    { value: 'all', label: 'All' },
    { value: 'draft' as InvoiceStatus, label: 'Draft' },
    { value: 'sent' as InvoiceStatus, label: 'Sent' },
    { value: 'paid' as InvoiceStatus, label: 'Paid' },
    { value: 'overdue' as InvoiceStatus, label: 'Overdue' }
];

const filteredInvoices = computed(() => {
    if (selectedStatus.value === 'all') {
        return invoices.value;
    }
    return invoices.value.filter(inv => inv.status === selectedStatus.value);
});

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-EU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date);
};

const formatCurrency = (amount: number): string => {
    return InvoiceHelpers.formatCurrency(amount, 'EUR');
};

const getStatusClass = (status: InvoiceStatus): string => {
    switch (status) {
        case 'paid':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'sent':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'overdue':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
};

const handleCreateInvoice = () => {
    // Navigate to create invoice page
    window.location.href = '/invoices/create';
};

const handleViewInvoice = (invoiceId: string) => {
    // Navigate to invoice detail page
    window.location.href = `/invoices/${invoiceId}`;
};

const handleDownloadPDF = async (invoiceId: string) => {
    await downloadPDF(invoiceId);
};

onMounted(() => {
    fetchInvoices();
});
</script>
