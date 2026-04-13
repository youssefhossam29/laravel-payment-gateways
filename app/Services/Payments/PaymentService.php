<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payments\PaymobPaymentService;
use App\Services\Payments\CODPaymentService;
use App\Services\Payments\StripePaymentService;
use Exception;

class PaymentService
{
    // Maps payment methods to their service classes + gateway name
    private array $services = [
        'card'   => ['service' => PaymobPaymentService::class, 'gateway' => 'paymob'],
        'wallet' => ['service' => PaymobPaymentService::class, 'gateway' => 'paymob'],
        'cod'    => ['service' => CODPaymentService::class,    'gateway' => 'cod'   ],
        'stripe' => ['service' => StripePaymentService::class, 'gateway' => 'stripe'],
    ];

    // Maps gateway names to their service classes
    private array $gatewayServices = [
        'paymob' => PaymobPaymentService::class,
        'cod'    => CODPaymentService::class,
        'stripe' => StripePaymentService::class,
    ];

    private function resolve(string $method): PaymentGatewayInterface
    {
        if (!isset($this->services[$method])) {
            throw new Exception("Unsupported payment method: {$method}");
        }

        return app($this->services[$method]['service']);
    }

    public function pay(array $data): ?string
    {
        $method = $data['payment_method'];
        $service  = $this->resolve($method);
        $result  = $service->pay($data);

        return $result;
    }

    public function handleCallback(string $gateway, mixed $payload, ?string $signature): bool
    {
        if (!isset($this->gatewayServices[$gateway])) {
            throw new Exception("No service registered for gateway: {$gateway}");
        }

        $service = app($this->gatewayServices[$gateway]);

        return $service->handleCallback($payload, $signature);
    }

    public function handleResponse(array $payload, array $params): bool
    {
        $gateway = $params['gateway_type'] ?? null;

        if (!$gateway || !isset($this->gatewayServices[$gateway])) {
            throw new Exception("No service registered for gateway: {$gateway}");
        }

        $service = app($this->gatewayServices[$gateway]);

        return $service->handleResponse($payload, $params);
    }
}
