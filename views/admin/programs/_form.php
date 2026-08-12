<?php
/** @var array|null $program */
$p = $program ?? [];
$isEdit = !empty($p);
?>
<div class="max-w-2xl">
    <div class="card">
        <form method="POST" action="<?= url('admin/programs' . ($isEdit ? '/' . (int) $p['id'] : '')) ?>" class="p-6 space-y-5">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div>
                <label class="label" for="code">Program Code <span class="text-red-600">*</span></label>
                <input type="text" name="code" id="code" class="input" maxlength="20" required
                       value="<?= e(old('code', $p['code'] ?? '')) ?>" placeholder="e.g. BAT">
                <p class="text-xs text-ink-500 mt-1">Short code (unique), e.g. BAT, BSA, BSIT.</p>
            </div>
            <div>
                <label class="label" for="name">Program Name <span class="text-red-600">*</span></label>
                <input type="text" name="name" id="name" class="input" maxlength="191" required
                       value="<?= e(old('name', $p['name'] ?? '')) ?>"
                       placeholder="e.g. Bachelor of Agricultural Technology">
            </div>
            <div>
                <label class="label" for="description">Description</label>
                <textarea name="description" id="description" rows="4" class="input"><?= e(old('description', $p['description'] ?? '')) ?></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                       <?= old('is_active', $p['is_active'] ?? true) ? 'checked' : '' ?>>
                Active (available for new graduates)
            </label>

            <div class="flex items-center gap-2 pt-2">
                <a href="<?= url('admin/programs') ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?= $isEdit ? 'Save Changes' : 'Add Program' ?></button>
            </div>
        </form>
    </div>
</div>
