# Getting Started with Timeclocker API

This guide will walk you through building your first integration with the Timeclocker API in under 15 minutes.

## What We'll Build

A simple integration that:
1. Authenticates with the API
2. Fetches the current user's profile
3. Creates a time entry
4. Retrieves time entries for today
5. Calculates total hours worked

---

## Prerequisites

- A Timeclocker account (free tier works!)
- An organization with at least one project
- Basic knowledge of REST APIs and JSON
- A tool to make HTTP requests (curl, Postman, or your favorite programming language)

---

## Step 1: Get Your API Token

1. Log in to [Timeclocker](https://timeclocker.com)
2. Navigate to **Settings** > **API Tokens**
3. Click **"Create New Token"**
4. Give it a name (e.g., "My First Integration")
5. Copy the token immediately (it won't be shown again!)

**Example Token**:
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

Store this securely - never commit it to version control!

---

## Step 2: Test Your Authentication

Let's verify your token works by fetching your user profile.

### Using cURL

```bash
curl https://api.timeclocker.com/v1/users/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Using JavaScript (Node.js)

```javascript
const axios = require('axios');

const API_URL = 'https://api.timeclocker.com/v1';
const API_TOKEN = 'YOUR_TOKEN_HERE';

async function getMe() {
  const response = await axios.get(`${API_URL}/users/me`, {
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json'
    }
  });

  console.log('Logged in as:', response.data.data.name);
  return response.data.data;
}

getMe();
```

### Using Python

```python
import requests

API_URL = 'https://api.timeclocker.com/v1'
API_TOKEN = 'YOUR_TOKEN_HERE'

headers = {
    'Authorization': f'Bearer {API_TOKEN}',
    'Content-Type': 'application/json'
}

response = requests.get(f'{API_URL}/users/me', headers=headers)
user = response.json()['data']

print(f"Logged in as: {user['name']}")
```

### Using PHP

```php
<?php

$apiUrl = 'https://api.timeclocker.com/v1';
$apiToken = 'YOUR_TOKEN_HERE';

$headers = [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
];

$ch = curl_init($apiUrl . '/users/me');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$user = json_decode($response, true)['data'];

echo "Logged in as: " . $user['name'] . "\n";

curl_close($ch);
```

**Expected Response**:
```json
{
  "data": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john@example.com",
    "timezone": "America/New_York",
    "week_start": "monday"
  }
}
```

---

## Step 3: Get Your Organization ID

Most API endpoints require an organization ID. Let's fetch your organizations.

```bash
curl https://api.timeclocker.com/v1/users/me/memberships \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Response**:
```json
{
  "data": [
    {
      "id": "membership-uuid",
      "organization": {
        "id": "org-uuid-here",
        "name": "My Company",
        "role": "owner"
      }
    }
  ]
}
```

Save your `organization.id` for the next steps!

---

## Step 4: Create a Time Entry

Now let's create a time entry for work you've done today.

### Using cURL

```bash
curl -X POST https://api.timeclocker.com/v1/organizations/ORG_ID/time-entries \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Building my first API integration",
    "start": "2025-11-05T09:00:00Z",
    "end": "2025-11-05T10:30:00Z",
    "billable": true
  }'
```

### Using JavaScript

```javascript
async function createTimeEntry(orgId) {
  const response = await axios.post(
    `${API_URL}/organizations/${orgId}/time-entries`,
    {
      description: 'Building my first API integration',
      start: '2025-11-05T09:00:00Z',
      end: '2025-11-05T10:30:00Z',
      billable: true
    },
    {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Content-Type': 'application/json'
      }
    }
  );

  console.log('Time entry created:', response.data.data.id);
  return response.data.data;
}

createTimeEntry('your-org-id');
```

### Using Python

```python
def create_time_entry(org_id):
    payload = {
        'description': 'Building my first API integration',
        'start': '2025-11-05T09:00:00Z',
        'end': '2025-11-05T10:30:00Z',
        'billable': True
    }

    response = requests.post(
        f'{API_URL}/organizations/{org_id}/time-entries',
        json=payload,
        headers=headers
    )

    entry = response.json()['data']
    print(f"Time entry created: {entry['id']}")
    return entry

create_time_entry('your-org-id')
```

**Response**:
```json
{
  "data": {
    "id": "time-entry-uuid",
    "description": "Building my first API integration",
    "start": "2025-11-05T09:00:00.000000Z",
    "end": "2025-11-05T10:30:00.000000Z",
    "duration_seconds": 5400,
    "billable": true,
    "user_id": "user-uuid",
    "project_id": null
  }
}
```

---

## Step 5: Retrieve Today's Time Entries

Let's fetch all time entries for today to see what we've tracked.

### Using cURL

```bash
curl "https://api.timeclocker.com/v1/organizations/ORG_ID/time-entries?start_date=2025-11-05&end_date=2025-11-05" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Using JavaScript

```javascript
async function getTodayEntries(orgId) {
  const today = new Date().toISOString().split('T')[0];

  const response = await axios.get(
    `${API_URL}/organizations/${orgId}/time-entries`,
    {
      params: {
        start_date: today,
        end_date: today
      },
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Content-Type': 'application/json'
      }
    }
  );

  const entries = response.data.data;
  console.log(`Found ${entries.length} time entries for today`);
  return entries;
}

getTodayEntries('your-org-id');
```

### Using Python

```python
from datetime import date

def get_today_entries(org_id):
    today = date.today().isoformat()

    params = {
        'start_date': today,
        'end_date': today
    }

    response = requests.get(
        f'{API_URL}/organizations/{org_id}/time-entries',
        params=params,
        headers=headers
    )

    entries = response.json()['data']
    print(f"Found {len(entries)} time entries for today")
    return entries

get_today_entries('your-org-id')
```

---

## Step 6: Calculate Total Hours

Let's calculate the total hours worked today.

### JavaScript

```javascript
async function calculateTotalHours(orgId) {
  const entries = await getTodayEntries(orgId);

  const totalSeconds = entries.reduce((sum, entry) => {
    return sum + entry.duration_seconds;
  }, 0);

  const totalHours = (totalSeconds / 3600).toFixed(2);
  console.log(`Total hours worked today: ${totalHours}`);
  return totalHours;
}

calculateTotalHours('your-org-id');
```

### Python

```python
def calculate_total_hours(org_id):
    entries = get_today_entries(org_id)

    total_seconds = sum(entry['duration_seconds'] for entry in entries)
    total_hours = total_seconds / 3600

    print(f"Total hours worked today: {total_hours:.2f}")
    return total_hours

calculate_total_hours('your-org-id')
```

---

## Complete Example: Node.js

Here's a complete working example:

```javascript
const axios = require('axios');

const API_URL = 'https://api.timeclocker.com/v1';
const API_TOKEN = process.env.TIMECLOCKER_API_TOKEN;

const client = axios.create({
  baseURL: API_URL,
  headers: {
    'Authorization': `Bearer ${API_TOKEN}`,
    'Content-Type': 'application/json'
  }
});

async function main() {
  try {
    // 1. Get current user
    const userResponse = await client.get('/users/me');
    const user = userResponse.data.data;
    console.log(`✓ Logged in as: ${user.name}`);

    // 2. Get organization
    const membershipsResponse = await client.get('/users/me/memberships');
    const orgId = membershipsResponse.data.data[0].organization.id;
    console.log(`✓ Using organization: ${membershipsResponse.data.data[0].organization.name}`);

    // 3. Create time entry
    const entryResponse = await client.post(`/organizations/${orgId}/time-entries`, {
      description: 'API integration test',
      start: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(), // 2 hours ago
      end: new Date().toISOString(),
      billable: true
    });
    console.log(`✓ Created time entry: ${entryResponse.data.data.id}`);

    // 4. Get today's entries
    const today = new Date().toISOString().split('T')[0];
    const entriesResponse = await client.get(`/organizations/${orgId}/time-entries`, {
      params: { start_date: today, end_date: today }
    });

    // 5. Calculate total hours
    const totalSeconds = entriesResponse.data.data.reduce((sum, e) => sum + e.duration_seconds, 0);
    const totalHours = (totalSeconds / 3600).toFixed(2);
    console.log(`✓ Total hours today: ${totalHours}`);

  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
}

main();
```

---

## Next Steps

Now that you've mastered the basics, explore these advanced topics:

### Webhooks
Subscribe to real-time events instead of polling:
- [Webhooks Guide](./webhooks.md)

### Projects & Tasks
Organize time entries with projects:
```bash
# Create a project
POST /v1/organizations/{org_id}/projects

# Assign team members
POST /v1/organizations/{org_id}/projects/{project_id}/project-members

# Track time to a project
POST /v1/organizations/{org_id}/time-entries
{
  "project_id": "uuid",
  "task_id": "uuid",
  ...
}
```

### Invoicing
Generate invoices from tracked time:
```bash
# Create invoice
POST /v1/organizations/{org_id}/invoices

# Mark as sent
POST /v1/organizations/{org_id}/invoices/{id}/mark-as-sent

# Track payment
GET /v1/organizations/{org_id}/payments
```

### Reports
Generate analytics and insights:
```bash
# Weekly project overview
GET /v1/organizations/{org_id}/charts/weekly-project-overview

# Daily tracked hours
GET /v1/organizations/{org_id}/charts/daily-tracked-hours
```

---

## Common Patterns

### Error Handling

```javascript
try {
  const response = await client.post('/organizations/123/time-entries', data);
} catch (error) {
  if (error.response) {
    // Server responded with error status
    console.error('API Error:', error.response.status);
    console.error('Message:', error.response.data.message);

    if (error.response.status === 422) {
      // Validation errors
      console.error('Validation Errors:', error.response.data.errors);
    }
  } else if (error.request) {
    // Request made but no response
    console.error('Network Error: No response from server');
  } else {
    // Something else went wrong
    console.error('Error:', error.message);
  }
}
```

### Rate Limit Handling

```javascript
async function makeRequestWithRetry(fn, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (error) {
      if (error.response?.status === 429) {
        const retryAfter = error.response.headers['retry-after'] || 60;
        console.log(`Rate limited. Retrying after ${retryAfter} seconds...`);
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      } else {
        throw error;
      }
    }
  }
  throw new Error('Max retries exceeded');
}
```

### Pagination

```javascript
async function getAllTimeEntries(orgId) {
  let allEntries = [];
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = await client.get(`/organizations/${orgId}/time-entries`, {
      params: { page, per_page: 100 }
    });

    allEntries = allEntries.concat(response.data.data);
    hasMore = response.data.links.next !== null;
    page++;
  }

  return allEntries;
}
```

---

## Resources

- **Interactive API Explorer**: https://docs.timeclocker.com/api
- **Postman Collection**: [Download](https://timeclocker.com/api/postman.json)
- **Example Apps**: [GitHub](https://github.com/timeclocker/examples)
- **Support**: api-support@timeclocker.com

---

**Need Help?**

- Check the [FAQ](./faq.md)
- Read the [full API reference](./README.md)
- Email us at api-support@timeclocker.com
- Check our [status page](https://status.timeclocker.com)

---

**Last Updated**: 2025-11-05
