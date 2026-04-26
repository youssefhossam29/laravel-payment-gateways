<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use Exception;
use App\Services\OrderService;

class CODPaymentService implements PaymentGatewayInterface
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function pay(array $data): string
    {
        $this->orderService->createLocalOrder(
            $data,
            'cod',
            'cash',
            null,
        );

        return route('payment.success');
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
