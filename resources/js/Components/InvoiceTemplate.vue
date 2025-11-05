<template>
    <div class="invoice-template bg-white p-8 md:p-12 max-w-4xl mx-auto" :style="{ '--primary-color': primaryColor }">
        <!-- Header -->
        <div class="flex items-start justify-between mb-12">
            <!-- Logo & Issuer -->
            <div>
                <div v-if="logoUrl" class="mb-4">
                    <img :src="logoUrl" alt="Logo" class="h-12 object-contain" />
                </div>
                <div class="text-sm">
                    <div class="font-bold text-lg mb-2">{{ invoice.from.name }}</div>
                    <div v-if="invoice.from.address">{{ invoice.from.address }}</div>
                    <div v-if="invoice.from.city || invoice.from.postalCode">
                        {{ invoice.from.postalCode }} {{ invoice.from.city }}
                    </div>
                    <div v-if="invoice.from.country">{{ invoice.from.country }}</div>
                    <div v-if="invoice.from.vatNumber" class="mt-2">
                        <strong>VAT:</strong> {{ invoice.from.vatNumber }}
                    </div>
                    <div v-if="invoice.from.companyNumber">
                        <strong>Company No:</strong> {{ invoice.from.companyNumber }}
                    </div>
                    <div v-if="invoice.from.email" class="mt-2">{{ invoice.from.email }}</div>
                    <div v-if="invoice.from.phone">{{ invoice.from.phone }}</div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="text-right">
                <div class="text-3xl font-bold mb-4" :style="{ color: primaryColor }">INVOICE</div>
                <div class="text-sm space-y-1">
                    <div><strong>Invoice No:</strong> {{ invoice.invoiceNumber }}</div>
                    <div><strong>Date:</strong> {{ formatDate(invoice.issueDate) }}</div>
                    <div><strong>Due Date:</strong> {{ formatDate(invoice.dueDate) }}</div>
                    <div v-if="invoice.status" class="mt-3">
                        <span
                            class="inline-block px-3 py-1 text-xs font-semibold rounded-full"
                            :class="statusClasses"
                        >
                            {{ formatStatus(invoice.status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill To -->
        <div class="mb-12">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Bill To</div>
            <div class="text-sm">
                <div class="font-bold text-lg mb-2">{{ invoice.to.name }}</div>
                <div v-if="invoice.to.address">{{ invoice.to.address }}</div>
                <div v-if="invoice.to.city || invoice.to.postalCode">
                    {{ invoice.to.postalCode }} {{ invoice.to.city }}
                </div>
                <div v-if="invoice.to.country">{{ invoice.to.country }}</div>
                <div v-if="invoice.to.vatNumber" class="mt-2">
                    <strong>VAT:</strong> {{ invoice.to.vatNumber }}
                </div>
                <div v-if="invoice.to.email" class="mt-2">{{ invoice.to.email }}</div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="mb-8">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2" :style="{ borderColor: primaryColor }">
                        <th class="text-left py-3 text-sm font-semibold">Description</th>
                        <th class="text-right py-3 text-sm font-semibold w-20">Qty</th>
                        <th class="text-right py-3 text-sm font-semibold w-28">Unit Price</th>
                        <th v-if="showTax" class="text-right py-3 text-sm font-semibold w-20">Tax</th>
                        <th class="text-right py-3 text-sm font-semibold w-32">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, index) in invoice.lineItems"
                        :key="index"
                        class="border-b border-gray-200"
                    >
                        <td class="py-3 text-sm">{{ item.description }}</td>
                        <td class="py-3 text-sm text-right">{{ item.quantity }}</td>
                        <td class="py-3 text-sm text-right">{{ formatCurrency(item.unitPrice) }}</td>
                        <td v-if="showTax" class="py-3 text-sm text-right">{{ item.taxRate }}%</td>
                        <td class="py-3 text-sm text-right font-medium">{{ formatCurrency(item.total) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end mb-12">
            <div class="w-80">
                <div class="flex justify-between py-2 text-sm">
                    <span>Subtotal:</span>
                    <span class="font-medium">{{ formatCurrency(invoice.subtotal) }}</span>
                </div>
                <div v-if="showTax" class="flex justify-between py-2 text-sm">
                    <span>{{ taxLabel }}:</span>
                    <span class="font-medium">{{ formatCurrency(invoice.taxAmount) }}</span>
                </div>
                <div v-if="invoice.reverseCharge" class="py-2 text-xs text-gray-600 italic">
                    * Reverse charge applies - VAT is payable by the recipient
                </div>
                <div
                    class="flex justify-between py-3 text-lg font-bold border-t-2"
                    :style="{ borderColor: primaryColor }"
                >
                    <span>Total:</span>
                    <span>{{ formatCurrency(invoice.total) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Terms & Notes -->
        <div class="mb-8 space-y-4">
            <div v-if="invoice.paymentTerms">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Payment Terms</div>
                <div class="text-sm">{{ formatPaymentTerms(invoice.paymentTerms) }}</div>
            </div>

            <div v-if="bankDetails">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Bank Details</div>
                <div class="text-sm space-y-1">
                    <div v-if="bankDetails.bankName"><strong>Bank:</strong> {{ bankDetails.bankName }}</div>
                    <div v-if="bankDetails.iban"><strong>IBAN:</strong> {{ bankDetails.iban }}</div>
                    <div v-if="bankDetails.bic"><strong>BIC/SWIFT:</strong> {{ bankDetails.bic }}</div>
                </div>
            </div>

            <div v-if="invoice.notes">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Notes</div>
                <div class="text-sm whitespace-pre-line">{{ invoice.notes }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div v-if="footerText" class="text-center text-xs text-gray-500 border-t border-gray-200 pt-6 mt-12">
            {{ footerText }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Invoice, InvoiceStatus } from '@/types/invoice';
import { InvoiceHelpers } from '@/types/invoice';

interface Props {
    invoice: Invoice;
    logoUrl?: string;
    primaryColor?: string;
    bankDetails?: {
        bankName?: string;
        iban?: string;
        bic?: string;
    };
    footerText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    primaryColor: '#06b6d4',
    footerText: 'Thank you for your business!'
});

// Computed
const showTax = computed(() => props.invoice.taxAmount > 0);

const taxLabel = computed(() => {
    switch (props.invoice.taxType) {
        case 'vat':
            return 'VAT';
        case 'gst':
            return 'GST';
        default:
            return 'Tax';
    }
});

const statusClasses = computed(() => {
    switch (props.invoice.status) {
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'sent':
            return 'bg-blue-100 text-blue-800';
        case 'overdue':
            return 'bg-red-100 text-red-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
});

// Methods
const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
};

const formatCurrency = (amount: number): string => {
    return InvoiceHelpers.formatCurrency(amount, props.invoice.currency);
};

const formatPaymentTerms = (terms: string): string => {
    return InvoiceHelpers.formatPaymentTerms(terms as any);
};

const formatStatus = (status: InvoiceStatus): string => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<style scoped>
.invoice-template {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Print styles */
@media print {
    .invoice-template {
        padding: 0;
        max-width: none;
    }
}
</style>
