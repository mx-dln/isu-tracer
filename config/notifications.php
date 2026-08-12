<?php

declare(strict_types=1);

/**
 * Notification delivery configuration.
 */
return [
    'test_mode' => (bool) (env('NOTIFICATION_TEST_MODE', false) === true || env('NOTIFICATION_TEST_MODE', false) === 'true'),
];
