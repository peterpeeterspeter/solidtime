<template>
    <div class="invoice-list">
        <div class="header">
            <div>
                <h2>Invoices</h2>
                <p>Manage and track all your client invoices</p>
            </div>
            <button @click="$emit('create')" class="btn btn-primary">
                Create Invoice
            </button>
        </div>

        <!-- Statistics -->
        <div class="statistics-grid">
            <div class="stat-card">
                <div class="stat-label">Draft</div>
                <div class="stat-value">{{ statistics.draft }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sent</div>
                <div class="stat-value">{{ statistics.sent }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Paid</div>
                <div class="stat-value">{{ statistics.paid }}</div>
                <div class="stat-amount">${{ formatCurrency(statistics.paidAmount) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Overdue</div>
                <div class="stat-value alert">{{ statistics.overdue }}</div>
                <div class="stat-amount">${{ formatCurrency(statistics.outstandingAmount) }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" @change="loadInvoices" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Search</label>
                <input
                    v-model="filters.search"
                    @input="debouncedSearch"
                    type="text"
                    placeholder="Search by invoice number or client..."
                    class="filter-input"
                />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !invoices.length" class="loading">
            Loading invoices...
        </div>

        <!-- Error State -->
        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <!-- Invoice Table -->
        <div v-if="!loading || invoices.length" class="invoice-table-container">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="invoice in invoices" :key="invoice.id" class="invoice-row">
                        <td class="invoice-number">
                            {{ invoice.invoice_number }}
                        </td>
                        <td>
                            <div class="client-info">
                                <div class="client-name">{{ invoice.to_details.name }}</div>
                                <div class="client-email">{{ invoice.to_details.email }}</div>
                            </div>
                        </td>
                        <td>{{ formatDate(invoice.issue_date) }}</td>
                        <td>
                            <span :class="{ 'overdue-date': isOverdue(invoice) }">
                                {{ formatDate(invoice.due_date) }}
                            </span>
                        </td>
                        <td class="amount">${{ formatCurrency(invoice.total) }}</td>
                        <td>
                            <span :class="['status-badge', `status-${invoice.status}`]">
                                {{ invoice.status }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button
                                    @click="$emit('view', invoice)"
                                    class="btn-icon"
                                    title="View"
                                >
                                    👁️
                                </button>
                                <button
                                    v-if="invoice.status === 'draft'"
                                    @click="$emit('edit', invoice)"
                                    class="btn-icon"
                                    title="Edit"
                                >
                                    ✏️
                                </button>
                                <button
                                    v-if="invoice.status === 'draft'"
                                    @click="handleMarkAsSent(invoice.id)"
                                    class="btn-icon"
                                    title="Mark as Sent"
                                >
                                    📧
                                </button>
                                <button
                                    v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                                    @click="handleMarkAsPaid(invoice.id)"
                                    class="btn-icon"
                                    title="Mark as Paid"
                                >
                                    ✅
                                </button>
                                <button
                                    @click="handleDelete(invoice.id)"
                                    class="btn-icon btn-danger"
                                    title="Delete"
                                >
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="!invoices.length && !loading" class="empty-state">
                <div class="empty-icon">📄</div>
                <h3>No invoices found</h3>
                <p>Create your first invoice to get started</p>
                <button @click="$emit('create')" class="btn btn-primary">
                    Create Invoice
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination">
            <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="btn btn-secondary"
            >
                Previous
            </button>
            <span class="page-info">
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="btn btn-secondary"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useInvoices } from '@/composables/payment/useInvoices';
import type { Invoice } from '@/types/payment';

const props = defineProps<{
    organizationId: string;
}>();

const emit = defineEmits<{
    (e: 'create'): void;
    (e: 'edit', invoice: Invoice): void;
    (e: 'view', invoice: Invoice): void;
}>();

const {
    invoices,
    loading,
    error,
    pagination,
    statistics,
    fetchInvoices,
    markAsSent,
    markAsPaid,
    deleteInvoice
} = useInvoices(props.organizationId);

const filters = ref({
    status: 'all' as 'all' | 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled',
    search: ''
});

let searchTimeout: ReturnType<typeof setTimeout>;

onMounted(() => {
    loadInvoices();
});

const loadInvoices = async () => {
    await fetchInvoices({
        page: 1,
        status: filters.value.status,
        search: filters.value.search || undefined
    });
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadInvoices();
    }, 500);
};

const changePage = async (page: number) => {
    await fetchInvoices({
        page,
        status: filters.value.status,
        search: filters.value.search || undefined
    });
};

const handleMarkAsSent = async (invoiceId: string) => {
    if (confirm('Mark this invoice as sent?')) {
        await markAsSent(invoiceId);
    }
};

const handleMarkAsPaid = async (invoiceId: string) => {
    if (confirm('Mark this invoice as paid?')) {
        await markAsPaid(invoiceId);
    }
};

const handleDelete = async (invoiceId: string) => {
    if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        await deleteInvoice(invoiceId);
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const isOverdue = (invoice: Invoice): boolean => {
    return (
        (invoice.status === 'sent' || invoice.status === 'overdue') &&
        new Date(invoice.due_date) < new Date()
    );
};
</script>

<style scoped>
.invoice-list {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.header h2 {
    font-size: 1.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.header p {
    color: #6b7280;
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 600;
    color: #111827;
}

.stat-value.alert {
    color: #dc2626;
}

.stat-amount {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.filter-select,
.filter-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.loading,
.error-message {
    text-align: center;
    padding: 2rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
}

.error-message {
    background-color: #fee2e2;
    border-color: #fecaca;
    color: #991b1b;
}

.invoice-table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
}

.invoice-table thead {
    background-color: #f9fafb;
}

.invoice-table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.05em;
}

.invoice-table td {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.invoice-number {
    font-weight: 600;
    color: #3b82f6;
}

.client-info {
    display: flex;
    flex-direction: column;
}

.client-name {
    font-weight: 500;
}

.client-email {
    font-size: 0.875rem;
    color: #6b7280;
}

.amount {
    font-weight: 600;
    color: #111827;
}

.overdue-date {
    color: #dc2626;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-draft {
    background-color: #f3f4f6;
    color: #6b7280;
}

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-paid {
    background-color: #d1fae5;
    color: #065f46;
}

.status-overdue {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-cancelled {
    background-color: #f3f4f6;
    color: #374151;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.25rem 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.125rem;
    transition: transform 0.1s;
}

.btn-icon:hover {
    transform: scale(1.1);
}

.btn-danger:hover {
    filter: brightness(0.8);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
}

.page-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.btn {
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #2563eb;
}

.btn-secondary {
    background-color: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
    background-color: #f9fafb;
}
</style>
