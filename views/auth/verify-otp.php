<?php
/** @var string $phone */
$phone = $phone ?? '';
$masked = \App\Services\OtpService::maskPhone($phone);
?>
<div>
    <div class="text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-brand-50 border border-brand-200 text-brand-700 flex items-center justify-center mb-3">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
        </div>
        <h2 class="text-lg font-bold text-ink-900">Verify Mobile Number</h2>
        <p class="text-xs text-ink-500 mt-1">
            We sent a 6-digit verification code via Semaphore SMS to:
        </p>
        <p class="text-sm font-bold text-ink-800 mt-1 font-mono tracking-wider"><?= e($masked) ?></p>
    </div>

    <form method="POST" action="<?= url('verify-otp') ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="otp_code" class="label text-center">Enter 6-Digit Verification Code</label>
            <input type="text" id="otp_code" name="otp_code" maxlength="6" pattern="[0-9]{6}"
                   class="input text-center font-mono text-2xl tracking-[0.5em] py-3 font-bold"
                   placeholder="000000" required autofocus autocomplete="one-time-code">
            <p class="text-[11px] text-ink-400 text-center mt-1.5">Code expires in 10 minutes.</p>
        </div>

        <button type="submit" class="btn-primary w-full !py-2.5">
            <i data-lucide="check" class="w-4 h-4"></i> Verify &amp; Proceed
        </button>
    </form>

    <div class="mt-6 pt-4 border-t border-ink-100 flex items-center justify-between text-xs">
        <form method="POST" action="<?= url('verify-otp/resend') ?>" class="inline">
            <?= csrf_field() ?>
            <button type="submit" class="text-brand-700 hover:text-brand-800 font-semibold flex items-center gap-1">
                <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> Resend Code
            </button>
        </form>

        <a href="<?= url('login') ?>" class="text-ink-400 hover:text-ink-600">Back to Sign In</a>
    </div>
</div>
