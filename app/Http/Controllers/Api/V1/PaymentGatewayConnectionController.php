<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\PaymentGateway\PaymentGatewayAuthorizationUrlRequest;
use App\Http\Requests\V1\PaymentGateway\PaymentGatewayCallbackRequest;
use App\Http\Resources\V1\PaymentGateway\PaymentGatewayConnectionCollection;
use App\Http\Resources\V1\PaymentGateway\PaymentGatewayConnectionResource;
use App\Models\PaymentGatewayConnection;
use App\Services\Payment\PayPalPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class PaymentGatewayConnectionController extends Controller
{
    /**
     * Get payment gateway connections for the authenticated user
     *
     * @operationId getPaymentGatewayConnections
     */
    public function index(): PaymentGatewayConnectionCollection
    {
        $user = $this->user();

        $connections = PaymentGatewayConnection::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return new PaymentGatewayConnectionCollection($connections);
    }

    /**
     * Get authorization URL for connecting a payment gateway
     *
     * @operationId getPaymentGatewayAuthorizationUrl
     */
    public function getAuthorizationUrl(PaymentGatewayAuthorizationUrlRequest $request): JsonResponse
    {
        $user = $this->user();
        $gateway = $request->input('gateway');
        $redirectUri = $request->input('redirect_uri');

        $gatewayService = $this->getGatewayService($gateway);
        $authUrl = $gatewayService->getAuthorizationUrl($user->id, $redirectUri);

        return response()->json([
            'authorization_url' => $authUrl,
        ]);
    }

    /**
     * Handle OAuth callback from payment gateway
     *
     * @operationId handlePaymentGatewayCallback
     */
    public function handleCallback(PaymentGatewayCallbackRequest $request): PaymentGatewayConnectionResource
    {
        $user = $this->user();
        $gateway = $request->input('gateway');
        $code = $request->input('code');

        $gatewayService = $this->getGatewayService($gateway);
        $connection = $gatewayService->handleCallback($code, $user->id);

        return new PaymentGatewayConnectionResource($connection);
    }

    /**
     * Disconnect a payment gateway
     *
     * @throws AuthorizationException
     *
     * @operationId disconnectPaymentGateway
     */
    public function destroy(PaymentGatewayConnection $connection): JsonResponse
    {
        $user = $this->user();

        if ($connection->user_id !== $user->id) {
            throw new AuthorizationException('You do not have permission to disconnect this gateway');
        }

        $gatewayService = $this->getGatewayService($connection->gateway);
        $gatewayService->disconnect($connection);

        $connection->delete();

        return response()->json([
            'message' => 'Payment gateway disconnected successfully',
        ]);
    }

    /**
     * Get the appropriate gateway service instance
     */
    private function getGatewayService(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'stripe' => app(StripePaymentGateway::class),
            'paypal' => app(PayPalPaymentGateway::class),
            default => throw new \InvalidArgumentException("Unsupported gateway: {$gateway}"),
        };
    }
}
