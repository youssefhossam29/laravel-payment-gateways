<?php

return [
    'api_key'      => env('PAYMOB_API_KEY'),
    'public_key'   => env('PAYMOB_PUBLIC_KEY'),
    'secret_key'   => env('PAYMOB_SECRET_KEY'),
    'hmac_secret'  => env('PAYMOB_HMAC_SECRET'),

    'integration' => [
        'card'   => env('PAYMOB_CARD_INTEGRATION_ID'),
        'wallet' => env('PAYMOB_WALLET_INTEGRATION_ID'),
    ],

    'frame_id' => [
        'card'   => env('PAYMOB_CARD_FRAME_ID'),
        'wallet' => env('PAYMOB_WALLET_FRAME_ID'),
    ],
];
