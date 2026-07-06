<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayPal Mode
    |--------------------------------------------------------------------------
    |
    | Options: 'sandbox' or 'live'
    |
    */

    'mode' => env('PAYPAL_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Configuration
    |--------------------------------------------------------------------------
    */

    'sandbox' => [
        'username' => env('PAYPAL_SANDBOX_API_USERNAME', ''),
        'password' => env('PAYPAL_SANDBOX_API_PASSWORD', ''),
        'signature' => env('PAYPAL_SANDBOX_API_SIGNATURE', ''),
        'endpoint' => 'https://api-3t.sandbox.paypal.com/nvp',
        'redirect_url' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Configuration
    |--------------------------------------------------------------------------
    */

    'live' => [
        'username' => env('PAYPAL_LIVE_API_USERNAME', ''),
        'password' => env('PAYPAL_LIVE_API_PASSWORD', ''),
        'signature' => env('PAYPAL_LIVE_API_SIGNATURE', ''),
        'endpoint' => 'https://api-3t.paypal.com/nvp',
        'redirect_url' => 'https://www.paypal.com/cgi-bin/webscr',
    ],

];
