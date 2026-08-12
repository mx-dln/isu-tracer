<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Competency category reference (admin-managed).
 */
final class CompetencyCategory
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM competency_categories WHERE deleted_at IS NULL';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return Database::fetchAll($sql . ' ORDER BY sort_order, name');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM competency_categories WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE cc.deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND cc.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM competency_categories cc {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT cc.*, COUNT(c.id) AS competency_count
             FROM competency_categories cc
             LEFT JOIN competencies c ON c.category_id = cc.id AND c.deleted_at IS NULL
             {$where}
             GROUP BY cc.id
             ORDER BY cc.sort_order, cc.name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO competency_categories (name, description, sort_order, is_active) VALUES (?, ?, ?, ?)',
            [$data['name'], $data['description'] ?? null, (int) ($data['sort_order'] ?? 0), isset($data['is_active']) ? 1 : 0]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE competency_categories SET name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$data['name'], $data['description'] ?? null, (int) ($data['sort_order'] ?? 0), isset($data['is_active']) ? 1 : 0, $id]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE competency_categories SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }
}
