<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Graduate curriculum feedback ratings.
 */
final class CurriculumFeedback
{
    /**
     * Save (upsert) a rating + optional comment for a graduate + question pair.
     */
    public static function save(int $graduateId, int $questionId, int $rating, ?string $comment = null): void
    {
        Database::run(
            'INSERT INTO curriculum_feedback (graduate_id, question_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), updated_at = NOW()',
            [$graduateId, $questionId, $rating, $comment]
        );
    }

    public static function replaceAll(int $graduateId, array $ratings, array $comments = []): int
    {
        if ($ratings === []) {
            return 0;
        }
        Database::run('DELETE FROM curriculum_feedback WHERE graduate_id = ?', [$graduateId]);
        foreach ($ratings as $questionId => $rating) {
            self::save($graduateId, (int) $questionId, (int) $rating, $comments[(int) $questionId] ?? null);
        }
        return count($ratings);
    }

    public static function hasSubmitted(int $graduateId): bool
    {
        return (bool) Database::fetch(
            'SELECT id FROM curriculum_feedback WHERE graduate_id = ? LIMIT 1',
            [$graduateId]
        );
    }

    /**
     * Existing feedback keyed by question id for the graduate.
     */
    public static function forGraduate(int $graduateId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM curriculum_feedback WHERE graduate_id = ?',
            [$graduateId]
        );
        $keyed = [];
        foreach ($rows as $row) {
            $keyed[(int) $row['question_id']] = $row;
        }
        return $keyed;
    }
}
