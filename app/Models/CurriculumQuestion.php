<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Curriculum feedback question reference.
 */
final class CurriculumQuestion
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM curriculum_questions WHERE deleted_at IS NULL';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return Database::fetchAll($sql . ' ORDER BY sort_order');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM curriculum_questions WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND question_text LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM curriculum_questions {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT cq.*, COUNT(cf.id) AS feedback_count
             FROM curriculum_questions cq
             LEFT JOIN curriculum_feedback cf ON cf.question_id = cq.id
             {$where}
             GROUP BY cq.id
             ORDER BY cq.sort_order
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO curriculum_questions (question_text, description, sort_order, is_active) VALUES (?, ?, ?, ?)',
            [$data['question_text'], $data['description'] ?? null, (int) ($data['sort_order'] ?? 0), isset($data['is_active']) ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE curriculum_questions SET question_text = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$data['question_text'], $data['description'] ?? null, (int) ($data['sort_order'] ?? 0), isset($data['is_active']) ? 1 : 0, $id]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE curriculum_questions SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }
}
