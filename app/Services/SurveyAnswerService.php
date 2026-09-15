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
    private const MAX_IMAGE_BYTES = 5242880;
    private const IMAGE_MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

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
            $file = $this->uploadedFile($qid);

            $hasValue = is_array($value)
                ? count(array_filter($value, static fn ($v) => trim((string) $v) !== '')) > 0
                : ($value !== null && trim((string) $value) !== '');
            if ($q['type'] === 'image_upload') {
                $hasValue = $file !== null;
            }

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
                case 'image_upload':
                    if ($file !== null) {
                        $imageError = $this->validateImageFile($file);
                        if ($imageError !== null) {
                            $errors[] = 'Question "' . $this->short($label) . '" ' . $imageError;
                        }
                    }
                    break;
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

            if ($q['type'] === 'image_upload') {
                $file = $this->uploadedFile($qid);
                $stored = $file ? $this->storeImageFile($file, $responseId, $qid) : null;
                \App\Models\SurveyResponse::saveAnswer($responseId, $qid, $stored, null);
                continue;
            }

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

    private function uploadedFile(int $questionId): ?array
    {
        $key = 'answer_file_' . $questionId;
        $file = $_FILES[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    private function validateImageFile(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return 'could not be uploaded. Please choose another image.';
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_IMAGE_BYTES) {
            return 'must not exceed 5 MB.';
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'could not be verified. Please choose another image.';
        }
        $mime = mime_content_type($tmp) ?: '';
        if (!isset(self::IMAGE_MIME_EXTENSIONS[$mime])) {
            return 'must be a JPG, PNG, GIF, or WebP image.';
        }
        return null;
    }

    private function storeImageFile(array $file, int $responseId, int $questionId): string
    {
        $error = $this->validateImageFile($file);
        if ($error !== null) {
            throw new \RuntimeException($error);
        }

        $tmp = (string) $file['tmp_name'];
        $mime = mime_content_type($tmp) ?: '';
        $ext = self::IMAGE_MIME_EXTENSIONS[$mime] ?? 'jpg';
        $relativeDir = 'uploads/survey-proofs/' . date('Y/m');
        $targetDir = storage_path($relativeDir);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $name = sprintf(
            'response-%d-question-%d-%s.%s',
            $responseId,
            $questionId,
            bin2hex(random_bytes(8)),
            $ext
        );
        $target = $targetDir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            throw new \RuntimeException('The uploaded image could not be saved.');
        }

        return $relativeDir . '/' . $name;
    }
}
