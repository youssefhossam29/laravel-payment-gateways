<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\OrderService;
use Illuminate\Support\Facades\Http;
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
        $response = Http::withBasicAuth(config('stripe.secret_key'), '')
            ->asForm()
            ->post($this->baseUrl . '/v1/checkout/sessions', [
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
                'payment_intent_data[metadata]' => $this->buildMetaData($data),
                'success_url' => 'https://vicissitudinary-euchromatic-celina.ngrok-free.dev/payment/response?session_id={CHECKOUT_SESSION_ID}&gateway_type=stripe',
                'customer_email' => $data['email'],

                // uncomment to allow striped to collect shipping address and phone number
                // 'shipping_address_collection[allowed_countries][]' => $data['country'],
                // 'phone_number_collection[enabled]' => 'true',
            ]);

        if (!$response->successful()) {
            throw new Exception('Stripe error: ' . $response->body());
        }

        // Create local order and payment record before redirecting to Paymob
        $this->orderService->createLocalOrder(
            $data,
            'stripe',
            $data['payment_method'],
            $stripeOrderId = $response['id'] ?? null,
        );

        return $response['url'];
    }

    public function handleResponse(array $payload, array $params): bool
    {
        $session_id = $params['session_id'] ?? null;

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . config('stripe.secret_key'),
        ])->get($this->baseUrl . '/v1/checkout/sessions/'.$session_id);

        $status = $response['payment_status'] ?? '';
        $paymentStatus = $response['status'] ?? '';
        $success = $status === 'paid' && $paymentStatus === 'complete';

        return $success;
    }

    public function handleCallback(mixed $payload, ?string $signature): bool
    {
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            throw new Exception('Invalid Stripe webhook signature', 403);
        }

        $event = json_decode($payload, true);

        if ($event['type'] !== 'checkout.session.completed') {
            return false;
        }

        $session = $event['data']['object'] ?? [];
        $stripeOrderId = $session['id'] ?? null;
        $transactionId = $session['payment_intent'] ?? null;
        $paymentStatus = ($session['payment_status'] ?? '') === 'paid';
        $status = ($session['status'] ?? '') === 'complete';
        $success = $paymentStatus && $status;

        return $this->orderService->updateOrderStatus(
            $stripeOrderId,
            'stripe',
            $success,
            $transactionId,
            $event
        );
    }

    private function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('stripe.webhook_secret');

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            return false;
        }

        $timestamp = $parts['t'];
        $signature = $parts['v1'];

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        return hash_equals($expectedSignature, $signature);
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
