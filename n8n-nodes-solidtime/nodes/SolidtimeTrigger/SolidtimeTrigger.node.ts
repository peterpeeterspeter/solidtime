import {
	IHookFunctions,
	IWebhookFunctions,
	IDataObject,
	INodeType,
	INodeTypeDescription,
	IWebhookResponseData,
	NodeApiError,
} from 'n8n-workflow';

import { createHmac } from 'crypto';

export class SolidtimeTrigger implements INodeType {
	description: INodeTypeDescription = {
		displayName: 'Solidtime Trigger',
		name: 'solidtimeTrigger',
		icon: 'file:solidtime.svg',
		group: ['trigger'],
		version: 1,
		subtitle: '={{$parameter["event"]}}',
		description: 'Starts the workflow when Solidtime events occur',
		defaults: {
			name: 'Solidtime Trigger',
		},
		inputs: [],
		outputs: ['main'],
		credentials: [
			{
				name: 'solidtimeApi',
				required: true,
			},
		],
		webhooks: [
			{
				name: 'default',
				httpMethod: 'POST',
				responseMode: 'onReceived',
				path: 'webhook',
			},
		],
		properties: [
			{
				displayName: 'Organization ID',
				name: 'organizationId',
				type: 'string',
				default: '',
				required: true,
				placeholder: 'e.g., 01234567-89ab-cdef-0123-456789abcdef',
				description: 'The ID of your Solidtime organization. Find this in your organization settings.',
			},
			{
				displayName: 'Events',
				name: 'events',
				type: 'multiOptions',
				required: true,
				default: [],
				description: 'The events that should trigger the workflow',
				options: [
					{
						name: 'Time Entry Started',
						value: 'time_entry.started',
						description: 'When a time entry is started',
					},
					{
						name: 'Time Entry Stopped',
						value: 'time_entry.stopped',
						description: 'When a time entry is stopped',
					},
					{
						name: 'Time Entry Created',
						value: 'time_entry.created',
						description: 'When a time entry is created',
					},
					{
						name: 'Time Entry Updated',
						value: 'time_entry.updated',
						description: 'When a time entry is updated',
					},
					{
						name: 'Time Entry Deleted',
						value: 'time_entry.deleted',
						description: 'When a time entry is deleted',
					},
					{
						name: 'Focus Session Detected',
						value: 'focus_session.detected',
						description: 'When a focus session is automatically detected',
					},
					{
						name: 'Focus Session Completed',
						value: 'focus_session.completed',
						description: 'When a focus session is completed',
					},
					{
						name: 'Project Created',
						value: 'project.created',
						description: 'When a project is created',
					},
					{
						name: 'Project Updated',
						value: 'project.updated',
						description: 'When a project is updated',
					},
					{
						name: 'Project Deleted',
						value: 'project.deleted',
						description: 'When a project is deleted',
					},
					{
						name: 'Task Created',
						value: 'task.created',
						description: 'When a task is created',
					},
					{
						name: 'Task Updated',
						value: 'task.updated',
						description: 'When a task is updated',
					},
					{
						name: 'Task Deleted',
						value: 'task.deleted',
						description: 'When a task is deleted',
					},
					{
						name: 'Task Completed',
						value: 'task.completed',
						description: 'When a task is marked as completed',
					},
					{
						name: 'Member Added',
						value: 'member.added',
						description: 'When a member is added to the organization',
					},
					{
						name: 'Member Removed',
						value: 'member.removed',
						description: 'When a member is removed from the organization',
					},
					{
						name: 'Report Generated',
						value: 'report.generated',
						description: 'When a report is generated',
					},
					{
						name: 'Timesheet Exported',
						value: 'timesheet.exported',
						description: 'When a timesheet is exported',
					},
					{
						name: 'Invoice Created',
						value: 'invoice.created',
						description: 'When an invoice is created',
					},
					{
						name: 'Invoice Sent',
						value: 'invoice.sent',
						description: 'When an invoice is sent',
					},
					{
						name: 'Invoice Paid',
						value: 'invoice.paid',
						description: 'When an invoice is marked as paid',
					},
				],
			},
			{
				displayName: 'Webhook Name',
				name: 'webhookName',
				type: 'string',
				default: '',
				placeholder: 'e.g., n8n Time Entry Automation',
				description: 'A friendly name for this webhook in Solidtime',
			},
			{
				displayName: 'Options',
				name: 'options',
				type: 'collection',
				placeholder: 'Add Option',
				default: {},
				options: [
					{
						displayName: 'Verify Signature',
						name: 'verifySignature',
						type: 'boolean',
						default: true,
						description: 'Whether to verify the HMAC signature of incoming webhooks for security',
					},
				],
			},
		],
	};

	// @ts-ignore (because of request)
	webhookMethods = {
		default: {
			async checkExists(this: IHookFunctions): Promise<boolean> {
				const webhookData = this.getWorkflowStaticData('node');
				if (webhookData.webhookId === undefined) {
					return false;
				}

				const credentials = await this.getCredentials('solidtimeApi');
				const baseUrl = credentials.baseUrl as string;
				const webhookId = webhookData.webhookId as string;

				try {
					const options = {
						method: 'GET',
						uri: `${baseUrl}/api/v1/webhooks/${webhookId}`,
						headers: {
							Authorization: `Bearer ${credentials.apiKey}`,
							Accept: 'application/json',
						},
						json: true,
					};

					const response = await this.helpers.request(options);
					return response.data !== undefined;
				} catch (error) {
					return false;
				}
			},

			async create(this: IHookFunctions): Promise<boolean> {
				const webhookUrl = this.getNodeWebhookUrl('default');
				const credentials = await this.getCredentials('solidtimeApi');
				const baseUrl = credentials.baseUrl as string;
				const organizationId = this.getNodeParameter('organizationId') as string;
				const events = this.getNodeParameter('events') as string[];
				const webhookName = this.getNodeParameter('webhookName', '') as string;

				const body: IDataObject = {
					organization_id: organizationId,
					name: webhookName || `n8n Webhook - ${this.getWorkflow().name}`,
					description: `Automatically created by n8n workflow: ${this.getWorkflow().name}`,
					url: webhookUrl,
					events,
					// Secret will be auto-generated by the API
				};

				try {
					const options = {
						method: 'POST',
						uri: `${baseUrl}/api/v1/webhooks`,
						headers: {
							Authorization: `Bearer ${credentials.apiKey}`,
							Accept: 'application/json',
							'Content-Type': 'application/json',
						},
						body,
						json: true,
					};

					const response = await this.helpers.request(options);
					const webhookData = this.getWorkflowStaticData('node');
					webhookData.webhookId = response.data.id;
					webhookData.webhookSecret = response.data.secret;

					return true;
				} catch (error) {
					throw new NodeApiError(this.getNode(), error as any);
				}
			},

			async delete(this: IHookFunctions): Promise<boolean> {
				const webhookData = this.getWorkflowStaticData('node');
				if (webhookData.webhookId === undefined) {
					return false;
				}

				const credentials = await this.getCredentials('solidtimeApi');
				const baseUrl = credentials.baseUrl as string;
				const webhookId = webhookData.webhookId as string;

				try {
					const options = {
						method: 'DELETE',
						uri: `${baseUrl}/api/v1/webhooks/${webhookId}`,
						headers: {
							Authorization: `Bearer ${credentials.apiKey}`,
							Accept: 'application/json',
						},
						json: true,
					};

					await this.helpers.request(options);
					delete webhookData.webhookId;
					delete webhookData.webhookSecret;

					return true;
				} catch (error) {
					return false;
				}
			},
		},
	};

	async webhook(this: IWebhookFunctions): Promise<IWebhookResponseData> {
		const webhookData = this.getWorkflowStaticData('node') as IDataObject;
		const options = this.getNodeParameter('options', {}) as IDataObject;
		const verifySignature = options.verifySignature !== false;

		const req = this.getRequestObject();
		const bodyData = this.getBodyData();

		// Verify signature if enabled
		if (verifySignature && webhookData.webhookSecret) {
			const signature = req.headers['x-solidtime-signature'] as string;
			if (!signature) {
				return {
					workflowData: [[]],
				};
			}

			const payload = JSON.stringify(bodyData);
			const expectedSignature = createHmac('sha256', webhookData.webhookSecret as string)
				.update(payload)
				.digest('hex');

			if (signature !== expectedSignature) {
				return {
					workflowData: [[]],
				};
			}
		}

		return {
			workflowData: [this.helpers.returnJsonArray(bodyData as IDataObject)],
		};
	}
}
