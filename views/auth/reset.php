<div>
    <h2 class="text-lg font-bold text-ink-900">Set New Password</h2>
    <p class="text-sm text-ink-500 mt-1">Choose a new password for your account.</p>

    <form method="POST" action="<?= url('auth/password-reset/' . e($token)) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label for="password" class="label">New Password</label>
            <input type="password" id="password" name="password" class="input" required>
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="input" required>
        </div>
        <button type="submit" class="btn-primary w-full !py-2.5">
            <i data-lucide="key" class="w-4 h-4"></i> Update Password
        </button>
    </form>
</div>
