<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Lightweight per-IP rate limiting backed by the rate_limits table.
 *
 * Buckets name the endpoint class (e.g. public_survey_invalid_tokens) and the
 * caller decides the max hits allowed within a sliding window.
 */
final class RateLimiter
{
    /**
     * Record a hit for a bucket + IP.
     */
    public static function hit(string $bucket, string $ip): void
    {
        Database::run(
            'INSERT INTO rate_limits (bucket, ip_address) VALUES (?, ?)',
            [$bucket, $ip]
        );
    }

    /**
     * Number of hits recorded in the window (seconds) for a bucket + IP.
     */
    public static function hits(string $bucket, string $ip, int $windowSeconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = Database::fetch(
            'SELECT COUNT(*) AS c FROM rate_limits WHERE bucket = ? AND ip_address = ? AND created_at >= ?',
            [$bucket, $ip, $since]
        )['c'];
        return (int) $count;
    }

    /**
     * True when the bucket/IP has exceeded $max hits within $windowSeconds.
     */
    public static function tooManyAttempts(string $bucket, string $ip, int $max, int $windowSeconds): bool
    {
        return self::hits($bucket, $ip, $windowSeconds) >= $max;
    }

    /**
     * Opportunistic prune of rows older than the given window (keeps the
     * table small without a scheduled job).
     */
    public static function prune(string $bucket, int $olderThanSeconds): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $olderThanSeconds);
        Database::run(
            'DELETE FROM rate_limits WHERE bucket = ? AND created_at < ?',
            [$bucket, $cutoff]
        );
    }
}
