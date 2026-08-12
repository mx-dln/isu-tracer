<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
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
        $this->view('admin/curriculum/index', [
            'title'     => 'Curriculum Feedback',
            'subtitle'  => 'Curriculum questions and weighted feedback analysis',
            'questions' => CurriculumQuestion::paginate(trim((string) $request->query('search')) ?: null, max(1, (int) $request->query('page', 1)), 15),
            'search'    => trim((string) $request->query('search')),
            'analysis'  => (new AnalyticsService())->curriculumAnalysis(),
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
