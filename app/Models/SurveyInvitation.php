<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Secure one-time-use survey invitation (public, no-login access).
 *
 * The raw token is shown only once (in the invitation link); the database
 * stores only its SHA-256 hash so a leaked database cannot be replayed.
 */
final class SurveyInvitation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_OPENED = 'opened';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM survey_invitations WHERE id = ?', [$id]);
    }

    public static function findByHash(string $tokenHash): ?array
    {
        return Database::fetch('SELECT * FROM survey_invitations WHERE token_hash = ?', [$tokenHash]);
    }

    public static function findBySurvey(int $surveyId, int $graduateId): ?array
    {
        return Database::fetch(
            'SELECT * FROM survey_invitations WHERE survey_id = ? AND graduate_id = ?',
            [$surveyId, $graduateId]
        );
    }

    /**
     * Create (or re-create) an invitation for a graduate.
     * Returns [id, token]; re-issues a fresh token when one already exists.
     */
    public static function issue(int $surveyId, int $graduateId, int $expiryDays): array
    {
        $existing = self::findBySurvey($surveyId, $graduateId);
        if ($existing) {
            return self::rotate((int) $existing['id'], $expiryDays);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = $expiryDays > 0
            ? date('Y-m-d H:i:s', time() + ($expiryDays * 86400))
            : null;

        Database::run(
            'INSERT INTO survey_invitations (survey_id, graduate_id, token_hash, status, expires_at)
             VALUES (?, ?, ?, ?, ?)',
            [$surveyId, $graduateId, hash('sha256', $token), self::STATUS_PENDING, $expiresAt]
        );

        return [(int) Database::lastInsertId(), $token];
    }

    /**
     * Re-issue a fresh token for an existing (non-completed) invitation.
     */
    public static function rotate(int $id, int $expiryDays): array
    {
        $inv = self::find($id);
        if (!$inv) {
            throw new \RuntimeException('Invitation not found.');
        }
        if ($inv['status'] === self::STATUS_COMPLETED) {
            throw new \RuntimeException('A completed invitation cannot be re-issued.');
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = $expiryDays > 0
            ? date('Y-m-d H:i:s', time() + ($expiryDays * 86400))
            : null;

        Database::run(
            'UPDATE survey_invitations SET token_hash = ?, status = ?, expires_at = ?, sent_at = NULL, opened_at = NULL WHERE id = ?',
            [hash('sha256', $token), self::STATUS_PENDING, $expiresAt, $id]
        );

        return [$id, $token];
    }

    public static function markOpened(int $id): void
    {
        Database::run(
            'UPDATE survey_invitations SET status = ?, opened_at = COALESCE(opened_at, NOW()) WHERE id = ? AND status NOT IN (?, ?)',
            [self::STATUS_OPENED, $id, self::STATUS_COMPLETED, self::STATUS_REVOKED]
        );
    }

    public static function markCompleted(int $id): void
    {
        Database::run(
            'UPDATE survey_invitations SET status = ?, completed_at = NOW() WHERE id = ?',
            [self::STATUS_COMPLETED, $id]
        );
    }

    public static function markExpired(int $id): void
    {
        Database::run(
            'UPDATE survey_invitations SET status = ? WHERE id = ? AND status IN (?, ?)',
            [self::STATUS_EXPIRED, $id, self::STATUS_PENDING, self::STATUS_OPENED]
        );
    }

    public static function revoke(int $id): void
    {
        Database::run(
            'UPDATE survey_invitations SET status = ? WHERE id = ? AND status != ?',
            [self::STATUS_REVOKED, $id, self::STATUS_COMPLETED]
        );
    }

    /**
     * Evaluate a token for access. Returns the invitation row (or null).
     * Distinguishes failure reasons via $reason out-param.
     */
    public static function validateAccess(string $rawToken, ?string &$reason = null): ?array
    {
        if ($rawToken === '' || strlen($rawToken) < 20) {
            $reason = 'invalid';
            return null;
        }

        $inv = self::findByHash(hash('sha256', $rawToken));
        if (!$inv) {
            $reason = 'invalid';
            return null;
        }
        if ($inv['status'] === self::STATUS_REVOKED) {
            $reason = 'revoked';
            return null;
        }
        if ($inv['status'] === self::STATUS_COMPLETED) {
            $reason = 'completed';
            return null;
        }
        if ($inv['expires_at'] !== null && strtotime((string) $inv['expires_at']) < time()) {
            self::markExpired((int) $inv['id']);
            $reason = 'expired';
            return null;
        }

        return $inv;
    }

    /**
     * Stats for the survey invitation management page.
     */
    public static function stats(int $surveyId): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS c FROM survey_invitations WHERE survey_id = ? GROUP BY status',
            [$surveyId]
        );
        $stats = [
            'total' => 0,
            'pending' => 0,
            'opened' => 0,
            'completed' => 0,
            'expired' => 0,
            'revoked' => 0,
        ];
        foreach ($rows as $row) {
            $stats[$row['status']] = (int) $row['c'];
            $stats['total'] += (int) $row['c'];
        }
        $stats['response_rate'] = $stats['total'] > 0
            ? round(($stats['completed'] / $stats['total']) * 100, 1)
            : 0.0;
        return $stats;
    }

    /**
     * Paginated invitation list with graduate details.
     */
    public static function paginate(int $surveyId, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE i.survey_id = ?';
        $params = [$surveyId];

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM survey_invitations i {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT i.*, g.student_number, g.first_name, g.last_name, g.email, g.is_demo,
                    p.code AS program_code, b.year AS batch_year,
                    r.id AS response_id, r.submitted_at AS response_submitted_at
             FROM survey_invitations i
             LEFT JOIN graduates g ON g.id = i.graduate_id
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             LEFT JOIN survey_responses r ON r.invitation_id = i.id
             {$where}
             ORDER BY i.created_at DESC, i.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }
}
