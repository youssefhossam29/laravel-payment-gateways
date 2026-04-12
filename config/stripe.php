<?php

return [
    'public_key'   => env('STRIPE_PUBLIC'),
    'secret_key'   => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
