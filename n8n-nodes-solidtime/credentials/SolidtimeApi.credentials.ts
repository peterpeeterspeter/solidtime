import {
	IAuthenticateGeneric,
	ICredentialTestRequest,
	ICredentialType,
	INodeProperties,
} from 'n8n-workflow';

export class SolidtimeApi implements ICredentialType {
	name = 'solidtimeApi';
	displayName = 'Solidtime API';
	documentationUrl = 'https://docs.solidtime.io/api';
	properties: INodeProperties[] = [
		{
			displayName: 'API Key',
			name: 'apiKey',
			type: 'string',
			typeOptions: {
				password: true,
			},
			default: '',
			required: true,
			placeholder: 'sk_...',
			description: 'The API key to use for authentication. Generate this in your Solidtime dashboard under Settings > Automation > API Keys.',
		},
		{
			displayName: 'Base URL',
			name: 'baseUrl',
			type: 'string',
			default: 'https://app.solidtime.io',
			required: true,
			description: 'The base URL of your Solidtime instance. Use https://app.solidtime.io for the hosted version, or your self-hosted URL.',
		},
	];

	// This allows the credential to be used by other parts of n8n
	// like the HTTP Request node
	authenticate: IAuthenticateGeneric = {
		type: 'generic',
		properties: {
			headers: {
				Authorization: '=Bearer {{$credentials.apiKey}}',
			},
		},
	};

	// Test the credential by making a request to the API
	test: ICredentialTestRequest = {
		request: {
			baseURL: '={{$credentials.baseUrl}}',
			url: '/api/v1/api-keys/scopes',
			method: 'GET',
		},
	};
}
