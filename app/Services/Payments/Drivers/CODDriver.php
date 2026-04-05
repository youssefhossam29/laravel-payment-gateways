<?php

namespace App\Services\Payments\Drivers;

use App\Services\Payments\Contracts\PaymentDriver;
use Exception;

class CODDriver implements PaymentDriver
{
    public function pay(array $data): array
    {
        return [
            'url' => null,
            'gateway_order_id' => null,
        ];
    }

    public function handleCallback(array $payload, ?string $hmac): bool
    {
        return true;
    }
}
