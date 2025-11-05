import { ref, computed } from 'vue';
import axios from 'axios';
import type {
    Invoice,
    InvoiceSettings,
    CreateInvoiceRequest,
    GenerateInvoiceFromTimeRequest
} from '@/types/invoice';
import { InvoiceStatus } from '@/types/invoice';

/**
 * Composable for invoice management
 */
export function useInvoices() {
    const invoices = ref<Invoice[]>([]);
    const currentInvoice = ref<Invoice | null>(null);
    const settings = ref<InvoiceSettings | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Fetch all invoices
     */
    const fetchInvoices = async (filters?: {
        status?: InvoiceStatus;
        projectId?: string;
        startDate?: string;
        endDate?: string;
    }) => {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (filters?.status) params.append('status', filters.status);
            if (filters?.projectId) params.append('project_id', filters.projectId);
            if (filters?.startDate) params.append('start_date', filters.startDate);
            if (filters?.endDate) params.append('end_date', filters.endDate);

            const response = await axios.get(`/api/v1/invoices?${params.toString()}`);
            invoices.value = response.data.data || [];
        } catch (err: any) {
            console.error('Failed to fetch invoices:', err);
            error.value = err.response?.data?.message || 'Failed to load invoices';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch single invoice
     */
    const fetchInvoice = async (invoiceId: string) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/v1/invoices/${invoiceId}`);
            currentInvoice.value = response.data.data;
            return response.data.data;
        } catch (err: any) {
            console.error('Failed to fetch invoice:', err);
            error.value = err.response?.data?.message || 'Failed to load invoice';
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create new invoice
     */
    const createInvoice = async (data: CreateInvoiceRequest): Promise<Invoice | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/v1/invoices', data);
            const newInvoice = response.data.data;
            invoices.value.unshift(newInvoice);
            return newInvoice;
        } catch (err: any) {
            console.error('Failed to create invoice:', err);
            error.value = err.response?.data?.message || 'Failed to create invoice';
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update invoice
     */
    const updateInvoice = async (invoiceId: string, data: Partial<Invoice>): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.patch(`/api/v1/invoices/${invoiceId}`, data);
            const updated = response.data.data;

            // Update in list
            const index = invoices.value.findIndex(i => i.id === invoiceId);
            if (index !== -1) {
                invoices.value[index] = updated;
            }

            if (currentInvoice.value?.id === invoiceId) {
                currentInvoice.value = updated;
            }

            return true;
        } catch (err: any) {
            console.error('Failed to update invoice:', err);
            error.value = err.response?.data?.message || 'Failed to update invoice';
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete invoice
     */
    const deleteInvoice = async (invoiceId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(`/api/v1/invoices/${invoiceId}`);

            // Remove from list
            invoices.value = invoices.value.filter(i => i.id !== invoiceId);

            if (currentInvoice.value?.id === invoiceId) {
                currentInvoice.value = null;
            }

            return true;
        } catch (err: any) {
            console.error('Failed to delete invoice:', err);
            error.value = err.response?.data?.message || 'Failed to delete invoice';
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Mark invoice as sent
     */
    const markAsSent = async (invoiceId: string): Promise<boolean> => {
        return updateInvoice(invoiceId, { status: InvoiceStatus.SENT });
    };

    /**
     * Mark invoice as paid
     */
    const markAsPaid = async (invoiceId: string, paidDate?: string): Promise<boolean> => {
        return updateInvoice(invoiceId, {
            status: InvoiceStatus.PAID,
            paidDate: paidDate || new Date().toISOString()
        });
    };

    /**
     * Download invoice PDF
     */
    const downloadPDF = async (invoiceId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/v1/invoices/${invoiceId}/pdf`, {
                responseType: 'blob'
            });

            // Create download link
            const blob = new Blob([response.data], { type: 'application/pdf' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `invoice-${invoiceId}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            return true;
        } catch (err: any) {
            console.error('Failed to download PDF:', err);
            error.value = err.response?.data?.message || 'Failed to download PDF';
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Generate invoice from time entries
     */
    const generateFromTime = async (data: GenerateInvoiceFromTimeRequest): Promise<Invoice | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/v1/invoices/generate-from-time', data);
            const newInvoice = response.data.data;
            invoices.value.unshift(newInvoice);
            return newInvoice;
        } catch (err: any) {
            console.error('Failed to generate invoice from time:', err);
            error.value = err.response?.data?.message || 'Failed to generate invoice';
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch invoice settings
     */
    const fetchSettings = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/v1/invoice-settings');
            settings.value = response.data.data;
        } catch (err: any) {
            console.error('Failed to fetch invoice settings:', err);
            error.value = err.response?.data?.message || 'Failed to load settings';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update invoice settings
     */
    const updateSettings = async (data: Partial<InvoiceSettings>): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.patch('/api/v1/invoice-settings', data);
            settings.value = response.data.data;
            return true;
        } catch (err: any) {
            console.error('Failed to update invoice settings:', err);
            error.value = err.response?.data?.message || 'Failed to update settings';
            return false;
        } finally {
            loading.value = false;
        }
    };

    // Computed
    const draftInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'draft')
    );

    const sentInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'sent')
    );

    const paidInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'paid')
    );

    const overdueInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'overdue')
    );

    const totalRevenue = computed(() =>
        paidInvoices.value.reduce((sum, inv) => sum + inv.total, 0)
    );

    const outstandingAmount = computed(() =>
        sentInvoices.value.reduce((sum, inv) => sum + inv.total, 0) +
        overdueInvoices.value.reduce((sum, inv) => sum + inv.total, 0)
    );

    return {
        // State
        invoices,
        currentInvoice,
        settings,
        loading,
        error,

        // Computed
        draftInvoices,
        sentInvoices,
        paidInvoices,
        overdueInvoices,
        totalRevenue,
        outstandingAmount,

        // Methods
        fetchInvoices,
        fetchInvoice,
        createInvoice,
        updateInvoice,
        deleteInvoice,
        markAsSent,
        markAsPaid,
        downloadPDF,
        generateFromTime,
        fetchSettings,
        updateSettings
    };
}
