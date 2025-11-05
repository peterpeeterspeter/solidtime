<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(InvoiceController::class)]
class InvoiceEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Invoice::factory()->forOrganization($data->organization)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_invoices_of_organization_ordered_by_created_at_desc(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $invoices = Invoice::factory()
            ->forOrganization($data->organization)
            ->randomCreatedAt()
            ->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.index', [$data->organization->getKey()]));

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

    public function test_index_endpoint_filters_invoices_by_status(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        Invoice::factory()->forOrganization($data->organization)->draft()->createMany(2);
        Invoice::factory()->forOrganization($data->organization)->sent()->createMany(3);
        Invoice::factory()->forOrganization($data->organization)->paid()->createMany(1);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.index', [
            $data->organization->getKey(),
            'status' => 'sent',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_filters_invoices_by_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $client1 = Client::factory()->forOrganization($data->organization)->create();
        $client2 = Client::factory()->forOrganization($data->organization)->create();

        Invoice::factory()->forOrganization($data->organization)->forClient($client1)->createMany(2);
        Invoice::factory()->forOrganization($data->organization)->forClient($client2)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.index', [
            $data->organization->getKey(),
            'client_id' => $client1->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_searches_invoices_by_invoice_number(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        Invoice::factory()->forOrganization($data->organization)->create(['invoice_number' => 'INV-202511-0001']);
        Invoice::factory()->forOrganization($data->organization)->create(['invoice_number' => 'INV-202511-0002']);
        Invoice::factory()->forOrganization($data->organization)->create(['invoice_number' => 'INV-202512-0001']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.index', [
            $data->organization->getKey(),
            'search' => '202511',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.show', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_invoice_with_relationships(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()
            ->forOrganization($data->organization)
            ->forClient($client)
            ->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.show', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.id', $invoice->id)
            ->where('data.invoice_number', $invoice->invoice_number)
            ->has('data.client')
        );
    }

    public function test_show_endpoint_fails_if_invoice_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:view']);
        $otherOrganization = Organization::factory()->create();
        $invoice = Invoice::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invoices.show', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'from_details' => ['name' => 'Test Company'],
            'to_details' => ['name' => 'Client Company'],
            'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'amount' => 100]],
            'subtotal' => 100,
            'total' => 100,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_invoice_with_auto_generated_invoice_number(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:create']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'from_details' => ['name' => 'Test Company'],
            'to_details' => ['name' => 'Client Company'],
            'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'amount' => 100]],
            'subtotal' => 100,
            'total' => 100,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.client_id', $client->id)
            ->where('data.user_id', $data->user->id)
            ->where('data.organization_id', $data->organization->id)
            ->where('data.subtotal', 100)
            ->where('data.total', 100)
            ->has('data.invoice_number')
        );

        $this->assertDatabaseHas(Invoice::class, [
            'client_id' => $client->id,
            'organization_id' => $data->organization->id,
        ]);
    }

    public function test_store_endpoint_accepts_custom_invoice_number(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:create']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'client_id' => $client->id,
            'invoice_number' => 'CUSTOM-001',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'from_details' => ['name' => 'Test Company'],
            'to_details' => ['name' => 'Client Company'],
            'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'amount' => 100]],
            'subtotal' => 100,
            'total' => 100,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.invoice_number', 'CUSTOM-001')
        );
    }

    public function test_store_endpoint_validates_required_fields(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:create']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id', 'issue_date', 'due_date', 'from_details', 'to_details', 'line_items', 'subtotal', 'total']);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $invoice = Invoice::factory()->forOrganization($data->organization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.invoices.update', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]), [
            'notes' => 'Updated notes',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_draft_invoice(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.invoices.update', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]), [
            'notes' => 'Updated notes',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.notes', 'Updated notes')
        );

        $this->assertDatabaseHas(Invoice::class, [
            'id' => $invoice->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_update_endpoint_can_change_status_of_sent_invoice(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->sent()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.invoices.update', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]), [
            'status' => 'paid',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'paid')
        );
    }

    public function test_update_endpoint_fails_if_invoice_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $otherOrganization = Organization::factory()->create();
        $invoice = Invoice::factory()->forOrganization($otherOrganization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.invoices.update', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]), [
            'notes' => 'Updated notes',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_invoices(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $invoice = Invoice::factory()->forOrganization($data->organization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.invoices.destroy', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_deletes_draft_invoice(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:delete']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.invoices.destroy', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing(Invoice::class, [
            'id' => $invoice->id,
            'deleted_at' => null,
        ]);
    }

    public function test_destroy_endpoint_fails_for_sent_invoice(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:delete']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->sent()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.invoices.destroy', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Invoice::class, [
            'id' => $invoice->id,
        ]);
    }

    public function test_mark_as_sent_endpoint_updates_invoice_status(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->draft()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.mark-as-sent', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'sent')
            ->has('data.sent_at')
        );

        $this->assertDatabaseHas(Invoice::class, [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    }

    public function test_mark_as_paid_endpoint_updates_invoice_status(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['invoices:update']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->sent()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invoices.mark-as-paid', [
            $data->organization->getKey(),
            $invoice->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'paid')
            ->has('data.paid_at')
        );

        $this->assertDatabaseHas(Invoice::class, [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }
}
