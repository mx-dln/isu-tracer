<?php
/** @var array $analysis */
$analysis = $analysis ?? [];
$categories = $analysis['categories'] ?? [];
$overall = $analysis['overall'] ?? null;
$ranking = $analysis['ranking'] ?? [];
$strongest = $analysis['strongest'] ?? [];
$weakest = $analysis['weakest'] ?? [];

$barColor = static function (float $score): string {
    if ($score >= 4.20) return 'bg-emerald-500';
    if ($score >= 3.40) return 'bg-brand-500';
    if ($score >= 2.60) return 'bg-amber-500';
    return 'bg-red-500';
};
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Competency Analysis</h2>
            <p class="text-sm text-ink-500">Weighted mean scores by competency category (1–5 scale)</p>
        </div>
        <a href="<?= url('admin/competencies') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
    </div>

    <!-- Overall + strongest/weakest -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="kpi-card">
            <p class="text-3xl font-bold text-ink-900"><?= $overall !== null ? $overall : '—' ?></p>
            <p class="text-xs text-ink-500">Overall Competency Mean</p>
        </div>
        <?php foreach ($strongest as $name => $score): ?>
            <div class="kpi-card">
                <p class="text-2xl font-bold text-emerald-600"><?= e($name) ?></p>
                <p class="text-xs text-ink-500">Strongest area &middot; <?= $score ?>/5</p>
            </div>
        <?php endforeach; ?>
        <?php foreach ($weakest as $name => $score): ?>
            <div class="kpi-card">
                <p class="text-2xl font-bold text-red-600"><?= e($name) ?></p>
                <p class="text-xs text-ink-500">Weakest area &middot; <?= $score ?>/5</p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Per-category bars -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Category Scores</h3></div>
        <div class="card-body space-y-5">
            <?php if (empty($categories)): ?>
                <div class="text-center py-10 text-ink-500">No competency data available.</div>
            <?php endif; ?>
            <?php foreach ($categories as $cat): ?>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-sm font-semibold text-ink-800"><?= e($cat['category']) ?></p>
                        <p class="text-sm font-semibold text-ink-700"><?= $cat['score'] !== null ? $cat['score'] : 'No data' ?>/5</p>
                    </div>
                    <div class="h-3 bg-ink-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $cat['score'] !== null ? $barColor($cat['score']) : 'bg-ink-200' ?> rounded-full transition-all duration-700"
                             style="width: <?= $cat['score'] !== null ? ($cat['score'] / 5 * 100) : 0 ?>%"></div>
                    </div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <?php foreach ($cat['competencies'] as $comp): ?>
                            <div class="flex items-center justify-between rounded-lg border border-ink-100 bg-ink-50 px-3 py-2 text-xs">
                                <span class="text-ink-600 truncate mr-2"><?= e($comp['name']) ?></span>
                                <span class="font-semibold text-ink-800 shrink-0">
                                    <?= $comp['score'] !== null ? $comp['score'] : '—' ?>
                                    <span class="text-ink-400 font-normal">/ <?= (int) $comp['responses'] ?> resp</span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
