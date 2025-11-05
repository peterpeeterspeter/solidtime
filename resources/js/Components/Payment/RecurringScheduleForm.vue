<template>
    <div class="recurring-schedule-form">
        <div class="form-header">
            <h2>{{ schedule ? 'Edit Recurring Schedule' : 'Create Recurring Schedule' }}</h2>
            <button @click="$emit('cancel')" class="btn btn-secondary">
                Cancel
            </button>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <form @submit.prevent="handleSubmit" class="form-container">
            <!-- Schedule Configuration -->
            <div class="form-section">
                <h3>Schedule Configuration</h3>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Schedule Name *</label>
                        <input
                            v-model="formData.name"
                            type="text"
                            required
                            placeholder="e.g., Monthly Retainer - Client X"
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>Frequency *</label>
                        <select v-model="formData.frequency" required class="form-control">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="biannually">Bi-annually</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Interval</label>
                        <input
                            v-model.number="formData.interval"
                            type="number"
                            min="1"
                            class="form-control"
                        />
                        <small class="help-text">{{ getFrequencyHelp() }}</small>
                    </div>

                    <div v-if="formData.frequency === 'monthly'" class="form-group">
                        <label>Day of Month</label>
                        <input
                            v-model.number="formData.day_of_month"
                            type="number"
                            min="1"
                            max="31"
                            class="form-control"
                        />
                    </div>

                    <div v-if="formData.frequency === 'weekly'" class="form-group">
                        <label>Day of Week</label>
                        <select v-model.number="formData.day_of_week" class="form-control">
                            <option :value="0">Sunday</option>
                            <option :value="1">Monday</option>
                            <option :value="2">Tuesday</option>
                            <option :value="3">Wednesday</option>
                            <option :value="4">Thursday</option>
                            <option :value="5">Friday</option>
                            <option :value="6">Saturday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Date *</label>
                        <input
                            v-model="formData.start_date"
                            type="date"
                            required
                            class="form-control"
                        />
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <input
                            v-model="formData.end_date"
                            type="date"
                            class="form-control"
                        />
                        <small class="help-text">Leave empty for indefinite</small>
                    </div>

                    <div class="form-group">
                        <label>Max Occurrences</label>
                        <input
                            v-model.number="formData.max_occurrences"
                            type="number"
                            min="1"
                            class="form-control"
                        />
                        <small class="help-text">Leave empty for unlimited</small>
                    </div>

                    <div class="form-group">
                        <label>Client</label>
                        <select v-model="formData.client_id" class="form-control">
                            <option value="">Select a client (optional)</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="form-section">
                <h3>Invoice Template</h3>

                <div class="form-grid">
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
                        <label>Due Days *</label>
                        <input
                            v-model.number="formData.due_days"
                            type="number"
                            min="0"
                            required
                            class="form-control"
                        />
                        <small class="help-text">Days after issue date</small>
                    </div>

                    <div class="form-group">
                        <label>Due Date Type</label>
                        <select v-model="formData.due_date_type" class="form-control">
                            <option value="from_issue">From Issue Date</option>
                            <option value="from_month_end">From Month End</option>
                            <option value="from_month_start">From Month Start</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
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

                <!-- Totals -->
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

            <!-- Automation Settings -->
            <div class="form-section">
                <h3>Automation Settings</h3>

                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input
                            v-model="formData.auto_send"
                            type="checkbox"
                            class="checkbox"
                        />
                        <span>Automatically send invoices when generated</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="formData.auto_charge"
                            type="checkbox"
                            class="checkbox"
                        />
                        <span>Automatically charge via payment gateway</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="formData.notify_on_generation"
                            type="checkbox"
                            class="checkbox"
                        />
                        <span>Send notification when invoice is generated</span>
                    </label>
                </div>

                <div v-if="formData.notify_on_generation" class="form-group" style="margin-top: 1rem;">
                    <label>Notification Emails</label>
                    <input
                        v-model="notificationEmailsInput"
                        type="text"
                        placeholder="email1@example.com, email2@example.com"
                        class="form-control"
                    />
                    <small class="help-text">Comma-separated email addresses</small>
                </div>
            </div>

            <!-- Notes and Terms -->
            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea
                            v-model="formData.notes"
                            rows="3"
                            class="form-control"
                        ></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Terms & Conditions</label>
                        <textarea
                            v-model="formData.terms"
                            rows="3"
                            class="form-control"
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

                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="loading"
                >
                    {{ loading ? 'Saving...' : schedule ? 'Update Schedule' : 'Create Schedule' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { useRecurringSchedules } from '@/composables/payment/useRecurringSchedules';
import type {
    RecurringInvoiceSchedule,
    InvoiceLineItem,
    InvoiceContactDetails
} from '@/types/payment';

const props = defineProps<{
    organizationId: string;
    schedule?: RecurringInvoiceSchedule | null;
    clients: Array<{ id: string; name: string }>;
}>();

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'created', schedule: RecurringInvoiceSchedule): void;
    (e: 'updated', schedule: RecurringInvoiceSchedule): void;
}>();

const { createSchedule, updateSchedule, loading, error, calculateTotals } = useRecurringSchedules(props.organizationId);

const notificationEmailsInput = ref('');

const formData = reactive<any>({
    name: '',
    frequency: 'monthly',
    interval: 1,
    day_of_month: null,
    day_of_week: null,
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    max_occurrences: null,
    client_id: '',
    currency: 'USD',
    due_days: 30,
    due_date_type: 'from_issue',
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
    discount_amount: 0,
    auto_send: false,
    auto_charge: false,
    notify_on_generation: true,
    notification_emails: []
});

onMounted(() => {
    if (props.schedule) {
        Object.assign(formData, {
            name: props.schedule.name,
            frequency: props.schedule.frequency,
            interval: props.schedule.interval,
            day_of_month: props.schedule.day_of_month,
            day_of_week: props.schedule.day_of_week,
            start_date: props.schedule.start_date,
            end_date: props.schedule.end_date || '',
            max_occurrences: props.schedule.max_occurrences,
            client_id: props.schedule.client_id || '',
            currency: props.schedule.currency,
            due_days: props.schedule.due_days,
            due_date_type: props.schedule.due_date_type,
            from_details: { ...props.schedule.from_details },
            to_details: { ...props.schedule.to_details },
            line_items: props.schedule.line_items.map(item => ({ ...item })),
            notes: props.schedule.notes || '',
            terms: props.schedule.terms || '',
            tax_rate: props.schedule.tax_rate,
            discount_amount: props.schedule.discount_amount,
            auto_send: props.schedule.auto_send,
            auto_charge: props.schedule.auto_charge,
            notify_on_generation: props.schedule.notify_on_generation
        });

        if (props.schedule.notification_emails) {
            notificationEmailsInput.value = props.schedule.notification_emails.join(', ');
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

const getFrequencyHelp = (): string => {
    const interval = formData.interval || 1;
    switch (formData.frequency) {
        case 'daily':
            return interval === 1 ? 'Generate daily' : `Generate every ${interval} days`;
        case 'weekly':
            return interval === 1 ? 'Generate weekly' : `Generate every ${interval} weeks`;
        case 'monthly':
            return interval === 1 ? 'Generate monthly' : `Generate every ${interval} months`;
        default:
            return '';
    }
};

const handleSubmit = async () => {
    // Ensure all line items are calculated
    formData.line_items.forEach((_: any, index: number) => calculateLineItem(index));

    // Parse notification emails
    const notificationEmails = notificationEmailsInput.value
        .split(',')
        .map(email => email.trim())
        .filter(email => email.length > 0);

    const scheduleData = {
        ...formData,
        end_date: formData.end_date || undefined,
        max_occurrences: formData.max_occurrences || undefined,
        client_id: formData.client_id || undefined,
        day_of_month: formData.day_of_month || undefined,
        day_of_week: formData.day_of_week || undefined,
        notification_emails: notificationEmails.length > 0 ? notificationEmails : undefined
    };

    if (props.schedule) {
        const updated = await updateSchedule(props.schedule.id, scheduleData);
        if (updated) {
            emit('updated', updated);
        }
    } else {
        const created = await createSchedule(scheduleData);
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
/* Reusing similar styles from InvoiceForm */
.recurring-schedule-form {
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

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
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

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
}

.line-items-table {
    overflow-x: auto;
    margin-bottom: 1rem;
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
}
</style>
