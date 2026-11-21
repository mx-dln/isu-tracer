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
                    self::ensureGraduateRecord($row);
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
        self::ensureGraduateRecord($row);
        self::$user = (object) $row;
        self::$resolved = true;
    }

    /**
     * Ensure a graduate-role user always has a valid, non-deleted linked record in `graduates`.
     */
    private static function ensureGraduateRecord(array &$row): void
    {
        if (($row['role_slug'] ?? '') !== 'graduate') {
            return;
        }

        $userId = (int) ($row['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $gradId = !empty($row['graduate_id']) ? (int) $row['graduate_id'] : 0;
        if ($gradId > 0) {
            $existing = Database::fetch('SELECT id, deleted_at FROM graduates WHERE id = ?', [$gradId]);
            if ($existing) {
                if ($existing['deleted_at'] !== null) {
                    Database::run('UPDATE graduates SET deleted_at = NULL WHERE id = ?', [$gradId]);
                }
                return;
            }
        }

        // Try matching by user_id, email, or full name in `graduates`
        $email = trim((string) ($row['email'] ?? ''));
        $fullName = trim((string) ($row['name'] ?? ''));

        $match = Database::fetch(
            'SELECT id, deleted_at FROM graduates
             WHERE user_id = ?
                OR (? <> "" AND LOWER(TRIM(email)) = LOWER(?))
                OR (? <> "" AND LOWER(TRIM(CONCAT(first_name, " ", last_name))) = LOWER(?))
             ORDER BY (user_id = ?) DESC, (deleted_at IS NULL) DESC, id DESC
             LIMIT 1',
            [$userId, $email, $email, $fullName, $fullName, $userId]
        );

        if ($match) {
            $linkedId = (int) $match['id'];
            Database::run('UPDATE graduates SET user_id = ?, deleted_at = NULL WHERE id = ?', [$userId, $linkedId]);
            Database::run('UPDATE users SET graduate_id = ? WHERE id = ?', [$linkedId, $userId]);
            $row['graduate_id'] = $linkedId;
            return;
        }

        // Auto-create a graduate profile record if none exists for this user account
        $parts = preg_split('/\s+/', $fullName ?: 'Graduate User', 2);
        $firstName = $parts[0] ?? 'Graduate';
        $lastName = $parts[1] ?? 'User';

        $prog = Database::fetch('SELECT id, code FROM programs WHERE deleted_at IS NULL ORDER BY (code = "BAT") DESC, id ASC LIMIT 1');
        $batch = Database::fetch('SELECT id, year FROM batches WHERE deleted_at IS NULL ORDER BY year DESC, id DESC LIMIT 1');

        if (!$prog || !$batch) {
            return;
        }

        $programId = (int) $prog['id'];
        $major = ($prog['code'] ?? '') === 'BAT' ? 'Crop Production' : null;
        $batchId = (int) $batch['id'];
        $batchYear = (int) $batch['year'];
        $studentNo = sprintf('%d-%04d', $batchYear, $userId);

        // Avoid duplicate student_number collision
        if (Database::fetch('SELECT id FROM graduates WHERE student_number = ?', [$studentNo])) {
            $studentNo = sprintf('%d-U%04d', $batchYear, $userId);
        }

        Database::run(
            'INSERT INTO graduates (user_id, student_number, first_name, last_name, email, program_id, major, batch_id, graduation_year, is_validated, is_demo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)',
            [$userId, $studentNo, $firstName, $lastName, $email !== '' ? $email : null, $programId, $major, $batchId, $batchYear]
        );
        $newGradId = (int) Database::lastInsertId();
        Database::run('UPDATE users SET graduate_id = ? WHERE id = ?', [$newGradId, $userId]);
        $row['graduate_id'] = $newGradId;
    }
}
