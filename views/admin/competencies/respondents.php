<?php
/** @var array $responses @var array $filters @var array $programs @var array $batches @var array $categories */
$responses = $responses ?? [];
$filters = $filters ?? [];
$programs = $programs ?? [];
$batches = $batches ?? [];
$categories = $categories ?? [];
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Competency Respondents</h2>
            <p class="text-sm text-ink-500"><?= number_format((int) ($responses['total'] ?? 0)) ?> graduates answered the competency self-assessment</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/competencies/analysis') ?>" class="btn-secondary">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analysis
            </a>
            <a href="<?= url('admin/competencies/manage') ?>" class="btn-secondary">
                <i data-lucide="settings-2" class="w-4 h-4"></i> Manage Competencies
            </a>
        </div>
    </div>

    <form method="GET" action="<?= url('admin/competencies') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label" for="f-search">Search</label>
            <input type="search" name="search" id="f-search" class="input" placeholder="Name, student no., or email" value="<?= e($filters['search'] ?? '') ?>">
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
            <label class="label" for="f-category">Category</label>
            <select name="category_id" id="f-category" class="input !w-auto">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ($filters['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Filter</button>
        <a href="<?= url('admin/competencies') ?>" class="btn-secondary">Clear</a>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Graduate</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Answers</th>
                        <th>Average</th>
                        <th>First Submitted</th>
                        <th>Last Updated</th>
                        <th class="text-right">Profile</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($responses['items'])): ?>
                    <tr><td colspan="8" class="!text-center !py-10 text-ink-500">No competency respondents found.</td></tr>
                <?php endif; ?>
                <?php foreach ($responses['items'] as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/graduates/' . (int) $r['graduate_id']) ?>" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                                <?= e($r['last_name']) ?>, <?= e($r['first_name']) ?>
                            </a>
                            <p class="text-xs text-ink-400"><?= e($r['student_number']) ?> &middot; <?= e($r['email'] ?: 'No email') ?></p>
                        </td>
                        <td>
                            <span class="font-medium text-ink-800"><?= e($r['program_code']) ?></span>
                            <p class="text-xs text-ink-400"><?= e($r['program_name']) ?></p>
                        </td>
                        <td><?= (int) $r['batch_year'] ?></td>
                        <td><span class="badge-blue"><?= number_format((int) $r['response_count']) ?></span></td>
                        <td><span class="badge-green"><?= e((string) $r['average_rating']) ?>/5</span></td>
                        <td><?= $r['first_submitted_at'] ? date('M j, Y g:i A', strtotime($r['first_submitted_at'])) : '—' ?></td>
                        <td><?= $r['last_updated_at'] ? date('M j, Y g:i A', strtotime($r['last_updated_at'])) : '—' ?></td>
                        <td class="text-right">
                            <a href="<?= url('admin/graduates/' . (int) $r['graduate_id']) ?>" class="icon-btn" title="View graduate"><i data-lucide="eye" class="w-4 h-4"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $responses,
            'path'      => 'admin/competencies',
            'query'     => array_filter($filters, static fn ($v) => $v !== null && $v !== ''),
        ]) ?>
    </div>
</div>
