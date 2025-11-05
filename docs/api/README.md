# Timeclocker API Documentation

**Version**: 1.0
**Base URL**: `https://api.timeclocker.com/v1`
**Documentation**: https://docs.timeclocker.com/api
**Status Page**: https://status.timeclocker.com

---

## Welcome to the Timeclocker API

The Timeclocker API is a RESTful API that allows you to integrate time tracking, invoicing, payments, and team management into your applications. Built with privacy-first principles and developer experience in mind.

### What You Can Do

- **Time Tracking**: Create, update, and query time entries
- **Projects & Tasks**: Manage projects, tasks, and assignments
- **Team Management**: Handle organizations, members, and invitations
- **Invoicing**: Generate invoices, track payments, set up recurring billing
- **Privacy Controls**: Configure user privacy settings and tracking levels
- **Webhooks**: Subscribe to real-time events
- **Reports**: Generate detailed analytics and insights

### Key Features

✅ **RESTful Design** - Predictable resource-oriented URLs
✅ **JSON by Default** - All requests and responses use JSON
✅ **OAuth 2.0 & API Tokens** - Secure authentication options
✅ **Rate Limiting** - Fair usage with clear headers
✅ **Webhooks** - Real-time event notifications
✅ **Comprehensive Errors** - Detailed error messages and codes
✅ **Pagination** - Efficient data retrieval for large datasets
✅ **Filtering & Sorting** - Flexible query parameters
✅ **Privacy-First** - GDPR compliant with full transparency

---

## Quick Start

### 1. Get Your API Token

Navigate to **Settings > API Tokens** in your Timeclocker dashboard and create a new token.

```bash
# Your token will look like this:
YOUR_API_TOKEN="abc123def456..."
```

### 2. Make Your First Request

```bash
curl https://api.timeclocker.com/v1/users/me \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

**Response**:
```json
{
  "data": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john@example.com",
    "timezone": "America/New_York"
  }
}
```

### 3. Create a Time Entry

```bash
curl -X POST https://api.timeclocker.com/v1/organizations/{org_id}/time-entries \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Working on API documentation",
    "start": "2025-11-05T09:00:00Z",
    "end": "2025-11-05T10:30:00Z",
    "project_id": "project-uuid-here"
  }'
```

---

## Authentication

Timeclocker supports two authentication methods:

### API Tokens (Recommended)

API tokens are the simplest way to authenticate. They're perfect for server-to-server integrations.

**Creating a Token**:
1. Go to Settings > API Tokens
2. Click "Create New Token"
3. Copy the token (shown only once!)

**Using the Token**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.timeclocker.com/v1/users/me
```

### OAuth 2.0

OAuth is ideal for third-party applications that need to access user data on their behalf.

**Supported Grant Types**:
- Authorization Code (recommended for web apps)
- Client Credentials (for server-to-server)

**OAuth Flow**:
1. Redirect user to `/oauth/authorize`
2. User grants permission
3. Receive authorization code
4. Exchange code for access token
5. Use access token for API requests

See the [OAuth Guide](./oauth.md) for detailed implementation steps.

---

## Rate Limiting

To ensure fair usage and system stability, API requests are rate limited based on your plan tier.

### Rate Limit Tiers

| Tier       | Requests/Minute | Requests/Hour |
|------------|-----------------|---------------|
| Free       | 100             | 6,000         |
| Pro        | 500             | 30,000        |
| Enterprise | 2,000           | 120,000       |

### Rate Limit Headers

Every API response includes rate limit information:

```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699200000
```

**Headers Explained**:
- `X-RateLimit-Limit`: Maximum requests allowed per window
- `X-RateLimit-Remaining`: Requests remaining in current window
- `X-RateLimit-Reset`: Unix timestamp when the limit resets

### Rate Limit Exceeded

When you exceed your rate limit, you'll receive a `429 Too Many Requests` response:

```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Maximum 100 requests per 1 minute(s).",
  "retry_after": 45
}
```

**Best Practices**:
- Implement exponential backoff
- Cache responses when possible
- Use webhooks instead of polling
- Respect the `Retry-After` header

---

## Pagination

List endpoints return paginated results to keep responses fast and manageable.

### Default Pagination

```bash
GET /v1/organizations/{org_id}/time-entries?page=1&per_page=50
```

**Parameters**:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 50, max: 100)

**Response Structure**:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 50,
    "per_page": 50,
    "total": 247
  },
  "links": {
    "first": "https://api.timeclocker.com/v1/time-entries?page=1",
    "last": "https://api.timeclocker.com/v1/time-entries?page=5",
    "prev": null,
    "next": "https://api.timeclocker.com/v1/time-entries?page=2"
  }
}
```

---

## Filtering & Sorting

Most list endpoints support filtering and sorting.

### Filtering

```bash
# Filter by date range
GET /v1/organizations/{org_id}/time-entries?start_date=2025-11-01&end_date=2025-11-05

# Filter by user
GET /v1/organizations/{org_id}/time-entries?user_id=uuid-here

# Filter by project
GET /v1/organizations/{org_id}/time-entries?project_id=uuid-here

# Combine filters
GET /v1/organizations/{org_id}/time-entries?user_id=uuid&billable=true
```

### Sorting

```bash
# Sort by start time (ascending)
GET /v1/organizations/{org_id}/time-entries?sort=start

# Sort descending (prefix with -)
GET /v1/organizations/{org_id}/time-entries?sort=-start

# Multiple sort fields
GET /v1/organizations/{org_id}/time-entries?sort=project_id,-start
```

---

## Errors

Timeclocker uses conventional HTTP status codes and provides detailed error messages.

### HTTP Status Codes

| Code | Meaning                                          |
|------|--------------------------------------------------|
| 200  | OK - Request succeeded                           |
| 201  | Created - Resource created successfully          |
| 204  | No Content - Request succeeded, no response body |
| 400  | Bad Request - Invalid request parameters         |
| 401  | Unauthorized - Missing or invalid authentication |
| 403  | Forbidden - Authenticated but not authorized     |
| 404  | Not Found - Resource doesn't exist               |
| 422  | Unprocessable Entity - Validation failed         |
| 429  | Too Many Requests - Rate limit exceeded          |
| 500  | Internal Server Error - Something went wrong     |

### Error Response Format

```json
{
  "error": "Validation Failed",
  "message": "The given data was invalid.",
  "errors": {
    "start": ["The start field is required."],
    "end": ["The end must be after the start time."]
  }
}
```

### Common Error Codes

```json
{
  "error": "Unauthorized",
  "message": "Invalid or expired API token."
}
```

```json
{
  "error": "Not Found",
  "message": "Time entry not found."
}
```

```json
{
  "error": "Forbidden",
  "message": "You do not have permission to access this organization."
}
```

---

## Webhooks

Subscribe to real-time events instead of polling the API.

### Available Events

- `time_entry.created`
- `time_entry.updated`
- `time_entry.deleted`
- `project.created`
- `project.archived`
- `invoice.sent`
- `invoice.paid`
- `payment.received`
- `payment.refunded`
- `member.added`
- `member.removed`

### Creating a Webhook

```bash
curl -X POST https://api.timeclocker.com/v1/webhooks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-app.com/webhooks/timeclocker",
    "events": ["time_entry.created", "invoice.sent"],
    "secret": "your-webhook-secret"
  }'
```

### Webhook Payload

```json
{
  "event": "time_entry.created",
  "id": "delivery-uuid",
  "timestamp": "2025-11-05T14:30:00Z",
  "data": {
    "id": "uuid",
    "description": "Working on feature X",
    "start": "2025-11-05T09:00:00Z",
    "end": "2025-11-05T10:30:00Z"
  }
}
```

### Verifying Webhook Signatures

```php
$signature = $_SERVER['HTTP_X_TIMECLOCKER_SIGNATURE'];
$payload = file_get_contents('php://input');
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

See the [Webhooks Guide](./webhooks.md) for more details.

---

## Resources

- [Getting Started](./getting-started.md) - Step-by-step tutorial
- [Authentication](./authentication.md) - OAuth & API tokens
- [Webhooks](./webhooks.md) - Real-time events
- [Rate Limiting](./rate-limiting.md) - Limits and best practices
- [Examples](./examples.md) - Code samples in multiple languages
- [Changelog](./changelog.md) - API version history

---

## Support

- **Documentation**: https://docs.timeclocker.com
- **Status Page**: https://status.timeclocker.com
- **Email**: api-support@timeclocker.com
- **Response Time**: <24 hours

---

## Terms of Service

By using the Timeclocker API, you agree to our [Terms of Service](https://timeclocker.com/terms) and [Privacy Policy](https://timeclocker.com/privacy).

---

**Last Updated**: 2025-11-05
**API Version**: 1.0
