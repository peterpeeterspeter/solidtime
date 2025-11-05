/**
 * Authentication Tests
 */

const zapier = require('zapier-platform-core');
const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Authentication', () => {
  it('should authenticate with valid API key', async () => {
    const bundle = {
      authData: {
        api_key: process.env.TEST_API_KEY || 'test_key_123',
      },
    };

    const result = await appTester(
      App.authentication.test,
      bundle
    );

    expect(result).toBeDefined();
    expect(result.id).toBeDefined();
    expect(result.name).toBeDefined();
    expect(result.email).toBeDefined();
  });

  it('should reject invalid API key', async () => {
    const bundle = {
      authData: {
        api_key: 'invalid_key',
      },
    };

    await expect(
      appTester(App.authentication.test, bundle)
    ).rejects.toThrow();
  });

  it('should format connection label correctly', async () => {
    const bundle = {
      authData: {
        api_key: process.env.TEST_API_KEY || 'test_key_123',
      },
    };

    const result = await appTester(
      App.authentication.test,
      bundle
    );

    const label = App.authentication.connectionLabel
      .replace('{{name}}', result.name)
      .replace('{{email}}', result.email);

    expect(label).toContain(result.name);
    expect(label).toContain(result.email);
  });
});
