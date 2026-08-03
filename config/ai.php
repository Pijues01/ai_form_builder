<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider driver
    |--------------------------------------------------------------------------
    | "openai"      -> any OpenAI-compatible chat-completions REST API
    | "mock"        -> deterministic local generator (no network, for demos/tests)
    */
    'driver' => env('AI_DRIVER', 'mock'),

    'base_url' => rtrim(env('AI_BASE_URL', 'https://api.openai.com/v1'), '/'),

    'api_key' => env('AI_API_KEY', ''),

    'model' => env('AI_MODEL', 'gpt-4o-mini'),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 3000),

    'temperature' => (float) env('AI_TEMPERATURE', 0.3),

    'timeout' => (int) env('AI_TIMEOUT', 90),

    'retries' => (int) env('AI_RETRIES', 2),

];
