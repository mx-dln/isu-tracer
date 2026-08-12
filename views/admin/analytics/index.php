<?php
/** @var array $kpis @var array $employmentByYear @var array $statusDistribution @var array $sectorDistribution @var array $timeToEmployment @var array $relevance @var array $competency @var array $curriculum @var array $filters @var array $selected @var array $years */
$kpis = $kpis ?? [];
$employmentByYear = $employmentByYear ?? [];
$statusDistribution = $statusDistribution ?? [];
$sectorDistribution = $sectorDistribution ?? [];
$timeToEmployment = $timeToEmployment ?? [];
$relevance = $relevance ?? [];
$competency = $competency ?? [];
$curriculum = $curriculum ?? [];
$filters = $filters ?? [];
$selected = $selected ?? [];
$years = $years ?? [];

$statusLabel = [
    'employed' => ['Employed', 'badge-green'], 'self_employed' => ['Self-Employed', 'badge-blue'],
    'unemployed' => ['Unemployed', 'badge-red'], 'further_studies' => ['Further Studies', 'badge-amber'],
];
?>
<div class="space-y-6">

    <!-- Filter bar -->
    <form method="GET" action="<?= url('admin/analytics') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="f-program">Program</label>
            <select name="program_id" id="f-program" class="input !w-auto">
                <option value="">All programs</option>
                <?php foreach (($filters['programs'] ?? []) as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($selected['program_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-batch">Batch</label>
            <select name="batch_id" id="f-batch" class="input !w-auto">
                <option value="">All batches</option>
                <?php foreach (($filters['batches'] ?? []) as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= ($selected['batch_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-year">Graduation Year</label>
            <select name="graduation_year" id="f-year" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= ($selected['graduation_year'] ?? 0) == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-status">Employment Status</label>
            <select name="status" id="f-status" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach ($statusLabel as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($selected['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-sector">Sector</label>
            <select name="sector_id" id="f-sector" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach (($filters['sectors'] ?? []) as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($selected['sector_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Apply</button>
        <a href="<?= url('admin/analytics') ?>" class="btn-secondary">Reset</a>
    </form>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= number_format($kpis['total_graduates'] ?? 0) ?></p>
            <p class="text-xs text-ink-500">Total Graduates</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= number_format($kpis['total_respondents'] ?? 0) ?></p>
            <p class="text-xs text-ink-500">Respondents &middot; <?= ($kpis['response_rate'] ?? 0) ?>% rate</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-emerald-600"><?= ($kpis['employment_rate'] ?? 0) ?>%</p>
            <p class="text-xs text-ink-500">Employment Rate</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-red-600"><?= ($kpis['unemployment_rate'] ?? 0) ?>%</p>
            <p class="text-xs text-ink-500">Unemployment Rate</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= ($kpis['avg_time_to_first_employment'] ?? '—') ?></p>
            <p class="text-xs text-ink-500">Avg. Months to 1st Job</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= ($kpis['job_relevance_rate'] ?? 0) ?>%</p>
            <p class="text-xs text-ink-500">Job Relevance Rate (&ge;3)</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $kpis['avg_competency_score'] ?? '—' ?></p>
            <p class="text-xs text-ink-500">Avg. Competency (1–5)</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $kpis['curriculum_relevance_score'] ?? '—' ?></p>
            <p class="text-xs text-ink-500">Curriculum Relevance (1–5)</p>
        </div>
    </div>

    <!-- Charts row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Employment Status Distribution</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartStatus"></canvas></div>
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <?php foreach ($statusDistribution as $status => $item): ?>
                        <div class="rounded-lg border border-ink-100 bg-ink-50 p-2 text-center">
                            <p class="text-xs text-ink-500"><?= e($statusLabel[$status][0] ?? ucwords(str_replace('_', ' ', $status))) ?></p>
                            <p class="text-sm font-bold text-ink-800"><?= (int) $item['count'] ?> <span class="text-xs font-normal text-ink-400">(<?= $item['percentage'] ?>%)</span></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Employment Rate by Year</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartYear"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Charts row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Time to First Employment</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartTime"></canvas></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Job Relevance (Weighted Mean: <?= $relevance['weighted_mean'] ?? '—' ?>/5)</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartRelevance"></canvas></div>
                <p class="mt-3 text-xs text-ink-500"><?= e($relevance['interpretation'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <!-- Charts row 3 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Sector Distribution</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartSector"></canvas></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Competency by Category</h3></div>
            <div class="card-body">
                <div class="h-64"><canvas id="chartCompetency"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Curriculum table -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Curriculum Feedback Weighted Means</h3>
            <span class="badge-blue">Overall: <?= $curriculum['overall'] ?? '—' ?>/5</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Responses</th>
                        <th>Weighted Mean</th>
                        <th>Interpretation</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty(($curriculum['items'] ?? []))): ?>
                    <tr><td colspan="4" class="!text-center !py-8 text-ink-500">No curriculum feedback yet.</td></tr>
                <?php endif; ?>
                <?php foreach (($curriculum['items'] ?? []) as $item): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($item['question']) ?></td>
                        <td><?= number_format((int) $item['responses']) ?></td>
                        <td><?= $item['score'] !== null ? $item['score'] . '/5' : '—' ?></td>
                        <td class="text-sm text-ink-600"><?= e($item['interpretation'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$chartData = [
    'status' => [
        'labels' => array_map(static fn ($s) => $statusLabel[$s][0] ?? ucwords(str_replace('_', ' ', $s)), array_keys($statusDistribution)),
        'data'   => array_map(static fn ($v) => $v['count'], array_values($statusDistribution)),
    ],
    'year' => [
        'labels' => array_keys($employmentByYear),
        'data'   => array_map(static fn ($v) => $v['rate'], array_values($employmentByYear)),
    ],
    'time' => [
        'labels' => array_keys($timeToEmployment),
        'data'   => array_map(static fn ($v) => $v['count'], array_values($timeToEmployment)),
    ],
    'relevance' => [
        'labels' => ['5', '4', '3', '2', '1'],
        'data'   => [
            ($relevance['frequencies'][5]['count'] ?? 0),
            ($relevance['frequencies'][4]['count'] ?? 0),
            ($relevance['frequencies'][3]['count'] ?? 0),
            ($relevance['frequencies'][2]['count'] ?? 0),
            ($relevance['frequencies'][1]['count'] ?? 0),
        ],
    ],
    'sector' => [
        'labels' => array_keys($sectorDistribution),
        'data'   => array_map(static fn ($v) => $v['count'], array_values($sectorDistribution)),
    ],
    'competency' => [
        'labels' => array_keys($competency['ranking'] ?? []),
        'data'   => array_values($competency['ranking'] ?? []),
    ],
];
$scripts = '<script>window.analyticsData = ' . json_encode($chartData) . ';</script><script src="' . asset('js/pages/admin-analytics.js') . '"></script>';
