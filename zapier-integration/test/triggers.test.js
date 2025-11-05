/**
 * Trigger Tests
 */

const zapier = require('zapier-platform-core');
const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Triggers', () => {
  const bundle = {
    authData: {
      api_key: process.env.TEST_API_KEY || 'test_key_123',
    },
    inputData: {
      organization_id: process.env.TEST_ORG_ID || 'org_123',
    },
  };

  describe('Time Entry Created', () => {
    it('should subscribe to webhook', async () => {
      const subscribeBundle = {
        ...bundle,
        targetUrl: 'https://hooks.zapier.com/test/123',
      };

      const result = await appTester(
        App.triggers.time_entry_created.operation.performSubscribe,
        subscribeBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
      expect(result.url).toBe(subscribeBundle.targetUrl);
    });

    it('should fetch time entries', async () => {
      const result = await appTester(
        App.triggers.time_entry_created.operation.perform,
        bundle
      );

      expect(Array.isArray(result)).toBe(true);
      if (result.length > 0) {
        expect(result[0]).toHaveProperty('id');
        expect(result[0]).toHaveProperty('description');
        expect(result[0]).toHaveProperty('start');
      }
    });
  });

  describe('Invoice Sent', () => {
    it('should subscribe to webhook', async () => {
      const subscribeBundle = {
        ...bundle,
        targetUrl: 'https://hooks.zapier.com/test/456',
      };

      const result = await appTester(
        App.triggers.invoice_sent.operation.performSubscribe,
        subscribeBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
    });

    it('should fetch invoices', async () => {
      const result = await appTester(
        App.triggers.invoice_sent.operation.perform,
        bundle
      );

      expect(Array.isArray(result)).toBe(true);
    });
  });

  describe('Payment Received', () => {
    it('should subscribe to webhook', async () => {
      const subscribeBundle = {
        ...bundle,
        targetUrl: 'https://hooks.zapier.com/test/789',
      };

      const result = await appTester(
        App.triggers.payment_received.operation.performSubscribe,
        subscribeBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
    });
  });

  describe('Project Archived', () => {
    it('should subscribe to webhook', async () => {
      const subscribeBundle = {
        ...bundle,
        targetUrl: 'https://hooks.zapier.com/test/abc',
      };

      const result = await appTester(
        App.triggers.project_archived.operation.performSubscribe,
        subscribeBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
    });
  });
});
