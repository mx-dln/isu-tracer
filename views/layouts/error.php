<?php
/**
 * Error layout.
 */
$code = $code ?? 404;
$title = $title ?? 'Error';
$message = $message ?? 'An unexpected error occurred.';
$debug = $debug ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($code) ?> &middot; <?= e($title) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-ink-900 flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <p class="text-7xl font-extrabold text-brand-500"><?= e($code) ?></p>
        <h1 class="mt-3 text-2xl font-bold text-white"><?= e($title) ?></h1>
        <p class="mt-2 text-ink-300"><?= e($message) ?></p>
        <a href="<?= url('') ?>" class="mt-6 inline-flex items-center gap-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg px-5 py-2.5 text-sm font-medium transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i> Back to Home
        </a>
        <?php if ($debug): ?>
            <pre class="mt-8 text-left text-xs text-ink-400 bg-ink-800 rounded-xl p-4 overflow-x-auto max-h-64"><?= e($debug) ?></pre>
        <?php endif; ?>
    </div>
    <script src="<?= asset('js/lucide.umd.min.js') ?>"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
