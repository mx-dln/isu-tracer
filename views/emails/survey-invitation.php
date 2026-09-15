<?php
/**
 * Reusable survey-invitation email (HTML).
 * Pass $vars: graduate_name, survey_title, survey_description, survey_start_date,
 * survey_end_date, invitation_url, institution_name, campus_name, institute_name,
 * custom_message, job_link.
 * @var array $vars
 */
$v = $vars ?? [];
$grad  = $v['graduate_name'] ?? '';
$title = $v['survey_title'] ?? 'Graduate Tracer Study';
$desc  = $v['survey_description'] ?? '';
$start = $v['survey_start_date'] ?? '';
$end   = $v['survey_end_date'] ?? '';
$url   = $v['invitation_url'] ?? '#';
$univ  = $v['institution_name'] ?? 'Isabela State University';
$campus = $v['campus_name'] ?? 'Cauayan Campus';
$inst  = $v['institute_name'] ?? 'Institute of Agricultural Technology';
$custom = trim((string) ($v['custom_message'] ?? ''));
$jobLink = trim((string) ($v['job_link'] ?? ''));
$period = ($start !== '' && $end !== '') ? $start . ' – ' . $end : ($start !== '' ? 'Starting ' . $start : '');
?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
    <div style="background:#1e1b4b;color:#fff;padding:20px 24px">
        <h2 style="margin:0;font-size:18px">IAT Graduate Tracer Study – Survey Invitation</h2>
    </div>
    <div style="padding:24px;color:#1f2937;font-size:14px;line-height:1.6">
        <p>Dear <?= e($grad ?: 'Graduate') ?>,</p>
        <p>You are invited to participate in the <?= e($univ) ?> – <?= e($campus) ?> <?= e($inst) ?> Graduate Tracer Study.</p>
        <p>Please log in to your existing Graduate Account and complete the following survey:</p>
        <p style="font-weight:bold;font-size:16px"><?= e($title) ?></p>
        <?php if ($desc !== ''): ?>
            <p style="color:#6b7280"><?= nl2br(e($desc)) ?></p>
        <?php endif; ?>
        <?php if ($period !== ''): ?>
            <p style="color:#6b7280">Survey Period: <?= e($period) ?></p>
        <?php endif; ?>
        <?php if ($custom !== ''): ?>
            <div style="margin:18px 0;padding:14px 16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px">
                <p style="margin:0;font-weight:bold;color:#111827">Additional message</p>
                <p style="margin:8px 0 0;color:#374151"><?= nl2br(e($custom)) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($jobLink !== ''): ?>
            <div style="margin:18px 0;padding:14px 16px;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px">
                <p style="margin:0 0 10px;font-weight:bold;color:#065f46">Job hiring opportunity</p>
                <a href="<?= e($jobLink) ?>" style="background:#047857;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block">View Job Hiring</a>
                <p style="font-size:12px;color:#047857;word-break:break-all;margin:10px 0 0"><?= e($jobLink) ?></p>
            </div>
        <?php endif; ?>
        <p style="margin:24px 0">
            <a href="<?= e($url) ?>" style="background:#4338ca;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block">Complete Survey</a>
        </p>
        <p style="font-size:12px;color:#6b7280">Survey link:<br><a href="<?= e($url) ?>" style="color:#4338ca;word-break:break-all"><?= e($url) ?></a></p>
        <p style="font-size:12px;color:#6b7280">Your existing Graduate Account is used to access the survey. You do not need to create another account.</p>
        <p>Thank you for your participation.</p>
        <p style="margin:0"><?= e($inst) ?><br><?= e($univ) ?><br><?= e($campus) ?></p>
    </div>
    <div style="background:#f9fafb;padding:12px 24px;font-size:11px;color:#6b7280"><?= e($univ) ?> &middot; <?= e($campus) ?></div>
</div>
