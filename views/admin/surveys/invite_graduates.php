<?php
/**
 * Invite Graduates: filter -> select -> save selection -> review -> generate -> notify.
 * @var array $survey @var array $graduates @var string $search
 * @var int $programId @var int $graduationYear @var array $programs @var array $years
 * @var array $savedTargets @var int $savedCount @var bool $filtersActive
 * @var bool $generatePending @var array $generateTargets @var bool $sendEmail @var bool $sendSms
 */
$survey = $survey ?? [];
$graduates = $graduates ?? ['items' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
$search = $search ?? '';
$programId = (int) ($programId ?? 0);
$graduationYear = (int) ($graduationYear ?? 0);
$programs = $programs ?? [];
$years = $years ?? [];
$savedTargets = $savedTargets ?? [];
$savedCount = (int) ($savedCount ?? 0);
$filtersActive = (bool) ($filtersActive ?? false);
$generatePending = (bool) ($generatePending ?? false);
$generateTargets = $generateTargets ?? [];
$sendEmail = (bool) ($sendEmail ?? true);
$sendSms = (bool) ($sendSms ?? false);
$customMessage = (string) ($customMessage ?? '');
$jobLink = (string) ($jobLink ?? '');
$customRecipientScope = (string) ($customRecipientScope ?? 'all');
$customRecipientScope = in_array($customRecipientScope, ['all', 'unemployed'], true) ? $customRecipientScope : 'all';
$base = 'admin/surveys/' . (int) $survey['id'];
$generateUrl = url($base . '/invitations/generate');

// Derived summary of the saved selection (independent of the current filter).
$savedPrograms = [];
$savedYears = [];
foreach ($savedTargets as $t) {
    if (!empty($t['program_code'])) $savedPrograms[$t['program_code']] = 1;
    if (!empty($t['graduation_year'])) $savedYears[(int) $t['graduation_year']] = 1;
}
$savedSummary = trim(implode(', ', array_keys($savedPrograms)) . ($savedYears ? ' · Graduation Year ' . implode(', ', array_keys($savedYears)) : ''));

$targetList = $generatePending ? $generateTargets : $savedTargets;
?>
<div class="space-y-5">

    <!-- Header -->
    <div class="card">
        <div class="card-header">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-ink-900">Invite Graduates</h2>
                <p class="text-xs text-ink-500 mt-1">Filter graduates, save your target selection, review who is included, then generate invitations and send notifications.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= url($base . '/invitations') ?>" class="btn-secondary h-9"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to invitations</a>
            </div>
        </div>
    </div>

    <!-- Saved selection (distinct from the filter results) -->
    <div class="card <?= $savedCount > 0 ? 'border-brand-300 bg-brand-50/40' : '' ?>">
        <div class="card-body flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-brand-700"></i>
                    <h3 class="font-semibold text-ink-900">Saved Invitation Selection</h3>
                </div>
                <?php if ($savedCount > 0): ?>
                    <p class="text-sm text-ink-700 mt-1"><strong><?= number_format($savedCount) ?></strong> graduate<?= $savedCount === 1 ? '' : 's' ?> saved for this survey.</p>
                    <?php if ($savedSummary !== ''): ?><p class="text-xs text-ink-500 mt-0.5"><?= e($savedSummary) ?></p><?php endif; ?>
                    <p class="text-xs text-ink-500 mt-0.5">Saved selections persist and are independent of the current filter.</p>
                <?php else: ?>
                    <p class="text-sm text-ink-500 mt-1">No saved graduates yet. Select graduates below and click <strong>Save Selection</strong>.</p>
                <?php endif; ?>
            </div>
            <?php if ($savedCount > 0): ?>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button" class="btn-secondary h-9" data-open-modal="review-modal"><i data-lucide="list" class="w-4 h-4"></i> Review Selection</button>
                    <button type="button" class="btn-primary h-9" data-open-modal="generate-modal"><i data-lucide="send" class="w-4 h-4"></i> Generate Invitations</button>
                    <form method="POST" action="<?= url($base . '/invitations/selection/clear') ?>" onsubmit="return confirmAction('Clear the saved selection for this survey? No invitations will be deleted.', this);">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-secondary h-9 !text-red-600 hover:!bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i> Clear Selection</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current filter -->
    <div class="card">
        <form method="GET" action="<?= url($base . '/invitations/graduates') ?>" class="card-body flex flex-wrap items-end gap-3">
            <div class="w-40">
                <label class="label" for="f-program">Program</label>
                <select name="program_id" id="f-program" class="input h-10">
                    <option value="">All programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $programId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['code'] . ($p['name'] ? ' — ' . $p['name'] : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-40">
                <label class="label" for="f-year">Graduation Year</label>
                <select name="graduation_year" id="f-year" class="input h-10">
                    <option value="">All years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $graduationYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="label" for="f-search">Search</label>
                <input type="search" name="search" id="f-search" class="input h-10" placeholder="Name, student number, email" value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn-primary h-10"><i data-lucide="search" class="w-4 h-4"></i> Apply Filters</button>
            <?php if ($filtersActive): ?>
                <a href="<?= url($base . '/invitations/graduates') ?>" class="btn-secondary h-10"><i data-lucide="x" class="w-4 h-4"></i> Reset</a>
            <?php endif; ?>
        </form>
        <div class="px-5 pb-4">
            <p class="text-xs text-ink-500"><?= number_format((int) $graduates['total']) ?> graduate<?= (int) $graduates['total'] === 1 ? '' : 's' ?> match your filters.</p>
        </div>
    </div>

    <!-- Results + selection -->
    <div class="card">
        <div class="card-body flex flex-wrap items-center justify-between gap-3 border-b border-ink-100">
            <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                <input type="checkbox" id="check-all" class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Select all on this page
            </label>
            <div class="flex items-center gap-3">
                <span class="text-xs text-ink-500" id="selected-count">0 selected</span>
                <button type="button" class="btn-primary h-9" id="save-selection-btn" disabled><i data-lucide="save" class="w-4 h-4"></i> Save Selection</button>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th>Graduate</th>
                        <th>Student No.</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($graduates['items'])): ?>
                    <tr><td colspan="6" class="!text-center !py-10 text-ink-500">No graduates found.</td></tr>
                <?php endif; ?>
                <?php foreach ($graduates['items'] as $g): ?>
                    <tr class="<?= $g['already_completed'] ? 'opacity-40' : '' ?>">
                        <td>
                            <input type="checkbox" value="<?= (int) $g['id'] ?>" class="row-check w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                                   <?= $g['already_completed'] ? 'disabled' : '' ?>
                                   <?= $g['already_saved'] ? 'checked' : '' ?>>
                        </td>
                        <td class="font-medium text-ink-800"><?= e($g['last_name'] . ', ' . $g['first_name']) ?>
                            <?php if ($g['already_completed']): ?><span class="badge-green">responded</span><?php endif; ?>
                            <?php if ($g['already_saved']): ?><span class="badge-blue">saved</span><?php endif; ?>
                        </td>
                        <td class="text-xs"><?= e($g['student_number']) ?></td>
                        <td class="text-xs"><?= e($g['program_code']) ?></td>
                        <td class="text-xs"><?= (int) ($g['graduation_year'] ?? $g['batch_year']) ?></td>
                        <td class="text-xs text-ink-500"><?= e($g['email'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $graduates,
            'path'      => $base . '/invitations/graduates',
            'query'     => array_filter(['search' => $search, 'program_id' => $programId, 'graduation_year' => $graduationYear]),
        ]) ?>
    </div>
</div>

<!-- Review selection modal -->
<div id="review-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-ink-900/40" data-close-modal></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:m-auto sm:max-w-2xl sm:h-fit sm:max-h-[85vh] overflow-y-auto bg-white sm:rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100 sticky top-0 bg-white">
            <div>
                <h3 class="font-bold text-ink-900">Saved Selection — <?= number_format($savedCount) ?> Graduate<?= $savedCount === 1 ? '' : 's' ?></h3>
                <?php if ($savedSummary !== ''): ?><p class="text-xs text-ink-500 mt-0.5"><?= e($savedSummary) ?></p><?php endif; ?>
            </div>
            <button type="button" class="icon-btn" data-close-modal aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-5">
            <?php if (empty($savedTargets)): ?>
                <p class="text-sm text-ink-500 text-center py-6">No saved graduates yet.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($savedTargets as $t):
                        $status = match ($t['invitation_status'] ?? null) {
                            'completed' => ['Completed', 'badge-green'],
                            'pending', 'opened' => ['Invited', 'badge-blue'],
                            'expired' => ['Expired', 'badge-amber'],
                            'revoked' => ['Revoked', 'badge-red'],
                            default => ['Saved', 'badge-gray'],
                        }; ?>
                        <div class="flex items-center gap-3 rounded-lg border border-ink-100 px-3 py-2.5">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-ink-800"><?= e($t['last_name'] . ', ' . $t['first_name']) ?></p>
                                <p class="text-xs text-ink-500"><?= e($t['student_number']) ?> &middot; <?= e($t['program_code'] ?: '—') ?> &middot; <?= (int) ($t['graduation_year'] ?? 0) ?: '—' ?></p>
                                <?php if (!empty($t['email'])): ?><p class="text-xs text-ink-400"><?= e($t['email']) ?></p><?php endif; ?>
                            </div>
                            <span class="<?= $status[1] ?> shrink-0"><?= $status[0] ?></span>
                            <?php if (!$t['invitation_id']): ?>
                                <form method="POST" action="<?= url('admin/surveys/invitations/selection/' . (int) $t['target_id'] . '/remove') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Remove from saved selection"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Generate confirmation modal -->
<div id="generate-modal" class="fixed inset-0 z-50 hidden <?= $generatePending ? '' : '' ?>" data-generate-modal="<?= $generatePending ? 'open' : '' ?>">
    <div class="absolute inset-0 bg-ink-900/40" data-close-modal></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:m-auto sm:max-w-lg sm:h-fit sm:max-h-[85vh] overflow-y-auto bg-white sm:rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100 sticky top-0 bg-white">
            <h3 class="font-bold text-ink-900">Generate Invitations</h3>
            <button type="button" class="icon-btn" data-close-modal aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" action="<?= $generateUrl ?>" class="p-5 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="confirm" value="1">
            <p class="text-sm text-ink-700">You are about to generate invitations for <strong><?= number_format(count($targetList)) ?></strong> graduate<?= count($targetList) === 1 ? '' : 's' ?>.</p>
            <p class="text-xs text-ink-500">Graduates who already have an active or completed invitation will be skipped automatically.</p>

            <div class="rounded-lg border border-ink-200 bg-ink-50/60 p-3 max-h-40 overflow-y-auto">
                <?php foreach ($targetList as $t): ?>
                    <p class="text-sm text-ink-700 py-0.5">
                        <i data-lucide="user" class="w-4 h-4 inline-block -mt-0.5 text-ink-400"></i>
                        <?= e(($t['last_name'] ?? '') . ', ' . ($t['first_name'] ?? '')) ?>
                        <span class="text-xs text-ink-400"><?= e(($t['student_number'] ?? '') ? ' · ' . $t['student_number'] : '') ?></span>
                    </p>
                <?php endforeach; ?>
            </div>

            <div>
                <p class="text-sm font-medium text-ink-800 mb-2">Notification Method</p>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500" <?= $sendEmail ? 'checked' : '' ?>>
                        Email
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                        <input type="checkbox" name="send_sms" value="1" class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500" <?= $sendSms ? 'checked' : '' ?>>
                        SMS
                    </label>
                </div>
            </div>

            <div>
                <label class="label" for="custom_message">Custom message</label>
                <textarea name="custom_message" id="custom_message" rows="4" class="input" maxlength="1000" placeholder="Optional note to include in the email and SMS. Example: Please answer this tracer survey before Friday."><?= e($customMessage) ?></textarea>
                <p class="text-xs text-ink-500 mt-1">Email receives the full note. SMS uses a shortened version to help keep the message within one credit.</p>
            </div>

            <div>
                <label class="label" for="job_link">Job hiring link</label>
                <input type="url" name="job_link" id="job_link" class="input" maxlength="500" placeholder="https://example.com/job-posting or https://bit.ly/..." value="<?= e($jobLink) ?>">
                <p class="text-xs text-ink-500 mt-1">Optional. Add a hiring post or job opportunity link that fits the target graduates. Use a short link for SMS.</p>
                <a href="https://tinyurl.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline mt-1">
                    <i data-lucide="external-link" class="w-3 h-3"></i>
                    Shorten long job links with TinyURL
                </a>
                <p class="text-xs mt-1" id="sms-length-hint" data-sample-survey-url="<?= e(url('s/' . str_repeat('a', 64))) ?>"></p>
            </div>

            <div>
                <p class="text-sm font-medium text-ink-800 mb-2">Send custom message and job link to</p>
                <div class="grid gap-2">
                    <label class="flex items-start gap-2 rounded-lg border border-ink-200 px-3 py-2 cursor-pointer hover:bg-ink-50">
                        <input type="radio" name="custom_recipient_scope" value="all" class="mt-0.5 w-4 h-4 border-ink-300 text-brand-700 focus:ring-brand-500" <?= $customRecipientScope === 'all' ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-medium text-ink-800">All selected graduates</span>
                            <span class="block text-xs text-ink-500 mt-0.5">Everyone receives the custom message and job hiring link.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-ink-200 px-3 py-2 cursor-pointer hover:bg-ink-50">
                        <input type="radio" name="custom_recipient_scope" value="unemployed" class="mt-0.5 w-4 h-4 border-ink-300 text-brand-700 focus:ring-brand-500" <?= $customRecipientScope === 'unemployed' ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-medium text-ink-800">Unemployed graduates only</span>
                            <span class="block text-xs text-ink-500 mt-0.5">All selected graduates still receive the survey invitation, but only unemployed graduates receive the custom/job content.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" class="btn-secondary h-9" data-close-modal>Cancel</button>
                <button type="submit" class="btn-success h-9"><i data-lucide="send" class="w-4 h-4"></i> Generate &amp; Notify</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= url($base . '/invitations/selection') ?>" id="save-selection-form">
    <?= csrf_field() ?>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var all = document.getElementById('check-all');
        var rows = Array.from(document.querySelectorAll('.row-check'));
        var countEl = document.getElementById('selected-count');
        var saveBtn = document.getElementById('save-selection-btn');
        var customMessage = document.getElementById('custom_message');
        var jobLink = document.getElementById('job_link');
        var smsHint = document.getElementById('sms-length-hint');

        function update() {
            var n = rows.filter(function (r) { return r.checked; }).length;
            if (countEl) countEl.textContent = n + ' selected';
            if (saveBtn) {
                saveBtn.disabled = n === 0;
                saveBtn.classList.toggle('opacity-40', n === 0);
            }
            if (all) all.checked = rows.length > 0 && rows.every(function (r) { return r.checked; });
            all.indeterminate = !all.checked && rows.some(function (r) { return r.checked; });
        }
        if (all) all.addEventListener('change', function () {
            rows.forEach(function (r) { r.checked = all.checked; });
            update();
        });
        rows.forEach(function (r) { r.addEventListener('change', update); });

        if (saveBtn) saveBtn.addEventListener('click', function () {
            var form = document.getElementById('save-selection-form');
            form.querySelectorAll('input[name="graduate_ids[]"]').forEach(function (i) { i.remove(); });
            rows.forEach(function (r) {
                if (r.checked && !r.disabled) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'graduate_ids[]';
                    input.value = r.value;
                    form.appendChild(input);
                }
            });
            form.submit();
        });

        // Modal handling
        function closeModals() {
            document.querySelectorAll('[data-generate-modal]').forEach(function (m) { m.classList.add('hidden'); });
            document.getElementById('review-modal') && document.getElementById('review-modal').classList.add('hidden');
        }
        document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModals();
                var el = document.getElementById(btn.dataset.openModal);
                if (el) el.classList.remove('hidden');
            });
        });
        document.querySelectorAll('[data-close-modal]').forEach(function (el) {
            el.addEventListener('click', function () { closeModals(); });
        });
        // Pre-open the generate modal when a confirmation step is pending.
        document.querySelectorAll('#generate-modal[data-generate-modal="open"]').forEach(function (m) {
            closeModals();
            m.classList.remove('hidden');
        });

        function normalizeLink(value) {
            value = String(value || '').trim();
            if (!value) return '';
            return /^https?:\/\//i.test(value) ? value : 'https://' + value;
        }

        function updateSmsHint() {
            if (!smsHint) return;
            var surveyUrl = smsHint.dataset.sampleSurveyUrl || '';
            var link = normalizeLink(jobLink ? jobLink.value : '');
            var note = String(customMessage ? customMessage.value : '').replace(/[^\x20-\x7E]/g, ' ').replace(/\s+/g, ' ').trim();
            var message = 'ISU IAT Tracer:\n';
            var remaining = 160 - message.length - surveyUrl.length - (link ? (6 + link.length) : 0) - 2;
            if (note && remaining > 8) {
                message += note.slice(0, remaining) + '\n';
            }
            message += surveyUrl;
            if (link) message += '\nJob: ' + link;
            var len = message.length;
            smsHint.textContent = 'Estimated SMS length: ' + len + '/160 characters' + (len > 160 ? ' — use a shorter job link for one SMS credit.' : ' — should fit one SMS credit.');
            smsHint.className = 'text-xs mt-1 ' + (len > 160 ? 'text-amber-700' : 'text-ink-500');
        }
        if (customMessage) customMessage.addEventListener('input', updateSmsHint);
        if (jobLink) jobLink.addEventListener('input', updateSmsHint);

        update();
        updateSmsHint();
    });
</script>
