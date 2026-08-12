<?php
/** @var array $survey @var array $sections @var array $questions @var array $responses @var int $responseCount */
$survey = $survey ?? [];
$sections = $sections ?? [];
$questions = $questions ?? [];
$responses = $responses ?? ['items' => [], 'total' => 0, 'last_page' => 1, 'page' => 1];
$responseCount = $responseCount ?? 0;
$questionCount = count($questions);

$statusBadge = [
    'draft'  => ['Draft', 'badge-gray'],
    'active' => ['Active', 'badge-green'],
    'closed' => ['Closed', 'badge-red'],
];
$typeBadge = [
    'text' => ['Short Text', 'badge-gray'],
    'long_text' => ['Paragraph', 'badge-blue'],
    'number' => ['Number', 'badge-amber'],
    'date' => ['Date', 'badge-purple'],
    'single_choice' => ['Single Choice', 'badge-green'],
    'multiple_choice' => ['Multiple Choice', 'badge-green'],
    'dropdown' => ['Dropdown', 'badge-blue'],
    'likert' => ['Likert', 'badge-purple'],
    'yes_no' => ['Yes / No', 'badge-amber'],
];
[$statusLabel, $statusCls] = $statusBadge[$survey['status'] ?? 'draft'] ?? ['Unknown', 'badge-gray'];
?>
<div class="space-y-6">

    <!-- Survey header -->
    <div class="card">
        <div class="card-header">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-bold text-ink-900"><?= e($survey['title']) ?></h2>
                    <span class="<?= $statusCls ?>"><?= $statusLabel ?></span>
                </div>
                <?php if (!empty($survey['description'])): ?>
                    <p class="text-sm text-ink-500 mt-1"><?= e($survey['description']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-ink-400 mt-1">
                    Tracer year <?= e($survey['tracer_year'] ?: '—') ?> &middot;
                    <?= e($survey['start_date'] ?: '—') ?> &rarr; <?= e($survey['end_date'] ?: '—') ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= url('admin/surveys/' . (int) $survey['id'] . '/edit') ?>" class="btn-secondary"><i data-lucide="pencil" class="w-4 h-4"></i> Edit</a>
                <?php if ($survey['status'] === 'draft'): ?>
                    <form method="POST" action="<?= url('admin/surveys/' . (int) $survey['id'] . '/activate') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-success"><i data-lucide="play" class="w-4 h-4"></i> Activate</button>
                    </form>
                <?php elseif ($survey['status'] === 'active'): ?>
                    <form method="POST" action="<?= url('admin/surveys/' . (int) $survey['id'] . '/close') ?>" onsubmit="return confirm('Close this survey? Graduates can no longer submit responses.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-secondary"><i data-lucide="square" class="w-4 h-4"></i> Close</button>
                    </form>
                <?php endif; ?>
                <a href="<?= url('admin/surveys') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
            </div>
        </div>
        <div class="card-body grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-ink-900"><?= count($sections) ?></p>
                <p class="text-xs text-ink-500">Sections</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-ink-900"><?= number_format($questionCount) ?></p>
                <p class="text-xs text-ink-500">Questions</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-ink-900"><?= number_format($responseCount) ?></p>
                <p class="text-xs text-ink-500">Responses</p>
            </div>
        </div>
    </div>

    <!-- Structure -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-ink-800">Survey Structure</h3>
            <details class="relative">
                <summary class="btn-secondary cursor-pointer list-none"><i data-lucide="plus" class="w-4 h-4"></i> Add Section</summary>
                <div class="card absolute right-0 z-20 mt-2 w-96">
                    <form method="POST" action="<?= url('admin/surveys/sections') ?>" class="p-5 space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="survey_id" value="<?= (int) $survey['id'] ?>">
                        <div>
                            <label class="label" for="sec-title">Section Title <span class="text-red-600">*</span></label>
                            <input type="text" name="title" id="sec-title" class="input" maxlength="191" required placeholder="e.g. Employment Profile">
                        </div>
                        <div>
                            <label class="label" for="sec-desc">Description</label>
                            <textarea name="description" id="sec-desc" rows="2" class="input"></textarea>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Add Section</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>

        <?php if (empty($sections)): ?>
            <div class="card p-10 text-center">
                <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto text-ink-300 mb-3"></i>
                <p class="text-sm text-ink-500">No sections yet. Add a section to start building the survey.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($sections as $index => $section): ?>
            <div class="card mb-4 overflow-visible">
                <div class="card-header">
                    <div>
                        <h4 class="font-semibold text-ink-800"><?= e($section['title']) ?></h4>
                        <?php if (!empty($section['description'])): ?>
                            <p class="text-xs text-ink-500 mt-0.5"><?= e($section['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <details class="relative">
                            <summary class="icon-btn cursor-pointer list-none" title="Edit section"><i data-lucide="pencil" class="w-4 h-4"></i></summary>
                            <div class="card absolute right-0 z-20 mt-2 w-96">
                                <form method="POST" action="<?= url('admin/surveys/sections/' . (int) $section['id']) ?>" class="p-5 space-y-4">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <div>
                                        <label class="label" for="sec-<?= (int) $section['id'] ?>-title">Title <span class="text-red-600">*</span></label>
                                        <input type="text" name="title" id="sec-<?= (int) $section['id'] ?>-title" class="input" required value="<?= e($section['title']) ?>">
                                    </div>
                                    <div>
                                        <label class="label" for="sec-<?= (int) $section['id'] ?>-desc">Description</label>
                                        <textarea name="description" id="sec-<?= (int) $section['id'] ?>-desc" rows="2" class="input"><?= e($section['description'] ?? '') ?></textarea>
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save</button>
                                    </div>
                                </form>
                            </div>
                        </details>
                        <form method="POST" action="<?= url('admin/surveys/sections/' . (int) $section['id']) ?>" onsubmit="return confirmAction('Delete this section and all of its questions?', this);">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete section"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                    </div>
                </div>

                <?php if (empty($section['questions'])): ?>
                    <div class="card-body text-sm text-ink-500">No questions in this section.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Options</th>
                                    <th>Req.</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($section['questions'] as $q): ?>
                                <?php [$qlabel, $qcls] = $typeBadge[$q['type']] ?? [$q['type'], 'badge-gray']; ?>
                                <tr>
                                    <td class="text-ink-400"><?= (int) $q['sort_order'] ?: ($index + 1) ?></td>
                                    <td class="!whitespace-normal min-w-[240px]">
                                        <p class="font-medium text-ink-800"><?= e($q['question_text']) ?></p>
                                        <?php if ($q['question_key']): ?><p class="text-xs text-ink-400 font-mono">key: <?= e($q['question_key']) ?></p><?php endif; ?>
                                        <?php if ($q['help_text']): ?><p class="text-xs text-ink-500"><?= e($q['help_text']) ?></p><?php endif; ?>
                                    </td>
                                    <td><span class="<?= $qcls ?>"><?= $qlabel ?></span></td>
                                    <td class="!whitespace-normal max-w-[220px] text-xs text-ink-600">
                                        <?php if ($q['options']): ?>
                                            <?= e(implode(' · ', array_map(static fn ($o) => $o['option_text'], $q['options']))) ?>
                                        <?php else: ?>
                                            <span class="text-ink-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $q['is_required'] ? '<span class="badge-red">Yes</span>' : '<span class="badge-gray">No</span>' ?></td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <details class="relative">
                                                <summary class="icon-btn cursor-pointer list-none" title="Edit question"><i data-lucide="pencil" class="w-4 h-4"></i></summary>
                                                <div class="card absolute right-0 z-20 mt-2 w-[560px] max-w-[calc(100vw-2rem)]">
                                                    <div class="p-5">
                                                        <p class="font-semibold text-ink-800 mb-3">Edit Question</p>
                                                        <?= \App\Core\View::partial('admin/surveys/_question_form', [
                                                            'surveyId' => (int) $survey['id'],
                                                            'sections' => $sections,
                                                            'question' => $q,
                                                            'action'   => url('admin/surveys/questions/' . (int) $q['id']),
                                                            'submit'   => 'Save Question',
                                                        ]) ?>
                                                    </div>
                                                </div>
                                            </details>
                                            <form method="POST" action="<?= url('admin/surveys/questions/' . (int) $q['id']) ?>" onsubmit="return confirmAction('Delete this question?', this);">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete question"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Add question -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Add Question</h3></div>
        <div class="card-body">
            <?= \App\Core\View::partial('admin/surveys/_question_form', [
                'surveyId' => (int) $survey['id'],
                'sections' => $sections,
                'question' => null,
                'action'   => url('admin/surveys/questions'),
                'submit'   => 'Add Question',
            ]) ?>
        </div>
    </div>

    <!-- Responses -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Responses</h3>
            <span class="badge-blue"><?= number_format($responseCount) ?> submitted</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Submitted At</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($responses['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No responses submitted yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($responses['items'] as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/graduates/' . (int) $r['graduate_id']) ?>" class="font-medium text-brand-700 hover:underline">
                                <?= e($r['last_name']) ?>, <?= e($r['first_name']) ?>
                            </a>
                            <p class="text-xs text-ink-400"><?= e($r['student_number']) ?></p>
                        </td>
                        <td><?= e($r['program_code']) ?></td>
                        <td><?= (int) $r['batch_year'] ?></td>
                        <td><?= e($r['submitted_at']) ?></td>
                        <td class="text-xs text-ink-500"><?= e($r['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $responses,
            'path'      => 'admin/surveys/' . (int) $survey['id'],
            'query'     => [],
        ]) ?>
    </div>
</div>
