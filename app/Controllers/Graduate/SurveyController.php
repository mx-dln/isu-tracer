<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Graduate;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\EmploymentSyncService;
use App\Services\SurveyAnswerService;

/**
 * Tracer survey for graduates: list, fill out, and submit responses.
 */
class SurveyController extends Controller
{
    public function index(Request $request): void
    {
        $graduateId = (int) (auth()->graduate_id ?? 0);
        $gradYear = (int) (Graduate::find($graduateId)['graduation_year'] ?? 0);
        $surveys = Database::fetchAll(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM survey_responses r
                      WHERE r.survey_id = s.id AND r.graduate_id = ? AND r.status = 'submitted') AS submitted
             FROM surveys s
             WHERE s.deleted_at IS NULL AND s.status IN ('active', 'closed')
               AND s.tracer_year = ?
             ORDER BY s.tracer_year DESC, s.created_at DESC",
            [$graduateId, $gradYear]
        );

        $this->view('graduate/survey/index', [
            'title'    => 'Tracer Survey',
            'subtitle' => 'Complete the active tracer study survey',
            'surveys'  => $surveys,
            'graduateId' => $graduateId,
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey || $survey['status'] === 'draft') {
            abort(404, 'Survey not found.');
        }

        $graduateId = (int) (auth()->graduate_id ?? 0);
        $gradYear = (int) (Graduate::find($graduateId)['graduation_year'] ?? 0);
        if ((int) $survey['tracer_year'] !== $gradYear) {
            abort(404, 'Survey not found.');
        }
        $existing = SurveyResponse::findForGraduate((int) $survey['id'], $graduateId);
        $submitted = $existing && $existing['status'] === 'submitted';

        $structure = Survey::structure((int) $survey['id']);

        if ($submitted) {
            $this->view('graduate/survey/summary', [
                'title'     => 'Survey Response',
                'subtitle'  => 'Your submitted response',
                'survey'    => $survey,
                'sections'  => $structure['sections'],
                'answers'   => SurveyResponse::answers((int) $existing['id']),
                'response'  => $existing,
            ]);
            return;
        }

        if ($survey['status'] === 'closed') {
            $this->view('graduate/survey/closed', [
                'title'   => 'Survey Closed',
                'subtitle' => $survey['title'],
                'survey'  => $survey,
            ]);
            return;
        }

        $this->view('graduate/survey/fill', [
            'title'     => 'Tracer Survey',
            'subtitle'  => $survey['title'],
            'survey'    => $survey,
            'sections'  => $structure['sections'],
            'questions' => $structure['questions'],
            'old'       => Session::get('_old_input', []),
        ]);
    }

    public function submit(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey || $survey['status'] !== 'active') {
            $this->error('This survey is not open for responses.', 'graduate/survey');
        }

        $graduateId = (int) (auth()->graduate_id ?? 0);
        $gradYear = (int) (Graduate::find($graduateId)['graduation_year'] ?? 0);
        if ((int) $survey['tracer_year'] !== $gradYear) {
            $this->error('This survey is only available to graduates of its tracer year.', 'graduate/survey');
        }
        $existing = SurveyResponse::findForGraduate((int) $survey['id'], $graduateId);
        if ($existing && $existing['status'] === 'submitted') {
            $this->error('You have already submitted a response for this survey.', 'graduate/survey/' . (int) $survey['id']);
        }

        $structure = Survey::structure((int) $survey['id']);
        $answers = $request->all()['answers'] ?? [];

        // Validate required questions and basic types.
        $errors = (new SurveyAnswerService())->validate($structure, $answers);

        if ($errors) {
            Session::flashOldInput($request->all());
            flash('error', implode(' ', array_slice($errors, 0, 3)));
            redirect('graduate/survey/' . (int) $survey['id']);
        }

        $responseId = SurveyResponse::ensure((int) $survey['id'], $graduateId, $request->ip(), $request->userAgent());

        (new SurveyAnswerService())->store($responseId, $structure, $answers);

        SurveyResponse::submit($responseId);
        (new EmploymentSyncService())->syncFromResponse($graduateId, $responseId);
        $this->audit('submit', 'surveys', "Submitted tracer survey response (survey {$survey['id']}).");
        $this->success('Thank you! Your tracer survey response has been submitted.', 'graduate/survey');
    }
}
