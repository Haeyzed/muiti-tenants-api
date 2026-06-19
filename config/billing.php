<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default billing provider
    |--------------------------------------------------------------------------
    |
    | Supported: stripe, paddle, paystack, paypal, flutterwave
    |
    */

    'default_provider' => env('BILLING_DEFAULT_PROVIDER', 'stripe'),

    'default_plan' => 'starter',

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'sandbox' => (bool) env('PAYPAL_SANDBOX', true),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
    ],

];
