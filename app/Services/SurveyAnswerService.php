<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SurveyQuestion;

/**
 * Validates and persists survey answers for any entry path
 * (public invitation flow or authenticated graduate flow).
 */
final class SurveyAnswerService
{
    /**
     * Validate raw request answers against the survey structure.
     * Returns a flat list of human-readable error messages.
     */
    public function validate(array $structure, array $answers): array
    {
        $errors = [];
        foreach ($structure['questions'] as $q) {
            $qid = (int) $q['id'];
            $value = $answers[$qid] ?? null;
            $valid = SurveyQuestion::decodeValidation($q['validation'] ?? null);

            $hasValue = is_array($value)
                ? count(array_filter($value, static fn ($v) => trim((string) $v) !== '')) > 0
                : ($value !== null && trim((string) $value) !== '');

            $label = $q['question_text'];

            if ($q['is_required'] && !$hasValue) {
                $errors[] = 'Question "' . $this->short($label) . '" is required.';
                continue;
            }
            if (!$hasValue) {
                continue;
            }

            $single = is_array($value)
                ? trim((string) (array_values(array_filter($value, static fn ($v) => trim((string) $v) !== ''))[0] ?? ''))
                : trim((string) $value);

            switch ($q['type']) {
                case 'number':
                    if (!is_numeric($single)) {
                        $errors[] = 'Question "' . $this->short($label) . '" must be a number.';
                    } else {
                        $n = (float) $single;
                        if (isset($valid['min']) && $n < (float) $valid['min']) {
                            $errors[] = 'Question "' . $this->short($label) . '" must be at least ' . $valid['min'] . '.';
                        }
                        if (isset($valid['max']) && $n > (float) $valid['max']) {
                            $errors[] = 'Question "' . $this->short($label) . '" must not exceed ' . $valid['max'] . '.';
                        }
                    }
                    break;
                case 'linear_scale':
                case 'rating':
                    if (!is_numeric($single)) {
                        $errors[] = 'Question "' . $this->short($label) . '" requires a selection from the scale.';
                    } else {
                        $n = (int) $single;
                        $lo = $q['type'] === 'rating' ? 1 : (int) ($valid['scale_min'] ?? 0);
                        $hi = $q['type'] === 'rating' ? (int) ($valid['stars'] ?? $q['likert_scale'] ?? 5) : (int) ($valid['scale_max'] ?? 10);
                        if ($n < $lo || $n > $hi) {
                            $errors[] = 'Question "' . $this->short($label) . '" selection is out of range.';
                        }
                    }
                    break;
                case 'date':
                    $d = \DateTime::createFromFormat('Y-m-d', $single);
                    if (!$d || $d->format('Y-m-d') !== $single) {
                        $errors[] = 'Question "' . $this->short($label) . '" must be a valid date.';
                    }
                    break;
                case 'time':
                    $t = \DateTime::createFromFormat('H:i', $single);
                    if (!$t || $t->format('H:i') !== $single) {
                        $errors[] = 'Question "' . $this->short($label) . '" must be a valid time (HH:MM).';
                    }
                    break;
                case 'text':
                    if (isset($valid['max_length']) && mb_strlen($single) > (int) $valid['max_length']) {
                        $errors[] = 'Question "' . $this->short($label) . '" must not exceed ' . $valid['max_length'] . ' characters.';
                    }
                    if (!empty($valid['email']) && !filter_var($single, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'Question "' . $this->short($label) . '" must be a valid email address.';
                    }
                    break;
                case 'multiple_choice':
                    if (!is_array($value)) {
                        $errors[] = 'Question "' . $this->short($label) . '" requires a selection.';
                        break;
                    }
                    $count = count(array_filter($value, static fn ($v) => trim((string) $v) !== ''));
                    if (isset($valid['min_selections']) && $count < (int) $valid['min_selections']) {
                        $errors[] = 'Question "' . $this->short($label) . '" requires at least ' . $valid['min_selections'] . ' selection(s).';
                    }
                    if (isset($valid['max_selections']) && $count > (int) $valid['max_selections']) {
                        $errors[] = 'Question "' . $this->short($label) . '" allows at most ' . $valid['max_selections'] . ' selection(s).';
                    }
                    break;
            }
        }
        return array_slice($errors, 0, 5);
    }

    /**
     * Persist answers for a response, matching the storage conventions the
     * analytics/forecasting layers expect (answer_value for scalars, JSON array
     * for multiple-choice).
     */
    public function store(int $responseId, array $structure, array $answers): void
    {
        foreach ($structure['questions'] as $q) {
            $qid = (int) $q['id'];
            $value = $answers[$qid] ?? null;

            if ($q['type'] === 'multiple_choice' && is_array($value)) {
                $selected = array_values(array_filter($value, static fn ($v) => trim((string) $v) !== ''));
                \App\Models\SurveyResponse::saveAnswer($responseId, $qid, json_encode($selected), $selected);
                continue;
            }
            if (is_array($value)) {
                $selected = array_values(array_filter($value, static fn ($v) => trim((string) $v) !== ''));
                \App\Models\SurveyResponse::saveAnswer($responseId, $qid, $selected[0] ?? null, null);
                continue;
            }
            \App\Models\SurveyResponse::saveAnswer(
                $responseId,
                $qid,
                ($value === null || trim((string) $value) === '') ? null : trim((string) $value),
                null
            );
        }
    }

    private function short(string $text): string
    {
        return mb_substr($text, 0, 80);
    }
}