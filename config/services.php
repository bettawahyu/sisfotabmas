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

    'sinta' => [
        // Public site, for links to a lecturer's profile.
        'url' => env('SINTA_URL', 'https://sinta.kemdiktisaintek.go.id'),
        // Official API account issued by the SINTA team; syncing is off until it is filled in.
        'api' => [
            'url' => env('SINTA_API_URL', 'http://apisinta.kemdikbud.go.id'),
            'env' => env('SINTA_API_ENV', 'prod'),
            'uniq' => env('SINTA_API_UNIQ'),
            'username' => env('SINTA_API_USERNAME'),
            'password' => env('SINTA_API_PASSWORD'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
