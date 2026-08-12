<?php
/** @var array $surveys @var int $graduateId */
$surveys = $surveys ?? [];
$graduateId = $graduateId ?? 0;
$statusBadge = [
    'active' => ['Active', 'badge-green'],
    'closed' => ['Closed', 'badge-red'],
];
?>
<div class="space-y-6 max-w-4xl">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800">Available Surveys</h3>
                <p class="text-xs text-ink-500">Complete the active tracer study survey to record your employment profile and feedback.</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            <?php if (empty($surveys)): ?>
                <div class="py-10 text-center">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto text-ink-300 mb-3"></i>
                    <p class="text-sm text-ink-500">No surveys are available at the moment.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($surveys as $s): ?>
                <?php [$label, $cls] = $statusBadge[$s['status']] ?? ['Unknown', 'badge-gray']; ?>
                <div class="rounded-xl border border-ink-200 p-5 flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[220px]">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-ink-800"><?= e($s['title']) ?></h4>
                            <span class="<?= $cls ?>"><?= $label ?></span>
                            <?php if ($s['submitted']): ?>
                                <span class="badge-green"><i data-lucide="check-circle" class="w-3 h-3"></i> Completed</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($s['description'])): ?>
                            <p class="text-sm text-ink-500 mt-1"><?= e($s['description']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-ink-400 mt-1">
                            Tracer year <?= e($s['tracer_year'] ?: '—') ?> &middot;
                            <?= e($s['start_date'] ?: '—') ?> &rarr; <?= e($s['end_date'] ?: '—') ?>
                        </p>
                    </div>
                    <?php if ($s['submitted']): ?>
                        <a href="<?= url('graduate/survey/' . (int) $s['id']) ?>" class="btn-secondary"><i data-lucide="eye" class="w-4 h-4"></i> View Response</a>
                    <?php elseif ($s['status'] === 'active'): ?>
                        <a href="<?= url('graduate/survey/' . (int) $s['id']) ?>" class="btn-primary"><i data-lucide="clipboard-list" class="w-4 h-4"></i> Start Survey</a>
                    <?php else: ?>
                        <span class="text-xs text-ink-400">Closed</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
