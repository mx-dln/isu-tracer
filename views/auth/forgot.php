<div>
    <h2 class="text-lg font-bold text-ink-900">Forgot Password</h2>
    <p class="text-sm text-ink-500 mt-1">Enter your email and we will send you a reset link.</p>

    <form method="POST" action="<?= url('auth/forgot') ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label for="email" class="label">Email Address</label>
            <input type="email" id="email" name="email" value="<?= e(old('email', '')) ?>" class="input" required autofocus>
        </div>
        <button type="submit" class="btn-primary w-full !py-2.5">
            <i data-lucide="send" class="w-4 h-4"></i> Send Reset Link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        <a href="<?= url('login') ?>" class="text-brand-700 hover:text-brand-800 font-semibold">Back to sign in</a>
    </p>
</div>
