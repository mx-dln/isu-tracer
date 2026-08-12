<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Linear-regression forecasting engine for employment indicators.
 *
 * Uses the least-squares method over historical observations (by graduation
 * year) to project future values, with a simple residual-based confidence band.
 */
final class ForecastingService
{
    public const INDICATORS = [
        'employment_rate'            => ['label' => 'Employment Rate', 'unit' => '%'],
        'employment_within_3_months' => ['label' => 'Employment Within 3 Months', 'unit' => '%'],
        'average_waiting_time'       => ['label' => 'Average Waiting Time', 'unit' => 'months'],
        'median_waiting_time'        => ['label' => 'Median Waiting Time', 'unit' => 'months'],
        'job_relevance'              => ['label' => 'Job Relevance (mean)', 'unit' => '/5'],
        'competency_rating'          => ['label' => 'Competency Rating (mean)', 'unit' => '/5'],
    ];

    /**
     * Minimum number of historical observations required to produce a forecast.
     */
    public const MIN_OBSERVATIONS = 3;

    /**
     * Graduation date proxy: graduation_year-06-15 (no exact date is stored).
     * This must stay consistent with AnalyticsService::averageTimeToFirstEmployment().
     */
    private const GRAD_MONTH = 6;
    private const GRAD_DAY = 15;

    /**
     * Indicator-specific benchmark shown in the UI, or null when none applies.
     * Values are read from the existing Settings system.
     */
    public static function benchmark(string $indicator): ?array
    {
        switch ($indicator) {
            case 'employment_rate':
                return ['label' => 'Employment target', 'value' => (int) (setting('employment_target', 70) ?: 70) . '%'];
            case 'employment_within_3_months':
                return ['label' => '3-Month employment benchmark', 'value' => (int) (setting('three_month_employment_target', 70) ?: 70) . '%'];
            case 'average_waiting_time':
            case 'median_waiting_time':
                return ['label' => 'Waiting-time benchmark', 'value' => '≤ ' . (int) (setting('waiting_time_benchmark_months', 3) ?: 3) . ' months'];
            default:
                return null;
        }
    }

    /**
     * Display unit for an indicator (including legacy keys).
     */
    public static function unit(string $indicator): string
    {
        return self::INDICATORS[$indicator]['unit']
            ?? (in_array($indicator, ['time_to_employment'], true) ? 'months' : '');
    }

    /**
     * A short, non-causal interpretation of the forecast for the detail page.
     */
    public static function interpretation(string $indicator, ?array $model): string
    {
        $slope = $model ? (float) ($model['slope'] ?? 0) : 0.0;
        $trend = abs($slope) < 0.0001 ? 'remained stable' : ($slope > 0 ? 'increased' : 'decreased');
        $change = abs($slope) < 0.0001 ? 'stability' : ($slope > 0 ? 'an increase' : 'a decrease');

        switch ($indicator) {
            case 'employment_rate':
                $base = "Historical employment has {$trend} over the observed years. The regression model projects a continuation of this trend.";
                break;
            case 'employment_within_3_months':
                $base = 'The forecast indicates the projected percentage of graduates who obtain their first employment within three months of graduation.';
                break;
            case 'average_waiting_time':
            case 'median_waiting_time':
                $base = "The forecast indicates a projected {$change} in waiting time to first employment. A decrease suggests graduates may transition into employment more quickly.";
                break;
            case 'job_relevance':
                $base = 'The forecast indicates the projected mean job-relevance rating (1–5) among employed graduates.';
                break;
            case 'competency_rating':
                $base = 'The forecast indicates the projected mean competency rating (1–5).';
                break;
            default:
                $base = 'The forecast is derived from a linear regression of historical observations.';
        }
        return $base . ' This is a projected trend based on historical observations and does not imply causation.';
    }

    public static function rSquaredNote(): string
    {
        return 'R² indicates how much of the variation in the historical observations is explained by the linear regression model.';
    }

    /**
     * Gather historical observations for an indicator.
     *
     * @return array<int, array{year:int, value:float}>
     */
    public function historical(string $indicator): array
    {
        $analytics = new AnalyticsService();

        switch ($indicator) {
            case 'employment_rate':
                $rows = $analytics->employmentRateByYear();
                $out = [];
                foreach ($rows as $year => $row) {
                    $out[] = ['year' => (int) $year, 'value' => (float) $row['rate']];
                }
                return $out;

            case 'employment_within_3_months':
                $out = [];
                foreach ($this->waitingByYear() as $year => $rows) {
                    $withDate = count($rows);
                    $within = count(array_filter($rows, static fn ($r) => $r['within3']));
                    if ($withDate > 0) {
                        $out[] = ['year' => $year, 'value' => round($within / $withDate * 100, 1)];
                    }
                }
                return $out;

            case 'average_waiting_time':
                $out = [];
                foreach ($this->waitingByYear() as $year => $rows) {
                    $out[] = ['year' => $year, 'value' => round(array_sum(array_column($rows, 'months')) / count($rows), 1)];
                }
                return $out;

            case 'median_waiting_time':
                $out = [];
                foreach ($this->waitingByYear() as $year => $rows) {
                    $values = array_column($rows, 'months');
                    sort($values);
                    $count = count($values);
                    $median = $count % 2 === 1
                        ? $values[(int) (($count - 1) / 2)]
                        : ($values[(int) ($count / 2) - 1] + $values[(int) ($count / 2)]) / 2;
                    $out[] = ['year' => $year, 'value' => round($median, 1)];
                }
                return $out;

            case 'job_relevance':
                $rows = Database::fetchAll(
                    'SELECT g.graduation_year AS year,
                            AVG(ep.job_relevance_rating) AS value
                     FROM employment_profiles ep
                     JOIN graduates g ON g.id = ep.graduate_id
                     WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
                       AND ep.status IN ("employed","self_employed")
                       AND ep.job_relevance_rating IS NOT NULL
                       AND g.graduation_year IS NOT NULL
                     GROUP BY g.graduation_year
                     ORDER BY g.graduation_year'
                );
                return array_map(
                    static fn ($r) => ['year' => (int) $r['year'], 'value' => round((float) $r['value'], 2)],
                    $rows
                );

            case 'competency_rating':
                $rows = Database::fetchAll(
                    'SELECT g.graduation_year AS year, AVG(cr.rating) AS value
                     FROM competency_responses cr
                     JOIN graduates g ON g.id = cr.graduate_id
                     WHERE g.deleted_at IS NULL AND g.graduation_year IS NOT NULL AND cr.rating IS NOT NULL
                     GROUP BY g.graduation_year
                     ORDER BY g.graduation_year'
                );
                return array_map(
                    static fn ($r) => ['year' => (int) $r['year'], 'value' => round((float) $r['value'], 2)],
                    $rows
                );

            // Legacy key retained for saved models.
            case 'time_to_employment':
                $rows = Database::fetchAll(
                    'SELECT g.graduation_year AS year,
                            AVG(TIMESTAMPDIFF(MONTH,
                                STR_TO_DATE(CONCAT(g.graduation_year, "-06-15"), "%Y-%m-%d"),
                                ep.first_employment_date)) AS value
                     FROM employment_profiles ep
                     JOIN graduates g ON g.id = ep.graduate_id
                     WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
                       AND ep.status IN ("employed","self_employed")
                       AND ep.first_employment_date IS NOT NULL AND ep.first_employment_date <> ""
                       AND g.graduation_year IS NOT NULL
                     GROUP BY g.graduation_year
                     ORDER BY g.graduation_year'
                );
                return array_map(
                    static fn ($r) => ['year' => (int) $r['year'], 'value' => round((float) $r['value'], 1)],
                    $rows
                );

            default:
                return [];
        }
    }

    /**
     * Per-cohort waiting times (in months) from graduation to first employment.
     *
     * Only graduates whose first employment occurred after graduation are
     * included. Graduates employed before graduation ("Already employed before
     * graduation") and those without a first-employment date are excluded so
     * they cannot distort the waiting-time statistics.
     *
     * The 3-month classification uses the calendar rule:
     * first_employment_date <= graduation_date + 3 months.
     *
     * @return array<int, array<int, array{months:float, within3:int}>>
     */
    private function waitingByYear(): array
    {
        $rows = Database::fetchAll(
            'SELECT g.graduation_year AS year, ep.first_employment_date AS first_date
             FROM employment_profiles ep
             JOIN graduates g ON g.id = ep.graduate_id
             WHERE g.deleted_at IS NULL AND ep.deleted_at IS NULL AND ep.is_current = 1
               AND ep.status IN ("employed","self_employed")
               AND ep.first_employment_date IS NOT NULL AND ep.first_employment_date <> ""
               AND g.graduation_year IS NOT NULL'
        );

        $byYear = [];
        foreach ($rows as $r) {
            $year = (int) $r['year'];
            $gradDate = sprintf('%04d-%02d-%02d', $year, self::GRAD_MONTH, self::GRAD_DAY);
            $gradTs = strtotime($gradDate);
            $firstTs = strtotime((string) $r['first_date']);
            if ($gradTs === false || $firstTs === false) {
                continue;
            }
            if ($firstTs < $gradTs) {
                continue; // already employed before graduation
            }
            $days = ($firstTs - $gradTs) / 86400;
            $months = round($days / 30.4375, 3);
            $within3 = $firstTs <= strtotime($gradDate . ' +3 months') ? 1 : 0;
            $byYear[$year][] = ['months' => $months, 'within3' => $within3];
        }
        return $byYear;
    }

    /**
     * Least-squares linear regression on (x = year, y = value).
     *
     * @return array{slope:float, intercept:float, r_squared:?float}
     */
    public function fit(array $points): array
    {
        $n = count($points);
        if ($n < 2) {
            return ['slope' => 0.0, 'intercept' => 0.0, 'r_squared' => null];
        }

        $x = array_map(static fn ($p) => (float) $p['year'], $points);
        $y = array_map(static fn ($p) => (float) $p['value'], $points);

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $sxy = 0.0;
        $sxx = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sxy += ($x[$i] - $meanX) * ($y[$i] - $meanY);
            $sxx += ($x[$i] - $meanX) ** 2;
        }

        $slope = $sxx > 0 ? $sxy / $sxx : 0.0;
        $intercept = $meanY - $slope * $meanX;

        // Coefficient of determination.
        $sst = 0.0;
        $sse = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $sst += ($y[$i] - $meanY) ** 2;
            $sse += ($y[$i] - $predicted) ** 2;
        }
        $rSquared = $sst > 0 ? 1 - ($sse / $sst) : null;
        if ($rSquared !== null) {
            $rSquared = max(0.0, min(1.0, round($rSquared, 4)));
        }

        return ['slope' => round($slope, 4), 'intercept' => round($intercept, 4), 'r_squared' => $rSquared];
    }

    /**
     * Residual standard error for a confidence band.
     */
    private function residualStd(array $points, array $model): float
    {
        if (count($points) < 3) {
            return 0.0;
        }
        $errors = [];
        foreach ($points as $p) {
            $predicted = $model['slope'] * $p['year'] + $model['intercept'];
            $errors[] = ($p['value'] - $predicted) ** 2;
        }
        return sqrt(array_sum($errors) / (count($points) - 2));
    }

    /**
     * Generate a forecast: historical series + projected years with bounds.
     */
    public function forecast(string $indicator, int $yearsAhead = 3): array
    {
        $historical = $this->historical($indicator);
        usort($historical, static fn ($a, $b) => $a['year'] <=> $b['year']);
        $meta = self::INDICATORS[$indicator] ?? ['label' => $indicator, 'unit' => ''];

        if (count($historical) < self::MIN_OBSERVATIONS) {
            return [
                'indicator'   => $indicator,
                'label'       => $meta['label'],
                'unit'        => $meta['unit'],
                'model'       => null,
                'series'      => array_map(
                    static fn ($h) => ['year' => $h['year'], 'value' => $h['value'], 'is_forecast' => false, 'lower' => null, 'upper' => null],
                    $historical
                ),
                'warnings'    => [
                    'Insufficient historical observations for reliable forecasting. '
                        . 'Forecasting requires at least ' . self::MIN_OBSERVATIONS . ' data points.',
                ],
            ];
        }

        $model = $this->fit($historical);
        $lastYear = max(array_column($historical, 'year'));
        $sigma = $this->residualStd($historical, $model);

        $series = array_map(
            static fn ($h) => [
                'year' => $h['year'],
                'value' => $h['value'],
                'is_forecast' => false,
                'lower' => null,
                'upper' => null,
            ],
            $historical
        );

        for ($i = 1; $i <= $yearsAhead; $i++) {
            $year = $lastYear + $i;
            $value = $model['slope'] * $year + $model['intercept'];
            $band = $sigma > 0 ? 1.96 * $sigma : 0.0;
            // Sanitize non-finite values (residual std of 0 can push the upper
            // bound to NaN during JSON serialization).
            $value = is_finite($value) ? max(0, $value) : null;
            $lower = ($band > 0 && $value !== null && is_finite($value - $band))
                ? round(max(0, $value - $band), 2)
                : null;
            $upper = ($band > 0 && $value !== null && is_finite($value + $band))
                ? round($value + $band, 2)
                : null;
            $series[] = [
                'year' => $year,
                'value' => $value !== null ? round($value, 2) : null,
                'is_forecast' => true,
                'lower' => $lower,
                'upper' => $upper,
            ];
        }

        $warnings = [];
        if ($model['r_squared'] !== null && $model['r_squared'] < 0.3) {
            $warnings[] = 'The linear trend explains less than 30% of variance (R² = ' . $model['r_squared'] . '); forecast confidence is low.';
        }
        if (count($historical) < 5) {
            $warnings[] = 'Only ' . count($historical) . ' historical observations available; a longer history improves accuracy.';
        }

        return [
            'indicator' => $indicator,
            'label'     => $meta['label'],
            'unit'      => $meta['unit'],
            'model'     => $model,
            'series'    => $series,
            'warnings'  => $warnings,
            'historical_count' => count($historical),
        ];
    }

    /**
     * Persist a forecast model + results.
     */
    public function store(array $forecast, int $yearsAhead): int
    {
        $historical = array_values(array_filter($forecast['series'], static fn ($s) => !$s['is_forecast']));
        $projected  = array_values(array_filter($forecast['series'], static fn ($s) => $s['is_forecast']));
        usort($historical, static fn ($a, $b) => $a['year'] <=> $b['year']);
        usort($projected, static fn ($a, $b) => $a['year'] <=> $b['year']);

        $startYear = $historical ? $historical[0]['year'] : null;
        $endYear = $projected ? end($projected)['year'] : null;

        Database::run(
            'INSERT INTO forecast_models
                (name, method, indicator, indicator_label, historical_start, historical_end,
                 forecast_start, forecast_end, observations, slope, intercept, r_squared, warnings, is_demo)
             VALUES (?, "linear_regression", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [
                $forecast['label'] . ' (Generated ' . date('M j, Y') . ')',
                $forecast['indicator'],
                $forecast['label'],
                $startYear,
                end($historical)['year'],
                $projected ? $projected[0]['year'] : null,
                $endYear,
                count($historical),
                $forecast['model']['slope'],
                $forecast['model']['intercept'],
                $forecast['model']['r_squared'],
                $forecast['warnings'] ? implode(' ', $forecast['warnings']) : null,
            ]
        );
        $modelId = (int) Database::lastInsertId();

        foreach ($forecast['series'] as $point) {
            Database::run(
                'INSERT INTO forecast_results (model_id, year, is_forecast, value, lower_bound, upper_bound)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value), lower_bound = VALUES(lower_bound), upper_bound = VALUES(upper_bound)',
                [
                    $modelId,
                    $point['year'],
                    $point['is_forecast'] ? 1 : 0,
                    $point['value'],
                    $point['lower'],
                    $point['upper'],
                ]
            );
        }

        return $modelId;
    }

    /**
     * Recent persisted models.
     */
    public function recentModels(int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT fm.*, u.name AS created_by_name
             FROM forecast_models fm
             LEFT JOIN users u ON u.id = fm.created_by
             ORDER BY fm.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    public function modelResults(int $modelId): array
    {
        return Database::fetchAll(
            'SELECT * FROM forecast_results WHERE model_id = ? ORDER BY year',
            [$modelId]
        );
    }

    /**
     * Employment target threshold (for warnings).
     */
    public function employmentTarget(): int
    {
        return (int) (setting('employment_target', 70) ?: 70);
    }
}
