# Solidtime Zapier Integration

Official Zapier integration for Solidtime - Connect your time tracking with 5,000+ apps.

## Overview

This Zapier integration allows Solidtime users to automate their workflows by connecting with popular apps like:
- **Project Management**: Asana, Trello, Monday.com, Jira
- **Communication**: Slack, Microsoft Teams, Discord
- **CRM**: Salesforce, HubSpot, Pipedrive
- **Accounting**: QuickBooks, Xero, FreshBooks
- **And 5,000+ more apps**

## Features

### Triggers (When this happens...)
- **New Time Entry** - Triggers when a new time entry is created
- **Invoice Sent** - Triggers when an invoice is marked as sent
- **Payment Received** - Triggers when a payment is received
- **Project Archived** - Triggers when a project is archived

### Actions (Do this...)
- **Create Time Entry** - Creates a new time entry
- **Create Invoice** - Creates a new invoice
- **Start Timer** - Starts a new running timer
- **Stop Timer** - Stops the currently running timer

## Installation

### Prerequisites
- Node.js >= 18.0.0
- npm >= 9.0.0
- Zapier CLI

### Setup

1. Install dependencies:
```bash
npm install
```

2. Set environment variables:
```bash
export BASE_URL=https://api.solidtime.io
export TEST_API_KEY=your_test_api_key
export TEST_ORG_ID=your_test_org_id
```

3. Run tests:
```bash
npm test
```

4. Validate the app:
```bash
npm run validate
```

## Development

### Project Structure
```
zapier-integration/
├── index.js                    # Main entry point
├── authentication.js           # Authentication configuration
├── package.json               # Dependencies and scripts
├── triggers/                  # Webhook triggers
│   ├── time_entry_created.js
│   ├── invoice_sent.js
│   ├── payment_received.js
│   └── project_archived.js
├── creates/                   # Actions
│   ├── create_time_entry.js
│   ├── create_invoice.js
│   ├── start_timer.js
│   └── stop_timer.js
└── test/                      # Test files
    ├── authentication.test.js
    ├── triggers.test.js
    └── creates.test.js
```

### Adding New Triggers

1. Create a new file in `triggers/`:
```javascript
module.exports = {
  key: 'my_trigger',
  noun: 'My Object',
  display: {
    label: 'My Trigger',
    description: 'Triggers when...',
  },
  operation: {
    type: 'hook',
    performSubscribe: subscribeHook,
    performUnsubscribe: unsubscribeHook,
    perform: getFallbackData,
    sample: { /* sample data */ },
  },
};
```

2. Register it in `index.js`:
```javascript
const myTrigger = require('./triggers/my_trigger');

module.exports = {
  // ...
  triggers: {
    [myTrigger.key]: myTrigger,
    // ...
  },
};
```

### Adding New Actions

1. Create a new file in `creates/`:
```javascript
module.exports = {
  key: 'my_action',
  noun: 'My Object',
  display: {
    label: 'My Action',
    description: 'Creates a...',
  },
  operation: {
    perform: performAction,
    inputFields: [ /* input fields */ ],
    sample: { /* sample output */ },
  },
};
```

2. Register it in `index.js`:
```javascript
const myAction = require('./creates/my_action');

module.exports = {
  // ...
  creates: {
    [myAction.key]: myAction,
    // ...
  },
};
```

## Testing

### Unit Tests
```bash
npm test
```

### Integration Tests
```bash
# Set real API credentials
export TEST_API_KEY=your_real_api_key
export TEST_ORG_ID=your_org_id
export TEST_CLIENT_ID=your_client_id

npm test
```

### Manual Testing
```bash
# Test authentication
zapier test --auth

# Test a specific trigger
zapier test --trigger time_entry_created

# Test a specific action
zapier test --create create_time_entry
```

## Deployment

### Push to Zapier
```bash
# Push the latest version
npm run push

# View deployment
zapier versions
```

### Promote to Production
```bash
# Promote version to production
npm run promote 1.0.0

# Make it public
zapier promote 1.0.0 --public
```

## Authentication

The integration uses API key authentication. Users can generate API keys from:
**Solidtime Dashboard → Settings → API Tokens**

The API key is passed as a Bearer token in the Authorization header:
```
Authorization: Bearer YOUR_API_KEY
```

## API Endpoints

All requests are made to: `https://api.solidtime.io/api/v1/`

### Base URL Override
For testing with a local or staging environment:
```bash
export BASE_URL=http://localhost:8000
```

## Webhook Subscriptions

Triggers use webhook subscriptions for real-time updates:

1. **Subscribe**: Creates a webhook when user enables the trigger
2. **Receive**: Receives webhook payloads at Zapier's target URL
3. **Unsubscribe**: Deletes the webhook when user disables the trigger

## Error Handling

The integration includes comprehensive error handling:
- **HTTP 401**: Invalid API key
- **HTTP 403**: Permission denied
- **HTTP 404**: Resource not found
- **HTTP 429**: Rate limit exceeded
- **HTTP 5xx**: Server error

All errors include descriptive messages to help users troubleshoot.

## Rate Limiting

The Solidtime API has the following rate limits:
- **Free tier**: 100 requests/minute
- **Pro tier**: 500 requests/minute
- **Enterprise tier**: 2,000 requests/minute

The integration respects these limits and will retry with exponential backoff.

## Support

- **Documentation**: https://docs.solidtime.io/zapier
- **API Reference**: https://docs.solidtime.io/api
- **Issues**: https://github.com/solidtime/zapier-integration/issues

## License

MIT License - see LICENSE file for details
