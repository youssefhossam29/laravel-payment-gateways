<?php
use Illuminate\Http\Request;

if (!function_exists('bool_to_string')) {
    /**
     * Convert boolean-like values to 'true' or 'false' string
     */
    function bool_to_string($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}

if (!function_exists('extractPayload')) {
    /**
     * Extract raw payload from request based on gateway type
     */
    function extractPayload(Request $request, string $gateway)
    {
        return match ($gateway) {
            'paymob' => $request->json()->all(),
            default => throw new Exception("Unsupported gateway: {$gateway}")
        };
    }
}

if (!function_exists('extractSignature')) {
    /**
     * Extract signature from request headers based on gateway type
     */
    function extractSignature(Request $request, string $gateway)
    {
        return match ($gateway) {
            'paymob' => $request->query('hmac'),
            default => null
        };
    }
}
