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

    /*
    |--------------------------------------------------------------------------
    | Twilio VOIP Service
    |--------------------------------------------------------------------------
    |
    | Configuration for Twilio voice calling service used for
    | customer-courier masked calling functionality.
    |
    */

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | VOIP Configuration
    |--------------------------------------------------------------------------
    |
    | General VOIP settings. Provider can be 'twilio', 'telnyx', or 'direct'.
    | Direct mode uses tel: links for native phone dialing.
    |
    */

    'voip' => [
        'provider' => env('VOIP_PROVIDER', 'direct'),
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'proxy_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trendyol Go by Uber Eats API
    |--------------------------------------------------------------------------
    |
    | Configuration for Trendyol Go integration. Used for receiving orders,
    | updating order status, and syncing menu with Trendyol Go platform.
    |
    */

    'trendyol' => [
        'api_key' => env('TRENDYOL_API_KEY'),
        'api_secret' => env('TRENDYOL_API_SECRET'),
        'supplier_id' => env('TRENDYOL_SUPPLIER_ID'),
        'store_id' => env('TRENDYOL_STORE_ID'),
        'base_url' => env('TRENDYOL_API_URL', 'https://api.tgoapis.com'),
        'agent_name' => env('TRENDYOL_AGENT_NAME', 'SeferXLojistik'),
        'executor_email' => env('TRENDYOL_EXECUTOR_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM)
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging push notifications.
    | Used for sending push notifications to courier mobile apps.
    |
    */

    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'sender_id' => env('FIREBASE_SENDER_ID'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pusher Mobile Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for Pusher-based real-time notifications to courier
    | mobile apps. Separate from Laravel broadcasting Pusher config.
    |
    */

    'pusher_mobile' => [
        'key' => env('PUSHER_MOBILE_APP_KEY'),
        'secret' => env('PUSHER_MOBILE_APP_SECRET'),
        'app_id' => env('PUSHER_MOBILE_APP_ID'),
        'cluster' => env('PUSHER_MOBILE_APP_CLUSTER', 'eu'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS notification providers.
    | Supported: netgsm, iletimerkezi, twilio
    |
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'netgsm'),

        'netgsm' => [
            'username' => env('NETGSM_USERNAME'),
            'password' => env('NETGSM_PASSWORD'),
            'header' => env('NETGSM_HEADER'),
        ],

        'iletimerkezi' => [
            'api_key' => env('ILETIMERKEZI_API_KEY'),
            'sender' => env('ILETIMERKEZI_SENDER'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp notification providers.
    | Supported: twilio, wati
    |
    */

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),

        'wati' => [
            'api_key' => env('WATI_API_KEY'),
            'api_url' => env('WATI_API_URL'),
        ],
    ],

];
