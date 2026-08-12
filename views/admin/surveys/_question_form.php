<?php
/** @var int $surveyId @var array $sections @var array|null $question @var string $action @var string $submit */
$question = $question ?? null;
$q = $question ?? [];
$isEdit = !empty($q);
$optionsText = $isEdit
    ? implode("\n", array_map(static fn ($o) => $o['option_text'], $q['options'] ?? []))
    : '';
$qid = (int) ($q['id'] ?? 0);
?>
<div class="<?= $isEdit ? 'bg-ink-50 rounded-lg border border-ink-200 p-4' : '' ?>">
    <form method="POST" action="<?= e($action) ?>" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="survey_id" value="<?= (int) $surveyId ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>

        <div class="md:col-span-6">
            <label class="label" for="q-<?= $qid ?>-text">Question Text <span class="text-red-600">*</span></label>
            <input type="text" name="question_text" id="q-<?= $qid ?>-text" class="input" required
                   value="<?= e(old('question_text', $q['question_text'] ?? '')) ?>">
        </div>
        <div class="md:col-span-3">
            <label class="label" for="q-<?= $qid ?>-section">Section</label>
            <select name="section_id" id="q-<?= $qid ?>-section" class="input">
                <option value="">— No section —</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= (int) $sec['id'] ?>" <?= (int) old('section_id', $q['section_id'] ?? 0) === (int) $sec['id'] ? 'selected' : '' ?>><?= e($sec['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="label" for="q-<?= $qid ?>-type">Type</label>
            <select name="type" id="q-<?= $qid ?>-type" class="input">
                <?php foreach (\App\Models\Survey::QUESTION_TYPES as $type): ?>
                    <option value="<?= $type ?>" <?= old('type', $q['type'] ?? 'text') === $type ? 'selected' : '' ?>><?= \App\Models\SurveyQuestion::typeLabel($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-6">
            <label class="label" for="q-<?= $qid ?>-key">Question Key (for analytics)</label>
            <input type="text" name="question_key" id="q-<?= $qid ?>-key" class="input" maxlength="100"
                   placeholder="e.g. employment_status"
                   value="<?= e(old('question_key', $q['question_key'] ?? '')) ?>">
        </div>
        <div class="md:col-span-3">
            <label class="label" for="q-<?= $qid ?>-order">Sort Order</label>
            <input type="number" name="sort_order" id="q-<?= $qid ?>-order" class="input"
                   value="<?= e(old('sort_order', $q['sort_order'] ?? 0)) ?>">
        </div>
        <div class="md:col-span-3 flex items-end">
            <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer pb-2.5">
                <input type="checkbox" name="is_required" value="1"
                       class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                       <?= old('is_required', $q['is_required'] ?? true) ? 'checked' : '' ?>>
                Required
            </label>
        </div>

        <div class="md:col-span-6">
            <label class="label" for="q-<?= $qid ?>-help">Help Text</label>
            <input type="text" name="help_text" id="q-<?= $qid ?>-help" class="input"
                   value="<?= e(old('help_text', $q['help_text'] ?? '')) ?>">
        </div>
        <div class="md:col-span-6">
            <label class="label" for="q-<?= $qid ?>-options">Options <span class="text-ink-400 font-normal">(one per line; for choice, dropdown &amp; likert)</span></label>
            <textarea name="options" id="q-<?= $qid ?>-options" rows="3" class="input"><?= e(old('options', $optionsText)) ?></textarea>
        </div>

        <div class="md:col-span-12 flex items-center justify-end gap-2">
            <?php if ($isEdit): ?>
                <a href="<?= url('admin/surveys/' . (int) $surveyId) ?>" class="btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?= e($submit) ?></button>
        </div>
    </form>
</div>
