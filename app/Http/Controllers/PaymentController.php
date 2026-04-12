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

        $user = auth()->user();
        $name = $user->name;

        if ((count(explode(" ", $name)) == 1)) {
            $first_name = $name;
            $last_name = $name;
        } else {
            $first_name = explode(" ", $name)[0];
            $last_name = explode(" ", $name)[1];
        }

        $validated['first_name'] = $first_name;
        $validated['last_name'] = $last_name;
        $validated['email'] = $user->email;

        try {
            $url = $this->paymentService->pay($validated);

            return $url
                ? redirect($url)
                : redirect()->route('payment.success');

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // Payment Server-to-Server Callback
    public function callback(Request $request)
    {
        $params = $request->query();
        $gateway = $params['gateway_type'] ?? null;

        try {
            $payload = extractPayload($request, $gateway);
            $signature = extractSignature($request, $gateway);

            $success = $this->paymentService->handleCallback(
                $gateway,
                $payload,
                $signature
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
        $params = $request->query();

        $success = $this->paymentService->handleResponse($request->json()->all(), $params);

        if($success){
            return redirect()->route('payment.success');
        } else {
            return redirect()->route('payment.failed');
        }
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
