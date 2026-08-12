<?php
/** @var array $survey @var array $links @var int $emailed @var array $sentIds @var array $summary */
$survey = $survey ?? [];
$links = $links ?? [];
$emailed = $emailed ?? 0;
$sentIds = $sentIds ?? [];
$summary = $summary ?? [];
$base = 'admin/surveys/' . (int) $survey['id'];

$univ = (string) setting('university_name', 'Isabela State University');
$campus = (string) setting('campus_name', 'Cauayan Campus');
$institute = (string) setting('institute_name', 'Institute of Agricultural Technology');
$shareIntro = "Hello!\n\nYou are invited to participate in the {$univ} – {$campus} {$institute} Graduate Tracer Study.\n\nPlease complete the survey using the following secure link:\n\n";
$shareOutro = "\n\nThank you for your participation.";
?>
<div class="space-y-5">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="text-lg font-bold text-ink-900">Invitation Links Generated</h2>
                <p class="text-xs text-ink-500 mt-1">
                    These links are shown <strong>once</strong>. Copy them now — the system only stores a hash, so a link cannot be recovered later (use “Regenerate” instead).
                    Share via Messenger, SMS, Viber, WhatsApp, email, or any platform by copying the link.
                    <?php if ($emailed): ?> <span class="text-emerald-600"><?= $emailed ?> email(s) sent.</span><?php endif; ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary h-9" id="copy-all-links" data-links="<?= e(implode("\n", array_column($links, 'link'))) ?>"><i data-lucide="copy" class="w-4 h-4"></i> Copy all links</button>
                <a href="<?= url($base . '/invitations') ?>" class="btn-primary h-9"><i data-lucide="check" class="w-4 h-4"></i> Done</a>
            </div>
        </div>
    </div>

    <?php if ($summary): ?>
        <div class="card">
            <div class="card-body grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="rounded-lg bg-ink-50/60 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-ink-900 leading-none"><?= (int) ($summary['generated'] ?? 0) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">Invitations generated</p>
                </div>
                <div class="rounded-lg bg-ink-50/60 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-ink-900 leading-none"><?= (int) ($summary['skipped'] ?? 0) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">Skipped</p>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-emerald-700 leading-none"><?= (int) ($summary['email_sent'] ?? 0) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">Email sent</p>
                </div>
                <div class="rounded-lg bg-amber-50 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-amber-700 leading-none"><?= (int) (($summary['email_failed'] ?? 0) + ($summary['email_skipped'] ?? 0)) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">Email failed/skipped</p>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-emerald-700 leading-none"><?= (int) ($summary['sms_sent'] ?? 0) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">SMS sent</p>
                </div>
                <div class="rounded-lg bg-amber-50 px-3 py-2.5 text-center">
                    <p class="text-lg font-bold text-amber-700 leading-none"><?= (int) (($summary['sms_failed'] ?? 0) + ($summary['sms_skipped'] ?? 0)) ?></p>
                    <p class="text-[11px] text-ink-500 mt-1">SMS failed/skipped</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Graduate</th>
                        <th>Email</th>
                        <th>One-time link</th>
                        <th>Status</th>
                        <th class="text-right">Copy &amp; Share</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($links as $l): $shareMsg = $shareIntro . $l['link'] . $shareOutro; ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($l['name']) ?></td>
                        <td class="text-xs"><?= e($l['email'] ?: '—') ?></td>
                        <td class="!whitespace-normal">
                            <code class="text-xs text-brand-800 break-all"><?= e($l['link']) ?></code>
                        </td>
                        <td>
                            <?php if (in_array($l['id'], $sentIds, true)): ?>
                                <span class="badge-green">Emailed</span>
                            <?php else: ?>
                                <span class="badge-amber">Copy to share</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" class="icon-btn" title="Copy link" aria-label="Copy link" data-copy="<?= e($l['link']) ?>"><i data-lucide="link" class="w-4 h-4"></i></button>
                                <button type="button" class="icon-btn" title="Copy message" aria-label="Copy message" data-copy-message="<?= e($shareMsg) ?>"><i data-lucide="message-square" class="w-4 h-4"></i></button>
                                <button type="button" class="icon-btn" title="Share" aria-label="Share" data-share="<?= e($l['link']) ?>" data-share-title="<?= e($survey['title'] ?? 'Graduate Tracer Study') ?>" data-share-text="<?= e($shareMsg) ?>"><i data-lucide="share-2" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
        var all = document.getElementById('copy-all-links');
        if (all) all.addEventListener('click', function () { copy(all.dataset.links, 'All links copied!'); });
    });
</script>