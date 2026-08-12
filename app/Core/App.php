<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use Throwable;

/**
 * Application bootstrap: environment, error handling, session, routing.
 */
final class App
{
    private static ?string $basePath = null;

    public static function basePath(): string
    {
        return self::$basePath;
    }

    /**
     * Read a configuration value (delegates to Config registry).
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }

    /**
     * Boot the application. Called from public/index.php.
     */
    public static function boot(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/\\');

        // Load environment.
        $envFile = self::$basePath . '/.env';
        if (is_file($envFile)) {
            Dotenv::createImmutable(self::$basePath)->safeLoad();
        }

        // Timezone.
        date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Manila'));

        // Register error handling.
        self::registerErrorHandlers();

        // Load config files eagerly so dot-notation lookups are cheap.
        foreach (['app', 'database', 'mail'] as $file) {
            Config::load($file . '.php');
        }

        // Session.
        Session::start();

        // Load routes.
        $router = new Router();
        $routesFile = self::$basePath . '/routes/web.php';
        if (is_file($routesFile)) {
            require $routesFile;
        }
        $apiFile = self::$basePath . '/routes/api.php';
        if (is_file($apiFile)) {
            require $apiFile;
        }

        // Dispatch.
        $request = new Request();
        $router->dispatch($request);
    }

    private static function registerErrorHandlers(): void
    {
        $debug = env('APP_DEBUG', false) === true || env('APP_DEBUG', false) === 'true';

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$basePath . '/storage/logs/php-error.log');

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            // Do not escalate deprecations; only real errors.
            $escalate = $severity & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE);
            if ($escalate) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });

        set_exception_handler(function (Throwable $e) use ($debug): void {
            Logger::error($e->getMessage(), [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            http_response_code(500);
            if ($debug) {
                // Show a friendly page with useful debug details.
                View::render('errors/error', [
                    'code'    => 500,
                    'title'   => 'Server Error',
                    'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                    'debug'   => $e->getTraceAsString(),
                ], 'error');
            } else {
                View::render('errors/error', [
                    'code'    => 500,
                    'title'   => 'Server Error',
                    'message' => 'An unexpected error occurred. Please try again later.',
                ], 'error');
            }
            exit;
        });
    }
}
