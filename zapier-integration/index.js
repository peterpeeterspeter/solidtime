/**
 * Solidtime Zapier Integration
 *
 * This is the main entry point for the Solidtime Zapier app.
 * It registers all triggers, actions, and authentication methods.
 *
 * @version 1.0.0
 */

const authentication = require('./authentication');

// Triggers
const timeEntryCreated = require('./triggers/time_entry_created');
const invoiceSent = require('./triggers/invoice_sent');
const paymentReceived = require('./triggers/payment_received');
const projectArchived = require('./triggers/project_archived');

// Actions (Creates)
const createTimeEntry = require('./creates/create_time_entry');
const createInvoice = require('./creates/create_invoice');
const startTimer = require('./creates/start_timer');
const stopTimer = require('./creates/stop_timer');

// Before request middleware - add base URL if not set
const addBaseUrl = (request, z, bundle) => {
  if (!process.env.BASE_URL) {
    process.env.BASE_URL = 'https://api.solidtime.io';
  }
  return request;
};

// After response middleware - handle errors
const handleHttpError = (response, z, bundle) => {
  if (response.status >= 400) {
    const errorMessage = response.data?.error || response.data?.message || 'An error occurred';
    throw new Error(`HTTP ${response.status}: ${errorMessage}`);
  }
  return response;
};

module.exports = {
  version: require('./package.json').version,
  platformVersion: require('zapier-platform-core').version,

  // Authentication
  authentication: authentication,

  // Middleware
  beforeRequest: [addBaseUrl],
  afterResponse: [handleHttpError],

  // Triggers - when something happens in Solidtime
  triggers: {
    [timeEntryCreated.key]: timeEntryCreated,
    [invoiceSent.key]: invoiceSent,
    [paymentReceived.key]: paymentReceived,
    [projectArchived.key]: projectArchived,
  },

  // Actions - do something in Solidtime
  creates: {
    [createTimeEntry.key]: createTimeEntry,
    [createInvoice.key]: createInvoice,
    [startTimer.key]: startTimer,
    [stopTimer.key]: stopTimer,
  },

  // Searches and dynamic dropdowns would go here
  searches: {},
};
