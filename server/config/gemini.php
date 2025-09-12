<?php

return [
    'api_key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),

    'oauth' => [
        'client_id'     => env('GEMINI_OAUTH_CLIENT_ID'),
        'client_secret' => env('GEMINI_OAUTH_CLIENT_SECRET'),
        'refresh_token' => env('GEMINI_OAUTH_REFRESH_TOKEN'),

        'token_uri'     => env('GEMINI_OAUTH_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
    ],
];

