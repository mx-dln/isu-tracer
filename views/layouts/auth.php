<?php
/**
 * Authentication layout: centered card on a subtle gradient.
 */
$appName = (string) setting('university_name', 'Isabela State University');
$campus = (string) setting('campus_name', 'Cauayan Campus');
$flashMessages = \App\Core\Session::pullFlash();
$validationErrors = \App\Core\Session::pullErrors();
$title = $title ?? 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> &middot; <?= e($appName) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-ink-900 via-ink-800 to-brand-900 flex items-center justify-center px-4 py-10">

<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <img src="<?= asset('images/logo.png') ?>" alt="<?= e($appName) ?>" class="mx-auto w-16 h-16 rounded-2xl object-cover">
        <h1 class="mt-4 text-white text-xl font-bold"><?= e($appName) ?></h1>
        <p class="text-ink-300 text-sm mt-1"><?= e($campus) ?> &middot; Institute of Agricultural Technology</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
        <?php if ($flashMessages): ?>
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="mb-4 flex items-start gap-2 rounded-lg border px-4 py-3 text-sm
                    <?= $type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                        : ($type === 'error' ? 'bg-red-50 border-red-200 text-red-700'
                        : ($type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800'
                        : 'bg-sky-50 border-sky-200 text-sky-800')) ?>">
                    <div class="flex-1">
                        <p><?= e($message) ?></p>
                        <?php if ($validationErrors): ?>
                            <ul class="mt-1 list-disc list-inside text-xs">
                                <?php foreach ($validationErrors as $fieldErrors): foreach ($fieldErrors as $e2): ?>
                                    <li><?= e($e2) ?></li>
                                <?php endforeach; endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?= $content ?>
    </div>

    <p class="text-center text-ink-400 text-xs mt-6">
        &copy; <?= date('Y') ?> <?= e($appName) ?> &middot; <?= e($campus) ?>
    </p>
</div>

<script src="<?= asset('js/lucide.umd.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
