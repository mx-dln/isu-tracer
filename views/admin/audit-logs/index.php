<?php
/** @var array $logs @var array $modules @var array $actions @var string $search @var string $module @var string $action @var int $page @var int $pages @var int $total */
$logs = $logs ?? [];
$modules = $modules ?? [];
$actions = $actions ?? [];
$search = $search ?? '';
$module = $module ?? '';
$action = $action ?? '';
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-ink-900">Audit Logs</h2>
        <p class="text-sm text-ink-500">System activity trail (<?= number_format((int) $total) ?> events)</p>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="<?= url('admin/audit-logs') ?>" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search description, user, IP…" class="input !w-64">
                <select name="module" class="input !w-40">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $m))) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="action" class="input !w-40">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="<?= url('admin/audit-logs') ?>" class="btn-secondary">Reset</a>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="!text-center !py-10 text-ink-500">No audit events found.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-sm whitespace-nowrap"><?= date('M j, Y g:i:s A', strtotime($log['created_at'])) ?></td>
                        <td class="text-sm"><?= e($log['user_name'] ?? 'System') ?></td>
                        <td><span class="badge-<?= $log['action'] === 'delete' ? 'red' : ($log['action'] === 'create' ? 'green' : 'blue') ?>"><?= e($log['action']) ?></span></td>
                        <td><span class="badge-gray"><?= e(ucwords(str_replace('_', ' ', (string) $log['module']))) ?></span></td>
                        <td class="text-sm text-ink-600 max-w-md"><?= e($log['description'] ?? '') ?></td>
                        <td class="text-sm text-ink-500 whitespace-nowrap"><?= e($log['ip_address'] ?? '—') ?></td>
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
                        <a href="<?= url('admin/audit-logs?page=' . ($page - 1) . '&search=' . urlencode($search) . '&module=' . urlencode($module) . '&action=' . urlencode($action)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= url('admin/audit-logs?page=' . ($page + 1) . '&search=' . urlencode($search) . '&module=' . urlencode($module) . '&action=' . urlencode($action)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
