<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\EmploymentProfile;
use App\Models\EmploymentSector;

/**
 * Maps submitted tracer-survey answers into an employment profile.
 */
final class EmploymentSyncService
{
    /**
     * Answer values keyed by question_key for a response.
     * Choice answers are stored as option values; translate them back to their
     * option text so the downstream mappings (status, sector, relation, type)
     * match the labels the analytics layer expects.
     */
    private function answersByKey(int $responseId): array
    {
        $rows = Database::fetchAll(
            'SELECT q.id AS question_id, q.question_key, a.answer_value
             FROM survey_answers a
             JOIN survey_questions q ON q.id = a.question_id
             WHERE a.response_id = ? AND q.question_key IS NOT NULL AND q.question_key <> ""',
            [$responseId]
        );
        $optionTexts = [];
        $qids = array_unique(array_map(static fn ($r) => (int) $r['question_id'], $rows));
        if ($qids) {
            $placeholders = implode(',', array_fill(0, count($qids), '?'));
            $optionRows = Database::fetchAll(
                "SELECT question_id, option_value, option_text FROM survey_options WHERE question_id IN ({$placeholders})",
                $qids
            );
            foreach ($optionRows as $o) {
                $optionTexts[(int) $o['question_id']][(string) $o['option_value']] = $o['option_text'];
            }
        }
        $out = [];
        foreach ($rows as $row) {
            $value = $row['answer_value'];
            $out[$row['question_key']] = $optionTexts[(int) $row['question_id']][(string) $value] ?? $value;
        }
        return $out;
    }

    private static function mapStatus(string $value): string
    {
        $map = [
            'Employed' => 'employed',
            'Self-employed' => 'self_employed',
            'Unemployed' => 'unemployed',
            'Pursuing further studies' => 'further_studies',
        ];
        return $map[$value] ?? 'unemployed';
    }

    private static function mapType(string $value): ?string
    {
        $map = [
            'Regular/Permanent' => 'regular',
            'Contractual' => 'contractual',
            'Temporary' => 'temporary',
            'Casual' => 'casual',
            'Part-time' => 'part_time',
            'Self-employed' => 'self_employed',
        ];
        return $map[$value] ?? null;
    }

    private static function mapRelated(string $value): ?int
    {
        return match ($value) {
            'Yes' => 1,
            'Somewhat' => 1,
            'No' => 0,
            default => null,
        };
    }

    /**
     * Build an employment profile payload from survey answers.
     */
    public function buildPayload(array $answers): array
    {
        $status = isset($answers['employment_status'])
            ? self::mapStatus((string) $answers['employment_status'])
            : 'unemployed';

        $sectorId = null;
        $sectorOther = null;
        if (!empty($answers['industry_sector'])) {
            $sector = EmploymentSector::findByName((string) $answers['industry_sector']);
            if ($sector) {
                $sectorId = (int) $sector['id'];
            } else {
                $sectorOther = (string) $answers['industry_sector'];
            }
        }

        $relevance = isset($answers['job_relevance_rating'])
            ? (int) $answers['job_relevance_rating']
            : null;

        return [
            'status'                => $status,
            'job_title'             => !empty($answers['job_title']) ? (string) $answers['job_title'] : null,
            'employer'              => !empty($answers['employer']) ? (string) $answers['employer'] : null,
            'sector_id'             => $sectorId,
            'sector_other'          => $sectorOther,
            'employment_type'       => !empty($answers['employment_type']) ? self::mapType((string) $answers['employment_type']) : null,
            'work_location'         => !empty($answers['work_location']) ? (string) $answers['work_location'] : null,
            'date_hired'            => !empty($answers['date_hired']) ? (string) $answers['date_hired'] : null,
            'first_employment_date' => !empty($answers['first_job_date']) ? (string) $answers['first_job_date'] : null,
            'salary_range'          => !empty($answers['salary_range']) ? (string) $answers['salary_range'] : null,
            'job_description'       => !empty($answers['job_description']) ? (string) $answers['job_description'] : null,
            'is_related_to_program' => isset($answers['job_relation']) ? self::mapRelated((string) $answers['job_relation']) : null,
            'job_relevance_rating'  => $relevance,
        ];
    }

    /**
     * Sync a graduate's employment profile from a submitted survey response.
     * Returns the profile id, or null when there was nothing to sync.
     */
    public function syncFromResponse(int $graduateId, int $responseId): ?int
    {
        $answers = $this->answersByKey($responseId);
        $payload = $this->buildPayload($answers);

        $hasEmploymentKeys = !empty(array_intersect(
            array_keys($answers),
            ['employment_status', 'job_title', 'employer', 'industry_sector', 'employment_type', 'work_location', 'date_hired', 'salary_range', 'job_description', 'first_job_date', 'job_relation', 'job_relevance_rating']
        ));
        if (!$hasEmploymentKeys) {
            return null;
        }

        return EmploymentProfile::save($graduateId, $payload);
    }
}
