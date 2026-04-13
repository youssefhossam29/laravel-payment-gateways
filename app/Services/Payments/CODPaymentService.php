<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use Exception;

class CODPaymentService implements PaymentGatewayInterface
{
    public function pay(array $data): array
    {
        return [
            'url' => null,
            'gateway_order_id' => null,
        ];
    }

    public function handleCallback(mixed $payload, ?string $signature): bool
    {
        return true;
    }

    public function handleResponse(array $payload, array $params): bool
    {
        return true;
    }
}
