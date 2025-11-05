/**
 * Action: Create Time Entry
 *
 * Creates a new time entry in Solidtime.
 */

const createTimeEntry = async (z, bundle) => {
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
      start: bundle.inputData.start,
      end: bundle.inputData.end || null,
      project_id: bundle.inputData.project_id || null,
      task_id: bundle.inputData.task_id || null,
      billable: bundle.inputData.billable !== undefined ? bundle.inputData.billable : true,
      tags: bundle.inputData.tags ? bundle.inputData.tags.split(',').map(t => t.trim()) : [],
    },
  });

  return response.data;
};

const getProjects = async (z, bundle) => {
  // Dynamic dropdown for projects
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/organizations/${bundle.inputData.organization_id}/projects`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
  });

  return (response.data.data || []).map(project => ({
    value: project.id,
    label: project.name,
  }));
};

module.exports = {
  key: 'create_time_entry',
  noun: 'Time Entry',

  display: {
    label: 'Create Time Entry',
    description: 'Creates a new time entry in Solidtime.',
    important: true,
  },

  operation: {
    perform: createTimeEntry,

    inputFields: [
      {
        key: 'organization_id',
        label: 'Organization ID',
        type: 'string',
        required: true,
        helpText: 'The ID of the organization to create the time entry in.',
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
        required: true,
        helpText: 'When did you start working?',
      },
      {
        key: 'end',
        label: 'End Time',
        type: 'datetime',
        required: false,
        helpText: 'When did you finish? Leave empty for ongoing timer.',
      },
      {
        key: 'project_id',
        label: 'Project',
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
        helpText: 'Comma-separated tags (e.g., "development, frontend")',
      },
    ],

    sample: {
      id: '9a5f1234-5678-90ab-cdef-123456789abc',
      description: 'Task from Zapier',
      start: '2025-11-05T14:00:00Z',
      end: '2025-11-05T15:30:00Z',
      duration_seconds: 5400,
      billable: true,
      user_id: '8b4e1234-5678-90ab-cdef-123456789abc',
      project_id: '7c3d1234-5678-90ab-cdef-123456789abc',
      created_at: '2025-11-05T14:00:00Z',
    },

    outputFields: [
      { key: 'id', label: 'Time Entry ID', type: 'string' },
      { key: 'description', label: 'Description', type: 'string' },
      { key: 'start', label: 'Start Time', type: 'datetime' },
      { key: 'end', label: 'End Time', type: 'datetime' },
      { key: 'duration_seconds', label: 'Duration (seconds)', type: 'integer' },
      { key: 'billable', label: 'Billable', type: 'boolean' },
      { key: 'user_id', label: 'User ID', type: 'string' },
      { key: 'project_id', label: 'Project ID', type: 'string' },
    ],
  },
};
