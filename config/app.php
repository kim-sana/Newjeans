<?php

return [
    'name' => 'NewJeans Fan Hub',
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'session' => [
        'name' => 'newjeans_session',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => env('APP_ENV', 'production') === 'production',
    ],
    'uploads' => [
        'max_size' => 5 * 1024 * 1024,
    ],
];
