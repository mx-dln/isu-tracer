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

        $allowed = ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'contact_number', 'sex', 'birth_date', 'civil_status', 'address', 'municipality', 'province'];
        $payload = [];
        foreach ($allowed as $field) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
            $payload[$field] = $value !== '' ? $value : null;
        }

        // Preserve admin-managed fields by merging over the existing row.
        Graduate::update($graduateId, array_merge($graduate, $payload));
        $this->audit('update', 'graduate-profile', "Graduate updated their profile (graduate {$graduateId}).");
        $this->success('Profile updated successfully.', 'graduate/profile');
    }
}
