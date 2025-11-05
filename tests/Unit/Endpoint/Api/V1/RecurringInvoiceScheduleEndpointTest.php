<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\RecurringInvoiceScheduleController;
use App\Models\Client;
use App\Models\Organization;
use App\Models\RecurringInvoiceSchedule;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(RecurringInvoiceScheduleController::class)]
class RecurringInvoiceScheduleEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_schedules_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $schedules = RecurringInvoiceSchedule::factory()
            ->forOrganization($data->organization)
            ->randomCreatedAt()
            ->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 4)
        );
    }

    public function test_index_endpoint_filters_schedules_by_status(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->active()->createMany(2);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->paused()->createMany(3);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->completed()->createMany(1);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.index', [
            $data->organization->getKey(),
            'status' => 'active',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filters_schedules_by_frequency(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->daily()->createMany(2);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->weekly()->createMany(3);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->monthly()->createMany(1);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.index', [
            $data->organization->getKey(),
            'frequency' => 'monthly',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_endpoint_filters_schedules_by_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $client1 = Client::factory()->forOrganization($data->organization)->create();
        $client2 = Client::factory()->forOrganization($data->organization)->create();

        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->forClient($client1)->createMany(2);
        RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->forClient($client2)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.index', [
            $data->organization->getKey(),
            'client_id' => $client1->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_schedules(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.show', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_schedule_with_relationships(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $schedule = RecurringInvoiceSchedule::factory()
            ->forOrganization($data->organization)
            ->forClient($client)
            ->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.show', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.id', $schedule->id)
            ->where('data.name', $schedule->name)
            ->has('data.client')
        );
    }

    public function test_show_endpoint_fails_if_schedule_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $otherOrganization = Organization::factory()->create();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.recurring-schedules.show', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_schedules(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.store', [$data->organization->getKey()]), [
            'client_id' => $client->id,
            'name' => 'Monthly Retainer',
            'frequency' => 'monthly',
            'start_date' => now()->format('Y-m-d'),
            'from_details' => ['name' => 'Test Company'],
            'to_details' => ['name' => 'Client Company'],
            'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 1000, 'amount' => 1000]],
            'subtotal' => 1000,
            'total' => 1000,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_recurring_schedule(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:create']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.store', [$data->organization->getKey()]), [
            'client_id' => $client->id,
            'name' => 'Monthly Retainer',
            'frequency' => 'monthly',
            'interval' => 1,
            'day_of_month' => 1,
            'start_date' => now()->format('Y-m-d'),
            'from_details' => ['name' => 'Test Company'],
            'to_details' => ['name' => 'Client Company'],
            'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 1000, 'amount' => 1000]],
            'subtotal' => 1000,
            'total' => 1000,
            'auto_send' => true,
            'auto_charge' => false,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.client_id', $client->id)
            ->where('data.name', 'Monthly Retainer')
            ->where('data.frequency', 'monthly')
            ->where('data.interval', 1)
            ->where('data.day_of_month', 1)
            ->where('data.status', 'active')
            ->where('data.auto_send', true)
            ->where('data.auto_charge', false)
        );

        $this->assertDatabaseHas(RecurringInvoiceSchedule::class, [
            'client_id' => $client->id,
            'organization_id' => $data->organization->id,
            'name' => 'Monthly Retainer',
            'frequency' => 'monthly',
        ]);
    }

    public function test_store_endpoint_validates_required_fields(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:create']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.store', [$data->organization->getKey()]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id', 'name', 'frequency', 'start_date', 'from_details', 'to_details', 'line_items', 'subtotal', 'total']);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_schedules(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.recurring-schedules.update', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]), [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_schedule(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.recurring-schedules.update', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]), [
            'name' => 'Updated Monthly Retainer',
            'interval' => 2,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.name', 'Updated Monthly Retainer')
            ->where('data.interval', 2)
        );

        $this->assertDatabaseHas(RecurringInvoiceSchedule::class, [
            'id' => $schedule->id,
            'name' => 'Updated Monthly Retainer',
            'interval' => 2,
        ]);
    }

    public function test_update_endpoint_fails_if_schedule_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $otherOrganization = Organization::factory()->create();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.recurring-schedules.update', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]), [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_schedules(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.recurring-schedules.destroy', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_deletes_schedule(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:delete']);
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.recurring-schedules.destroy', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing(RecurringInvoiceSchedule::class, [
            'id' => $schedule->id,
            'deleted_at' => null,
        ]);
    }

    public function test_pause_endpoint_pauses_active_schedule(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->active()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.pause', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'paused')
        );

        $this->assertDatabaseHas(RecurringInvoiceSchedule::class, [
            'id' => $schedule->id,
            'status' => 'paused',
        ]);
    }

    public function test_pause_endpoint_fails_if_user_has_no_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->active()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.pause', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_resume_endpoint_resumes_paused_schedule(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->paused()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.resume', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'active')
        );

        $this->assertDatabaseHas(RecurringInvoiceSchedule::class, [
            'id' => $schedule->id,
            'status' => 'active',
        ]);
    }

    public function test_resume_endpoint_fails_if_user_has_no_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->paused()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.recurring-schedules.resume', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_pause_and_resume_toggle_status_correctly(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $schedule = RecurringInvoiceSchedule::factory()->forOrganization($data->organization)->active()->create();
        Passport::actingAs($data->user);

        // Act - Pause
        $this->postJson(route('api.v1.recurring-schedules.pause', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        $schedule->refresh();
        $this->assertEquals('paused', $schedule->status);

        // Act - Resume
        $this->postJson(route('api.v1.recurring-schedules.resume', [
            $data->organization->getKey(),
            $schedule->getKey(),
        ]));

        $schedule->refresh();
        $this->assertEquals('active', $schedule->status);
    }
}
