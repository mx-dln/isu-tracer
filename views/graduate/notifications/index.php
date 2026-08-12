<?php
/** @var array $notifications @var int $unread @var int $page @var int $pages @var int $total */
$notifications = $notifications ?? [];
$unread = $unread ?? 0;
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Notifications</h2>
            <p class="text-sm text-ink-500"><?= (int) $total ?> total &middot; <?= (int) $unread ?> unread</p>
        </div>
        <?php if ($unread > 0): ?>
            <form method="POST" action="<?= url('graduate/notifications/mark-all-read') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary"><i data-lucide="check-check" class="w-4 h-4"></i> Mark All Read</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body space-y-2">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12 text-ink-500">
                    <i data-lucide="bell-off" class="w-10 h-10 mx-auto mb-3 text-ink-300"></i>
                    <p>You have no notifications.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($notifications as $n): ?>
                <div class="rounded-lg border border-ink-200 p-4 <?= $n['is_read'] ? 'bg-white' : 'bg-sky-50 border-sky-200' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-ink-800"><?= e($n['title']) ?></h4>
                                <?php if (!$n['is_read']): ?><span class="badge-blue">New</span><?php endif; ?>
                            </div>
                            <p class="text-sm text-ink-600 mt-1"><?= e($n['message']) ?></p>
                            <p class="text-xs text-ink-400 mt-2"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></p>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <form method="POST" action="<?= url('graduate/notifications/' . (int) $n['id'] . '/read') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="icon-btn" title="Mark as read"><i data-lucide="check" class="w-4 h-4"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?= url($n['link']) ?>" class="text-xs font-medium text-brand-700 hover:text-brand-800 mt-2 inline-flex items-center gap-1">
                            View <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <div class="card-footer flex items-center justify-between">
                <span class="text-sm text-ink-500">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('graduate/notifications?page=' . ($page - 1)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= url('graduate/notifications?page=' . ($page + 1)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
