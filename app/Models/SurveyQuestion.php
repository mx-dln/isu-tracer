<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Survey question with its answer options.
 */
final class SurveyQuestion
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM survey_questions WHERE id = ?', [$id]);
    }

    public static function create(int $surveyId, array $data): int
    {
        $key = self::resolveKey($surveyId, $data['question_key'] ?? '', $data['question_text'] ?? '');
        $sectionId = $data['section_id'] ?? null;
        if (!$sectionId) {
            $first = Database::fetch(
                'SELECT id FROM survey_sections WHERE survey_id = ? ORDER BY sort_order, id LIMIT 1',
                [$surveyId]
            );
            $sectionId = $first ? (int) $first['id'] : null;
        }
        $sortOrder = $data['sort_order'] ?? null;
        if ($sortOrder === null || $sortOrder === '') {
            $sortOrder = (int) Database::fetch(
                'SELECT COALESCE(MAX(sort_order), 0) AS sort_order FROM survey_questions WHERE survey_id = ? AND deleted_at IS NULL',
                [$surveyId]
            )['sort_order'] + 1;
        }
        Database::run(
            'INSERT INTO survey_questions
                (survey_id, section_id, question_key, question_text, help_text, type,
                 likert_scale, validation, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $surveyId,
                $sectionId,
                $key,
                $data['question_text'],
                $data['help_text'] ?? null,
                $data['type'],
                in_array($data['type'], ['likert', 'linear_scale', 'rating'], true) ? (int) ($data['likert_scale'] ?? 5) : null,
                self::encodeValidation($data),
                isset($data['is_required']) ? 1 : 0,
                (int) $sortOrder,
            ]
        );
        $id = (int) Database::lastInsertId();
        self::replaceOptions($id, $data['options'] ?? []);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $existing = self::find($id);
        $hasAnswers = self::hasAnswers($id);

        $type = $data['type'] ?? ($existing['type'] ?? 'text');
        $typeChanged = $type !== ($existing['type'] ?? 'text');
        $key = self::resolveKey((int) ($existing['survey_id'] ?? 0), $data['question_key'] ?? '', $data['question_text'] ?? '', $hasAnswers ? $existing['question_key'] ?? '' : null);

        // Changing a question's type is allowed even after responses exist;
        // the administrator decides how existing answers are interpreted.
        // When the type is unchanged, option edits on an answered question
        // remain blocked (data protection). Option validation happens BEFORE
        // writing so a rejected change never leaves the question partial.
        $lockOptions = $hasAnswers && !$typeChanged;
        if ($lockOptions && isset($data['options']) && is_array($data['options']) && self::optionsDiffer($id, $data['options'])) {
            throw new \RuntimeException('This question already has responses, so its options cannot be changed. Duplicate the question instead.');
        }

        Database::run(
            'UPDATE survey_questions SET
                section_id = ?, question_key = ?, question_text = ?, help_text = ?,
                type = ?, likert_scale = ?, validation = ?, is_required = ?, sort_order = ?
             WHERE id = ?',
            [
                $data['section_id'] ?? $existing['section_id'] ?? null,
                $key,
                $data['question_text'],
                $data['help_text'] ?? null,
                $type,
                in_array($type, ['likert', 'linear_scale', 'rating'], true) ? (int) ($data['likert_scale'] ?? ($existing['likert_scale'] ?? 5)) : null,
                self::encodeValidation($data),
                isset($data['is_required']) ? 1 : 0,
                (int) ($data['sort_order'] ?? $existing['sort_order'] ?? 0),
                $id,
            ]
        );

        if (isset($data['options']) && is_array($data['options'])) {
            self::replaceOptions($id, $data['options'], $lockOptions);
        }
    }

    /**
     * Replace the question's options from a list of strings (text, value, sort).
     *
     * When the question already has answers, replacing the option set is
     * destructive and therefore blocked.
     */
    public static function replaceOptions(int $questionId, array $options, bool $hasAnswers = false): void
    {
        $options = array_values(array_filter($options, static fn ($o) => trim((string) ($o['text'] ?? $o)) !== ''));
        if ($hasAnswers) {
            if (self::optionsDiffer($questionId, $options)) {
                throw new \RuntimeException('This question already has responses, so its options cannot be changed. Duplicate the question instead.');
            }
            return;
        }

        Database::run('DELETE FROM survey_options WHERE question_id = ?', [$questionId]);
        $order = 0;
        foreach ($options as $option) {
            if (is_string($option)) {
                $text = trim($option);
                if ($text === '') {
                    continue;
                }
                $value = $order + 1;
            } else {
                $text = trim((string) ($option['text'] ?? ''));
                $value = $option['value'] ?? null;
                $order = (int) ($option['sort_order'] ?? $order);
                if ($text === '') {
                    continue;
                }
            }
            $order++;
            Database::run(
                'INSERT INTO survey_options (question_id, option_text, option_value, sort_order) VALUES (?, ?, ?, ?)',
                [$questionId, $text, (string) $value, $order]
            );
        }
    }

    /**
     * Parse a textarea (one option per line) into option rows.
     */
    public static function parseOptionLines(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $options = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $options[] = $line;
            }
        }
        return $options;
    }

    /**
     * True when the submitted option list differs from the stored options
     * (order-insensitive). Used to guard option edits on answered questions.
     */
    private static function optionsDiffer(int $questionId, array $options): bool
    {
        $current = array_map(static fn ($o) => $o['option_text'], self::options($questionId));
        $next = array_map(static fn ($o) => trim((string) ($o['text'] ?? $o)), $options);
        sort($current);
        sort($next);
        return $current !== $next;
    }

    /**
     * Hard delete a question and its options (safe only when no answers reference it).
     */
    public static function destroy(int $id): void
    {
        $hasAnswers = self::hasAnswers($id);
        if ($hasAnswers) {
            Database::run('UPDATE survey_questions SET deleted_at = NOW() WHERE id = ?', [$id]);
            return;
        }
        Database::run('DELETE FROM survey_options WHERE question_id = ?', [$id]);
        Database::run('DELETE FROM survey_questions WHERE id = ?', [$id]);
    }

    /**
     * Clone a question (text, type, options, validation) into the same section.
     */
    public static function duplicate(int $id): int
    {
        $q = self::find($id);
        if (!$q) {
            throw new \RuntimeException('Question not found.');
        }

        $maxOrder = (int) Database::fetch(
            'SELECT COALESCE(MAX(sort_order), 0) AS o FROM survey_questions WHERE survey_id = ?',
            [(int) $q['survey_id']]
        )['o'];

        $data = [
            'section_id'  => $q['section_id'],
            'question_text' => $q['question_text'] . ' (copy)',
            'help_text'   => $q['help_text'],
            'type'        => $q['type'],
            'likert_scale' => $q['likert_scale'],
            'validation'  => $q['validation'],
            'is_required' => $q['is_required'],
            'sort_order'  => $maxOrder + 1,
            'options'     => array_map(static fn ($o) => $o['option_text'], self::options($id)),
        ];

        $newId = self::create((int) $q['survey_id'], $data);

        // Duplicated questions get a unique key (slugify original key + suffix).
        $baseKey = ($q['question_key'] ?? '') !== '' ? $q['question_key'] : '';
        $newKey = self::uniqueKey((int) $q['survey_id'], $baseKey === '' ? self::slugify($q['question_text']) : $baseKey);
        Database::run('UPDATE survey_questions SET question_key = ? WHERE id = ?', [$newKey, $newId]);

        return $newId;
    }

    /**
     * Does any submitted answer reference this question?
     */
    public static function hasAnswers(int $questionId): bool
    {
        return (bool) Database::fetch(
            'SELECT a.id FROM survey_answers a JOIN survey_responses r ON r.id = a.response_id AND r.status = "submitted" WHERE a.question_id = ? LIMIT 1',
            [$questionId]
        );
    }

    public static function options(int $questionId): array
    {
        return Database::fetchAll(
            'SELECT * FROM survey_options WHERE question_id = ? ORDER BY sort_order, id',
            [$questionId]
        );
    }

    /**
     * Build a slug-style question key from free text.
     */
    public static function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'question';
        }
        return mb_substr($slug, 0, 95);
    }

    /**
     * Make a key unique within a survey by appending _2, _3, ...
     */
    public static function uniqueKey(int $surveyId, string $key): string
    {
        if ($key === '') {
            $key = 'question';
        }
        $existing = array_map('strval', array_column(
            Database::fetchAll('SELECT question_key FROM survey_questions WHERE survey_id = ? AND question_key IS NOT NULL AND question_key <> ""', [$surveyId]),
            'question_key'
        ));
        $existing = array_flip($existing);
        $candidate = $key;
        $i = 2;
        while (isset($existing[$candidate])) {
            $candidate = $key . '_' . $i;
            $i++;
        }
        return mb_substr($candidate, 0, 100);
    }

    /**
     * Resolve the key to persist: explicit key wins, otherwise auto-generate.
     * When the question already has answers, the stored key is always preserved.
     */
    public static function resolveKey(int $surveyId, string $explicit, string $questionText, ?string $preserveKey = null): string
    {
        if ($preserveKey !== null && $preserveKey !== '') {
            return $preserveKey;
        }
        if ($explicit !== '' && $explicit !== null) {
            return self::uniqueKey($surveyId, self::slugify($explicit));
        }
        return self::uniqueKey($surveyId, self::slugify($questionText));
    }

    /**
     * Normalize per-type validation into a JSON string (or null).
     */
    public static function encodeValidation(array $data): ?string
    {
        $v = [];
        foreach (['min', 'max', 'max_length', 'min_selections', 'max_selections', 'scale_min', 'scale_max', 'stars'] as $num) {
            if (isset($data[$num]) && $data[$num] !== '' && $data[$num] !== null) {
                $v[$num] = (int) $data[$num];
            }
        }
        if (!empty($data['min_label'])) {
            $v['min_label'] = trim((string) $data['min_label']);
        }
        if (!empty($data['max_label'])) {
            $v['max_label'] = trim((string) $data['max_label']);
        }
        if (!empty($data['email'])) {
            $v['email'] = 1;
        }
        return $v === [] ? null : json_encode($v);
    }

    /**
     * Decode a question's validation JSON into an array.
     */
    public static function decodeValidation(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Text display labels for question types.
     */
    public static function typeLabel(string $type): string
    {
        $labels = [
            'text' => 'Short Text', 'long_text' => 'Paragraph', 'number' => 'Number',
            'date' => 'Date', 'single_choice' => 'Single Choice', 'multiple_choice' => 'Multiple Choice',
            'dropdown' => 'Dropdown', 'likert' => 'Likert Scale', 'yes_no' => 'Yes / No',
            'linear_scale' => 'Linear Scale', 'rating' => 'Rating', 'time' => 'Time',
            'image_upload' => 'Image Upload',
        ];
        return $labels[$type] ?? ucfirst($type);
    }
}
