<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zainpay' => [
        'secret_key'         => env('ZAINPAY_SECRET_KEY'),
        'inline_key'         => env('ZAINPAY_INLINE_KEY'),
        'private_key'        => env('ZAINPAY_PRIVATE_KEY', env('ZAINPAY_SECRET_KEY')),
        'zainbox_code'       => env('ZAINPAY_ZAINBOX_CODE', '84889_gcfrR211UbtKsqW9fLsE'),
        'webhook_secret'     => env('ZAINPAY_WEBHOOK_SECRET'),
        'source_va_number'   => env('ZAINPAY_SOURCE_VA_NUMBER'),
        'source_va_bank_code'=> env('ZAINPAY_SOURCE_VA_BANK_CODE'),
        // DVA (per-transaction) accounts must use gtBank; fidelity is only valid
        // for static VAs (per ZainPay support, July 2026).
        'dva_bank_type'      => env('ZAINPAY_DVA_BANK_TYPE', 'gtBank'),
    ],

];
