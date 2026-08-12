<?php
/** @var array $programs @var string $search */
$programs = $programs ?? [];
$search = $search ?? '';
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Programs</h2>
            <p class="text-sm text-ink-500"><?= number_format($programs['total'] ?? 0) ?> degree offerings</p>
        </div>
        <a href="<?= url('admin/programs/create') ?>" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Program</a>
    </div>

    <form method="GET" action="<?= url('admin/programs') ?>" class="card p-4 flex items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Program name or code" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="search" class="w-4 h-4"></i> Search</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Program Name</th>
                        <th>Graduates</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($programs['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-10 text-ink-500">No programs found.</td></tr>
                <?php endif; ?>
                <?php foreach ($programs['items'] as $p): ?>
                    <tr>
                        <td class="font-mono font-medium text-ink-800"><?= e($p['code']) ?></td>
                        <td class="font-medium text-ink-800"><?= e($p['name']) ?></td>
                        <td><?= number_format((int) $p['graduate_count']) ?></td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/programs/' . (int) $p['id'] . '/edit') ?>" class="icon-btn" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="<?= url('admin/programs/' . (int) $p['id']) ?>" onsubmit="return confirmAction('Delete program <?= e(addslashes($p['code'])) ?>?', this);">
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
            'paginator' => $programs,
            'path'      => 'admin/programs',
            'query'     => $search !== '' ? ['search' => $search] : [],
        ]) ?>
    </div>
</div>
