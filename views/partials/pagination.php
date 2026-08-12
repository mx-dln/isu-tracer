<?php
/**
 * Pagination links partial.
 * Expects: $paginator (array), $path (route path), $query (preserved GET params).
 */
$paginator = $paginator ?? [];
$path = $path ?? '';
$query = $query ?? [];
$page = max(1, (int) ($paginator['page'] ?? 1));
$lastPage = max(1, (int) ($paginator['last_page'] ?? 1));
$total = (int) ($paginator['total'] ?? 0);

if ($lastPage <= 1) {
    return;
}

$build = static function (int $p) use ($query, $path): string {
    $q = array_merge($query, ['page' => $p]);
    return url($path . '?' . http_build_query($q));
};
?>
<div class="flex items-center justify-between gap-3 flex-wrap px-4 py-3 border-t border-ink-100">
    <p class="text-xs text-ink-500"><?= number_format($total) ?> record<?= $total === 1 ? '' : 's' ?> &middot; page <?= $page ?> of <?= $lastPage ?></p>
    <nav class="flex items-center gap-1" aria-label="Pagination">
        <a href="<?= e($build(max(1, $page - 1))) ?>"
           class="page-link <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </a>
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
            <?php if ($i === 1 || $i === $lastPage || abs($i - $page) <= 2): ?>
                <a href="<?= e($build($i)) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php elseif (abs($i - $page) === 3): ?>
                <span class="page-ellipsis">&hellip;</span>
            <?php endif; ?>
        <?php endfor; ?>
        <a href="<?= e($build(min($lastPage, $page + 1))) ?>"
           class="page-link <?= $page >= $lastPage ? 'disabled' : '' ?>" aria-label="Next">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
    </nav>
</div>
