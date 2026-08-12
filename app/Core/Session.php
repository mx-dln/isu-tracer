<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Session wrapper with flash data support.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $lifetime = (int) config('app.session.lifetime', 120) * 60;
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => (bool) config('app.session.cookie_secure', false),
            'httponly' => (bool) config('app.session.cookie_httponly', true),
            'samesite' => config('app.session.cookie_samesite', 'Lax'),
        ]);
        session_name((string) config('app.session.name', 'iat_tracer_session'));
        session_start();

        // Regenerate to prevent session fixation on start if not already fixed.
        if (!self::has('_session_fixed')) {
            session_regenerate_id(true);
            self::put('_session_fixed', true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Consume all pending flash messages for the current request.
     */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /**
     * Store old input for repopulating forms.
     */
    public static function flashOldInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public static function forgetOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    public static function pullErrors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $errors;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function id(): string
    {
        return session_id();
    }
}
