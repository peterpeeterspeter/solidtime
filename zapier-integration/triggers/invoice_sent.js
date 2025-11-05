/**
 * Trigger: Invoice Sent
 *
 * Fires when an invoice is marked as sent in Solidtime.
 * Uses webhook subscription for real-time updates.
 */

const subscribeHook = async (z, bundle) => {
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/webhooks`,
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: {
      url: bundle.targetUrl,
      events: ['invoice.sent'],
      name: 'Zapier - Invoice Sent',
      description: 'Webhook created by Zapier integration',
    },
  });

  return response.data;
};

const unsubscribeHook = async (z, bundle) => {
  const webhookId = bundle.subscribeData.id;

  await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/webhooks/${webhookId}`,
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
  });

  return {};
};

const getInvoices = async (z, bundle) => {
  // Fallback: fetch recent invoices
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/invoices`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
    params: {
      per_page: 10,
      status: 'sent',
    },
  });

  return response.data.data || [];
};

module.exports = {
  key: 'invoice_sent',
  noun: 'Invoice',

  display: {
    label: 'Invoice Sent',
    description: 'Triggers when an invoice is marked as sent.',
    important: true,
  },

  operation: {
    type: 'hook',

    performSubscribe: subscribeHook,
    performUnsubscribe: unsubscribeHook,
    perform: getInvoices,

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      invoice_number: 'INV-2025-001',
      client_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      client_name: 'Acme Corporation',
      issue_date: '2025-11-01',
      due_date: '2025-11-30',
      status: 'sent',
      subtotal: 5000.00,
      tax: 500.00,
      total: 5500.00,
      currency: 'USD',
      sent_at: '2025-11-05T10:00:00Z',
      pdf_url: 'https://app.solidtime.io/invoices/9a5f1234/pdf',
    },

    outputFields: [
      { key: 'id', label: 'Invoice ID', type: 'string' },
      { key: 'invoice_number', label: 'Invoice Number', type: 'string' },
      { key: 'client_id', label: 'Client ID', type: 'string' },
      { key: 'client_name', label: 'Client Name', type: 'string' },
      { key: 'issue_date', label: 'Issue Date', type: 'date' },
      { key: 'due_date', label: 'Due Date', type: 'date' },
      { key: 'status', label: 'Status', type: 'string' },
      { key: 'subtotal', label: 'Subtotal', type: 'number' },
      { key: 'tax', label: 'Tax', type: 'number' },
      { key: 'total', label: 'Total Amount', type: 'number' },
      { key: 'currency', label: 'Currency', type: 'string' },
      { key: 'sent_at', label: 'Sent At', type: 'datetime' },
      { key: 'pdf_url', label: 'PDF URL', type: 'string' },
    ],
  },
};
