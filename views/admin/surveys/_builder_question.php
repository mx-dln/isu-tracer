<?php
/**
 * A single builder question card (new or existing) — consistent editor grid.
 *
 * Common field order for EVERY question type:
 *   Question → Type + Section → Type options → Question key + Help text → Required → Actions
 *
 * @var array|null $q @var int $surveyId @var array $sections
 */
$q = $q ?? null;
$surveyId = (int) ($surveyId ?? 0);
$sections = $sections ?? [];
$isEdit = !empty($q);
$qid = (int) ($q['id'] ?? 0);
$valid = $q ? \App\Models\SurveyQuestion::decodeValidation($q['validation'] ?? null) : [];
$options = $q ? ($q['options'] ?? []) : [];
$type = $q['type'] ?? 'text';
$action = $isEdit ? url('admin/surveys/questions/' . $qid) : url('admin/surveys/questions');
$allTypes = \App\Models\Survey::QUESTION_TYPES;
$noOptionTypes = ['long_text', 'date', 'time'];
?>
<div class="builder-question card mb-4" data-question-id="<?= $qid ?>">
    <div class="flex items-stretch">
        <div class="question-drag-handle drag-handle flex items-center justify-center w-11 shrink-0 cursor-grab text-ink-300 hover:text-ink-500 border-r border-ink-100" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
            <i data-lucide="grip-vertical" class="w-4 h-4"></i>
        </div>

        <form method="POST" action="<?= e($action) ?>" class="flex-1 px-5 py-4 space-y-4" data-question-form enctype="application/x-www-form-urlencoded">
            <?= csrf_field() ?>
            <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <!-- Row 1 — Question + Type + Section (always aligned on the same row on desktop) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-start">
                <div class="sm:col-span-2 lg:col-span-7">
                    <label class="label" for="bq-<?= $qid ?>-text">Question</label>
                    <textarea name="question_text" id="bq-<?= $qid ?>-text" rows="1"
                        class="input min-h-[44px] !font-medium !text-ink-900 resize-none"
                        placeholder="Question text"
                        oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"><?= e($q['question_text'] ?? '') ?></textarea>
                </div>
                <div class="lg:col-span-2">
                    <label class="label" for="bq-<?= $qid ?>-type">Question type</label>
                    <select name="type" id="bq-<?= $qid ?>-type" class="input h-10" data-type-select>
                        <?php foreach ($allTypes as $t): ?>
                            <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e(\App\Models\SurveyQuestion::typeLabel($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="label" for="bq-<?= $qid ?>-section">Section</label>
                    <select name="section_id" id="bq-<?= $qid ?>-section" class="input h-10" data-qsection>
                        <option value="">— None —</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= (int) $sec['id'] ?>" <?= (int) ($q['section_id'] ?? 0) === (int) $sec['id'] ? 'selected' : '' ?>><?= e($sec['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2 — Type-specific options (subtle, dedicated container) -->
            <div class="rounded-lg border border-ink-200 bg-ink-50/60 p-4 space-y-4" data-type-settings>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Type options</p>

                <div class="panel-options" data-panel="single_choice|multiple_choice|dropdown|likert" style="<?= in_array($type, ['single_choice', 'multiple_choice', 'dropdown', 'likert'], true) ? '' : 'display:none' ?>">
                    <label class="label">Answer Options</label>
                    <div class="space-y-2" data-option-list>
                        <?php if ($options): ?>
                            <?php foreach ($options as $o): ?>
                                <div class="flex items-center gap-2" data-option-row>
                                    <i data-lucide="circle-dot" class="w-4 h-4 text-ink-300 shrink-0"></i>
                                    <input type="text" name="options[]" class="input h-10" value="<?= e($o['option_text']) ?>" placeholder="Option <?= (int) $o['sort_order'] ?>">
                                    <button type="button" class="icon-btn !text-red-500 hover:!bg-red-50" data-remove-option title="Remove option" aria-label="Remove option"><i data-lucide="x" class="w-4 h-4"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = 1; $i <= 2; $i++): ?>
                                <div class="flex items-center gap-2" data-option-row>
                                    <i data-lucide="circle-dot" class="w-4 h-4 text-ink-300 shrink-0"></i>
                                    <input type="text" name="options[]" class="input h-10" value="" placeholder="Option <?= $i ?>">
                                    <button type="button" class="icon-btn !text-red-500 hover:!bg-red-50" data-remove-option title="Remove option" aria-label="Remove option"><i data-lucide="x" class="w-4 h-4"></i></button>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-secondary mt-2 !py-1.5 !px-3" data-add-option><i data-lucide="plus" class="w-4 h-4"></i> Add option</button>
                </div>

                <div class="panel-scale" data-panel="linear_scale" style="<?= $type === 'linear_scale' ? '' : 'display:none' ?>">
                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-scale-min">Scale From</label>
                            <input type="number" name="scale_min" id="bq-<?= $qid ?>-scale-min" class="input h-10" value="<?= e($valid['scale_min'] ?? 0) ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-scale-max">Scale To</label>
                            <input type="number" name="scale_max" id="bq-<?= $qid ?>-scale-max" class="input h-10" value="<?= e($valid['scale_max'] ?? 10) ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-min-label">Low Label</label>
                            <input type="text" name="min_label" id="bq-<?= $qid ?>-min-label" class="input h-10" value="<?= e($valid['min_label'] ?? '') ?>" placeholder="e.g. Not at all">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-max-label">High Label</label>
                            <input type="text" name="max_label" id="bq-<?= $qid ?>-max-label" class="input h-10" value="<?= e($valid['max_label'] ?? '') ?>" placeholder="e.g. Very much">
                        </div>
                    </div>
                </div>

                <div class="panel-rating" data-panel="rating" style="<?= $type === 'rating' ? '' : 'display:none' ?>">
                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-stars">Maximum Rating</label>
                            <select name="stars" id="bq-<?= $qid ?>-stars" class="input h-10">
                                <?php foreach ([3, 5, 7, 10] as $n): ?>
                                    <option value="<?= $n ?>" <?= (int) ($valid['stars'] ?? 5) === $n ? 'selected' : '' ?>><?= $n ?> stars</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="panel-number" data-panel="number" style="<?= $type === 'number' ? '' : 'display:none' ?>">
                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-num-min">Minimum</label>
                            <input type="number" step="any" name="min" id="bq-<?= $qid ?>-num-min" class="input h-10" value="<?= e($valid['min'] ?? '') ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-num-max">Maximum</label>
                            <input type="number" step="any" name="max" id="bq-<?= $qid ?>-num-max" class="input h-10" value="<?= e($valid['max'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="panel-text" data-panel="text" style="<?= $type === 'text' ? '' : 'display:none' ?>">
                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-maxlen">Maximum Length</label>
                            <input type="number" name="max_length" id="bq-<?= $qid ?>-maxlen" class="input h-10" value="<?= e($valid['max_length'] ?? '') ?>" placeholder="Leave blank for none">
                        </div>
                        <div class="lg:col-span-9 pb-1">
                            <label class="inline-flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                                <input type="checkbox" name="email" value="1" class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500" <?= !empty($valid['email']) ? 'checked' : '' ?>>
                                Require valid email
                            </label>
                        </div>
                    </div>
                </div>

                <div class="panel-multi" data-panel="multiple_choice" style="<?= $type === 'multiple_choice' ? '' : 'display:none' ?>">
                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-min-sel">Min Selections</label>
                            <input type="number" name="min_selections" id="bq-<?= $qid ?>-min-sel" class="input h-10" value="<?= e($valid['min_selections'] ?? 1) ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label" for="bq-<?= $qid ?>-max-sel">Max Selections</label>
                            <input type="number" name="max_selections" id="bq-<?= $qid ?>-max-sel" class="input h-10" value="<?= e($valid['max_selections'] ?? '') ?>" placeholder="None">
                        </div>
                    </div>
                </div>

                <div class="panel-yesno" data-panel="yes_no" style="<?= $type === 'yes_no' ? '' : 'display:none' ?>">
                    <p class="text-sm text-ink-500">Respondents choose either <strong>Yes</strong> or <strong>No</strong>. These options are fixed.</p>
                </div>

                <div class="panel-empty text-sm text-ink-500" data-panel-empty style="<?= in_array($type, $noOptionTypes, true) ? '' : 'display:none' ?>">
                    No additional settings for this question type.
                </div>
            </div>

            <!-- Row 3 — Question key + Help text + Required (consistent positions) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <div class="lg:col-span-5">
                    <label class="label" for="bq-<?= $qid ?>-key">Question key (analytics)</label>
                    <input type="text" name="question_key" id="bq-<?= $qid ?>-key" class="input h-10 font-mono" maxlength="100" placeholder="auto-generated" value="<?= e($q['question_key'] ?? '') ?>">
                </div>
                <div class="lg:col-span-5">
                    <label class="label" for="bq-<?= $qid ?>-help">Help text</label>
                    <input type="text" name="help_text" id="bq-<?= $qid ?>-help" class="input h-10" value="<?= e($q['help_text'] ?? '') ?>" placeholder="Optional guidance">
                </div>
                <div class="lg:col-span-2 flex items-end justify-end pb-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_required" value="1" class="sr-only peer" <?= ($q['is_required'] ?? true) ? 'checked' : '' ?>>
                        <span class="relative inline-flex w-9 h-5 rounded-full bg-ink-300 peer-checked:bg-brand-600 transition-colors after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:after:translate-x-4" aria-hidden="true"></span>
                        <span class="text-sm text-ink-700">Required</span>
                    </label>
                </div>
            </div>

            <!-- Row 4 — Consistent action footer -->
            <div class="flex items-center justify-end gap-2 border-t border-ink-100 pt-3">
                <?php if ($isEdit): ?>
                    <a href="<?= url('admin/surveys/' . $surveyId) ?>" class="btn-secondary"><i data-lucide="x" class="w-4 h-4"></i> Cancel</a>
                    <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save changes</button>
                <?php else: ?>
                    <button type="button" class="btn-secondary" data-clear-question><i data-lucide="x" class="w-4 h-4"></i> Cancel</button>
                    <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save question</button>
                <?php endif; ?>
            </div>
        </form>

        <div class="flex flex-col items-center justify-center gap-1 w-14 shrink-0 border-l border-ink-100 px-1">
            <?php if ($isEdit): ?>
                <form method="POST" action="<?= url('admin/surveys/questions/' . $qid . '/duplicate') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-btn" title="Duplicate question" aria-label="Duplicate question"><i data-lucide="copy" class="w-4 h-4"></i></button>
                </form>
                <form method="POST" action="<?= url('admin/surveys/questions/' . $qid) ?>" onsubmit="return confirmAction('Delete this question?', this);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete question" aria-label="Delete question"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
