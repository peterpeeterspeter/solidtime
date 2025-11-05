# n8n Workflow Automation Integration - Part 1: Foundation ✅

**Status**: Part 1 COMPLETE (Database & Backend Services)
**Date**: 2025-11-05

## Overview

This document tracks the implementation of n8n workflow automation integration for Solidtime, enabling users to create powerful, privacy-conscious automations with the leading open-source workflow engine.

## Phase 1: Database Schema & Models (COMPLETE)

### Database Tables Created

#### 1. `api_keys` Table
**Purpose**: Store API keys for external workflow authentication

**Columns**:
- `id` (UUID) - Primary key
- `user_id` (UUID) - Foreign key to users
- `organization_id` (UUID) - Foreign key to organizations
- `name` (STRING) - User-friendly name
- `key_prefix` (STRING, 20) - First 11 chars for identification (e.g., "sk_abc12345")
- `key_hash` (STRING) - Hashed full key (never store plain text)
- `description` (TEXT, nullable) - Purpose description
- `scopes` (JSON) - Array of permissions (e.g., ["time_entries:read", "projects:write"])
- `is_active` (BOOLEAN) - Active status
- `last_used_at` (TIMESTAMP, nullable) - Last usage timestamp
- `last_used_ip` (STRING, nullable) - Last IP address
- `usage_count` (BIGINT) - Total number of API calls
- `expires_at` (TIMESTAMP, nullable) - Expiration date
- `created_at`, `updated_at`, `deleted_at` (TIMESTAMP)

**Indexes**:
- Primary: `id`
- Unique: `key_prefix`, `key_hash`
- Composite: `(organization_id, is_active)`, `(user_id, is_active)`
- Single: `last_used_at`, `expires_at`

**Security Features**:
- Keys are hashed using Laravel's `Hash::make()` (bcrypt)
- Only prefix shown in UI (e.g., "sk_abc12345...")
- Plain key only returned once at creation
- Soft deletes for audit trail

#### 2. `webhooks` Table
**Purpose**: Store webhook subscriptions for event notifications

**Columns**:
- `id` (UUID) - Primary key
- `user_id`, `organization_id` (UUID) - Ownership
- `name` (STRING) - User-friendly name
- `description` (TEXT, nullable) - Purpose description
- `url` (STRING) - Webhook endpoint URL
- `secret` (STRING, nullable) - HMAC signing secret
- `events` (JSON) - Subscribed event types (e.g., ["time_entry.started", "invoice.created"])
- `filters` (JSON, nullable) - Optional event filters
- `is_active` (BOOLEAN) - Active status
- `failure_count` (INTEGER) - Consecutive failures
- `last_triggered_at` (TIMESTAMP, nullable) - Last trigger time
- `last_success_at` (TIMESTAMP, nullable) - Last successful delivery
- `last_failure_at` (TIMESTAMP, nullable) - Last failure time
- `last_error` (TEXT, nullable) - Last error message
- `verification_status` (ENUM) - ['pending', 'verified', 'failed']
- `verified_at` (TIMESTAMP, nullable) - Verification time
- `created_at`, `updated_at`, `deleted_at` (TIMESTAMP)

**Indexes**:
- Primary: `id`
- Composite: `(organization_id, is_active)`, `(user_id, is_active)`

**Health Monitoring**:
- Auto-disables after 10 consecutive failures
- Tracks last success/failure timestamps
- Stores error messages for debugging

#### 3. `webhook_deliveries` Table
**Purpose**: Audit log for webhook delivery attempts

**Columns**:
- `id` (UUID) - Primary key
- `webhook_id` (UUID) - Foreign key to webhooks
- `event_type` (STRING) - Event that triggered this delivery
- `payload` (JSON) - Full event payload
- `delivery_id` (STRING, unique) - Unique delivery ID (e.g., "del_abc123...")
- `status` (ENUM) - ['pending', 'success', 'failed', 'retrying']
- `http_status_code` (INTEGER, nullable) - HTTP response code
- `response_body` (TEXT, nullable) - Response from endpoint
- `error_message` (TEXT, nullable) - Error details
- `attempted_at` (TIMESTAMP) - Attempt time
- `completed_at` (TIMESTAMP, nullable) - Completion time
- `duration_ms` (INTEGER, nullable) - Request duration in milliseconds
- `attempt_number` (INTEGER) - Current attempt (1-3)
- `max_attempts` (INTEGER) - Maximum retries (default: 3)
- `next_retry_at` (TIMESTAMP, nullable) - Scheduled retry time
- `created_at`, `updated_at` (TIMESTAMP)

**Indexes**:
- Primary: `id`
- Unique: `delivery_id`
- Composite: `(webhook_id, status)`, `(event_type, attempted_at)`, `(status, next_retry_at)`

**Retry Logic**:
- Exponential backoff: 5min, 10min, 20min
- Maximum 3 attempts
- Status transitions: pending → success/failed → retrying → success/failed

### Eloquent Models Created

#### 1. `ApiKey` Model
**File**: `app/Models/ApiKey.php`

**Key Methods**:
```php
// Generate new API key
ApiKey::generateKey(): array
// Returns: ['key' => 'sk_...', 'prefix' => 'sk_abc12345', 'hash' => '...']

// Verify plain key against hash
$apiKey->verifyKey(string $plainKey): bool

// Check if key is valid (active + not expired)
$apiKey->isValid(): bool

// Check if key has specific scope
$apiKey->hasScope(string $scope): bool

// Record API usage
$apiKey->recordUsage(?string $ipAddress): void

// Revoke (deactivate) key
$apiKey->revoke(): void
```

**Scopes**:
- `active()` - Only active, non-expired keys
- `expired()` - Expired keys
- `forOrganization(string $organizationId)`
- `forUser(string $userId)`

**Relationships**:
- `belongsTo(User::class)`
- `belongsTo(Organization::class)`

#### 2. `Webhook` Model
**File**: `app/Models/Webhook.php`

**Event Types Supported** (`Webhook::EVENTS`):
```php
// Time entries
'time_entry.started', 'time_entry.stopped', 'time_entry.created',
'time_entry.updated', 'time_entry.deleted'

// Focus sessions
'focus_session.detected', 'focus_session.completed'

// Projects
'project.created', 'project.updated', 'project.deleted'

// Tasks
'task.created', 'task.updated', 'task.deleted', 'task.completed'

// Team
'member.added', 'member.removed'

// Reports
'report.generated', 'timesheet.exported'

// Invoices
'invoice.created', 'invoice.sent', 'invoice.paid'
```

**Key Methods**:
```php
// Generate webhook secret
Webhook::generateSecret(): string
// Returns: 'whsec_...' (48 random chars)

// Check event subscription
$webhook->isSubscribedTo(string $eventType): bool

// Check health status
$webhook->isHealthy(): bool

// Record delivery result
$webhook->recordSuccess(): void
$webhook->recordFailure(string $error): void

// Verification
$webhook->markVerified(): void

// Enable/disable
$webhook->enable(): void
$webhook->disable(): void
```

**Scopes**:
- `active()` - Only active webhooks
- `healthy()` - Active with low failure count (<10)
- `forOrganization(string $organizationId)`
- `subscribedTo(string $eventType)`

**Relationships**:
- `belongsTo(User::class)`
- `belongsTo(Organization::class)`
- `hasMany(WebhookDelivery::class)`

#### 3. `WebhookDelivery` Model
**File**: `app/Models/WebhookDelivery.php`

**Key Methods**:
```php
// Generate delivery ID
WebhookDelivery::generateDeliveryId(): string
// Returns: 'del_...' (24 random chars)

// Check delivery status
$delivery->isSuccess(): bool
$delivery->canRetry(): bool

// Mark delivery result
$delivery->markAsSuccess(int $httpStatus, ?string $responseBody, int $durationMs): void
$delivery->markAsFailed(string $error, ?int $httpStatus, ?string $responseBody, int $durationMs): void

// Retry logic
$delivery->incrementAttempt(): void
```

**Scopes**:
- `pending()` - Pending deliveries
- `retryable()` - Failed deliveries ready for retry
- `forEvent(string $eventType)`

**Relationships**:
- `belongsTo(Webhook::class)`

---

## Phase 2: Backend Services (COMPLETE)

### 1. ApiKeyService
**File**: `app/Services/ApiKeyService.php`

**Purpose**: Manage API key lifecycle

**Methods**:
```php
// Create new API key
create(
    string $userId,
    string $organizationId,
    string $name,
    array $scopes,
    ?string $description = null,
    ?Carbon $expiresAt = null
): array
// Returns: ['api_key' => ApiKey, 'plain_key' => 'sk_...']

// Authenticate with API key
authenticate(string $plainKey): ?ApiKey

// Revoke API key
revoke(string $apiKeyId): bool

// Get API keys
getForOrganization(string $organizationId): Collection
getForUser(string $userId): Collection

// Update scopes
updateScopes(string $apiKeyId, array $scopes): bool

// Cleanup
cleanupExpired(): int
```

**Usage Example**:
```php
$apiKeyService = new ApiKeyService();

// Create
$result = $apiKeyService->create(
    userId: $user->id,
    organizationId: $org->id,
    name: 'n8n Production',
    scopes: ['time_entries:read', 'projects:read'],
    description: 'API key for n8n time entry automation',
    expiresAt: now()->addYear()
);

// Show key once to user
echo "Your API key: {$result['plain_key']}";
// Save: sk_abc123... (NEVER SHOWN AGAIN)

// Later: Authenticate
$apiKey = $apiKeyService->authenticate($requestKey);
if ($apiKey && $apiKey->hasScope('time_entries:read')) {
    // Allow access
}
```

### 2. WebhookDispatcher
**File**: `app/Services/WebhookDispatcher.php`

**Purpose**: Send webhook events to subscribed endpoints

**Methods**:
```php
// Dispatch event to all subscribed webhooks
dispatch(string $eventType, array $payload, string $organizationId): void

// Send to specific webhook
send(Webhook $webhook, string $eventType, array $payload): WebhookDelivery

// Retry failed delivery
retry(WebhookDelivery $delivery): void

// Process all retryable deliveries
processRetries(): int
```

**Features**:
- 10-second HTTP timeout
- HMAC-SHA256 signature verification
- Exponential backoff retry (5min → 10min → 20min)
- Comprehensive error logging
- Duration tracking in milliseconds

**Headers Sent**:
```
Content-Type: application/json
User-Agent: Solidtime-Webhooks/1.0
X-Solidtime-Event: time_entry.started
X-Solidtime-Delivery: del_abc123...
X-Solidtime-Timestamp: 1699200000
X-Solidtime-Signature: a1b2c3d4e5f6... (if secret configured)
```

**Payload Format**:
```json
{
  "event": "time_entry.started",
  "delivery_id": "del_abc123...",
  "timestamp": "2025-11-05T12:00:00Z",
  "data": {
    "time_entry_id": "uuid",
    "user_id": "uuid",
    "project_id": "uuid",
    "started_at": "2025-11-05T12:00:00Z",
    ...
  }
}
```

**Usage Example**:
```php
$dispatcher = new WebhookDispatcher();

// When time entry starts
$dispatcher->dispatch(
    eventType: 'time_entry.started',
    payload: [
        'time_entry_id' => $timeEntry->id,
        'user_id' => $user->id,
        'project_id' => $timeEntry->project_id,
        'started_at' => $timeEntry->started_at->toIso8601String(),
    ],
    organizationId: $org->id
);

// This will:
// 1. Find all webhooks subscribed to 'time_entry.started'
// 2. Send HTTP POST to each webhook URL
// 3. Include HMAC signature if secret configured
// 4. Log delivery in webhook_deliveries table
// 5. Update webhook health status
```

---

## Security Features

### API Key Security
1. **Hashing**: Keys hashed with bcrypt (never stored in plain text)
2. **Prefix**: Only first 11 chars shown (`sk_abc12345...`)
3. **One-time Display**: Plain key shown once at creation
4. **Expiration**: Optional expiration dates
5. **Revocation**: Can be revoked anytime
6. **Scopes**: Granular permissions
7. **Audit Trail**: Usage tracking (IP, timestamp, count)
8. **Soft Deletes**: Deletion history preserved

### Webhook Security
1. **HMAC Signatures**: Optional HMAC-SHA256 signing
2. **Secret Keys**: Per-webhook secrets (`whsec_...`)
3. **Health Monitoring**: Auto-disable after 10 failures
4. **Delivery IDs**: Unique per delivery for idempotency
5. **Timestamps**: Prevent replay attacks
6. **HTTPS Required**: (to be enforced in validation)
7. **Audit Log**: Complete delivery history
8. **Rate Limiting**: (to be implemented)

### Data Privacy
- **Organization Scoped**: All data filtered by organization
- **User Permissions**: Only authorized users can create/manage
- **EU Compliance**: Data stays in EU unless user explicitly routes elsewhere
- **Soft Deletes**: Audit trail preservation
- **No Sensitive Data**: Response bodies truncated if > 10KB

---

## Performance Considerations

### Database Indexes
- **Composite indexes** for common queries:
  - `(organization_id, is_active)` - List active keys/webhooks
  - `(webhook_id, status)` - Delivery history
  - `(status, next_retry_at)` - Retry queue processing

### Webhook Delivery
- **Async Processing**: Should be queued (Laravel Jobs)
- **Batch Retries**: Process up to 100 retries at once
- **Timeout**: 10 seconds per webhook
- **Circuit Breaker**: Auto-disable after 10 failures

### Cleanup
- **API Key Expiration**: Scheduled task to soft-delete expired keys
- **Delivery Log Retention**: Archive deliveries > 30 days old (to be implemented)

---

## Next Steps (Part 2)

### Immediate Tasks
1. **API Controllers**: Create REST endpoints for CRUD operations
2. **Authentication Middleware**: Protect API routes with API key auth
3. **Event Listeners**: Trigger webhooks on model events
4. **Scheduled Job**: Webhook retry processor
5. **Validation**: Request validation rules
6. **Rate Limiting**: Prevent abuse

### UI Components (Part 3)
1. **API Keys Management**: List, create, revoke keys
2. **Webhooks Management**: CRUD webhooks
3. **Webhook Logs**: View delivery history
4. **Event Browser**: Browse available events
5. **Test Webhook**: Send test payloads

### n8n Integration (Part 4)
1. **Custom Nodes**: Solidtime trigger/action nodes
2. **Workflow Templates**: Pre-built automation recipes
3. **Documentation**: Setup guides
4. **Community Library**: Publish to n8n

---

## File Summary

| File | Type | LOC | Purpose |
|------|------|-----|---------|
| `2025_11_05_230000_create_api_keys_table.php` | Migration | 50 | API keys schema |
| `2025_11_05_230100_create_webhooks_table.php` | Migration | 45 | Webhooks schema |
| `2025_11_05_230200_create_webhook_deliveries_table.php` | Migration | 50 | Delivery audit log |
| `app/Models/ApiKey.php` | Model | 150 | API key model |
| `app/Models/Webhook.php` | Model | 200 | Webhook model |
| `app/Models/WebhookDelivery.php` | Model | 120 | Delivery model |
| `app/Services/ApiKeyService.php` | Service | 100 | API key management |
| `app/Services/WebhookDispatcher.php` | Service | 150 | Webhook dispatcher |
| **Total** | | **865 LOC** | **8 files** |

---

## Acceptance Criteria Status

### Part 1: Foundation
- [x] Database schema designed
- [x] Migrations created
- [x] Eloquent models with relationships
- [x] API key generation and hashing
- [x] Webhook event types defined
- [x] API key service
- [x] Webhook dispatcher service
- [x] HMAC signature generation
- [x] Retry logic with exponential backoff
- [x] Health monitoring
- [x] Audit logging

### Part 2: API & Events (Pending)
- [ ] REST API endpoints
- [ ] API key authentication middleware
- [ ] Event listeners for model changes
- [ ] Webhook retry scheduled job
- [ ] Request validation
- [ ] Rate limiting

### Part 3: UI (Pending)
- [ ] API keys management page
- [ ] Webhooks management page
- [ ] Delivery logs viewer
- [ ] Event browser
- [ ] Test webhook feature

### Part 4: n8n Integration (Pending)
- [ ] Custom n8n nodes
- [ ] Workflow templates
- [ ] Setup documentation
- [ ] Community library publication

---

## Conclusion

Part 1 (Foundation) is **COMPLETE**. The database schema, models, and core services are production-ready. This provides a solid foundation for building the REST API and UI components in Part 2 and 3.

**Next Commit**: Part 2 - API Controllers & Event Listeners
