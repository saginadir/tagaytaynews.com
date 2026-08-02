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

    'posthog' => [
        // PostHog product analytics (EU cloud). Public ingestion key is
        // safe to expose; the personal API key for querying stays in .env.
        'key' => env('POSTHOG_KEY'),
        'host' => env('POSTHOG_HOST', 'https://eu.i.posthog.com'),
        'api_host' => env('POSTHOG_API_HOST', 'https://eu.posthog.com'),
        'personal_key' => env('POSTHOG_PERSONAL_KEY'),
        'project_id' => env('POSTHOG_PROJECT_ID'),
    ],

    'clarity' => [
        // Microsoft Clarity project ID (session recordings + heatmaps).
        // Set CLARITY_ID in .env once the Clarity project exists.
        'id' => env('CLARITY_ID'),
        // Data Export API token (Clarity → Settings → Data Export).
        'token' => env('CLARITY_API_TOKEN'),
    ],

    'indexnow' => [
        'enabled' => env('INDEXNOW_ENABLED', true),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
