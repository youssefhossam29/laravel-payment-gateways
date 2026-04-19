<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\OrderService;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;
use Exception;

class StripePaymentService implements PaymentGatewayInterface
{
    private string $baseUrl;
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->baseUrl = config("stripe.base_url");
        $this->orderService = $orderService;
    }

    public function pay(array $data): string
    {
        $stripe = new StripeClient(config('stripe.secret_key'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => 'Order Payment',
                    ],
                    'unit_amount' => $data['amount'] * 100,
                ],
                'quantity' => 1,
            ]],
            'success_url' => 'https://vicissitudinary-euchromatic-celina.ngrok-free.dev/payment/response?session_id={CHECKOUT_SESSION_ID}&gateway_type=stripe',
            'cancel_url' => 'https://vicissitudinary-euchromatic-celina.ngrok-free.dev/payment/failed',
            'payment_intent_data[metadata]' => $this->buildMetaData($data),
            'customer_email' => $data['email'],
        ]);

        if (!$session || !isset($session->id) || !isset($session->url)) {
            throw new Exception('Stripe error: ' . $session->body());
        }

        // Create local order and payment record before redirecting to Paymob
        $this->orderService->createLocalOrder(
            $data,
            'stripe',
            $data['payment_method'],
            $stripeOrderId = $session->id ?? null,
        );

        return $session->url;
    }

    public function handleResponse(array $payload, array $params): bool
    {
        $session_id = $params['session_id'] ?? null;

        $stripe = new StripeClient(config('stripe.secret_key'));

        $session = $stripe->checkout->sessions->retrieve(
            $session_id,
            []
        );

        $status = $session->payment_status ?? '';
        $paymentStatus = $session->status ?? '';
        $success = $status === 'paid' && $paymentStatus === 'complete';

        return $success;
    }

    public function handleCallback(mixed $payload, ?string $signature): bool
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            throw new Exception('Invalid Stripe Webhook', 403);
        }

        if ($event->type !== 'checkout.session.completed') {
            return false;
        }

        $session = $event->data->object;
        $stripeOrderId = $session->id ?? null;
        $transactionId = $session->payment_intent ?? null;
        $paymentStatus = ($session->payment_status ?? '') === 'paid';
        $status = ($session->status ?? '') === 'complete';
        $success = $paymentStatus && $status;

        return $this->orderService->updateOrderStatus(
            $stripeOrderId,
            'stripe',
            $success,
            $transactionId,
            $event
        );
    }

    private function buildMetaData(array $data): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'apartment' => $data['apartment'] ?? 'NA',
            'floor' => $data['floor'] ?? 'NA',
            'street' => $data['street'] ?? 'NA',
            'building' => $data['building'] ?? 'NA',
            'shipping_method' => 'NA',
            'postal_code' => $data['postal_code'] ?? 'NA',
            'city' => $data['city'] ?? 'NA',
            'country' => $data['country'] ?? 'EG',
            'state' => $data['state'] ?? 'NA',
        ];
    }
}
