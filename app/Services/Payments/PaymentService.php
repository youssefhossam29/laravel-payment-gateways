<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payments\PaymobPaymentService;
use App\Services\Payments\CODPaymentService;
use App\Services\Payments\StripePaymentService;
use Exception;

class PaymentService
{
    public function mapMethodToGateway(string $method): string
    {
        return match ($method) {
            'card', 'wallet' => 'paymob',
            'stripe' => 'stripe',
            'cod' => 'cod',
            default => throw new Exception("Unsupported payment method"),
        };
    }

    public function resolve(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'paymob' => app(PaymobPaymentService::class),
            'stripe' => app(StripePaymentService::class),
            'cod' => app(CODPaymentService::class),
            default => throw new Exception("Unsupported gateway"),
        };
    }

    public function pay(array $data): string
    {
        $gateway = $this->mapMethodToGateway($data['payment_method']);
        return $this->resolve($gateway)->pay($data);
    }

    public function handleCallback(string $gateway, $payload, $signature): bool
    {
        return $this->resolve($gateway)->handleCallback($payload, $signature);
    }

    public function handleResponse(array $payload, array $params): bool
    {
        $gateway = $params['gateway_type'] ?? null;
        return $this->resolve($gateway)->handleResponse($payload, $params);
    }
}
