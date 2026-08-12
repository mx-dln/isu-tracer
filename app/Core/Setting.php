<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cached access to system_settings table.
 */
final class Setting
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $value = is_scalar($value) ? (string) $value : json_encode($value);
        Database::run(
            'INSERT INTO system_settings (`key`, `value`, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()',
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        self::load();
        return self::$cache;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        $rows = Database::fetchAll('SELECT `key`, `value` FROM system_settings');
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['key']] = $row['value'];
        }
        self::$cache = $cache;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
