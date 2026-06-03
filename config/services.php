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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'email' => [
        'delivery_driver' => env('EMAIL_DELIVERY_DRIVER', 'brevo_api'),
        'brevo' => [
            'api_base_url' => env('BREVO_API_BASE_URL', 'https://api.brevo.com'),
            'timeout' => (int) env('EMAIL_BREVO_TIMEOUT_SECONDS', env('NEWSLETTER_BREVO_TIMEOUT_SECONDS', 15)),
            'connect_timeout' => (int) env('EMAIL_BREVO_CONNECT_TIMEOUT_SECONDS', env('NEWSLETTER_BREVO_CONNECT_TIMEOUT_SECONDS', 5)),
            'api_key' => env('BREVO_API_KEY', ''),
        ],
    ],

    'newsletter' => [
        'public_base_url' => env('NEWSLETTER_PUBLIC_BASE_URL', 'https://www.nordsudaction.ma'),
        'unsubscribe_base_url' => env('NEWSLETTER_UNSUBSCRIBE_BASE_URL', env('APP_URL', 'http://localhost')),
        'unsubscribe_mailto' => env('NEWSLETTER_UNSUBSCRIBE_MAILTO', env('MAIL_FROM_ADDRESS', 'contact@nordsudaction.ma')),
        'send_delay_seconds' => (int) env('NEWSLETTER_SEND_DELAY_SECONDS', 0),
        'brevo' => [
            'use_batch' => filter_var(env('NEWSLETTER_BREVO_USE_BATCH', false), FILTER_VALIDATE_BOOL),
            'enable_list_unsubscribe_headers' => filter_var(env('NEWSLETTER_BREVO_ENABLE_LIST_UNSUBSCRIBE_HEADERS', false), FILTER_VALIDATE_BOOL),
            'batch_size' => (int) env('NEWSLETTER_BREVO_BATCH_SIZE', 100),
        ],
    ],

];
