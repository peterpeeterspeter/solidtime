/**
 * Action: Create Invoice
 *
 * Creates a new invoice in Solidtime.
 */

const createInvoice = async (z, bundle) => {
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/invoices`,
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: {
      client_id: bundle.inputData.client_id,
      issue_date: bundle.inputData.issue_date,
      due_date: bundle.inputData.due_date,
      currency: bundle.inputData.currency || 'USD',
      notes: bundle.inputData.notes || null,
      items: bundle.inputData.items ? JSON.parse(bundle.inputData.items) : [],
    },
  });

  return response.data;
};

const getClients = async (z, bundle) => {
  // Dynamic dropdown for clients
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/clients`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
  });

  return (response.data.data || []).map(client => ({
    value: client.id,
    label: client.name,
  }));
};

module.exports = {
  key: 'create_invoice',
  noun: 'Invoice',

  display: {
    label: 'Create Invoice',
    description: 'Creates a new invoice in Solidtime.',
    important: true,
  },

  operation: {
    perform: createInvoice,

    inputFields: [
      {
        key: 'organization_id',
        label: 'Organization ID',
        type: 'string',
        required: true,
        helpText: 'The ID of the organization to create the invoice in.',
      },
      {
        key: 'client_id',
        label: 'Client',
        type: 'string',
        required: true,
        dynamic: 'client.id.name',
        helpText: 'Which client is this invoice for?',
      },
      {
        key: 'issue_date',
        label: 'Issue Date',
        type: 'date',
        required: true,
        helpText: 'When was this invoice issued?',
      },
      {
        key: 'due_date',
        label: 'Due Date',
        type: 'date',
        required: true,
        helpText: 'When is payment due?',
      },
      {
        key: 'currency',
        label: 'Currency',
        type: 'string',
        required: false,
        default: 'USD',
        choices: ['USD', 'EUR', 'GBP', 'CAD', 'AUD'],
        helpText: 'Invoice currency (default: USD)',
      },
      {
        key: 'items',
        label: 'Line Items (JSON)',
        type: 'text',
        required: false,
        helpText: 'JSON array of invoice items: [{"description":"Web Design","quantity":10,"unit_price":150}]',
      },
      {
        key: 'notes',
        label: 'Notes',
        type: 'text',
        required: false,
        helpText: 'Additional notes for the invoice',
      },
    ],

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      invoice_number: 'INV-2025-001',
      client_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      issue_date: '2025-11-01',
      due_date: '2025-11-30',
      status: 'draft',
      subtotal: 5000.00,
      tax: 500.00,
      total: 5500.00,
      currency: 'USD',
      created_at: '2025-11-05T10:00:00Z',
    },

    outputFields: [
      { key: 'id', label: 'Invoice ID', type: 'string' },
      { key: 'invoice_number', label: 'Invoice Number', type: 'string' },
      { key: 'client_id', label: 'Client ID', type: 'string' },
      { key: 'issue_date', label: 'Issue Date', type: 'date' },
      { key: 'due_date', label: 'Due Date', type: 'date' },
      { key: 'status', label: 'Status', type: 'string' },
      { key: 'subtotal', label: 'Subtotal', type: 'number' },
      { key: 'tax', label: 'Tax', type: 'number' },
      { key: 'total', label: 'Total Amount', type: 'number' },
      { key: 'currency', label: 'Currency', type: 'string' },
    ],
  },
};
