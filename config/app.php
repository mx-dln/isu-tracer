<?php

declare(strict_types=1);

/**
 * Application configuration.
 */
return [
    'name'        => env('APP_NAME', 'ISU-Cauayan IAT Tracer Study'),
    'env'         => env('APP_ENV', 'production'),
    'debug'       => env('APP_DEBUG', false) === true || env('APP_DEBUG', false) === 'true',
    'url'         => rtrim((string) env('APP_URL', 'http://localhost:8080'), '/'),
    'timezone'    => env('APP_TIMEZONE', 'Asia/Manila'),
    'key'         => env('APP_KEY', ''),
    'session'     => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'name'     => env('SESSION_NAME', 'iat_tracer_session'),
        'cookie_secure' => false, // enable over HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'security'    => [
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 15),
    ],
    'paths'       => [
        'root'     => dirname(__DIR__),
        'public'   => dirname(__DIR__) . '/public',
        'storage'  => dirname(__DIR__) . '/storage',
        'views'    => dirname(__DIR__) . '/views',
        'routes'   => dirname(__DIR__) . '/routes',
        'storage_logs' => dirname(__DIR__) . '/storage/logs',
        'storage_exports' => dirname(__DIR__) . '/storage/exports',
        'storage_uploads' => dirname(__DIR__) . '/storage/uploads',
        'storage_forecasts' => dirname(__DIR__) . '/storage/forecasts',
    ],
];
