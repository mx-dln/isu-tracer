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
        if (!empty($filters['category_id'])) {
            $where .= ' AND cc.id = ?';
            $params[] = (int) $filters['category_id'];
        }

        $from = 'FROM graduates g
            JOIN competency_responses cr ON cr.graduate_id = g.id
            JOIN competencies c ON c.id = cr.competency_id AND c.deleted_at IS NULL
            JOIN competency_categories cc ON cc.id = c.category_id AND cc.deleted_at IS NULL
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
                    COUNT(cr.id) AS response_count, ROUND(AVG(cr.rating), 2) AS average_rating,
                    MIN(cr.submitted_at) AS first_submitted_at, MAX(cr.updated_at) AS last_updated_at
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
