/**
 * Trigger: Project Archived
 *
 * Fires when a project is archived in Solidtime.
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
      events: ['project.archived'],
      name: 'Zapier - Project Archived',
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

const getProjects = async (z, bundle) => {
  // Fallback: fetch recent archived projects
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/projects`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
    params: {
      per_page: 10,
      is_archived: true,
    },
  });

  return response.data.data || [];
};

module.exports = {
  key: 'project_archived',
  noun: 'Project',

  display: {
    label: 'Project Archived',
    description: 'Triggers when a project is archived.',
  },

  operation: {
    type: 'hook',

    performSubscribe: subscribeHook,
    performUnsubscribe: unsubscribeHook,
    perform: getProjects,

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      name: 'Website Redesign',
      client_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      client_name: 'Acme Corporation',
      color: '#3b82f6',
      billable_rate: 150.00,
      is_billable: true,
      is_archived: true,
      archived_at: '2025-11-05T15:00:00Z',
      created_at: '2025-01-01T00:00:00Z',
      total_hours: 240.5,
      total_earnings: 36075.00,
    },

    outputFields: [
      { key: 'id', label: 'Project ID', type: 'string' },
      { key: 'name', label: 'Project Name', type: 'string' },
      { key: 'client_id', label: 'Client ID', type: 'string' },
      { key: 'client_name', label: 'Client Name', type: 'string' },
      { key: 'color', label: 'Color', type: 'string' },
      { key: 'billable_rate', label: 'Billable Rate', type: 'number' },
      { key: 'is_billable', label: 'Is Billable', type: 'boolean' },
      { key: 'is_archived', label: 'Is Archived', type: 'boolean' },
      { key: 'archived_at', label: 'Archived At', type: 'datetime' },
      { key: 'created_at', label: 'Created At', type: 'datetime' },
      { key: 'total_hours', label: 'Total Hours', type: 'number' },
      { key: 'total_earnings', label: 'Total Earnings', type: 'number' },
    ],
  },
};
