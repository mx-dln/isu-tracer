<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Employment industry sector reference.
 */
final class EmploymentSector
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM employment_sectors WHERE deleted_at IS NULL';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return Database::fetchAll($sql . ' ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM employment_sectors WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function findByName(string $name): ?array
    {
        return Database::fetch('SELECT * FROM employment_sectors WHERE name = ? AND deleted_at IS NULL', [$name]);
    }

    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE s.deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND s.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM employment_sectors s {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT s.*, COUNT(ep.id) AS usage_count
             FROM employment_sectors s
             LEFT JOIN employment_profiles ep ON ep.sector_id = s.id AND ep.deleted_at IS NULL
             {$where}
             GROUP BY s.id
             ORDER BY s.name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }

    public static function create(string $name): int
    {
        Database::run('INSERT INTO employment_sectors (name, is_active) VALUES (?, 1)', [$name]);
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, string $name, bool $active): void
    {
        Database::run(
            'UPDATE employment_sectors SET name = ?, is_active = ? WHERE id = ?',
            [$name, $active ? 1 : 0, $id]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE employment_sectors SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }
}
