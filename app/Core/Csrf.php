<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generation and validation.
 */
final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::put('_csrf_token', bin2hex(random_bytes(32)));
        }
        return (string) Session::get('_csrf_token');
    }

    /**
     * Validate the token from a request (header or _token field).
     */
    public static function validate(?string $token = null): bool
    {
        $token = $token ?? $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        $expected = Session::get('_csrf_token');

        if (!is_string($token) || !is_string($expected)) {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function validateOrAbort(): void
    {
        if (!self::validate()) {
            abort(419, 'Your session has expired. Please try again.');
        }
    }
}
