import {
	IExecuteFunctions,
	IDataObject,
	INodeExecutionData,
	INodeType,
	INodeTypeDescription,
	NodeApiError,
	NodeOperationError,
} from 'n8n-workflow';

export class Solidtime implements INodeType {
	description: INodeTypeDescription = {
		displayName: 'Solidtime',
		name: 'solidtime',
		icon: 'file:solidtime.svg',
		group: ['transform'],
		version: 1,
		subtitle: '={{$parameter["operation"] + ": " + $parameter["resource"]}}',
		description: 'Interact with Solidtime API - Create time entries, manage projects, and more',
		defaults: {
			name: 'Solidtime',
		},
		inputs: ['main'],
		outputs: ['main'],
		credentials: [
			{
				name: 'solidtimeApi',
				required: true,
			},
		],
		properties: [
			// Resource selection
			{
				displayName: 'Resource',
				name: 'resource',
				type: 'options',
				noDataExpression: true,
				options: [
					{
						name: 'Time Entry',
						value: 'timeEntry',
					},
					{
						name: 'Project',
						value: 'project',
					},
					{
						name: 'Task',
						value: 'task',
					},
					{
						name: 'Member',
						value: 'member',
					},
				],
				default: 'timeEntry',
			},

			// Time Entry Operations
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: {
					show: {
						resource: ['timeEntry'],
					},
				},
				options: [
					{
						name: 'Create',
						value: 'create',
						description: 'Create a new time entry',
						action: 'Create a time entry',
					},
					{
						name: 'Get',
						value: 'get',
						description: 'Get a time entry by ID',
						action: 'Get a time entry',
					},
					{
						name: 'Get All',
						value: 'getAll',
						description: 'Get all time entries',
						action: 'Get all time entries',
					},
					{
						name: 'Start',
						value: 'start',
						description: 'Start a new time entry',
						action: 'Start a time entry',
					},
					{
						name: 'Stop',
						value: 'stop',
						description: 'Stop the currently running time entry',
						action: 'Stop a time entry',
					},
					{
						name: 'Update',
						value: 'update',
						description: 'Update a time entry',
						action: 'Update a time entry',
					},
					{
						name: 'Delete',
						value: 'delete',
						description: 'Delete a time entry',
						action: 'Delete a time entry',
					},
				],
				default: 'create',
			},

			// Project Operations
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: {
					show: {
						resource: ['project'],
					},
				},
				options: [
					{
						name: 'Create',
						value: 'create',
						description: 'Create a new project',
						action: 'Create a project',
					},
					{
						name: 'Get',
						value: 'get',
						description: 'Get a project by ID',
						action: 'Get a project',
					},
					{
						name: 'Get All',
						value: 'getAll',
						description: 'Get all projects',
						action: 'Get all projects',
					},
					{
						name: 'Update',
						value: 'update',
						description: 'Update a project',
						action: 'Update a project',
					},
					{
						name: 'Delete',
						value: 'delete',
						description: 'Delete a project',
						action: 'Delete a project',
					},
				],
				default: 'create',
			},

			// Task Operations
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: {
					show: {
						resource: ['task'],
					},
				},
				options: [
					{
						name: 'Create',
						value: 'create',
						description: 'Create a new task',
						action: 'Create a task',
					},
					{
						name: 'Get',
						value: 'get',
						description: 'Get a task by ID',
						action: 'Get a task',
					},
					{
						name: 'Get All',
						value: 'getAll',
						description: 'Get all tasks',
						action: 'Get all tasks',
					},
					{
						name: 'Update',
						value: 'update',
						description: 'Update a task',
						action: 'Update a task',
					},
					{
						name: 'Delete',
						value: 'delete',
						description: 'Delete a task',
						action: 'Delete a task',
					},
				],
				default: 'create',
			},

			// Member Operations
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: {
					show: {
						resource: ['member'],
					},
				},
				options: [
					{
						name: 'Get All',
						value: 'getAll',
						description: 'Get all members',
						action: 'Get all members',
					},
					{
						name: 'Invite',
						value: 'invite',
						description: 'Invite a new member',
						action: 'Invite a member',
					},
				],
				default: 'getAll',
			},

			// Common field: Organization ID
			{
				displayName: 'Organization ID',
				name: 'organizationId',
				type: 'string',
				default: '',
				required: true,
				description: 'The ID of your Solidtime organization',
			},

			// Time Entry: Create fields
			{
				displayName: 'Description',
				name: 'description',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create', 'start'],
					},
				},
				default: '',
				description: 'Description of the time entry',
			},
			{
				displayName: 'Project ID',
				name: 'projectId',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create', 'start'],
					},
				},
				default: '',
				description: 'The project this time entry belongs to',
			},
			{
				displayName: 'Task ID',
				name: 'taskId',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create', 'start'],
					},
				},
				default: '',
				description: 'The task this time entry belongs to',
			},
			{
				displayName: 'Start Time',
				name: 'start',
				type: 'dateTime',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create'],
					},
				},
				default: '',
				required: true,
				description: 'When the time entry started',
			},
			{
				displayName: 'End Time',
				name: 'end',
				type: 'dateTime',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create'],
					},
				},
				default: '',
				description: 'When the time entry ended (leave empty for running timer)',
			},
			{
				displayName: 'Billable',
				name: 'billable',
				type: 'boolean',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['create', 'start'],
					},
				},
				default: false,
				description: 'Whether this time entry is billable',
			},

			// Time Entry: Get/Update/Delete ID
			{
				displayName: 'Time Entry ID',
				name: 'timeEntryId',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['get', 'update', 'delete'],
					},
				},
				default: '',
				required: true,
				description: 'The ID of the time entry',
			},

			// Time Entry: Stop (no additional fields needed)

			// Time Entry: Get All filters
			{
				displayName: 'Filters',
				name: 'filters',
				type: 'collection',
				placeholder: 'Add Filter',
				default: {},
				displayOptions: {
					show: {
						resource: ['timeEntry'],
						operation: ['getAll'],
					},
				},
				options: [
					{
						displayName: 'Project ID',
						name: 'project_id',
						type: 'string',
						default: '',
						description: 'Filter by project',
					},
					{
						displayName: 'Task ID',
						name: 'task_id',
						type: 'string',
						default: '',
						description: 'Filter by task',
					},
					{
						displayName: 'Start Date',
						name: 'start_date',
						type: 'dateTime',
						default: '',
						description: 'Filter entries starting from this date',
					},
					{
						displayName: 'End Date',
						name: 'end_date',
						type: 'dateTime',
						default: '',
						description: 'Filter entries up to this date',
					},
				],
			},

			// Project: Create fields
			{
				displayName: 'Project Name',
				name: 'projectName',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['project'],
						operation: ['create'],
					},
				},
				default: '',
				required: true,
				description: 'Name of the project',
			},
			{
				displayName: 'Color',
				name: 'color',
				type: 'color',
				displayOptions: {
					show: {
						resource: ['project'],
						operation: ['create'],
					},
				},
				default: '#3b82f6',
				description: 'Color for the project',
			},
			{
				displayName: 'Client Name',
				name: 'clientName',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['project'],
						operation: ['create'],
					},
				},
				default: '',
				description: 'Name of the client for this project',
			},
			{
				displayName: 'Billable',
				name: 'isBillable',
				type: 'boolean',
				displayOptions: {
					show: {
						resource: ['project'],
						operation: ['create'],
					},
				},
				default: false,
				description: 'Whether time entries on this project are billable by default',
			},

			// Project: Get/Update/Delete ID
			{
				displayName: 'Project ID',
				name: 'projectIdParam',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['project'],
						operation: ['get', 'update', 'delete'],
					},
				},
				default: '',
				required: true,
				description: 'The ID of the project',
			},

			// Task: Create fields
			{
				displayName: 'Task Name',
				name: 'taskName',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['task'],
						operation: ['create'],
					},
				},
				default: '',
				required: true,
				description: 'Name of the task',
			},
			{
				displayName: 'Project ID',
				name: 'projectIdForTask',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['task'],
						operation: ['create'],
					},
				},
				default: '',
				required: true,
				description: 'The project this task belongs to',
			},
			{
				displayName: 'Estimated Hours',
				name: 'estimatedHours',
				type: 'number',
				displayOptions: {
					show: {
						resource: ['task'],
						operation: ['create'],
					},
				},
				default: 0,
				description: 'Estimated hours for this task',
			},

			// Task: Get/Update/Delete ID
			{
				displayName: 'Task ID',
				name: 'taskIdParam',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['task'],
						operation: ['get', 'update', 'delete'],
					},
				},
				default: '',
				required: true,
				description: 'The ID of the task',
			},

			// Member: Invite fields
			{
				displayName: 'Email',
				name: 'email',
				type: 'string',
				displayOptions: {
					show: {
						resource: ['member'],
						operation: ['invite'],
					},
				},
				default: '',
				required: true,
				description: 'Email address of the member to invite',
			},
			{
				displayName: 'Role',
				name: 'role',
				type: 'options',
				displayOptions: {
					show: {
						resource: ['member'],
						operation: ['invite'],
					},
				},
				options: [
					{
						name: 'Admin',
						value: 'admin',
					},
					{
						name: 'Member',
						value: 'member',
					},
				],
				default: 'member',
				description: 'Role for the new member',
			},
		],
	};

	async execute(this: IExecuteFunctions): Promise<INodeExecutionData[][]> {
		const items = this.getInputData();
		const returnData: IDataObject[] = [];
		const credentials = await this.getCredentials('solidtimeApi');
		const baseUrl = credentials.baseUrl as string;

		for (let i = 0; i < items.length; i++) {
			try {
				const resource = this.getNodeParameter('resource', i) as string;
				const operation = this.getNodeParameter('operation', i) as string;
				const organizationId = this.getNodeParameter('organizationId', i) as string;

				let responseData: IDataObject | IDataObject[] = {};

				// TIME ENTRY operations
				if (resource === 'timeEntry') {
					if (operation === 'create') {
						const description = this.getNodeParameter('description', i) as string;
						const projectId = this.getNodeParameter('projectId', i) as string;
						const taskId = this.getNodeParameter('taskId', i) as string;
						const start = this.getNodeParameter('start', i) as string;
						const end = this.getNodeParameter('end', i) as string;
						const billable = this.getNodeParameter('billable', i) as boolean;

						const body: IDataObject = {
							organization_id: organizationId,
							description,
							start,
							billable,
						};

						if (projectId) body.project_id = projectId;
						if (taskId) body.task_id = taskId;
						if (end) body.end = end;

						responseData = await this.helpers.httpRequest({
							method: 'POST',
							url: `${baseUrl}/api/v1/time-entries`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'start') {
						const description = this.getNodeParameter('description', i) as string;
						const projectId = this.getNodeParameter('projectId', i) as string;
						const taskId = this.getNodeParameter('taskId', i) as string;
						const billable = this.getNodeParameter('billable', i) as boolean;

						const body: IDataObject = {
							organization_id: organizationId,
							description,
							start: new Date().toISOString(),
							billable,
						};

						if (projectId) body.project_id = projectId;
						if (taskId) body.task_id = taskId;

						responseData = await this.helpers.httpRequest({
							method: 'POST',
							url: `${baseUrl}/api/v1/time-entries`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'stop') {
						// Get the currently running time entry and stop it
						const runningEntries = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/time-entries?organization_id=${organizationId}&active=true`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});

						if (runningEntries.data && runningEntries.data.length > 0) {
							const entryId = runningEntries.data[0].id;
							responseData = await this.helpers.httpRequest({
								method: 'PUT',
								url: `${baseUrl}/api/v1/time-entries/${entryId}`,
								headers: {
									Authorization: `Bearer ${credentials.apiKey}`,
									'Content-Type': 'application/json',
								},
								body: {
									end: new Date().toISOString(),
								},
								json: true,
							});
						} else {
							throw new NodeOperationError(this.getNode(), 'No running time entry found');
						}
					} else if (operation === 'get') {
						const timeEntryId = this.getNodeParameter('timeEntryId', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/time-entries/${timeEntryId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'getAll') {
						const filters = this.getNodeParameter('filters', i, {}) as IDataObject;
						const queryParams = new URLSearchParams({
							organization_id: organizationId,
							...filters as any,
						});

						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/time-entries?${queryParams}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'update') {
						const timeEntryId = this.getNodeParameter('timeEntryId', i) as string;
						// For update, we'd need additional fields - simplified here
						const body: IDataObject = {};

						responseData = await this.helpers.httpRequest({
							method: 'PUT',
							url: `${baseUrl}/api/v1/time-entries/${timeEntryId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'delete') {
						const timeEntryId = this.getNodeParameter('timeEntryId', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'DELETE',
							url: `${baseUrl}/api/v1/time-entries/${timeEntryId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					}
				}

				// PROJECT operations
				else if (resource === 'project') {
					if (operation === 'create') {
						const projectName = this.getNodeParameter('projectName', i) as string;
						const color = this.getNodeParameter('color', i) as string;
						const clientName = this.getNodeParameter('clientName', i) as string;
						const isBillable = this.getNodeParameter('isBillable', i) as boolean;

						const body: IDataObject = {
							organization_id: organizationId,
							name: projectName,
							color,
							is_billable: isBillable,
						};

						if (clientName) body.client_name = clientName;

						responseData = await this.helpers.httpRequest({
							method: 'POST',
							url: `${baseUrl}/api/v1/projects`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'get') {
						const projectId = this.getNodeParameter('projectIdParam', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/projects/${projectId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'getAll') {
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/projects?organization_id=${organizationId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'update') {
						const projectId = this.getNodeParameter('projectIdParam', i) as string;
						const body: IDataObject = {};

						responseData = await this.helpers.httpRequest({
							method: 'PUT',
							url: `${baseUrl}/api/v1/projects/${projectId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'delete') {
						const projectId = this.getNodeParameter('projectIdParam', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'DELETE',
							url: `${baseUrl}/api/v1/projects/${projectId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					}
				}

				// TASK operations
				else if (resource === 'task') {
					if (operation === 'create') {
						const taskName = this.getNodeParameter('taskName', i) as string;
						const projectId = this.getNodeParameter('projectIdForTask', i) as string;
						const estimatedHours = this.getNodeParameter('estimatedHours', i) as number;

						const body: IDataObject = {
							organization_id: organizationId,
							project_id: projectId,
							name: taskName,
							estimated_hours: estimatedHours,
						};

						responseData = await this.helpers.httpRequest({
							method: 'POST',
							url: `${baseUrl}/api/v1/tasks`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'get') {
						const taskId = this.getNodeParameter('taskIdParam', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/tasks/${taskId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'getAll') {
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/tasks?organization_id=${organizationId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'update') {
						const taskId = this.getNodeParameter('taskIdParam', i) as string;
						const body: IDataObject = {};

						responseData = await this.helpers.httpRequest({
							method: 'PUT',
							url: `${baseUrl}/api/v1/tasks/${taskId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					} else if (operation === 'delete') {
						const taskId = this.getNodeParameter('taskIdParam', i) as string;
						responseData = await this.helpers.httpRequest({
							method: 'DELETE',
							url: `${baseUrl}/api/v1/tasks/${taskId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					}
				}

				// MEMBER operations
				else if (resource === 'member') {
					if (operation === 'getAll') {
						responseData = await this.helpers.httpRequest({
							method: 'GET',
							url: `${baseUrl}/api/v1/members?organization_id=${organizationId}`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
							},
							json: true,
						});
					} else if (operation === 'invite') {
						const email = this.getNodeParameter('email', i) as string;
						const role = this.getNodeParameter('role', i) as string;

						const body: IDataObject = {
							organization_id: organizationId,
							email,
							role,
						};

						responseData = await this.helpers.httpRequest({
							method: 'POST',
							url: `${baseUrl}/api/v1/members/invite`,
							headers: {
								Authorization: `Bearer ${credentials.apiKey}`,
								'Content-Type': 'application/json',
							},
							body,
							json: true,
						});
					}
				}

				if (Array.isArray(responseData)) {
					returnData.push(...responseData);
				} else {
					returnData.push(responseData);
				}
			} catch (error) {
				if (this.continueOnFail()) {
					returnData.push({ error: (error as Error).message });
					continue;
				}
				throw new NodeApiError(this.getNode(), error as any);
			}
		}

		return [this.helpers.returnJsonArray(returnData)];
	}
}
