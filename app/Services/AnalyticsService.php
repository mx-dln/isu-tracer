<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Central analytics service: KPIs, aggregations, and time-to-employment.
 */
final class AnalyticsService
{
    /**
     * Build a WHERE fragment + params for shared filters.
     *
     * Filters referencing employment profiles (status, sector_id) require the
     * query to join `employment_profiles ep`. The third return value signals
     * that requirement so callers can include the join.
     *
     * @return array{0: string, 1: array, 2: bool} [whereSql, params, needsEp]
     */
    private function filterSql(array $filters): array
    {
        $where = [];
        $params = [];
        $needsEp = false;

        if (!empty($filters['batch_id'])) {
            $where[] = 'g.batch_id = ?';
            $params[] = $filters['batch_id'];
        } elseif (!empty($filters['graduation_year'])) {
            $where[] = 'g.graduation_year = ?';
            $params[] = (int) $filters['graduation_year'];
        }
        if (!empty($filters['program_id'])) {
            $where[] = 'g.program_id = ?';
            $params[] = $filters['program_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ep.status = ?';
            $params[] = $filters['status'];
            $needsEp = true;
        }
        if (!empty($filters['sector_id'])) {
            $where[] = 'ep.sector_id = ?';
            $params[] = $filters['sector_id'];
            $needsEp = true;
        }

        $sql = $where ? ' AND ' . implode(' AND ', $where) : '';
        return [$sql, $params, $needsEp];
    }

    /**
     * Compute dashboard KPIs. Responders are graduates with a submitted
     * employment profile (respondent = participated in the tracer).
     */
    public function kpis(array $filters = []): array
    {
        [$filterSql, $params, $needsEp] = $this->filterSql($filters);

        // Graduates (optionally restricted to those with an employment profile
        // when a status/sector filter is applied, so the filter stays valid).
        $totalGraduatesSql = 'SELECT COUNT(DISTINCT g.id) AS c FROM graduates g';
        if ($needsEp) {
            $totalGraduatesSql .= ' JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL AND ep.is_current = 1';
        }
        $totalGraduatesSql .= ' WHERE g.deleted_at IS NULL' . $filterSql;
        $totalGraduates = (int) Database::fetch($totalGraduatesSql, $params)['c'];

        // Respondents: graduates with a current employment profile.
        $respondentWhere = ' WHERE g.deleted_at IS NULL AND ep.id IS NOT NULL AND ep.is_current = 1' . $filterSql;
        $totalRespondents = (int) Database::fetch(
            'SELECT COUNT(DISTINCT g.id) AS c
             FROM graduates g
             LEFT JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL' . $respondentWhere,
            $params
        )['c'];

        $statusCounts = $this->statusCounts($filters);
        $employedCount = $statusCounts['employed'] + $statusCounts['self_employed'];

        $responseRate = $totalGraduates > 0 ? round($totalRespondents / $totalGraduates * 100, 1) : 0;
        $employmentRate = $totalRespondents > 0 ? round($employedCount / $totalRespondents * 100, 1) : 0;

        // Job relevance rate: % of employed respondents with rating >= 3.
        $jobRelevanceRate = 0;
        if ($employedCount > 0) {
            $rel = Database::fetch(
                'SELECT COUNT(DISTINCT ep.graduate_id) AS c
                 FROM employment_profiles ep
                 JOIN graduates g ON g.id = ep.graduate_id
                 WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
                   AND ep.status IN ("employed","self_employed") AND ep.job_relevance_rating >= 3'
                    . $filterSql,
                $params
            )['c'];
            $jobRelevanceRate = round($rel / $employedCount * 100, 1);
        }

        return [
            'total_graduates'     => $totalGraduates,
            'total_respondents'   => $totalRespondents,
            'response_rate'       => $responseRate,
            'employment_rate'     => $employmentRate,
            'employed'            => $statusCounts['employed'],
            'self_employed'       => $statusCounts['self_employed'],
            'unemployed'          => $statusCounts['unemployed'],
            'further_studies'     => $statusCounts['further_studies'],
            'self_employment_rate' => $totalRespondents > 0 ? round($statusCounts['self_employed'] / $totalRespondents * 100, 1) : 0,
            'unemployment_rate'   => $totalRespondents > 0 ? round($statusCounts['unemployed'] / $totalRespondents * 100, 1) : 0,
            'further_studies_rate' => $totalRespondents > 0 ? round($statusCounts['further_studies'] / $totalRespondents * 100, 1) : 0,
            'job_relevance_rate'  => $jobRelevanceRate,
            'avg_time_to_first_employment' => $this->averageTimeToFirstEmployment($filters),
            'avg_competency_score'  => $this->averageCompetencyScore($filters),
            'curriculum_relevance_score' => $this->averageCurriculumScore($filters),
        ];
    }

    public function statusCounts(array $filters = []): array
    {
        [$filterSql, $params] = $this->filterSql($filters);
        $rows = Database::fetchAll(
            'SELECT ep.status, COUNT(DISTINCT g.id) AS c
             FROM graduates g
             JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL AND ep.is_current = 1
             WHERE g.deleted_at IS NULL' . $filterSql . '
             GROUP BY ep.status',
            $params
        );
        $counts = ['employed' => 0, 'self_employed' => 0, 'unemployed' => 0, 'further_studies' => 0];
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['c'];
            }
        }
        return $counts;
    }

    /**
     * Employment status distribution percentages.
     */
    public function statusDistribution(array $filters = []): array
    {
        $counts = $this->statusCounts($filters);
        $total = array_sum($counts);
        $result = [];
        foreach ($counts as $status => $count) {
            $result[$status] = [
                'count'      => $count,
                'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }
        return $result;
    }

    /**
     * Average time (months) from graduation to first employment.
     */
    public function averageTimeToFirstEmployment(array $filters = []): ?float
    {
        [$filterSql, $params] = $this->filterSql($filters);
        $row = Database::fetch(
            'SELECT AVG(TIMESTAMPDIFF(MONTH,
                       STR_TO_DATE(CONCAT(g.graduation_year, "-06-15"), "%Y-%m-%d"),
                       ep.first_employment_date)) AS avg_months
             FROM graduates g
             JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL AND ep.is_current = 1
             WHERE g.deleted_at IS NULL
               AND ep.first_employment_date IS NOT NULL
               AND ep.first_employment_date <> ""
               AND ep.status IN ("employed","self_employed")' . $filterSql,
            $params
        );

        $avg = $row['avg_months'] ?? null;
        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Time-to-first-employment category distribution.
     */
    public function timeToEmploymentDistribution(array $filters = []): array
    {
        [$filterSql, $params] = $this->filterSql($filters);
        $rows = Database::fetchAll(
            'SELECT g.graduation_year, ep.first_employment_date,
                    TIMESTAMPDIFF(MONTH, STR_TO_DATE(CONCAT(g.graduation_year, "-06-15"), "%Y-%m-%d"), ep.first_employment_date) AS months
             FROM graduates g
             JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL AND ep.is_current = 1
             WHERE g.deleted_at IS NULL
               AND ep.first_employment_date IS NOT NULL
               AND ep.first_employment_date <> ""
               AND ep.status IN ("employed","self_employed")' . $filterSql,
            $params
        );

        $categories = [
            'Less than 1 month' => 0,
            '1-3 months' => 0,
            '4-6 months' => 0,
            '7-12 months' => 0,
            'More than 1 year' => 0,
        ];
        foreach ($rows as $row) {
            $m = (int) $row['months'];
            if ($m < 1) {
                $categories['Less than 1 month']++;
            } elseif ($m <= 3) {
                $categories['1-3 months']++;
            } elseif ($m <= 6) {
                $categories['4-6 months']++;
            } elseif ($m <= 12) {
                $categories['7-12 months']++;
            } else {
                $categories['More than 1 year']++;
            }
        }
        $total = array_sum($categories);
        $out = [];
        foreach ($categories as $label => $count) {
            $out[$label] = ['count' => $count, 'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0];
        }
        return $out;
    }

    /**
     * Employment sector distribution among employed respondents.
     */
    public function sectorDistribution(array $filters = []): array
    {
        [$filterSql, $params] = $this->filterSql($filters);
        $rows = Database::fetchAll(
            'SELECT es.name, COUNT(DISTINCT ep.graduate_id) AS c
             FROM employment_profiles ep
             JOIN employment_sectors es ON es.id = ep.sector_id
             JOIN graduates g ON g.id = ep.graduate_id
             WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
               AND ep.status IN ("employed","self_employed")' . $filterSql . '
             GROUP BY es.name
             ORDER BY c DESC',
            $params
        );
        $total = array_sum(array_column($rows, 'c'));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['name']] = [
                'count'      => (int) $row['c'],
                'percentage' => $total > 0 ? round((int) $row['c'] / $total * 100, 1) : 0,
            ];
        }
        return $out;
    }

    /**
     * Employment rate by graduation year.
     */
    public function employmentRateByYear(): array
    {
        $rows = Database::fetchAll(
            'SELECT g.graduation_year AS year,
                    COUNT(DISTINCT g.id) AS respondents,
                    SUM(CASE WHEN ep.status IN ("employed","self_employed") THEN 1 ELSE 0 END) AS employed
             FROM graduates g
             JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.deleted_at IS NULL AND ep.is_current = 1
             WHERE g.deleted_at IS NULL AND g.graduation_year IS NOT NULL
             GROUP BY g.graduation_year
             ORDER BY g.graduation_year'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['year']] = [
                'respondents' => (int) $row['respondents'],
                'employed'    => (int) $row['employed'],
                'rate'        => $row['respondents'] > 0 ? round($row['employed'] / $row['respondents'] * 100, 1) : 0,
            ];
        }
        return $out;
    }

    /**
     * Job relevance Likert analysis (weighted mean).
     */
    public function jobRelevanceAnalysis(array $filters = []): array
    {
        [$filterSql, $params] = $this->filterSql($filters);
        $rows = Database::fetchAll(
            'SELECT ep.job_relevance_rating AS rating, COUNT(DISTINCT ep.graduate_id) AS c
             FROM employment_profiles ep
             JOIN graduates g ON g.id = ep.graduate_id
             WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
               AND ep.status IN ("employed","self_employed")
               AND ep.job_relevance_rating IS NOT NULL'
                . $filterSql . '
             GROUP BY ep.job_relevance_rating
             ORDER BY ep.job_relevance_rating DESC',
            $params
        );

        $total = 0;
        $sumFx = 0;
        $frequencies = [];
        foreach ($rows as $row) {
            $rating = (int) $row['rating'];
            $count = (int) $row['c'];
            $frequencies[$rating] = $count;
            $total += $count;
            $sumFx += $rating * $count;
        }

        $weightedMean = $total > 0 ? round($sumFx / $total, 2) : 0;
        $interpretation = $this->interpret($weightedMean, 'job_relevance');

        $withPct = [];
        foreach ($frequencies as $rating => $count) {
            $withPct[$rating] = [
                'count'      => $count,
                'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }
        krsort($withPct);

        return [
            'total'           => $total,
            'weighted_mean'   => $weightedMean,
            'interpretation'  => $interpretation,
            'frequencies'     => $withPct,
        ];
    }

    /**
     * Competency performance grouped by category (weighted mean per category).
     */
    public function competencyAnalysis(array $filters = []): array
    {
        $rows = Database::fetchAll(
            'SELECT cc.id AS category_id, cc.name AS category, c.id AS competency_id, c.name AS competency,
                    AVG(cr.rating) AS avg_rating, COUNT(cr.id) AS responses
             FROM competencies c
             JOIN competency_categories cc ON cc.id = c.category_id
             LEFT JOIN competency_responses cr ON cr.competency_id = c.id
             WHERE c.deleted_at IS NULL AND c.is_active = 1
             GROUP BY c.id
             ORDER BY cc.sort_order, c.sort_order'
        );

        $categories = [];
        foreach ($rows as $row) {
            $cat = $row['category'];
            if (!isset($categories[$cat])) {
                $categories[$cat] = ['category' => $cat, 'competencies' => [], 'score' => null];
            }
            $categories[$cat]['competencies'][] = [
                'name'      => $row['competency'],
                'score'     => $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null,
                'responses' => (int) $row['responses'],
            ];
        }
        foreach ($categories as &$cat) {
            $scores = array_column(array_filter($cat['competencies'], fn ($c) => $c['score'] !== null), 'score');
            $cat['score'] = count($scores) ? round(array_sum($scores) / count($scores), 2) : null;
        }
        unset($cat);

        $catScores = [];
        foreach ($categories as $cat) {
            if ($cat['score'] !== null) {
                $catScores[$cat['category']] = $cat['score'];
            }
        }
        arsort($catScores);

        return [
            'categories'       => array_values($categories),
            'overall'          => count($catScores) ? round(array_sum($catScores) / count($catScores), 2) : null,
            'ranking'          => $catScores,
            'strongest'        => $catScores ? [array_key_first($catScores) => reset($catScores)] : [],
            'weakest'          => $catScores ? [array_key_last($catScores) => end($catScores)] : [],
        ];
    }

    /**
     * Curriculum feedback weighted means.
     */
    public function curriculumAnalysis(array $filters = []): array
    {
        $rows = Database::fetchAll(
            'SELECT cq.id, cq.question_text, AVG(cf.rating) AS avg_rating, COUNT(cf.id) AS responses
             FROM curriculum_questions cq
             LEFT JOIN curriculum_feedback cf ON cf.question_id = cq.id
             WHERE cq.deleted_at IS NULL AND cq.is_active = 1
             GROUP BY cq.id
             ORDER BY cq.sort_order'
        );
        $total = 0;
        $sumFx = 0;
        $out = [];
        foreach ($rows as $row) {
            $avg = $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null;
            if ($avg !== null) {
                $sumFx += (float) $avg * (int) $row['responses'];
                $total += (int) $row['responses'];
            }
            $out[] = [
                'question'      => $row['question_text'],
                'score'         => $avg,
                'responses'     => (int) $row['responses'],
                'interpretation' => $avg !== null ? $this->interpret($avg, 'curriculum') : null,
            ];
        }
        $overall = $total > 0 ? round($sumFx / $total, 2) : null;

        return [
            'items'    => $out,
            'overall'  => $overall,
            'interpretation' => $overall !== null ? $this->interpret($overall, 'curriculum') : null,
        ];
    }

    public function averageCompetencyScore(array $filters = []): ?float
    {
        $analysis = $this->competencyAnalysis($filters);
        return $analysis['overall'];
    }

    public function averageCurriculumScore(array $filters = []): ?float
    {
        $analysis = $this->curriculumAnalysis($filters);
        return $analysis['overall'];
    }

    /**
     * Interpret a weighted mean using the configured likert_scales table.
     *
     * Uses the standard 5-point ranges stored in likert_scales:
     *   5 = Highly Aligned (4.20-5.00), 4 = Aligned (3.40-4.19),
     *   3 = Moderately Aligned (2.60-3.39), 2 = Weakly Aligned (1.80-2.59),
     *   1 = Not Aligned (1.00-1.79).
     * The mean is clamped to the valid 1-5 range before lookup.
     */
    public function interpret(float $mean, string $scaleType = 'job_relevance'): string
    {
        $mean = max(1.0, min(5.0, $mean));
        $row = Database::fetch(
            'SELECT interpretation FROM likert_scales
             WHERE scale_type = ? AND ? >= lower_bound AND ? <= upper_bound
             ORDER BY score DESC LIMIT 1',
            [$scaleType, $mean, $mean]
        );
        if ($row) {
            return $row['interpretation'];
        }
        $fallback = Database::fetch(
            'SELECT interpretation FROM likert_scales WHERE scale_type = ? ORDER BY score DESC LIMIT 1',
            [$scaleType]
        );
        return $fallback['interpretation'] ?? '';
    }

    /**
     * Options for filter dropdowns.
     */
    public function filterOptions(): array
    {
        return [
            'programs' => Database::fetchAll('SELECT id, code, name FROM programs WHERE deleted_at IS NULL ORDER BY name'),
            'batches'  => Database::fetchAll('SELECT id, year, label FROM batches WHERE deleted_at IS NULL ORDER BY year DESC'),
            'sectors'  => Database::fetchAll('SELECT id, name FROM employment_sectors WHERE deleted_at IS NULL ORDER BY name'),
        ];
    }
}
