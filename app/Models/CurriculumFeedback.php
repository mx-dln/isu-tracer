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

    public static function respondents(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE g.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (g.student_number LIKE ? OR g.last_name LIKE ? OR g.first_name LIKE ? OR g.email LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if (!empty($filters['program_id'])) {
            $where .= ' AND g.program_id = ?';
            $params[] = (int) $filters['program_id'];
        }
        if (!empty($filters['batch_id'])) {
            $where .= ' AND g.batch_id = ?';
            $params[] = (int) $filters['batch_id'];
        }
        if (!empty($filters['question_id'])) {
            $where .= ' AND cq.id = ?';
            $params[] = (int) $filters['question_id'];
        }

        $from = 'FROM graduates g
            JOIN curriculum_feedback cf ON cf.graduate_id = g.id
            JOIN curriculum_questions cq ON cq.id = cf.question_id AND cq.deleted_at IS NULL
            JOIN programs p ON p.id = g.program_id
            JOIN batches b ON b.id = g.batch_id';

        $total = (int) Database::fetch(
            "SELECT COUNT(*) AS c FROM (SELECT g.id {$from} {$where} GROUP BY g.id) respondents",
            $params
        )['c'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT g.id AS graduate_id, g.student_number, g.first_name, g.last_name, g.email,
                    p.code AS program_code, p.name AS program_name, b.year AS batch_year,
                    COUNT(cf.id) AS response_count, ROUND(AVG(cf.rating), 2) AS average_rating,
                    SUM(CASE WHEN cf.comment IS NOT NULL AND cf.comment <> '' THEN 1 ELSE 0 END) AS comment_count,
                    MIN(cf.submitted_at) AS first_submitted_at, MAX(cf.updated_at) AS last_updated_at
             {$from}
             {$where}
             GROUP BY g.id, g.student_number, g.first_name, g.last_name, g.email, p.code, p.name, b.year
             ORDER BY last_updated_at DESC, g.last_name, g.first_name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }
}
