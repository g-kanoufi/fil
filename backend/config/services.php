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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
        'scheme' => 'https',
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    'dwolla' => [
        'key' => env('DWOLLA_KEY'),
        'secret' => env('DWOLLA_SECRET'),
        'token' => env('DWOLLA_TOKEN'),
        'environment' => env('DWOLLA_ENVIRONMENT', 'sandbox'),
        'webhook_secret' => env('DWOLLA_WEBHOOK_SECRET'),
        'destination_funding_source_id' => env('DWOLLA_DESTINATION_FUNDING_SOURCE_ID'),
        'terms_url' => env('DWOLLA_TERMS_URL', 'https://www.dwolla.com/legal/tos/'),
        'privacy_url' => env('DWOLLA_PRIVACY_URL', 'https://www.dwolla.com/legal/privacy/'),
    ],

    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET'),
        'environment' => env('PLAID_ENVIRONMENT', 'sandbox'),
        'webhook_url' => env('PLAID_WEBHOOK_URL'),
    ],

];
