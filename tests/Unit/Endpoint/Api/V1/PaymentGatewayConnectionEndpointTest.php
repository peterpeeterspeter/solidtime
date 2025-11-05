<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\PaymentGatewayConnectionController;
use App\Models\PaymentGatewayConnection;
use App\Services\Payment\PayPalPaymentGateway;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use Mockery;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(PaymentGatewayConnectionController::class)]
class PaymentGatewayConnectionEndpointTest extends ApiEndpointTestAbstract
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_endpoint_returns_list_of_user_payment_gateway_connections(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $connections = PaymentGatewayConnection::factory()
            ->forUser($data->user)
            ->createMany(3);

        // Create connections for other user (should not be returned)
        $otherUser = \App\Models\User::factory()->create();
        PaymentGatewayConnection::factory()
            ->forUser($otherUser)
            ->createMany(2);

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.payment-gateways.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 3)
            ->where('data.0.user_id', $data->user->id)
            ->where('data.1.user_id', $data->user->id)
            ->where('data.2.user_id', $data->user->id)
        );
    }

    public function test_index_endpoint_fails_if_user_is_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.payment-gateways.index'));

        // Assert
        $response->assertStatus(401);
    }

    public function test_get_authorization_url_endpoint_returns_stripe_authorization_url(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $mockStripeGateway = Mockery::mock(StripePaymentGateway::class);
        $mockStripeGateway->shouldReceive('getAuthorizationUrl')
            ->once()
            ->with($data->user->id, 'http://localhost/callback')
            ->andReturn('https://connect.stripe.com/oauth/authorize?client_id=test');

        $this->app->instance(StripePaymentGateway::class, $mockStripeGateway);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.authorization-url'), [
            'gateway' => 'stripe',
            'redirect_uri' => 'http://localhost/callback',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'authorization_url' => 'https://connect.stripe.com/oauth/authorize?client_id=test',
        ]);
    }

    public function test_get_authorization_url_endpoint_returns_paypal_authorization_url(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $mockPayPalGateway = Mockery::mock(PayPalPaymentGateway::class);
        $mockPayPalGateway->shouldReceive('getAuthorizationUrl')
            ->once()
            ->with($data->user->id, 'http://localhost/callback')
            ->andReturn('https://www.paypal.com/connect?client_id=test');

        $this->app->instance(PayPalPaymentGateway::class, $mockPayPalGateway);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.authorization-url'), [
            'gateway' => 'paypal',
            'redirect_uri' => 'http://localhost/callback',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'authorization_url' => 'https://www.paypal.com/connect?client_id=test',
        ]);
    }

    public function test_get_authorization_url_endpoint_fails_with_invalid_gateway(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.authorization-url'), [
            'gateway' => 'invalid_gateway',
            'redirect_uri' => 'http://localhost/callback',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gateway']);
    }

    public function test_get_authorization_url_endpoint_fails_with_invalid_redirect_uri(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.authorization-url'), [
            'gateway' => 'stripe',
            'redirect_uri' => 'not-a-valid-url',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['redirect_uri']);
    }

    public function test_handle_callback_endpoint_creates_new_connection(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $mockConnection = PaymentGatewayConnection::factory()->forUser($data->user)->stripe()->make();
        $mockConnection->id = fake()->uuid();

        $mockStripeGateway = Mockery::mock(StripePaymentGateway::class);
        $mockStripeGateway->shouldReceive('handleCallback')
            ->once()
            ->with('test_auth_code', $data->user->id)
            ->andReturn($mockConnection);

        $this->app->instance(StripePaymentGateway::class, $mockStripeGateway);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.callback'), [
            'gateway' => 'stripe',
            'code' => 'test_auth_code',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.gateway', 'stripe')
            ->where('data.user_id', $data->user->id)
        );
    }

    public function test_handle_callback_endpoint_fails_if_code_is_missing(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.payment-gateways.callback'), [
            'gateway' => 'stripe',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_destroy_endpoint_disconnects_payment_gateway(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $connection = PaymentGatewayConnection::factory()
            ->forUser($data->user)
            ->stripe()
            ->create();

        Passport::actingAs($data->user);

        $mockStripeGateway = Mockery::mock(StripePaymentGateway::class);
        $mockStripeGateway->shouldReceive('disconnect')
            ->once()
            ->with(Mockery::on(function ($arg) use ($connection) {
                return $arg->id === $connection->id;
            }))
            ->andReturn(true);

        $this->app->instance(StripePaymentGateway::class, $mockStripeGateway);

        // Act
        $response = $this->deleteJson(route('api.v1.payment-gateways.destroy', [$connection->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Payment gateway disconnected successfully',
        ]);
        $this->assertDatabaseMissing(PaymentGatewayConnection::class, [
            'id' => $connection->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_connection_belongs_to_different_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherUser = \App\Models\User::factory()->create();
        $connection = PaymentGatewayConnection::factory()
            ->forUser($otherUser)
            ->stripe()
            ->create();

        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.payment-gateways.destroy', [$connection->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(PaymentGatewayConnection::class, [
            'id' => $connection->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_is_not_authenticated(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $connection = PaymentGatewayConnection::factory()
            ->forUser($data->user)
            ->stripe()
            ->create();

        // Act
        $response = $this->deleteJson(route('api.v1.payment-gateways.destroy', [$connection->getKey()]));

        // Assert
        $response->assertStatus(401);
        $this->assertDatabaseHas(PaymentGatewayConnection::class, [
            'id' => $connection->getKey(),
        ]);
    }
}
