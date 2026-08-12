<?php
/** @var array $survey @var array $sections @var array $questions @var array $old */
$sections = $sections ?? [];
$questions = $questions ?? [];
$old = $old ?? [];
$answers = $old['answers'] ?? [];
$scripts = '<script src="' . asset('js/public-survey.js') . '"></script>';
?>
<div class="space-y-6 max-w-3xl">

    <div class="card">
        <div class="card-body text-center">
            <h2 class="text-xl font-bold text-ink-900"><?= e($survey['title']) ?></h2>
            <?php if (!empty($survey['description'])): ?>
                <p class="text-sm text-ink-500 mt-1 max-w-xl mx-auto"><?= e($survey['description']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-ink-400 mt-2">
                <?= count($sections) ?> section<?= count($sections) === 1 ? '' : 's' ?> &middot; Responses are recorded once submitted.
            </p>
        </div>
    </div>

    <?php if (empty($sections) || empty($questions)): ?>
        <div class="card p-10 text-center">
            <p class="text-sm text-ink-500">This survey has no questions yet. Please check again later.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= url('graduate/survey/' . (int) $survey['id'] . '/submit') ?>" class="space-y-6">
            <?= csrf_field() ?>
            <?php foreach ($sections as $section): ?>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="font-semibold text-ink-800"><?= e($section['title']) ?></h3>
                            <?php if (!empty($section['description'])): ?>
                                <p class="text-xs text-ink-500 mt-0.5"><?= e($section['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body space-y-5">
                        <?php foreach ($section['questions'] as $q): ?>
                            <div>
                                <label class="block font-medium text-ink-800 text-sm mb-1.5">
                                    <?= e($q['question_text']) ?>
                                    <?php if ($q['is_required']): ?><span class="text-red-600">*</span><?php endif; ?>
                                </label>
                                <?php if ($q['help_text']): ?>
                                    <p class="text-xs text-ink-500 mb-1.5"><?= e($q['help_text']) ?></p>
                                <?php endif; ?>
                                <?= \App\Core\View::partial('partials/_question_input', [
                                    'q' => $q,
                                    'name' => 'answers[' . (int) $q['id'] . ']',
                                    'value' => $answers[(int) $q['id']] ?? null,
                                ]) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="card p-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-500">Review your answers before submitting. You can only submit once.</p>
                <div class="flex items-center gap-2">
                    <a href="<?= url('graduate/survey') ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary"><i data-lucide="send" class="w-4 h-4"></i> Submit Response</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
