<?php
/** @var array|null $survey */
$s = $survey ?? [];
$isEdit = !empty($s);
?>
<div class="max-w-3xl">
    <div class="card">
        <form method="POST" action="<?= url('admin/surveys' . ($isEdit ? '/' . (int) $s['id'] : '')) ?>" class="p-6 space-y-5">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div>
                <label class="label" for="title">Survey Title <span class="text-red-600">*</span></label>
                <input type="text" name="title" id="title" class="input" maxlength="191" required
                       value="<?= e(old('title', $s['title'] ?? '')) ?>"
                       placeholder="e.g. IAT Graduate Tracer Study 2026">
            </div>
            <div>
                <label class="label" for="description">Description</label>
                <textarea name="description" id="description" rows="4" class="input"><?= e(old('description', $s['description'] ?? '')) ?></textarea>
                <p class="text-xs text-ink-500 mt-1">Shown to respondents at the top of the survey.</p>
            </div>
            <div>
                <label class="label" for="confirmation_message">Confirmation Message</label>
                <textarea name="confirmation_message" id="confirmation_message" rows="2" class="input"><?= e(old('confirmation_message', $s['confirmation_message'] ?? '')) ?></textarea>
                <p class="text-xs text-ink-500 mt-1">Shown after a respondent submits. Leave blank to use the default thank-you message.</p>
            </div>

            <div class="rounded-lg border border-ink-200 bg-ink-50/60 p-4 space-y-3">
                <p class="text-sm font-semibold text-ink-800">Access &amp; Delivery</p>
                <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                    <input type="checkbox" name="require_invitation" value="1" class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                           <?= old('require_invitation', $s['require_invitation'] ?? 1) ? 'checked' : '' ?>>
                    Require a secure one-time invitation link (no login needed)
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="invitation_expiry_days">Invitation expiry (days)</label>
                        <input type="number" name="invitation_expiry_days" id="invitation_expiry_days" class="input" min="1" max="365"
                               value="<?= e(old('invitation_expiry_days', $s['invitation_expiry_days'] ?? 30)) ?>">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="status">Status</label>
                    <select name="status" id="status" class="input">
                        <option value="draft" <?= old('status', $s['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="active" <?= old('status', $s['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="closed" <?= old('status', $s['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">Setting to Active opens the survey to graduates of the matching tracer year.</p>
                </div>
                <div>
                    <label class="label" for="tracer_year">Tracer Year</label>
                    <input type="number" name="tracer_year" id="tracer_year" class="input" min="1950" max="<?= (int) date('Y') + 1 ?>"
                           value="<?= e(old('tracer_year', $s['tracer_year'] ?? '')) ?>" placeholder="<?= (int) date('Y') ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="input"
                           value="<?= e(old('start_date', $s['start_date'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="input"
                           value="<?= e(old('end_date', $s['end_date'] ?? '')) ?>">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <a href="<?= url('admin/surveys') ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?= $isEdit ? 'Save Changes' : 'Create Survey' ?></button>
            </div>
        </form>
    </div>
    <?php if ($isEdit): ?>
        <p class="text-sm text-ink-500 mt-3">
            After saving, use the <a href="<?= url('admin/surveys/' . (int) $s['id']) ?>" class="text-brand-700 hover:underline">builder</a> to add sections and questions.
        </p>
    <?php endif; ?>
</div>