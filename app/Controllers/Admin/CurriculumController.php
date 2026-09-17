<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\CurriculumFeedback;
use App\Models\CurriculumQuestion;
use App\Services\AnalyticsService;
use App\Validators\Validator;

/**
 * Curriculum feedback management (admin): questions + weighted analysis.
 */
class CurriculumController extends Controller
{
    public function index(Request $request): void
    {
        $responseFilters = [
            'response_search' => trim((string) $request->query('response_search')),
            'program_id'      => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'batch_id'        => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'question_id'     => $request->query('question_id') !== null && $request->query('question_id') !== '' ? (int) $request->query('question_id') : null,
        ];
        $respondentFilters = [
            'search'      => $responseFilters['response_search'],
            'program_id'  => $responseFilters['program_id'],
            'batch_id'    => $responseFilters['batch_id'],
            'question_id' => $responseFilters['question_id'],
        ];

        $this->view('admin/curriculum/index', [
            'title'     => 'Curriculum Feedback',
            'subtitle'  => 'Curriculum questions and weighted feedback analysis',
            'questions' => CurriculumQuestion::paginate(trim((string) $request->query('search')) ?: null, max(1, (int) $request->query('page', 1)), 15),
            'search'    => trim((string) $request->query('search')),
            'analysis'  => (new AnalyticsService())->curriculumAnalysis(),
            'responses' => CurriculumFeedback::respondents(array_filter($respondentFilters, fn ($v) => $v !== null && $v !== ''), max(1, (int) $request->query('responses_page', 1)), 15),
            'responseFilters' => $responseFilters,
            'programs'   => \App\Models\Program::all(true),
            'batches'    => \App\Models\Batch::all(true),
            'allQuestions' => CurriculumQuestion::all(true),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();
        $validator = (new Validator($data))->required('question_text')->integer('sort_order');
        $validator->validateOrFail();

        CurriculumQuestion::create($data);
        $this->audit('create', 'curriculum', "Created curriculum question: {$data['question_text']}.");
        $this->success('Curriculum question added.', 'admin/curriculum-feedback');
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $q = CurriculumQuestion::find((int) $params['id']);
        if (!$q) {
            abort(404, 'Curriculum question not found.');
        }
        $data = $request->all();
        $validator = (new Validator($data))->required('question_text')->integer('sort_order');
        $validator->validateOrFail();

        CurriculumQuestion::update((int) $params['id'], $data);
        $this->audit('update', 'curriculum', "Updated curriculum question: {$data['question_text']}.");
        $this->success('Curriculum question updated.', 'admin/curriculum-feedback');
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $q = CurriculumQuestion::find((int) $params['id']);
        if (!$q) {
            abort(404, 'Curriculum question not found.');
        }
        CurriculumQuestion::softDelete((int) $params['id']);
        $this->audit('delete', 'curriculum', "Deleted curriculum question: {$q['question_text']}.");
        $this->success('Curriculum question removed.', 'admin/curriculum-feedback');
    }
}
