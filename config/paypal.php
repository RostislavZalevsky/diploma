<?php
/**
 * PayPal Setting & API Credentials
 * Created by Raza Mehdi <srmk@outlook.com>.
 */

return [

    // srmklive/laravel-paypal
    'mode'    => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
    'sandbox' => [
        'client_id'         => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_SANDBOX_SECRET', ''),
        'app_id'            => 'APP-80W284485P519543T',
    ],
    'live' => [
        'client_id'         => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_LIVE_SECRET', ''),
//        'username'          => env('PAYPAL_LIVE_API_USERNAME', ''),
//        'password'          => env('PAYPAL_LIVE_API_PASSWORD', ''),
//        'secret'            => env('PAYPAL_LIVE_API_SECRET', ''),
//        'certificate'       => env('PAYPAL_LIVE_API_CERTIFICATE', ''),
        'app_id'            => '',
    ],

    'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Authorization'), // Can only be 'Sale', 'Authorization' or 'Order'
    'currency'       => env('PAYPAL_CURRENCY', 'USD'),
    'notify_url'     => env('PAYPAL_NOTIFY_URL', 'https://dev.bulktra.com/webhook/paypal'), // Change this accordingly for your application.
    'locale'         => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
    'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
    // end srmklive/laravel-paypal

    // just
    'client_id' => env(
        (env('PAYPAL_MODE', 'sandbox') == 'live'
            ? 'PAYPAL_LIVE_CLIENT_ID'
            : 'PAYPAL_SANDBOX_CLIENT_ID'),
        ''),

    'secret' => env(
        (env('PAYPAL_MODE', 'sandbox') == 'live'
            ? 'PAYPAL_LIVE_SECRET'
            : 'PAYPAL_SANDBOX_SECRET'),
        ''),

    'webhook' => env(
        (env('PAYPAL_MODE', 'sandbox') == 'live'
            ? 'PAYPAL_LIVE_WEBHOOK_ID'
            : 'PAYPAL_SANDBOX_WEBHOOK_ID'),
        ''),
];
