# Webhooks Guide

Webhooks allow you to receive real-time notifications when events occur in Timeclocker, instead of polling the API for changes.

## Overview

When you register a webhook URL, Timeclocker will send HTTP POST requests to that URL whenever subscribed events occur. This is more efficient than polling and enables you to build responsive integrations.

### Benefits

✅ **Real-time** - Instant notifications when events occur
✅ **Efficient** - No need to poll the API repeatedly
✅ **Reliable** - Automatic retries with exponential backoff
✅ **Secure** - HMAC-SHA256 signature verification
✅ **Flexible** - Subscribe to specific events you care about

---

## Available Events

### Time Tracking Events
- `time_entry.created` - New time entry created
- `time_entry.updated` - Time entry modified
- `time_entry.deleted` - Time entry removed

### Project Events
- `project.created` - New project created
- `project.updated` - Project details modified
- `project.archived` - Project archived/completed

### Invoice Events
- `invoice.created` - New invoice generated
- `invoice.sent` - Invoice sent to client
- `invoice.paid` - Invoice marked as paid
- `invoice.overdue` - Invoice past due date

### Payment Events
- `payment.received` - Payment successfully processed
- `payment.refunded` - Payment refunded
- `payment.failed` - Payment attempt failed

### Team Events
- `member.added` - New team member added
- `member.removed` - Team member removed
- `member.role_changed` - Member role/permissions updated

---

## Creating a Webhook

### API Request

```bash
POST https://api.timeclocker.com/v1/webhooks
```

```json
{
  "url": "https://your-app.com/webhooks/timeclocker",
  "events": ["time_entry.created", "invoice.sent"],
  "secret": "your-webhook-secret-key",
  "is_active": true
}
```

### cURL Example

```bash
curl -X POST https://api.timeclocker.com/v1/webhooks \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-app.com/webhooks/timeclocker",
    "events": ["time_entry.created", "invoice.sent"],
    "secret": "your-webhook-secret-key",
    "is_active": true
  }'
```

### Response

```json
{
  "data": {
    "id": "webhook-uuid",
    "url": "https://your-app.com/webhooks/timeclocker",
    "events": ["time_entry.created", "invoice.sent"],
    "is_active": true,
    "created_at": "2025-11-05T14:00:00.000000Z"
  }
}
```

**Note**: The `secret` field is write-only and won't be returned in responses. Store it securely!

---

## Webhook Payload Structure

All webhook deliveries follow this structure:

```json
{
  "id": "delivery-uuid",
  "event": "time_entry.created",
  "timestamp": "2025-11-05T14:30:00Z",
  "data": {
    "id": "resource-uuid",
    "description": "Working on feature X",
    "start": "2025-11-05T09:00:00Z",
    "end": "2025-11-05T10:30:00Z",
    "user_id": "user-uuid",
    "project_id": "project-uuid"
  }
}
```

### Payload Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique delivery ID (for idempotency) |
| `event` | string | Event type that triggered the webhook |
| `timestamp` | string | ISO 8601 timestamp when event occurred |
| `data` | object | Event-specific data (see examples below) |

---

## Event Payload Examples

### time_entry.created

```json
{
  "id": "delivery-001",
  "event": "time_entry.created",
  "timestamp": "2025-11-05T14:30:00Z",
  "data": {
    "id": "uuid",
    "description": "Working on feature X",
    "start": "2025-11-05T09:00:00Z",
    "end": "2025-11-05T10:30:00Z",
    "duration_seconds": 5400,
    "billable": true,
    "user_id": "user-uuid",
    "project_id": "project-uuid",
    "task_id": null
  }
}
```

### invoice.sent

```json
{
  "id": "delivery-002",
  "event": "invoice.sent",
  "timestamp": "2025-11-05T15:00:00Z",
  "data": {
    "id": "invoice-uuid",
    "invoice_number": "INV-2025-001",
    "client_id": "client-uuid",
    "amount": 1500.00,
    "currency": "USD",
    "status": "sent",
    "due_date": "2025-11-20",
    "sent_at": "2025-11-05T15:00:00Z"
  }
}
```

### payment.received

```json
{
  "id": "delivery-003",
  "event": "payment.received",
  "timestamp": "2025-11-05T16:00:00Z",
  "data": {
    "id": "payment-uuid",
    "invoice_id": "invoice-uuid",
    "amount": 1500.00,
    "currency": "USD",
    "gateway": "stripe",
    "gateway_payment_id": "ch_abc123",
    "status": "succeeded",
    "paid_at": "2025-11-05T16:00:00Z"
  }
}
```

### member.added

```json
{
  "id": "delivery-004",
  "event": "member.added",
  "timestamp": "2025-11-05T17:00:00Z",
  "data": {
    "id": "member-uuid",
    "user_id": "user-uuid",
    "organization_id": "org-uuid",
    "role": "member",
    "added_by": "admin-user-uuid",
    "added_at": "2025-11-05T17:00:00Z"
  }
}
```

---

## Verifying Webhook Signatures

**Always verify webhook signatures** to ensure requests are from Timeclocker and haven't been tampered with.

### Signature Header

Timeclocker sends a signature in the `X-Timeclocker-Signature` header:

```
X-Timeclocker-Signature: sha256=abc123def456...
```

### Verification Process

1. Extract the signature from the header
2. Compute HMAC-SHA256 of the raw request body using your webhook secret
3. Compare the computed signature with the received signature using constant-time comparison

### Node.js Example

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, webhookSecret) {
  const signature = req.headers['x-timeclocker-signature'];
  if (!signature) {
    throw new Error('No signature header found');
  }

  // Extract signature (format: "sha256=...")
  const receivedSignature = signature.split('=')[1];

  // Compute expected signature
  const rawBody = JSON.stringify(req.body);
  const expectedSignature = crypto
    .createHmac('sha256', webhookSecret)
    .update(rawBody)
    .digest('hex');

  // Constant-time comparison to prevent timing attacks
  const isValid = crypto.timingSafeEqual(
    Buffer.from(receivedSignature),
    Buffer.from(expectedSignature)
  );

  if (!isValid) {
    throw new Error('Invalid webhook signature');
  }

  return true;
}

// Express.js route example
app.post('/webhooks/timeclocker', express.json(), (req, res) => {
  try {
    verifyWebhookSignature(req, process.env.WEBHOOK_SECRET);

    // Signature verified - process webhook
    const { event, data } = req.body;
    console.log(`Received ${event}:`, data);

    // Acknowledge receipt immediately
    res.status(200).json({ received: true });

    // Process webhook asynchronously
    processWebhook(event, data);

  } catch (error) {
    console.error('Webhook verification failed:', error);
    res.status(401).json({ error: 'Invalid signature' });
  }
});
```

### PHP Example

```php
<?php

function verifyWebhookSignature($requestBody, $signature, $webhookSecret) {
    if (empty($signature)) {
        throw new Exception('No signature header found');
    }

    // Extract signature (format: "sha256=...")
    list($algorithm, $receivedSignature) = explode('=', $signature, 2);

    // Compute expected signature
    $expectedSignature = hash_hmac('sha256', $requestBody, $webhookSecret);

    // Constant-time comparison
    if (!hash_equals($expectedSignature, $receivedSignature)) {
        throw new Exception('Invalid webhook signature');
    }

    return true;
}

// Usage
$requestBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TIMECLOCKER_SIGNATURE'] ?? '';
$webhookSecret = getenv('WEBHOOK_SECRET');

try {
    verifyWebhookSignature($requestBody, $signature, $webhookSecret);

    // Signature verified
    $payload = json_decode($requestBody, true);
    error_log("Received {$payload['event']}");

    // Acknowledge receipt
    http_response_code(200);
    echo json_encode(['received' => true]);

    // Process webhook
    processWebhook($payload['event'], $payload['data']);

} catch (Exception $e) {
    error_log('Webhook verification failed: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
```

### Python Example

```python
import hmac
import hashlib
import json

def verify_webhook_signature(request_body, signature, webhook_secret):
    if not signature:
        raise ValueError('No signature header found')

    # Extract signature (format: "sha256=...")
    algorithm, received_signature = signature.split('=', 1)

    # Compute expected signature
    expected_signature = hmac.new(
        webhook_secret.encode(),
        request_body.encode(),
        hashlib.sha256
    ).hexdigest()

    # Constant-time comparison
    if not hmac.compare_digest(expected_signature, received_signature):
        raise ValueError('Invalid webhook signature')

    return True

# Flask example
from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route('/webhooks/timeclocker', methods=['POST'])
def webhook():
    try:
        signature = request.headers.get('X-Timeclocker-Signature', '')
        request_body = request.get_data(as_text=True)

        verify_webhook_signature(request_body, signature, WEBHOOK_SECRET)

        # Signature verified
        payload = request.get_json()
        print(f"Received {payload['event']}")

        # Acknowledge receipt immediately
        return jsonify({'received': True}), 200

    except ValueError as e:
        print(f'Webhook verification failed: {e}')
        return jsonify({'error': 'Invalid signature'}), 401
```

---

## Handling Webhooks

### Best Practices

1. **Respond Quickly** (< 5 seconds)
   - Acknowledge receipt immediately with `200 OK`
   - Process the webhook asynchronously (queue, background job)
   - Don't make external API calls in the webhook handler

2. **Implement Idempotency**
   - Use the delivery `id` to prevent duplicate processing
   - Store processed delivery IDs in your database

3. **Handle Retries Gracefully**
   - Timeclocker will retry failed deliveries up to 5 times
   - Return `200 OK` even if you've already processed this delivery

4. **Validate Event Data**
   - Always verify the signature
   - Validate the event type matches your subscriptions
   - Validate required fields in `data` object

5. **Log Everything**
   - Log all webhook deliveries (success and failure)
   - Useful for debugging and compliance

### Example: Idempotent Processing

```javascript
const processedDeliveries = new Set();

async function handleWebhook(webhookPayload) {
  const { id, event, data } = webhookPayload;

  // Check if already processed
  if (processedDeliveries.has(id)) {
    console.log(`Delivery ${id} already processed, skipping`);
    return { status: 'duplicate' };
  }

  // Process based on event type
  switch (event) {
    case 'time_entry.created':
      await handleTimeEntryCreated(data);
      break;
    case 'invoice.sent':
      await handleInvoiceSent(data);
      break;
    default:
      console.warn(`Unknown event type: ${event}`);
  }

  // Mark as processed
  processedDeliveries.add(id);

  return { status: 'processed' };
}
```

---

## Retry Logic

Timeclocker automatically retries failed webhook deliveries:

- **Retry Attempts**: Up to 5 retries
- **Retry Schedule**: Exponential backoff (2s, 4s, 8s, 16s, 32s)
- **Success Criteria**: HTTP 200-299 status code
- **Timeout**: 10 seconds per request

### Retry Example Timeline

```
Attempt 1: Immediately after event
Attempt 2: 2 seconds later (if failed)
Attempt 3: 4 seconds later (if failed)
Attempt 4: 8 seconds later (if failed)
Attempt 5: 16 seconds later (if failed)
Attempt 6: 32 seconds later (final attempt)
```

If all retries fail, the webhook is marked as failed and you'll be notified via email.

---

## Managing Webhooks

### List All Webhooks

```bash
GET /v1/webhooks
```

```bash
curl https://api.timeclocker.com/v1/webhooks \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Get Webhook Details

```bash
GET /v1/webhooks/{webhook_id}
```

### Update Webhook

```bash
PUT /v1/webhooks/{webhook_id}
```

```json
{
  "url": "https://new-url.com/webhooks",
  "events": ["time_entry.created", "project.created"],
  "is_active": true
}
```

### Delete Webhook

```bash
DELETE /v1/webhooks/{webhook_id}
```

### Test Webhook

Send a test event to verify your endpoint:

```bash
POST /v1/webhooks/{webhook_id}/test
```

This sends a `webhook.test` event with sample data.

---

## Webhook Delivery Log

View the last 100 webhook deliveries:

```bash
GET /v1/webhooks/{webhook_id}/deliveries
```

**Response**:
```json
{
  "data": [
    {
      "id": "delivery-uuid",
      "webhook_id": "webhook-uuid",
      "event": "time_entry.created",
      "attempt": 1,
      "response_status": 200,
      "response_body": "{\"received\":true}",
      "delivered_at": "2025-11-05T14:30:05Z"
    }
  ]
}
```

---

## Troubleshooting

### Webhook Not Receiving Events

1. **Check webhook is active**: `is_active: true`
2. **Verify URL is publicly accessible**: Test with curl
3. **Check firewall rules**: Ensure inbound HTTPS traffic allowed
4. **Review delivery log**: Look for error messages

### Signature Verification Failing

1. **Use raw request body**: Don't parse JSON before verification
2. **Check secret matches**: Compare with webhook creation
3. **Inspect signature header**: Ensure `X-Timeclocker-Signature` is present
4. **Use constant-time comparison**: Prevent timing attacks

### Webhook Timing Out

1. **Respond within 5 seconds**: Acknowledge immediately, process async
2. **Don't make external API calls**: Queue them for background processing
3. **Check server performance**: Ensure endpoint isn't overloaded

### Duplicate Events

1. **Implement idempotency**: Use delivery `id` to track processed events
2. **Check retry logic**: Failed deliveries will be retried
3. **Return 200 OK for duplicates**: Prevents further retries

---

## Security Considerations

### HTTPS Only

Timeclocker only delivers webhooks to HTTPS URLs. HTTP endpoints will be rejected.

### IP Whitelisting

If you use IP whitelisting, add Timeclocker's webhook IPs:

```
52.21.45.0/24
52.21.46.0/24
```

(IP ranges updated periodically - check docs for current list)

### Signature Verification

**Always verify signatures** to prevent:
- Replay attacks
- Spoofed webhooks
- Man-in-the-middle attacks

### Secret Storage

- Store webhook secrets in environment variables
- Never commit secrets to version control
- Rotate secrets periodically
- Use different secrets for production/staging

---

## Example Integration: Slack Notifications

Send Slack notifications when invoices are paid:

```javascript
const axios = require('axios');

async function handleInvoicePaid(data) {
  const message = {
    text: `🎉 Invoice ${data.invoice_number} has been paid!`,
    blocks: [
      {
        type: 'section',
        text: {
          type: 'mrkdwn',
          text: `*Invoice Paid*\n\nInvoice: ${data.invoice_number}\nAmount: $${data.amount}\nClient: ${data.client_name}`
        }
      }
    ]
  };

  await axios.post(process.env.SLACK_WEBHOOK_URL, message);
}

app.post('/webhooks/timeclocker', express.json(), async (req, res) => {
  try {
    verifyWebhookSignature(req, process.env.WEBHOOK_SECRET);

    const { event, data } = req.body;

    if (event === 'invoice.paid') {
      await handleInvoicePaid(data);
    }

    res.status(200).json({ received: true });
  } catch (error) {
    console.error('Webhook error:', error);
    res.status(500).json({ error: error.message });
  }
});
```

---

## Resources

- [API Reference](./README.md)
- [Getting Started Guide](./getting-started.md)
- [Webhook Event Catalog](./events.md)
- [Postman Collection](https://timeclocker.com/api/postman.json)

---

## Support

Need help with webhooks?

- **Email**: api-support@timeclocker.com
- **Response Time**: <24 hours
- **Documentation**: https://docs.timeclocker.com/webhooks

---

**Last Updated**: 2025-11-05
**API Version**: 1.0
