<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Survey section (group of questions).
 */
final class SurveySection
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM survey_sections WHERE id = ?', [$id]);
    }

    public static function create(int $surveyId, array $data): int
    {
        $maxOrder = (int) Database::fetch(
            'SELECT COALESCE(MAX(sort_order), 0) AS o FROM survey_sections WHERE survey_id = ?',
            [$surveyId]
        )['o'];

        Database::run(
            'INSERT INTO survey_sections (survey_id, title, description, sort_order) VALUES (?, ?, ?, ?)',
            [
                $surveyId,
                $data['title'],
                $data['description'] ?? null,
                (int) ($data['sort_order'] ?? $maxOrder + 1),
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE survey_sections SET title = ?, description = ?, sort_order = ? WHERE id = ?',
            [
                $data['title'],
                $data['description'] ?? null,
                (int) ($data['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    /**
     * Delete the section and all of its questions (with their options).
     */
    public static function destroy(int $id): void
    {
        $questionIds = array_column(
            Database::fetchAll('SELECT id FROM survey_questions WHERE section_id = ?', [$id]),
            'id'
        );
        foreach ($questionIds as $qid) {
            SurveyQuestion::destroy((int) $qid);
        }
        Database::run('DELETE FROM survey_sections WHERE id = ?', [$id]);
    }

    /**
     * Persist a new ordering for a survey's sections.
     */
    public static function reorder(int $surveyId, array $ids): void
    {
        $order = 10;
        foreach ($ids as $id) {
            Database::run(
                'UPDATE survey_sections SET sort_order = ? WHERE id = ? AND survey_id = ?',
                [$order, (int) $id, $surveyId]
            );
            $order += 10;
        }
    }

    /**
     * Persist a new ordering for questions within a section.
     */
    public static function reorderQuestions(int $surveyId, array $ids): void
    {
        $order = 10;
        foreach ($ids as $id) {
            Database::run(
                'UPDATE survey_questions SET sort_order = ? WHERE id = ? AND survey_id = ?',
                [$order, (int) $id, $surveyId]
            );
            $order += 10;
        }
    }
}
