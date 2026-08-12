<?php
$lockoutRemaining = $lockoutRemaining ?? 0;
$maxAttempts = (int) config('app.security.login_max_attempts', 5);
?>
<div>
    <h2 class="text-lg font-bold text-ink-900">Sign In</h2>
    <p class="text-sm text-ink-500 mt-1">Access your IAT tracer study account.</p>

    <?php if ($lockoutRemaining > 0): ?>
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            Too many failed attempts. Please try again in <?= ceil($lockoutRemaining / 60) ?> minute(s).
        </div>
    <?php else: ?>
        <form method="POST" action="<?= url('login') ?>" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="email" class="label">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e(old('email', '')) ?>" class="input" required autofocus autocomplete="email">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="label !mb-0">Password</label>
                    <a href="<?= url('auth/forgot') ?>" class="text-xs text-brand-700 hover:text-brand-800 font-medium">Forgot password?</a>
                </div>
                <div class="relative">
                    <input type="password" id="password" name="password" class="input pr-10" required autocomplete="current-password">
                    <button type="button" data-toggle-password="#password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-ink-400 hover:text-ink-600" aria-label="Show password">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full !py-2.5">
                <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-500">
            New IAT graduate?
            <a href="<?= url('register') ?>" class="text-brand-700 hover:text-brand-800 font-semibold">Create an account</a>
        </p>

        <div class="mt-6 rounded-lg bg-ink-50 border border-ink-200 px-4 py-3">
            <p class="text-xs font-semibold text-ink-600 mb-2">Development credentials — quick login</p>
            <div class="grid grid-cols-1 gap-2">
                <button type="button" data-quick-login data-email="admin@example.com" data-password="Admin@12345"
                        class="flex items-center justify-between gap-2 rounded-lg border border-ink-200 bg-white px-3 py-2 text-left text-xs hover:border-brand-400 hover:bg-brand-50 transition">
                    <span class="flex items-center gap-2">
                        <i data-lucide="shield" class="w-4 h-4 text-brand-600"></i>
                        <span>
                            <span class="font-semibold text-ink-800 block">Admin</span>
                            <span class="text-ink-500">admin@example.com</span>
                        </span>
                    </span>
                    <i data-lucide="log-in" class="w-4 h-4 text-ink-400"></i>
                </button>
                <button type="button" data-quick-login data-email="test.graduate@example.com" data-password="Graduate@12345"
                        class="flex items-center justify-between gap-2 rounded-lg border border-ink-200 bg-white px-3 py-2 text-left text-xs hover:border-brand-400 hover:bg-brand-50 transition">
                    <span class="flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="w-4 h-4 text-ink-500"></i>
                        <span>
                            <span class="font-semibold text-ink-800 block">Demo Graduate</span>
                            <span class="text-ink-500">test.graduate@example.com</span>
                        </span>
                    </span>
                    <i data-lucide="log-in" class="w-4 h-4 text-ink-400"></i>
                </button>
            </div>
            <p class="mt-2 text-[11px] text-ink-400">All demo graduates use password <code>Graduate@12345</code></p>
        </div>
    <?php endif; ?>

    <script>
        (function () {
            const form = document.querySelector('form[action*="login"]');
            if (!form) return;
            document.querySelectorAll('[data-quick-login]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.querySelector('#email').value = btn.dataset.email;
                    form.querySelector('#password').value = btn.dataset.password;
                    form.submit();
                });
            });
        })();
    </script>
</div>
