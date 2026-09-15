<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\Graduate;
use App\Models\Program;
use App\Models\Survey;
use App\Models\SurveyInvitation;
use App\Models\SurveyInvitationTarget;
use App\Services\Mailer;
use App\Services\SemaphoreService;

/**
 * Secure survey invitation management (public no-login respondents).
 *
 * Workflow: filter graduates -> save a target selection -> review it ->
 * generate invitations for the saved selection -> notify (email/SMS).
 * Saved selections and generated invitations are separate concepts.
 */
class SurveyInvitationController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $page = max(1, (int) $request->query('page', 1));
        $stats = SurveyInvitation::stats((int) $survey['id']);

        $this->view('admin/surveys/invitations', [
            'title'     => 'Invitations',
            'subtitle'  => $survey['title'],
            'survey'    => $survey,
            'invitations' => SurveyInvitation::paginate((int) $survey['id'], $page, 15),
            'stats'     => $stats,
            'invitationStats' => $stats,
            'savedCount' => SurveyInvitationTarget::count((int) $survey['id']),
            'sectionCount'  => Survey::sectionCount((int) $survey['id']),
            'questionCount' => Survey::questionCount((int) $survey['id']),
            'responseCount' => Survey::responseCount((int) $survey['id']),
        ]);
    }

    // ------------------------------------------------------------------
    // Graduate picker + saved-selection management
    // ------------------------------------------------------------------

    public function graduates(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $this->view('admin/surveys/invite_graduates', $this->graduatesPageData($request, $survey));
    }

    /**
     * Save the currently selected graduates as the survey's target selection.
     * Does NOT create invitations or send notifications.
     */
    public function saveSelection(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        $graduateIds = (array) ($request->input('graduate_ids') ?? []);
        $graduateIds = array_values(array_unique(array_filter($graduateIds, 'is_numeric')));
        if (!$graduateIds) {
            $this->error('Select at least one graduate to save.', 'admin/surveys/' . (int) $survey['id'] . '/invitations/graduates');
        }

        $count = SurveyInvitationTarget::saveForSurvey((int) $survey['id'], $graduateIds, auth_id());
        if ($count === 0) {
            $this->error('No valid graduates were selected.', 'admin/surveys/' . (int) $survey['id'] . '/invitations/graduates');
        }

        $this->audit('save_invitation_selection', 'surveys', "Saved {$count} graduate(s) as invitation targets for survey {$survey['id']}.");
        $this->success("Invitation selection saved. {$count} graduates are now saved for this survey.", 'admin/surveys/' . (int) $survey['id'] . '/invitations/graduates');
    }

    /**
     * Remove a single graduate from the saved selection (only when no
     * invitation has been generated for them yet).
     */
    public function removeTarget(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $target = Database::fetch('SELECT * FROM survey_invitation_targets WHERE id = ?', [(int) $params['tid']]);
        if (!$target) {
            abort(404, 'Saved target not found.');
        }
        if (!SurveyInvitationTarget::remove((int) $target['survey_id'], (int) $target['graduate_id'])) {
            $this->error('This graduate already has an invitation and can no longer be removed from the saved selection.', 'admin/surveys/' . (int) $target['survey_id'] . '/invitations/graduates');
        }
        $this->audit('modify_invitation_selection', 'surveys', "Removed graduate {$target['graduate_id']} from saved selection for survey {$target['survey_id']}.");
        $this->success('Graduate removed from the saved selection.', 'admin/surveys/' . (int) $target['survey_id'] . '/invitations/graduates');
    }

    /**
     * Clear the entire saved selection. Invitations already generated are
     * NOT deleted.
     */
    public function clearTargets(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $count = SurveyInvitationTarget::clear((int) $survey['id']);
        $this->audit('clear_invitation_selection', 'surveys', "Cleared saved selection ({$count} graduate(s)) for survey {$survey['id']}.");
        $this->success('Saved selection cleared. Invitations were not affected.', 'admin/surveys/' . (int) $survey['id'] . '/invitations/graduates');
    }

    // ------------------------------------------------------------------
    // Invitation generation from the saved selection
    // ------------------------------------------------------------------

    public function generate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $surveyId = (int) $survey['id'];

        // Target source: explicitly posted ids (quick path) or the saved selection.
        $postedIds = (array) ($request->input('graduate_ids') ?? []);
        $postedIds = array_values(array_unique(array_filter($postedIds, 'is_numeric')));
        $targets = [];
        if ($postedIds) {
            foreach ($postedIds as $gid) {
                $g = Graduate::find((int) $gid);
                if ($g) {
                    $targets[(int) $gid] = $g;
                }
            }
            SurveyInvitationTarget::saveForSurvey($surveyId, array_keys($targets), auth_id());
        } else {
            foreach (SurveyInvitationTarget::forSurvey($surveyId) as $t) {
                $targets[(int) $t['graduate_id']] = $t;
            }
        }
        if (!$targets) {
            $this->error('No saved graduates to invite. Save a selection first.', 'admin/surveys/' . $surveyId . '/invitations/graduates');
        }

        $sendEmail = (int) ($request->input('send_email') ?? 0) === 1;
        $sendSms   = (int) ($request->input('send_sms') ?? 0) === 1;
        $confirmed = (int) ($request->input('confirm') ?? 0) === 1;
        $customMessage = trim((string) ($request->input('custom_message') ?? ''));
        $customMessage = mb_substr($customMessage, 0, 1000);
        $jobLink = $this->normalizeOptionalUrl((string) ($request->input('job_link') ?? ''));
        $customRecipientScope = in_array($request->input('custom_recipient_scope'), ['all', 'unemployed'], true)
            ? (string) $request->input('custom_recipient_scope')
            : 'all';

        if (!$confirmed) {
            // Confirmation step: show the target list + notification options.
            $pageData = $this->graduatesPageData($request, $survey);
            $this->view('admin/surveys/invite_graduates', array_merge($pageData, [
                'generatePending' => true,
                'generateTargets' => array_values($targets),
                'sendEmail'       => $sendEmail,
                'sendSms'         => $sendSms,
                'customMessage'   => $customMessage,
                'jobLink'         => $jobLink,
                'customRecipientScope' => $customRecipientScope,
            ]));
            return;
        }

        $expiryDays = max(1, (int) ($survey['invitation_expiry_days'] ?? 30));
        $links = [];
        $emailedIds = [];
        $summary = ['generated' => 0, 'skipped' => 0, 'skipped_reasons' => []];
        $summary += ['email_sent' => 0, 'email_failed' => 0, 'email_skipped' => 0, 'sms_sent' => 0, 'sms_failed' => 0, 'sms_skipped' => 0];

        foreach ($targets as $gid => $g) {
            // Duplicate protection: pending/opened/completed invitations are kept.
            $existing = SurveyInvitation::findBySurvey($surveyId, (int) $gid);
            if ($existing && in_array($existing['status'], ['pending', 'opened', 'completed'], true)) {
                $summary['skipped']++;
                $summary['skipped_reasons'][$existing['status']] = ($summary['skipped_reasons'][$existing['status']] ?? 0) + 1;
                continue;
            }

            [$invId, $token] = SurveyInvitation::issue($surveyId, (int) $gid, $expiryDays);
            $summary['generated']++;
            $links[] = [
                'id'    => (int) $invId,
                'name'  => trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? '')),
                'email' => $g['email'] ?? null,
                'token' => $token,
                'link'  => url('survey/respond/' . $token),
            ];
            $sendCustomContent = $customRecipientScope === 'all' || $this->isGraduateUnemployed((int) $gid);
            $messageForGraduate = $sendCustomContent ? $customMessage : '';
            $jobLinkForGraduate = $sendCustomContent ? $jobLink : '';

            if ($sendEmail) {
                $res = $this->sendInvitationEmail((int) $gid, $survey, $token, (int) $invId, $messageForGraduate, $jobLinkForGraduate);
                if (in_array($res['status'], ['sent', 'simulated'], true)) {
                    $summary['email_sent']++;
                    $emailedIds[] = (int) $invId;
                } elseif ($res['status'] === 'skipped') {
                    $summary['email_skipped']++;
                } else {
                    $summary['email_failed']++;
                }
            }
            if ($sendSms) {
                $res = $this->sendInvitationSms((int) $gid, $survey, $token, (int) $invId, $messageForGraduate, $jobLinkForGraduate);
                if (in_array($res['status'], ['sent', 'simulated'], true)) {
                    $summary['sms_sent']++;
                } elseif ($res['status'] === 'skipped') {
                    $summary['sms_skipped']++;
                } else {
                    $summary['sms_failed']++;
                }
            }
        }

        $this->audit('generate', 'surveys', "Generated {$summary['generated']} invitation(s) for survey {$surveyId}.");

        $this->view('admin/surveys/invite_links', [
            'title'    => 'Invitation Links',
            'subtitle' => $survey['title'],
            'survey'   => $survey,
            'links'    => $links,
            'emailed'  => $summary['email_sent'],
            'sentIds'  => $emailedIds,
            'summary'  => $summary,
        ]);
    }

    /**
     * Resend a notification for an existing invitation without duplicating it.
     * Accepts channels[] = email and/or sms.
     */
    public function notify(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $invitation = SurveyInvitation::find((int) $params['iid']);
        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }
        $survey = Survey::find((int) $invitation['survey_id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        $channels = (array) ($request->input('channels') ?? []);
        if (!$channels) {
            $this->error('Choose at least one notification channel.', 'admin/surveys/' . (int) $survey['id'] . '/invitations');
        }

        try {
            [$id, $token] = SurveyInvitation::rotate((int) $invitation['id'], max(1, (int) $survey['invitation_expiry_days']));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 'admin/surveys/' . (int) $survey['id'] . '/invitations');
        }

        $sent = [];
        if (in_array('email', $channels, true)) {
            $sent[] = 'email';
            $this->sendInvitationEmail((int) $invitation['graduate_id'], $survey, $token, (int) $invitation['id']);
        }
        if (in_array('sms', $channels, true)) {
            $sent[] = 'sms';
            $this->sendInvitationSms((int) $invitation['graduate_id'], $survey, $token, (int) $invitation['id']);
        }

        $this->audit('notify', 'surveys', "Re-sent notification(s) (" . implode(',', $sent) . ") for invitation {$invitation['id']}.");
        Session::put('fresh_link', [
            'name' => $this->graduateName((int) $invitation['graduate_id']),
            'id'   => (int) $invitation['id'],
            'link' => url('survey/respond/' . $token),
        ]);
        $this->success('Notification re-sent with a fresh link. Copy it from the banner above.', 'admin/surveys/' . (int) $survey['id'] . '/invitations');
    }

    public function revoke(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $invitation = SurveyInvitation::find((int) $params['iid']);
        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }
        SurveyInvitation::revoke((int) $invitation['id']);
        $this->audit('revoke', 'surveys', "Revoked invitation {$invitation['id']}.");
        $this->success('Invitation revoked. The link no longer works.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
    }

    public function regenerate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $invitation = SurveyInvitation::find((int) $params['iid']);
        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }
        $survey = Survey::find((int) $invitation['survey_id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        try {
            [$id, $token] = SurveyInvitation::rotate((int) $invitation['id'], max(1, (int) $survey['invitation_expiry_days']));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
        }

        $this->audit('regenerate', 'surveys', "Re-issued token for invitation {$id}.");
        Session::put('fresh_link', [
            'name' => $this->graduateName((int) $invitation['graduate_id']),
            'id'   => (int) $invitation['id'],
            'link' => url('survey/respond/' . $token),
        ]);
        $this->success('A new invitation link was generated. Copy it from the banner above.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
    }

    public function resend(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $invitation = SurveyInvitation::find((int) $params['iid']);
        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }
        if ((int) $invitation['graduate_id'] === 0 || $invitation['graduate_id'] === null) {
            $this->error('This invitation has no linked graduate email.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
        }
        $survey = Survey::find((int) $invitation['survey_id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        try {
            [$id, $token] = SurveyInvitation::rotate((int) $invitation['id'], max(1, (int) $survey['invitation_expiry_days']));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
        }

        if ($this->sendInvitationEmail((int) $invitation['graduate_id'], $survey, $token, (int) $invitation['id'])['status'] !== 'failed') {
            $this->audit('resend', 'surveys', "Resent invitation email for {$invitation['id']}.");
            Session::put('fresh_link', [
                'name' => $this->graduateName((int) $invitation['graduate_id']),
                'id'   => (int) $invitation['id'],
                'link' => url('survey/respond/' . $token),
            ]);
            $this->success('Invitation email re-sent with a fresh link. Copy it from the banner above.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
        }
        $this->error('Invitation email could not be sent.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
    }

    public function viewResponse(Request $request, array $params): void
    {
        $invitation = SurveyInvitation::find((int) $params['iid']);
        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }
        $response = \App\Models\SurveyResponse::findByInvitation((int) $invitation['id']);
        if (!$response) {
            $this->error('This invitation has not been completed yet.', 'admin/surveys/' . (int) $invitation['survey_id'] . '/invitations');
        }
        redirect('admin/surveys/' . (int) $invitation['survey_id'] . '/responses/' . (int) $response['id']);
    }

    // ------------------------------------------------------------------
    // Shared page data + notification delivery
    // ------------------------------------------------------------------

    private function graduatesPageData(Request $request, array $survey): array
    {
        $search = trim((string) $request->query('search'));
        $programId = (int) $request->query('program_id');
        $graduationYear = (int) $request->query('graduation_year');
        $page = max(1, (int) $request->query('page', 1));

        $filters = ['search' => $search];
        if ($programId > 0) {
            $filters['program_id'] = $programId;
        }
        if ($graduationYear > 0) {
            $filters['graduation_year'] = $graduationYear;
        }
        $graduates = Graduate::paginate($filters, $page, 50);

        $graduateIds = array_column($graduates['items'], 'id');
        $completed = [];
        if ($graduateIds) {
            $placeholders = implode(',', array_fill(0, count($graduateIds), '?'));
            $rows = Database::fetchAll(
                "SELECT r.graduate_id FROM survey_responses r WHERE r.survey_id = ? AND r.graduate_id IN ({$placeholders}) AND r.status = 'submitted'",
                array_merge([(int) $survey['id']], $graduateIds)
            );
            $completed = array_column($rows, 'graduate_id');
        }

        $savedTargets = SurveyInvitationTarget::forSurvey((int) $survey['id']);
        $savedIds = array_column($savedTargets, 'graduate_id');

        foreach ($graduates['items'] as &$g) {
            $g['already_completed'] = in_array((int) $g['id'], $completed, true);
            $g['already_saved'] = in_array((int) $g['id'], $savedIds, true);
        }
        unset($g);

        $years = [];
        foreach (Database::fetchAll('SELECT DISTINCT graduation_year FROM graduates WHERE deleted_at IS NULL AND graduation_year IS NOT NULL ORDER BY graduation_year DESC') as $y) {
            $years[] = (int) $y['graduation_year'];
        }

        return [
            'title'      => 'Invite Graduates',
            'subtitle'   => $survey['title'],
            'survey'     => $survey,
            'graduates'  => $graduates,
            'search'     => $search,
            'programId'  => $programId,
            'graduationYear' => $graduationYear,
            'programs'   => Program::all(),
            'years'      => $years,
            'savedTargets' => $savedTargets,
            'savedCount'   => count($savedTargets),
            'filtersActive' => ($search !== '' || $programId > 0 || $graduationYear > 0),
        ];
    }

    private function buildInvitationEmail(array $graduate, array $survey, string $token, string $customMessage = '', string $jobLink = ''): array
    {
        $subject = 'IAT Graduate Tracer Study – Survey Invitation';
        $link = url('survey/respond/' . $token);
        $vars = [
            'graduate_name'      => trim(($graduate['first_name'] ?? '') . ' ' . ($graduate['last_name'] ?? '')),
            'survey_title'       => $survey['title'],
            'survey_description' => $survey['description'] ?? '',
            'survey_start_date'  => $survey['start_date'] ?? '',
            'survey_end_date'    => $survey['end_date'] ?? '',
            'invitation_url'     => $link,
            'institution_name'   => (string) setting('university_name', 'Isabela State University'),
            'campus_name'        => (string) setting('campus_name', 'Cauayan Campus'),
            'institute_name'     => (string) setting('institute_name', 'Institute of Agricultural Technology'),
            'custom_message'     => $customMessage,
            'job_link'           => $jobLink,
        ];
        $html = View::partial('emails/survey-invitation', ['vars' => $vars]);
        $text = "Dear {$vars['graduate_name']},\n\n"
            . "You are invited to participate in the IAT Graduate Tracer Study.\n\n"
            . "Please complete the survey: {$vars['survey_title']}\n";
        if ($customMessage !== '') {
            $text .= "\n{$customMessage}\n";
        }
        if ($jobLink !== '') {
            $text .= "\nJob hiring link: {$jobLink}\n";
        }
        $text .= "\nSurvey link: {$link}\n\n"
            . "Thank you for your participation.\n"
            . "{$vars['institute_name']}\n{$vars['institution_name']}\n{$vars['campus_name']}";
        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    private function sendInvitationEmail(int $graduateId, array $survey, string $token, int $invitationId, string $customMessage = '', string $jobLink = ''): array
    {
        $graduate = Graduate::find($graduateId);
        $recipient = $graduate['email'] ?? '';
        if (!$graduate || $recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->logNotification((int) $survey['id'], $invitationId, $graduateId, 'email', $recipient, 'skipped', null, 'no_valid_email');
            return ['status' => 'skipped', 'error' => 'no_valid_email'];
        }

        if ((bool) config('notifications.test_mode')) {
            $this->logNotification((int) $survey['id'], $invitationId, $graduateId, 'email', $recipient, 'simulated', null, null);
            return ['status' => 'simulated', 'error' => null];
        }

        $email = $this->buildInvitationEmail($graduate, $survey, $token, $customMessage, $jobLink);
        try {
            $ok = Mailer::send($recipient, $email['subject'], $email['html'], [], $email['text']);
        } catch (\Throwable $e) {
            Logger::error('Invitation email failed: ' . $e->getMessage(), ['to' => $recipient]);
            $ok = false;
        }
        $status = $ok ? (config('mail.enabled') ? 'sent' : 'simulated') : 'failed';
        $this->logNotification((int) $survey['id'], $invitationId, $graduateId, 'email', $recipient, $status, null, $ok ? null : 'mail_failed');
        if ($ok) {
            Database::run('UPDATE survey_invitations SET sent_at = NOW() WHERE id = ?', [$invitationId]);
        }
        return ['status' => $status, 'error' => $ok ? null : 'mail_failed'];
    }

    private function sendInvitationSms(int $graduateId, array $survey, string $token, int $invitationId, string $customMessage = '', string $jobLink = ''): array
    {
        $graduate = Graduate::find($graduateId);
        $number = $graduate['contact_number'] ?? '';
        if (!$graduate || trim($number) === '') {
            $this->logNotification((int) $survey['id'], $invitationId, $graduateId, 'sms', $number, 'skipped', null, 'no_valid_mobile_number');
            return ['status' => 'skipped', 'error' => 'no_valid_mobile_number'];
        }

        // Keep the SMS well under 160 characters for one Semaphore credit and
        // leave the URL bare so mobile SMS apps detect it as tappable.
        $url = url('s/' . $token);
        $message = $this->buildInvitationSmsMessage($url, $customMessage, $jobLink);

        $res = (new SemaphoreService())->send($number, $message);
        $this->logNotification((int) $survey['id'], $invitationId, $graduateId, 'sms', $number, $res['status'], $res['message_id'], $res['error']);
        return $res;
    }

    private function buildInvitationSmsMessage(string $surveyUrl, string $customMessage = '', string $jobLink = ''): string
    {
        $prefix = 'ISU IAT Tracer:';
        $lines = [$prefix];

        $custom = trim(preg_replace('/[^\x20-\x7E]/u', ' ', $customMessage) ?? '');
        $custom = preg_replace('/\s+/', ' ', $custom) ?? '';
        if ($custom !== '') {
            $reserved = strlen($prefix) + 1 + strlen($surveyUrl);
            if ($jobLink !== '') {
                $reserved += 6 + strlen($jobLink);
            }
            $maxCustom = 160 - $reserved - 2;
            if ($maxCustom > 8) {
                $lines[] = mb_strimwidth($custom, 0, $maxCustom, '');
            }
        }

        $lines[] = $surveyUrl;
        if ($jobLink !== '') {
            $lines[] = 'Job: ' . $jobLink;
        }

        return implode("\n", $lines);
    }

    private function normalizeOptionalUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 500) : '';
    }

    private function isGraduateUnemployed(int $graduateId): bool
    {
        $row = Database::fetch(
            'SELECT status FROM employment_profiles WHERE graduate_id = ? AND is_current = 1 AND deleted_at IS NULL ORDER BY updated_at DESC, id DESC LIMIT 1',
            [$graduateId]
        );

        return ($row['status'] ?? null) === 'unemployed';
    }

    private function logNotification(int $surveyId, ?int $invitationId, int $graduateId, string $channel, string $recipient, string $status, int|string|null $providerMessageId, ?string $error): void
    {
        Database::run(
            'INSERT INTO notification_logs (survey_id, invitation_id, graduate_id, channel, recipient, status, provider_message_id, error_message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$surveyId, $invitationId, $graduateId, $channel, $recipient, $status, $providerMessageId !== null ? (string) $providerMessageId : null, $error !== null ? mb_substr($error, 0, 500) : null]
        );
    }

    private function graduateName(int $graduateId): string
    {
        $g = $graduateId > 0 ? Graduate::find($graduateId) : null;
        return $g ? trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? '')) : '';
    }
}
