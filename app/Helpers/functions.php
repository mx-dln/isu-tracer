<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

if (!function_exists('env')) {
    /**
     * Read a value from the environment (.env). Returns $default when missing.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
        return $value;
    }
}

if (!function_exists('config_path')) {
    /**
     * Absolute path to the config directory.
     */
    function config_path(string $file = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $file !== '' ? $base . '/config/' . ltrim($file, '/') : $base . '/config';
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $base = base_path('storage');
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation (config('app.name')).
     */
    function config(string $key, mixed $default = null): mixed
    {
        return App::config($key, $default);
    }
}

if (!function_exists('e')) {
    /**
     * Escape output for safe HTML rendering.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Build an application URL.
     */
    function url(string $path = ''): string
    {
        $base = rtrim(config('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Build a URL to a public asset.
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a route/path.
     */
    function redirect(string $to, int $status = 302): never
    {
        if (preg_match('/^https?:\/\//', $to) !== 1) {
            $to = url($to);
        }
        header('Location: ' . $to, true, $status);
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous page.
     */
    function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('');
        redirect($referer, 302);
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with an HTTP error code and render an error page.
     */
    function abort(int $code = 404, string $message = ''): never
    {
        http_response_code($code);
        $titles = [403 => 'Forbidden', 404 => 'Page Not Found', 419 => 'Session Expired', 500 => 'Server Error'];
        $data = [
            'code'    => $code,
            'title'   => $titles[$code] ?? 'Error',
            'message' => $message,
        ];
        View::render('errors/error', $data, 'error');
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve an old input value from the session flash data.
     */
    function old(string $key, mixed $default = null): mixed
    {
        $old = Session::get('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Output a hidden CSRF token input.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Return the current CSRF token.
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('auth')) {
    /**
     * Get the authenticated user (or null).
     */
    function auth(): ?object
    {
        return Auth::user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return Auth::id();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::check() && Auth::user()->role_slug === 'admin';
    }
}

if (!function_exists('is_graduate')) {
    function is_graduate(): bool
    {
        return Auth::check() && Auth::user()->role_slug === 'graduate';
    }
}

if (!function_exists('current_path')) {
    /**
     * The current request path (e.g. '/admin/dashboard').
     */
    function current_path(): string
    {
        static $path = null;
        if ($path === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?: '/';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
            if ($scriptDir !== '.' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
            }
            if ($path === '') {
                $path = '/';
            }
        }
        return $path;
    }
}

if (!function_exists('current_route_active')) {
    /**
     * Determine if a navigation item is active for the current path.
     */
    function current_route_active(string $prefix): bool
    {
        $path = current_path();
        return $path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/');
    }
}

if (!function_exists('flash')) {
    /**
     * Set a one-time flash message ('success' | 'error' | 'warning' | 'info').
     */
    function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}

if (!function_exists('setting')) {
    /**
     * Read a system setting from the database (cached per request).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Core\Setting::get($key, $default);
    }
}

if (!function_exists('option_list')) {
    /**
     * Fetch lightweight option lists (id + name/label) for dropdowns.
     *
     * Supported tables: programs, batches, employment_sectors.
     */
    function option_list(string $table): array
    {
        return match ($table) {
            'programs' => \App\Core\Database::fetchAll(
                'SELECT id, code, name FROM programs WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name'
            ),
            'batches' => \App\Core\Database::fetchAll(
                'SELECT id, year, label FROM batches WHERE is_active = 1 AND deleted_at IS NULL ORDER BY year DESC'
            ),
            'employment_sectors' => \App\Core\Database::fetchAll(
                'SELECT id, name FROM employment_sectors WHERE deleted_at IS NULL ORDER BY name'
            ),
            default => [],
        };
    }
}
