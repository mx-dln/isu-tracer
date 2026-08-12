<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\EmploymentProfile;
use App\Models\EmploymentSector;
use App\Validators\Validator;

/**
 * Employment profile for the logged-in graduate.
 */
class EmploymentController extends Controller
{
    private function graduateId(): int
    {
        return (int) (auth()->graduate_id ?? 0);
    }

    public function index(Request $request): void
    {
        $graduateId = $this->graduateId();
        $this->view('graduate/employment/index', [
            'title'    => 'My Employment',
            'subtitle' => 'Current employment details and work history',
            'profile'  => EmploymentProfile::findCurrent($graduateId),
            'history'  => EmploymentProfile::history($graduateId),
            'sectors'  => EmploymentSector::all(true),
        ]);
    }

    public function update(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('status')
            ->in('status', ['employed', 'self_employed', 'unemployed', 'further_studies'])
            ->in('employment_type', ['regular', 'contractual', 'temporary', 'casual', 'part_time', 'self_employed'])
            ->in('is_related_to_program', ['0', '1'])
            ->date('date_hired')
            ->date('first_employment_date')
            ->integer('job_relevance_rating')
            ->min('job_relevance_rating', 1)
            ->max('job_relevance_rating', 5)
            ->integer('sector_id');
        $validator->validateOrFail();

        $data['sector_id'] = !empty($data['sector_id']) ? (int) $data['sector_id'] : null;
        $data['is_related_to_program'] = ($data['is_related_to_program'] ?? '') !== '' ? (int) $data['is_related_to_program'] : null;
        $data['job_relevance_rating'] = !empty($data['job_relevance_rating']) ? (int) $data['job_relevance_rating'] : null;

        EmploymentProfile::save($graduateId, $data);
        $this->audit('update', 'employment', "Graduate updated own employment profile (graduate {$graduateId}).");
        $this->success('Your employment profile has been updated.', 'graduate/employment');
    }

    public function storeHistory(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('job_title')
            ->required('employer')
            ->date('start_date')
            ->date('end_date')
            ->integer('sector_id')
            ->in('employment_type', ['regular', 'contractual', 'temporary', 'casual', 'part_time', 'self_employed'])
            ->in('is_related_to_program', ['0', '1']);
        $validator->validateOrFail();

        $data['sector_id'] = !empty($data['sector_id']) ? (int) $data['sector_id'] : null;
        $data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : null;
        $data['is_related_to_program'] = ($data['is_related_to_program'] ?? '') !== '' ? (int) $data['is_related_to_program'] : null;

        EmploymentProfile::addHistory($graduateId, $data);
        $this->audit('create', 'employment', "Graduate added employment history entry (graduate {$graduateId}).");
        $this->success('Work history entry added.', 'graduate/employment');
    }

    public function destroyHistory(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();

        $entry = Database::fetch(
            'SELECT id FROM employment_history WHERE id = ? AND graduate_id = ? AND deleted_at IS NULL',
            [(int) $params['id'], $graduateId]
        );
        if (!$entry) {
            abort(404, 'History entry not found.');
        }
        EmploymentProfile::deleteHistory((int) $params['id']);
        $this->audit('delete', 'employment', "Graduate removed employment history entry (graduate {$graduateId}).");
        $this->success('Work history entry removed.', 'graduate/employment');
    }
}
