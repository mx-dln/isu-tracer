<?php
/**
 * Master layout.
 * Variables: $content (rendered view HTML), $data (view data).
 */
$user = auth();
$role = $user->role_slug ?? 'guest';
$appName = (string) setting('university_name', 'Isabela State University');
$campus = (string) setting('campus_name', 'Cauayan Campus');
$flashMessages = \App\Core\Session::pullFlash();
$validationErrors = \App\Core\Session::pullErrors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? setting('study_title', 'IAT Tracer Study')) ?> &middot; <?= e($appName) ?> <?= e($campus) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
    <?php if (isset($headExtra)) echo $headExtra; ?>
</head>
<body class="bg-ink-100 min-h-screen">

<div class="flex min-h-screen">

    <!-- ===================== SIDEBAR ===================== -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-ink-900 text-ink-300 flex flex-col transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen transition-transform duration-200" aria-label="Sidebar">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/10 shrink-0">
            <img src="<?= asset('images/logo.png') ?>" alt="<?= e($appName) ?>" class="w-9 h-9 rounded-lg object-cover shrink-0">
            <div class="min-w-0">
                <p class="text-white text-sm font-bold leading-tight truncate"><?= e($appName) ?></p>
                <p class="text-ink-400 text-xs truncate"><?= e($campus) ?> &middot; <?= e(setting('institute_name', 'IAT')) ?></p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" id="sidebar-nav">
            <?php if ($role === 'admin'): ?>
                <a href="<?= url('admin/dashboard') ?>" class="sidebar-link <?= current_route_active('/admin/dashboard') ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i><span>Dashboard</span>
                </a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Records</p>
                <a href="<?= url('admin/graduates') ?>" class="sidebar-link <?= current_route_active('/admin/graduates') ? 'active' : '' ?>">
                    <i data-lucide="users" class="w-4 h-4"></i><span>Graduates</span>
                </a>
                <a href="<?= url('admin/programs') ?>" class="sidebar-link <?= current_route_active('/admin/programs') ? 'active' : '' ?>">
                    <i data-lucide="graduation-cap" class="w-4 h-4"></i><span>Programs</span>
                </a>
                <a href="<?= url('admin/batches') ?>" class="sidebar-link <?= current_route_active('/admin/batches') ? 'active' : '' ?>">
                    <i data-lucide="layers" class="w-4 h-4"></i><span>Batches</span>
                </a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Research Data</p>
                <a href="<?= url('admin/surveys') ?>" class="sidebar-link <?= current_route_active('/admin/surveys') ? 'active' : '' ?>">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i><span>Surveys</span>
                </a>
                <a href="<?= url('admin/employment') ?>" class="sidebar-link <?= current_route_active('/admin/employment') ? 'active' : '' ?>">
                    <i data-lucide="briefcase" class="w-4 h-4"></i><span>Employment</span>
                </a>
                <a href="<?= url('admin/competencies') ?>" class="sidebar-link <?= current_route_active('/admin/competencies') ? 'active' : '' ?>">
                    <i data-lucide="award" class="w-4 h-4"></i><span>Competencies</span>
                </a>
                <a href="<?= url('admin/curriculum-feedback') ?>" class="sidebar-link <?= current_route_active('/admin/curriculum-feedback') ? 'active' : '' ?>">
                    <i data-lucide="book-open-check" class="w-4 h-4"></i><span>Curriculum Feedback</span>
                </a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Intelligence</p>
                <a href="<?= url('admin/analytics') ?>" class="sidebar-link <?= current_route_active('/admin/analytics') ? 'active' : '' ?>">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i><span>Analytics</span>
                </a>
                <a href="<?= url('admin/forecasting') ?>" class="sidebar-link <?= current_route_active('/admin/forecasting') ? 'active' : '' ?>">
                    <i data-lucide="trending-up" class="w-4 h-4"></i><span>Forecasting</span>
                </a>
                <a href="<?= url('admin/reports') ?>" class="sidebar-link <?= current_route_active('/admin/reports') ? 'active' : '' ?>">
                    <i data-lucide="file-text" class="w-4 h-4"></i><span>Reports</span>
                </a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">Administration</p>
                <a href="<?= url('admin/notifications') ?>" class="sidebar-link <?= current_route_active('/admin/notifications') ? 'active' : '' ?>">
                    <i data-lucide="bell" class="w-4 h-4"></i><span>Notifications</span>
                </a>
                <a href="<?= url('admin/users') ?>" class="sidebar-link <?= current_route_active('/admin/users') ? 'active' : '' ?>">
                    <i data-lucide="user-cog" class="w-4 h-4"></i><span>Users</span>
                </a>
                <a href="<?= url('admin/audit-logs') ?>" class="sidebar-link <?= current_route_active('/admin/audit-logs') ? 'active' : '' ?>">
                    <i data-lucide="scroll-text" class="w-4 h-4"></i><span>Audit Logs</span>
                </a>
                <a href="<?= url('admin/login-logs') ?>" class="sidebar-link <?= current_route_active('/admin/login-logs') ? 'active' : '' ?>">
                    <i data-lucide="log-in" class="w-4 h-4"></i><span>Login Logs</span>
                </a>
                <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= current_route_active('/admin/settings') ? 'active' : '' ?>">
                    <i data-lucide="settings" class="w-4 h-4"></i><span>Settings</span>
                </a>
            <?php elseif ($role === 'graduate'): ?>
                <a href="<?= url('graduate/dashboard') ?>" class="sidebar-link <?= current_route_active('/graduate/dashboard') ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i><span>Dashboard</span>
                </a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500">My Records</p>
                <a href="<?= url('graduate/profile') ?>" class="sidebar-link <?= current_route_active('/graduate/profile') ? 'active' : '' ?>">
                    <i data-lucide="user" class="w-4 h-4"></i><span>Profile</span>
                </a>
                <a href="<?= url('graduate/survey') ?>" class="sidebar-link <?= current_route_active('/graduate/survey') ? 'active' : '' ?>">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i><span>Tracer Survey</span>
                </a>
                <a href="<?= url('graduate/employment') ?>" class="sidebar-link <?= current_route_active('/graduate/employment') ? 'active' : '' ?>">
                    <i data-lucide="briefcase" class="w-4 h-4"></i><span>Employment</span>
                </a>
                <a href="<?= url('graduate/competencies') ?>" class="sidebar-link <?= current_route_active('/graduate/competencies') ? 'active' : '' ?>">
                    <i data-lucide="award" class="w-4 h-4"></i><span>Competencies</span>
                </a>
                <a href="<?= url('graduate/curriculum-feedback') ?>" class="sidebar-link <?= current_route_active('/graduate/curriculum-feedback') ? 'active' : '' ?>">
                    <i data-lucide="book-open-check" class="w-4 h-4"></i><span>Curriculum Feedback</span>
                </a>
                <a href="<?= url('graduate/notifications') ?>" class="sidebar-link <?= current_route_active('/graduate/notifications') ? 'active' : '' ?>">
                    <i data-lucide="bell" class="w-4 h-4"></i><span>Notifications</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-3 border-t border-white/10 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-8 h-8 rounded-full bg-brand-700 text-white flex items-center justify-center text-sm font-bold uppercase">
                    <?= e(substr($user->name ?? '?', 0, 1)) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-white text-xs font-semibold truncate"><?= e($user->name ?? '') ?></p>
                    <p class="text-ink-500 text-[11px] truncate"><?= e($role === 'admin' ? 'Administrator' : 'IAT Graduate') ?></p>
                </div>
                <form method="POST" action="<?= url('logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" title="Logout" class="text-ink-400 hover:text-white transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-ink-900/50 z-30 hidden lg:hidden"></div>

    <!-- ===================== MAIN ===================== -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        <header class="bg-white border-b border-ink-200 sticky top-0 z-20">
            <div class="flex items-center justify-between gap-3 px-4 lg:px-6 h-16">
                <div class="flex items-center gap-3">
                    <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg hover:bg-ink-100 text-ink-600" aria-label="Toggle sidebar">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div>
                        <h1 class="text-base lg:text-lg font-bold text-ink-900 leading-tight"><?= e($title ?? 'Dashboard') ?></h1>
                        <p class="text-xs text-ink-500 hidden sm:block"><?= e($subtitle ?? $campus . ' &middot; ' . setting('institute_name', 'IAT')) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Notifications -->
                    <div class="relative" id="notification-dropdown">
                        <button id="notification-bell" class="relative p-2 rounded-lg hover:bg-ink-100 text-ink-600" aria-label="Notifications">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span id="notification-count" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notification-panel" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-ink-200 z-50 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-ink-100">
                                <p class="text-sm font-semibold text-ink-800">Notifications</p>
                                <button id="mark-all-read" class="text-xs text-brand-700 hover:text-brand-800 font-medium">Mark all as read</button>
                            </div>
                            <div id="notification-list" class="max-h-96 overflow-y-auto divide-y divide-ink-100">
                                <div class="px-4 py-8 text-center text-sm text-ink-500">Loading...</div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-3 pl-2 border-l border-ink-200">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-ink-800 leading-tight"><?= e($user->name ?? '') ?></p>
                            <p class="text-xs text-ink-500"><?= e($role === 'admin' ? 'Administrator' : 'IAT Graduate') ?></p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold uppercase">
                            <?= e(substr($user->name ?? '?', 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Alerts -->
        <?php if ($flashMessages || $validationErrors): ?>
        <div class="px-4 lg:px-6 pt-4 space-y-2">
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
                    <button type="button" class="alert-dismiss text-current opacity-60 hover:opacity-100">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <main class="flex-1 px-4 lg:px-6 py-6">
            <?= $content ?>
        </main>

        <footer class="px-4 lg:px-6 py-4 border-t border-ink-200 text-center text-xs text-ink-500">
            &copy; <?= date('Y') ?> <?= e($appName) ?> &middot; <?= e($campus) ?> &middot; Institute of Agricultural Technology
        </footer>
    </div>
</div>

<!-- Toast container -->
<div id="toast-container" class="fixed top-4 right-4 z-[100] space-y-2 w-80 max-w-[calc(100vw-2rem)]"></div>

<script src="<?= asset('js/lucide.umd.min.js') ?>"></script>
<script src="<?= asset('js/chart.umd.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script>
    // Global notification center wiring
    document.addEventListener('DOMContentLoaded', function () {
        initNotificationCenter({ base: '<?= url('api/notifications') ?>', role: '<?= e($role) ?>' });
    });
</script>
<?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
