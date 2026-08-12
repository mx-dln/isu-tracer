<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Survey;
use App\Models\SurveySection;
use App\Validators\Validator;

/**
 * Survey section builder actions (embedded in the survey builder page).
 */
class SurveySectionController extends Controller
{
    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $survey = Survey::find((int) $request->input('survey_id'));
        if (!$survey) {
            abort(404, 'Survey not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('survey_id', 'title')
            ->maxLength('title', 191)
            ->integer('sort_order');
        $validator->validateOrFail();

        SurveySection::create((int) $survey['id'], $data);
        $this->audit('create', 'surveys', "Added section '{$data['title']}' to survey {$survey['id']}.");
        $this->success('Section added.', 'admin/surveys/' . (int) $survey['id']);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $section = SurveySection::find((int) $params['id']);
        if (!$section) {
            abort(404, 'Section not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('title')
            ->maxLength('title', 191)
            ->integer('sort_order');
        $validator->validateOrFail();

        SurveySection::update((int) $params['id'], $data);
        $this->audit('update', 'surveys', "Updated section '{$data['title']}' (id {$params['id']}).");
        $this->success('Section updated.', 'admin/surveys/' . (int) $section['survey_id']);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $section = SurveySection::find((int) $params['id']);
        if (!$section) {
            abort(404, 'Section not found.');
        }
        SurveySection::destroy((int) $params['id']);
        $this->audit('delete', 'surveys', "Deleted section '{$section['title']}' and its questions.");
        $this->success('Section and its questions removed.', 'admin/surveys/' . (int) $section['survey_id']);
    }

    /**
     * Persist a drag-and-drop ordering of sections.
     */
    public function reorder(Request $request): void
    {
        Csrf::validateOrAbort();
        $surveyId = (int) $request->input('survey_id');
        $ids = (array) ($request->input('ids') ?? []);
        $survey = Survey::find($surveyId);
        if (!$survey || !$ids) {
            abort(404, 'Survey not found.');
        }
        SurveySection::reorder($surveyId, $ids);
        $this->json(['success' => true]);
    }
}
