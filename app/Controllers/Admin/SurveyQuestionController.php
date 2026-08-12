<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Validators\Validator;

/**
 * Survey question builder actions (embedded in the Google-Forms-style builder).
 */
class SurveyQuestionController extends Controller
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
            ->required('survey_id', 'question_text')
            ->in('type', Survey::QUESTION_TYPES)
            ->integer('section_id')
            ->maxLength('question_key', 100);
        $validator->validateOrFail();

        try {
            SurveyQuestion::create((int) $survey['id'], $this->normalize($data));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 'admin/surveys/' . (int) $survey['id']);
        }

        $this->audit('create', 'surveys', "Added question to survey {$survey['id']}.");
        $this->success('Question added.', 'admin/surveys/' . (int) $survey['id']);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $question = SurveyQuestion::find((int) $params['id']);
        if (!$question) {
            abort(404, 'Question not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('question_text')
            ->in('type', Survey::QUESTION_TYPES)
            ->integer('section_id')
            ->maxLength('question_key', 100);
        $validator->validateOrFail();

        try {
            SurveyQuestion::update((int) $params['id'], $this->normalize($data));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 'admin/surveys/' . (int) $question['survey_id']);
        }

        $this->audit('update', 'surveys', "Updated question (id {$params['id']}).");
        $this->success('Question updated.', 'admin/surveys/' . (int) $question['survey_id']);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $question = SurveyQuestion::find((int) $params['id']);
        if (!$question) {
            abort(404, 'Question not found.');
        }
        SurveyQuestion::destroy((int) $params['id']);
        $this->audit('delete', 'surveys', "Deleted question (id {$params['id']}).");
        $this->success('Question removed.', 'admin/surveys/' . (int) $question['survey_id']);
    }

    public function duplicate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $question = SurveyQuestion::find((int) $params['id']);
        if (!$question) {
            abort(404, 'Question not found.');
        }
        $newId = SurveyQuestion::duplicate((int) $params['id']);
        $this->audit('duplicate', 'surveys', "Duplicated question (id {$params['id']} -> {$newId}).");
        $this->success('Question duplicated.', 'admin/surveys/' . (int) $question['survey_id']);
    }

    /**
     * Persist a drag-and-drop ordering of questions within a section.
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
        SurveySection::reorderQuestions($surveyId, $ids);
        $this->json(['success' => true]);
    }

    /**
     * Normalize builder input: array options, option textarea fallback, validation.
     */
    private function normalize(array $data): array
    {
        $type = (string) ($data['type'] ?? 'text');

        $options = [];
        if (isset($data['options']) && is_array($data['options'])) {
            $options = array_values(array_filter(
                array_map(static fn ($o) => trim((string) $o), $data['options']),
                static fn ($o) => $o !== ''
            ));
        } elseif (isset($data['options']) && is_string($data['options'])) {
            $options = SurveyQuestion::parseOptionLines((string) $data['options']);
        }

        if (in_array($type, Survey::CHOICE_TYPES, true) && empty($options)) {
            flash('error', 'Choice questions require at least one option.');
            redirect('admin/surveys/' . (int) ($data['survey_id'] ?? 0));
        }

        return [
            'section_id'     => $data['section_id'] ?? null,
            'question_key'   => trim((string) ($data['question_key'] ?? '')),
            'question_text'  => $data['question_text'] ?? '',
            'help_text'      => $data['help_text'] ?? null,
            'type'           => $type,
            'likert_scale'   => $data['likert_scale'] ?? null,
            'is_required'    => $data['is_required'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
            'options'        => $options,
            'min'            => $data['min'] ?? null,
            'max'            => $data['max'] ?? null,
            'max_length'     => $data['max_length'] ?? null,
            'min_selections' => $data['min_selections'] ?? null,
            'max_selections' => $data['max_selections'] ?? null,
            'scale_min'      => $data['scale_min'] ?? null,
            'scale_max'      => $data['scale_max'] ?? null,
            'stars'          => $data['stars'] ?? null,
            'min_label'      => $data['min_label'] ?? null,
            'max_label'      => $data['max_label'] ?? null,
            'email'          => $data['email'] ?? null,
        ];
    }
}
