<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Static configuration registry built from config/*.php files.
 */
final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $items = [];

    /**
     * Load a config file into the registry once.
     */
    public static function load(string $file): void
    {
        $key = pathinfo($file, PATHINFO_FILENAME);
        if (!isset(self::$items[$key])) {
            self::$items[$key] = require config_path($file);
        }
    }

    /**
     * Get a value using dot notation: config('app.name').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);
        self::load($file . '.php');

        $value = self::$items[$file] ?? [];
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public static function all(): array
    {
        return self::$items;
    }
}
