<?php
/** @var array $survey @var int $responseId */
$responseId = $responseId ?? null;
$message = !empty($survey['confirmation_message'])
    ? $survey['confirmation_message']
    : 'Your IAT Graduate Tracer Study response has been successfully submitted. Your response has been recorded. Thank you for helping the Institute of Agricultural Technology improve its programs and graduate services.';
?>
<div class="survey-card rounded-xl bg-white border border-emerald-200 text-center p-10">
    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center">
        <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-600"></i>
    </div>
    <h1 class="mt-4 text-xl font-bold text-ink-900">Thank You!</h1>
    <p class="mt-2 text-sm text-ink-600 max-w-md mx-auto whitespace-pre-line"><?= e($message) ?></p>
    <?php if ($responseId): ?>
        <p class="mt-4 text-xs text-ink-400">Reference: #<?= (int) $responseId ?></p>
    <?php endif; ?>
    <a href="<?= url('') ?>" class="btn-secondary mt-6 inline-flex"><i data-lucide="home" class="w-4 h-4"></i> Return to Home</a>
</div>
