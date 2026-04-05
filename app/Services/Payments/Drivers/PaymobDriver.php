<?php

namespace App\Services\Payments\Drivers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymobDriver implements PaymentDriver
{
    private string $baseUrl = "https://accept.paymob.com/api";

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

    public function pay(array $data): array
    {
        $method = $data['payment_method'];

        $token = $this->generateAuthToken();
        $paymobOrderId = $this->createPaymobOrder($token, $data['amount']);
        $paymentToken = $this->generatePaymentToken($token, $paymobOrderId, $data, $method);
        // [$paymentToken, $paymobOrderId] = $this->createIntention($data);

        return [
            'url' => $this->getIframeUrl($paymentToken, $method),
            'gateway_order_id' => (string) $paymobOrderId,
        ];
    }

    public function handleCallback(array $payload, ?string $hmac): bool
    {
        if (!$this->verifyHmac($payload, $hmac)) {
            throw new Exception('Invalid HMAC', 403);
        }

        $obj = $payload['obj'] ?? [];
        $success = filter_var($obj['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $paymobOrderId = (string) ($obj['order']['id'] ?? '');
        $transactionId = (string) ($obj['id'] ?? '');

        $payment = Payment::where('gateway_order_id', $paymobOrderId)
            ->where('gateway', 'paymob')
            ->where('status', 'pending')
            ->with('order')
            ->firstOrFail();

        DB::transaction(function () use ($payment, $success, $transactionId, $obj) {
            if ($success) {
                $payment->update([
                    'transaction_id' => $transactionId,
                    'status' => 'paid',
                    'gateway_response' => $obj,
                    'paid_at' => now(),
                ]);
                $payment->order->markAsPaid();
            } else {
                $payment->update([
                    'transaction_id' => $transactionId ?: null,
                    'status' => 'failed',
                    'gateway_response' => $obj,
                ]);
                $payment->order->markAsFailed();
            }
        });

        return $success;
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

    // verify Hmac
    private function verifyHmac(array $data, ?string $hmac): bool
    {
        $secret = config('paymob.hmac_secret');

        $obj = $data['obj'] ?? [];
        $order = $obj['order'] ?? [];
        $source = $obj['source_data'] ?? [];

        $boolToStr = fn($val) => filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';

        $string =
            ($obj['amount_cents'] ?? '') .
            ($obj['created_at'] ?? '') .
            ($obj['currency'] ?? '') .
            $boolToStr($obj['error_occured'] ?? false) .
            $boolToStr($obj['has_parent_transaction'] ?? false) .
            ($obj['id'] ?? '') .
            ($obj['integration_id'] ?? '') .
            $boolToStr($obj['is_3d_secure'] ?? false) .
            $boolToStr($obj['is_auth'] ?? false) .
            $boolToStr($obj['is_capture'] ?? false) .
            $boolToStr($obj['is_refunded'] ?? false) .
            $boolToStr($obj['is_standalone_payment'] ?? false) .
            $boolToStr($obj['is_voided'] ?? false) .
            ($order['id'] ?? '') .
            ($obj['owner'] ?? '') .
            $boolToStr($obj['pending'] ?? false) .
            ($source['pan'] ?? '') .
            ($source['sub_type'] ?? '') .
            ($source['type'] ?? '') .
            $boolToStr($obj['success'] ?? false);

        $calculated = hash_hmac('sha512', $string, $secret);

        return hash_equals($calculated, (string) $hmac);
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
}
