<template>
    <div class="invoice-form">
        <div class="form-header">
            <h2>{{ invoice ? 'Edit Invoice' : 'Create Invoice' }}</h2>
            <button @click="$emit('cancel')" class="btn btn-secondary">
                Cancel
            </button>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <form @submit.prevent="handleSubmit" class="form-container">
            <!-- Invoice Details Section -->
            <div class="form-section">
                <h3>Invoice Details</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Client *</label>
                        <select v-model="formData.client_id" required class="form-control">
                            <option value="">Select a client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Currency</label>
                        <select v-model="formData.currency" class="form-control">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="CAD">CAD - Canadian Dollar</option>
                            <option value="AUD">AUD - Australian Dollar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Issue Date *</label>
                        <input
                            v-model="formData.issue_date"
                            type="date"
                            required
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Due Date *</label>
                        <input
                            v-model="formData.due_date"
                            type="date"
                            required
                            class="form-control"
                        />
                    </div>
                </div>
            </div>

            <!-- From Details Section -->
            <div class="form-section">
                <h3>From (Your Business)</h3>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Business Name *</label>
                        <input
                            v-model="formData.from_details.name"
                            type="text"
                            required
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="formData.from_details.email"
                            type="email"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input
                            v-model="formData.from_details.address"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input
                            v-model="formData.from_details.city"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>State/Province</label>
                        <input
                            v-model="formData.from_details.state"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Postal Code</label>
                        <input
                            v-model="formData.from_details.postal_code"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <input
                            v-model="formData.from_details.country"
                            type="text"
                            class="form-control"
                        />
                    </div>
                </div>
            </div>

            <!-- To Details Section -->
            <div class="form-section">
                <h3>To (Client)</h3>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Client Name *</label>
                        <input
                            v-model="formData.to_details.name"
                            type="text"
                            required
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="formData.to_details.email"
                            type="email"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input
                            v-model="formData.to_details.address"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input
                            v-model="formData.to_details.city"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>State/Province</label>
                        <input
                            v-model="formData.to_details.state"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Postal Code</label>
                        <input
                            v-model="formData.to_details.postal_code"
                            type="text"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <input
                            v-model="formData.to_details.country"
                            type="text"
                            class="form-control"
                        />
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="form-section">
                <div class="section-header">
                    <h3>Line Items</h3>
                    <button
                        type="button"
                        @click="addLineItem"
                        class="btn btn-secondary btn-sm"
                    >
                        + Add Item
                    </button>
                </div>

                <div class="line-items-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%">Description</th>
                                <th style="width: 15%">Quantity</th>
                                <th style="width: 20%">Unit Price</th>
                                <th style="width: 20%">Amount</th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in formData.line_items" :key="index">
                                <td>
                                    <input
                                        v-model="item.description"
                                        type="text"
                                        placeholder="Description"
                                        required
                                        class="form-control"
                                        @input="calculateLineItem(index)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="form-control"
                                        @input="calculateLineItem(index)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="form-control"
                                        @input="calculateLineItem(index)"
                                    />
                                </td>
                                <td class="amount-cell">
                                    ${{ formatNumber(item.amount) }}
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        @click="removeLineItem(index)"
                                        class="btn-icon btn-danger"
                                        :disabled="formData.line_items.length === 1"
                                    >
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="form-section">
                <div class="totals-container">
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span class="amount">${{ formatNumber(calculatedTotals.subtotal) }}</span>
                    </div>

                    <div class="totals-row">
                        <div class="tax-input-group">
                            <label>Tax Rate (%):</label>
                            <input
                                v-model.number="formData.tax_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="form-control form-control-sm"
                                @input="recalculateTotals"
                            />
                        </div>
                        <span class="amount">${{ formatNumber(calculatedTotals.taxAmount) }}</span>
                    </div>

                    <div class="totals-row">
                        <div class="discount-input-group">
                            <label>Discount:</label>
                            <input
                                v-model.number="formData.discount_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control form-control-sm"
                                @input="recalculateTotals"
                            />
                        </div>
                        <span class="amount">-${{ formatNumber(formData.discount_amount || 0) }}</span>
                    </div>

                    <div class="totals-row total">
                        <span>Total:</span>
                        <span class="amount">${{ formatNumber(calculatedTotals.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes and Terms Section -->
            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea
                            v-model="formData.notes"
                            rows="3"
                            class="form-control"
                            placeholder="Any additional notes for the client..."
                        ></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Terms & Conditions</label>
                        <textarea
                            v-model="formData.terms"
                            rows="3"
                            class="form-control"
                            placeholder="Payment terms and conditions..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="btn btn-secondary"
                    :disabled="loading"
                >
                    Cancel
                </button>

                <div class="primary-actions">
                    <button
                        type="submit"
                        class="btn btn-secondary"
                        :disabled="loading"
                        @click.prevent="handleSaveAsDraft"
                    >
                        {{ loading ? 'Saving...' : 'Save as Draft' }}
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="loading"
                    >
                        {{ loading ? 'Saving...' : invoice ? 'Update Invoice' : 'Create & Send' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useInvoices } from '@/composables/payment/useInvoices';
import type { Invoice, InvoiceLineItem, InvoiceContactDetails } from '@/types/payment';

const props = defineProps<{
    organizationId: string;
    invoice?: Invoice | null;
    clients: Array<{ id: string; name: string }>;
}>();

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'created', invoice: Invoice): void;
    (e: 'updated', invoice: Invoice): void;
}>();

const { createInvoice, updateInvoice, loading, error, calculateTotals } = useInvoices(props.organizationId);

const formData = reactive<{
    client_id: string;
    issue_date: string;
    due_date: string;
    currency: string;
    from_details: InvoiceContactDetails;
    to_details: InvoiceContactDetails;
    line_items: InvoiceLineItem[];
    notes: string;
    terms: string;
    tax_rate: number;
    discount_amount: number;
}>({
    client_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    currency: 'USD',
    from_details: {
        name: '',
        email: '',
        address: '',
        city: '',
        state: '',
        postal_code: '',
        country: ''
    },
    to_details: {
        name: '',
        email: '',
        address: '',
        city: '',
        state: '',
        postal_code: '',
        country: ''
    },
    line_items: [
        {
            description: '',
            quantity: 1,
            unit_price: 0,
            amount: 0
        }
    ],
    notes: '',
    terms: '',
    tax_rate: 0,
    discount_amount: 0
});

onMounted(() => {
    if (props.invoice) {
        // Populate form with existing invoice data
        formData.client_id = props.invoice.client_id || '';
        formData.issue_date = props.invoice.issue_date;
        formData.due_date = props.invoice.due_date;
        formData.currency = props.invoice.currency;
        formData.from_details = { ...props.invoice.from_details };
        formData.to_details = { ...props.invoice.to_details };
        formData.line_items = props.invoice.line_items.map(item => ({ ...item }));
        formData.notes = props.invoice.notes || '';
        formData.terms = props.invoice.terms || '';
        formData.tax_rate = props.invoice.tax_rate;
        formData.discount_amount = props.invoice.discount_amount;
    }
});

// Watch client selection and populate "to" details
watch(() => formData.client_id, (clientId) => {
    if (clientId) {
        const client = props.clients.find(c => c.id === clientId);
        if (client) {
            formData.to_details.name = client.name;
        }
    }
});

const calculatedTotals = computed(() => {
    return calculateTotals(
        formData.line_items,
        formData.tax_rate,
        formData.discount_amount
    );
});

const calculateLineItem = (index: number) => {
    const item = formData.line_items[index];
    item.amount = parseFloat((item.quantity * item.unit_price).toFixed(2));
};

const recalculateTotals = () => {
    // Trigger reactivity
    formData.line_items = [...formData.line_items];
};

const addLineItem = () => {
    formData.line_items.push({
        description: '',
        quantity: 1,
        unit_price: 0,
        amount: 0
    });
};

const removeLineItem = (index: number) => {
    if (formData.line_items.length > 1) {
        formData.line_items.splice(index, 1);
    }
};

const handleSaveAsDraft = async () => {
    await handleSubmit(true);
};

const handleSubmit = async (saveAsDraft = false) => {
    // Ensure all line items are calculated
    formData.line_items.forEach((_, index) => calculateLineItem(index));

    const invoiceData = {
        client_id: formData.client_id || undefined,
        issue_date: formData.issue_date,
        due_date: formData.due_date,
        currency: formData.currency,
        from_details: formData.from_details,
        to_details: formData.to_details,
        line_items: formData.line_items,
        notes: formData.notes || undefined,
        terms: formData.terms || undefined,
        tax_rate: formData.tax_rate,
        discount_amount: formData.discount_amount
    };

    if (props.invoice) {
        // Update existing invoice
        const updated = await updateInvoice(props.invoice.id, invoiceData);
        if (updated) {
            emit('updated', updated);
        }
    } else {
        // Create new invoice
        const created = await createInvoice(invoiceData);
        if (created) {
            emit('created', created);
        }
    }
};

const formatNumber = (value: number): string => {
    return value.toFixed(2);
};
</script>

<style scoped>
.invoice-form {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    font-size: 1.875rem;
    font-weight: 600;
}

.error-message {
    background-color: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.form-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-control {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    width: 100px;
}

textarea.form-control {
    resize: vertical;
}

.line-items-table {
    overflow-x: auto;
}

.line-items-table table {
    width: 100%;
    border-collapse: collapse;
}

.line-items-table th {
    text-align: left;
    padding: 0.75rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    background-color: #f9fafb;
}

.line-items-table td {
    padding: 0.5rem;
}

.amount-cell {
    font-weight: 600;
    color: #111827;
}

.totals-container {
    max-width: 400px;
    margin-left: auto;
    border-top: 2px solid #e5e7eb;
    padding-top: 1rem;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.totals-row.total {
    font-size: 1.25rem;
    font-weight: 600;
    border-top: 2px solid #e5e7eb;
    padding-top: 1rem;
    margin-top: 0.5rem;
}

.totals-row .amount {
    font-weight: 600;
}

.tax-input-group,
.discount-input-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tax-input-group label,
.discount-input-group label {
    font-size: 0.875rem;
    margin: 0;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.primary-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    padding: 0.625rem 1.25rem;
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

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.btn-icon {
    padding: 0.25rem 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.125rem;
}

.btn-danger:hover {
    filter: brightness(0.8);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
        gap: 1rem;
    }

    .primary-actions {
        flex-direction: column;
    }
}
</style>
