<?php

declare(strict_types=1);

/**
 * SMS configuration (Semaphore).
 * All credentials come from environment variables — never hardcode secrets.
 */
return [
    'enabled'     => env('SEMAPHORE_API_KEY', '') !== '',
    'api_key'     => env('SEMAPHORE_API_KEY', ''),
    'sender_name' => env('SEMAPHORE_SENDER_NAME', ''),
    'api_base'    => env('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages'),
    'test_mode'   => (bool) (env('NOTIFICATION_TEST_MODE', false) === true || env('NOTIFICATION_TEST_MODE', false) === 'true'),
];
