<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\OrderService;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymobPaymentService implements PaymentGatewayInterface
{
    private string $baseUrl;
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->baseUrl = config("paymob.base_url");
        $this->orderService = $orderService;
    }

    public function pay(array $data): string
    {
        $method = $data['payment_method'];

        $token = $this->generateAuthToken();
        $paymobOrderId = $this->createPaymobOrder($token, $data['amount']);
        $paymentToken = $this->generatePaymentToken($token, $paymobOrderId, $data, $method);
        // [$paymentToken, $paymobOrderId] = $this->createIntention($data);

        // Create local order and payment record before redirecting to Paymob
        $this->orderService->createLocalOrder(
            $data,
            'paymob',
            $method,
            $paymobOrderId,
        );

        return $this->getIframeUrl($paymentToken, $method);
    }

    private function generateAuthToken(): string
    {
        $response = Http::post($this->baseUrl . '/auth/tokens', [
            'api_key' => config('paymob.api_key'),
        ]);

        if ($response->successful()) {
            return $response->json('token');
        }

        throw new Exception('Paymob auth failed: ' . $response->body());
    }

    private function createPaymobOrder(string $token, float $amount): int
    {
        $response = Http::post($this->baseUrl . '/ecommerce/orders', [
            'auth_token' => $token,
            'delivery_needed' => 'false',
            'amount_cents' => $amount * 100,
            'currency' => 'EGP',
            'items' => [],
        ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        throw new Exception('Paymob order creation failed: ' . $response->body());
    }

    private function generatePaymentToken(string $token, int $orderId, array $data, string $method): string
    {
        $response = Http::post($this->baseUrl . '/acceptance/payment_keys', [
            'auth_token' => $token,
            'amount_cents' => $data['amount'] * 100,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => $this->buildBillingData($data),
            'currency' => 'EGP',
            'integration_id' => config("paymob.integration.{$method}"),
        ]);

        if ($response->successful()) {
            return $response->json('token');
        }

        throw new Exception('Paymob payment token failed: ' . $response->body());
    }

    private function getIframeUrl(string $token, string $method): string
    {
        $iframeId = config("paymob.frame_id.{$method}");
        return "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$token}";
    }

    // handle user redirect response
    public function handleResponse(array $payload, array $params): bool
    {
        return filter_var($params['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    // handle server-to-server callback
    public function handleCallback(mixed $payload, ?string $signature): bool
    {
        if (!$this->verifyHmac($payload, $signature)) {
            throw new Exception('Invalid HMAC', 403);
        }

        $obj = $payload['obj'] ?? [];
        $success = filter_var($obj['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $paymobOrderId = (string) ($obj['order']['id'] ?? '');
        $transactionId = (string) ($obj['id'] ?? '');

        return $this->orderService->updateOrderStatus(
            $paymobOrderId,
            'paymob',
            $success,
            $transactionId,
            $obj
        );
    }

    // verify Hmac
    private function verifyHmac(array $data, ?string $receivedHmac): bool
    {
        // Get HMAC secret from config
        $secret = config('paymob.hmac_secret');

        // Extract main parts of the payload
        $transaction = $data['obj'] ?? [];
        $order = $transaction['order'] ?? [];
        $source = $transaction['source_data'] ?? [];

        // Values must follow Paymob's exact required order
        $values = [
            $transaction['amount_cents'] ?? '',
            $transaction['created_at'] ?? '',
            $transaction['currency'] ?? '',
            bool_to_string($transaction['error_occured'] ?? false),
            bool_to_string($transaction['has_parent_transaction'] ?? false),
            $transaction['id'] ?? '',
            $transaction['integration_id'] ?? '',
            bool_to_string($transaction['is_3d_secure'] ?? false),
            bool_to_string($transaction['is_auth'] ?? false),
            bool_to_string($transaction['is_capture'] ?? false),
            bool_to_string($transaction['is_refunded'] ?? false),
            bool_to_string($transaction['is_standalone_payment'] ?? false),
            bool_to_string($transaction['is_voided'] ?? false),
            $order['id'] ?? '',
            $transaction['owner'] ?? '',
            bool_to_string($transaction['pending'] ?? false),
            $source['pan'] ?? '',
            $source['sub_type'] ?? '',
            $source['type'] ?? '',
            bool_to_string($transaction['success'] ?? false),
        ];

        // Concatenate all values into a single string
        $concatenatedString = implode('', $values);

        // Generate HMAC using SHA-512
        $calculatedHmac = hash_hmac('sha512', $concatenatedString, $secret);

        return hash_equals($calculatedHmac, (string) $receivedHmac);
    }

    private function buildBillingData(array $data): array
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

    // this method represents the new way of creating intention and getting payment token in one step
    public function createIntention(array $data): array
    {
        $method = $data['payment_method'];
        $integrationId = (int) config("paymob.integration.$method");

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . config('paymob.secret_key'),
            'Content-Type'  => 'application/json',
        ])->post('https://accept.paymob.com/v1/intention/', [
            'amount'          => $data['amount'] * 100,
            'currency'        => 'EGP',
            'payment_methods' => [$integrationId],
            'items'           => [],
            'billing_data'    => $this->buildBillingData($data),
            'expiration'      => 3600,
        ]);

        if ($response->successful()) {
            return [
                $response->json('payment_keys.0.key'),
                $response->json('payment_keys.0.order_id'),
            ];
        }

        throw new Exception('Failed to create intention: ' . $response->body());
    }
}
