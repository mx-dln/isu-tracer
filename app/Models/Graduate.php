<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * IAT graduate record and related demographic/employment data.
 */
final class Graduate
{
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT g.*, p.code AS program_code, p.name AS program_name,
                    b.year AS batch_year, b.label AS batch_label
             FROM graduates g
             JOIN programs p ON p.id = g.program_id
             JOIN batches b ON b.id = g.batch_id
             WHERE g.id = ? AND g.deleted_at IS NULL',
            [$id]
        );
    }

    public static function findByStudentNumber(string $studentNumber): ?array
    {
        return Database::fetch(
            'SELECT * FROM graduates WHERE student_number = ? AND deleted_at IS NULL',
            [$studentNumber]
        );
    }

    /**
     * Paginated, filterable graduate list.
     *
     * @param array $filters [search, program_id, batch_id, validated, status]
     */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE g.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (g.student_number LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ? OR CONCAT(g.first_name, " ", g.last_name) LIKE ? OR g.email LIKE ?)';
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
        if (!empty($filters['graduation_year'])) {
            $where .= ' AND g.graduation_year = ?';
            $params[] = (int) $filters['graduation_year'];
        }
        if (isset($filters['validated']) && $filters['validated'] !== '') {
            $where .= ' AND g.is_validated = ?';
            $params[] = (int) $filters['validated'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND ep.status = ?';
            $params[] = $filters['status'];
        }

        $total = (int) Database::fetch(
            "SELECT COUNT(DISTINCT g.id) AS c
             FROM graduates g
             LEFT JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
             {$where}",
            $params
        )['c'];

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT g.id, g.student_number, g.first_name, g.middle_name, g.last_name, g.suffix,
                    g.email, g.contact_number, g.sex, g.is_validated, g.is_demo,
                    g.program_id, g.batch_id, g.graduation_year,
                    p.code AS program_code, p.name AS program_name,
                    b.year AS batch_year,
                    ep.id AS employment_profile_id, ep.status AS employment_status, ep.job_title,
                    ep.proof_image_path, ep.proof_image_url
             FROM graduates g
             JOIN programs p ON p.id = g.program_id
             JOIN batches b ON b.id = g.batch_id
             LEFT JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
             {$where}
             ORDER BY g.last_name, g.first_name
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

    /**
     * Employment profile + history for the graduate detail page.
     */
    public static function employment(int $graduateId): array
    {
        $profile = Database::fetch(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.graduate_id = ? AND ep.deleted_at IS NULL
             ORDER BY ep.is_current DESC, ep.created_at DESC
             LIMIT 1',
            [$graduateId]
        );

        $history = Database::fetchAll(
            'SELECT eh.*, es.name AS sector_name
             FROM employment_history eh
             LEFT JOIN employment_sectors es ON es.id = eh.sector_id
             WHERE eh.graduate_id = ? AND eh.deleted_at IS NULL
             ORDER BY eh.start_date DESC',
            [$graduateId]
        );

        return ['profile' => $profile, 'history' => $history];
    }

    public static function surveyCount(int $graduateId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_responses WHERE graduate_id = ?',
            [$graduateId]
        )['c'];
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO graduates
                (student_number, first_name, middle_name, last_name, suffix, email, contact_number,
                 sex, birth_date, civil_status, address, municipality, province,
                 program_id, batch_id, graduation_year, is_validated, is_demo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [
                $data['student_number'],
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['suffix'] ?? null,
                $data['email'] ?? null,
                $data['contact_number'] ?? null,
                $data['sex'] ?? null,
                $data['birth_date'] ?? null,
                $data['civil_status'] ?? null,
                $data['address'] ?? null,
                $data['municipality'] ?? null,
                $data['province'] ?? null,
                $data['program_id'],
                $data['batch_id'],
                $data['graduation_year'] ?? null,
                isset($data['is_validated']) ? 1 : 0,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE graduates SET
                student_number = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                email = ?, contact_number = ?, sex = ?, birth_date = ?, civil_status = ?,
                address = ?, municipality = ?, province = ?,
                program_id = ?, batch_id = ?, graduation_year = ?, is_validated = ?
             WHERE id = ?',
            [
                $data['student_number'],
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['suffix'] ?? null,
                $data['email'] ?? null,
                $data['contact_number'] ?? null,
                $data['sex'] ?? null,
                $data['birth_date'] ?? null,
                $data['civil_status'] ?? null,
                $data['address'] ?? null,
                $data['municipality'] ?? null,
                $data['province'] ?? null,
                $data['program_id'],
                $data['batch_id'],
                $data['graduation_year'] ?? null,
                isset($data['is_validated']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE graduates SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    /**
     * Build a graduate payload from a CSV row (header keys normalized).
     */
    public static function payloadFromCsvRow(array $row, array $programMap, array $batchMap): ?array
    {
        $studentNumber = trim((string) ($row['student_number'] ?? $row['student_no'] ?? $row['id_number'] ?? ''));
        if ($studentNumber === '') {
            return null;
        }
        $first = trim((string) ($row['first_name'] ?? $row['firstname'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? $row['lastname'] ?? ''));
        if ($first === '' || $last === '') {
            return null;
        }

        $program = $row['program_code'] ?? $row['program'] ?? '';
        $batchYear = (int) ($row['batch_year'] ?? $row['graduation_year'] ?? $row['year'] ?? 0);

        $programId = is_numeric($program) ? (int) $program : ($programMap[trim((string) $program)] ?? null);
        $batchId = $batchYear ? ($batchMap[$batchYear] ?? null) : null;
        if ($programId === null || $batchId === null) {
            return null;
        }

        return [
            'student_number'  => $studentNumber,
            'first_name'      => $first,
            'middle_name'     => trim((string) ($row['middle_name'] ?? $row['middlename'] ?? '')) ?: null,
            'last_name'       => $last,
            'suffix'          => trim((string) ($row['suffix'] ?? '')) ?: null,
            'email'           => trim((string) ($row['email'] ?? '')) ?: null,
            'contact_number'  => trim((string) ($row['contact_number'] ?? $row['mobile'] ?? '')) ?: null,
            'sex'             => in_array(strtolower((string) ($row['sex'] ?? '')), ['m', 'male'], true) ? 'Male'
                               : (in_array(strtolower((string) ($row['sex'] ?? '')), ['f', 'female'], true) ? 'Female' : null),
            'program_id'      => $programId,
            'batch_id'        => $batchId,
            'graduation_year' => $batchYear ?: null,
            'is_validated'    => 0,
        ];
    }
}
