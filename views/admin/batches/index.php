<?php
/** @var array $batches @var string $search */
$batches = $batches ?? [];
$search = $search ?? '';
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Batches</h2>
            <p class="text-sm text-ink-500"><?= number_format($batches['total'] ?? 0) ?> graduation cohorts</p>
        </div>
        <a href="<?= url('admin/batches/create') ?>" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Batch</a>
    </div>

    <form method="GET" action="<?= url('admin/batches') ?>" class="card p-4 flex items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Year or label" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="search" class="w-4 h-4"></i> Search</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Label</th>
                        <th>Graduates</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($batches['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-10 text-ink-500">No batches found.</td></tr>
                <?php endif; ?>
                <?php foreach ($batches['items'] as $b): ?>
                    <tr>
                        <td class="font-mono font-medium text-ink-800"><?= (int) $b['year'] ?></td>
                        <td><?= e($b['label'] ?: '—') ?></td>
                        <td><?= number_format((int) $b['graduate_count']) ?></td>
                        <td>
                            <?php if ($b['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/batches/' . (int) $b['id'] . '/edit') ?>" class="icon-btn" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="<?= url('admin/batches/' . (int) $b['id']) ?>" onsubmit="return confirmAction('Delete batch <?= (int) $b['year'] ?>?', this);">
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
            'paginator' => $batches,
            'path'      => 'admin/batches',
            'query'     => $search !== '' ? ['search' => $search] : [],
        ]) ?>
    </div>
</div>
