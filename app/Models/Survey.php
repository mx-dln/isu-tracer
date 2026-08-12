<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Tracer study survey and its section/question structure.
 */
final class Survey
{
    public const QUESTION_TYPES = [
        'text', 'long_text', 'number', 'date', 'single_choice',
        'multiple_choice', 'dropdown', 'likert', 'yes_no',
        'linear_scale', 'rating', 'time',
    ];

    public const CHOICE_TYPES = ['single_choice', 'multiple_choice', 'dropdown', 'likert'];

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM surveys WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function findWithTrashed(int $id): ?array
    {
        return Database::fetch('SELECT * FROM surveys WHERE id = ?', [$id]);
    }

    /**
     * Paginated survey list with response counts.
     */
    public static function paginate(?string $search = null, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE s.deleted_at IS NULL';
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND (s.title LIKE ? OR s.description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM surveys s {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT s.*,
                    COUNT(DISTINCT q.id) AS question_count,
                    COUNT(DISTINCT r.id) AS response_count,
                    COUNT(DISTINCT i.id) AS invitation_count,
                    COUNT(DISTINCT CASE WHEN i.status = 'completed' THEN i.id END) AS invitation_completed
             FROM surveys s
             LEFT JOIN survey_questions q ON q.survey_id = s.id AND q.deleted_at IS NULL
             LEFT JOIN survey_responses r ON r.survey_id = s.id
             LEFT JOIN survey_invitations i ON i.survey_id = s.id
             {$where}
             GROUP BY s.id
             ORDER BY s.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        foreach ($items as &$item) {
            $totalInvites = (int) $item['invitation_count'];
            $item['invitation_rate'] = $totalInvites > 0
                ? round(((int) ($item['invitation_completed'] ?? 0) / $totalInvites) * 100, 1)
                : 0.0;
        }
        unset($item);

        return [
            'items'     => $items,
            'total'     => $total,
            'per_page'  => $perPage,
            'page'      => $page,
            'last_page' => $lastPage,
        ];
    }

    public static function active(): ?array
    {
        return Database::fetch("SELECT * FROM surveys WHERE status = 'active' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    }

    /**
     * Full structure: sections (with questions and options) ordered.
     */
    public static function structure(int $surveyId): array
    {
        $sections = Database::fetchAll(
            'SELECT * FROM survey_sections WHERE survey_id = ? ORDER BY sort_order, id',
            [$surveyId]
        );

        $questions = Database::fetchAll(
            'SELECT * FROM survey_questions WHERE survey_id = ? AND deleted_at IS NULL ORDER BY sort_order, id',
            [$surveyId]
        );
        $optionsByQuestion = [];
        if ($questions) {
            $ids = array_column($questions, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $options = Database::fetchAll(
                "SELECT * FROM survey_options WHERE question_id IN ({$placeholders}) ORDER BY sort_order, id",
                $ids
            );
            foreach ($options as $option) {
                $optionsByQuestion[$option['question_id']][] = $option;
            }
        }

        $questionsBySection = [];
        $unsectioned = [];
        $withOptions = [];
        foreach ($questions as $question) {
            $question['options'] = $optionsByQuestion[$question['id']] ?? [];
            $withOptions[] = $question;
            if ($question['section_id'] === null || $question['section_id'] === '') {
                $unsectioned[] = $question;
                continue;
            }
            $questionsBySection[(int) $question['section_id']][] = $question;
        }

        foreach ($sections as &$section) {
            $section['questions'] = $questionsBySection[(int) $section['id']] ?? [];
        }
        unset($section);

        // Keep questions that have no section visible under a "General" heading.
        if ($unsectioned) {
            array_unshift($sections, [
                'id'          => 0,
                'title'       => 'General',
                'description' => null,
                'questions'   => $unsectioned,
            ]);
        }

        return [
            'survey'    => self::find($surveyId),
            'sections'  => $sections,
            'questions' => $withOptions,
        ];
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO surveys (title, description, confirmation_message, status, start_date, end_date, tracer_year, require_invitation, invitation_expiry_days, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['description'] ?? null,
                $data['confirmation_message'] ?? null,
                $data['status'] ?? 'draft',
                self::blankToNull($data['start_date'] ?? null),
                self::blankToNull($data['end_date'] ?? null),
                self::blankToNull($data['tracer_year'] ?? null),
                isset($data['require_invitation']) ? 1 : 0,
                (int) ($data['invitation_expiry_days'] ?? 30),
                $data['created_by'] ?? auth_id(),
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE surveys SET title = ?, description = ?, confirmation_message = ?, status = ?, start_date = ?, end_date = ?, tracer_year = ?, require_invitation = ?, invitation_expiry_days = ? WHERE id = ?',
            [
                $data['title'],
                $data['description'] ?? null,
                $data['confirmation_message'] ?? null,
                $data['status'] ?? 'draft',
                self::blankToNull($data['start_date'] ?? null),
                self::blankToNull($data['end_date'] ?? null),
                self::blankToNull($data['tracer_year'] ?? null),
                isset($data['require_invitation']) ? 1 : 0,
                (int) ($data['invitation_expiry_days'] ?? 30),
                $id,
            ]
        );
    }

    /**
     * Blank date/number strings from HTML forms become NULL for the database.
     */
    private static function blankToNull($value): ?string
    {
        return ($value === null || trim((string) $value) === '') ? null : (string) $value;
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::run('UPDATE surveys SET status = ? WHERE id = ?', [$status, $id]);
    }

    /**
     * Activate a survey. Surveys are year-specific (one per tracer year), so
     * activating one does not demote other active surveys.
     */
    public static function activate(int $id): void
    {
        Database::run("UPDATE surveys SET status = 'active' WHERE id = ?", [$id]);
    }

    public static function softDelete(int $id): void
    {
        Database::run('UPDATE surveys SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    public static function responseCount(int $surveyId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_responses WHERE survey_id = ? AND status = "submitted"',
            [$surveyId]
        )['c'];
    }

    public static function sectionCount(int $surveyId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_sections WHERE survey_id = ?',
            [$surveyId]
        )['c'];
    }

    public static function questionCount(int $surveyId): int
    {
        return (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM survey_questions WHERE survey_id = ? AND deleted_at IS NULL',
            [$surveyId]
        )['c'];
    }

    /**
     * Does the graduate already have a submitted response for this survey?
     */
    public static function hasResponse(int $surveyId, int $graduateId): bool
    {
        return (bool) Database::fetch(
            'SELECT id FROM survey_responses WHERE survey_id = ? AND graduate_id = ?',
            [$surveyId, $graduateId]
        );
    }

    /**
     * Paginated submitted responses for a survey (with graduate details).
     */
    public static function responses(int $surveyId, int $page = 1, int $perPage = 15): array
    {
        $where = 'WHERE r.survey_id = ? AND r.status = "submitted"';
        $params = [$surveyId];

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM survey_responses r {$where}", $params)['c'];
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = Database::fetchAll(
            "SELECT r.*, g.student_number, g.first_name, g.last_name, g.program_id,
                    p.code AS program_code, b.year AS batch_year
             FROM survey_responses r
             JOIN graduates g ON g.id = r.graduate_id
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             {$where}
             ORDER BY r.submitted_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage];
    }
}
