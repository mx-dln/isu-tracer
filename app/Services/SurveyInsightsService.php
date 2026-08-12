<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Survey;
use App\Models\SurveyInvitation;

/**
 * Per-survey insights built from survey answers keyed by question_key:
 * per-question breakdowns/averages, and a completion forecast.
 */
final class SurveyInsightsService
{
    /**
     * Per-question answer analytics for a survey.
     *
     * @return array<int, array>
     */
    public function questionBreakdowns(int $surveyId): array
    {
        $structure = Survey::structure($surveyId);
        $questions = $structure['questions'] ?? [];
        if (!$questions) {
            return [];
        }

        $qids = array_map('intval', array_column($questions, 'id'));
        $placeholders = implode(',', array_fill(0, count($qids), '?'));
        $rows = Database::fetchAll(
            "SELECT a.question_id, a.answer_value, a.response_id
             FROM survey_answers a
             WHERE a.question_id IN ({$placeholders})",
            $qids
        );

        $agg = [];
        foreach ($questions as $q) {
            $agg[(int) $q['id']] = [
                'id'           => (int) $q['id'],
                'key'          => $q['question_key'] ?? '',
                'text'         => $q['question_text'],
                'type'         => $q['type'],
                'options'      => $q['options'] ?? [],
                'seen'         => [],
                'value_counts' => [],
                'numeric_sum'  => 0.0,
                'numeric_min'  => null,
                'numeric_max'  => null,
            ];
        }

        $numericTypes = ['number', 'rating', 'likert', 'linear_scale'];

        foreach ($rows as $row) {
            $qid = (int) $row['question_id'];
            if (!isset($agg[$qid])) {
                continue;
            }
            $a = &$agg[$qid];
            $a['seen'][(int) $row['response_id']] = true;

            $value = $row['answer_value'];
            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            if ($a['type'] === 'multiple_choice') {
                $decoded = json_decode((string) $value, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $v) {
                        $a['value_counts'][(string) $v] = ($a['value_counts'][(string) $v] ?? 0) + 1;
                    }
                }
                continue;
            }

            if (in_array($a['type'], $numericTypes, true)) {
                $n = (float) $value;
                $a['numeric_sum'] += $n;
                if ($a['numeric_min'] === null || $n < $a['numeric_min']) {
                    $a['numeric_min'] = $n;
                }
                if ($a['numeric_max'] === null || $n > $a['numeric_max']) {
                    $a['numeric_max'] = $n;
                }
            }

            $a['value_counts'][(string) $value] = ($a['value_counts'][(string) $value] ?? 0) + 1;
        }

        foreach ($agg as &$a) {
            $responseCount = count($a['seen']);
            $a['response_count'] = $responseCount;
            $a['average'] = null;
            $a['breakdown'] = [];

            if ($responseCount === 0) {
                unset($a['seen']);
                continue;
            }

            $isChoice = in_array($a['type'], ['single_choice', 'multiple_choice', 'dropdown'], true);

            if (in_array($a['type'], $numericTypes, true)) {
                $a['average'] = round($a['numeric_sum'] / $responseCount, 2);
                if (!in_array($a['type'], ['number'], true) || count($a['value_counts']) <= 12) {
                    ksort($a['value_counts'], SORT_NUMERIC);
                    foreach ($a['value_counts'] as $v => $count) {
                        $a['breakdown'][] = [
                            'label'      => $v,
                            'value'      => $v,
                            'count'      => $count,
                            'percentage' => round($count / $responseCount * 100, 1),
                        ];
                    }
                }
            } elseif ($isChoice) {
                $labelMap = [];
                foreach ($a['options'] as $opt) {
                    $labelMap[(string) $opt['option_value']] = $opt['option_text'];
                }
                // Ordered by option definition first.
                foreach ($a['options'] as $opt) {
                    $v = (string) $opt['option_value'];
                    $count = $a['value_counts'][$v] ?? 0;
                    if ($count > 0) {
                        $a['breakdown'][] = [
                            'label'      => $opt['option_text'],
                            'value'      => $v,
                            'count'      => $count,
                            'percentage' => round($count / $responseCount * 100, 1),
                        ];
                    }
                }
                // Stray values (answers not matching a current option).
                foreach ($a['value_counts'] as $v => $count) {
                    if (!isset($labelMap[$v])) {
                        $a['breakdown'][] = [
                            'label'      => $v,
                            'value'      => $v,
                            'count'      => $count,
                            'percentage' => round($count / $responseCount * 100, 1),
                        ];
                    }
                }
            }

            unset($a['seen']);
        }
        unset($a);

        return array_values($agg);
    }

    /**
     * Completion forecast: invitations issued, responses received, current
     * completion, and a projected final completion (time-based when the survey
     * has a schedule, otherwise based on the current response propensity).
     */
    public function completionForecast(int $surveyId): array
    {
        $survey = Survey::find($surveyId);
        $inv = SurveyInvitation::stats($surveyId);

        $issued = (int) ($inv['total'] ?? 0);
        $responded = (int) ($inv['completed'] ?? 0);
        $eligible = (int) ($inv['pending'] ?? 0) + (int) ($inv['opened'] ?? 0);
        $currentRate = $issued > 0 ? round($responded / $issued * 100, 1) : 0.0;

        $now = time();
        $startTs = !empty($survey['start_date']) ? strtotime((string) $survey['start_date']) : false;
        $endTs = !empty($survey['end_date']) ? strtotime((string) $survey['end_date'] . ' 23:59:59') : false;

        $projectedResponses = $responded;
        $basis = 'propensity';

        if ($startTs && $endTs && $endTs > $startTs) {
            $spanDays = max(1, ($endTs - $startTs) / 86400);
            $elapsedDays = max(1, ($now - $startTs) / 86400);
            $ratePerDay = $responded / $elapsedDays;
            $projectedResponses = (int) round($ratePerDay * $spanDays);
            $projectedResponses = min($projectedResponses, max($issued, $responded));
            $basis = 'schedule';
        } else {
            $propensity = $issued > 0 ? $responded / $issued : 0.0;
            $projectedResponses = $responded + (int) round($eligible * $propensity);
        }

        $projectedRate = $issued > 0 ? min(100.0, round($projectedResponses / $issued * 100, 1)) : 0.0;

        return [
            'issued'              => $issued,
            'responded'           => $responded,
            'eligible'            => $eligible,
            'current_rate'        => $currentRate,
            'projected_responses' => $projectedResponses,
            'projected_rate'      => $projectedRate,
            'basis'               => $basis,
        ];
    }
}
