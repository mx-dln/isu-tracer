<?php
/**
 * Survey Workspace Header — shared by Builder / Responses / Invitations.
 * Renders survey identity + actions, view tabs, and a compact stats strip.
 *
 * @var array  $survey  Survey row (title, description, status, tracer_year, start_date, end_date).
 * @var string $activeTab One of: builder | responses | invitations.
 * @var int $sectionCount @var int $questionCount @var int $responseCount
 * @var array $invitationStats (total, response_rate, ...)
 */
$survey = $survey ?? [];
$activeTab = $activeTab ?? 'builder';
$sectionCount = (int) ($sectionCount ?? 0);
$questionCount = (int) ($questionCount ?? 0);
$responseCount = (int) ($responseCount ?? 0);
$invitationStats = $invitationStats ?? [];
$insightsButton = !empty($insightsButton);
$base = 'admin/surveys/' . (int) ($survey['id'] ?? 0);

$statusBadge = [
    'draft'  => ['Draft', 'badge-gray'],
    'active' => ['Active', 'badge-green'],
    'closed' => ['Closed', 'badge-red'],
];
[$statusLabel, $statusCls] = $statusBadge[$survey['status'] ?? 'draft'] ?? ['Unknown', 'badge-gray'];

$fmtDate = function ($d): string {
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('M j, Y', $t) : (string) $d;
};
$startD = $fmtDate($survey['start_date'] ?? '');
$endD = $fmtDate($survey['end_date'] ?? '');
$hasDates = $startD !== '' || $endD !== '';

$tabs = [
    'builder' => [
        'label' => 'Builder',
        'href'  => url($base),
        'icon'  => 'hammer',
        'count' => null,
    ],
    'responses' => [
        'label' => 'Responses',
        'href'  => url($base . '/responses'),
        'icon'  => 'clipboard-check',
        'count' => $responseCount,
    ],
    'invitations' => [
        'label' => 'Invitations',
        'href'  => url($base . '/invitations'),
        'icon'  => 'send',
        'count' => (int) ($invitationStats['total'] ?? 0),
    ],
];

$stats = [
    ['label' => 'Sections', 'value' => number_format($sectionCount), 'href' => null],
    ['label' => 'Questions', 'value' => number_format($questionCount), 'href' => null],
    ['label' => 'Responses', 'value' => number_format($responseCount), 'href' => url($base . '/responses')],
    ['label' => 'Invitations', 'value' => number_format((int) ($invitationStats['total'] ?? 0)), 'href' => url($base . '/invitations')],
    ['label' => 'Completion', 'value' => (int) ($invitationStats['response_rate'] ?? 0) . '%', 'href' => null],
];
?>
<div class="card overflow-hidden">

    <!-- Identity + Actions -->
    <div class="px-5 py-4 lg:px-6 lg:py-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h1 class="text-lg lg:text-xl font-bold text-ink-900 leading-tight"><?= e($survey['title'] ?? 'Untitled Survey') ?></h1>
                    <span class="<?= $statusCls ?>"><?= $statusLabel ?></span>
                </div>
                <?php if (!empty($survey['description'])): ?>
                    <p class="text-sm text-ink-500 mt-1 max-w-2xl"><?= e($survey['description']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-ink-400 mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
                        Tracer Year <?= e($survey['tracer_year'] ?? '—') ?>
                    </span>
                    <?php if ($hasDates): ?>
                        <span class="text-ink-300" aria-hidden="true">&middot;</span>
                        <span><?= e($startD ?: '—') ?> &rarr; <?= e($endD ?: '—') ?></span>
                    <?php else: ?>
                        <span class="text-ink-300" aria-hidden="true">&middot;</span>
                        <span>No schedule set</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <?php if (($survey['status'] ?? '') === 'draft'): ?>
                    <form method="POST" action="<?= url($base . '/activate') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-success h-9"><i data-lucide="play" class="w-4 h-4"></i> Activate</button>
                    </form>
                <?php elseif (($survey['status'] ?? '') === 'active'): ?>
                    <form method="POST" action="<?= url($base . '/close') ?>" onsubmit="return confirm('Close this survey? Responses will no longer be accepted.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-secondary h-9"><i data-lucide="square" class="w-4 h-4"></i> Close</button>
                    </form>
                <?php endif; ?>
                <a href="<?= url($base . '/preview') ?>" target="_blank" rel="noopener" class="btn-secondary h-9"><i data-lucide="eye" class="w-4 h-4"></i> Preview</a>
                <a href="<?= url($base . '/edit') ?>" class="btn-secondary h-9"><i data-lucide="settings" class="w-4 h-4"></i> Settings</a>
                <?php if ($insightsButton): ?>
                    <button type="button" class="btn-secondary h-9" data-open-insights title="View per-question analytics"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Insights</button>
                <?php endif; ?>
                <a href="<?= url('admin/surveys') ?>" class="btn-secondary h-9"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
            </div>
        </div>
    </div>

    <!-- View tabs -->
    <nav class="flex items-center gap-1 px-5 lg:px-6 py-2 border-t border-ink-100 bg-ink-50/50 overflow-x-auto" aria-label="Survey views">
        <?php foreach ($tabs as $key => $tab): $isActive = $activeTab === $key; ?>
            <a href="<?= e($tab['href']) ?>"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors
                      <?= $isActive ? 'bg-ink-900 text-white shadow-sm' : 'text-ink-600 hover:bg-ink-100' ?>">
                <i data-lucide="<?= e($tab['icon']) ?>" class="w-4 h-4"></i>
                <?= e($tab['label']) ?>
                <?php if ($tab['count'] !== null): ?>
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-semibold
                                 <?= $isActive ? 'bg-white/20 text-white' : 'bg-ink-100 text-ink-500' ?>"><?= number_format($tab['count']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Stats strip -->
    <dl class="grid grid-cols-2 sm:grid-cols-5 border-t border-ink-100">
        <?php foreach ($stats as $i => $s): ?>
            <?php
            $cellCls = 'flex items-center justify-between gap-2 px-5 py-3 min-w-0 sm:flex-col sm:items-center sm:justify-center sm:gap-0.5 sm:text-center odd:border-r sm:odd:border-r-0 sm:border-l sm:first:border-l-0 border-ink-100';
            ?>
            <div class="<?= $cellCls ?>">
                <dt class="text-[11px] font-medium uppercase tracking-wider text-ink-400 order-2 sm:order-1"><?= e($s['label']) ?></dt>
                <dd class="text-lg font-bold text-ink-900 leading-none order-1 sm:order-2">
                    <?php if ($s['href']): ?>
                        <a href="<?= e($s['href']) ?>" class="hover:text-brand-700 transition-colors"><?= $s['value'] ?></a>
                    <?php else: ?>
                        <?= $s['value'] ?>
                    <?php endif; ?>
                </dd>
            </div>
        <?php endforeach; ?>
    </dl>
</div>