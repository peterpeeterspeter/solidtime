import { ref, computed } from 'vue';
import axios from 'axios';
import type {
    Invoice,
    InvoiceStatus,
    CreateInvoiceRequest,
    UpdateInvoiceRequest,
    InvoiceLineItem,
    PaginatedResponse
} from '@/types/payment';

export function useInvoices(organizationId: string) {
    const invoices = ref<Invoice[]>([]);
    const currentInvoice = ref<Invoice | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
    });

    /**
     * Fetch invoices with optional filtering
     */
    const fetchInvoices = async (params: {
        page?: number;
        per_page?: number;
        status?: InvoiceStatus | 'all';
        client_id?: string;
        search?: string;
    } = {}): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<PaginatedResponse<Invoice>>(
                `/api/v1/organizations/${organizationId}/invoices`,
                { params }
            );

            invoices.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total
            };
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch invoices';
            console.error('Error fetching invoices:', err);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch a single invoice
     */
    const fetchInvoice = async (invoiceId: string): Promise<Invoice | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: Invoice }>(
                `/api/v1/organizations/${organizationId}/invoices/${invoiceId}`
            );
            currentInvoice.value = response.data.data;
            return response.data.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch invoice';
            console.error('Error fetching invoice:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create a new invoice
     */
    const createInvoice = async (data: CreateInvoiceRequest): Promise<Invoice | null> => {
        loading.value = true;
        error.value = null;

        try {
            // Calculate amounts
            const subtotal = data.line_items.reduce((sum, item) => sum + item.amount, 0);
            const taxAmount = subtotal * (data.tax_rate || 0) / 100;
            const total = subtotal + taxAmount - (data.discount_amount || 0);

            const response = await axios.post<{ data: Invoice }>(
                `/api/v1/organizations/${organizationId}/invoices`,
                {
                    ...data,
                    subtotal,
                    tax_amount: taxAmount,
                    total
                }
            );

            const invoice = response.data.data;
            invoices.value.unshift(invoice);
            return invoice;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create invoice';
            console.error('Error creating invoice:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update an invoice
     */
    const updateInvoice = async (
        invoiceId: string,
        data: UpdateInvoiceRequest
    ): Promise<Invoice | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.put<{ data: Invoice }>(
                `/api/v1/organizations/${organizationId}/invoices/${invoiceId}`,
                data
            );

            const invoice = response.data.data;
            const index = invoices.value.findIndex(i => i.id === invoiceId);
            if (index !== -1) {
                invoices.value[index] = invoice;
            }
            if (currentInvoice.value?.id === invoiceId) {
                currentInvoice.value = invoice;
            }
            return invoice;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update invoice';
            console.error('Error updating invoice:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete an invoice
     */
    const deleteInvoice = async (invoiceId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(
                `/api/v1/organizations/${organizationId}/invoices/${invoiceId}`
            );
            invoices.value = invoices.value.filter(i => i.id !== invoiceId);
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete invoice';
            console.error('Error deleting invoice:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Mark invoice as sent
     */
    const markAsSent = async (invoiceId: string): Promise<boolean> => {
        const result = await updateInvoice(invoiceId, { status: 'sent' });
        return result !== null;
    };

    /**
     * Mark invoice as paid
     */
    const markAsPaid = async (invoiceId: string): Promise<boolean> => {
        const result = await updateInvoice(invoiceId, { status: 'paid' });
        return result !== null;
    };

    /**
     * Cancel an invoice
     */
    const cancelInvoice = async (invoiceId: string): Promise<boolean> => {
        const result = await updateInvoice(invoiceId, { status: 'cancelled' });
        return result !== null;
    };

    /**
     * Send invoice email
     */
    const sendInvoice = async (invoiceId: string, email: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.post(
                `/api/v1/organizations/${organizationId}/invoices/${invoiceId}/send`,
                { email }
            );
            await markAsSent(invoiceId);
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to send invoice';
            console.error('Error sending invoice:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Calculate line item amount
     */
    const calculateLineItemAmount = (quantity: number, unitPrice: number): number => {
        return parseFloat((quantity * unitPrice).toFixed(2));
    };

    /**
     * Calculate invoice totals
     */
    const calculateTotals = (
        lineItems: InvoiceLineItem[],
        taxRate: number = 0,
        discountAmount: number = 0
    ) => {
        const subtotal = lineItems.reduce((sum, item) => sum + item.amount, 0);
        const taxAmount = (subtotal * taxRate) / 100;
        const total = subtotal + taxAmount - discountAmount;

        return {
            subtotal: parseFloat(subtotal.toFixed(2)),
            taxAmount: parseFloat(taxAmount.toFixed(2)),
            total: parseFloat(total.toFixed(2))
        };
    };

    /**
     * Filter invoices by status
     */
    const filterByStatus = (status: InvoiceStatus | 'all'): Invoice[] => {
        if (status === 'all') {
            return invoices.value;
        }
        return invoices.value.filter(i => i.status === status);
    };

    /**
     * Get invoice statistics
     */
    const statistics = computed(() => {
        const draft = invoices.value.filter(i => i.status === 'draft').length;
        const sent = invoices.value.filter(i => i.status === 'sent').length;
        const paid = invoices.value.filter(i => i.status === 'paid').length;
        const overdue = invoices.value.filter(i => i.status === 'overdue').length;

        const totalAmount = invoices.value.reduce((sum, i) => sum + i.total, 0);
        const paidAmount = invoices.value
            .filter(i => i.status === 'paid')
            .reduce((sum, i) => sum + i.total, 0);
        const outstandingAmount = invoices.value
            .filter(i => i.status === 'sent' || i.status === 'overdue')
            .reduce((sum, i) => sum + i.total, 0);

        return {
            draft,
            sent,
            paid,
            overdue,
            totalAmount,
            paidAmount,
            outstandingAmount
        };
    });

    return {
        invoices,
        currentInvoice,
        loading,
        error,
        pagination,
        statistics,
        fetchInvoices,
        fetchInvoice,
        createInvoice,
        updateInvoice,
        deleteInvoice,
        markAsSent,
        markAsPaid,
        cancelInvoice,
        sendInvoice,
        calculateLineItemAmount,
        calculateTotals,
        filterByStatus
    };
}
