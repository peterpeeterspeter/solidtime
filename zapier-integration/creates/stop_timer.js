/**
 * Action: Stop Timer
 *
 * Stops the currently running timer in Solidtime.
 */

const stopTimer = async (z, bundle) => {
  // First, get the active timer for the user
  const activeResponse = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/users/me/time-entries/active`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
  });

  const activeTimer = activeResponse.data;

  if (!activeTimer || !activeTimer.id) {
    throw new Error('No active timer found. Start a timer first.');
  }

  // Stop the timer by updating it with an end time
  const stopResponse = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/time-entries/${activeTimer.id}`,
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: {
      end: bundle.inputData.end || new Date().toISOString(),
    },
  });

  return stopResponse.data;
};

module.exports = {
  key: 'stop_timer',
  noun: 'Timer',

  display: {
    label: 'Stop Timer',
    description: 'Stops the currently running timer in Solidtime.',
    important: true,
  },

  operation: {
    perform: stopTimer,

    inputFields: [
      {
        key: 'organization_id',
        label: 'Organization ID',
        type: 'string',
        required: true,
        helpText: 'The ID of the organization with the running timer.',
      },
      {
        key: 'end',
        label: 'End Time',
        type: 'datetime',
        required: false,
        helpText: 'When to stop the timer (default: now)',
      },
    ],

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      description: 'Timer stopped from Zapier',
      start: '2025-11-05T16:00:00Z',
      end: '2025-11-05T17:30:00Z',
      duration_seconds: 5400,
      is_running: false,
      billable: true,
      user_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      project_id: '7c3d1234-5678-90ab-cdef-123456789abc',
      updated_at: '2025-11-05T17:30:00Z',
    },

    outputFields: [
      { key: 'id', label: 'Time Entry ID', type: 'string' },
      { key: 'description', label: 'Description', type: 'string' },
      { key: 'start', label: 'Start Time', type: 'datetime' },
      { key: 'end', label: 'End Time', type: 'datetime' },
      { key: 'duration_seconds', label: 'Duration (seconds)', type: 'integer' },
      { key: 'is_running', label: 'Is Running', type: 'boolean' },
      { key: 'billable', label: 'Billable', type: 'boolean' },
      { key: 'user_id', label: 'User ID', type: 'string' },
      { key: 'project_id', label: 'Project ID', type: 'string' },
    ],
  },
};
