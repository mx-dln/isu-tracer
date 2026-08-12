<?php
/** @var array $kpis @var array $employmentByYear @var array $statusDistribution @var array $sectorDistribution @var array $relevance @var array $competency @var array $timeToEmployment @var array $filters @var array $selected */
$kpis = $kpis ?? [];
$employmentByYear = $employmentByYear ?? [];
$statusDistribution = $statusDistribution ?? [];
$sectorDistribution = $sectorDistribution ?? [];
$relevance = $relevance ?? [];
$competency = $competency ?? [];
$timeToEmployment = $timeToEmployment ?? [];
$filters = $filters ?? [];
$selected = $selected ?? [];
?>
<div class="space-y-6">

    <!-- Filter bar -->
    <form method="GET" action="<?= url('admin/dashboard') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="f-batch">Graduation Year</label>
            <select name="batch_id" id="f-batch" class="input !w-auto">
                <option value="">All years</option>
                <?php foreach ($filters['batches'] ?? [] as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= ($selected['batch_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-program">Program</label>
            <select name="program_id" id="f-program" class="input !w-auto">
                <option value="">All programs</option>
                <?php foreach ($filters['programs'] ?? [] as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($selected['program_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Apply</button>
        <?php if ($selected): ?>
            <a href="<?= url('admin/dashboard') ?>" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php
        $cards = [
            ['label' => 'Total IAT Graduates', 'value' => $kpis['total_graduates'] ?? 0, 'icon' => 'users', 'color' => 'text-sky-600 bg-sky-50'],
            ['label' => 'Survey Responses', 'value' => $kpis['total_respondents'] ?? 0, 'icon' => 'clipboard-check', 'color' => 'text-emerald-600 bg-emerald-50'],
            ['label' => 'Response Rate', 'value' => ($kpis['response_rate'] ?? 0) . '%', 'icon' => 'percent', 'color' => 'text-violet-600 bg-violet-50'],
            ['label' => 'Employment Rate', 'value' => ($kpis['employment_rate'] ?? 0) . '%', 'icon' => 'briefcase', 'color' => 'text-brand-600 bg-brand-50'],
            ['label' => 'Self-Employment Rate', 'value' => ($kpis['self_employment_rate'] ?? 0) . '%', 'icon' => 'store', 'color' => 'text-amber-600 bg-amber-50'],
            ['label' => 'Unemployment Rate', 'value' => ($kpis['unemployment_rate'] ?? 0) . '%', 'icon' => 'user-x', 'color' => 'text-red-600 bg-red-50'],
            ['label' => 'Further Studies', 'value' => ($kpis['further_studies_rate'] ?? 0) . '%', 'icon' => 'book-open', 'color' => 'text-sky-600 bg-sky-50'],
            ['label' => 'Job Relevance Rate', 'value' => ($kpis['job_relevance_rate'] ?? 0) . '%', 'icon' => 'target', 'color' => 'text-emerald-600 bg-emerald-50'],
            ['label' => 'Avg. Time to First Job', 'value' => ($kpis['avg_time_to_first_employment'] ?? '—') . ($kpis['avg_time_to_first_employment'] !== null ? ' mo' : ''), 'icon' => 'clock', 'color' => 'text-violet-600 bg-violet-50'],
            ['label' => 'Avg. Competency Score', 'value' => $kpis['avg_competency_score'] ?? '—', 'icon' => 'award', 'color' => 'text-brand-600 bg-brand-50'],
            ['label' => 'Curriculum Relevance', 'value' => $kpis['curriculum_relevance_score'] ?? '—', 'icon' => 'book-check', 'color' => 'text-amber-600 bg-amber-50'],
        ];
        foreach ($cards as $card): ?>
            <div class="kpi-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 <?= $card['color'] ?>">
                    <i data-lucide="<?= $card['icon'] ?>" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-ink-900 leading-tight"><?= e($card['value']) ?></p>
                    <p class="text-xs text-ink-500 truncate"><?= e($card['label']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Employment Status</h2>
                <span class="badge-gray">Respondents</span>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartEmployment"></canvas></div></div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Employment Rate by Graduation Year</h2>
                <span class="badge-gray">Historical</span>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartEmploymentByYear"></canvas></div></div>
        </div>
    </div>

    <!-- Charts row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Job Relevance</h2>
                <span class="badge-green">WM: <?= e($relevance['weighted_mean'] ?? 0) ?> &middot; <?= e($relevance['interpretation'] ?? '') ?></span>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartRelevance"></canvas></div></div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Employment Sector</h2>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartSector"></canvas></div></div>
        </div>
    </div>

    <!-- Charts row 3 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Competency Performance</h2>
                <span class="badge-blue">Overall: <?= e($competency['overall'] ?? '—') ?>/5</span>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartCompetency"></canvas></div></div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Time to First Employment</h2>
            </div>
            <div class="card-body"><div class="h-72"><canvas id="chartTimeToEmployment"></canvas></div></div>
        </div>
    </div>
</div>

<?php
$chartData = [
    'employmentStatus' => [
        'labels' => ['Employed', 'Self-employed', 'Unemployed', 'Further Studies'],
        'data'   => [
            $statusDistribution['employed']['count'] ?? 0,
            $statusDistribution['self_employed']['count'] ?? 0,
            $statusDistribution['unemployed']['count'] ?? 0,
            $statusDistribution['further_studies']['count'] ?? 0,
        ],
    ],
    'employmentByYear' => [
        'labels' => array_keys($employmentByYear),
        'data'   => array_map(fn ($v) => $v['rate'], array_values($employmentByYear)),
    ],
    'relevance' => [
        'labels' => ['5 - Highly Aligned', '4 - Aligned', '3 - Moderately Aligned', '2 - Weakly Aligned', '1 - Not Aligned'],
        'data'   => [
            $relevance['frequencies'][5]['count'] ?? 0,
            $relevance['frequencies'][4]['count'] ?? 0,
            $relevance['frequencies'][3]['count'] ?? 0,
            $relevance['frequencies'][2]['count'] ?? 0,
            $relevance['frequencies'][1]['count'] ?? 0,
        ],
    ],
    'sector' => [
        'labels' => array_keys($sectorDistribution),
        'data'   => array_map(fn ($v) => $v['count'], array_values($sectorDistribution)),
    ],
    'competency' => [
        'labels' => array_keys($competency['ranking'] ?? []),
        'data'   => array_values($competency['ranking'] ?? []),
    ],
    'timeToEmployment' => [
        'labels' => array_keys($timeToEmployment),
        'data'   => array_map(fn ($v) => $v['count'], array_values($timeToEmployment)),
    ],
];
$scripts = '<script>window.dashboardData = ' . json_encode($chartData) . ';</script><script src="' . asset('js/pages/admin-dashboard.js') . '"></script>';
