<?php

namespace App\Services\Payments\Contracts;

interface PaymentDriver
{
    public function pay(array $data): array;

    public function handleCallback(array $payload, ?string $hmac): bool;

    public function handleResponse(array $payload, array $params): bool;
}
