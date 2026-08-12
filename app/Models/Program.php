<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Program (degree/curriculum offering) of the Institute of Agricultural Technology.
 */
final class Program
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM programs WHERE deleted_at IS NULL';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return Database::fetchAll($sql . ' ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM programs WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        return Database::fetch('SELECT * FROM programs WHERE code = ? AND deleted_at IS NULL', [$code]);
    }

    /**
     * Paginated list with graduate counts and optional search.
     */
    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE p.deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND (p.name LIKE ? OR p.code LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch(
            "SELECT COUNT(*) AS c FROM programs p {$where}",
            $params
        )['c'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT p.*, COUNT(g.id) AS graduate_count
             FROM programs p
             LEFT JOIN graduates g ON g.program_id = p.id AND g.deleted_at IS NULL
             {$where}
             GROUP BY p.id
             ORDER BY p.name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items'      => $items,
            'total'      => $total,
            'per_page'   => $perPage,
            'page'       => $page,
            'last_page'  => $lastPage,
        ];
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO programs (code, name, description, is_active)
             VALUES (?, ?, ?, ?)',
            [
                $data['code'],
                $data['name'],
                $data['description'] ?? null,
                isset($data['is_active']) ? 1 : 0,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE programs SET code = ?, name = ?, description = ?, is_active = ? WHERE id = ?',
            [
                $data['code'],
                $data['name'],
                $data['description'] ?? null,
                isset($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE programs SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }
}
