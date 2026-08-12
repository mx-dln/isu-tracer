<?php

declare(strict_types=1);

/**
 * Mail configuration (PHPMailer / SMTP).
 */
return [
    'enabled'    => env('MAIL_HOST', '') !== '',
    'mailer'     => env('MAIL_MAILER', 'smtp'),
    'host'       => env('MAIL_HOST', ''),
    'port'       => (int) env('MAIL_PORT', 587),
    'username'   => env('MAIL_USERNAME', ''),
    'password'   => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    'from_name'    => env('MAIL_FROM_NAME', 'ISU IAT Tracer'),
    'debug'      => env('MAIL_DEBUG', false) === true || env('MAIL_DEBUG', false) === 'true',
];
