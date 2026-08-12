<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Graduate employment profile and history.
 */
final class EmploymentProfile
{
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.id = ? AND ep.deleted_at IS NULL',
            [$id]
        );
    }

    public static function findCurrent(int $graduateId): ?array
    {
        return Database::fetch(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.graduate_id = ? AND ep.deleted_at IS NULL
             ORDER BY ep.is_current DESC, ep.created_at DESC
             LIMIT 1',
            [$graduateId]
        );
    }

    public static function allForGraduate(int $graduateId): array
    {
        return Database::fetchAll(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.graduate_id = ? AND ep.deleted_at IS NULL
             ORDER BY ep.is_current DESC, ep.created_at DESC',
            [$graduateId]
        );
    }

    public static function history(int $graduateId): array
    {
        return Database::fetchAll(
            'SELECT eh.*, es.name AS sector_name
             FROM employment_history eh
             LEFT JOIN employment_sectors es ON es.id = eh.sector_id
             WHERE eh.graduate_id = ? AND eh.deleted_at IS NULL
             ORDER BY eh.start_date DESC',
            [$graduateId]
        );
    }

    /**
     * Create or update the graduate's current employment profile.
     */
    public static function save(int $graduateId, array $data): int
    {
        $current = self::findCurrent($graduateId);

        $normalizeDate = static fn ($v): ?string => ($v === null || $v === '') ? null : (string) $v;
        $normalizeText = static fn ($v): ?string => ($v === null || trim((string) $v) === '') ? null : (string) $v;

        $fields = [
            'status'                => $data['status'] ?? 'unemployed',
            'job_title'             => $normalizeText($data['job_title'] ?? null),
            'employer'              => $normalizeText($data['employer'] ?? null),
            'sector_id'             => !empty($data['sector_id']) ? (int) $data['sector_id'] : null,
            'sector_other'          => $normalizeText($data['sector_other'] ?? null),
            'employment_type'       => $data['employment_type'] ?? null,
            'work_location'         => $normalizeText($data['work_location'] ?? null),
            'date_hired'            => $normalizeDate($data['date_hired'] ?? null),
            'first_employment_date' => $normalizeDate($data['first_employment_date'] ?? null),
            'salary_range'          => $normalizeText($data['salary_range'] ?? null),
            'job_description'       => $data['job_description'] ?? null,
            'is_related_to_program' => array_key_exists('is_related_to_program', $data)
                ? ($data['is_related_to_program'] === null ? null : (int) $data['is_related_to_program'])
                : null,
            'job_relevance_rating'  => !empty($data['job_relevance_rating']) ? (int) $data['job_relevance_rating'] : null,
        ];

        if ($current) {
            Database::run(
                'UPDATE employment_profiles SET
                    status = ?, job_title = ?, employer = ?, sector_id = ?, sector_other = ?,
                    employment_type = ?, work_location = ?, date_hired = ?, first_employment_date = ?,
                    salary_range = ?, job_description = ?, is_related_to_program = ?, job_relevance_rating = ?
                 WHERE id = ?',
                [
                    $fields['status'], $fields['job_title'], $fields['employer'], $fields['sector_id'], $fields['sector_other'],
                    $fields['employment_type'], $fields['work_location'], $fields['date_hired'], $fields['first_employment_date'],
                    $fields['salary_range'], $fields['job_description'], $fields['is_related_to_program'], $fields['job_relevance_rating'],
                    (int) $current['id'],
                ]
            );
            return (int) $current['id'];
        }

        Database::run(
            'INSERT INTO employment_profiles
                (graduate_id, status, job_title, employer, sector_id, sector_other, employment_type,
                 work_location, date_hired, first_employment_date, salary_range, job_description,
                 is_related_to_program, job_relevance_rating, is_current, is_demo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)',
            [
                $graduateId, $fields['status'], $fields['job_title'], $fields['employer'], $fields['sector_id'], $fields['sector_other'], $fields['employment_type'],
                $fields['work_location'], $fields['date_hired'], $fields['first_employment_date'], $fields['salary_range'], $fields['job_description'],
                $fields['is_related_to_program'], $fields['job_relevance_rating'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function addHistory(int $graduateId, array $data): int
    {
        if (!empty($data['is_current'])) {
            Database::run(
                'UPDATE employment_history SET is_current = 0 WHERE graduate_id = ?',
                [$graduateId]
            );
        }
        Database::run(
            'INSERT INTO employment_history
                (graduate_id, job_title, employer, sector_id, employment_type, start_date, end_date, is_current, is_related_to_program, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $graduateId,
                $data['job_title'] ?? null,
                $data['employer'] ?? null,
                $data['sector_id'] ?? null,
                $data['employment_type'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                !empty($data['is_current']) ? 1 : 0,
                array_key_exists('is_related_to_program', $data) && $data['is_related_to_program'] !== '' ? (int) $data['is_related_to_program'] : null,
                $data['notes'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function deleteHistory(int $id): void
    {
        Database::run('UPDATE employment_history SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    /**
     * Admin list: employment profiles joined with graduates, with time-to-first-employment.
     */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE ep.deleted_at IS NULL AND g.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (g.student_number LIKE ? OR g.last_name LIKE ? OR g.first_name LIKE ? OR ep.job_title LIKE ? OR ep.employer LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }
        if (!empty($filters['program_id'])) {
            $where .= ' AND g.program_id = ?';
            $params[] = (int) $filters['program_id'];
        }
        if (!empty($filters['batch_id'])) {
            $where .= ' AND g.batch_id = ?';
            $params[] = (int) $filters['batch_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND ep.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['sector_id'])) {
            $where .= ' AND ep.sector_id = ?';
            $params[] = (int) $filters['sector_id'];
        }

        $total = (int) Database::fetch(
            "SELECT COUNT(*) AS c FROM employment_profiles ep JOIN graduates g ON g.id = ep.graduate_id {$where}",
            $params
        )['c'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT ep.id, ep.graduate_id, ep.status, ep.job_title, ep.employer, ep.employment_type,
                    ep.first_employment_date, ep.job_relevance_rating, ep.is_related_to_program,
                    ep.sector_id, ep.sector_other, es.name AS sector_name,
                    g.student_number, g.first_name, g.last_name, g.graduation_year,
                    p.code AS program_code, b.year AS batch_year,
                    TIMESTAMPDIFF(MONTH, STR_TO_DATE(CONCAT(g.graduation_year, '-06-15'), '%Y-%m-%d'), ep.first_employment_date) AS time_to_first_job_months
             FROM employment_profiles ep
             JOIN graduates g ON g.id = ep.graduate_id
             JOIN programs p ON p.id = g.program_id
             JOIN batches b ON b.id = g.batch_id
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             {$where}
             ORDER BY g.last_name, g.first_name
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items'     => $items,
            'total'     => $total,
            'per_page'  => $perPage,
            'page'      => $page,
            'last_page' => $lastPage,
        ];
    }

    public static function statusLabel(string $status): string
    {
        $map = [
            'employed' => 'Employed', 'self_employed' => 'Self-Employed',
            'unemployed' => 'Unemployed', 'further_studies' => 'Further Studies',
        ];
        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function statusBadgeClass(string $status): string
    {
        $map = [
            'employed' => 'badge-green', 'self_employed' => 'badge-blue',
            'unemployed' => 'badge-red', 'further_studies' => 'badge-amber',
        ];
        return $map[$status] ?? 'badge-gray';
    }

    public static function typeLabel(?string $type): string
    {
        $map = [
            'regular' => 'Regular', 'contractual' => 'Contractual', 'temporary' => 'Temporary',
            'casual' => 'Casual', 'part_time' => 'Part-time', 'self_employed' => 'Self-employed',
        ];
        return $type ? ($map[$type] ?? ucfirst(str_replace('_', ' ', $type))) : '—';
    }
}
