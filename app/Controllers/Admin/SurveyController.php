<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\Survey;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Validators\Validator;

/**
 * Survey management (admin): create, edit, activate, close, view structure + responses.
 */
class SurveyController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/surveys/index', [
            'title'    => 'Surveys',
            'subtitle' => 'Tracer study surveys and response collection',
            'surveys'  => Survey::paginate($search, $page, 15),
            'search'   => $search,
            'activeSurvey' => Survey::active(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/surveys/create', [
            'title'    => 'Create Survey',
            'subtitle' => 'Set up a new tracer study survey',
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('title')
            ->maxLength('title', 191)
            ->in('status', ['draft', 'active', 'closed'])
            ->date('start_date')
            ->date('end_date')
            ->integer('tracer_year')
            ->integer('invitation_expiry_days');
        $validator->validateOrFail();

        $id = Survey::create($data);
        if (($data['status'] ?? '') === 'active') {
            Survey::activate($id);
        }
        $this->audit('create', 'surveys', "Created survey: {$data['title']}.");
        $this->success('Survey created. Add sections and questions to begin.', 'admin/surveys/' . $id);
    }

    public function show(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $structure = Survey::structure((int) $params['id']);

        $this->view('admin/surveys/builder', [
            'title'     => $survey['title'],
            'subtitle'  => 'Survey builder',
            'survey'    => $survey,
            'sections'  => $structure['sections'],
            'questions' => $structure['questions'],
            'sectionCount'  => Survey::sectionCount((int) $params['id']),
            'questionCount' => Survey::questionCount((int) $params['id']),
            'responseCount' => Survey::responseCount((int) $params['id']),
            'invitationStats' => SurveyInvitation::stats((int) $params['id']),
        ]);
    }

    public function responses(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/surveys/responses', [
            'title'     => $survey['title'],
            'subtitle'  => 'Response viewer',
            'survey'    => $survey,
            'responses' => Survey::responses((int) $params['id'], $page, 15),
            'sectionCount'  => Survey::sectionCount((int) $params['id']),
            'questionCount' => Survey::questionCount((int) $params['id']),
            'responseCount' => Survey::responseCount((int) $params['id']),
            'invitationStats' => SurveyInvitation::stats((int) $params['id']),
            'insights' => (new \App\Services\SurveyInsightsService())->questionBreakdowns((int) $params['id']),
        ]);
    }

    public function preview(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $structure = Survey::structure((int) $params['id']);

        $this->view('public/survey/preview', [
            'title'    => $survey['title'],
            'subtitle' => 'Survey preview',
            'survey'   => $survey,
            'sections' => $structure['sections'],
        ], 'public');
    }

    public function edit(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $this->view('admin/surveys/edit', [
            'title'   => 'Edit Survey',
            'subtitle' => $survey['title'],
            'survey'  => $survey,
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $survey = Survey::find($id);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('title')
            ->maxLength('title', 191)
            ->in('status', ['draft', 'active', 'closed'])
            ->date('start_date')
            ->date('end_date')
            ->integer('tracer_year')
            ->integer('invitation_expiry_days');
        $validator->validateOrFail();

        Survey::update($id, $data);
        $this->audit('update', 'surveys', "Updated survey: {$data['title']}.");
        $this->success('Survey updated successfully.', 'admin/surveys/' . $id);
    }

    public function showResponse(Request $request, array $params): void
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $response = SurveyResponse::find((int) ($params['rid'] ?? 0));
        if (!$response || (int) $response['survey_id'] !== (int) $survey['id']) {
            abort(404, 'Response not found.');
        }

        $structure = Survey::structure((int) $survey['id']);
        $respondent = Database::fetch(
            'SELECT g.id, g.student_number, g.first_name, g.middle_name, g.last_name, g.suffix,
                    g.email, g.contact_number, p.code AS program_code, p.name AS program_name,
                    b.year AS batch_year
             FROM graduates g
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             WHERE g.id = ?',
            [(int) $response['graduate_id']]
        );

        $this->view('admin/surveys/view_response', [
            'title'   => 'Response #' . (int) $response['id'],
            'subtitle' => $survey['title'],
            'survey'  => $survey,
            'sections' => $structure['sections'],
            'answers' => SurveyResponse::answers((int) $response['id']),
            'response' => $response,
            'respondent' => $respondent,
        ]);
    }

    public function proof(Request $request, array $params): never
    {
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        $response = SurveyResponse::find((int) ($params['rid'] ?? 0));
        if (!$response || (int) $response['survey_id'] !== (int) $survey['id']) {
            abort(404, 'Response not found.');
        }

        $answer = Database::fetch(
            'SELECT a.answer_value, q.type
             FROM survey_answers a
             JOIN survey_questions q ON q.id = a.question_id
             WHERE a.response_id = ? AND a.question_id = ?',
            [(int) $response['id'], (int) ($params['qid'] ?? 0)]
        );
        if (!$answer || $answer['type'] !== 'image_upload' || empty($answer['answer_value'])) {
            abort(404, 'Uploaded proof not found.');
        }

        $relative = str_replace('\\', '/', (string) $answer['answer_value']);
        if (str_contains($relative, '..') || !str_starts_with($relative, 'uploads/survey-proofs/')) {
            abort(404, 'Uploaded proof not found.');
        }

        $file = storage_path($relative);
        if (!is_file($file)) {
            abort(404, 'Uploaded proof not found.');
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            abort(404, 'Uploaded proof not found.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }

    public function activate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        Survey::activate((int) $params['id']);
        $this->audit('activate', 'surveys', "Activated survey: {$survey['title']}.");
        $this->success('Survey activated and is now the active tracer study survey.', 'admin/surveys/' . (int) $params['id']);
    }

    public function close(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        Survey::setStatus((int) $params['id'], 'closed');
        $this->audit('close', 'surveys', "Closed survey: {$survey['title']}.");
        $this->success('Survey closed. Graduates can no longer submit responses.', 'admin/surveys/' . (int) $params['id']);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }
        Survey::softDelete((int) $params['id']);
        $this->audit('delete', 'surveys', "Deleted survey: {$survey['title']}.");
        $this->success('Survey moved to trash.', 'admin/surveys');
    }

    /**
     * Duplicate a survey for the next tracer year: copies the full structure
     * (sections, questions, options) with only the year/period changed.
     */
    public function duplicate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $params['id']);
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        $newYear = (int) $survey['tracer_year'] + 1;
        $title = preg_replace('/\b\d{4}\b/', (string) $newYear, (string) $survey['title']);
        $shiftYear = static function (?string $date) use ($newYear): ?string {
            if (!$date) {
                return null;
            }
            $t = strtotime($date);
            return $t ? date('Y-m-d', mktime(0, 0, 0, (int) date('n', $t), (int) date('j', $t), $newYear)) : $date;
        };

        $newId = Survey::create([
            'title'                 => $title,
            'description'           => $survey['description'],
            'confirmation_message'  => $survey['confirmation_message'],
            'status'                => 'draft',
            'tracer_year'           => $newYear,
            'start_date'            => $shiftYear($survey['start_date']),
            'end_date'              => $shiftYear($survey['end_date']),
            'require_invitation'    => (int) ($survey['require_invitation'] ?? 1),
            'invitation_expiry_days'=> (int) ($survey['invitation_expiry_days'] ?? 30),
            'created_by'            => auth_id(),
        ]);

        $this->copyStructure((int) $survey['id'], $newId);

        $this->audit('duplicate', 'surveys', "Duplicated survey {$survey['id']} as survey {$newId} (tracer year {$newYear}).");
        $this->success("Survey duplicated for tracer year {$newYear}. Review it before activating.", 'admin/surveys/' . $newId);
    }

    /**
     * Copy sections, questions, and options from one survey into another.
     */
    private function copyStructure(int $fromId, int $toId): void
    {
        $sectionMap = [];
        foreach (Database::fetchAll('SELECT * FROM survey_sections WHERE survey_id = ? ORDER BY sort_order, id', [$fromId]) as $sec) {
            Database::run(
                'INSERT INTO survey_sections (survey_id, title, description, sort_order) VALUES (?, ?, ?, ?)',
                [$toId, $sec['title'], $sec['description'], $sec['sort_order']]
            );
            $sectionMap[(int) $sec['id']] = (int) Database::lastInsertId();
        }

        $qidMap = [];
        foreach (Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? AND deleted_at IS NULL ORDER BY sort_order, id', [$fromId]) as $q) {
            Database::run(
                'INSERT INTO survey_questions (survey_id, section_id, question_key, question_text, help_text, type, likert_scale, validation, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $toId,
                    isset($sectionMap[(int) $q['section_id']]) ? $sectionMap[(int) $q['section_id']] : null,
                    $q['question_key'],
                    $q['question_text'],
                    $q['help_text'],
                    $q['type'],
                    $q['likert_scale'],
                    $q['validation'],
                    $q['is_required'],
                    $q['sort_order'],
                ]
            );
            $qidMap[(int) $q['id']] = (int) Database::lastInsertId();
        }

        if (!$qidMap) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($qidMap), '?'));
        $options = Database::fetchAll(
            "SELECT * FROM survey_options WHERE question_id IN ({$placeholders}) ORDER BY sort_order, id",
            array_keys($qidMap)
        );
        foreach ($options as $o) {
            Database::run(
                'INSERT INTO survey_options (question_id, option_text, option_value, sort_order) VALUES (?, ?, ?, ?)',
                [$qidMap[(int) $o['question_id']], $o['option_text'], $o['option_value'], $o['sort_order']]
            );
        }
    }
}
