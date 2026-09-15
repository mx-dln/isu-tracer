<?php
/**
 * Public (no-login) survey form. Sections render as navigable steps.
 * @var array $survey @var array $sections @var array|null $recipient @var array $old
 */
$sections = $sections ?? [];
$recipient = $recipient ?? null;
$old = $old ?? [];
$answers = $old['answers'] ?? [];
$totalSections = count($sections);
$hasQuestions = false;
foreach ($sections as $sec) {
    if (!empty($sec['questions'])) {
        $hasQuestions = true;
        break;
    }
}
?>
<div class="space-y-4" data-survey-wizard>

    <!-- Progress -->
    <div class="survey-card rounded-xl bg-white border border-ink-200 p-4">
        <div class="flex items-center justify-between text-xs text-ink-500 mb-2">
            <span id="progress-label">Section 1 of <?= max(1, $totalSections) ?></span>
            <span id="progress-percent">0%</span>
        </div>
        <div class="h-2 bg-ink-100 rounded-full overflow-hidden">
            <div id="progress-bar" class="h-full bg-brand-700 rounded-full transition-all duration-300" style="width:0%"></div>
        </div>
        <?php if ($totalSections > 1): ?>
            <div id="section-dots" class="flex items-center gap-1.5 mt-3 flex-wrap">
                <?php foreach ($sections as $idx => $sec): ?>
                    <span data-dot="<?= $idx ?>" class="w-2 h-2 rounded-full bg-ink-200"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Intro card -->
    <div class="survey-card rounded-xl bg-white border border-ink-200 overflow-hidden">
        <div class="p-6">
            <h1 class="text-xl font-bold text-ink-900"><?= e($survey['title']) ?></h1>
            <?php if (!empty($survey['description'])): ?>
                <p class="text-sm text-ink-500 mt-1.5 whitespace-pre-line"><?= e($survey['description']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-ink-400 mt-2">
                <?= e($survey['tracer_year'] ? 'Tracer Year ' . $survey['tracer_year'] : 'Isabela State University Graduate Tracer Study') ?>
            </p>
            <?php if ($recipient): ?>
                <p class="text-sm text-brand-700 mt-3">
                    <i data-lucide="user" class="w-4 h-4 inline-block -mt-0.5"></i>
                    <?= e($recipient['first_name'] . ' ' . $recipient['last_name']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$hasQuestions): ?>
        <div class="survey-card rounded-xl bg-white border border-ink-200 p-10 text-center">
            <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto text-ink-300 mb-3"></i>
            <p class="text-sm text-ink-500">This survey has no questions yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= url('survey/respond/' . rawurlencode((string) ($token ?? '')) . '/submit') ?>" id="survey-form" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <?php foreach ($sections as $idx => $section): ?>
                <section class="survey-step <?= $idx === 0 ? '' : 'hidden' ?>" data-step="<?= $idx ?>">
                    <div class="survey-card rounded-xl bg-white border border-ink-200">
                        <div class="px-6 pt-5 pb-4 border-b border-ink-100">
                            <h2 class="font-semibold text-ink-900"><?= e($section['title']) ?></h2>
                            <?php if (!empty($section['description'])): ?>
                                <p class="text-sm text-ink-500 mt-1"><?= e($section['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="p-6 space-y-6">
                            <?php foreach ($section['questions'] as $q): ?>
                                <div class="question-block" data-question>
                                    <label class="block font-medium text-ink-800 text-sm mb-1.5">
                                        <?= e($q['question_text']) ?>
                                        <?php if ($q['is_required']): ?><span class="text-red-600">*</span><?php endif; ?>
                                    </label>
                                    <?php if (!empty($q['help_text'])): ?>
                                        <p class="text-xs text-ink-500 mb-1.5"><?= e($q['help_text']) ?></p>
                                    <?php endif; ?>
                                    <?= \App\Core\View::partial('partials/_question_input', [
                                        'q' => $q,
                                        'name' => 'answers[' . (int) $q['id'] . ']',
                                        'value' => $answers[(int) $q['id']] ?? null,
                                    ]) ?>
                                    <p class="q-error hidden text-xs text-red-600 mt-1"></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>

            <!-- Nav bar -->
            <div class="survey-card rounded-xl bg-white border border-ink-200 p-4 flex items-center justify-between gap-3 sticky bottom-4">
                <button type="button" data-nav="prev" class="btn-secondary hidden">Back</button>
                <span class="flex-1"></span>
                <button type="button" data-nav="next" class="btn-primary">Next</button>
                <button type="submit" data-nav="submit" class="btn-success hidden">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit Response
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
