<?php
/** @var array $survey @var array $invitations @var array $stats */
$survey = $survey ?? [];
$invitations = $invitations ?? ['items' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
$stats = $stats ?? [];
$base = 'admin/surveys/' . (int) $survey['id'];
$statusBadge = [
    'pending'   => ['Pending', 'badge-gray'],
    'opened'    => ['Opened', 'badge-blue'],
    'completed' => ['Completed', 'badge-green'],
    'expired'   => ['Expired', 'badge-amber'],
    'revoked'   => ['Revoked', 'badge-red'],
];
$freshLink = \App\Core\Session::get('fresh_link', null);
\App\Core\Session::forget('fresh_link');
$shareIntro = "Hello!\n\nYou are invited to participate in the IAT Graduate Tracer Study.\n\nPlease complete the survey using the following secure link:\n\n";
$shareOutro = "\n\nThank you for your participation.";
?>
<div class="space-y-5">
    <?= \App\Core\View::partial('admin/surveys/_workspace-header', [
        'survey'          => $survey,
        'activeTab'       => 'invitations',
        'sectionCount'    => (int) ($sectionCount ?? 0),
        'questionCount'   => (int) ($questionCount ?? 0),
        'responseCount'   => (int) ($responseCount ?? 0),
        'invitationStats' => $invitationStats ?? $stats,
    ]) ?>

    <?php if ($freshLink): $freshMsg = $shareIntro . ($freshLink['link'] ?? '') . $shareOutro; ?>
        <div class="card border-brand-200 bg-brand-50/40">
            <div class="card-body flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-ink-900">
                        <i data-lucide="link-2" class="w-4 h-4 inline-block -mt-0.5 text-brand-700"></i>
                        New invitation link for <?= e($freshLink['name'] ?: 'graduate') ?>
                    </p>
                    <p class="text-xs text-ink-500 mt-1">This link is shown once. Copy it now and share it with the respondent.</p>
                    <code class="block text-xs text-brand-800 break-all mt-1.5"><?= e($freshLink['link'] ?? '') ?></code>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" class="btn-secondary h-9" data-copy="<?= e($freshLink['link'] ?? '') ?>"><i data-lucide="copy" class="w-4 h-4"></i> Copy link</button>
                    <button type="button" class="btn-secondary h-9" data-copy-message="<?= e($freshMsg) ?>"><i data-lucide="message-square" class="w-4 h-4"></i> Copy message</button>
                    <button type="button" class="btn-secondary h-9" data-share="<?= e($freshLink['link'] ?? '') ?>" data-share-title="<?= e($survey['title'] ?? 'Graduate Tracer Study') ?>" data-share-text="<?= e($freshMsg) ?>"><i data-lucide="share-2" class="w-4 h-4"></i> Share</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-ink-900">Invitation summary</h2>
                    <span class="badge-purple">No-login invitations</span>
                </div>
                <p class="text-xs text-ink-500 mt-1">
                    Each invitation issues a secure, one-time-use link to a graduate. Links are shown only once at creation.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= url($base . '/invitations/graduates') ?>" class="btn-success h-9"><i data-lucide="user-plus" class="w-4 h-4"></i> Invite Graduates</a>
            </div>
        </div>
        <div class="card-body grid grid-cols-3 sm:grid-cols-6 gap-3">
            <div class="rounded-lg bg-brand-50/60 px-3 py-2.5 text-center">
                <p class="text-lg font-bold text-ink-900 leading-none"><?= (int) ($savedCount ?? 0) ?></p>
                <p class="text-[11px] text-ink-500 mt-1">Saved targets</p>
            </div>
            <?php foreach ([
                'total' => 'Total', 'pending' => 'Pending', 'opened' => 'Opened',
                'completed' => 'Completed', 'expired' => 'Expired',
            ] as $key => $label): ?>
                <div class="rounded-lg bg-ink-50/60 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-ink-900 leading-none"><?= (int) ($stats[$key] ?? 0) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1"><?= e($label) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Graduate</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Issued</th>
                        <th>Expires</th>
                        <th>Opened</th>
                        <th>Completed</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($invitations['items'])): ?>
                    <tr><td colspan="8" class="!text-center !py-10 text-ink-500">No invitations yet. Click “Invite Graduates” to send secure links.</td></tr>
                <?php endif; ?>
                <?php foreach ($invitations['items'] as $i): ?>
                    <?php [$ilabel, $icls] = $statusBadge[$i['status']] ?? [$i['status'], 'badge-gray']; ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/graduates/' . (int) $i['graduate_id']) ?>" class="font-medium text-brand-700 hover:underline"><?= e($i['last_name'] . ', ' . $i['first_name']) ?></a>
                            <p class="text-xs text-ink-400"><?= e($i['student_number']) ?></p>
                        </td>
                        <td class="text-xs"><?= e($i['program_code']) ?> &middot; <?= (int) $i['batch_year'] ?></td>
                        <td><span class="<?= $icls ?>"><?= $ilabel ?></span></td>
                        <td class="text-xs"><?= e($i['created_at']) ?></td>
                        <td class="text-xs"><?= e($i['expires_at'] ?: '—') ?></td>
                        <td class="text-xs"><?= e($i['opened_at'] ?: '—') ?></td>
                        <td class="text-xs"><?= e($i['completed_at'] ?: '—') ?></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if (!empty($i['response_id'])): ?>
                                    <a href="<?= url($base . '/responses/' . (int) $i['response_id']) ?>" class="icon-btn text-emerald-600" title="View response"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                <?php else: ?>
                                    <button type="button" class="icon-btn text-ink-300" title="No response yet" disabled><i data-lucide="eye-off" class="w-4 h-4"></i></button>
                                <?php endif; ?>
                                <?php if ($i['status'] !== 'completed'): ?>
                                    <form method="POST" action="<?= url('admin/surveys/invitations/' . (int) $i['id'] . '/resend') ?>" title="Send a fresh link by email">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn" title="Resend link"><i data-lucide="mail" class="w-4 h-4"></i></button>
                                    </form>
                                    <form method="POST" action="<?= url('admin/surveys/invitations/' . (int) $i['id'] . '/regenerate') ?>" title="Issue a new link">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn" title="Regenerate link"><i data-lucide="key-round" class="w-4 h-4"></i></button>
                                    </form>
                                    <?php if ($i['status'] !== 'revoked'): ?>
                                        <form method="POST" action="<?= url('admin/surveys/invitations/' . (int) $i['id'] . '/revoke') ?>" onsubmit="return confirmAction('Revoke this invitation? The link will stop working.', this);">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Revoke"><i data-lucide="ban" class="w-4 h-4"></i></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $invitations,
            'path'      => $base . '/invitations',
            'query'     => [],
        ]) ?>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function copy(text, ok) {
            window.copyText(text).then(function () { window.toast(ok || 'Copied to clipboard.', 'success'); }).catch(function () { window.toast('Could not copy.', 'error'); });
        }
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () { copy(btn.dataset.copy, 'Link copied!'); });
        });
        document.querySelectorAll('[data-copy-message]').forEach(function (btn) {
            btn.addEventListener('click', function () { copy(btn.dataset.copyMessage, 'Message copied — paste it anywhere.'); });
        });
        document.querySelectorAll('[data-share]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: btn.dataset.shareTitle, text: btn.dataset.shareText, url: btn.dataset.share }).catch(function () {});
                } else {
                    copy(btn.dataset.share, 'Link copied!');
                }
            });
        });
    });
</script>