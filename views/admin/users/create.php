<?php
/** @var array $roles @var array $user */
$roles = $roles ?? [];
$user = $user ?? null;
$isEdit = $user !== null;
?>
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-ink-900"><?= $isEdit ? 'Edit User' : 'Add User' ?></h2>
        <p class="text-sm text-ink-500"><?= $isEdit ? e($user['name']) : 'Create a new user account' ?></p>
    </div>

    <div class="card max-w-2xl">
        <div class="card-body">
            <form method="POST" action="<?= $isEdit ? url('admin/users/' . (int) $user['id']) : url('admin/users') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

                <div>
                    <label class="label" for="u-name">Full Name</label>
                    <input type="text" name="name" id="u-name" class="input" value="<?= e($user['name'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="label" for="u-email">Email</label>
                    <input type="email" name="email" id="u-email" class="input" value="<?= e($user['email'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="label" for="u-role">Role</label>
                    <select name="role_id" id="u-role" class="input" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="u-password"><?= $isEdit ? 'New Password (leave blank to keep)' : 'Password' ?></label>
                    <input type="password" name="password" id="u-password" class="input" <?= $isEdit ? '' : 'required' ?>>
                    <p class="text-xs text-ink-500 mt-1">Minimum 8 characters.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="u-active" class="accent-brand-700 w-4 h-4" <?= !$isEdit || $user['is_active'] ? 'checked' : '' ?>>
                    <label for="u-active" class="text-sm text-ink-700">Account is active</label>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="submit" class="btn-primary"><?= $isEdit ? 'Save Changes' : 'Create User' ?></button>
                    <a href="<?= url('admin/users') ?>" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
