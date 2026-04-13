<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payments\PaymobPaymentService;
use App\Services\Payments\CODPaymentService;
use App\Services\Payments\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    // Maps payment methods to their driver classes + gateway name
    private array $drivers = [
        'card'   => ['driver' => PaymobPaymentService::class, 'gateway' => 'paymob'],
        'wallet' => ['driver' => PaymobPaymentService::class, 'gateway' => 'paymob'],
        'cod'    => ['driver' => CODPaymentService::class,    'gateway' => 'cod'   ],
        'stripe' => ['driver' => StripePaymentService::class, 'gateway' => 'stripe'],
    ];

    // Maps gateway names to their driver classes
    private array $gatewayDrivers = [
        'paymob' => PaymobPaymentService::class,
        'cod'    => CODPaymentService::class,
        'stripe' => StripePaymentService::class,
    ];

    private function resolve(string $method): PaymentGatewayInterface
    {
        if (!isset($this->drivers[$method])) {
            throw new Exception("Unsupported payment method: {$method}");
        }

        return app($this->drivers[$method]['driver']);
    }

    public function pay(array $data): ?string
    {
        $method = $data['payment_method'];
        $driver  = $this->resolve($method);
        $result  = $driver->pay($data);

        $this->createLocalOrder(
            $data,
            $this->drivers[$method]['gateway'],
            $method === 'cod' ? 'cash' : $method,
            $result['gateway_order_id'],
        );

        return $result['url'];
    }

    public function handleCallback(string $gateway, mixed $payload, ?string $signature): bool
    {
        if (!isset($this->gatewayDrivers[$gateway])) {
            throw new Exception("No driver registered for gateway: {$gateway}");
        }

        $driver = app($this->gatewayDrivers[$gateway]);

        return $driver->handleCallback($payload, $signature);
    }

    public function handleResponse(array $payload, array $params): bool
    {
        $gateway = $params['gateway_type'] ?? null;

        if (!$gateway || !isset($this->gatewayDrivers[$gateway])) {
            throw new Exception("No driver registered for gateway: {$gateway}");
        }

        $driver = app($this->gatewayDrivers[$gateway]);

        return $driver->handleResponse($payload, $params);
    }

    private function createLocalOrder(array $data, string $gateway, string $method, ?string $gatewayOrderId): Order
    {
        return DB::transaction(function () use ($data, $gateway, $method, $gatewayOrderId) {

            $order = Order::create([
                'user_id' => auth()->id(),
                'amount' => $data['amount'],
                'currency' => 'EGP',
                'status' => 'pending',
            ]);

            $order->payments()->create([
                'gateway'          => $gateway,
                'gateway_order_id' => $gatewayOrderId,
                'payment_method'   => $method,
                'status'           => 'pending',
                'amount'           => $data['amount'],
                'currency'         => 'EGP',
            ]);

            return $order;
        });
    }
}
