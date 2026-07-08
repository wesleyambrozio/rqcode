<?php

return [
    'name' => env('APP_NAME', 'Central SaaS'),
    'env' => env('APP_ENV', 'production'),
    'url' => env('APP_URL', 'http://localhost'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
];
