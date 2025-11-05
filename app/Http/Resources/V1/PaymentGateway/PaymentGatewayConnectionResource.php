<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\PaymentGateway;

use App\Http\Resources\V1\BaseResource;
use App\Models\PaymentGatewayConnection;
use Illuminate\Http\Request;

/**
 * @property PaymentGatewayConnection $resource
 */
class PaymentGatewayConnectionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $user_id User ID */
            'user_id' => $this->resource->user_id,
            /** @var string $gateway Gateway type (stripe, paypal) */
            'gateway' => $this->resource->gateway,
            /** @var string|null $gateway_account_id Gateway account ID */
            'gateway_account_id' => $this->resource->gateway_account_id,
            /** @var bool $is_active Whether the connection is active */
            'is_active' => $this->resource->is_active,
            /** @var string|null $token_expires_at When the access token expires */
            'token_expires_at' => $this->resource->token_expires_at
                ? $this->formatDateTime($this->resource->token_expires_at)
                : null,
            /** @var array|null $metadata Additional metadata */
            'metadata' => $this->resource->metadata,
            /** @var string $created_at When the connection was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the connection was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
