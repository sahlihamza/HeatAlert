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

    'nasa_firms' => [
        'map_key' => env('NASA_FIRMS_MAP_KEY', ''),
        'default_source' => env('NASA_FIRMS_SOURCE', 'VIIRS_SNPP_NRT'),
        'cache_ttl' => (int) env('NASA_FIRMS_CACHE_TTL', 900), // 15 minutes
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model'   => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY', ''),
        'model'   => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
    ],

    // Azure Cognitive Services TTS (ar-TN-ReemNeural / ar-TN-HediNeural)
    // Gratuit jusqu'à 500 000 caractères/mois
    'azure_tts' => [
        'key'    => env('AZURE_TTS_KEY', ''),
        'region' => env('AZURE_TTS_REGION', 'eastus'),
    ],

    'heatalert_ml' => [
        'url' => env('HEATALERT_ML_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('HEATALERT_ML_TIMEOUT', 5),
        'token' => env('HEATALERT_ML_TOKEN'),
    ],

    // STEG — Société Tunisienne de l'Électricité et du Gaz
    'steg' => [
        'cache_ttl' => (int) env('STEG_CACHE_TTL', 7200), // 2 heures
    ],

];
