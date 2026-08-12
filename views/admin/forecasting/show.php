<?php
/** @var array $model @var array $series @var array $results @var array $params @var int $target @var string $unit @var ?array $benchmark @var string $interpretation */
$model = $model ?? [];
$series = $series ?? [];
$results = $results ?? [];
$target = $target ?? 70;
$unit = $unit ?? '';
$benchmark = $benchmark ?? null;
$interpretation = $interpretation ?? '';

$historical = array_values(array_filter($series, static fn ($s) => !$s['is_forecast']));
$projected  = array_values(array_filter($series, static fn ($s) => $s['is_forecast']));
$lastProjected = $projected ? end($projected) : null;
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900"><?= e($model['indicator_label'] ?? 'Forecast') ?></h2>
            <p class="text-sm text-ink-500">
                <?= (int) $model['historical_start'] ?>–<?= (int) $model['historical_end'] ?> history
                &middot; <?= (int) $model['forecast_start'] ?>–<?= (int) $model['forecast_end'] ?> projection
                &middot; <?= e(ucwords(str_replace('_', ' ', $model['method'] ?? ''))) ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($benchmark): ?>
                <span class="badge-blue"><?= e($benchmark['label']) ?>: <?= e($benchmark['value']) ?></span>
            <?php endif; ?>
            <a href="<?= url('admin/forecasting') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
        </div>
    </div>

    <!-- Model stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= (int) $model['observations'] ?></p>
            <p class="text-xs text-ink-500">Historical Observations</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $model['slope'] ?? '—' ?></p>
            <p class="text-xs text-ink-500">Slope (trend per year)</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold <?= $model['r_squared'] !== null && $model['r_squared'] >= 0.3 ? 'text-emerald-600' : 'text-amber-600' ?>">
                <?= $model['r_squared'] ?? '—' ?>
            </p>
            <p class="text-xs text-ink-500">Coefficient of Determination (R²)</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $lastProjected ? $lastProjected['value'] : '—' ?> <?= e($unit) ?></p>
            <p class="text-xs text-ink-500">Projected/Estimated <?= $lastProjected ? $lastProjected['year'] : '' ?> value</p>
        </div>
    </div>

    <!-- Chart -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Historical Trend &amp; Projection</h3></div>
        <div class="card-body">
            <div class="h-80"><canvas id="forecastChart"></canvas></div>
        </div>
    </div>

    <!-- Interpretation -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Interpretation</h3></div>
        <div class="card-body space-y-2 text-sm text-ink-600">
            <p><?= e($interpretation) ?></p>
            <p class="text-xs text-ink-500">
                <?= e(\App\Services\ForecastingService::rSquaredNote()) ?>
                Current R² = <?= $model['r_squared'] ?? 'n/a' ?>.
            </p>
        </div>
    </div>

    <!-- Warnings -->
    <?php if (!empty($model['warnings'])): ?>
        <div class="alert-box flex items-start gap-2 rounded-lg border px-4 py-3 text-sm bg-amber-50 border-amber-200 text-amber-800">
            <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <div class="flex-1"><?= nl2br(e($model['warnings'])) ?></div>
        </div>
    <?php endif; ?>

    <!-- Data table -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Forecast Data</h3></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Value<?= $unit !== '' ? ' (' . e($unit) . ')' : '' ?></th>
                        <th>Lower Bound</th>
                        <th>Upper Bound</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No forecast data.</td></tr>
                <?php endif; ?>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= (int) $r['year'] ?></td>
                        <td><?= $r['value'] !== null && is_finite((float) $r['value']) ? $r['value'] : '—' ?></td>
                        <td><?= $r['lower_bound'] !== null && is_finite((float) $r['lower_bound']) ? $r['lower_bound'] : '—' ?></td>
                        <td><?= $r['upper_bound'] !== null && is_finite((float) $r['upper_bound']) ? $r['upper_bound'] : '—' ?></td>
                        <td>
                            <?php if ($r['is_forecast']): ?>
                                <span class="badge-blue">Projected/Estimated</span>
                            <?php else: ?>
                                <span class="badge-gray">Historical</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$forecastData = [
    'labels' => array_map(static fn ($s) => (string) $s['year'], $series),
    'historical' => array_map(static fn ($s) => $s['is_forecast'] ? null : $s['value'], $series),
    'projected'  => array_map(static fn ($s) => $s['is_forecast'] ? $s['value'] : null, $series),
    'lower'      => array_map(static fn ($s) => $s['is_forecast'] ? $s['lower'] : null, $series),
    'upper'      => array_map(static fn ($s) => $s['is_forecast'] ? $s['upper'] : null, $series),
];
$scripts = '<script>window.forecastData = ' . json_encode($forecastData) . ';</script><script src="' . asset('js/pages/admin-forecasting.js') . '"></script>';
