<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    public function pay(array $data): string;

    public function handleCallback(mixed $payload, ?string $signature): bool;

    public function handleResponse(array $payload, array $params): bool;
}
