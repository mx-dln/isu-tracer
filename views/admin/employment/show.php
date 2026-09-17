<?php
/** @var array $profile @var array $graduate @var array $history @var array $allProfiles @var array $sectors */
$p = $profile ?? [];
$g = $graduate ?? [];
$history = $history ?? [];
$allProfiles = $allProfiles ?? [];
$sectors = $sectors ?? [];
$old = \App\Core\Session::get('_old_input', []);
$value = static function (string $key, $default = '') use ($p, $old) {
    return isset($old[$key]) ? $old[$key] : ($p[$key] ?? $default);
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

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900"><?= e($g['last_name']) ?>, <?= e($g['first_name']) ?></h2>
            <p class="text-sm text-ink-500"><?= e($g['student_number']) ?> &middot; <?= e($g['program_code']) ?> &middot; Batch <?= (int) $g['batch_year'] ?></p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="<?= url('admin/employment/' . (int) $p['id'] . '/sync') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary" onclick="return confirmAction('Rebuild this profile from the latest survey response?', this);">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync from Survey
                </button>
            </form>
            <a href="<?= url('admin/employment') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
        </div>
    </div>

    <!-- Current status badges -->
    <div class="flex flex-wrap items-center gap-2">
        <?php if (isset($statusLabel[$p['status']])): ?>
            <span class="<?= $statusLabel[$p['status']][1] ?>"><?= $statusLabel[$p['status']][0] ?></span>
        <?php else: ?>
            <span class="badge-gray"><?= e($p['status'] ?? 'Unknown') ?></span>
        <?php endif; ?>
        <?php if ($p['job_relevance_rating']): ?>
            <span class="badge-amber">Relevance: <?= (int) $p['job_relevance_rating'] ?>/5</span>
        <?php endif; ?>
        <?php if ($p['is_related_to_program'] !== null): ?>
            <span class="<?= $p['is_related_to_program'] ? 'badge-green' : 'badge-red' ?>">
                <?= $p['is_related_to_program'] ? 'Related to program' : 'Not related to program' ?>
            </span>
        <?php endif; ?>
        <?php if ($p['is_demo']): ?><span class="badge-gray">DEMO DATA</span><?php endif; ?>
        <span class="badge-blue">Updated: <?= date('M j, Y', strtotime($p['updated_at'])) ?></span>
    </div>

    <!-- Edit profile form -->
    <form method="POST" action="<?= url('admin/employment/' . (int) $p['id']) ?>" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="card-header"><h3 class="font-semibold text-ink-800">Edit Employment Profile</h3></div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="label" for="status">Employment Status *</label>
                <select name="status" id="status" class="input">
                    <?php foreach ($statusLabel as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $value('status', 'unemployed') === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="job_title">Job Title</label>
                <input type="text" name="job_title" id="job_title" class="input" value="<?= e($value('job_title')) ?>">
            </div>
            <div>
                <label class="label" for="employer">Employer</label>
                <input type="text" name="employer" id="employer" class="input" value="<?= e($value('employer')) ?>">
            </div>
            <div>
                <label class="label" for="sector_id">Industry Sector</label>
                <select name="sector_id" id="sector_id" class="input">
                    <option value="">— Select —</option>
                    <?php foreach ($sectors as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $value('sector_id') === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="employment_type">Employment Type</label>
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
                <label class="label" for="salary_range">Salary Range</label>
                <input type="text" name="salary_range" id="salary_range" class="input" value="<?= e($value('salary_range')) ?>">
            </div>
            <div>
                <label class="label" for="job_relevance_rating">Job Relevance (1–5)</label>
                <input type="number" name="job_relevance_rating" id="job_relevance_rating" min="1" max="5" class="input" value="<?= e($value('job_relevance_rating')) ?>">
            </div>
            <div>
                <label class="label" for="is_related_to_program">Related to Program?</label>
                <select name="is_related_to_program" id="is_related_to_program" class="input">
                    <option value="">Not indicated</option>
                    <option value="1" <?= $value('is_related_to_program') === '1' || $value('is_related_to_program') === 1 ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $value('is_related_to_program') === '0' || $value('is_related_to_program') === 0 ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label" for="job_description">Job Description</label>
                <textarea name="job_description" id="job_description" rows="3" class="input"><?= e($value('job_description')) ?></textarea>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label" for="proof_image_url">Proof Image Link</label>
                <input type="url" name="proof_image_url" id="proof_image_url" class="input" placeholder="https://..." value="<?= e($value('proof_image_url')) ?>">
                <p class="text-xs text-ink-500 mt-1">Optional link to employment proof hosted outside the system.</p>
            </div>
        </div>
        <div class="card-footer flex justify-end">
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Changes</button>
        </div>
    </form>

    <?php if (!empty($p['proof_image_path']) || !empty($p['proof_image_url'])): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-ink-800">Proof of Employment</h3>
            </div>
            <div class="card-body">
                <div class="flex flex-col sm:flex-row gap-4">
                    <?php if (!empty($p['proof_image_path'])):
                        $proofUrl = url('admin/employment/' . (int) $p['id'] . '/proof'); ?>
                        <button type="button" class="block rounded-lg border border-ink-200 bg-ink-50 overflow-hidden shrink-0 cursor-zoom-in" style="width: 180px; height: 135px;" data-proof-zoom="<?= e($proofUrl) ?>" data-proof-title="Proof of employment">
                            <img src="<?= e($proofUrl) ?>" alt="Proof of employment" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </button>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink-900">Graduate submitted proof for this employment profile.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <?php if (!empty($p['proof_image_path'])): ?>
                                <button type="button" class="btn-secondary h-9" data-proof-zoom="<?= e($proofUrl) ?>" data-proof-title="Proof of employment">
                                    <i data-lucide="zoom-in" class="w-4 h-4"></i> Zoom uploaded image
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($p['proof_image_url'])): ?>
                                <a href="<?= e($p['proof_image_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-secondary h-9">
                                    <i data-lucide="external-link" class="w-4 h-4"></i> Open proof link
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                        <th>Related</th>
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

<div id="proof-zoom-modal" class="hidden fixed inset-0 bg-ink-950/85 p-4" style="z-index: 120;" aria-hidden="true">
    <div class="h-full w-full flex flex-col">
        <div class="flex items-center justify-between gap-3 text-white mb-3">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-white/60">Employment proof</p>
                <p id="proof-zoom-title" class="text-sm font-semibold truncate">Proof image</p>
            </div>
            <button type="button" id="proof-zoom-close" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/20" aria-label="Close image preview">
                <i data-lucide="x" class="w-4 h-4"></i>
                Close
            </button>
        </div>
        <button type="button" id="proof-zoom-backdrop" class="flex-1 min-h-0 rounded-lg bg-black/30 p-2 cursor-zoom-out" aria-label="Close image preview">
            <img id="proof-zoom-image" src="" alt="Employment proof preview" class="m-auto rounded-lg shadow-2xl" style="max-width: 100%; max-height: 100%; object-fit: contain;">
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('proof-zoom-modal');
        var image = document.getElementById('proof-zoom-image');
        var title = document.getElementById('proof-zoom-title');
        var close = document.getElementById('proof-zoom-close');
        var backdrop = document.getElementById('proof-zoom-backdrop');
        if (!modal || !image || !title) return;

        function openZoom(src, label) {
            image.src = src;
            title.textContent = label || 'Proof image';
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            if (close) close.focus();
        }

        function closeZoom() {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            image.src = '';
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-proof-zoom]').forEach(function (button) {
            button.addEventListener('click', function () {
                openZoom(button.dataset.proofZoom || '', button.dataset.proofTitle || '');
            });
        });
        if (close) close.addEventListener('click', closeZoom);
        if (backdrop) backdrop.addEventListener('click', closeZoom);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeZoom();
            }
        });
    });
</script>
