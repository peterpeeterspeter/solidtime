# Solidtime Zapier Integration Guide

Connect Solidtime with 5,000+ apps to automate your time tracking and invoicing workflows.

## Table of Contents

- [Getting Started](#getting-started)
- [Available Triggers](#available-triggers)
- [Available Actions](#available-actions)
- [Common Use Cases](#common-use-cases)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

## Getting Started

### Step 1: Get Your API Key

1. Log in to Solidtime at https://app.solidtime.io
2. Navigate to **Settings** → **API Tokens**
3. Click **Create New Token**
4. Give it a name (e.g., "Zapier Integration")
5. Copy the API key (you won't be able to see it again!)

### Step 2: Connect Solidtime to Zapier

1. Go to https://zapier.com
2. Create a new Zap
3. Search for "Solidtime" as your trigger or action
4. Click **Connect an Account**
5. Paste your API key when prompted
6. Click **Continue**

Your Solidtime account is now connected! 🎉

## Available Triggers

Triggers are events in Solidtime that start your Zap.

### 1. New Time Entry

**Triggers when**: A new time entry is created in Solidtime

**Use it to**:
- Post to Slack when you start working
- Create tasks in your project management tool
- Log time entries to a Google Sheet
- Send notifications to your team

**Output Fields**:
- Time Entry ID
- Description
- Start Time
- End Time
- Duration (seconds)
- Billable (true/false)
- User ID
- Project ID
- Project Name
- Tags

**Example Zap**:
```
Trigger: New Time Entry in Solidtime
Action: Send a Slack message
Message: "{{Description}} started at {{Start Time}}"
```

### 2. Invoice Sent

**Triggers when**: An invoice is marked as sent in Solidtime

**Use it to**:
- Send invoice PDFs to clients via email
- Post invoice notifications to Slack
- Log invoices in accounting software
- Create reminders for payment follow-up

**Output Fields**:
- Invoice ID
- Invoice Number
- Client ID
- Client Name
- Issue Date
- Due Date
- Status
- Subtotal
- Tax
- Total Amount
- Currency
- Sent At
- PDF URL

**Example Zap**:
```
Trigger: Invoice Sent in Solidtime
Action: Send Gmail
To: {{Client Email}}
Subject: Invoice {{Invoice Number}}
Body: Please find attached invoice {{Invoice Number}} for {{Total Amount}} {{Currency}}
```

### 3. Payment Received

**Triggers when**: A payment is received for an invoice

**Use it to**:
- Celebrate payments with team notifications
- Update accounting records
- Send thank-you emails to clients
- Track revenue in a spreadsheet

**Output Fields**:
- Payment ID
- Invoice ID
- Invoice Number
- Amount
- Currency
- Payment Method
- Payment Date
- Transaction ID
- Status
- Client Name
- Notes

**Example Zap**:
```
Trigger: Payment Received in Solidtime
Action: Create Google Sheets row
Spreadsheet: Revenue Tracker
Row data: {{Payment Date}}, {{Client Name}}, {{Amount}}, {{Currency}}
```

### 4. Project Archived

**Triggers when**: A project is archived in Solidtime

**Use it to**:
- Archive related files in Google Drive
- Close corresponding projects in other tools
- Send project completion reports
- Update project status in databases

**Output Fields**:
- Project ID
- Project Name
- Client ID
- Client Name
- Color
- Billable Rate
- Is Billable
- Is Archived
- Archived At
- Created At
- Total Hours
- Total Earnings

**Example Zap**:
```
Trigger: Project Archived in Solidtime
Action: Create Trello card
List: Completed Projects
Title: {{Project Name}} - {{Total Hours}} hours
Description: Total earnings: ${{Total Earnings}}
```

## Available Actions

Actions are things you can do in Solidtime from other apps.

### 1. Create Time Entry

**Use it to**:
- Log time entries from other apps
- Automatically track time for specific events
- Sync time from other time tracking tools

**Required Fields**:
- Organization ID
- Description
- Start Time

**Optional Fields**:
- End Time (leave empty for ongoing timer)
- Project ID
- Task ID
- Billable (true/false)
- Tags (comma-separated)

**Example Zap**:
```
Trigger: New Asana task assigned to me
Action: Create Time Entry in Solidtime
Description: {{Task Name}}
Start Time: (current time)
Project ID: {{Asana Project ID}}
Billable: true
```

### 2. Create Invoice

**Use it to**:
- Generate invoices from other systems
- Automate invoice creation on specific triggers
- Sync invoices from other platforms

**Required Fields**:
- Organization ID
- Client ID
- Issue Date
- Due Date

**Optional Fields**:
- Currency (default: USD)
- Line Items (JSON format)
- Notes

**Example Zap**:
```
Trigger: New project completed in Basecamp
Action: Create Invoice in Solidtime
Client ID: {{Client ID}}
Issue Date: (today)
Due Date: (30 days from now)
Notes: Invoice for {{Project Name}}
```

### 3. Start Timer

**Use it to**:
- Start timer from calendar events
- Auto-start timer for meetings
- Begin tracking time from other triggers

**Required Fields**:
- Organization ID
- Description

**Optional Fields**:
- Start Time (default: now)
- Project ID
- Task ID
- Billable (true/false)
- Tags

**Example Zap**:
```
Trigger: Google Calendar event starts
Filter: Only if event title contains "Client:"
Action: Start Timer in Solidtime
Description: {{Event Title}}
Start Time: {{Event Start Time}}
Billable: true
```

### 4. Stop Timer

**Use it to**:
- Stop timer from calendar events
- Auto-stop timer after meetings
- End time tracking based on triggers

**Required Fields**:
- Organization ID

**Optional Fields**:
- End Time (default: now)

**Example Zap**:
```
Trigger: Google Calendar event ends
Filter: Only if event title contains "Client:"
Action: Stop Timer in Solidtime
End Time: {{Event End Time}}
```

## Common Use Cases

### 1. Slack Notifications for Time Tracking

**Goal**: Post to Slack when team members start/stop work

**Setup**:
```
Trigger: New Time Entry in Solidtime
Filter: Only if Description contains specific keywords
Action: Send Slack Channel Message
Channel: #time-tracking
Message: "{{User Name}} started: {{Description}}"
```

### 2. Auto-Track Client Meetings

**Goal**: Automatically start/stop timer for client meetings

**Zap 1 (Start)**:
```
Trigger: Google Calendar event starts
Filter: Only if event contains "Client:"
Action: Start Timer in Solidtime
Description: {{Event Title}}
Billable: true
```

**Zap 2 (Stop)**:
```
Trigger: Google Calendar event ends
Filter: Only if event contains "Client:"
Action: Stop Timer in Solidtime
```

### 3. Invoice Notification to Clients

**Goal**: Automatically email clients when invoices are sent

**Setup**:
```
Trigger: Invoice Sent in Solidtime
Action: Gmail - Send Email
To: {{Client Email}}
Subject: Invoice {{Invoice Number}} from Your Company
Body: |
  Hi {{Client Name}},

  Your invoice {{Invoice Number}} is ready for {{Total Amount}} {{Currency}}.

  Due date: {{Due Date}}

  View invoice: {{PDF URL}}

  Thank you!
```

### 4. Payment Celebration

**Goal**: Celebrate when payments come in

**Setup**:
```
Trigger: Payment Received in Solidtime
Action: Send Slack Channel Message
Channel: #celebrations
Message: "🎉 Payment received! {{Client Name}} paid {{Amount}} {{Currency}}"
```

### 5. Sync Time to Google Sheets

**Goal**: Keep a log of all time entries in a spreadsheet

**Setup**:
```
Trigger: New Time Entry in Solidtime
Action: Create Google Sheets Row
Spreadsheet: Time Log 2025
Worksheet: All Entries
Row values:
  - {{Start Time}}
  - {{End Time}}
  - {{Description}}
  - {{Project Name}}
  - {{Duration Hours}}
  - {{Billable}}
```

### 6. Project Completion Report

**Goal**: Send summary when a project is archived

**Setup**:
```
Trigger: Project Archived in Solidtime
Action: Gmail - Send Email
To: manager@company.com
Subject: Project Completed: {{Project Name}}
Body: |
  Project: {{Project Name}}
  Client: {{Client Name}}
  Total Hours: {{Total Hours}}
  Total Earnings: ${{Total Earnings}}
  Duration: {{Created At}} to {{Archived At}}
```

### 7. Create Tasks from Time Entries

**Goal**: Auto-create follow-up tasks based on time entries

**Setup**:
```
Trigger: New Time Entry in Solidtime
Filter: Only if Tags contains "follow-up"
Action: Create Asana Task
Project: Follow-ups
Name: Follow up: {{Description}}
Notes: Worked on {{Start Time}} for {{Duration Hours}} hours
```

### 8. Auto-Invoice on Project Archive

**Goal**: Automatically generate invoice when project completes

**Setup**:
```
Trigger: Project Archived in Solidtime
Filter: Only if Is Billable is true
Action: Create Invoice in Solidtime
Client ID: {{Client ID}}
Issue Date: (today)
Due Date: (30 days from now)
Notes: Final invoice for {{Project Name}} - {{Total Hours}} hours
```

## Troubleshooting

### Invalid API Key Error

**Problem**: "Invalid API key. Please check your credentials."

**Solutions**:
1. Verify your API key is correct
2. Check that the key hasn't been revoked in Settings → API Tokens
3. Generate a new API key and reconnect
4. Make sure there are no extra spaces when pasting the key

### No Active Timer Error

**Problem**: "No active timer found. Start a timer first." (when using Stop Timer)

**Solutions**:
1. Check that a timer is actually running in Solidtime
2. Make sure the timer belongs to the authenticated user
3. Verify the organization ID is correct

### Permission Denied Error

**Problem**: "Permission denied" or HTTP 403 errors

**Solutions**:
1. Check your user role has the required permissions
2. Verify you're a member of the organization
3. Contact your organization admin to adjust permissions

### Webhook Not Firing

**Problem**: Trigger doesn't run when expected

**Solutions**:
1. Check that the Zap is turned ON
2. Verify the webhook is still active in Settings → Webhooks
3. Test the trigger manually in Zapier
4. Check Zapier's Task History for errors
5. Reconnect your Solidtime account

### Rate Limit Exceeded

**Problem**: "HTTP 429: Rate limit exceeded"

**Solutions**:
1. Space out your Zap runs (add delays)
2. Upgrade your Solidtime plan for higher limits
3. Contact support if you need enterprise limits

### Missing Organization ID

**Problem**: "Organization ID is required"

**Solutions**:
1. Find your organization ID:
   - Go to Solidtime dashboard
   - Look in the URL: app.solidtime.io/organizations/**[ORG_ID]**/...
2. Copy and paste it into the Organization ID field

## FAQ

### Q: Is the integration free?

**A**: Yes! The Zapier integration is free to use. You'll need:
- A Zapier account (Free tier works, but has limited tasks)
- A Solidtime account (API access available on all plans)

### Q: How real-time are the triggers?

**A**: Very! Triggers use webhooks, so they fire within seconds of the event happening in Solidtime.

### Q: Can I use multiple Solidtime accounts?

**A**: Yes! You can connect multiple Solidtime accounts to Zapier. When creating a Zap, you can choose which account to use.

### Q: What's the difference between Create Time Entry and Start Timer?

**A**:
- **Create Time Entry**: Creates a complete time entry with start and end times
- **Start Timer**: Creates a running timer (no end time) that you can stop later

### Q: Can I create invoices with line items?

**A**: Yes! Pass line items as a JSON array in the "Line Items (JSON)" field:
```json
[
  {"description": "Web Design", "quantity": 10, "unit_price": 150},
  {"description": "Development", "quantity": 20, "unit_price": 100}
]
```

### Q: How do I find my Organization ID?

**A**:
1. Log in to Solidtime
2. Look at the URL in your browser
3. It will be: `app.solidtime.io/organizations/YOUR_ORG_ID/...`
4. Copy the UUID between `/organizations/` and the next `/`

### Q: Can I filter triggers?

**A**: Yes! Zapier has built-in filters. For example:
- Only trigger if Description contains "urgent"
- Only trigger if Amount is greater than 1000
- Only trigger if Billable is true

### Q: What happens if my API key is compromised?

**A**:
1. Immediately revoke the key in Settings → API Tokens
2. Generate a new key
3. Update it in Zapier (reconnect your account)

### Q: Can I use this with Zapier's multi-step Zaps?

**A**: Absolutely! Solidtime triggers and actions work with all Zapier features:
- Multi-step Zaps
- Paths (conditional logic)
- Filters
- Formatters
- Delays
- Webhooks

### Q: Does this work with Zapier's free plan?

**A**: Yes, but Zapier's free plan has limitations:
- 100 tasks/month
- 5 Zaps maximum
- Single-step Zaps only

For heavy automation, consider Zapier's paid plans.

## Support

Need help? We're here for you!

- 📖 **Documentation**: https://docs.solidtime.io/zapier
- 💬 **Community**: https://community.solidtime.io
- 📧 **Email**: support@solidtime.io
- 🐛 **Bug Reports**: https://github.com/solidtime/solidtime/issues

## What's Next?

Explore these resources:
- [Solidtime API Documentation](https://docs.solidtime.io/api)
- [Webhook Guide](https://docs.solidtime.io/webhooks)
- [Zapier Community Apps](https://zapier.com/apps)
- [Automation Examples](https://zapier.com/learn/zapier-quick-start-guide/)

Happy automating! 🚀
