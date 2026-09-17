<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\CompetencyResponse;
use App\Services\AnalyticsService;
use App\Validators\Validator;

/**
 * Competency framework management (admin): categories, competencies, analysis.
 */
class CompetencyController extends Controller
{
    public function index(Request $request): void
    {
        $filters = [
            'search'      => trim((string) $request->query('search')),
            'program_id'  => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'batch_id'    => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'category_id' => $request->query('category_id') !== null && $request->query('category_id') !== '' ? (int) $request->query('category_id') : null,
        ];

        $this->view('admin/competencies/respondents', [
            'title'      => 'Competency Respondents',
            'subtitle'   => 'Graduates who answered the competency self-assessment',
            'responses'  => CompetencyResponse::respondents(array_filter($filters, fn ($v) => $v !== null && $v !== ''), max(1, (int) $request->query('page', 1)), 15),
            'filters'    => $filters,
            'programs'   => \App\Models\Program::all(true),
            'batches'    => \App\Models\Batch::all(true),
            'categories' => CompetencyCategory::all(true),
        ]);
    }

    public function manage(Request $request): void
    {
        $this->view('admin/competencies/index', [
            'title'    => 'Manage Competencies',
            'subtitle' => 'Competency framework, categories, and self-assessment items',
            'categories' => CompetencyCategory::paginate(trim((string) $request->query('search_cat')) ?: null, max(1, (int) $request->query('cat_page', 1)), 15),
            'competencies' => Competency::paginate(
                $request->query('category_id') !== null && $request->query('category_id') !== '' ? (int) $request->query('category_id') : null,
                trim((string) $request->query('search')) ?: null,
                max(1, (int) $request->query('page', 1)),
                15
            ),
            'allCategories' => CompetencyCategory::all(true),
            'filters' => ['category_id' => $request->query('category_id') !== null && $request->query('category_id') !== '' ? (int) $request->query('category_id') : null, 'search' => trim((string) $request->query('search'))],
            'searchCat' => trim((string) $request->query('search_cat')),
        ]);
    }

    public function analysis(Request $request): void
    {
        $analytics = new AnalyticsService();
        $this->view('admin/competencies/analysis', [
            'title'     => 'Competency Analysis',
            'subtitle'  => 'Weighted mean scores by competency category',
            'analysis'  => $analytics->competencyAnalysis(),
        ]);
    }

    // ---- Categories ----

    public function storeCategory(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();
        $validator = (new Validator($data))
            ->required('name')
            ->maxLength('name', 191)
            ->unique('name', 'competency_categories');
        $validator->validateOrFail();

        CompetencyCategory::create($data);
        $this->audit('create', 'competencies', "Created competency category: {$data['name']}.");
        $this->success('Competency category added.', 'admin/competencies/manage');
    }

    public function updateCategory(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $cat = CompetencyCategory::find((int) $params['id']);
        if (!$cat) {
            abort(404, 'Category not found.');
        }
        $data = $request->all();
        $validator = (new Validator($data))
            ->required('name')
            ->maxLength('name', 191)
            ->unique('name', 'competency_categories', null, (int) $params['id'])
            ->integer('sort_order');
        $validator->validateOrFail();

        CompetencyCategory::update((int) $params['id'], $data);
        $this->audit('update', 'competencies', "Updated competency category: {$data['name']}.");
        $this->success('Competency category updated.', 'admin/competencies/manage');
    }

    public function destroyCategory(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $cat = CompetencyCategory::find((int) $params['id']);
        if (!$cat) {
            abort(404, 'Category not found.');
        }
        CompetencyCategory::softDelete((int) $params['id']);
        $this->audit('delete', 'competencies', "Deleted competency category: {$cat['name']}.");
        $this->success('Competency category removed.', 'admin/competencies/manage');
    }

    // ---- Competencies ----

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();
        $validator = (new Validator($data))
            ->required('category_id')
            ->required('name')
            ->maxLength('name', 191)
            ->exists('category_id', 'competency_categories');
        $validator->validateOrFail();

        Competency::create((int) $data['category_id'], $data);
        $this->audit('create', 'competencies', "Created competency: {$data['name']}.");
        $this->success('Competency added.', 'admin/competencies/manage');
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $comp = Competency::find((int) $params['id']);
        if (!$comp) {
            abort(404, 'Competency not found.');
        }
        $data = $request->all();
        $validator = (new Validator($data))
            ->required('category_id')
            ->required('name')
            ->maxLength('name', 191)
            ->exists('category_id', 'competency_categories');
        $validator->validateOrFail();

        Competency::update((int) $params['id'], $data);
        $this->audit('update', 'competencies', "Updated competency: {$data['name']}.");
        $this->success('Competency updated.', 'admin/competencies/manage');
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $comp = Competency::find((int) $params['id']);
        if (!$comp) {
            abort(404, 'Competency not found.');
        }
        Competency::softDelete((int) $params['id']);
        $this->audit('delete', 'competencies', "Deleted competency: {$comp['name']}.");
        $this->success('Competency removed.', 'admin/competencies/manage');
    }
}
