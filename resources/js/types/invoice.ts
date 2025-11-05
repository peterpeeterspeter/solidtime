/**
 * Invoice Types
 * EU-compliant invoice data models
 */

export enum InvoiceStatus {
    DRAFT = 'draft',
    SENT = 'sent',
    PAID = 'paid',
    OVERDUE = 'overdue',
    CANCELLED = 'cancelled'
}

export enum PaymentTerms {
    NET_7 = 'net_7',
    NET_14 = 'net_14',
    NET_30 = 'net_30',
    NET_60 = 'net_60',
    DUE_ON_RECEIPT = 'due_on_receipt'
}

export enum TaxType {
    VAT = 'vat',
    GST = 'gst',
    NONE = 'none'
}

// Invoice line item
export interface InvoiceLineItem {
    id?: string;
    description: string;
    quantity: number;
    unitPrice: number;
    taxRate: number;
    amount: number; // quantity * unitPrice
    taxAmount: number; // amount * (taxRate / 100)
    total: number; // amount + taxAmount
}

// Invoice contact information
export interface InvoiceContact {
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    postalCode?: string;
    country?: string;
    vatNumber?: string; // EU VAT number
    companyNumber?: string; // Company registration number
}

// Invoice data model
export interface Invoice {
    id: string;
    invoiceNumber: string;
    status: InvoiceStatus;

    // Dates
    issueDate: string; // ISO date
    dueDate: string; // ISO date
    paidDate?: string; // ISO date

    // Parties
    from: InvoiceContact; // Issuer (freelancer/company)
    to: InvoiceContact; // Client

    // Line items
    lineItems: InvoiceLineItem[];

    // Amounts
    subtotal: number; // Sum of all line items (before tax)
    taxAmount: number; // Total tax
    total: number; // subtotal + taxAmount

    // Payment
    paymentTerms: PaymentTerms;
    currency: string; // ISO 4217 currency code

    // Additional fields
    notes?: string;
    taxType: TaxType;
    reverseCharge: boolean; // EU reverse charge mechanism

    // Metadata
    organizationId: string;
    projectId?: string;
    createdAt: string;
    updatedAt: string;
}

// Invoice creation request
export interface CreateInvoiceRequest {
    invoiceNumber?: string; // Auto-generated if not provided
    issueDate: string;
    dueDate: string;
    to: InvoiceContact;
    lineItems: Omit<InvoiceLineItem, 'id' | 'amount' | 'taxAmount' | 'total'>[];
    paymentTerms: PaymentTerms;
    notes?: string;
    projectId?: string;
}

// Invoice settings (organization-level)
export interface InvoiceSettings {
    // Issuer information
    from: InvoiceContact;

    // Defaults
    defaultPaymentTerms: PaymentTerms;
    defaultTaxRate: number;
    defaultCurrency: string;
    taxType: TaxType;

    // Invoice numbering
    invoiceNumberPrefix: string;
    nextInvoiceNumber: number;

    // EU-specific
    reverseChargeDefault: boolean;

    // Customization
    logoUrl?: string;
    primaryColor: string;

    // Bank details
    bankName?: string;
    iban?: string;
    bic?: string;

    // Footer
    footerText?: string;
}

// Invoice time entries (for generating invoices from tracked time)
export interface InvoiceTimeEntry {
    id: string;
    description: string;
    date: string;
    hours: number;
    rate: number;
    amount: number;
}

// Invoice generation from time entries
export interface GenerateInvoiceFromTimeRequest {
    projectId?: string;
    startDate: string;
    endDate: string;
    hourlyRate: number;
    groupByDate?: boolean;
    to: InvoiceContact;
    paymentTerms?: PaymentTerms;
    notes?: string;
}

// Helper functions for invoice calculations
export const InvoiceHelpers = {
    /**
     * Calculate line item totals
     */
    calculateLineItem(
        quantity: number,
        unitPrice: number,
        taxRate: number
    ): Pick<InvoiceLineItem, 'amount' | 'taxAmount' | 'total'> {
        const amount = quantity * unitPrice;
        const taxAmount = amount * (taxRate / 100);
        const total = amount + taxAmount;

        return {
            amount: Math.round(amount * 100) / 100,
            taxAmount: Math.round(taxAmount * 100) / 100,
            total: Math.round(total * 100) / 100
        };
    },

    /**
     * Calculate invoice totals
     */
    calculateTotals(lineItems: InvoiceLineItem[]): {
        subtotal: number;
        taxAmount: number;
        total: number;
    } {
        const subtotal = lineItems.reduce((sum, item) => sum + item.amount, 0);
        const taxAmount = lineItems.reduce((sum, item) => sum + item.taxAmount, 0);
        const total = lineItems.reduce((sum, item) => sum + item.total, 0);

        return {
            subtotal: Math.round(subtotal * 100) / 100,
            taxAmount: Math.round(taxAmount * 100) / 100,
            total: Math.round(total * 100) / 100
        };
    },

    /**
     * Calculate due date based on payment terms
     */
    calculateDueDate(issueDate: string, paymentTerms: PaymentTerms): string {
        const date = new Date(issueDate);

        switch (paymentTerms) {
            case PaymentTerms.NET_7:
                date.setDate(date.getDate() + 7);
                break;
            case PaymentTerms.NET_14:
                date.setDate(date.getDate() + 14);
                break;
            case PaymentTerms.NET_30:
                date.setDate(date.getDate() + 30);
                break;
            case PaymentTerms.NET_60:
                date.setDate(date.getDate() + 60);
                break;
            case PaymentTerms.DUE_ON_RECEIPT:
                // Due immediately
                break;
        }

        return date.toISOString().split('T')[0];
    },

    /**
     * Format payment terms for display
     */
    formatPaymentTerms(terms: PaymentTerms): string {
        switch (terms) {
            case PaymentTerms.NET_7:
                return 'Net 7 days';
            case PaymentTerms.NET_14:
                return 'Net 14 days';
            case PaymentTerms.NET_30:
                return 'Net 30 days';
            case PaymentTerms.NET_60:
                return 'Net 60 days';
            case PaymentTerms.DUE_ON_RECEIPT:
                return 'Due on receipt';
            default:
                return 'Net 30 days';
        }
    },

    /**
     * Format currency
     */
    formatCurrency(amount: number, currency: string): string {
        return new Intl.NumberFormat('en-EU', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Check if invoice is overdue
     */
    isOverdue(dueDate: string, status: InvoiceStatus): boolean {
        if (status === InvoiceStatus.PAID || status === InvoiceStatus.CANCELLED) {
            return false;
        }

        const due = new Date(dueDate);
        const now = new Date();
        return now > due;
    },

    /**
     * Generate invoice number
     */
    generateInvoiceNumber(prefix: string, nextNumber: number): string {
        const year = new Date().getFullYear();
        const paddedNumber = String(nextNumber).padStart(4, '0');
        return `${prefix}${year}-${paddedNumber}`;
    }
};
