<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    // Show Payment Form
    public function create()
    {
        return view('payments.form');
    }

    // Initiate Payment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone_number'   => 'required|string|max:20',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:card,wallet,cod',
            'apartment'      => 'nullable|string|max:50',
            'floor'          => 'nullable|string|max:50',
            'street'         => 'nullable|string|max:255',
            'building'       => 'nullable|string|max:50',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:2',
            'postal_code'    => 'nullable|string|max:20',
        ]);

        try {
            $user = auth()->user();
            $nameParts = explode(' ', $user->name, 2);
            $validated['first_name'] = $nameParts[0];
            $validated['last_name']  = $nameParts[1] ?? 'NA';
            $validated['email']      = $user->email;

            $url = $this->paymentService->pay($validated);

            return $url
                ? redirect($url)
                : redirect()->route('payment.success');

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // Paymob Server-to-Server Callback
    public function callback(Request $request)
    {
        try {
            $success = $this->paymentService->handleCallback(
                'paymob',
                $request->json()->all(),
                $request->query('hmac')
            );

            return response()->json([
                'message' => $success ? 'Payment verified & successful' : 'Payment failed',
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    // User Redirect After Payment
    public function response(Request $request)
    {
        return filter_var($request->query('success'), FILTER_VALIDATE_BOOLEAN)
            ? redirect()->route('payment.success')
            : redirect()->route('payment.failed');
    }

    // Redirect to success page
    public function success()
    {
        return view('payments.success');
    }

    // Redirect to failed page
    public function failed()
    {
        return view('payments.fail');
    }
}
