<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-based logger writing to storage/logs.
 */
final class Logger
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $dir = config('app.paths.storage_logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        $line = sprintf(
            "[%s] %s.%s: %s %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            (string) microtime(true),
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '',
            PHP_EOL
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            self::write('debug', $message, $context);
        }
    }
}
