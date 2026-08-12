<?php
/**
 * Public (no-login) survey layout: centered card, ISU branding, no sidebar.
 */
$appName = (string) setting('university_name', 'Isabela State University');
$campus = (string) setting('campus_name', 'Cauayan Campus');
$institute = (string) setting('institute_name', 'Institute of Agricultural Technology');
$flashMessages = \App\Core\Session::pullFlash();
$validationErrors = \App\Core\Session::pullErrors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Survey') ?> &middot; <?= e($appName) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .survey-card { box-shadow: 0 1px 2px rgba(17,24,39,.06), 0 10px 30px rgba(17,24,39,.10); }
        .star-btn { transition: transform .12s ease, background-color .12s ease; }
        .star-btn:hover { transform: scale(1.12); }
        .star-btn.active { background-color: #4f46e5 !important; border-color: #4f46e5 !important; color: #fff !important; }
        .scale-chip.active { background-color: #4f46e5 !important; border-color: #4f46e5 !important; color: #fff !important; }
        .likert-chip.active { background-color: #4f46e5 !important; border-color: #4f46e5 !important; color: #fff !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-ink-100 via-ink-50 to-brand-100/40">

<div class="min-h-screen flex flex-col">
    <!-- Brand header -->
    <header class="px-4 py-5">
        <div class="max-w-2xl mx-auto flex items-center gap-3">
            <img src="<?= asset('images/logo.png') ?>" alt="<?= e($appName) ?>" class="w-11 h-11 rounded-xl object-cover shadow-sm">
            <div class="min-w-0">
                <p class="font-bold text-ink-900 leading-tight truncate"><?= e($appName) ?></p>
                <p class="text-xs text-ink-500 truncate"><?= e($campus) ?> &middot; <?= e($institute) ?></p>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="flex-1 w-full max-w-2xl mx-auto px-4 pb-16">
        <?php if ($flashMessages): ?>
            <div class="mb-4 space-y-2">
                <?php foreach ($flashMessages as $type => $message): ?>
                    <div class="alert-box flex items-start gap-2 rounded-lg border px-4 py-3 text-sm
                        <?= $type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                            : ($type === 'error' ? 'bg-red-50 border-red-200 text-red-700'
                            : ($type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800'
                            : 'bg-sky-50 border-sky-200 text-sky-800')) ?>">
                        <i data-lucide="<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'alert-circle' : 'info') ?>" class="w-4 h-4 mt-0.5 shrink-0"></i>
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
                        <button type="button" class="alert-dismiss text-current opacity-60 hover:opacity-100"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="px-4 py-5 text-center text-xs text-ink-400 border-t border-ink-200/60 bg-white/60">
        &copy; <?= date('Y') ?> <?= e($appName) ?> &middot; <?= e($campus) ?> &middot; <?= e($institute) ?>
    </footer>
</div>

<script src="<?= asset('js/lucide.umd.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/public-survey.js') ?>"></script>
</body>
</html>
