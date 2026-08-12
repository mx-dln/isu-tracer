<?php
/** @var array $users @var array $roles @var string $search @var string $role @var int $page @var int $pages @var int $total */
$users = $users ?? [];
$roles = $roles ?? [];
$search = $search ?? '';
$role = $role ?? '';
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Users</h2>
            <p class="text-sm text-ink-500">Manage system user accounts</p>
        </div>
        <a href="<?= url('admin/users/create') ?>" class="btn-primary"><i data-lucide="user-plus" class="w-4 h-4"></i> Add User</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="<?= url('admin/users') ?>" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or email…" class="input !w-64">
                <select name="role" class="input !w-44">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r['slug']) ?>" <?= $role === $r['slug'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Graduate</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="!text-center !py-10 text-ink-500">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($u['name']) ?></td>
                        <td class="text-sm"><?= e($u['email']) ?></td>
                        <td><span class="badge-<?= $u['role_slug'] === 'admin' ? 'red' : 'blue' ?>"><?= e($u['role_name']) ?></span></td>
                        <td class="text-sm text-ink-500">
                            <?= $u['student_number'] ? e($u['student_number']) . ' <span class="text-ink-400">(' . e($u['program_code'] ?? '') . ' ' . e($u['batch_year'] ?? '') . ')</span>' : '—' ?>
                        </td>
                        <td class="text-sm text-ink-500"><?= $u['last_login_at'] ? date('M j, g:i A', strtotime($u['last_login_at'])) : 'Never' ?></td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'badge-green' : 'badge-gray' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('admin/users/' . (int) $u['id'] . '/edit') ?>" class="icon-btn" title="Edit"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                                <?php if ($u['email'] !== 'admin@example.com' && (int) $u['id'] !== (int) (auth()?->id ?? 0)): ?>
                                    <form method="POST" action="<?= url('admin/users/' . (int) $u['id']) ?>" onsubmit="return confirmAction('Delete this user account?', this);">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer flex items-center justify-between">
                <span class="text-sm text-ink-500">Page <?= (int) $page ?> of <?= (int) $pages ?> (<?= (int) $total ?> users)</span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('admin/users?page=' . ($page - 1) . '&search=' . urlencode($search) . '&role=' . urlencode($role)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= url('admin/users?page=' . ($page + 1) . '&search=' . urlencode($search) . '&role=' . urlencode($role)) ?>" class="btn-secondary !py-1.5 !px-3 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
