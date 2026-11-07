<?php
/** @var array $user @var ?array $employment @var ?array $activeSurvey @var bool $surveySubmitted @var int $competencyCount @var int $totalCompetencies @var bool $curriculumSubmitted @var int $unreadCount @var \App\Services\AnalyticsService $analytics */
$user = $user ?? [];
$employment = $employment ?? null;
$activeSurvey = $activeSurvey ?? null;
$surveySubmitted = $surveySubmitted ?? false;
$competencyCount = $competencyCount ?? 0;
$totalCompetencies = $totalCompetencies ?? 0;
$curriculumSubmitted = $curriculumSubmitted ?? false;
?>
<div class="space-y-6">

    <!-- Welcome banner -->
    <div class="card overflow-hidden">
        <div class="bg-gradient-to-r from-brand-800 to-brand-600 px-6 py-6 text-white">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Welcome, <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>!</h2>
                    <p class="text-brand-100 text-sm mt-1">
                        <?= e($user['student_number'] ?? '') ?> &middot; <?= e($user['program_name'] ?? '') ?><?= !empty($user['major']) ? ' (' . e($user['major']) . ')' : '' ?> &middot; Batch <?= e($user['batch_year'] ?? '') ?>
                    </p>
                </div>
                <a href="<?= url('graduate/profile') ?>" class="btn bg-white/15 hover:bg-white/25 text-white border border-white/30">
                    <i data-lucide="user" class="w-4 h-4"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Validation & Verification Status -->
    <div class="space-y-3">
        <?php if (!empty($user['is_validated'])): ?>
            <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl px-4 py-3 text-sm">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="badge-check" class="w-5 h-5"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-emerald-950">Official Graduate Record Validated</p>
                    <p class="text-xs text-emerald-800 mt-0.5">Your academic and graduate identity has been verified and validated by the IAT Administration<?= !empty($user['validated_at']) ? ' on ' . date('M j, Y', strtotime($user['validated_at'])) : '' ?>.</p>
                </div>
                <span class="badge-green shrink-0"><i data-lucide="check" class="w-3 h-3"></i> Validated</span>
            </div>
        <?php else: ?>
            <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl px-4 py-3 text-sm">
                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-amber-950">Information Pending Administrative Validation</p>
                    <p class="text-xs text-amber-800 mt-0.5">Your graduate profile has been submitted and is currently queued for official review by the IAT Administration. You may continue to complete your survey and employment details.</p>
                </div>
                <span class="badge-amber shrink-0"><i data-lucide="clock" class="w-3 h-3"></i> Pending</span>
            </div>
        <?php endif; ?>

        <?php if (empty($user['phone_verified_at']) && !empty($user['contact_number'])): ?>
            <div class="flex items-center justify-between gap-3 bg-sky-50 border border-sky-200 text-sky-900 rounded-xl px-4 py-3 text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center shrink-0">
                        <i data-lucide="smartphone" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sky-950">Verify Your Mobile Number</p>
                        <p class="text-xs text-sky-800 mt-0.5">Your contact number (<?= e($user['contact_number']) ?>) is not yet verified via OTP.</p>
                    </div>
                </div>
                <a href="<?= url('graduate/profile') ?>#verification" class="btn-primary !py-1.5 !px-3 !text-xs shrink-0">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Verify via OTP
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completion progress -->
    <div class="card">
        <div class="card-header"><h2 class="font-semibold text-ink-800">Tracer Study Completion</h2></div>
        <div class="card-body">
            <?php
            $steps = [
                ['label' => 'Profile', 'done' => (bool) ($user['id'] ?? false), 'link' => 'graduate/profile', 'icon' => 'user'],
                ['label' => 'Tracer Survey', 'done' => $surveySubmitted, 'link' => 'graduate/survey', 'icon' => 'clipboard-list'],
                ['label' => 'Employment', 'done' => (bool) $employment, 'link' => 'graduate/employment', 'icon' => 'briefcase'],
                ['label' => 'Competencies', 'done' => $competencyCount >= $totalCompetencies, 'link' => 'graduate/competencies', 'icon' => 'award'],
                ['label' => 'Curriculum Feedback', 'done' => $curriculumSubmitted, 'link' => 'graduate/curriculum-feedback', 'icon' => 'book-open-check'],
            ];
            $doneCount = count(array_filter($steps, fn ($s) => $s['done']));
            $pct = round($doneCount / count($steps) * 100);
            ?>
            <div class="flex items-center gap-3 mb-6">
                <div class="flex-1 h-3 bg-ink-100 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-600 rounded-full transition-all duration-700" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="text-sm font-semibold text-ink-700"><?= $doneCount ?>/<?= count($steps) ?> (<?= $pct ?>%)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <?php foreach ($steps as $step): ?>
                    <a href="<?= url($step['link']) ?>" class="rounded-xl border <?= $step['done'] ? 'border-emerald-200 bg-emerald-50' : 'border-ink-200 bg-white hover:bg-ink-50' ?> p-4 text-center">
                        <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center <?= $step['done'] ? 'bg-emerald-500 text-white' : 'bg-ink-100 text-ink-400' ?>">
                            <i data-lucide="<?= $step['done'] ? 'check' : $step['icon'] ?>" class="w-5 h-5"></i>
                        </div>
                        <p class="mt-2 text-sm font-medium <?= $step['done'] ? 'text-emerald-800' : 'text-ink-600' ?>"><?= e($step['label']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current survey status -->
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">Active Tracer Survey</h2>
                <span class="badge-green"><?= $activeSurvey ? 'Open' : 'None' ?></span>
            </div>
            <div class="card-body">
                <?php if ($activeSurvey): ?>
                    <h3 class="font-semibold text-ink-900"><?= e($activeSurvey['title']) ?></h3>
                    <p class="text-sm text-ink-500 mt-1"><?= e($activeSurvey['description'] ?? '') ?></p>
                    <p class="text-xs text-ink-400 mt-2">
                        Closes: <?= $activeSurvey['end_date'] ? date('M j, Y', strtotime($activeSurvey['end_date'])) : 'Open-ended' ?>
                    </p>
                    <div class="mt-4">
                        <?php if ($surveySubmitted): ?>
                            <div class="flex items-center gap-2 text-emerald-700 text-sm font-medium">
                                <i data-lucide="check-circle" class="w-5 h-5"></i> You have already submitted this survey.
                            </div>
                            <a href="<?= url('graduate/survey/' . (int) $activeSurvey['id']) ?>" class="btn-secondary mt-3">View my responses</a>
                        <?php else: ?>
                            <a href="<?= url('graduate/survey/' . (int) $activeSurvey['id']) ?>" class="btn-primary">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i> Complete Survey
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-ink-400">
                        <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2"></i>
                        <p class="text-sm">No active survey at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employment summary -->
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-ink-800">My Employment Profile</h2>
                <a href="<?= url('graduate/employment') ?>" class="text-xs text-brand-700 font-medium hover:underline">Manage</a>
            </div>
            <div class="card-body">
                <?php if ($employment): ?>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="badge <?= $employment['status'] === 'employed' ? 'badge-green' : ($employment['status'] === 'self_employed' ? 'badge-blue' : ($employment['status'] === 'unemployed' ? 'badge-red' : 'badge-purple')) ?>">
                                <?= e(str_replace('_', ' ', ucfirst($employment['status']))) ?>
                            </span>
                            <?php if ((int) $employment['job_relevance_rating']): ?>
                                <span class="badge-amber">Relevance: <?= (int) $employment['job_relevance_rating'] ?>/5</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($employment['job_title']): ?>
                            <p class="text-sm text-ink-700"><span class="font-semibold"><?= e($employment['job_title']) ?></span> <?= $employment['employer'] ? '&middot; ' . e($employment['employer']) : '' ?></p>
                        <?php endif; ?>
                        <?php if ($employment['sector_name']): ?>
                            <p class="text-sm text-ink-500">Sector: <?= e($employment['sector_name']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-ink-500">Updated: <?= date('M j, Y', strtotime($employment['updated_at'])) ?></p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-ink-400">
                        <i data-lucide="briefcase" class="w-10 h-10 mx-auto mb-2"></i>
                        <p class="text-sm">Please provide your current employment information.</p>
                        <a href="<?= url('graduate/employment') ?>" class="btn-primary mt-4 !py-2">Add Employment Info</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= (int) $unreadCount ?></p>
            <p class="text-xs text-ink-500">Unread Notifications</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $surveySubmitted ? 'Done' : 'Pending' ?></p>
            <p class="text-xs text-ink-500">Tracer Survey</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= (int) $competencyCount ?>/<?= (int) $totalCompetencies ?></p>
            <p class="text-xs text-ink-500">Competency Assessments</p>
        </div>
        <div class="kpi-card">
            <p class="text-2xl font-bold text-ink-900"><?= $curriculumSubmitted ? 'Done' : 'Pending' ?></p>
            <p class="text-xs text-ink-500">Curriculum Feedback</p>
        </div>
    </div>
</div>
