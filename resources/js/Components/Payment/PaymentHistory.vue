<template>
    <div class="payment-history">
        <div class="header">
            <div>
                <h2>Payment History</h2>
                <p>View all payment transactions and refunds</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" @change="loadPayments" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                    <option value="partially_refunded">Partially Refunded</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Gateway</label>
                <select v-model="filters.gateway" @change="loadPayments" class="filter-select">
                    <option value="all">All Gateways</option>
                    <option value="stripe">Stripe</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !payments.length" class="loading">
            Loading payments...
        </div>

        <!-- Error State -->
        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <!-- Payment Table -->
        <div v-if="!loading || payments.length" class="payment-table-container">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Gateway</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Net</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="payment-row">
                        <td>{{ formatDate(payment.created_at) }}</td>
                        <td>
                            <span class="invoice-link">
                                {{ payment.invoice?.invoice_number || 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="gateway-badge">
                                {{ formatGateway(payment.gateway) }}
                            </span>
                        </td>
                        <td class="transaction-id">
                            <span v-if="payment.gateway_transaction_id" :title="payment.gateway_transaction_id">
                                {{ truncateId(payment.gateway_transaction_id) }}
                            </span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td class="amount">
                            ${{ formatCurrency(payment.amount) }}
                            <span class="currency">{{ payment.currency }}</span>
                        </td>
                        <td class="fee">
                            <span v-if="payment.fee_amount > 0">
                                ${{ formatCurrency(payment.fee_amount) }}
                            </span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td class="amount">
                            ${{ formatCurrency(payment.net_amount) }}
                        </td>
                        <td>
                            <span :class="['status-badge', `status-${payment.status}`]">
                                {{ payment.status }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button
                                    @click="viewDetails(payment)"
                                    class="btn-icon"
                                    title="View Details"
                                >
                                    👁️
                                </button>
                                <button
                                    v-if="canRefund(payment)"
                                    @click="showRefundModal(payment)"
                                    class="btn-icon"
                                    title="Refund"
                                >
                                    ↩️
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="!payments.length && !loading" class="empty-state">
                <div class="empty-icon">💳</div>
                <h3>No payments found</h3>
                <p>Payment transactions will appear here</p>
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

        <!-- Refund Modal -->
        <div v-if="refundModal.show" class="modal-overlay" @click="closeRefundModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Refund Payment</h3>
                    <button @click="closeRefundModal" class="close-button">&times;</button>
                </div>

                <div class="modal-body">
                    <p>
                        Refunding payment of
                        <strong>${{ formatCurrency(refundModal.payment?.amount || 0) }}</strong>
                    </p>
                    <p class="text-muted">
                        Already refunded: ${{ formatCurrency(refundModal.payment?.refund_amount || 0) }}
                    </p>
                    <p>
                        <strong>Refundable: ${{ formatCurrency(getRefundableAmount(refundModal.payment)) }}</strong>
                    </p>

                    <div class="form-group">
                        <label>Refund Amount</label>
                        <input
                            v-model.number="refundModal.amount"
                            type="number"
                            min="0"
                            :max="getRefundableAmount(refundModal.payment)"
                            step="0.01"
                            class="form-control"
                        />
                        <small class="help-text">Leave empty for full refund</small>
                    </div>

                    <div class="form-group">
                        <label>Reason (Optional)</label>
                        <textarea
                            v-model="refundModal.reason"
                            rows="3"
                            class="form-control"
                            placeholder="Reason for refund..."
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        @click="closeRefundModal"
                        class="btn btn-secondary"
                        :disabled="refundModal.loading"
                    >
                        Cancel
                    </button>
                    <button
                        @click="processRefund"
                        class="btn btn-primary"
                        :disabled="refundModal.loading"
                    >
                        {{ refundModal.loading ? 'Processing...' : 'Process Refund' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="detailsModal.show" class="modal-overlay" @click="closeDetailsModal">
            <div class="modal-content modal-large" @click.stop>
                <div class="modal-header">
                    <h3>Payment Details</h3>
                    <button @click="closeDetailsModal" class="close-button">&times;</button>
                </div>

                <div class="modal-body" v-if="detailsModal.payment">
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="label">Payment ID:</span>
                            <span class="value">{{ detailsModal.payment.id }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Status:</span>
                            <span :class="['status-badge', `status-${detailsModal.payment.status}`]">
                                {{ detailsModal.payment.status }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Gateway:</span>
                            <span class="value">{{ formatGateway(detailsModal.payment.gateway) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Transaction ID:</span>
                            <span class="value">{{ detailsModal.payment.gateway_transaction_id || 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Amount:</span>
                            <span class="value">${{ formatCurrency(detailsModal.payment.amount) }} {{ detailsModal.payment.currency }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Fee:</span>
                            <span class="value">${{ formatCurrency(detailsModal.payment.fee_amount) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Net Amount:</span>
                            <span class="value">${{ formatCurrency(detailsModal.payment.net_amount) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Refunded:</span>
                            <span class="value">${{ formatCurrency(detailsModal.payment.refund_amount) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Created:</span>
                            <span class="value">{{ formatDateTime(detailsModal.payment.created_at) }}</span>
                        </div>
                        <div class="detail-item" v-if="detailsModal.payment.paid_at">
                            <span class="label">Paid At:</span>
                            <span class="value">{{ formatDateTime(detailsModal.payment.paid_at) }}</span>
                        </div>
                    </div>

                    <div v-if="detailsModal.payment.status_message" class="status-message">
                        <strong>Message:</strong> {{ detailsModal.payment.status_message }}
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closeDetailsModal" class="btn btn-secondary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import type { Payment } from '@/types/payment';

const props = defineProps<{
    organizationId: string;
}>();

const payments = ref<Payment[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
});

const filters = ref({
    status: 'all',
    gateway: 'all'
});

const refundModal = reactive({
    show: false,
    payment: null as Payment | null,
    amount: 0,
    reason: '',
    loading: false
});

const detailsModal = reactive({
    show: false,
    payment: null as Payment | null
});

onMounted(() => {
    loadPayments();
});

const loadPayments = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params: any = {
            page: pagination.value.current_page,
            per_page: pagination.value.per_page
        };

        if (filters.value.status !== 'all') {
            params.status = filters.value.status;
        }

        if (filters.value.gateway !== 'all') {
            params.gateway = filters.value.gateway;
        }

        const response = await axios.get(
            `/api/v1/organizations/${props.organizationId}/payments`,
            { params }
        );

        payments.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
        };
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to load payments';
    } finally {
        loading.value = false;
    }
};

const changePage = async (page: number) => {
    pagination.value.current_page = page;
    await loadPayments();
};

const canRefund = (payment: Payment): boolean => {
    return (
        (payment.status === 'completed' || payment.status === 'partially_refunded') &&
        payment.refund_amount < payment.amount
    );
};

const getRefundableAmount = (payment: Payment | null): number => {
    if (!payment) return 0;
    return payment.amount - payment.refund_amount;
};

const showRefundModal = (payment: Payment) => {
    refundModal.show = true;
    refundModal.payment = payment;
    refundModal.amount = 0;
    refundModal.reason = '';
};

const closeRefundModal = () => {
    refundModal.show = false;
    refundModal.payment = null;
    refundModal.amount = 0;
    refundModal.reason = '';
};

const processRefund = async () => {
    if (!refundModal.payment) return;

    refundModal.loading = true;

    try {
        const refundData: any = {};

        if (refundModal.amount > 0) {
            refundData.amount = refundModal.amount;
        }

        if (refundModal.reason) {
            refundData.reason = refundModal.reason;
        }

        await axios.post(
            `/api/v1/organizations/${props.organizationId}/payments/${refundModal.payment.id}/refund`,
            refundData
        );

        closeRefundModal();
        await loadPayments();
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to process refund';
    } finally {
        refundModal.loading = false;
    }
};

const viewDetails = (payment: Payment) => {
    detailsModal.show = true;
    detailsModal.payment = payment;
};

const closeDetailsModal = () => {
    detailsModal.show = false;
    detailsModal.payment = null;
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (dateString: string): string => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const formatGateway = (gateway: string): string => {
    return gateway.charAt(0).toUpperCase() + gateway.slice(1).replace('_', ' ');
};

const truncateId = (id: string): string => {
    return id.length > 20 ? id.substring(0, 20) + '...' : id;
};
</script>

<style scoped>
.payment-history {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.header {
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

.filter-select {
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

.payment-table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table thead {
    background-color: #f9fafb;
}

.payment-table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.05em;
}

.payment-table td {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.invoice-link {
    color: #3b82f6;
    cursor: pointer;
}

.gateway-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #f3f4f6;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.transaction-id {
    font-family: monospace;
    font-size: 0.875rem;
}

.amount {
    font-weight: 600;
    color: #111827;
}

.currency {
    font-size: 0.75rem;
    color: #6b7280;
    margin-left: 0.25rem;
}

.fee {
    color: #6b7280;
}

.text-muted {
    color: #9ca3af;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-pending { background-color: #fef3c7; color: #92400e; }
.status-processing { background-color: #dbeafe; color: #1e40af; }
.status-completed { background-color: #d1fae5; color: #065f46; }
.status-failed { background-color: #fee2e2; color: #991b1b; }
.status-refunded { background-color: #f3f4f6; color: #374151; }
.status-partially_refunded { background-color: #fef3c7; color: #92400e; }

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
}

.btn-icon:hover {
    transform: scale(1.1);
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

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 0.75rem;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-large {
    max-width: 700px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
}

.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.close-button:hover {
    color: #111827;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item .label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.detail-item .value {
    font-size: 0.875rem;
    color: #111827;
}

.status-message {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}
</style>
