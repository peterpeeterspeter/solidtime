/**
 * Action: Start Timer
 *
 * Starts a new timer (time entry with no end time) in Solidtime.
 */

const startTimer = async (z, bundle) => {
  // Create a time entry without an end time to start a timer
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/time-entries`,
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: {
      description: bundle.inputData.description,
      start: bundle.inputData.start || new Date().toISOString(),
      end: null, // No end time = running timer
      project_id: bundle.inputData.project_id || null,
      task_id: bundle.inputData.task_id || null,
      billable: bundle.inputData.billable !== undefined ? bundle.inputData.billable : true,
      tags: bundle.inputData.tags ? bundle.inputData.tags.split(',').map(t => t.trim()) : [],
    },
  });

  return response.data;
};

module.exports = {
  key: 'start_timer',
  noun: 'Timer',

  display: {
    label: 'Start Timer',
    description: 'Starts a new running timer in Solidtime.',
    important: true,
  },

  operation: {
    perform: startTimer,

    inputFields: [
      {
        key: 'organization_id',
        label: 'Organization ID',
        type: 'string',
        required: true,
        helpText: 'The ID of the organization to start the timer in.',
      },
      {
        key: 'description',
        label: 'Description',
        type: 'text',
        required: true,
        helpText: 'What are you working on?',
      },
      {
        key: 'start',
        label: 'Start Time',
        type: 'datetime',
        required: false,
        helpText: 'When to start the timer (default: now)',
      },
      {
        key: 'project_id',
        label: 'Project ID',
        type: 'string',
        required: false,
        dynamic: 'project.id.name',
        helpText: 'Which project is this for?',
      },
      {
        key: 'task_id',
        label: 'Task ID',
        type: 'string',
        required: false,
        helpText: 'Optional task ID within the project.',
      },
      {
        key: 'billable',
        label: 'Billable',
        type: 'boolean',
        required: false,
        default: 'true',
        helpText: 'Is this time billable to the client?',
      },
      {
        key: 'tags',
        label: 'Tags',
        type: 'string',
        required: false,
        helpText: 'Comma-separated tags (e.g., "meeting, planning")',
      },
    ],

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      description: 'Timer started from Zapier',
      start: '2025-11-05T16:00:00Z',
      end: null,
      duration_seconds: null,
      is_running: true,
      billable: true,
      user_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      project_id: '7c3d1234-5678-90ab-cdef-123456789abc',
      created_at: '2025-11-05T16:00:00Z',
    },

    outputFields: [
      { key: 'id', label: 'Time Entry ID', type: 'string' },
      { key: 'description', label: 'Description', type: 'string' },
      { key: 'start', label: 'Start Time', type: 'datetime' },
      { key: 'end', label: 'End Time', type: 'datetime' },
      { key: 'is_running', label: 'Is Running', type: 'boolean' },
      { key: 'billable', label: 'Billable', type: 'boolean' },
      { key: 'user_id', label: 'User ID', type: 'string' },
      { key: 'project_id', label: 'Project ID', type: 'string' },
    ],
  },
};
