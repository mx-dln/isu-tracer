<?php
/** @var array $graduates @var array $programs @var array $batches @var array $filters @var array $statuses */
$graduates = $graduates ?? [];
$filters = $filters ?? [];
$programs = $programs ?? [];
$batches = $batches ?? [];
$statuses = $statuses ?? [];
$exportQuery = http_build_query(array_filter($filters, static fn ($v) => $v !== null && $v !== ''));
$exportQuery = $exportQuery ? '?' . $exportQuery : '';

$statusLabel = [
    'employed'       => ['Employed', 'badge-green'],
    'self_employed'  => ['Self-Employed', 'badge-blue'],
    'unemployed'     => ['Unemployed', 'badge-red'],
    'further_studies'=> ['Further Studies', 'badge-amber'],
];
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Graduates</h2>
            <p class="text-sm text-ink-500"><?= number_format($graduates['total'] ?? 0) ?> records found</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/graduates/export' . $exportQuery) ?>" class="btn-secondary">
                <i data-lucide="download" class="w-4 h-4"></i> CSV
            </a>
            <a href="<?= url('admin/graduates/export?format=xlsx' . ($exportQuery ? '&' . ltrim($exportQuery, '?') : '')) ?>" class="btn-secondary">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Excel
            </a>
            <a href="<?= url('admin/graduates/import') ?>" class="btn-secondary">
                <i data-lucide="upload" class="w-4 h-4"></i> Import
            </a>
            <a href="<?= url('admin/graduates/create') ?>" class="btn-primary">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Add Graduate
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('admin/graduates') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Name, student no., email"
                   value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div>
            <label class="label" for="f-program">Program</label>
            <select name="program_id" id="f-program" class="input !w-auto">
                <option value="">All programs</option>
                <?php foreach ($programs as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($filters['program_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-batch">Batch</label>
            <select name="batch_id" id="f-batch" class="input !w-auto">
                <option value="">All batches</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= ($filters['batch_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-status">Employment</label>
            <select name="status" id="f-status" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-validated">Validated</label>
            <select name="validated" id="f-validated" class="input !w-auto">
                <option value="">Any</option>
                <option value="1" <?= ($filters['validated'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= ($filters['validated'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Filter</button>
        <a href="<?= url('admin/graduates') ?>" class="btn-secondary">Clear</a>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Contact</th>
                        <th>Employment</th>
                        <th>Validated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($graduates['items'])): ?>
                    <tr><td colspan="8" class="!text-center !py-10 text-ink-500">No graduates found.</td></tr>
                <?php endif; ?>
                <?php foreach ($graduates['items'] as $g): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($g['student_number']) ?></td>
                        <td>
                            <a href="<?= url('admin/graduates/' . (int) $g['id']) ?>" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                                <?= e($g['last_name']) ?>, <?= e($g['first_name']) ?><?= !empty($g['suffix']) ? ' ' . e($g['suffix']) : '' ?>
                            </a>
                        </td>
                        <td><?= e($g['program_code']) ?></td>
                        <td><?= (int) $g['batch_year'] ?></td>
                        <td class="!whitespace-normal max-w-[180px]">
                            <?php if ($g['email']): ?><p class="truncate text-ink-600"><?= e($g['email']) ?></p><?php endif; ?>
                            <?php if ($g['contact_number']): ?><p class="text-xs text-ink-400"><?= e($g['contact_number']) ?></p><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($g['employment_status'] && isset($statusLabel[$g['employment_status']])): ?>
                                <span class="<?= $statusLabel[$g['employment_status']][1] ?>"><?= $statusLabel[$g['employment_status']][0] ?></span>
                            <?php else: ?>
                                <span class="badge-gray">No Data</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($g['is_validated']): ?>
                                <span class="badge-green">Validated</span>
                            <?php else: ?>
                                <span class="badge-amber">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/graduates/' . (int) $g['id']) ?>" class="icon-btn" title="View"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                <a href="<?= url('admin/graduates/' . (int) $g['id'] . '/edit') ?>" class="icon-btn" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="<?= url('admin/graduates/' . (int) $g['id']) ?>" onsubmit="return confirmAction('Delete graduate <?= e(addslashes($g['student_number'])) ?>? This cannot be undone.', this);">
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
            'paginator' => $graduates,
            'path'      => 'admin/graduates',
            'query'     => array_filter($filters, static fn ($v) => $v !== null && $v !== ''),
        ]) ?>
    </div>
</div>
