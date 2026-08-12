<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Graduate competency self-assessment ratings.
 */
final class CompetencyResponse
{
    /**
     * Save (upsert) a rating for a graduate + competency pair.
     */
    public static function save(int $graduateId, int $competencyId, int $rating): void
    {
        Database::run(
            'INSERT INTO competency_responses (graduate_id, competency_id, rating)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()',
            [$graduateId, $competencyId, $rating]
        );
    }

    public static function replaceAll(int $graduateId, array $ratings): int
    {
        if ($ratings === []) {
            return 0;
        }
        Database::run('DELETE FROM competency_responses WHERE graduate_id = ?', [$graduateId]);
        foreach ($ratings as $competencyId => $rating) {
            self::save($graduateId, (int) $competencyId, (int) $rating);
        }
        return count($ratings);
    }
}