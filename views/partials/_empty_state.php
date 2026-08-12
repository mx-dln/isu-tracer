<?php
/**
 * Reusable centered empty-state block.
 * Params: $icon, $title, $message, $actions (raw HTML string, optional).
 */
$icon = $icon ?? 'inbox';
$title = $title ?? 'Nothing here yet';
$message = $message ?? '';
$actions = $actions ?? '';
?>
<div class="empty-state">
    <div class="empty-icon">
        <i data-lucide="<?= e($icon) ?>" class="w-6 h-6"></i>
    </div>
    <p class="text-sm font-semibold text-ink-800"><?= e($title) ?></p>
    <?php if ($message !== ''): ?>
        <p class="text-sm text-ink-500 mt-1 max-w-md"><?= e($message) ?></p>
    <?php endif; ?>
    <?php if ($actions !== ''): ?>
        <div class="flex flex-wrap items-center justify-center gap-2 mt-4"><?= $actions ?></div>
    <?php endif; ?>
</div>