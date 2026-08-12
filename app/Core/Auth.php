<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Authentication service: login, logout, session user, throttling.
 */
final class Auth
{
    private static ?object $user = null;
    private static bool $resolved = false;

    /**
     * Attempt to authenticate a user. Returns false when credentials are invalid.
     */
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = Database::fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.deleted_at IS NULL',
            [$email]
        );

        if (!$user || !password_verify($password, (string) $user['password'])) {
            self::logLogin($user['id'] ?? null, false, 'invalid_credentials');
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            self::logLogin($user['id'], false, 'account_disabled');
            return false;
        }

        self::loginFromRecord($user, $remember);
        return true;
    }

    /**
     * Log in a user directly (e.g. after registration).
     */
    public static function login(int $userId): bool
    {
        $user = Database::fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.deleted_at IS NULL AND u.is_active = 1',
            [$userId]
        );
        if (!$user) {
            return false;
        }
        self::loginFromRecord($user);
        return true;
    }

    private static function loginFromRecord(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::put('auth_user_id', (int) $user['id']);
        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        self::logLogin($user['id'], true, null);
        self::setUser($user);
    }

    /**
     * Get the current authenticated user object, or null.
     */
    public static function user(): ?object
    {
        if (!self::$resolved) {
            self::$resolved = true;
            $id = Session::get('auth_user_id');
            if ($id) {
                $row = Database::fetch(
                    'SELECT u.*, r.slug AS role_slug, r.name AS role_name
                     FROM users u
                     JOIN roles r ON r.id = u.role_id
                     WHERE u.id = ? AND u.deleted_at IS NULL AND u.is_active = 1',
                    [$id]
                );
                if ($row) {
                    self::$user = (object) $row;
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user->id : null;
    }

    public static function logout(): void
    {
        $id = Session::get('auth_user_id');
        if ($id) {
            Database::run('UPDATE login_logs SET logout_at = NOW() WHERE user_id = ? AND logout_at IS NULL ORDER BY login_at DESC LIMIT 1', [$id]);
        }
        Session::destroy();
        Session::start();
        Session::regenerate();
        self::$user = null;
        self::$resolved = false;
    }

    /**
     * Check whether the current IP is locked out due to repeated failures.
     */
    public static function isLockedOut(): bool
    {
        $max = (int) config('app.security.login_max_attempts', 5);
        $minutes = (int) config('app.security.login_lockout_minutes', 15);
        $ip = (new Request())->ip();
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $count = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM login_logs
             WHERE status = "failed" AND ip_address = ? AND login_at >= ?',
            [$ip, $since]
        )['c'];

        return $count >= $max;
    }

    /**
     * Remaining lockout seconds, or 0 when not locked out.
     */
    public static function lockoutRemaining(): int
    {
        $max = (int) config('app.security.login_max_attempts', 5);
        $minutes = (int) config('app.security.login_lockout_minutes', 15);
        $ip = (new Request())->ip();
        $last = Database::fetch(
            'SELECT MAX(login_at) AS last FROM login_logs
             WHERE status = "failed" AND ip_address = ?',
            [$ip]
        )['last'] ?? null;

        if (!$last) {
            return 0;
        }
        $remaining = (int) $last + ($minutes * 60) - time();
        return $remaining > 0 ? $remaining : 0;
    }

    private static function logLogin(?int $userId, bool $success, ?string $reason): void
    {
        $request = new Request();
        Database::run(
            'INSERT INTO login_logs (user_id, ip_address, user_agent, status, failure_reason, login_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$userId, $request->ip(), $request->userAgent(), $success ? 'success' : 'failed', $reason]
        );
    }

    private static function setUser(array $row): void
    {
        self::$user = (object) $row;
        self::$resolved = true;
    }
}
