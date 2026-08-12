<?php
/** @var array $survey @var array $sections */
$sections = $sections ?? [];
?>
<div class="space-y-4">
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 flex items-center gap-2 text-sm text-amber-800">
        <i data-lucide="eye" class="w-4 h-4"></i>
        Preview &mdash; this is how respondents will see your survey.
        <a href="<?= url('admin/surveys/' . (int) $survey['id']) ?>" class="ml-auto font-semibold text-amber-900 hover:underline">Back to builder</a>
    </div>

    <div class="survey-card rounded-xl bg-white border border-ink-200 overflow-hidden">
        <div class="p-6">
            <h1 class="text-xl font-bold text-ink-900"><?= e($survey['title']) ?></h1>
            <?php if (!empty($survey['description'])): ?>
                <p class="text-sm text-ink-500 mt-1.5 whitespace-pre-line"><?= e($survey['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="survey-card rounded-xl bg-white border border-ink-200">
            <div class="px-6 pt-5 pb-4 border-b border-ink-100">
                <h2 class="font-semibold text-ink-900"><?= e($section['title']) ?></h2>
                <?php if (!empty($section['description'])): ?>
                    <p class="text-sm text-ink-500 mt-1"><?= e($section['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="p-6 space-y-6">
                <?php foreach ($section['questions'] as $q): ?>
                    <div class="opacity-70 pointer-events-none">
                        <label class="block font-medium text-ink-800 text-sm mb-1.5">
                            <?= e($q['question_text']) ?>
                            <?php if ($q['is_required']): ?><span class="text-red-600">*</span><?php endif; ?>
                        </label>
                        <?php if (!empty($q['help_text'])): ?>
                            <p class="text-xs text-ink-500 mb-1.5"><?= e($q['help_text']) ?></p>
                        <?php endif; ?>
                        <?= \App\Core\View::partial('partials/_question_input', [
                            'q' => $q,
                            'name' => 'preview_' . (int) $q['id'],
                        ]) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>