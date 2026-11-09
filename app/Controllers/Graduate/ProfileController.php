<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Graduate;
use App\Validators\Validator;

/**
 * Graduate profile: view and update the graduate's own contact information.
 * Academic/identity fields (student number, program, batch, graduation year)
 * are managed by the admin and are not editable here.
 */
class ProfileController extends Controller
{
    private function graduateId(): int
    {
        return (int) (auth()->graduate_id ?? 0);
    }

    public function index(Request $request): void
    {
        $graduate = Database::fetch(
            'SELECT g.*, p.code AS program_code, p.name AS program_name, b.year AS batch_year
             FROM graduates g
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             WHERE g.id = ? AND g.deleted_at IS NULL',
            [$this->graduateId()]
        );
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $this->view('graduate/profile', [
            'title'        => 'My Profile',
            'subtitle'     => 'Review and update your contact information',
            'graduate'     => $graduate,
            'accountEmail' => (string) (auth()->email ?? ''),
            'old'          => Session::get('_old_input', []),
        ]);
    }

    public function update(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();
        $graduate = Graduate::find($graduateId);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $data = $request->all();

        $validator = (new Validator($data))
            ->required('first_name', 'last_name')
            ->in('sex', ['Male', 'Female'])
            ->in('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])
            ->date('birth_date')
            ->maxLength('email', 191)
            ->maxLength('contact_number', 30);
        if (!empty($data['email'])) {
            $validator->email('email');
        }
        $validator->validateOrFail();

        $allowed = ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'contact_number', 'sex', 'birth_date', 'civil_status', 'address', 'job_position', 'municipality', 'province'];
        $payload = [];
        foreach ($allowed as $field) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
            $payload[$field] = $value !== '' ? $value : null;
        }

        // Preserve admin-managed fields by merging over the existing row.
        // If phone was changed, reset phone_verified_at so it can be re-verified
        $oldPhone = trim((string) ($graduate['contact_number'] ?? ''));
        $newPhone = trim((string) ($payload['contact_number'] ?? ''));
        if ($newPhone !== '' && $newPhone !== $oldPhone) {
            $payload['phone_verified_at'] = null;
        }

        Graduate::update($graduateId, array_merge($graduate, $payload));

        // Synchronize job_position with active employment_profile
        $currentEp = Database::fetch('SELECT id, status, job_title FROM employment_profiles WHERE graduate_id = ? AND is_current = 1 AND deleted_at IS NULL LIMIT 1', [$graduateId]);
        if (!empty($payload['job_position'])) {
            if ($currentEp) {
                Database::run(
                    'UPDATE employment_profiles SET job_title = ?, status = CASE WHEN status = "unemployed" THEN "employed" ELSE status END, updated_at = NOW() WHERE id = ?',
                    [$payload['job_position'], $currentEp['id']]
                );
            } else {
                Database::run(
                    'INSERT INTO employment_profiles (graduate_id, status, job_title, is_current, created_at, updated_at)
                     VALUES (?, "employed", ?, 1, NOW(), NOW())',
                    [$graduateId, $payload['job_position']]
                );
            }
        }

        $this->audit('update', 'graduate-profile', "Graduate updated their profile (graduate {$graduateId}).");
        $this->success('Profile updated successfully.', 'graduate/profile');
    }

    public function sendOtp(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();
        $graduate = Graduate::find($graduateId);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $phone = trim((string) $request->input('contact_number', $graduate['contact_number'] ?? ''));
        if ($phone === '') {
            flash('error', 'Please enter a contact number to verify.');
            back();
        }

        // Update contact number if changed
        if ($phone !== ($graduate['contact_number'] ?? '')) {
            Database::run('UPDATE graduates SET contact_number = ?, phone_verified_at = NULL WHERE id = ?', [$phone, $graduateId]);
        }

        $otpService = new \App\Services\OtpService();
        $res = $otpService->send($phone, $graduateId, (int) auth()->id, 'profile_verification');

        if (!$res['success']) {
            flash('error', $res['error']);
            back();
        }

        Session::put('profile_otp_phone', $res['phone']);
        if ($res['simulated'] && !empty($res['code'])) {
            flash('info', "DEMO / TEST MODE: SMS simulated. Your OTP code is {$res['code']}");
        } else {
            flash('success', 'Verification code sent to ' . \App\Services\OtpService::maskPhone($res['phone']) . ' via SMS.');
        }

        back();
    }

    public function verifyOtp(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();
        $code = trim((string) $request->input('otp_code', ''));
        $phone = (string) Session::get('profile_otp_phone', '');

        if ($phone === '') {
            $graduate = Graduate::find($graduateId);
            $phone = (string) ($graduate['contact_number'] ?? '');
        }

        $otpService = new \App\Services\OtpService();
        if ($otpService->verify($phone, $code, 'profile_verification')) {
            Session::forget('profile_otp_phone');
            flash('success', 'Your mobile number has been successfully verified via OTP!');
        } else {
            flash('error', 'Invalid or expired OTP code. Please check the code and try again.');
        }

        back();
    }
}
