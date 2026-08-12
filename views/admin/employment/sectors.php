<?php
/** @var array $sectors @var string $search */
$sectors = $sectors ?? [];
$search = $search ?? '';
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Employment Sectors</h2>
            <p class="text-sm text-ink-500"><?= number_format($sectors['total'] ?? 0) ?> sector<?= ($sectors['total'] ?? 0) === 1 ? '' : 's' ?> found</p>
        </div>
        <a href="<?= url('admin/employment') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Employment</a>
    </div>

    <!-- Add sector -->
    <form method="POST" action="<?= url('admin/employment-sectors') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <?= csrf_field() ?>
        <div class="min-w-[240px] flex-1">
            <label class="label" for="new-name">Sector Name</label>
            <input type="text" name="name" id="new-name" class="input" placeholder="e.g. Information Technology" required>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Sector</button>
    </form>

    <!-- Search -->
    <form method="GET" action="<?= url('admin/employment-sectors') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[240px] flex-1">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Sector name" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Search</button>
        <a href="<?= url('admin/employment-sectors') ?>" class="btn-secondary">Clear</a>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sectors['items'])): ?>
                    <tr><td colspan="4" class="!text-center !py-10 text-ink-500">No sectors found.</td></tr>
                <?php endif; ?>
                <?php foreach ($sectors['items'] as $s): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($s['name']) ?></td>
                        <td><?= number_format((int) $s['usage_count']) ?> profile<?= (int) $s['usage_count'] === 1 ? '' : 's' ?></td>
                        <td>
                            <?php if ($s['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="<?= url('admin/employment-sectors/' . (int) $s['id']) ?>" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="text" name="name" value="<?= e($s['name']) ?>" class="input !w-auto !py-1.5" required>
                                    <label class="inline-flex items-center gap-1 text-xs text-ink-600">
                                        <input type="checkbox" name="is_active" value="1" <?= $s['is_active'] ? 'checked' : '' ?> class="rounded"> Active
                                    </label>
                                    <button type="submit" class="icon-btn" title="Save"><i data-lucide="save" class="w-4 h-4"></i></button>
                                </form>
                                <form method="POST" action="<?= url('admin/employment-sectors/' . (int) $s['id']) ?>" onsubmit="return confirmAction('Remove sector <?= e(addslashes($s['name'])) ?>?', this);">
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
            'paginator' => $sectors,
            'path'      => 'admin/employment-sectors',
            'query'     => ['search' => $search],
        ]) ?>
    </div>
</div>
