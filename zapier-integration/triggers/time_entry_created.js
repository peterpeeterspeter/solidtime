/**
 * Trigger: New Time Entry Created
 *
 * Fires when a new time entry is created in Solidtime.
 * Uses webhook subscription for real-time updates.
 */

const subscribeHook = async (z, bundle) => {
  // Subscribe to webhook for time_entry.created event
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
      events: ['time_entry.created'],
      name: 'Zapier - New Time Entry',
      description: 'Webhook created by Zapier integration',
    },
  });

  return response.data;
};

const unsubscribeHook = async (z, bundle) => {
  // Unsubscribe from webhook
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

const getTimeEntry = async (z, bundle) => {
  // Fallback: fetch recent time entries for testing
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/users/me/time-entries`,
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
  key: 'time_entry_created',
  noun: 'Time Entry',

  display: {
    label: 'New Time Entry',
    description: 'Triggers when a new time entry is created.',
    important: true,
  },

  operation: {
    type: 'hook',

    // Subscribe to webhook
    performSubscribe: subscribeHook,

    // Unsubscribe from webhook
    performUnsubscribe: unsubscribeHook,

    // Fallback for testing
    perform: getTimeEntry,

    // Sample data for Zapier editor
    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      description: 'Working on website redesign',
      start: '2025-11-05T09:00:00Z',
      end: '2025-11-05T10:30:00Z',
      duration_seconds: 5400,
      billable: true,
      user_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      project_id: '7c3d1234-5678-90ab-cdef-123456789abc',
      project_name: 'Website Redesign',
      task_id: null,
      tags: ['development', 'frontend'],
      created_at: '2025-11-05T09:00:00Z',
      updated_at: '2025-11-05T10:30:00Z',
    },

    // Output fields for mapping
    outputFields: [
      { key: 'id', label: 'Time Entry ID', type: 'string' },
      { key: 'description', label: 'Description', type: 'string' },
      { key: 'start', label: 'Start Time', type: 'datetime' },
      { key: 'end', label: 'End Time', type: 'datetime' },
      { key: 'duration_seconds', label: 'Duration (seconds)', type: 'integer' },
      { key: 'billable', label: 'Billable', type: 'boolean' },
      { key: 'user_id', label: 'User ID', type: 'string' },
      { key: 'project_id', label: 'Project ID', type: 'string' },
      { key: 'project_name', label: 'Project Name', type: 'string' },
      { key: 'task_id', label: 'Task ID', type: 'string' },
      { key: 'tags', label: 'Tags', type: 'string' },
    ],
  },
};
