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
        <form method="POST" action="<?= url('graduate/survey/' . (int) $survey['id'] . '/submit') ?>" class="space-y-6" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php foreach ($sections as $section): ?>
                <div class="card survey-section-card" data-section-title="<?= e(strtolower($section['title'])) ?>">
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
                            <div class="survey-question-item" data-question-id="<?= (int) $q['id'] ?>" data-question-key="<?= e(strtolower($q['question_key'] ?? '')) ?>">
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

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusInputs = document.querySelectorAll(
                '[data-question-key="employment_status"] select, [data-question-key="employment_status"] input'
            );

            function isUnemployedSelected() {
                for (const input of statusInputs) {
                    if (input.tagName === 'SELECT') {
                        const opt = input.options[input.selectedIndex];
                        const text = (opt ? opt.text : '').toLowerCase();
                        const val = (input.value || '').toLowerCase();
                        if (val.includes('unemployed') || text.includes('unemployed')) return true;
                        if (val !== '') return false;
                    } else if (input.type === 'radio' && input.checked) {
                        const label = input.closest('label')?.textContent?.toLowerCase() || '';
                        const val = (input.value || '').toLowerCase();
                        if (val.includes('unemployed') || label.includes('unemployed')) return true;
                        return false;
                    }
                }
                return false;
            }

            function toggleCompetencyQuestions() {
                const unemployed = isUnemployedSelected();

                // 1. Sections with "competenc" in title
                document.querySelectorAll('.survey-section-card').forEach(function (card) {
                    const title = (card.getAttribute('data-section-title') || '').toLowerCase();
                    if (title.includes('competenc')) {
                        card.style.display = unemployed ? 'none' : '';
                        card.querySelectorAll('input, select, textarea').forEach(function (el) {
                            if (unemployed) {
                                if (el.hasAttribute('required')) {
                                    el.dataset.wasRequired = '1';
                                    el.removeAttribute('required');
                                }
                            } else if (el.dataset.wasRequired === '1') {
                                el.setAttribute('required', 'required');
                            }
                        });
                    }
                });

                // 2. Individual questions with "competenc" or "skills_used" in key
                document.querySelectorAll('.survey-question-item').forEach(function (qItem) {
                    const key = (qItem.getAttribute('data-question-key') || '').toLowerCase();
                    if (key.includes('competenc') || key === 'skills_used') {
                        qItem.style.display = unemployed ? 'none' : '';
                        qItem.querySelectorAll('input, select, textarea').forEach(function (el) {
                            if (unemployed) {
                                if (el.hasAttribute('required')) {
                                    el.dataset.wasRequired = '1';
                                    el.removeAttribute('required');
                                }
                            } else if (el.dataset.wasRequired === '1') {
                                el.setAttribute('required', 'required');
                            }
                        });
                    }
                });
            }

            statusInputs.forEach(function (el) {
                el.addEventListener('change', toggleCompetencyQuestions);
            });

            // Initial check
            toggleCompetencyQuestions();
        });
        </script>
    <?php endif; ?>
</div>
