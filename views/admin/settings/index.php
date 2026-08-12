<?php
/** @var array $groups @var array $values */
$groups = $groups ?? [];
$values = $values ?? [];
?>
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-ink-900">Settings</h2>
        <p class="text-sm text-ink-500">System configuration</p>
    </div>

    <form method="POST" action="<?= url('admin/settings') ?>">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <?php foreach ($groups as $slug => $group): ?>
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-ink-800"><?= e($group['label']) ?></h3></div>
                    <div class="card-body space-y-4">
                        <?php foreach ($group['fields'] as $field): ?>
                            <?php $value = $values[$field['key']] ?? ''; ?>
                            <?php if ($field['type'] === 'checkbox'): ?>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="<?= e($field['key']) ?>" id="set-<?= e($field['key']) ?>" class="accent-brand-700 w-4 h-4" <?= $value === 'true' ? 'checked' : '' ?>>
                                    <label for="set-<?= e($field['key']) ?>" class="text-sm text-ink-700"><?= e($field['label']) ?></label>
                                </div>
                            <?php else: ?>
                                <div>
                                    <label class="label" for="set-<?= e($field['key']) ?>"><?= e($field['label']) ?></label>
                                    <input type="<?= $field['type'] === 'number' ? 'number' : 'text' ?>"
                                           name="<?= e($field['key']) ?>"
                                           id="set-<?= e($field['key']) ?>"
                                           class="input"
                                           value="<?= e((string) $value) ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Settings</button>
        </div>
    </form>
</div>
