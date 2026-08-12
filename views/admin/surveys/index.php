<?php
/** @var array $surveys @var string $search @var array|null $activeSurvey */
$surveys = $surveys ?? [];
$search = $search ?? '';
$activeSurvey = $activeSurvey ?? null;

$statusBadge = [
    'draft'   => ['Draft', 'badge-gray'],
    'active'  => ['Active', 'badge-green'],
    'closed'  => ['Closed', 'badge-red'],
];
?>
<div class="space-y-6">

    <?php if ($activeSurvey): ?>
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <i data-lucide="circle-check" class="w-5 h-5 text-emerald-600"></i>
            <div class="flex-1 min-w-[200px]">
                <p class="text-sm font-semibold text-emerald-800">Active survey: <?= e($activeSurvey['title']) ?></p>
                <p class="text-xs text-emerald-700">
                    <?= (int) $activeSurvey['tracer_year'] ? ('Tracer year ' . (int) $activeSurvey['tracer_year'] . ' · ') : '' ?>
                    <?= $activeSurvey['start_date'] ? ('Opens ' . e($activeSurvey['start_date']) . ' · ') : '' ?>
                    <?= $activeSurvey['end_date'] ? ('Closes ' . e($activeSurvey['end_date'])) : 'No end date' ?>
                </p>
            </div>
            <a href="<?= url('admin/surveys/' . (int) $activeSurvey['id']) ?>" class="btn-secondary">View</a>
        </div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Surveys</h2>
            <p class="text-sm text-ink-500"><?= number_format($surveys['total'] ?? 0) ?> tracer study surveys</p>
        </div>
        <a href="<?= url('admin/surveys/create') ?>" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Create Survey</a>
    </div>

    <form method="GET" action="<?= url('admin/surveys') ?>" class="card p-4 flex items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Survey title" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="search" class="w-4 h-4"></i> Search</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Tracer Year</th>
                        <th>Questions</th>
                        <th>Responses</th>
                        <th>Invitations</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($surveys['items'])): ?>
                    <tr><td colspan="8" class="!text-center !py-10 text-ink-500">No surveys found.</td></tr>
                <?php endif; ?>
                <?php foreach ($surveys['items'] as $s): ?>
                    <?php [$label, $cls] = $statusBadge[$s['status']] ?? ['Unknown', 'badge-gray']; ?>
                    <tr>
                        <td class="font-medium text-ink-800">
                            <a href="<?= url('admin/surveys/' . (int) $s['id']) ?>" class="hover:text-brand-700 hover:underline"><?= e($s['title']) ?></a>
                        </td>
                        <td><?= e($s['tracer_year'] ?: '—') ?></td>
                        <td><?= number_format((int) $s['question_count']) ?></td>
                        <td><?= number_format((int) $s['response_count']) ?></td>
                        <td>
                            <a href="<?= url('admin/surveys/' . (int) $s['id'] . '/invitations') ?>" class="text-brand-700 hover:underline">
                                <?= number_format((int) $s['invitation_count']) ?>
                            </a>
                            <?php if ((int) $s['invitation_count']): ?>
                                <p class="text-[11px] text-ink-400"><?= (float) $s['invitation_rate'] ?>% complete</p>
                            <?php endif; ?>
                        </td>
                        <td class="!whitespace-normal text-xs text-ink-500">
                            <?= e($s['start_date'] ?: '—') ?> &rarr; <?= e($s['end_date'] ?: '—') ?>
                        </td>
                        <td><span class="<?= $cls ?>"><?= $label ?></span></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/surveys/' . (int) $s['id']) ?>" class="icon-btn" title="Builder"><i data-lucide="wrench" class="w-4 h-4"></i></a>
                                <a href="<?= url('admin/surveys/' . (int) $s['id'] . '/edit') ?>" class="icon-btn" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="<?= url('admin/surveys/' . (int) $s['id'] . '/duplicate') ?>" class="inline" title="Duplicate for the next tracer year">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="icon-btn" title="Duplicate for next year"><i data-lucide="copy" class="w-4 h-4"></i></button>
                                </form>
                                <?php if ($s['status'] === 'draft'): ?>
                                    <form method="POST" action="<?= url('admin/surveys/' . (int) $s['id'] . '/activate') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn !text-emerald-600 hover:!bg-emerald-50" title="Activate"><i data-lucide="play" class="w-4 h-4"></i></button>
                                    </form>
                                <?php elseif ($s['status'] === 'active'): ?>
                                    <form method="POST" action="<?= url('admin/surveys/' . (int) $s['id'] . '/close') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn !text-amber-600 hover:!bg-amber-50" title="Close" onclick="return confirm('Close this survey? Graduates will no longer submit responses.')"><i data-lucide="square" class="w-4 h-4"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= url('admin/surveys/' . (int) $s['id']) ?>" class="inline" onsubmit="return confirmAction('Delete survey <?= e(addslashes($s['title'])) ?>?', this);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $surveys,
            'path'      => 'admin/surveys',
            'query'     => $search !== '' ? ['search' => $search] : [],
        ]) ?>
    </div>
</div>
