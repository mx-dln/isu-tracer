<?php
/** @var array $questions @var string $search @var array $analysis @var array $responses @var array $responseFilters @var array $programs @var array $batches @var array $allQuestions */
$questions = $questions ?? [];
$search = $search ?? '';
$analysis = $analysis ?? [];
$responses = $responses ?? [];
$responseFilters = $responseFilters ?? [];
$programs = $programs ?? [];
$batches = $batches ?? [];
$allQuestions = $allQuestions ?? [];
$analysisItems = $analysis['items'] ?? [];
$overall = $analysis['overall'] ?? null;
$interpretation = $analysis['interpretation'] ?? '';
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Curriculum Feedback</h2>
            <p class="text-sm text-ink-500">Curriculum questions and weighted feedback means (1–5)</p>
        </div>
    </div>

    <!-- Respondents -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800">Graduates Who Answered</h3>
                <p class="text-xs text-ink-500"><?= number_format((int) ($responses['total'] ?? 0)) ?> respondent<?= (int) ($responses['total'] ?? 0) === 1 ? '' : 's' ?> found</p>
            </div>
        </div>
        <form method="GET" action="<?= url('admin/curriculum-feedback') ?>" class="p-4 border-b border-ink-100 flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
                <label class="label" for="rf-search">Search</label>
                <input type="search" name="response_search" id="rf-search" class="input" placeholder="Name, student no., or email" value="<?= e($responseFilters['response_search'] ?? '') ?>">
            </div>
            <div>
                <label class="label" for="rf-program">Program</label>
                <select name="program_id" id="rf-program" class="input !w-auto">
                    <option value="">All programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($responseFilters['program_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="rf-batch">Batch</label>
                <select name="batch_id" id="rf-batch" class="input !w-auto">
                    <option value="">All batches</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= ($responseFilters['batch_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="rf-question">Question</label>
                <select name="question_id" id="rf-question" class="input !w-auto">
                    <option value="">All questions</option>
                    <?php foreach ($allQuestions as $q): ?>
                        <option value="<?= (int) $q['id'] ?>" <?= ($responseFilters['question_id'] ?? 0) == $q['id'] ? 'selected' : '' ?>><?= e(mb_substr($q['question_text'], 0, 70)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Filter</button>
            <a href="<?= url('admin/curriculum-feedback') ?>" class="btn-secondary">Clear</a>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Graduate</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Answers</th>
                        <th>Average</th>
                        <th>Comments</th>
                        <th>First Submitted</th>
                        <th>Last Updated</th>
                        <th class="text-right">Profile</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($responses['items'])): ?>
                    <tr><td colspan="9" class="!text-center !py-10 text-ink-500">No curriculum feedback respondents found.</td></tr>
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
                        <td><?= number_format((int) $r['comment_count']) ?></td>
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
            'path'      => 'admin/curriculum-feedback',
            'query'     => array_filter($responseFilters, static fn ($v) => $v !== null && $v !== ''),
            'page_param'=> 'responses_page',
        ]) ?>
    </div>

    <!-- Overall -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Overall Curriculum Mean</h3></div>
        <div class="card-body">
            <div class="flex items-center gap-4">
                <p class="text-4xl font-bold text-brand-700"><?= $overall !== null ? $overall : '—' ?><span class="text-lg text-ink-400">/5</span></p>
                <?php if ($interpretation): ?>
                    <p class="text-sm text-ink-600"><?= e($interpretation) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Weighted means table -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Question Weighted Means</h3></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Responses</th>
                        <th>Weighted Mean</th>
                        <th>Interpretation</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($analysisItems)): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No curriculum feedback yet.</td></tr>
                <?php endif; ?>
                <?php $i = 0; foreach ($analysisItems as $item): $i++; ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td class="font-medium text-ink-800"><?= e($item['question']) ?></td>
                        <td><?= number_format((int) $item['responses']) ?></td>
                        <td>
                            <?php if ($item['score'] !== null): ?>
                                <span class="badge-blue"><?= $item['score'] ?>/5</span>
                            <?php else: ?>
                                <span class="badge-gray">No data</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-ink-600"><?= e($item['interpretation'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add question -->
    <form method="POST" action="<?= url('admin/curriculum-feedback') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <?= csrf_field() ?>
        <div class="min-w-[320px] flex-1">
            <label class="label" for="new-q">Question Text</label>
            <input type="text" name="question_text" id="new-q" class="input" placeholder="e.g. Relevance of the curriculum to current industry needs" required>
        </div>
        <div class="min-w-[160px]">
            <label class="label" for="new-sort">Sort Order</label>
            <input type="number" name="sort_order" id="new-sort" class="input" value="0">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-ink-700 pb-1">
            <input type="checkbox" name="is_active" value="1" class="rounded" checked> Active
        </label>
        <button type="submit" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Question</button>
    </form>

    <!-- Questions table -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Curriculum Questions</h3>
            <form method="GET" action="<?= url('admin/curriculum-feedback') ?>" class="flex items-center gap-2">
                <input type="search" name="search" class="input !w-56 !py-1.5" placeholder="Search question" value="<?= e($search) ?>">
                <button type="submit" class="btn-secondary !py-1.5">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Feedback</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($questions['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No questions found.</td></tr>
                <?php endif; ?>
                <?php foreach ($questions['items'] as $q): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($q['question_text']) ?></td>
                        <td><?= number_format((int) $q['feedback_count']) ?></td>
                        <td><?= (int) $q['sort_order'] ?></td>
                        <td>
                            <?php if ($q['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="<?= url('admin/curriculum-feedback/' . (int) $q['id']) ?>" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="text" name="question_text" value="<?= e($q['question_text']) ?>" class="input !w-80 !py-1.5" required>
                                    <input type="number" name="sort_order" value="<?= (int) $q['sort_order'] ?>" class="input !w-20 !py-1.5">
                                    <label class="inline-flex items-center gap-1 text-xs text-ink-600">
                                        <input type="checkbox" name="is_active" value="1" <?= $q['is_active'] ? 'checked' : '' ?> class="rounded"> Active
                                    </label>
                                    <button type="submit" class="icon-btn" title="Save"><i data-lucide="save" class="w-4 h-4"></i></button>
                                </form>
                                <form method="POST" action="<?= url('admin/curriculum-feedback/' . (int) $q['id']) ?>" onsubmit="return confirmAction('Remove curriculum question?', this);">
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
            'paginator' => $questions,
            'path'      => 'admin/curriculum-feedback',
            'query'     => ['search' => $search],
            'page_param'=> 'page',
        ]) ?>
    </div>
</div>
