<?php
/** @var array $logs @var array $recentFailures @var string $search @var string $status @var int $page @var int $pages @var int $total */
$logs = $logs ?? [];
$recentFailures = $recentFailures ?? [];
$search = $search ?? '';
$status = $status ?? '';
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-ink-900">Login Logs</h2>
        <p class="text-sm text-ink-500">Authentication activity (<?= number_format((int) $total) ?> events)</p>
    </div>

    <?php if (!empty($recentFailures)): ?>
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Possible Brute-Force Activity (last 24h)</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>IP Address</th><th>Failed Attempts</th><th>Last Attempt</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentFailures as $rf): ?>
                            <tr>
                                <td class="font-mono text-sm"><?= e($rf['ip_address']) ?></td>
                                <td><span class="badge-red"><?= (int) $rf['attempts'] ?></span></td>
                                <td class="text-sm"><?= date('M j, Y g:i A', strtotime($rf['last_attempt'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="<?= url('admin/login-logs') ?>" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user or IP…" class="input !w-64">
                <select name="status" class="input !w-40">
                    <option value="">All Statuses</option>
                    <option value="success" <?= $status === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="!text-center !py-10 text-ink-500">No login events found.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-sm whitespace-nowrap"><?= date('M j, Y g:i:s A', strtotime($log['login_at'])) ?></td>
                        <td class="text-sm"><?= e($log['user_name'] ?? 'Unknown') ?></td>
                        <td>
                            <span class="badge <?= $log['status'] === 'success' ? 'badge-green' : 'badge-red' ?>"><?= e($log['status']) ?></span>
                        </td>
                        <td class="text-sm text-ink-500"><?= e(ucwords(str_replace('_', ' ', (string) $log['failure_reason']))) ?: '—' ?></td>
                        <td class="text-sm font-mono whitespace-nowrap"><?= e($log['ip_address'] ?? '—') ?></td>
                        <td class="text-xs text-ink-400 max-w-xs truncate"><?= e($log['user_agent'] ?? '') ?></td>
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
                        <a href="<?= url('admin/login-logs?page=' . ($page - 1) . '&search=' . urlencode($search) . '&status=' . urlencode($status)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= url('admin/login-logs?page=' . ($page + 1) . '&search=' . urlencode($search) . '&status=' . urlencode($status)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
