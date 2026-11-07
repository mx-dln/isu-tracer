<?php
/**
 * Auto-Logout Client Guard
 * Enforces client-side inactivity tracking, warning modal, and auto-logout.
 */
$timeoutMinutes = (int) setting('auto_logout_minutes', 15);
if ($timeoutMinutes <= 0) {
    $timeoutMinutes = 15;
}
$timeoutSeconds = $timeoutMinutes * 60;
$warningSeconds = 60; // Show warning 60 seconds before auto-logout
?>
<!-- Inactivity Warning Modal -->
<div id="autoLogoutModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-ink-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="autoLogoutTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-ink-100 text-center animate-in fade-in zoom-in-95 duration-200">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
            <i data-lucide="clock" class="h-7 w-7"></i>
        </div>
        <h3 id="autoLogoutTitle" class="text-lg font-bold text-ink-900">Session Inactivity Warning</h3>
        <p class="mt-2 text-sm text-ink-600">
            You have been idle for a while. For institutional data security, you will be automatically logged out in:
        </p>
        <div class="my-4">
            <span id="autoLogoutCountdown" class="inline-block rounded-xl bg-red-50 px-4 py-2 font-mono text-3xl font-extrabold text-red-600 border border-red-200">
                60
            </span>
            <p class="mt-1 text-xs text-ink-400">seconds</p>
        </div>
        <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-center gap-3">
            <form method="POST" action="<?= url('logout') ?>" class="w-full sm:w-auto">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary w-full !py-2.5">Log Out Now</button>
            </form>
            <button type="button" id="btnStayLoggedIn" class="btn-primary w-full sm:w-auto !py-2.5 !bg-brand-600 hover:!bg-brand-700">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Stay Signed In
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const totalSeconds = <?= $timeoutSeconds ?>;
    const warningSeconds = <?= $warningSeconds ?>;
    const keepAliveUrl = '<?= url('auth/keep-alive') ?>';
    const logoutUrl = '<?= url('logout') ?>';
    const csrfToken = '<?= csrf_token() ?>';

    let idleSeconds = 0;
    let warningActive = false;
    let countdownRemaining = warningSeconds;
    const modal = document.getElementById('autoLogoutModal');
    const countdownEl = document.getElementById('autoLogoutCountdown');
    const btnStay = document.getElementById('btnStayLoggedIn');

    function resetIdle() {
        if (!warningActive) {
            idleSeconds = 0;
        }
    }

    // Passive interaction listeners
    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(evt => {
        window.addEventListener(evt, resetIdle, { passive: true });
    });

    // Tick every 1 second
    const timerInterval = setInterval(function() {
        idleSeconds++;

        // Trigger warning modal
        if (idleSeconds >= (totalSeconds - warningSeconds) && !warningActive) {
            warningActive = true;
            countdownRemaining = totalSeconds - idleSeconds;
            if (countdownRemaining <= 0) countdownRemaining = warningSeconds;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            countdownEl.textContent = countdownRemaining;
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        // Countdown while warning is active
        if (warningActive) {
            countdownRemaining--;
            if (countdownRemaining >= 0) {
                countdownEl.textContent = countdownRemaining;
            }
            if (countdownRemaining <= 0) {
                clearInterval(timerInterval);
                performAutoLogout();
            }
        }
    }, 1000);

    function performAutoLogout() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }

    function staySignedIn() {
        fetch(keepAliveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_token=' + encodeURIComponent(csrfToken)
        }).then(() => {
            warningActive = false;
            idleSeconds = 0;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }).catch(() => {
            // Even if network blip, hide warning and reset timer
            warningActive = false;
            idleSeconds = 0;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }

    if (btnStay) {
        btnStay.addEventListener('click', staySignedIn);
    }
})();
</script>
