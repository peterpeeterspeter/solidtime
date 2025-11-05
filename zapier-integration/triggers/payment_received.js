/**
 * Trigger: Payment Received
 *
 * Fires when a payment is received for an invoice in Solidtime.
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
      events: ['payment.received'],
      name: 'Zapier - Payment Received',
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

const getPayments = async (z, bundle) => {
  // Fallback: fetch recent payments
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/payments`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
    params: {
      per_page: 10,
    },
  });

  return response.data.data || [];
};

module.exports = {
  key: 'payment_received',
  noun: 'Payment',

  display: {
    label: 'Payment Received',
    description: 'Triggers when a payment is received for an invoice.',
    important: true,
  },

  operation: {
    type: 'hook',

    performSubscribe: subscribeHook,
    performUnsubscribe: unsubscribeHook,
    perform: getPayments,

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      invoice_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      invoice_number: 'INV-2025-001',
      amount: 5500.00,
      currency: 'USD',
      payment_method: 'stripe',
      payment_date: '2025-11-05T14:30:00Z',
      transaction_id: 'ch_3abc123def456',
      status: 'completed',
      client_name: 'Acme Corporation',
      notes: 'Payment received via Stripe',
    },

    outputFields: [
      { key: 'id', label: 'Payment ID', type: 'string' },
      { key: 'invoice_id', label: 'Invoice ID', type: 'string' },
      { key: 'invoice_number', label: 'Invoice Number', type: 'string' },
      { key: 'amount', label: 'Amount', type: 'number' },
      { key: 'currency', label: 'Currency', type: 'string' },
      { key: 'payment_method', label: 'Payment Method', type: 'string' },
      { key: 'payment_date', label: 'Payment Date', type: 'datetime' },
      { key: 'transaction_id', label: 'Transaction ID', type: 'string' },
      { key: 'status', label: 'Status', type: 'string' },
      { key: 'client_name', label: 'Client Name', type: 'string' },
      { key: 'notes', label: 'Notes', type: 'text' },
    ],
  },
};
