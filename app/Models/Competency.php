<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Individual competency under a category.
 */
final class Competency
{
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT c.*, cc.name AS category_name
             FROM competencies c
             JOIN competency_categories cc ON cc.id = c.category_id
             WHERE c.id = ? AND c.deleted_at IS NULL',
            [$id]
        );
    }

    public static function allForGraduate(int $graduateId): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.name, c.description, cc.name AS category_name, cc.sort_order AS cat_order,
                    c.sort_order, cr.rating
             FROM competencies c
             JOIN competency_categories cc ON cc.id = c.category_id
             LEFT JOIN competency_responses cr ON cr.competency_id = c.id AND cr.graduate_id = ?
             WHERE c.deleted_at IS NULL AND c.is_active = 1 AND cc.deleted_at IS NULL AND cc.is_active = 1
             ORDER BY cc.sort_order, c.sort_order',
            [$graduateId]
        );
    }

    public static function paginate(?int $categoryId = null, ?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE c.deleted_at IS NULL';
        $params = [];
        if ($categoryId) {
            $where .= ' AND c.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== null && $search !== '') {
            $where .= ' AND c.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM competencies c {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT c.*, cc.name AS category_name
             FROM competencies c
             JOIN competency_categories cc ON cc.id = c.category_id
             {$where}
             ORDER BY cc.sort_order, c.sort_order, c.name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }

    public static function create(int $categoryId, array $data): int
    {
        Database::run(
            'INSERT INTO competencies (category_id, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)',
            [$categoryId, $data['name'], $data['description'] ?? null, (int) ($data['sort_order'] ?? 0), isset($data['is_active']) ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE competencies SET category_id = ?, name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [
                (int) $data['category_id'],
                $data['name'],
                $data['description'] ?? null,
                (int) ($data['sort_order'] ?? 0),
                isset($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE competencies SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }

    /**
     * Number of completed competency ratings for a graduate.
     */
    public static function completedCount(int $graduateId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competency_responses WHERE graduate_id = ?',
            [$graduateId]
        )['c'];
    }

    public static function totalActive(): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competencies WHERE deleted_at IS NULL AND is_active = 1'
        )['c'];
    }
}
