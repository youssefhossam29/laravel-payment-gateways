<?php

namespace App\Services\Payments\Contracts;

interface PaymentDriver
{
    public function pay(array $data): array;

    public function handleCallback(mixed $payload, ?string $signature): bool;

    public function handleResponse(array $payload, array $params): bool;
}
