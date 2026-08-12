<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Submitted tracer survey response and answers.
 */
final class SurveyResponse
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM survey_responses WHERE id = ?', [$id]);
    }

    public static function findForGraduate(int $surveyId, int $graduateId): ?array
    {
        return Database::fetch(
            'SELECT * FROM survey_responses WHERE survey_id = ? AND graduate_id = ?',
            [$surveyId, $graduateId]
        );
    }

    public static function findByInvitation(int $invitationId): ?array
    {
        return Database::fetch(
            'SELECT * FROM survey_responses WHERE invitation_id = ?',
            [$invitationId]
        );
    }

    /**
     * Create a response record for a graduate (idempotent via unique key).
     */
    public static function ensure(int $surveyId, int $graduateId, string $ip, string $userAgent, ?int $invitationId = null): int
    {
        $existing = self::findForGraduate($surveyId, $graduateId);
        if ($existing) {
            return (int) $existing['id'];
        }
        Database::run(
            'INSERT INTO survey_responses (survey_id, graduate_id, invitation_id, status, ip_address, user_agent, submitted_at)
             VALUES (?, ?, ?, "draft", ?, ?, NULL)',
            [$surveyId, $graduateId, $invitationId, $ip, mb_substr($userAgent, 0, 255)]
        );
        return (int) Database::lastInsertId();
    }

    public static function saveAnswer(int $responseId, int $questionId, ?string $value, ?array $options = null): void
    {
        Database::run(
            'INSERT INTO survey_answers (response_id, question_id, answer_value, answer_options)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE answer_value = VALUES(answer_value), answer_options = VALUES(answer_options)',
            [
                $responseId,
                $questionId,
                $value,
                $options !== null ? json_encode($options) : null,
            ]
        );
    }

    public static function submit(int $responseId): void
    {
        Database::run(
            'UPDATE survey_responses SET status = "submitted", submitted_at = NOW() WHERE id = ?',
            [$responseId]
        );
    }

    /**
     * All answers for a response keyed by question id.
     */
    public static function answers(int $responseId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*, q.question_text, q.type, q.question_key
             FROM survey_answers a
             JOIN survey_questions q ON q.id = a.question_id
             WHERE a.response_id = ?',
            [$responseId]
        );
        $keyed = [];
        foreach ($rows as $row) {
            $keyed[$row['question_id']] = $row;
        }
        return $keyed;
    }

    /**
     * Completed responses for the graduate dashboard stats.
     */
    public static function completedCount(int $graduateId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_responses WHERE graduate_id = ? AND status = "submitted"',
            [$graduateId]
        )['c'];
    }

    /**
     * Lightweight per-IP submission throttle guard (max submissions per hour).
     */
    public static function ipThrottled(int $surveyId, string $ip, int $maxPerHour = 10): bool
    {
        $hourAgo = date('Y-m-d H:i:s', time() - 3600);
        $count = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_responses WHERE survey_id = ? AND ip_address = ? AND status = "submitted" AND submitted_at >= ?',
            [$surveyId, $ip, $hourAgo]
        )['c'];
        return $count >= $maxPerHour;
    }
}
