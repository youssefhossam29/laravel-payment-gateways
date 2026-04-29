<?php

return [
    'public_key'   => env('STRIPE_PUBLIC'),
    'secret_key'   => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'base_url'     => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
];
