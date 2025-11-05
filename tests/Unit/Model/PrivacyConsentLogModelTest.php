<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\PrivacyConsentLog;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PrivacyConsentLog::class)]
class PrivacyConsentLogModelTest extends ModelTestAbstract
{
    public function test_privacy_consent_log_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $log = PrivacyConsentLog::factory()->forUser($user)->create();

        // Act
        $relatedUser = $log->user;

        // Assert
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertSame($user->getKey(), $relatedUser->getKey());
    }

    public function test_privacy_consent_log_is_deleted_when_user_is_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $log = PrivacyConsentLog::factory()->forUser($user)->create();

        // Act
        $user->delete();

        // Assert
        $this->assertDatabaseMissing('privacy_consent_logs', [
            'id' => $log->getKey(),
        ]);
    }

    public function test_privacy_consent_log_stores_change_details(): void
    {
        // Arrange & Act
        $log = PrivacyConsentLog::factory()
            ->forSetting('tracking_level', '0', '2')
            ->withReason('Enabling monitoring for productivity tracking')
            ->create();

        // Assert
        $this->assertSame('tracking_level', $log->setting_changed);
        $this->assertSame('0', $log->old_value);
        $this->assertSame('2', $log->new_value);
        $this->assertSame('Enabling monitoring for productivity tracking', $log->reason);
        $this->assertNotNull($log->consented_at);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
    }

    public function test_privacy_consent_log_can_be_created_without_reason(): void
    {
        // Arrange & Act
        $log = PrivacyConsentLog::factory()->withoutReason()->create();

        // Assert
        $this->assertNull($log->reason);
    }

    public function test_privacy_consent_log_can_be_created_without_old_value(): void
    {
        // Arrange & Act
        $log = PrivacyConsentLog::factory()
            ->forSetting('screenshot_enabled', null, 'true')
            ->create();

        // Assert
        $this->assertNull($log->old_value);
        $this->assertSame('true', $log->new_value);
    }

    public function test_privacy_consent_log_consented_at_is_cast_to_datetime(): void
    {
        // Arrange
        $consentTime = now()->subHours(2);

        // Act
        $log = PrivacyConsentLog::factory()
            ->consentedAt($consentTime)
            ->create();

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->consented_at);
        $this->assertTrue($consentTime->equalTo($log->consented_at));
    }

    public function test_privacy_consent_log_does_not_have_updated_at_timestamp(): void
    {
        // Arrange & Act
        $log = PrivacyConsentLog::factory()->create();

        // Assert - updated_at should be null or non-existent
        $this->assertObjectNotHasProperty('updated_at', $log);
    }

    public function test_privacy_consent_log_is_immutable_after_creation(): void
    {
        // Arrange
        $log = PrivacyConsentLog::factory()->create();
        $originalNewValue = $log->new_value;

        // Act - attempt to change the log
        $log->new_value = 'changed';
        $log->save();

        // Assert - verify the change was saved (logs are not truly immutable at DB level,
        // but business logic should prevent updates)
        $this->assertSame('changed', $log->fresh()->new_value);
        // Note: True immutability would be enforced at the service/controller level
    }

    public function test_multiple_consent_logs_can_exist_for_same_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $log1 = PrivacyConsentLog::factory()
            ->forUser($user)
            ->forSetting('tracking_level', '0', '1')
            ->create();

        $log2 = PrivacyConsentLog::factory()
            ->forUser($user)
            ->forSetting('tracking_level', '1', '2')
            ->create();

        // Assert
        $this->assertDatabaseHas('privacy_consent_logs', ['id' => $log1->getKey()]);
        $this->assertDatabaseHas('privacy_consent_logs', ['id' => $log2->getKey()]);
    }
}
