<?php
/** @var ?array $profile @var array $history @var array $sectors */
$profile = $profile ?? null;
$history = $history ?? [];
$sectors = $sectors ?? [];
$old = \App\Core\Session::get('_old_input', []);
$value = static function (string $key, $default = '') use ($profile, $old) {
    return isset($old[$key]) ? $old[$key] : ($profile[$key] ?? $default);
};

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

    <!-- Current employment form -->
    <form method="POST" action="<?= url('graduate/employment') ?>" class="card">
        <?= csrf_field() ?>
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Current Employment</h3>
            <?php if ($profile): ?>
                <span class="badge-blue">Updated: <?= date('M j, Y', strtotime($profile['updated_at'])) ?></span>
            <?php else: ?>
                <span class="badge-amber">Not yet provided</span>
            <?php endif; ?>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="label" for="status">Employment Status *</label>
                <select name="status" id="status" class="input" required>
                    <?php foreach ($statusLabel as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $value('status', 'unemployed') === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="job_title">Job Title / Position</label>
                <input type="text" name="job_title" id="job_title" class="input" value="<?= e($value('job_title')) ?>">
            </div>
            <div>
                <label class="label" for="employer">Employer / Company</label>
                <input type="text" name="employer" id="employer" class="input" value="<?= e($value('employer')) ?>">
            </div>
            <div>
                <label class="label" for="sector_id">Industry / Employment Sector</label>
                <select name="sector_id" id="sector_id" class="input">
                    <option value="">— Select —</option>
                    <?php foreach ($sectors as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $value('sector_id') === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="employment_type">Type of Employment</label>
                <select name="employment_type" id="employment_type" class="input">
                    <option value="">— Select —</option>
                    <?php foreach ($typeLabel as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $value('employment_type') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="work_location">Work Location</label>
                <input type="text" name="work_location" id="work_location" class="input" value="<?= e($value('work_location')) ?>">
            </div>
            <div>
                <label class="label" for="date_hired">Date Hired</label>
                <input type="date" name="date_hired" id="date_hired" class="input" value="<?= e($value('date_hired')) ?>">
            </div>
            <div>
                <label class="label" for="first_employment_date">First Employment Date</label>
                <input type="date" name="first_employment_date" id="first_employment_date" class="input" value="<?= e($value('first_employment_date')) ?>">
            </div>
            <div>
                <label class="label" for="salary_range">Monthly Salary Range (Php)</label>
                <input type="text" name="salary_range" id="salary_range" class="input" value="<?= e($value('salary_range')) ?>">
            </div>
            <div>
                <label class="label" for="job_relevance_rating">Job Relevance (1–5)</label>
                <input type="number" name="job_relevance_rating" id="job_relevance_rating" min="1" max="5" class="input" value="<?= e($value('job_relevance_rating')) ?>">
            </div>
            <div>
                <label class="label" for="is_related_to_program">Related to your program?</label>
                <select name="is_related_to_program" id="is_related_to_program" class="input">
                    <option value="">Not indicated</option>
                    <option value="1" <?= $value('is_related_to_program') === '1' || $value('is_related_to_program') === 1 ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $value('is_related_to_program') === '0' || $value('is_related_to_program') === 0 ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label" for="job_description">Brief Description of Job Duties</label>
                <textarea name="job_description" id="job_description" rows="3" class="input"><?= e($value('job_description')) ?></textarea>
            </div>
        </div>
        <div class="card-footer flex justify-end">
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Employment Info</button>
        </div>
    </form>

    <!-- Work history -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-ink-800">Work History</h3></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Employer</th>
                            <th>Start</th>
                            <th>End</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No work history entries yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="font-medium text-ink-800"><?= e($h['job_title'] ?: '—') ?></td>
                            <td><?= e($h['employer'] ?: '—') ?></td>
                            <td><?= e($h['start_date'] ?: '—') ?></td>
                            <td>
                                <?= e($h['end_date'] ?: '—') ?>
                                <?php if ($h['is_current']): ?><span class="badge-green ml-1">Current</span><?php endif; ?>
                            </td>
                            <td class="text-right">
                                <form method="POST" action="<?= url('graduate/employment/history/' . (int) $h['id']) ?>" onsubmit="return confirmAction('Remove this work history entry?', this);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Remove"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add work history -->
        <form method="POST" action="<?= url('graduate/employment/history') ?>" class="card h-fit">
            <?= csrf_field() ?>
            <div class="card-header"><h3 class="font-semibold text-ink-800">Add Work History Entry</h3></div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="h-job_title">Job Title *</label>
                    <input type="text" name="job_title" id="h-job_title" class="input" required>
                </div>
                <div>
                    <label class="label" for="h-employer">Employer *</label>
                    <input type="text" name="employer" id="h-employer" class="input" required>
                </div>
                <div>
                    <label class="label" for="h-sector_id">Sector</label>
                    <select name="sector_id" id="h-sector_id" class="input">
                        <option value="">— Select —</option>
                        <?php foreach ($sectors as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="h-employment_type">Type</label>
                    <select name="employment_type" id="h-employment_type" class="input">
                        <option value="">— Select —</option>
                        <?php foreach ($typeLabel as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="h-start_date">Start Date</label>
                    <input type="date" name="start_date" id="h-start_date" class="input">
                </div>
                <div>
                    <label class="label" for="h-end_date">End Date</label>
                    <input type="date" name="end_date" id="h-end_date" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="is_current" value="1" class="rounded"> Currently employed here
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="h-notes">Notes</label>
                    <textarea name="notes" id="h-notes" rows="2" class="input"></textarea>
                </div>
            </div>
            <div class="card-footer flex justify-end">
                <button type="submit" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Entry</button>
            </div>
        </form>
    </div>
</div>
