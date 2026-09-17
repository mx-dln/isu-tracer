<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\EmploymentProfile;
use App\Models\EmploymentSector;
use App\Models\Graduate;
use App\Models\SurveyResponse;
use App\Services\EmploymentSyncService;
use App\Validators\Validator;

/**
 * Employment profile management (admin).
 */
class EmploymentController extends Controller
{
    public function index(Request $request): void
    {
        $filters = [
            'search'     => trim((string) $request->query('search')),
            'program_id' => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'batch_id'   => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'status'     => trim((string) $request->query('status')),
            'sector_id'  => $request->query('sector_id') !== null && $request->query('sector_id') !== '' ? (int) $request->query('sector_id') : null,
        ];
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/employment/index', [
            'title'     => 'Employment Profiles',
            'subtitle'  => 'Employment status, job relevance and time to first employment',
            'profiles'  => EmploymentProfile::paginate(array_filter($filters, fn ($v) => $v !== null && $v !== ''), $page, 15),
            'filters'   => $filters,
            'programs'  => \App\Models\Program::all(true),
            'batches'   => \App\Models\Batch::all(true),
            'sectors'   => EmploymentSector::all(true),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $profile = EmploymentProfile::find((int) $params['id']);
        if (!$profile) {
            abort(404, 'Employment profile not found.');
        }
        $graduate = Graduate::find((int) $profile['graduate_id']);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $this->view('admin/employment/show', [
            'title'      => 'Employment Profile',
            'subtitle'   => $graduate['first_name'] . ' ' . $graduate['last_name'] . ' · ' . $graduate['student_number'],
            'profile'    => $profile,
            'graduate'   => $graduate,
            'history'    => EmploymentProfile::history((int) $graduate['id']),
            'allProfiles'=> EmploymentProfile::allForGraduate((int) $graduate['id']),
            'sectors'    => EmploymentSector::all(false),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $profile = EmploymentProfile::find((int) $params['id']);
        if (!$profile) {
            abort(404, 'Employment profile not found.');
        }

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
        $data['proof_image_url'] = $this->normalizeOptionalUrl((string) ($data['proof_image_url'] ?? ''));

        EmploymentProfile::save((int) $profile['graduate_id'], $data);
        $this->audit('update', 'employment', "Updated employment profile #{$profile['id']} (graduate {$profile['graduate_id']}).");
        $this->success('Employment profile updated.', 'admin/employment/' . (int) $profile['id']);
    }

    /**
     * Rebuild a graduate's employment profile from their latest survey response.
     */
    public function syncFromSurvey(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $profile = EmploymentProfile::find((int) $params['id']);
        if (!$profile) {
            abort(404, 'Employment profile not found.');
        }

        $response = \App\Core\Database::fetch(
            'SELECT id FROM survey_responses WHERE graduate_id = ? AND status = "submitted" ORDER BY submitted_at DESC LIMIT 1',
            [(int) $profile['graduate_id']]
        );
        if (!$response) {
            $this->error('No submitted survey response found for this graduate.', 'admin/employment/' . (int) $profile['id']);
        }

        $newId = (new EmploymentSyncService())->syncFromResponse((int) $profile['graduate_id'], (int) $response['id']);
        $this->audit('sync', 'employment', "Synced employment profile from survey response (graduate {$profile['graduate_id']}).");
        $this->success('Employment profile synced from the latest survey response.', 'admin/employment/' . $newId);
    }

    public function proof(Request $request, array $params): never
    {
        $profile = EmploymentProfile::find((int) $params['id']);
        if (!$profile || empty($profile['proof_image_path'])) {
            abort(404, 'Employment proof not found.');
        }

        $relative = ltrim(str_replace('\\', '/', (string) $profile['proof_image_path']), '/');
        if (str_contains($relative, '..') || !str_starts_with($relative, 'uploads/employment-proofs/')) {
            abort(404, 'Employment proof not found.');
        }

        $path = storage_path($relative);
        if (!is_file($path)) {
            abort(404, 'Employment proof not found.');
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        if (!str_starts_with($mime, 'image/')) {
            abort(404, 'Employment proof not found.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    private function normalizeOptionalUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 500) : null;
    }
}
