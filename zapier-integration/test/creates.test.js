/**
 * Action (Creates) Tests
 */

const zapier = require('zapier-platform-core');
const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('Actions (Creates)', () => {
  const bundle = {
    authData: {
      api_key: process.env.TEST_API_KEY || 'test_key_123',
    },
    inputData: {
      organization_id: process.env.TEST_ORG_ID || 'org_123',
    },
  };

  describe('Create Time Entry', () => {
    it('should create a time entry', async () => {
      const createBundle = {
        ...bundle,
        inputData: {
          ...bundle.inputData,
          description: 'Test time entry from Zapier',
          start: new Date().toISOString(),
          end: new Date(Date.now() + 3600000).toISOString(), // 1 hour later
          billable: true,
        },
      };

      const result = await appTester(
        App.creates.create_time_entry.operation.perform,
        createBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
      expect(result.description).toBe(createBundle.inputData.description);
    });
  });

  describe('Create Invoice', () => {
    it('should create an invoice', async () => {
      const createBundle = {
        ...bundle,
        inputData: {
          ...bundle.inputData,
          client_id: process.env.TEST_CLIENT_ID || 'client_123',
          issue_date: new Date().toISOString().split('T')[0],
          due_date: new Date(Date.now() + 30 * 24 * 3600000).toISOString().split('T')[0],
          currency: 'USD',
          notes: 'Test invoice from Zapier',
        },
      };

      const result = await appTester(
        App.creates.create_invoice.operation.perform,
        createBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
    });
  });

  describe('Start Timer', () => {
    it('should start a timer', async () => {
      const startBundle = {
        ...bundle,
        inputData: {
          ...bundle.inputData,
          description: 'Timer started from Zapier test',
          billable: true,
        },
      };

      const result = await appTester(
        App.creates.start_timer.operation.perform,
        startBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
      expect(result.description).toBe(startBundle.inputData.description);
      expect(result.end).toBeNull(); // Timer should be running
    });
  });

  describe('Stop Timer', () => {
    it('should stop a running timer', async () => {
      // First, start a timer
      const startBundle = {
        ...bundle,
        inputData: {
          ...bundle.inputData,
          description: 'Timer to stop from Zapier test',
        },
      };

      await appTester(
        App.creates.start_timer.operation.perform,
        startBundle
      );

      // Then stop it
      const stopBundle = {
        ...bundle,
        inputData: {
          ...bundle.inputData,
        },
      };

      const result = await appTester(
        App.creates.stop_timer.operation.perform,
        stopBundle
      );

      expect(result).toBeDefined();
      expect(result.id).toBeDefined();
      expect(result.end).not.toBeNull(); // Timer should be stopped
      expect(result.duration_seconds).toBeGreaterThan(0);
    });
  });
});
