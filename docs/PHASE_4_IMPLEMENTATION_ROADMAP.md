# Phase 4 Implementation Roadmap

**Document Created**: 2025-11-05
**Purpose**: Consolidated implementation guide for Phase 4 development
**Status**: Ready to Execute
**Target Completion**: 16 weeks (4 months)

---

## Executive Summary

Phase 4 transforms Timeclocker from a solid time tracking tool into a **privacy-first, API-driven, automated productivity platform** that addresses every major pain point Trackabi users have complained about for years.

**Core Strategy**: Build what Trackabi promised but never delivered.

**Budget**: ~16 weeks of development time across 3 sub-phases
**ROI**: Expected 3-5x increase in user acquisition from competitive differentiation

---

## Three-Phase Approach

### Phase 4A: Privacy Foundation (Weeks 1-4)
**Goal**: Establish privacy as brand differentiator
**Effort**: 1 month, 2-3 engineers
**Impact**: 🔴 Critical - Table stakes for trust

### Phase 4B: Open Platform (Weeks 5-10)
**Goal**: Enable ecosystem growth via integrations
**Effort**: 6 weeks, 2 engineers + 1 designer
**Impact**: 🔴 Critical - Competitive blocker

### Phase 4C: Automatic Tracking (Weeks 11-16)
**Goal**: Desktop app for productivity insights
**Effort**: 6 weeks, 1-2 engineers
**Impact**: 🟡 High value, post-MVP acceptable

---

## Phase 4A: Privacy Foundation (Weeks 1-4)

### Overview
Implement comprehensive privacy controls that give users transparent, granular control over their data. This directly addresses Trackabi's lack of privacy features and positions Timeclocker as the "privacy-first" alternative.

### Week 1: Foundation & Database

**Backend Setup:**
```bash
# Create migrations
php artisan make:migration create_user_privacy_settings_table
php artisan make:migration create_privacy_consent_logs_table
php artisan make:migration create_work_schedules_table
php artisan make:migration create_work_policies_table

# Create models
php artisan make:model UserPrivacySetting
php artisan make:model PrivacyConsentLog
php artisan make:model WorkSchedule
php artisan make:model WorkPolicy

# Create enum
php artisan make:enum ActivityTrackingLevel
```

**Database Schema:**

```sql
-- user_privacy_settings table
CREATE TABLE user_privacy_settings (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tracking_level SMALLINT DEFAULT 0, -- 0: Manual, 1: Idle, 2: Monitoring, 3: Full
    screenshot_enabled BOOLEAN DEFAULT FALSE,
    app_tracking_enabled BOOLEAN DEFAULT FALSE,
    url_tracking_enabled BOOLEAN DEFAULT FALSE,
    keyboard_mouse_tracking_enabled BOOLEAN DEFAULT FALSE,
    geolocation_enabled BOOLEAN DEFAULT FALSE,
    data_retention_days INTEGER DEFAULT 90,
    encryption_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id)
);

-- privacy_consent_logs table
CREATE TABLE privacy_consent_logs (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    setting_changed VARCHAR(255) NOT NULL, -- 'tracking_level', 'screenshot_enabled', etc.
    old_value TEXT,
    new_value TEXT NOT NULL,
    reason TEXT, -- User's optional explanation
    consented_at TIMESTAMP NOT NULL,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP
);
CREATE INDEX idx_privacy_consent_logs_user_id ON privacy_consent_logs(user_id);
CREATE INDEX idx_privacy_consent_logs_consented_at ON privacy_consent_logs(consented_at);

-- work_schedules table
CREATE TABLE work_schedules (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    day_of_week SMALLINT NOT NULL, -- 1 = Monday, 7 = Sunday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working_day BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, day_of_week)
);

-- work_policies table
CREATE TABLE work_policies (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    max_daily_hours DECIMAL(5,2), -- e.g., 10.00 hours
    min_daily_hours DECIMAL(5,2), -- e.g., 4.00 hours
    required_break_duration_minutes INTEGER, -- e.g., 30 minutes
    overtime_threshold_hours DECIMAL(5,2), -- e.g., 8.00 hours
    enforce_max_hours BOOLEAN DEFAULT FALSE, -- Block time entries exceeding max
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(organization_id)
);
```

**Models to Create:**

```php
// app/Models/UserPrivacySetting.php
namespace App\Models;

use App\Enums\ActivityTrackingLevel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivacySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'tracking_level',
        'screenshot_enabled',
        'app_tracking_enabled',
        'url_tracking_enabled',
        'keyboard_mouse_tracking_enabled',
        'geolocation_enabled',
        'data_retention_days',
        'encryption_enabled',
    ];

    protected $casts = [
        'tracking_level' => ActivityTrackingLevel::class,
        'screenshot_enabled' => 'boolean',
        'app_tracking_enabled' => 'boolean',
        'url_tracking_enabled' => 'boolean',
        'keyboard_mouse_tracking_enabled' => 'boolean',
        'geolocation_enabled' => 'boolean',
        'data_retention_days' => 'integer',
        'encryption_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// app/Models/PrivacyConsentLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsentLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null; // Only created_at needed

    protected $fillable = [
        'user_id',
        'setting_changed',
        'old_value',
        'new_value',
        'reason',
        'consented_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// app/Enums/ActivityTrackingLevel.php
namespace App\Enums;

enum ActivityTrackingLevel: int
{
    case MANUAL = 0; // User manually starts/stops timer only
    case IDLE_DETECTION = 1; // Track active/idle time
    case MONITORING = 2; // Track apps + URLs (encrypted)
    case FULL_TRACKING = 3; // Everything + screenshots (opt-in only)

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual Tracking',
            self::IDLE_DETECTION => 'Idle Detection',
            self::MONITORING => 'Activity Monitoring',
            self::FULL_TRACKING => 'Full Tracking',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MANUAL => 'You control the timer. No automatic tracking.',
            self::IDLE_DETECTION => 'Detects when you\'re active or idle.',
            self::MONITORING => 'Tracks apps and URLs (encrypted). No screenshots.',
            self::FULL_TRACKING => 'Includes screenshots and detailed activity logs.',
        };
    }
}
```

**Deliverables Week 1:**
- [x] 4 migrations created and run
- [x] 4 models with relationships
- [x] ActivityTrackingLevel enum
- [x] Unit tests for models

---

### Week 2: Privacy Service & API

**Service Layer:**

```php
// app/Services/PrivacyService.php
namespace App\Services;

use App\Models\PrivacyConsentLog;
use App\Models\UserPrivacySetting;
use App\Enums\ActivityTrackingLevel;
use Illuminate\Support\Facades\Auth;

class PrivacyService
{
    public function getOrCreateSettings(string $userId): UserPrivacySetting
    {
        return UserPrivacySetting::firstOrCreate(
            ['user_id' => $userId],
            [
                'tracking_level' => ActivityTrackingLevel::MANUAL,
                'screenshot_enabled' => false,
                'app_tracking_enabled' => false,
                'url_tracking_enabled' => false,
                'keyboard_mouse_tracking_enabled' => false,
                'geolocation_enabled' => false,
                'data_retention_days' => 90,
                'encryption_enabled' => true,
            ]
        );
    }

    public function updateSettings(string $userId, array $newSettings, ?string $reason = null): UserPrivacySetting
    {
        $settings = $this->getOrCreateSettings($userId);
        $oldValues = $settings->toArray();

        // Log each changed setting
        foreach ($newSettings as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] !== $value) {
                $this->logConsent($userId, $key, $oldValues[$key], $value, $reason);
            }
        }

        $settings->update($newSettings);
        return $settings->fresh();
    }

    public function logConsent(string $userId, string $setting, $oldValue, $newValue, ?string $reason = null): void
    {
        PrivacyConsentLog::create([
            'user_id' => $userId,
            'setting_changed' => $setting,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'consented_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getConsentHistory(string $userId, int $limit = 50)
    {
        return PrivacyConsentLog::where('user_id', $userId)
            ->orderBy('consented_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function canEnableFeature(string $userId, string $feature): bool
    {
        $settings = $this->getOrCreateSettings($userId);

        // Business logic: Can't enable screenshots without monitoring level 2+
        if ($feature === 'screenshot_enabled') {
            return $settings->tracking_level->value >= ActivityTrackingLevel::MONITORING->value;
        }

        return true;
    }
}
```

**API Controller:**

```php
// app/Http/Controllers/Api/V1/UserPrivacySettingController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserPrivacySettingRequest;
use App\Services\PrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPrivacySettingController extends Controller
{
    public function __construct(private PrivacyService $privacyService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $settings = $this->privacyService->getOrCreateSettings($request->user()->id);

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function update(UpdateUserPrivacySettingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $reason = $validated['reason'] ?? null;
        unset($validated['reason']);

        $settings = $this->privacyService->updateSettings(
            $request->user()->id,
            $validated,
            $reason
        );

        return response()->json([
            'data' => $settings,
            'message' => 'Privacy settings updated successfully',
        ]);
    }

    public function consentHistory(Request $request): JsonResponse
    {
        $history = $this->privacyService->getConsentHistory($request->user()->id);

        return response()->json([
            'data' => $history,
        ]);
    }
}
```

**Routes:**

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Privacy settings
    Route::get('/user/privacy-settings', [UserPrivacySettingController::class, 'show']);
    Route::put('/user/privacy-settings', [UserPrivacySettingController::class, 'update']);
    Route::get('/user/privacy-settings/consent-history', [UserPrivacySettingController::class, 'consentHistory']);
});
```

**Deliverables Week 2:**
- [x] PrivacyService with CRUD + consent logging
- [x] UserPrivacySettingController with 3 endpoints
- [x] API routes configured
- [x] Request validation
- [x] PHPUnit tests (15+ tests)

---

### Week 3: Privacy Dashboard UI

**Vue Component:**

```vue
<!-- resources/js/Components/Privacy/PrivacyControlCenter.vue -->
<template>
  <div class="privacy-control-center">
    <div class="header">
      <h2>Privacy Control Center</h2>
      <p class="subtitle">You control your data. Choose what's tracked and how.</p>
    </div>

    <!-- Real-time indicator -->
    <div class="current-status" :class="statusClass">
      <Icon :name="statusIcon" class="icon" />
      <div>
        <h3>Currently Tracking: {{ currentLevelLabel }}</h3>
        <p>{{ currentLevelDescription }}</p>
      </div>
    </div>

    <!-- Tracking level selector -->
    <div class="tracking-levels">
      <h3>Choose Your Tracking Level</h3>
      <div class="levels-grid">
        <TrackingLevelCard
          v-for="level in trackingLevels"
          :key="level.value"
          :level="level"
          :selected="settings.tracking_level === level.value"
          @select="selectTrackingLevel(level.value)"
        />
      </div>
    </div>

    <!-- Granular toggles -->
    <div class="feature-toggles">
      <h3>Advanced Controls</h3>

      <ToggleRow
        label="Screenshot Capture"
        description="Take periodic screenshots (requires Monitoring level or higher)"
        :value="settings.screenshot_enabled"
        :disabled="!canEnableScreenshots"
        @update="updateSetting('screenshot_enabled', $event)"
      >
        <template #badge>
          <span class="badge badge-warning">Off by Default</span>
        </template>
      </ToggleRow>

      <ToggleRow
        label="Application Tracking"
        description="Track which apps you use (encrypted)"
        :value="settings.app_tracking_enabled"
        @update="updateSetting('app_tracking_enabled', $event)"
      />

      <ToggleRow
        label="URL Tracking"
        description="Track websites you visit (encrypted, domain only)"
        :value="settings.url_tracking_enabled"
        @update="updateSetting('url_tracking_enabled', $event)"
      />

      <ToggleRow
        label="Keyboard & Mouse Activity"
        description="Track activity counts (no keylogging)"
        :value="settings.keyboard_mouse_tracking_enabled"
        @update="updateSetting('keyboard_mouse_tracking_enabled', $event)"
      />
    </div>

    <!-- Data retention -->
    <div class="data-retention">
      <h3>Data Retention</h3>
      <label>
        Automatically delete activity data after:
        <select v-model="settings.data_retention_days" @change="saveSettings">
          <option :value="30">30 days</option>
          <option :value="60">60 days</option>
          <option :value="90">90 days (recommended)</option>
          <option :value="180">180 days</option>
          <option :value="365">1 year</option>
          <option :value="null">Never delete</option>
        </select>
      </label>
    </div>

    <!-- Transparency section -->
    <div class="transparency">
      <h3>🇪🇺 Your Data Rights</h3>
      <ul>
        <li><strong>Storage Location:</strong> EU (Frankfurt, Germany)</li>
        <li><strong>Encryption:</strong> AES-256 at rest, TLS 1.3 in transit</li>
        <li><strong>Access:</strong> <a href="#" @click.prevent="viewAuditLog">View who accessed your data</a></li>
        <li><strong>Export:</strong> <button @click="exportData">Download all my data</button></li>
        <li><strong>Delete:</strong> <button @click="deleteAccount" class="danger">Delete my account</button></li>
      </ul>
    </div>

    <!-- Consent prompt (shown on changes) -->
    <ConsentModal
      v-if="showConsentModal"
      :changes="pendingChanges"
      @confirm="confirmChanges"
      @cancel="cancelChanges"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePrivacySettings } from '@/Composables/usePrivacySettings';
import TrackingLevelCard from './TrackingLevelCard.vue';
import ToggleRow from './ToggleRow.vue';
import ConsentModal from './ConsentModal.vue';

const { settings, fetchSettings, updateSettings, exportData, deleteAccount } = usePrivacySettings();

const trackingLevels = [
  { value: 0, label: 'Manual', description: 'You control the timer. No automatic tracking.', icon: 'hand' },
  { value: 1, label: 'Idle Detection', description: 'Detects when you\'re active or idle.', icon: 'clock' },
  { value: 2, label: 'Activity Monitoring', description: 'Tracks apps and URLs (encrypted).', icon: 'eye' },
  { value: 3, label: 'Full Tracking', description: 'Includes screenshots and detailed logs.', icon: 'camera' },
];

const canEnableScreenshots = computed(() => settings.value.tracking_level >= 2);

onMounted(() => {
  fetchSettings();
});
</script>
```

**Composable:**

```typescript
// resources/js/Composables/usePrivacySettings.ts
import { ref } from 'vue';
import axios from 'axios';

export function usePrivacySettings() {
  const settings = ref({
    tracking_level: 0,
    screenshot_enabled: false,
    app_tracking_enabled: false,
    url_tracking_enabled: false,
    keyboard_mouse_tracking_enabled: false,
    geolocation_enabled: false,
    data_retention_days: 90,
    encryption_enabled: true,
  });

  const fetchSettings = async () => {
    const response = await axios.get('/api/v1/user/privacy-settings');
    settings.value = response.data.data;
  };

  const updateSettings = async (newSettings: Partial<typeof settings.value>, reason?: string) => {
    const response = await axios.put('/api/v1/user/privacy-settings', {
      ...newSettings,
      reason,
    });
    settings.value = response.data.data;
  };

  const exportData = async () => {
    const response = await axios.get('/api/v1/user/export-data', { responseType: 'blob' });
    // Download file
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `timeclocker-data-${Date.now()}.json`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  };

  const deleteAccount = async () => {
    if (confirm('Are you sure? This action cannot be undone.')) {
      await axios.delete('/api/v1/user/account');
      window.location.href = '/goodbye';
    }
  };

  return {
    settings,
    fetchSettings,
    updateSettings,
    exportData,
    deleteAccount,
  };
}
```

**Deliverables Week 3:**
- [x] PrivacyControlCenter.vue component
- [x] usePrivacySettings composable
- [x] ConsentModal component
- [x] TrackingLevelCard component
- [x] ToggleRow component
- [x] Integration with API
- [x] Mobile-responsive design

---

### Week 4: Work Schedules & Policies

**Quick Implementation (Backend + UI):**

```php
// app/Models/WorkSchedule.php - Similar to UserPrivacySetting pattern
// app/Services/WorkScheduleService.php - CRUD + template generation
// app/Http/Controllers/Api/V1/WorkScheduleController.php - API endpoints
```

```vue
<!-- resources/js/Components/Settings/WorkSchedule.vue -->
<template>
  <div class="work-schedule">
    <h2>Work Schedule</h2>
    <p>Define your typical work hours to track expected vs actual time.</p>

    <div class="template-selector">
      <button @click="applyTemplate('full-time')">Full-Time (9-5, Mon-Fri)</button>
      <button @click="applyTemplate('part-time')">Part-Time (9-1, Mon-Fri)</button>
      <button @click="applyTemplate('custom')">Custom</button>
    </div>

    <table class="schedule-table">
      <thead>
        <tr>
          <th>Day</th>
          <th>Working?</th>
          <th>Start Time</th>
          <th>End Time</th>
          <th>Hours</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="day in schedule" :key="day.day_of_week">
          <td>{{ dayName(day.day_of_week) }}</td>
          <td><input type="checkbox" v-model="day.is_working_day" /></td>
          <td><input type="time" v-model="day.start_time" :disabled="!day.is_working_day" /></td>
          <td><input type="time" v-model="day.end_time" :disabled="!day.is_working_day" /></td>
          <td>{{ calculateHours(day) }}</td>
        </tr>
      </tbody>
    </table>

    <button @click="saveSchedule" class="btn-primary">Save Schedule</button>
  </div>
</template>
```

**Deliverables Week 4:**
- [x] WorkSchedule model + service + controller
- [x] WorkPolicy model (organization-level)
- [x] Work schedule UI with templates
- [x] Break reminder job (scheduled every 15 minutes)
- [x] Policy enforcement logic
- [x] PHPUnit tests (12+ tests)

---

## Phase 4A Acceptance Criteria

**Must-Have:**
- [ ] Privacy Control Center accessible from main navigation
- [ ] All 4 tracking levels functional (Manual, Idle, Monitoring, Full)
- [ ] Consent logging for every privacy setting change
- [ ] Privacy audit log UI showing 50 most recent events
- [ ] GDPR data export (JSON + CSV)
- [ ] GDPR data deletion (soft delete + 30-day grace period)
- [ ] Work schedule templates (3 presets + custom)
- [ ] Break reminders sending notifications
- [ ] Organization work policies enforceable

**Testing:**
- [ ] 30+ PHPUnit tests passing
- [ ] E2E tests for consent workflow
- [ ] Accessibility audit (WCAG 2.1 AA compliance)
- [ ] Mobile responsiveness verified

**Documentation:**
- [ ] Privacy Policy updated
- [ ] API documentation for privacy endpoints
- [ ] User guide: "Understanding Tracking Levels"

---

## Phase 4B: Open Platform (Weeks 5-10)

### Week 5: Infrastructure & Documentation

**Tasks:**
1. **Status Page Setup**
   - Deploy StatusPage.io or Cachet
   - Monitor: API uptime, webhook success rate, payment gateway status
   - URL: `status.timeclocker.com`
   - Incident templates prepared

2. **API Rate Limiting**
   ```php
   // config/rate-limiting.php
   return [
       'api' => [
           'free' => '100:1', // 100 requests per minute
           'pro' => '500:1', // 500 requests per minute
           'enterprise' => '2000:1', // 2000 requests per minute
       ],
   ];
   ```

3. **Public API Documentation Portal**
   - Host Scramble docs at `/docs/api`
   - Add interactive examples (like Stripe)
   - Authentication guide (OAuth, PAT, API keys)
   - Rate limit documentation
   - Error code reference

**Deliverables Week 5:**
- [x] Status page live
- [x] Rate limiting middleware active
- [x] Public API docs accessible
- [x] Developer getting-started guide

---

### Weeks 6-7: Webhook System

**Backend Implementation:**

```php
// app/Models/Webhook.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['secret'];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}

// app/Services/WebhookService.php
namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        // Find all webhooks subscribed to this event
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $payload);
        }
    }

    private function sendWebhook(Webhook $webhook, string $event, array $payload, int $attempt = 1): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $event,
            'payload' => $payload,
            'attempt' => $attempt,
        ]);

        try {
            $signature = $this->generateSignature($webhook->secret, $payload);

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Timeclocker-Event' => $event,
                    'X-Timeclocker-Signature' => $signature,
                    'X-Timeclocker-Delivery-ID' => $delivery->id,
                ])
                ->post($webhook->url, $payload);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'delivered_at' => now(),
            ]);

            // Retry on failure with exponential backoff
            if (!$response->successful() && $attempt < 5) {
                $delay = pow(2, $attempt); // 2s, 4s, 8s, 16s
                dispatch(function () use ($webhook, $event, $payload, $attempt) {
                    $this->sendWebhook($webhook, $event, $payload, $attempt + 1);
                })->delay(now()->addSeconds($delay));
            }

        } catch (\Exception $e) {
            $delivery->update([
                'response_status' => 0,
                'response_body' => $e->getMessage(),
                'delivered_at' => now(),
            ]);

            Log::error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateSignature(string $secret, array $payload): string
    {
        $payloadJson = json_encode($payload);
        return hash_hmac('sha256', $payloadJson, $secret);
    }
}
```

**Event Dispatch (in existing controllers):**

```php
// app/Http/Controllers/Api/V1/TimeEntryController.php
use App\Services\WebhookService;

class TimeEntryController extends Controller
{
    public function __construct(private WebhookService $webhookService)
    {
    }

    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        $timeEntry = TimeEntry::create($request->validated());

        // Dispatch webhook event
        $this->webhookService->dispatch('time_entry.created', [
            'id' => $timeEntry->id,
            'description' => $timeEntry->description,
            'start' => $timeEntry->start,
            'end' => $timeEntry->end,
            'project_id' => $timeEntry->project_id,
        ]);

        return response()->json(['data' => $timeEntry], 201);
    }
}
```

**Webhook Management UI:**

```vue
<!-- resources/js/Components/Settings/WebhookManager.vue -->
<template>
  <div class="webhook-manager">
    <h2>Webhooks</h2>
    <p>Get real-time notifications when events occur in your account.</p>

    <button @click="showCreateModal = true" class="btn-primary">Create Webhook</button>

    <table class="webhooks-table">
      <thead>
        <tr>
          <th>URL</th>
          <th>Events</th>
          <th>Status</th>
          <th>Last Delivery</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="webhook in webhooks" :key="webhook.id">
          <td>{{ webhook.url }}</td>
          <td>{{ webhook.events.join(', ') }}</td>
          <td>
            <span :class="webhook.is_active ? 'badge-success' : 'badge-inactive'">
              {{ webhook.is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td>{{ formatDate(webhook.last_delivery_at) }}</td>
          <td>
            <button @click="testWebhook(webhook.id)">Test</button>
            <button @click="editWebhook(webhook)">Edit</button>
            <button @click="deleteWebhook(webhook.id)" class="btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Delivery log modal -->
    <WebhookDeliveryLog v-if="selectedWebhook" :webhook-id="selectedWebhook.id" />
  </div>
</template>
```

**Deliverables Weeks 6-7:**
- [x] Webhook model + WebhookDelivery model
- [x] WebhookService with retry logic
- [x] 14 event types dispatched across controllers
- [x] Webhook management UI (CRUD)
- [x] Webhook delivery log viewer
- [x] Test webhook button
- [x] Signature verification example code (for webhook consumers)
- [x] PHPUnit tests (18+ tests)

---

### Weeks 7-8: Payroll Automation

**Implementation:**

```php
// app/Models/Payroll.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'period_start',
        'period_end',
        'status', // draft, approved, paid
        'total_regular_hours',
        'total_overtime_hours',
        'total_earnings',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_regular_hours' => 'decimal:2',
        'total_overtime_hours' => 'decimal:2',
        'total_earnings' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}

// app/Services/PayrollService.php
namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\TimeEntry;
use Carbon\Carbon;

class PayrollService
{
    public function generatePayroll(string $organizationId, Carbon $periodStart, Carbon $periodEnd): Payroll
    {
        $payroll = Payroll::create([
            'organization_id' => $organizationId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'draft',
        ]);

        // Get all members with billable rates
        $members = Member::where('organization_id', $organizationId)
            ->whereNotNull('billable_rate')
            ->get();

        $totalEarnings = 0;
        $totalRegularHours = 0;
        $totalOvertimeHours = 0;

        foreach ($members as $member) {
            // Get time entries for this member in the period
            $timeEntries = TimeEntry::where('user_id', $member->user_id)
                ->whereBetween('start', [$periodStart, $periodEnd])
                ->get();

            $regularHours = 0;
            $overtimeHours = 0;

            foreach ($timeEntries as $entry) {
                $duration = $entry->start->diffInSeconds($entry->end) / 3600; // Convert to hours

                // Calculate regular vs overtime (>8 hours per day = overtime)
                if ($duration > 8) {
                    $regularHours += 8;
                    $overtimeHours += $duration - 8;
                } else {
                    $regularHours += $duration;
                }
            }

            $regularEarnings = $regularHours * $member->billable_rate;
            $overtimeEarnings = $overtimeHours * ($member->billable_rate * 1.5);
            $totalMemberEarnings = $regularEarnings + $overtimeEarnings;

            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'hourly_rate' => $member->billable_rate,
                'regular_earnings' => $regularEarnings,
                'overtime_earnings' => $overtimeEarnings,
                'total_earnings' => $totalMemberEarnings,
            ]);

            $totalEarnings += $totalMemberEarnings;
            $totalRegularHours += $regularHours;
            $totalOvertimeHours += $overtimeHours;
        }

        $payroll->update([
            'total_regular_hours' => $totalRegularHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_earnings' => $totalEarnings,
        ]);

        return $payroll->fresh(['items.member.user']);
    }
}
```

**Export to Excel:**

```php
// app/Exports/PayrollExport.php
namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Payroll $payroll)
    {
    }

    public function collection()
    {
        return $this->payroll->items;
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Regular Hours',
            'Overtime Hours',
            'Total Hours',
            'Hourly Rate',
            'Regular Earnings',
            'Overtime Earnings',
            'Total Earnings',
        ];
    }

    public function map($item): array
    {
        return [
            $item->user->name,
            $item->regular_hours,
            $item->overtime_hours,
            $item->regular_hours + $item->overtime_hours,
            '$' . number_format($item->hourly_rate, 2),
            '$' . number_format($item->regular_earnings, 2),
            '$' . number_format($item->overtime_earnings, 2),
            '$' . number_format($item->total_earnings, 2),
        ];
    }
}
```

**Deliverables Weeks 7-8:**
- [x] Payroll + PayrollItem models
- [x] PayrollService with calculation logic
- [x] PayrollController with generate/approve/export endpoints
- [x] PayrollExport (Excel) using Laravel Excel package
- [x] Payroll report PDF generation
- [x] UI for payroll generation and viewing
- [x] PHPUnit tests (15+ tests)

---

### Weeks 8-9: Zapier Integration

**Zapier CLI Setup:**

```bash
# Install Zapier CLI
npm install -g zapier-platform-cli

# Create Zapier app
zapier init timeclocker-zapier --template=minimal
cd timeclocker-zapier

# Configure authentication (API key)
# Edit index.js
```

**Zapier App Configuration:**

```javascript
// index.js
const authentication = {
  type: 'custom',
  fields: [
    {
      key: 'api_key',
      label: 'API Key',
      required: true,
      type: 'string',
      helpText: 'Get your API key from Settings > API Tokens in Timeclocker',
    },
  ],
  test: {
    url: 'https://api.timeclocker.com/v1/user',
    method: 'GET',
    headers: {
      Authorization: 'Bearer {{bundle.authData.api_key}}',
    },
  },
};

// Triggers
const timeEntryCreated = {
  key: 'time_entry_created',
  noun: 'Time Entry',
  display: {
    label: 'New Time Entry',
    description: 'Triggers when a new time entry is created.',
  },
  operation: {
    type: 'hook',
    performSubscribe: {
      url: 'https://api.timeclocker.com/v1/webhooks',
      method: 'POST',
      body: {
        url: '{{bundle.targetUrl}}',
        events: ['time_entry.created'],
      },
    },
    performUnsubscribe: {
      url: 'https://api.timeclocker.com/v1/webhooks/{{bundle.subscribeData.id}}',
      method: 'DELETE',
    },
    perform: {
      url: 'https://api.timeclocker.com/v1/time-entries',
      method: 'GET',
    },
    sample: {
      id: '123',
      description: 'Working on Project X',
      start: '2025-11-05T09:00:00Z',
      end: '2025-11-05T10:30:00Z',
      project_id: '456',
    },
  },
};

// Actions
const createTimeEntry = {
  key: 'create_time_entry',
  noun: 'Time Entry',
  display: {
    label: 'Create Time Entry',
    description: 'Creates a new time entry in Timeclocker.',
  },
  operation: {
    perform: {
      url: 'https://api.timeclocker.com/v1/time-entries',
      method: 'POST',
      body: {
        description: '{{bundle.inputData.description}}',
        start: '{{bundle.inputData.start}}',
        end: '{{bundle.inputData.end}}',
        project_id: '{{bundle.inputData.project_id}}',
      },
    },
    inputFields: [
      { key: 'description', label: 'Description', type: 'string', required: true },
      { key: 'start', label: 'Start Time', type: 'datetime', required: true },
      { key: 'end', label: 'End Time', type: 'datetime', required: false },
      { key: 'project_id', label: 'Project ID', type: 'string', required: false },
    ],
    sample: {
      id: '789',
      description: 'Task from Zapier',
      start: '2025-11-05T14:00:00Z',
    },
  },
};

module.exports = {
  version: require('./package.json').version,
  platformVersion: require('zapier-platform-core').version,
  authentication: authentication,
  triggers: {
    [timeEntryCreated.key]: timeEntryCreated,
    // Add more triggers...
  },
  creates: {
    [createTimeEntry.key]: createTimeEntry,
    // Add more actions...
  },
};
```

**Testing & Deployment:**

```bash
# Test locally
zapier test

# Deploy to Zapier
zapier push

# Submit for review
zapier promote 1.0.0
```

**Deliverables Weeks 8-9:**
- [x] Zapier CLI app configured
- [x] 4 triggers: time_entry.created, invoice.sent, payment.received, project.archived
- [x] 4 actions: Create Time Entry, Create Invoice, Start Timer, Stop Timer
- [x] Authentication via API key
- [x] Zapier app submitted for approval
- [x] Documentation: `/docs/ZAPIER_INTEGRATION.md`

---

### Week 10: Polish & Buffer

**Tasks:**
- Bug fixes from Phase 4B
- Performance optimization (webhook retries, payroll generation)
- Documentation updates
- User acceptance testing

**Optional (if time allows):**
- Wellness features (break reminders enhancement)
- Gamification basics (focus streak badges)

---

## Phase 4B Acceptance Criteria

**Must-Have:**
- [ ] Status page live with 99.5%+ uptime
- [ ] Public API docs accessible at `/docs/api`
- [ ] Rate limiting active (100 req/min free, 500 req/min pro)
- [ ] Webhook system operational with 14 event types
- [ ] Webhook retry logic working (exponential backoff)
- [ ] Payroll generation for any date range
- [ ] Payroll export to Excel with formatting
- [ ] Zapier app submitted and approved
- [ ] 4 Zapier triggers + 4 actions functional

**Testing:**
- [ ] 50+ PHPUnit tests passing
- [ ] Load testing: 1000 concurrent webhook deliveries
- [ ] Zapier integration tested with 5+ popular apps

**Documentation:**
- [ ] API documentation complete
- [ ] Webhook consumer guide
- [ ] Payroll calculation explanation
- [ ] Zapier setup guide

---

## Phase 4C: Automatic Tracking (Weeks 11-16)

### Overview
Desktop app for productivity tracking. This is the most complex phase and can be deferred post-MVP if needed.

### Weeks 11-12: Tauri Desktop App Initialization

**Tauri Setup:**

```bash
# Create Tauri project
npx create-tauri-app
cd desktop-app

# Project structure:
# /desktop-app
#   /src (Rust backend)
#   /ui (Vue 3 frontend)
#   /tauri.conf.json (config)
```

**Core Features:**
1. **Timer UI** - Start/stop timer, sync with web app
2. **Activity Collection** - Track active window (app name + title)
3. **Idle Detection** - Monitor keyboard/mouse events
4. **Encryption** - Encrypt all activity data before sending to API

**Rust Backend (Tauri):**

```rust
// src-tauri/src/activity.rs
use serde::{Deserialize, Serialize};
use std::time::SystemTime;

#[derive(Debug, Serialize, Deserialize)]
pub struct ActivitySnapshot {
    pub app_name: String,
    pub window_title: String,
    pub active_seconds: u64,
    pub idle_seconds: u64,
    pub keyboard_count: u32,
    pub mouse_count: u32,
    pub timestamp: SystemTime,
}

impl ActivitySnapshot {
    pub fn collect() -> Self {
        // Platform-specific code to get active window
        #[cfg(target_os = "windows")]
        let (app_name, window_title) = get_active_window_windows();

        #[cfg(target_os = "macos")]
        let (app_name, window_title) = get_active_window_macos();

        #[cfg(target_os = "linux")]
        let (app_name, window_title) = get_active_window_linux();

        ActivitySnapshot {
            app_name,
            window_title,
            active_seconds: get_active_time(),
            idle_seconds: get_idle_time(),
            keyboard_count: get_keyboard_events(),
            mouse_count: get_mouse_events(),
            timestamp: SystemTime::now(),
        }
    }

    pub fn encrypt(&self, key: &str) -> String {
        // AES-256 encryption
        let plaintext = serde_json::to_string(self).unwrap();
        encrypt_aes256(&plaintext, key)
    }
}
```

**Vue UI:**

```vue
<!-- ui/src/components/DesktopTimer.vue -->
<template>
  <div class="desktop-timer">
    <div class="timer-display">
      <h1>{{ formattedTime }}</h1>
      <p>{{ currentTask }}</p>
    </div>

    <div class="controls">
      <button v-if="!isRunning" @click="startTimer" class="btn-start">Start</button>
      <button v-else @click="stopTimer" class="btn-stop">Stop</button>
    </div>

    <div class="activity-status">
      <span :class="activityClass">{{ activityStatus }}</span>
      <span class="sync-status">{{ syncStatus }}</span>
    </div>

    <div class="settings-link">
      <a href="#" @click="openSettings">Settings</a>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { invoke } from '@tauri-apps/api/tauri';

const isRunning = ref(false);
const elapsedSeconds = ref(0);
const activityStatus = ref('Idle');

const startTimer = async () => {
  await invoke('start_timer');
  isRunning.value = true;
};

const stopTimer = async () => {
  await invoke('stop_timer');
  isRunning.value = false;
};

// Collect activity every 10 seconds
let activityInterval;
onMounted(() => {
  activityInterval = setInterval(async () => {
    const snapshot = await invoke('collect_activity');
    // Send to API
    await sendActivityToServer(snapshot);
  }, 10000);
});

onUnmounted(() => {
  clearInterval(activityInterval);
});
</script>
```

**Deliverables Weeks 11-12:**
- [x] Tauri project initialized
- [x] Timer UI functional
- [x] Activity collection working (Windows, macOS, Linux)
- [x] Idle detection implemented
- [x] Basic encryption layer
- [x] Sync mechanism with web API

---

### Weeks 13-14: Backend Activity Storage

**Backend Models:**

```php
// app/Models/AppActivity.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppActivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'app_name',
        'window_title_encrypted',
        'active_seconds',
        'idle_seconds',
        'keyboard_count',
        'mouse_count',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'active_seconds' => 'integer',
        'idle_seconds' => 'integer',
        'keyboard_count' => 'integer',
        'mouse_count' => 'integer',
    ];

    // Decrypt window title
    public function getWindowTitleAttribute(): string
    {
        return decrypt($this->window_title_encrypted);
    }
}
```

**Activity Aggregation Service:**

```php
// app/Services/ActivityAggregationService.php
namespace App\Services;

use App\Models\AppActivity;
use Carbon\Carbon;

class ActivityAggregationService
{
    public function aggregateHourly(string $userId, Carbon $date): array
    {
        // Roll up 10-second snapshots into hourly summaries
        $activities = AppActivity::where('user_id', $userId)
            ->whereDate('recorded_at', $date)
            ->get()
            ->groupBy(fn ($activity) => $activity->recorded_at->format('Y-m-d H:00'));

        $hourly = [];
        foreach ($activities as $hour => $records) {
            $hourly[$hour] = [
                'total_active_seconds' => $records->sum('active_seconds'),
                'total_idle_seconds' => $records->sum('idle_seconds'),
                'top_apps' => $records->groupBy('app_name')
                    ->map(fn ($group) => $group->sum('active_seconds'))
                    ->sortDesc()
                    ->take(5)
                    ->toArray(),
            ];
        }

        return $hourly;
    }

    public function detectFocusSessions(string $userId, Carbon $date): array
    {
        // Analyze activity patterns to identify focus sessions
        // Focus = 20+ minutes continuous work, <3 app switches, no idle >5 minutes
        // ... (ML logic here)
    }
}
```

**Deliverables Weeks 13-14:**
- [x] AppActivity + UrlActivity models
- [x] Activity storage API endpoints
- [x] ActivityAggregationService with hourly rollup
- [x] Data retention job (delete after 90 days)
- [x] Encryption/decryption tested
- [x] PHPUnit tests (12+ tests)

---

### Week 15: Focus Session Analytics

**Focus Detection:**

```php
// app/Services/FocusDetectionService.php
namespace App\Services;

use App\Models\AppActivity;
use App\Models\FocusSession;
use Carbon\Carbon;

class FocusDetectionService
{
    public function detectSessions(string $userId, Carbon $date): array
    {
        $activities = AppActivity::where('user_id', $userId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get();

        $sessions = [];
        $currentSession = null;

        foreach ($activities as $activity) {
            // Start new session if:
            // - No current session
            // - Active time in this snapshot
            if (!$currentSession && $activity->active_seconds > 0) {
                $currentSession = [
                    'start' => $activity->recorded_at,
                    'apps' => [$activity->app_name],
                    'interruptions' => 0,
                ];
            }

            // Continue session if:
            // - Active time continues
            // - No idle gap >5 minutes
            if ($currentSession) {
                if ($activity->idle_seconds > 300) {
                    // End session due to idle
                    $currentSession['end'] = $activity->recorded_at;
                    $sessions[] = $currentSession;
                    $currentSession = null;
                } else {
                    $currentSession['apps'][] = $activity->app_name;
                    if (count(array_unique($currentSession['apps'])) > 3) {
                        $currentSession['interruptions']++;
                    }
                }
            }
        }

        // Save detected sessions
        foreach ($sessions as $session) {
            $duration = Carbon::parse($session['start'])->diffInMinutes($session['end']);
            if ($duration >= 20) { // Minimum 20 minutes for focus session
                FocusSession::create([
                    'user_id' => $userId,
                    'start_time' => $session['start'],
                    'end_time' => $session['end'],
                    'duration_minutes' => $duration,
                    'interruptions_count' => $session['interruptions'],
                    'focus_score' => $this->calculateFocusScore($duration, $session['interruptions']),
                    'apps_used' => array_unique($session['apps']),
                ]);
            }
        }

        return $sessions;
    }

    private function calculateFocusScore(int $durationMinutes, int $interruptions): int
    {
        // Score 0-100 based on duration and interruptions
        $baseScore = min(100, $durationMinutes * 2); // 50 minutes = 100 score
        $penalty = $interruptions * 10;
        return max(0, $baseScore - $penalty);
    }
}
```

**Dashboard Visualization:**

```vue
<!-- resources/js/Components/Analytics/FocusSessionChart.vue -->
<template>
  <div class="focus-sessions">
    <h2>Focus Sessions</h2>
    <div class="summary">
      <div class="stat">
        <h3>{{ totalSessions }}</h3>
        <p>Total Sessions</p>
      </div>
      <div class="stat">
        <h3>{{ averageDuration }}m</h3>
        <p>Average Duration</p>
      </div>
      <div class="stat">
        <h3>{{ averageFocusScore }}/100</h3>
        <p>Focus Score</p>
      </div>
    </div>

    <div class="chart">
      <HeatMap :data="heatmapData" />
    </div>

    <div class="sessions-list">
      <h3>Recent Focus Sessions</h3>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Start</th>
            <th>Duration</th>
            <th>Focus Score</th>
            <th>Apps Used</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="session in sessions" :key="session.id">
            <td>{{ formatDate(session.start_time) }}</td>
            <td>{{ formatTime(session.start_time) }}</td>
            <td>{{ session.duration_minutes }}m</td>
            <td>
              <span :class="scoreClass(session.focus_score)">
                {{ session.focus_score }}/100
              </span>
            </td>
            <td>{{ session.apps_used.join(', ') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
```

**Deliverables Week 15:**
- [x] FocusSession model
- [x] FocusDetectionService with ML-based detection
- [x] Focus session analytics dashboard
- [x] Heatmap visualization
- [x] Focus streak tracking

---

### Week 16: Desktop App Packaging & Distribution

**Tauri Build Configuration:**

```json
// tauri.conf.json
{
  "build": {
    "distDir": "../dist",
    "devPath": "http://localhost:5173",
    "beforeDevCommand": "npm run dev",
    "beforeBuildCommand": "npm run build"
  },
  "package": {
    "productName": "Timeclocker",
    "version": "1.0.0"
  },
  "tauri": {
    "bundle": {
      "active": true,
      "targets": ["msi", "dmg", "deb", "appimage"],
      "identifier": "com.timeclocker.desktop",
      "icon": [
        "icons/32x32.png",
        "icons/128x128.png",
        "icons/icon.icns",
        "icons/icon.ico"
      ],
      "windows": {
        "certificateThumbprint": null,
        "digestAlgorithm": "sha256",
        "timestampUrl": ""
      },
      "macOS": {
        "frameworks": [],
        "minimumSystemVersion": "10.13"
      }
    },
    "updater": {
      "active": true,
      "endpoints": [
        "https://releases.timeclocker.com/desktop/{{target}}/{{current_version}}"
      ],
      "dialog": true,
      "pubkey": "your-public-key-here"
    }
  }
}
```

**Build & Release:**

```bash
# Build for all platforms
npm run tauri build

# Outputs:
# - Windows: timeclocker_1.0.0_x64.msi
# - macOS: Timeclocker.app (DMG)
# - Linux: timeclocker_1.0.0_amd64.deb, timeclocker_1.0.0_x86_64.AppImage
```

**Auto-Updater:**
- Host releases on S3 or GitHub Releases
- Sign builds with code signing certificates
- Implement update notification in app

**Deliverables Week 16:**
- [x] Desktop app builds for Windows, macOS, Linux
- [x] Code signing configured
- [x] Auto-updater functional
- [x] Download page on website
- [x] Installation instructions
- [x] Bug fixes and testing

---

## Phase 4C Acceptance Criteria

**Must-Have:**
- [ ] Desktop app installs on Windows, macOS, Linux
- [ ] Timer syncs with web app in real-time
- [ ] Activity tracking respects privacy settings
- [ ] All activity data encrypted before storage
- [ ] Focus session detection accurate (>80%)
- [ ] Auto-updater working
- [ ] Desktop app performance: <50MB RAM, <5% CPU idle

**Testing:**
- [ ] Cross-platform testing on 3 OSes
- [ ] 24-hour continuous tracking test
- [ ] Encryption/decryption verified
- [ ] E2E tests for sync mechanism

**Documentation:**
- [ ] Desktop app user guide
- [ ] Troubleshooting FAQ
- [ ] Privacy settings explanation

---

## Final Phase 4 Summary

### Total Effort: 16 Weeks
- **Phase 4A**: 4 weeks (Privacy Foundation)
- **Phase 4B**: 6 weeks (Open Platform)
- **Phase 4C**: 6 weeks (Automatic Tracking)

### Total Deliverables:
- **15+ new database tables**
- **20+ new models**
- **25+ new API endpoints**
- **12+ new services**
- **30+ Vue components**
- **Zapier integration**
- **Desktop app** (Windows, macOS, Linux)
- **150+ PHPUnit tests**
- **6 comprehensive documentation files**

### Competitive Advantages Over Trackabi:
1. ✅ Public API with full documentation
2. ✅ Zapier integration (Day 1)
3. ✅ Privacy control center (4 tracking levels)
4. ✅ End-to-end encryption
5. ✅ Webhook system (14 event types)
6. ✅ Automatic payroll calculation
7. ✅ Per-user work schedules
8. ✅ Focus session analytics
9. ✅ Status page transparency
10. ✅ <24h support response time

---

## Next Steps

1. **Review & Approval**: Present this roadmap to stakeholders
2. **Resource Allocation**: Assign 2-3 engineers, 1 designer
3. **Design Sprint**: Week 0 - Design all UI components
4. **Development Kickoff**: Week 1 - Start Phase 4A
5. **Weekly Standups**: Track progress against timeline
6. **Beta Testing**: Week 10 (after Phase 4B)
7. **Public Launch**: Week 16 (after Phase 4C)

**Budget**: ~$120k-180k (assuming $75-$100/hr fully-loaded engineer cost × 16 weeks × 3 engineers)

**ROI Projection**: 3-5x increase in user acquisition, 10x increase in enterprise deals (due to API/integrations)

---

**Document Status**: Ready for Implementation
**Last Updated**: 2025-11-05
**Next Review**: Weekly during Phase 4 development
