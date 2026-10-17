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

    'odds_api' => [
        'key' => env('ODDS_API_KEY'),
        'url' => env('ODDS_API_URL', 'https://api.the-odds-api.com/v4'),
        'sport' => env('ODDS_API_SPORT', 'soccer_france_ligue_one'),
        'regions' => env('ODDS_API_REGIONS', 'fr'),
        'bookmakers' => env('ODDS_API_BOOKMAKERS'),
        'odds_format' => env('ODDS_API_ODDS_FORMAT', 'decimal'),
        'refresh_minutes' => (int) env('ODDS_FETCH_MINUTES', 30),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
