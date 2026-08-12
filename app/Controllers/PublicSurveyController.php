<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Graduate;
use App\Models\Survey;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Services\EmploymentSyncService;
use App\Services\RateLimiter;
use App\Services\SurveyAnswerService;

/**
 * Public (no-login) survey access via secure one-time-use invitation tokens.
 */
class PublicSurveyController extends Controller
{
    public function show(Request $request, array $params): void
    {
        $rawToken = (string) ($params['token'] ?? '');
        $invitation = SurveyInvitation::validateAccess($rawToken, $reason);
        $survey = $invitation ? Survey::find((int) $invitation['survey_id']) : null;

        if (!$invitation || !$survey) {
            // Rate-limit only genuine invalid-token guessing from a single IP.
            // Completed/expired/revoked tokens render their friendly page
            // without consuming the invalid bucket (a respondent reopening a
            // completed link is a normal action).
            if ($reason === 'invalid') {
                RateLimiter::hit('public_survey_invalid_tokens', $request->ip());
                if (RateLimiter::tooManyAttempts('public_survey_invalid_tokens', $request->ip(), 25, 600)) {
                    RateLimiter::prune('public_survey_invalid_tokens', 3600);
                    $this->renderUnavailable('throttled', 429);
                }
            }
            $this->renderUnavailable($reason ?? 'invalid', 404);
            return;
        }

        // Early "already responded" check (a graduate can only respond once).
        if ((int) $invitation['graduate_id'] > 0 && Survey::hasResponse((int) $survey['id'], (int) $invitation['graduate_id'])) {
            SurveyInvitation::markCompleted((int) $invitation['id']);
            $this->renderUnavailable('completed', 200, $survey);
            return;
        }

        if ($survey['status'] !== 'active') {
            $this->renderUnavailable($survey['status'], 200, $survey);
            return;
        }
        if ($survey['end_date'] !== null && strtotime((string) $survey['end_date'] . ' 23:59:59') < time()) {
            $this->renderUnavailable('expired_period', 200, $survey);
            return;
        }

        SurveyInvitation::markOpened((int) $invitation['id']);

        $structure = Survey::structure((int) $survey['id']);
        $recipient = (int) $invitation['graduate_id'] > 0
            ? Graduate::find((int) $invitation['graduate_id'])
            : null;

        $this->view('public/survey/index', [
            'title'      => $survey['title'],
            'subtitle'   => $survey['title'],
            'survey'     => $survey,
            'sections'   => $structure['sections'],
            'recipient'  => $recipient,
            'token'      => $rawToken,
            'old'        => Session::get('_old_input', []),
        ], 'public');
    }

    public function submit(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $rawToken = (string) ($params['token'] ?? '');
        $invitation = SurveyInvitation::validateAccess($rawToken, $reason);
        $survey = $invitation ? Survey::find((int) $invitation['survey_id']) : null;

        if (!$invitation || !$survey) {
            $this->renderUnavailable($reason ?? 'invalid', 404);
            return;
        }
        if ($survey['status'] !== 'active') {
            $this->error('This survey is not open for responses.', 'survey/respond/' . rawurlencode($rawToken));
        }
        if ($survey['end_date'] !== null && strtotime((string) $survey['end_date'] . ' 23:59:59') < time()) {
            $this->error('This survey has already closed.', 'survey/respond/' . rawurlencode($rawToken));
        }
        if ((int) $invitation['graduate_id'] > 0 && Survey::hasResponse((int) $survey['id'], (int) $invitation['graduate_id'])) {
            SurveyInvitation::markCompleted((int) $invitation['id']);
            $this->renderUnavailable('completed', 200, $survey);
            return;
        }

        // Rate-limit submission attempts per IP.
        if (RateLimiter::tooManyAttempts('public_survey_submit', $request->ip(), 30, 600)) {
            RateLimiter::prune('public_survey_submit', 3600);
            $this->error('Too many submission attempts from this device. Please try again later.', 'survey/respond/' . rawurlencode($rawToken));
        }
        RateLimiter::hit('public_survey_submit', $request->ip());

        // Lightweight per-IP submitted-response throttle.
        if (SurveyResponse::ipThrottled((int) $survey['id'], $request->ip(), 10)) {
            $this->error('Too many submissions from this device. Please try again later.', 'survey/respond/' . rawurlencode($rawToken));
        }

        $structure = Survey::structure((int) $survey['id']);
        if (empty($structure['questions'])) {
            $this->error('This survey has no questions yet.', 'survey/respond/' . rawurlencode($rawToken));
        }
        $answers = $request->all()['answers'] ?? [];
        $errors = (new SurveyAnswerService())->validate($structure, $answers);

        if ($errors) {
            Session::flashOldInput($request->all());
            flash('error', implode(' ', $errors));
            redirect('survey/respond/' . rawurlencode($rawToken));
        }

        $graduateId = (int) $invitation['graduate_id'];

        // Create the response + answers + completion inside one transaction so
        // a failure can never leave a partial response behind.
        $responseId = Database::transaction(function () use ($survey, $graduateId, $request, $invitation, $structure, $answers): int {
            $rid = SurveyResponse::ensure(
                (int) $survey['id'],
                $graduateId,
                $request->ip(),
                $request->userAgent(),
                (int) $invitation['id']
            );

            (new SurveyAnswerService())->store($rid, $structure, $answers);
            SurveyResponse::submit($rid);
            SurveyInvitation::markCompleted((int) $invitation['id']);
            return $rid;
        });

        if ($graduateId > 0) {
            (new EmploymentSyncService())->syncFromResponse($graduateId, $responseId);
        }
        $this->audit('submit', 'surveys', "Submitted public tracer survey response (survey {$survey['id']}).");

        $this->view('public/survey/confirmation', [
            'title'     => 'Response Submitted',
            'subtitle'  => $survey['title'],
            'survey'    => $survey,
            'responseId' => $responseId,
        ], 'public');
    }

    private function renderUnavailable(string $reason, int $code, ?array $survey = null): never
    {
        http_response_code($code);
        $headings = [
            'invalid'        => 'Survey Link Invalid',
            'throttled'      => 'Too Many Requests',
            'expired'        => 'Survey Invitation Expired',
            'expired_period' => 'Survey Closed',
            'revoked'        => 'Survey Invitation Unavailable',
            'completed'      => 'Survey Already Completed',
            'draft'          => 'Survey Not Open',
            'closed'         => 'Survey Closed',
            'active'         => 'Survey',
        ];
        $messages = [
            'invalid'        => 'This survey invitation link is invalid or no longer available. Please contact the Institute of Agricultural Technology if you believe this is an error.',
            'throttled'      => 'Too many requests were made from this device. Please wait a few minutes and try again.',
            'expired'        => 'This survey invitation has expired. Please contact the Institute of Agricultural Technology if you need a new invitation.',
            'expired_period' => 'This survey is no longer accepting responses.',
            'revoked'        => 'This invitation is no longer active. Please contact the Institute of Agricultural Technology if you need a new invitation.',
            'completed'      => 'This survey response has already been submitted. Thank you for participating in the IAT tracer study.',
            'draft'          => 'This survey is not yet open for responses.',
            'closed'         => 'This survey is no longer accepting responses. Thank you for your interest in the IAT tracer study.',
            'active'         => 'This survey is currently open for responses.',
        ];
        $this->view('public/survey/error', [
            'title'    => 'Survey',
            'subtitle' => $survey['title'] ?? 'Survey',
            'survey'   => $survey,
            'reason'   => $reason,
            'heading'  => $headings[$reason] ?? 'Survey Unavailable',
            'message'  => $messages[$reason] ?? $messages['invalid'],
        ], 'public');
        exit;
    }
}