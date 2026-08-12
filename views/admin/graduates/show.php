<?php
/** @var array $graduate @var array $employment @var int $surveyCount */
$g = $graduate ?? [];
$employment = $employment ?? ['profile' => null, 'history' => []];
$profile = $employment['profile'];
$history = $employment['history'];
$surveyCount = $surveyCount ?? 0;

$statusLabel = [
    'employed'       => ['Employed', 'badge-green'],
    'self_employed'  => ['Self-Employed', 'badge-blue'],
    'unemployed'     => ['Unemployed', 'badge-red'],
    'further_studies'=> ['Further Studies', 'badge-amber'],
];
$typeLabel = [
    'regular' => 'Regular', 'contractual' => 'Contractual', 'temporary' => 'Temporary',
    'casual' => 'Casual', 'part_time' => 'Part-time', 'self_employed' => 'Self-employed',
];
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-brand-700 text-white flex items-center justify-center text-xl font-bold uppercase">
                <?= e(mb_substr($g['first_name'] ?? '?', 0, 1)) ?><?= e(mb_substr($g['last_name'] ?? '?', 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-ink-900">
                    <?= e($g['first_name'] ?? '') ?> <?= e($g['middle_name'] ?? '') ?> <?= e($g['last_name'] ?? '') ?><?= !empty($g['suffix']) ? ' ' . e($g['suffix']) : '' ?>
                </h2>
                <p class="text-sm text-ink-500"><?= e($g['student_number'] ?? '') ?> &middot; <?= e($g['program_code'] ?? '') ?> &middot; Batch <?= (int) ($g['batch_year'] ?? 0) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/graduates/' . (int) $g['id'] . '/edit') ?>" class="btn-secondary"><i data-lucide="pencil" class="w-4 h-4"></i> Edit</a>
            <a href="<?= url('admin/graduates') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
        </div>
    </div>

    <!-- Status badges -->
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($g['is_validated']): ?>
            <span class="badge-green"><i data-lucide="badge-check" class="w-3 h-3"></i> Validated</span>
        <?php else: ?>
            <span class="badge-amber"><i data-lucide="clock" class="w-3 h-3"></i> Pending Validation</span>
        <?php endif; ?>
        <?php if ($g['is_demo']): ?>
            <span class="badge-gray">DEMO DATA</span>
        <?php endif; ?>
        <?php if ($profile && isset($statusLabel[$profile['status']])): ?>
            <span class="<?= $statusLabel[$profile['status']][1] ?>"><?= $statusLabel[$profile['status']][0] ?></span>
        <?php else: ?>
            <span class="badge-gray">No Employment Data</span>
        <?php endif; ?>
        <span class="badge-blue"><i data-lucide="clipboard-check" class="w-3 h-3"></i> <?= (int) $surveyCount ?> survey response<?= $surveyCount === 1 ? '' : 's' ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Personal details -->
        <div class="card lg:col-span-1">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Personal Information</h3></div>
            <div class="card-body space-y-3 text-sm">
                <?php
                $rows = [
                    'Email'          => $g['email'] ?? null,
                    'Contact Number' => $g['contact_number'] ?? null,
                    'Sex'            => $g['sex'] ?? null,
                    'Birth Date'     => $g['birth_date'] ?? null,
                    'Civil Status'   => $g['civil_status'] ?? null,
                    'Address'        => $g['address'] ?? null,
                    'Municipality'   => $g['municipality'] ?? null,
                    'Province'       => $g['province'] ?? null,
                    'Program'        => $g['program_name'] ?? null,
                    'Batch'          => ($g['batch_year'] ?? null) ? (int) $g['batch_year'] : null,
                    'Graduation Year'=> $g['graduation_year'] ?? null,
                    'Recorded'       => $g['created_at'] ?? null,
                ];
                ?>
                <?php foreach ($rows as $label => $value): ?>
                    <div class="flex justify-between gap-4 border-b border-ink-100 pb-2">
                        <span class="text-ink-500"><?= e($label) ?></span>
                        <span class="text-ink-800 font-medium text-right"><?= e($value ?: '—') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Current employment -->
        <div class="card lg:col-span-2">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Current Employment</h3></div>
            <div class="card-body">
                <?php if ($profile): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Job Title</p>
                            <p class="font-semibold text-ink-800"><?= e($profile['job_title'] ?: '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Employer</p>
                            <p class="font-semibold text-ink-800"><?= e($profile['employer'] ?: '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Industry Sector</p>
                            <p class="text-ink-800"><?= e($profile['sector_name'] ?: ($profile['sector_other'] ?: '—')) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Employment Type</p>
                            <p class="text-ink-800"><?= e($typeLabel[$profile['employment_type'] ?? ''] ?? '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Work Location</p>
                            <p class="text-ink-800"><?= e($profile['work_location'] ?: '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Salary Range</p>
                            <p class="text-ink-800"><?= e($profile['salary_range'] ?: '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Date Hired</p>
                            <p class="text-ink-800"><?= e($profile['date_hired'] ?: '—') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">First Employment Date</p>
                            <p class="text-ink-800"><?= e($profile['first_employment_date'] ?: '—') ?></p>
                        </div>
                    </div>
                    <hr class="border-ink-100 my-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Related to Program?</p>
                            <?php if ($profile['is_related_to_program'] === null): ?>
                                <span class="badge-gray">Not Indicated</span>
                            <?php else: ?>
                                <span class="<?= $profile['is_related_to_program'] ? 'badge-green' : 'badge-red' ?>">
                                    <?= $profile['is_related_to_program'] ? 'Yes' : 'No' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500 uppercase tracking-wide">Job Relevance Rating</p>
                            <?php if ($profile['job_relevance_rating']): ?>
                                <span class="badge-blue"><?= (int) $profile['job_relevance_rating'] ?>/5</span>
                            <?php else: ?>
                                <span class="badge-gray">Not Rated</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($profile['job_description']): ?>
                        <div class="mt-4">
                            <p class="text-xs text-ink-500 uppercase tracking-wide mb-1">Job Description</p>
                            <p class="text-sm text-ink-700"><?= nl2br(e($profile['job_description'])) ?></p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="py-10 text-center">
                        <i data-lucide="briefcase-x" class="w-10 h-10 mx-auto text-ink-300 mb-3"></i>
                        <p class="text-sm text-ink-500">No employment profile recorded for this graduate yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Employment history -->
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Employment History</h3></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Employer</th>
                        <th>Sector</th>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Related to Program</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="7" class="!text-center !py-8 text-ink-500">No employment history recorded.</td></tr>
                <?php endif; ?>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($h['job_title'] ?: '—') ?></td>
                        <td><?= e($h['employer'] ?: '—') ?></td>
                        <td><?= e($h['sector_name'] ?: '—') ?></td>
                        <td><?= e($h['employment_type'] ?: '—') ?></td>
                        <td><?= e($h['start_date'] ?: '—') ?></td>
                        <td>
                            <?= e($h['end_date'] ?: '—') ?>
                            <?php if ($h['is_current']): ?><span class="badge-green ml-1">Current</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($h['is_related_to_program'] === null): ?>
                                <span class="badge-gray">—</span>
                            <?php else: ?>
                                <span class="<?= $h['is_related_to_program'] ? 'badge-green' : 'badge-red' ?>"><?= $h['is_related_to_program'] ? 'Yes' : 'No' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
