<?php
/** @var array $profiles @var array $filters @var array $programs @var array $batches @var array $sectors */
$profiles = $profiles ?? [];
$filters = $filters ?? [];
$programs = $programs ?? [];
$batches = $batches ?? [];
$sectors = $sectors ?? [];

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
            <h2 class="text-xl font-bold text-ink-900">Employment Profiles</h2>
            <p class="text-sm text-ink-500"><?= number_format($profiles['total'] ?? 0) ?> records found</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/employment-sectors') ?>" class="btn-secondary">
                <i data-lucide="layers" class="w-4 h-4"></i> Sectors
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('admin/employment') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Name, student no., job title, employer"
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
            <label class="label" for="f-status">Status</label>
            <select name="status" id="f-status" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach ($statusLabel as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="f-sector">Sector</label>
            <select name="sector_id" id="f-sector" class="input !w-auto">
                <option value="">Any</option>
                <?php foreach ($sectors as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($filters['sector_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Filter</button>
        <a href="<?= url('admin/employment') ?>" class="btn-secondary">Clear</a>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Graduate</th>
                        <th>Status</th>
                        <th>Job Title</th>
                        <th>Employer</th>
                        <th>Sector</th>
                        <th>Relevance</th>
                        <th>Time to 1st Job</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($profiles['items'])): ?>
                    <tr><td colspan="8" class="!text-center !py-10 text-ink-500">No employment profiles found.</td></tr>
                <?php endif; ?>
                <?php foreach ($profiles['items'] as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/graduates/' . (int) $p['graduate_id']) ?>" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                                <?= e($p['last_name']) ?>, <?= e($p['first_name']) ?>
                            </a>
                            <p class="text-xs text-ink-400"><?= e($p['student_number']) ?> &middot; <?= e($p['program_code']) ?> &middot; <?= (int) $p['batch_year'] ?></p>
                        </td>
                        <td>
                            <?php if (isset($statusLabel[$p['status']])): ?>
                                <span class="<?= $statusLabel[$p['status']][1] ?>"><?= $statusLabel[$p['status']][0] ?></span>
                            <?php else: ?>
                                <span class="badge-gray"><?= e($p['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['job_title'] ?? '—') ?></td>
                        <td><?= e($p['employer'] ?? '—') ?></td>
                        <td><?= e($p['sector_name'] ?? $p['sector_other'] ?? '—') ?></td>
                        <td>
                            <?php if ($p['job_relevance_rating'] !== null): ?>
                                <span class="badge-amber"><?= (int) $p['job_relevance_rating'] ?>/5</span>
                            <?php else: ?>
                                <span class="badge-gray">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['time_to_first_job_months'] !== null): ?>
                                <?= (int) $p['time_to_first_job_months'] ?> mo
                            <?php else: ?>
                                <span class="text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/employment/' . (int) $p['id']) ?>" class="icon-btn" title="View"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $profiles,
            'path'      => 'admin/employment',
            'query'     => array_filter($filters, static fn ($v) => $v !== null && $v !== ''),
        ]) ?>
    </div>
</div>
