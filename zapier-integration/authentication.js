/**
 * Authentication Configuration for Solidtime Zapier Integration
 *
 * Uses API key authentication with Bearer token.
 */

const testAuthentication = async (z, bundle) => {
  // Test the API key by fetching the authenticated user
  const response = await z.request({
    url: `${process.env.BASE_URL || 'https://api.solidtime.io'}/api/v1/users/me`,
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${bundle.authData.api_key}`,
      'Accept': 'application/json',
    },
  });

  if (response.status !== 200) {
    throw new Error('Invalid API key. Please check your credentials.');
  }

  return response.data;
};

module.exports = {
  type: 'custom',

  // Configuration fields shown in Zapier UI
  fields: [
    {
      key: 'api_key',
      label: 'API Key',
      required: true,
      type: 'string',
      helpText: 'Get your API key from Settings > API Tokens in Solidtime (https://app.solidtime.io/settings/api-tokens)',
    },
  ],

  // Test function to validate the API key
  test: testAuthentication,

  // Connection label shown in Zapier
  connectionLabel: '{{name}} ({{email}})',
};
