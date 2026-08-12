<?php
/** @var array $questions @var string $search @var array $analysis */
$questions = $questions ?? [];
$search = $search ?? '';
$analysis = $analysis ?? [];
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
        ]) ?>
    </div>
</div>
