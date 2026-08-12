<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Saved invitation target selections for a survey (Phase 15).
 *
 * A saved target records an administrator's intention to invite a graduate.
 * It is NOT an invitation — invitations are created separately via
 * SurveyInvitation::issue() once the admin confirms generation.
 */
final class SurveyInvitationTarget
{
    /**
     * Persist the given graduate IDs as targets for a survey.
     * Verifies every ID refers to a real, non-deleted graduate.
     * Returns the number of graduates actually saved.
     */
    public static function saveForSurvey(int $surveyId, array $graduateIds, ?int $userId): int
    {
        $count = 0;
        foreach ($graduateIds as $gid) {
            $gid = (int) $gid;
            if ($gid <= 0) {
                continue;
            }
            $exists = Database::fetch('SELECT id FROM graduates WHERE id = ? AND deleted_at IS NULL', [$gid]);
            if (!$exists) {
                continue;
            }
            Database::run(
                'INSERT INTO survey_invitation_targets (survey_id, graduate_id, selected_by)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE selected_by = VALUES(selected_by)',
                [$surveyId, $gid, $userId]
            );
            $count++;
        }
        return $count;
    }

    /**
     * Remove a single saved target. Refuses when an invitation already exists
     * for the graduate (the target may no longer be safely removed).
     */
    public static function remove(int $surveyId, int $graduateId): bool
    {
        $invited = Database::fetch(
            'SELECT id FROM survey_invitations WHERE survey_id = ? AND graduate_id = ?',
            [$surveyId, $graduateId]
        );
        if ($invited) {
            return false;
        }
        Database::run(
            'DELETE FROM survey_invitation_targets WHERE survey_id = ? AND graduate_id = ?',
            [$surveyId, $graduateId]
        );
        return true;
    }

    /**
     * Remove all saved targets for a survey. Invitations are NOT touched.
     */
    public static function clear(int $surveyId): int
    {
        $count = self::count($surveyId);
        Database::run('DELETE FROM survey_invitation_targets WHERE survey_id = ?', [$surveyId]);
        return $count;
    }

    public static function count(int $surveyId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_invitation_targets WHERE survey_id = ?',
            [$surveyId]
        )['c'];
    }

    /**
     * Full saved selection for a survey with graduate + invitation status.
     * Status is derived, not stored: saved | invited | completed | expired | revoked.
     */
    public static function forSurvey(int $surveyId): array
    {
        return Database::fetchAll(
            'SELECT t.id AS target_id, t.graduate_id, g.student_number, g.first_name, g.last_name, g.email, g.contact_number,
                    g.graduation_year, p.code AS program_code, b.year AS batch_year,
                    i.id AS invitation_id, i.status AS invitation_status
             FROM survey_invitation_targets t
             JOIN graduates g ON g.id = t.graduate_id AND g.deleted_at IS NULL
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             LEFT JOIN survey_invitations i ON i.survey_id = t.survey_id AND i.graduate_id = t.graduate_id
             WHERE t.survey_id = ?
             ORDER BY g.last_name ASC, g.first_name ASC',
            [$surveyId]
        );
    }
}
