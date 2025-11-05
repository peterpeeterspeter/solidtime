# n8n-nodes-solidtime

[![npm version](https://badge.fury.io/js/n8n-nodes-solidtime.svg)](https://www.npmjs.com/package/n8n-nodes-solidtime)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

This is an n8n community node package for [Solidtime](https://solidtime.io) - the privacy-first, open-source time tracking solution.

[Solidtime](https://solidtime.io) is a modern time tracking application that helps you track work hours, manage projects, and analyze productivity - all while keeping your data secure and private. With these n8n nodes, you can build powerful automations connecting Solidtime to 400+ apps in the n8n ecosystem.

## Table of Contents

- [Installation](#installation)
- [Credentials](#credentials)
- [Nodes](#nodes)
  - [Solidtime](#solidtime-node)
  - [Solidtime Trigger](#solidtime-trigger-node)
- [Operations](#operations)
- [Workflow Templates](#workflow-templates)
- [Example Workflows](#example-workflows)
- [Compatibility](#compatibility)
- [Resources](#resources)
- [License](#license)

## Installation

### Community Nodes (Recommended)

Install directly in n8n:

1. Go to **Settings** > **Community Nodes**
2. Click **Install a community node**
3. Enter `n8n-nodes-solidtime`
4. Click **Install**

### Manual Installation

For self-hosted n8n instances:

```bash
cd ~/.n8n/nodes
npm install n8n-nodes-solidtime
```

Restart n8n to load the nodes.

## Credentials

Before using these nodes, you need to set up Solidtime API credentials:

### Creating API Credentials

1. Log in to your Solidtime account at [app.solidtime.io](https://app.solidtime.io)
2. Navigate to **Settings** > **Automation** > **API Keys**
3. Click **Create API Key**
4. Give it a name (e.g., "n8n Integration")
5. Select the appropriate scopes (permissions):
   - `time_entries:read` - Read time entries
   - `time_entries:write` - Create/update time entries
   - `projects:read` - Read projects
   - `projects:write` - Create/update projects
   - `tasks:read` - Read tasks
   - `tasks:write` - Create/update tasks
   - `*` - Full access (all permissions)
6. **Copy the API key immediately** - it will only be shown once!
7. Store it securely

### Configuring in n8n

1. In n8n, create a new credential
2. Search for **Solidtime API**
3. Enter your details:
   - **API Key**: The key you copied (starts with `sk_`)
   - **Base URL**: `https://app.solidtime.io` (or your self-hosted URL)
4. Click **Test** to verify the connection
5. Click **Save**

## Nodes

### Solidtime Node

The main action node for interacting with Solidtime.

**Resources:**
- Time Entry
- Project
- Task
- Member

**Time Entry Operations:**
- **Create** - Create a new time entry
- **Start** - Start a new time entry (uses current time)
- **Stop** - Stop the currently running time entry
- **Get** - Get a specific time entry by ID
- **Get All** - List all time entries (with filters)
- **Update** - Update an existing time entry
- **Delete** - Delete a time entry

**Project Operations:**
- **Create** - Create a new project
- **Get** - Get a specific project by ID
- **Get All** - List all projects
- **Update** - Update an existing project
- **Delete** - Delete a project

**Task Operations:**
- **Create** - Create a new task
- **Get** - Get a specific task by ID
- **Get All** - List all tasks
- **Update** - Update an existing task
- **Delete** - Delete a task

**Member Operations:**
- **Get All** - List all organization members
- **Invite** - Invite a new member to the organization

### Solidtime Trigger Node

Webhook-based trigger that listens for events from Solidtime.

**Supported Events:**
- **Time Entry**
  - `time_entry.started` - When a time entry is started
  - `time_entry.stopped` - When a time entry is stopped
  - `time_entry.created` - When a time entry is created
  - `time_entry.updated` - When a time entry is updated
  - `time_entry.deleted` - When a time entry is deleted
- **Focus Session**
  - `focus_session.detected` - When a focus session is auto-detected
  - `focus_session.completed` - When a focus session is completed
- **Project**
  - `project.created` - When a project is created
  - `project.updated` - When a project is updated
  - `project.deleted` - When a project is deleted
- **Task**
  - `task.created` - When a task is created
  - `task.updated` - When a task is updated
  - `task.deleted` - When a task is deleted
  - `task.completed` - When a task is marked as completed
- **Team**
  - `member.added` - When a member joins the organization
  - `member.removed` - When a member leaves the organization
- **Reporting**
  - `report.generated` - When a report is generated
  - `timesheet.exported` - When a timesheet is exported
- **Invoicing**
  - `invoice.created` - When an invoice is created
  - `invoice.sent` - When an invoice is sent
  - `invoice.paid` - When an invoice is marked as paid

**Features:**
- Automatic webhook creation and deletion
- HMAC signature verification for security
- Multiple event subscriptions per trigger

## Operations

### Creating a Time Entry

```javascript
// Start a timer (current time)
Resource: Time Entry
Operation: Start
Organization ID: your-org-id
Description: Working on feature X
Project ID: project-uuid
Billable: Yes
```

### Getting Time Entries with Filters

```javascript
Resource: Time Entry
Operation: Get All
Organization ID: your-org-id
Filters:
  - Start Date: {{ $now.minus({days: 7}).toISO() }}
  - End Date: {{ $now.toISO() }}
  - Project ID: project-uuid
```

### Creating a Project

```javascript
Resource: Project
Operation: Create
Organization ID: your-org-id
Project Name: Client Website Redesign
Color: #3b82f6
Client Name: Acme Corp
Billable: Yes
```

## Workflow Templates

This package includes 5 pre-built workflow templates:

### 1. Auto-create Time Entries from Google Calendar
Automatically creates time entries in Solidtime based on Google Calendar events.

**Use Case:** Sync your calendar meetings as billable time.

### 2. Send Slack Alert on Long Time Entry
Sends a Slack notification when a time entry exceeds 8 hours.

**Use Case:** Catch potential errors or remind team members to take breaks.

### 3. Auto-create Stripe Invoices from Completed Projects
Generates Stripe invoices automatically when a project is marked as complete.

**Use Case:** Streamline billing for completed work.

### 4. Sync Tasks with GitHub Issues
Creates GitHub issues when tasks are created in Solidtime.

**Use Case:** Keep development tasks synchronized across tools.

### 5. Daily Summary Email Report
Sends a daily email with time tracking statistics.

**Use Case:** Daily accountability and progress tracking.

### Importing Templates

1. Download the template JSON from `workflow-templates/`
2. In n8n, click **Workflows** > **Import from File**
3. Select the downloaded JSON file
4. Configure your credentials
5. Update organization IDs and other parameters
6. Activate the workflow

## Example Workflows

### Example 1: Start Timer When GitHub PR is Opened

```
GitHub Trigger (PR Opened)
  ↓
Solidtime (Start Time Entry)
  - Description: "PR #{{ $json.number }}: {{ $json.title }}"
  - Project: Linked to GitHub repo
```

### Example 2: Stop Timer on Calendar Event End

```
Google Calendar Trigger (Event Ended)
  ↓
Solidtime (Stop Time Entry)
```

### Example 3: Create Notion Page for Weekly Report

```
Schedule Trigger (Every Friday 5 PM)
  ↓
Solidtime (Get All Time Entries - Last 7 Days)
  ↓
Aggregate Data
  ↓
Notion (Create Page with Summary)
```

### Example 4: Alert on Missing Time Entries

```
Schedule Trigger (Daily 6 PM)
  ↓
Solidtime (Get Time Entries for Today)
  ↓
IF (Total Hours < 6)
  ↓
  Send Email Alert
```

## Compatibility

- **n8n version**: 0.220.0 or higher
- **Node.js**: 18.x or higher
- **Solidtime API**: v1

## Resources

- [Solidtime Website](https://solidtime.io)
- [Solidtime Documentation](https://docs.solidtime.io)
- [Solidtime API Docs](https://docs.solidtime.io/api)
- [n8n Documentation](https://docs.n8n.io)
- [GitHub Repository](https://github.com/solidtime-io/solidtime)
- [Community Forum](https://community.solidtime.io)

## Support

- **Issues**: [GitHub Issues](https://github.com/solidtime-io/solidtime/issues)
- **Email**: hello@solidtime.io
- **Discord**: [Join our community](https://discord.gg/solidtime)

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## Development

```bash
# Clone the repository
git clone https://github.com/solidtime-io/solidtime.git
cd solidtime/n8n-nodes-solidtime

# Install dependencies
npm install

# Build
npm run build

# Link for local testing
npm link
cd ~/.n8n/nodes
npm link n8n-nodes-solidtime

# Restart n8n
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

[MIT License](LICENSE)

---

## Privacy & Security

Solidtime is built with privacy at its core:

✅ **EU Data Hosting** - All data stored in EU data centers
✅ **GDPR Compliant** - Full compliance with EU privacy regulations
✅ **End-to-End Encryption** - Data encrypted in transit and at rest
✅ **Open Source** - Full transparency with open-source code
✅ **Self-Hosting Option** - Host on your own infrastructure
✅ **No Data Selling** - Your data is never sold to third parties

When using these n8n nodes, data is transmitted securely via HTTPS with API key authentication. Webhook payloads are signed with HMAC-SHA256 for verification.

---

Made with ❤️ by the Solidtime team
