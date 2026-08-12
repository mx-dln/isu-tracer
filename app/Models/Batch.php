<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Graduation batch (cohort year) of the Institute of Agricultural Technology.
 */
final class Batch
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM batches WHERE deleted_at IS NULL';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return Database::fetchAll($sql . ' ORDER BY year DESC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM batches WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function findByYear(int $year): ?array
    {
        return Database::fetch('SELECT * FROM batches WHERE year = ? AND deleted_at IS NULL', [$year]);
    }

    /**
     * Paginated list with graduate counts and optional search.
     */
    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE b.deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND (CAST(b.year AS CHAR) LIKE ? OR b.label LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch(
            "SELECT COUNT(*) AS c FROM batches b {$where}",
            $params
        )['c'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT b.*, COUNT(g.id) AS graduate_count
             FROM batches b
             LEFT JOIN graduates g ON g.batch_id = b.id AND g.deleted_at IS NULL
             {$where}
             GROUP BY b.id
             ORDER BY b.year DESC
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
            'INSERT INTO batches (year, label, is_active) VALUES (?, ?, ?)',
            [
                (int) $data['year'],
                $data['label'] ?? null,
                isset($data['is_active']) ? 1 : 0,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE batches SET year = ?, label = ?, is_active = ? WHERE id = ?',
            [
                (int) $data['year'],
                $data['label'] ?? null,
                isset($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE batches SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
    }
}
