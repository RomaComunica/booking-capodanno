<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nexi Mode
    |--------------------------------------------------------------------------
    |
    | Options: 'test' or 'production'
    |
    */

    'mode' => env('NEXI_MODE', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Test Configuration
    |--------------------------------------------------------------------------
    */

    'test' => [
        'alias' => env('NEXI_TEST_ALIAS', ''),
        'secret_key' => env('NEXI_TEST_SECRET_KEY', ''),
        'gateway_url' => 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet',
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Configuration
    |--------------------------------------------------------------------------
    */

    'production' => [
        'alias' => env('NEXI_PRODUCTION_ALIAS', ''),
        'secret_key' => env('NEXI_PRODUCTION_SECRET_KEY', ''),
        'gateway_url' => 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Code
    |--------------------------------------------------------------------------
    |
    | Nexi XPay DispatcherServlet requires 'EUR'
    |
    */

    'currency_code' => env('NEXI_CURRENCY_CODE', 'EUR'),

];
