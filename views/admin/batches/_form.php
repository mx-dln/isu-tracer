<?php
/** @var array|null $batch */
$b = $batch ?? [];
$isEdit = !empty($b);
?>
<div class="max-w-2xl">
    <div class="card">
        <form method="POST" action="<?= url('admin/batches' . ($isEdit ? '/' . (int) $b['id'] : '')) ?>" class="p-6 space-y-5">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div>
                <label class="label" for="year">Graduation Year <span class="text-red-600">*</span></label>
                <input type="number" name="year" id="year" class="input" min="1950" max="<?= (int) date('Y') + 1 ?>" required
                       value="<?= e(old('year', $b['year'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="label">Label (optional)</label>
                <input type="text" name="label" id="label" class="input" maxlength="100"
                       value="<?= e(old('label', $b['label'] ?? '')) ?>" placeholder="e.g. 2026 Intake">
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                       <?= old('is_active', $b['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>

            <div class="flex items-center gap-2 pt-2">
                <a href="<?= url('admin/batches') ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?= $isEdit ? 'Save Changes' : 'Add Batch' ?></button>
            </div>
        </form>
    </div>
</div>
