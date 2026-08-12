<?php
/** @var array $notifications @var array $templates @var array $rules @var array $recentResults @var int $page @var int $pages @var int $total @var string $search */
$notifications = $notifications ?? [];
$templates = $templates ?? [];
$rules = $rules ?? [];
$recentResults = $recentResults ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
$search = $search ?? '';
?>
<div class="space-y-6">

    <div>
        <h2 class="text-xl font-bold text-ink-900">Notifications</h2>
        <p class="text-sm text-ink-500">In-app notifications, templates and automated rules</p>
    </div>

    <?php if (!empty($recentResults)): ?>
        <div class="alert-box rounded-lg border px-4 py-3 text-sm bg-sky-50 border-sky-200 text-sky-800">
            <p class="font-medium mb-1">Rules just evaluated:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <?php foreach ($recentResults as $res): ?>
                    <li><?= e($res['name']) ?> — <?= (int) $res['count'] ?> notification(s) dispatched</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Broadcast -->
        <div class="card xl:col-span-1">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Send Notification</h3></div>
            <div class="card-body">
                <form method="POST" action="<?= url('admin/notifications/send') ?>" class="space-y-3">
                    <?= csrf_field() ?>
                    <div>
                        <label class="label" for="n-title">Title</label>
                        <input type="text" name="title" id="n-title" class="input" required>
                    </div>
                    <div>
                        <label class="label" for="n-message">Message</label>
                        <textarea name="message" id="n-message" class="input" rows="3" required></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="n-program">Program (optional)</label>
                            <select name="program_id" id="n-program" class="input">
                                <option value="">All</option>
                                <?php foreach (option_list('programs') as $opt): ?>
                                    <option value="<?= (int) $opt['id'] ?>"><?= e($opt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="label" for="n-batch">Batch (optional)</label>
                            <select name="batch_id" id="n-batch" class="input">
                                <option value="">All</option>
                                <?php foreach (option_list('batches') as $opt): ?>
                                    <option value="<?= (int) $opt['id'] ?>"><?= e($opt['label'] ?? $opt['year']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full"><i data-lucide="send" class="w-4 h-4"></i> Send to Graduates</button>
                </form>

                <div class="mt-4 pt-4 border-t border-ink-200">
                    <form method="POST" action="<?= url('admin/notifications/rules/run') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-secondary w-full"><i data-lucide="play" class="w-4 h-4"></i> Run Automated Rules</button>
                    </form>
                    <p class="text-xs text-ink-500 mt-2">Evaluates survey reminders, low response rate and approaching deadlines.</p>
                </div>
            </div>
        </div>

        <!-- Templates & Rules -->
        <div class="card xl:col-span-2">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Notification Templates</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Channel</th>
                            <th>Subject / Body</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($templates)): ?>
                        <tr><td colspan="4" class="!text-center !py-8 text-ink-500">No templates.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><span class="badge-blue"><?= e($t['type']) ?></span></td>
                            <td><span class="badge-gray"><?= e($t['channel']) ?></span></td>
                            <td>
                                <p class="font-medium text-ink-800 text-sm"><?= e($t['subject'] ?? '') ?></p>
                                <p class="text-xs text-ink-500 line-clamp-2"><?= e(mb_strimwidth($t['body'], 0, 120, '…')) ?></p>
                            </td>
                            <td>
                                <form method="POST" action="<?= url('admin/notifications/templates/' . (int) $t['id'] . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="badge <?= $t['is_active'] ? 'badge-green' : 'badge-gray' ?> cursor-pointer">
                                        <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-header mt-2"><h3 class="font-semibold text-ink-800">Automated Rules</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Description</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rules)): ?>
                        <tr><td colspan="3" class="!text-center !py-6 text-ink-500">No rules.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td class="font-medium text-ink-800"><?= e($rule['name']) ?></td>
                            <td class="text-sm text-ink-500"><?= e($rule['description'] ?? '') ?></td>
                            <td>
                                <form method="POST" action="<?= url('admin/notifications/rules/' . (int) $rule['id'] . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="badge <?= $rule['is_active'] ? 'badge-green' : 'badge-gray' ?> cursor-pointer">
                                        <?= $rule['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sent notifications -->
    <div class="card">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-ink-800">Sent Notifications (<?= (int) $total ?>)</h3>
            <form method="GET" action="<?= url('admin/notifications') ?>" class="flex items-center gap-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search notifications…" class="input !w-56">
                <button type="submit" class="btn-secondary !py-1.5 !px-3 text-xs">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($notifications)): ?>
                    <tr><td colspan="6" class="!text-center !py-10 text-ink-500">No notifications sent.</td></tr>
                <?php endif; ?>
                <?php foreach ($notifications as $n): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($n['user_name'] ?? '—') ?></td>
                        <td><span class="badge-blue"><?= e($n['type']) ?></span></td>
                        <td class="text-sm"><?= e(mb_strimwidth($n['title'], 0, 60, '…')) ?></td>
                        <td><span class="badge-gray"><?= e($n['channel']) ?></span></td>
                        <td>
                            <span class="badge <?= $n['is_read'] ? 'badge-gray' : 'badge-green' ?>">
                                <?= $n['is_read'] ? 'Read' : 'Unread' ?>
                            </span>
                        </td>
                        <td><?= date('M j, g:i A', strtotime($n['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer flex items-center justify-between">
                <span class="text-sm text-ink-500">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('admin/notifications?page=' . ($page - 1)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= url('admin/notifications?page=' . ($page + 1)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
