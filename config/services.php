<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'sso_unuja' => [
        'url' => env('SSO_UNUJA_URL'),
        'app_id' => env('SSO_UNUJA_APP_ID'),
        'dev_id' => env('SSO_UNUJA_DEV_ID'),
        'x_token' => env('SSO_UNUJA_X_TOKEN'),
    ],

    'campus_api' => [
        'base_url' => env('CAMPUS_API_BASE_URL', 'https://sso.unuja.ac.id/api/portal/data'),
        'app_id' => env('CAMPUS_API_APP_ID'),
        'dev_id' => env('CAMPUS_API_DEV_ID'),
        'x_token' => env('CAMPUS_API_X_TOKEN'),
        'user_agent' => env('CAMPUS_API_USER_AGENT', 'Project-3in1/1.0'),
        'default_password' => env('CAMPUS_DEFAULT_PASSWORD'),
    ],

];
