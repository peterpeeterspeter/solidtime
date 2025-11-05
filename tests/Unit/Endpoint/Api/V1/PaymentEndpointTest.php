<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use Mockery;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(PaymentController::class)]
class PaymentEndpointTest extends ApiEndpointTestAbstract
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_payments(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Payment::factory()->forOrganization($data->organization)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_payments_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payments = Payment::factory()
            ->forOrganization($data->organization)
            ->forInvoice($invoice)
            ->randomCreatedAt()
            ->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [$data->organization->getKey()]));

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

    public function test_index_endpoint_does_not_return_payments_from_other_organizations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $otherOrganization = Organization::factory()->create();

        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->createMany(2);

        $otherInvoice = Invoice::factory()->forOrganization($otherOrganization)->create();
        Payment::factory()->forOrganization($otherOrganization)->forInvoice($otherInvoice)->createMany(3);

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filters_payments_by_status(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();

        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->pending()->createMany(2);
        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->completed()->createMany(3);
        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->failed()->createMany(1);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [
            $data->organization->getKey(),
            'status' => 'completed',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_filters_payments_by_invoice(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $invoice1 = Invoice::factory()->forOrganization($data->organization)->create();
        $invoice2 = Invoice::factory()->forOrganization($data->organization)->create();

        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice1)->createMany(2);
        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice2)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [
            $data->organization->getKey(),
            'invoice_id' => $invoice1->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filters_payments_by_gateway(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();

        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->stripe()->createMany(2);
        Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->paypal()->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.index', [
            $data->organization->getKey(),
            'gateway' => 'stripe',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_payments(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.show', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_payment_with_relationships(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.show', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.id', $payment->id)
            ->where('data.amount', (float) $payment->amount)
            ->has('data.invoice')
        );
    }

    public function test_show_endpoint_fails_if_payment_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:view']);
        $otherOrganization = Organization::factory()->create();
        $invoice = Invoice::factory()->forOrganization($otherOrganization)->create();
        $payment = Payment::factory()->forOrganization($otherOrganization)->forInvoice($invoice)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payments.show', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_refund_endpoint_fails_if_user_has_no_permission_to_refund_payments(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]), [
            'amount' => 50.00,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_refund_endpoint_processes_full_refund(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:refund']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()
            ->forOrganization($data->organization)
            ->forInvoice($invoice)
            ->completed()
            ->stripe()
            ->create(['amount' => 100.00]);

        Passport::actingAs($data->user);

        $mockStripeGateway = Mockery::mock(StripePaymentGateway::class);
        $updatedPayment = $payment->replicate();
        $updatedPayment->status = 'refunded';
        $updatedPayment->refund_amount = 100.00;
        $updatedPayment->refunded_at = now();

        $mockStripeGateway->shouldReceive('refundPayment')
            ->once()
            ->with(
                Mockery::on(function ($arg) use ($payment) {
                    return $arg->id === $payment->id;
                }),
                100.00,
                null
            )
            ->andReturn($updatedPayment);

        $this->app->instance(StripePaymentGateway::class, $mockStripeGateway);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.status', 'refunded')
        );
    }

    public function test_refund_endpoint_processes_partial_refund(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:refund']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()
            ->forOrganization($data->organization)
            ->forInvoice($invoice)
            ->completed()
            ->stripe()
            ->create(['amount' => 100.00]);

        Passport::actingAs($data->user);

        $mockStripeGateway = Mockery::mock(StripePaymentGateway::class);
        $updatedPayment = $payment->replicate();
        $updatedPayment->status = 'partially_refunded';
        $updatedPayment->refund_amount = 50.00;
        $updatedPayment->refunded_at = now();

        $mockStripeGateway->shouldReceive('refundPayment')
            ->once()
            ->with(
                Mockery::on(function ($arg) use ($payment) {
                    return $arg->id === $payment->id;
                }),
                50.00,
                'Customer requested'
            )
            ->andReturn($updatedPayment);

        $this->app->instance(StripePaymentGateway::class, $mockStripeGateway);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]), [
            'amount' => 50.00,
            'reason' => 'Customer requested',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.status', 'partially_refunded')
        );
    }

    public function test_refund_endpoint_fails_for_pending_payment(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:refund']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->pending()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_refund_endpoint_fails_for_failed_payment(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:refund']);
        $invoice = Invoice::factory()->forOrganization($data->organization)->create();
        $payment = Payment::factory()->forOrganization($data->organization)->forInvoice($invoice)->failed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_refund_endpoint_fails_if_payment_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['payments:refund']);
        $otherOrganization = Organization::factory()->create();
        $invoice = Invoice::factory()->forOrganization($otherOrganization)->create();
        $payment = Payment::factory()->forOrganization($otherOrganization)->forInvoice($invoice)->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payments.refund', [
            $data->organization->getKey(),
            $payment->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }
}
