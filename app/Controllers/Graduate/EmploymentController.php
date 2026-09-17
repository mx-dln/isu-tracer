<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
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
        $data['proof_image_url'] = $this->normalizeOptionalUrl((string) ($data['proof_image_url'] ?? ''));

        $current = EmploymentProfile::findCurrent($graduateId);
        $proofPath = $this->storeProofImage($request->file('proof_image'), $graduateId);
        if ($proofPath !== null) {
            $data['proof_image_path'] = $proofPath;
        }
        $hasProof = $proofPath !== null
            || $data['proof_image_url'] !== null
            || !empty($current['proof_image_path']);
        if (in_array($data['status'], ['employed', 'self_employed'], true) && !$hasProof) {
            $this->error('Please upload or link proof of employment when your status is employed or self-employed.', 'graduate/employment');
        }

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

    private function storeProofImage(?array $file, int $graduateId): ?string
    {
        if ($file === null) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->error('Proof image could not be uploaded. Please try another image.', 'graduate/employment');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $this->error('Proof image upload is invalid.', 'graduate/employment');
        }

        $maxBytes = 5 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            $this->error('Proof image must not exceed 5MB.', 'graduate/employment');
        }

        $mime = mime_content_type($tmp) ?: '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime])) {
            $this->error('Proof image must be JPG, PNG, GIF, or WebP.', 'graduate/employment');
        }

        $relativeDir = 'uploads/employment-proofs/' . date('Y/m');
        $dir = storage_path($relativeDir);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            Logger::error('Failed to create employment proof upload directory', ['dir' => $dir]);
            $this->error('Could not save proof image. Please contact the administrator.', 'graduate/employment');
        }

        $filename = 'graduate-' . $graduateId . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
        $target = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $target)) {
            $this->error('Could not save proof image. Please try again.', 'graduate/employment');
        }

        return $relativeDir . '/' . $filename;
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
