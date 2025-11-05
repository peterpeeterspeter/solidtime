# n8n Solidtime Nodes - Implementation Guide

This document provides detailed technical information about the Solidtime n8n nodes implementation.

## Architecture Overview

The package consists of three main components:

1. **Credentials** (`SolidtimeApi.credentials.ts`)
2. **Trigger Node** (`SolidtimeTrigger.node.ts`)
3. **Action Node** (`Solidtime.node.ts`)

## File Structure

```
n8n-nodes-solidtime/
├── credentials/
│   └── SolidtimeApi.credentials.ts       # API authentication
├── nodes/
│   ├── Solidtime/
│   │   ├── Solidtime.node.ts             # Action node
│   │   └── solidtime.svg                 # Node icon
│   └── SolidtimeTrigger/
│       ├── SolidtimeTrigger.node.ts      # Trigger node
│       └── solidtime.svg                 # Node icon
├── workflow-templates/                    # Pre-built workflows
│   ├── 1-auto-create-time-entries-from-calendar.json
│   ├── 2-slack-notification-on-long-time-entry.json
│   ├── 3-auto-create-invoices-in-stripe.json
│   ├── 4-sync-tasks-with-github-issues.json
│   └── 5-daily-summary-email-report.json
├── package.json                           # Package configuration
├── tsconfig.json                          # TypeScript configuration
├── README.md                              # User documentation
└── IMPLEMENTATION.md                      # This file

```

## Credentials Implementation

The `SolidtimeApi.credentials.ts` file implements API key authentication:

### Key Features

- **Bearer Token Authentication**: Uses `Authorization: Bearer sk_...` header
- **Configurable Base URL**: Supports both hosted and self-hosted instances
- **Credential Testing**: Validates API key by calling `/api/v1/api-keys/scopes`

### Implementation Details

```typescript
authenticate: IAuthenticateGeneric = {
  type: 'generic',
  properties: {
    headers: {
      Authorization: '=Bearer {{$credentials.apiKey}}',
    },
  },
};
```

This allows the credential to be reused by other n8n nodes (e.g., HTTP Request node).

## Trigger Node Implementation

The `SolidtimeTrigger.node.ts` implements webhook-based event triggers.

### Webhook Lifecycle

1. **Creation** (`create` method):
   - Registers webhook with Solidtime API
   - Stores webhook ID and secret in workflow static data
   - Passes n8n webhook URL as the endpoint

2. **Verification** (`checkExists` method):
   - Checks if webhook still exists in Solidtime
   - Returns `false` if webhook was deleted externally

3. **Deletion** (`delete` method):
   - Removes webhook from Solidtime API
   - Cleans up static data

4. **Event Handling** (`webhook` method):
   - Receives webhook payload from Solidtime
   - Verifies HMAC signature (if enabled)
   - Returns data to workflow

### Signature Verification

```typescript
const signature = req.headers['x-solidtime-signature'] as string;
const payload = JSON.stringify(bodyData);
const expectedSignature = createHmac('sha256', webhookSecret)
  .update(payload)
  .digest('hex');

if (signature !== expectedSignature) {
  return { workflowData: [[]] }; // Reject invalid signature
}
```

### Event Types

Supports 21 event types across 8 categories:
- Time Entry (5 events)
- Focus Session (2 events)
- Project (3 events)
- Task (4 events)
- Member (2 events)
- Report (2 events)
- Invoice (3 events)

## Action Node Implementation

The `Solidtime.node.ts` implements CRUD operations for Solidtime resources.

### Resource Structure

```
Solidtime Node
├── Time Entry
│   ├── Create
│   ├── Start (convenience method)
│   ├── Stop (convenience method)
│   ├── Get
│   ├── Get All
│   ├── Update
│   └── Delete
├── Project
│   ├── Create
│   ├── Get
│   ├── Get All
│   ├── Update
│   └── Delete
├── Task
│   ├── Create
│   ├── Get
│   ├── Get All
│   ├── Update
│   └── Delete
└── Member
    ├── Get All
    └── Invite
```

### Dynamic Fields

Fields are shown/hidden based on selected resource and operation using `displayOptions`:

```typescript
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
  // ...
}
```

### API Request Pattern

All operations follow a consistent pattern:

```typescript
const responseData = await this.helpers.httpRequest({
  method: 'POST',
  url: `${baseUrl}/api/v1/resource`,
  headers: {
    Authorization: `Bearer ${credentials.apiKey}`,
    'Content-Type': 'application/json',
  },
  body: requestBody,
  json: true,
});
```

### Special Operations

**Start Time Entry** - Uses current timestamp:
```typescript
body.start = new Date().toISOString();
```

**Stop Time Entry** - Finds active entry first:
```typescript
const runningEntries = await this.helpers.httpRequest({
  url: `${baseUrl}/api/v1/time-entries?organization_id=${organizationId}&active=true`,
  // ...
});
const entryId = runningEntries.data[0].id;
```

## Workflow Templates

### Template Structure

Each template is a standard n8n workflow JSON with:
- Nodes array (trigger, actions, transformations)
- Connections mapping
- Placeholder values (e.g., `YOUR_ORG_ID`, `YOUR_EMAIL`)
- Metadata and tags

### Template Categories

1. **Calendar Sync** - Two-way sync with Google Calendar
2. **Alerting** - Slack/email notifications on events
3. **Invoicing** - Automated billing with Stripe
4. **Task Management** - GitHub/project management integration
5. **Reporting** - Daily/weekly summary reports

### Template Best Practices

- Use `continueOnFail` for robust error handling
- Include human-readable names for all nodes
- Add comments explaining complex logic
- Use expressions for dynamic values (e.g., `={{ $json.field }}`)

## Development Workflow

### Local Testing

1. Build the package:
```bash
npm run build
```

2. Link to n8n:
```bash
npm link
cd ~/.n8n/nodes
npm link n8n-nodes-solidtime
```

3. Restart n8n:
```bash
n8n start
```

### Testing Checklist

- [ ] Credentials validate successfully
- [ ] All resource operations work
- [ ] Trigger creates/deletes webhooks
- [ ] Signature verification works
- [ ] Error handling is graceful
- [ ] Fields show/hide correctly
- [ ] Templates import successfully

## API Endpoints Used

### Authentication
- `GET /api/v1/api-keys/scopes` - Test credentials

### Webhooks
- `POST /api/v1/webhooks` - Create webhook
- `GET /api/v1/webhooks/{id}` - Check webhook exists
- `DELETE /api/v1/webhooks/{id}` - Delete webhook

### Time Entries
- `POST /api/v1/time-entries` - Create/start entry
- `GET /api/v1/time-entries` - List entries
- `GET /api/v1/time-entries/{id}` - Get single entry
- `PUT /api/v1/time-entries/{id}` - Update/stop entry
- `DELETE /api/v1/time-entries/{id}` - Delete entry

### Projects
- `POST /api/v1/projects` - Create project
- `GET /api/v1/projects` - List projects
- `GET /api/v1/projects/{id}` - Get single project
- `PUT /api/v1/projects/{id}` - Update project
- `DELETE /api/v1/projects/{id}` - Delete project

### Tasks
- `POST /api/v1/tasks` - Create task
- `GET /api/v1/tasks` - List tasks
- `GET /api/v1/tasks/{id}` - Get single task
- `PUT /api/v1/tasks/{id}` - Update task
- `DELETE /api/v1/tasks/{id}` - Delete task

### Members
- `GET /api/v1/members` - List members
- `POST /api/v1/members/invite` - Invite member

## Error Handling

### Credential Errors
```typescript
if (!credentials) {
  throw new NodeOperationError(this.getNode(), 'No credentials found');
}
```

### API Errors
```typescript
try {
  const response = await this.helpers.httpRequest(options);
  return response;
} catch (error) {
  throw new NodeApiError(this.getNode(), error as any);
}
```

### Continue on Fail
```typescript
if (this.continueOnFail()) {
  returnData.push({ error: (error as Error).message });
  continue;
}
```

## Security Considerations

### API Key Storage
- Stored as password type (masked in UI)
- Never logged or exposed
- Transmitted only via HTTPS

### Webhook Signature
- HMAC-SHA256 verification
- Secret stored in workflow static data (encrypted)
- Optional disable for testing

### Data Privacy
- No data stored locally by nodes
- All requests go directly to Solidtime API
- Credentials managed by n8n's secure credential system

## Performance Optimization

### Batch Operations
For "Get All" operations, consider pagination:
```typescript
// Future enhancement
const limit = this.getNodeParameter('limit', i, 100);
url += `&limit=${limit}`;
```

### Caching
Static data (like project lists) could be cached:
```typescript
// Future enhancement
const cacheKey = `projects_${organizationId}`;
const cached = this.getWorkflowStaticData('node')[cacheKey];
```

## Internationalization

Future enhancement: Support for multiple languages in node descriptions.

```typescript
// Example
displayName: this.getNodeParameter('__i18n', i, 'Time Entry'),
```

## Publishing to npm

1. Update version in `package.json`
2. Build the package:
```bash
npm run build
```
3. Publish:
```bash
npm publish
```

## Community Guidelines

When contributing:
- Follow existing code style
- Add tests for new operations
- Update README with examples
- Increment version following semver

## Support & Resources

- **Documentation**: https://docs.solidtime.io/n8n
- **API Reference**: https://docs.solidtime.io/api
- **GitHub**: https://github.com/solidtime-io/solidtime
- **Discord**: https://discord.gg/solidtime

---

**Version**: 1.0.0
**Last Updated**: 2025-11-05
**Maintainer**: Solidtime Team
