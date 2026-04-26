<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    public function createLocalOrder(array $data, string $gateway, string $method, ?string $gatewayOrderId): Order
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

    public function updateOrderStatus(string $gatewayOrderId, string $gateway, bool $success, ?string $transactionId, mixed $gatewayResponse)
    {
        $payment = Payment::where('gateway_order_id', $gatewayOrderId)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->with('order')
            ->firstOrFail();

        if (!$payment) {
            return true;
        }

        DB::transaction(function () use ($payment, $success, $transactionId, $gatewayResponse) {
            if ($success) {
                $payment->update([
                    'transaction_id' => $transactionId,
                    'status' => 'paid',
                    'gateway_response' => $gatewayResponse,
                    'paid_at' => now(),
                ]);

                $payment->order->markAsPaid();
            } else {
                $payment->update([
                    'transaction_id' => $transactionId ?: null,
                    'status' => 'failed',
                    'gateway_response' => $gatewayResponse,
                ]);

                $payment->order->markAsFailed();
            }
        });

        return $success;
    }
}


