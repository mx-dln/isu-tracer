<?php
/**
 * Friendly public error page (no internal details leaked).
 * @var array|null $survey @var string $reason @var string $heading @var string $message
 */
$survey = $survey ?? null;
$reason = $reason ?? 'invalid';
$heading = $heading ?? 'Survey Unavailable';
$message = $message ?? 'This survey invitation link is invalid or no longer available.';

$icons = [
    'invalid'        => 'help-circle',
    'throttled'      => 'timer',
    'expired'        => 'clock',
    'expired_period' => 'lock',
    'revoked'        => 'ban',
    'completed'      => 'check-circle-2',
    'draft'          => 'hourglass',
    'closed'         => 'lock',
    'active'         => 'info',
];
$icon = $icons[$reason] ?? 'help-circle';

$tint = ($reason === 'completed')
    ? 'bg-emerald-100 text-emerald-600'
    : (($reason === 'expired' || $reason === 'revoked' || $reason === 'throttled')
        ? 'bg-amber-100 text-amber-600'
        : 'bg-ink-100 text-ink-500');
?>
<div class="survey-card rounded-xl bg-white border border-ink-200 text-center p-10">
    <div class="w-16 h-16 mx-auto rounded-full <?= $tint ?> flex items-center justify-center">
        <i data-lucide="<?= $icon ?>" class="w-8 h-8"></i>
    </div>
    <h1 class="mt-4 text-xl font-bold text-ink-900"><?= e($heading) ?></h1>
    <p class="mt-2 text-sm text-ink-600 max-w-md mx-auto"><?= e($message) ?></p>
    <a href="<?= url('') ?>" class="btn-secondary mt-6 inline-flex"><i data-lucide="home" class="w-4 h-4"></i> Return to Home</a>
</div>