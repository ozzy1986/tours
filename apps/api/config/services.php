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

    'embeddings' => [
        'url' => env('EMBEDDINGS_URL', 'http://127.0.0.1:8001'),
        'dim' => (int) env('EMBEDDINGS_DIM', 384),
        'timeout' => (int) env('EMBEDDINGS_TIMEOUT', 30),
        'api_key' => env('EMBEDDINGS_API_KEY'),
    ],

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai'),
        'base_url' => env('LLM_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('LLM_API_KEY'),
        'model' => env('LLM_MODEL', 'gpt-4o-mini'),
        'temperature' => (float) env('LLM_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 2048),
        'timeout' => (int) env('LLM_TIMEOUT', 600),
    ],

];
