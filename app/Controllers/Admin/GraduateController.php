<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Models\Graduate;
use App\Models\Program;
use App\Validators\Validator;

/**
 * Graduate record management (admin).
 */
class GraduateController extends Controller
{
    public function index(Request $request): void
    {
        $filters = [
            'search'     => trim((string) $request->query('search')),
            'program_id' => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'major'      => trim((string) $request->query('major')),
            'batch_id'   => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'validated'  => $request->query('validated') !== null && $request->query('validated') !== '' ? (int) $request->query('validated') : null,
            'status'     => trim((string) $request->query('status')),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/graduates/index', [
            'title'       => 'IAT Graduates',
            'subtitle'    => 'Master list of Institute of Agricultural Technology graduates',
            'graduates'   => Graduate::paginate(array_filter($filters, fn ($v) => $v !== null && $v !== ''), $page, 15),
            'programs'    => Program::all(true),
            'majors'      => ['Animal Production', 'Crop Production'],
            'batches'     => Batch::all(true),
            'filters'     => $filters,
            'statuses'    => ['employed', 'self_employed', 'unemployed', 'further_studies'],
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/graduates/create', [
            'title'    => 'Add Graduate',
            'subtitle' => 'Encode a new IAT graduate record',
            'programs' => Program::all(true),
            'majors'   => ['Animal Production', 'Crop Production'],
            'batches'  => Batch::all(true),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        // Trim and sanitize all string inputs
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }
        $data['student_number'] = strtoupper((string) ($data['student_number'] ?? ''));

        // Format validation on student_number
        if (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $data['student_number'])) {
            flash('error', 'Student ID Number must be 3-50 characters (letters, numbers, hyphens only).');
            Session::flashOldInput($request->all());
            back();
        }

        // Strict duplicate check (case-insensitive, trimmed)
        $existing = Database::fetch(
            'SELECT id, student_number, first_name, last_name, graduation_year FROM graduates WHERE LOWER(TRIM(student_number)) = LOWER(?)',
            [$data['student_number']]
        );
        if ($existing) {
            flash('error', "Student ID Number '{$data['student_number']}' already exists and is assigned to {$existing['first_name']} {$existing['last_name']} (Batch {$existing['graduation_year']}). Each graduate must have a unique ID number.");
            Session::flashOldInput($request->all());
            back();
        }

        $validator = (new Validator($data))
            ->required('student_number', 'first_name', 'last_name', 'program_id', 'batch_id')
            ->maxLength('student_number', 50)
            ->unique('student_number', 'graduates')
            ->maxLength('first_name', 100)
            ->maxLength('middle_name', 100)
            ->maxLength('last_name', 100)
            ->email('email')
            ->unique('email', 'graduates')
            ->integer('program_id')->exists('program_id', 'programs')
            ->integer('batch_id')->exists('batch_id', 'batches')
            ->in('sex', ['Male', 'Female'])
            ->in('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])
            ->date('birth_date')
            ->integer('graduation_year')
            ->min('graduation_year', 1950)
            ->max('graduation_year', (int) date('Y') + 1);
        $validator->validateOrFail();

        $prog = Program::find((int) $data['program_id']);
        if ($prog && $prog['code'] === 'BAT') {
            $data['major'] = in_array($data['major'] ?? '', ['Animal Production', 'Crop Production'], true)
                ? $data['major']
                : (!empty($data['major']) ? trim((string) $data['major']) : 'Crop Production');
        } else {
            $data['major'] = null;
        }

        $batch = Batch::find((int) $data['batch_id']);
        $data['graduation_year'] = !empty($data['graduation_year'])
            ? (int) $data['graduation_year']
            : (int) ($batch['year'] ?? date('Y'));

        $id = Graduate::create($data);

        // Synchronize job_position with active employment_profile
        if (!empty($data['job_position'])) {
            Database::run(
                'INSERT INTO employment_profiles (graduate_id, status, job_title, is_current, created_at, updated_at)
                 VALUES (?, "employed", ?, 1, NOW(), NOW())',
                [$id, $data['job_position']]
            );
        } else {
            Database::run(
                'INSERT INTO employment_profiles (graduate_id, status, is_current, created_at, updated_at)
                 VALUES (?, "unemployed", 1, NOW(), NOW())',
                [$id]
            );
        }

        $this->audit('create', 'graduates', "Created graduate {$data['student_number']} ({$data['first_name']} {$data['last_name']}).");
        $this->success('Graduate record added successfully.', 'admin/graduates');
    }

    public function show(Request $request, array $params): void
    {
        $graduate = Graduate::find((int) $params['id']);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }
        $employment = Graduate::employment((int) $params['id']);

        $this->view('admin/graduates/show', [
            'title'       => $graduate['first_name'] . ' ' . $graduate['last_name'],
            'subtitle'    => 'Graduate profile & employment record',
            'graduate'    => $graduate,
            'employment'  => $employment,
            'surveyCount' => Graduate::surveyCount((int) $params['id']),
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $graduate = Graduate::find((int) $params['id']);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }
        $this->view('admin/graduates/edit', [
            'title'    => 'Edit Graduate',
            'subtitle' => $graduate['student_number'] . ' &middot; ' . $graduate['first_name'] . ' ' . $graduate['last_name'],
            'graduate' => $graduate,
            'programs' => Program::all(true),
            'majors'   => ['Animal Production', 'Crop Production'],
            'batches'  => Batch::all(true),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $graduate = Graduate::find($id);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $data = $request->all();

        // Trim and sanitize all string inputs
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }
        $data['student_number'] = strtoupper((string) ($data['student_number'] ?? ''));

        // Format validation on student_number
        if (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $data['student_number'])) {
            flash('error', 'Student ID Number must be 3-50 characters (letters, numbers, hyphens only).');
            Session::flashOldInput($request->all());
            back();
        }

        // Strict duplicate check (case-insensitive, trimmed, excluding current graduate)
        $existing = Database::fetch(
            'SELECT id, student_number, first_name, last_name, graduation_year FROM graduates WHERE LOWER(TRIM(student_number)) = LOWER(?) AND id != ?',
            [$data['student_number'], $id]
        );
        if ($existing) {
            flash('error', "Student ID Number '{$data['student_number']}' already belongs to graduate {$existing['first_name']} {$existing['last_name']} (Batch {$existing['graduation_year']}).");
            Session::flashOldInput($request->all());
            back();
        }

        $validator = (new Validator($data))
            ->required('student_number', 'first_name', 'last_name', 'program_id', 'batch_id')
            ->maxLength('student_number', 50)
            ->unique('student_number', 'graduates', null, $id)
            ->maxLength('first_name', 100)
            ->maxLength('middle_name', 100)
            ->maxLength('last_name', 100)
            ->email('email')
            ->unique('email', 'graduates', null, $id)
            ->integer('program_id')->exists('program_id', 'programs')
            ->integer('batch_id')->exists('batch_id', 'batches')
            ->in('sex', ['Male', 'Female'])
            ->in('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])
            ->date('birth_date')
            ->integer('graduation_year')
            ->min('graduation_year', 1950)
            ->max('graduation_year', (int) date('Y') + 1);
        $validator->validateOrFail();

        $prog = Program::find((int) $data['program_id']);
        if ($prog && $prog['code'] === 'BAT') {
            $data['major'] = in_array($data['major'] ?? '', ['Animal Production', 'Crop Production'], true)
                ? $data['major']
                : (!empty($data['major']) ? trim((string) $data['major']) : 'Crop Production');
        } else {
            $data['major'] = null;
        }

        $batch = Batch::find((int) $data['batch_id']);
        $data['graduation_year'] = !empty($data['graduation_year'])
            ? (int) $data['graduation_year']
            : (int) ($batch['year'] ?? date('Y'));

        Graduate::update($id, $data);

        // Synchronize job_position with active employment_profile
        $currentEp = Database::fetch('SELECT id, status, job_title FROM employment_profiles WHERE graduate_id = ? AND is_current = 1 AND deleted_at IS NULL LIMIT 1', [$id]);
        if (!empty($data['job_position'])) {
            if ($currentEp) {
                Database::run(
                    'UPDATE employment_profiles SET job_title = ?, status = CASE WHEN status = "unemployed" THEN "employed" ELSE status END, updated_at = NOW() WHERE id = ?',
                    [$data['job_position'], $currentEp['id']]
                );
            } else {
                Database::run(
                    'INSERT INTO employment_profiles (graduate_id, status, job_title, is_current, created_at, updated_at)
                     VALUES (?, "employed", ?, 1, NOW(), NOW())',
                    [$id, $data['job_position']]
                );
            }
        } elseif ($currentEp && !empty($currentEp['job_title']) && empty($data['job_position'])) {
            Database::run('UPDATE employment_profiles SET job_title = NULL, updated_at = NOW() WHERE id = ?', [$currentEp['id']]);
        }

        $this->audit('update', 'graduates', "Updated graduate {$graduate['student_number']} ({$graduate['first_name']} {$graduate['last_name']}).");
        $this->success('Graduate record updated successfully.', 'admin/graduates');
    }

    public function checkId(Request $request): never
    {
        $sn = strtoupper(trim((string) $request->query('student_number', '')));
        $ignoreId = (int) $request->query('ignore_id', 0);

        if ($sn === '') {
            $this->json(['available' => true, 'message' => '']);
        }

        if (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $sn)) {
            $this->json([
                'available' => false,
                'invalid'   => true,
                'message'   => 'Format should be 3-50 letters, numbers, hyphens (e.g. 2021-0001).'
            ]);
        }

        $sql = 'SELECT id, student_number, first_name, last_name, graduation_year FROM graduates WHERE LOWER(TRIM(student_number)) = LOWER(?)';
        $params = [$sn];
        if ($ignoreId > 0) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }

        $existing = Database::fetch($sql, $params);

        if ($existing) {
            $this->json([
                'available' => false,
                'existing'  => [
                    'name' => trim($existing['first_name'] . ' ' . $existing['last_name']),
                    'year' => $existing['graduation_year'],
                ],
                'message'   => "Already assigned to {$existing['first_name']} {$existing['last_name']} (Batch {$existing['graduation_year']})"
            ]);
        }

        $this->json([
            'available' => true,
            'message'   => 'Student ID number is available.'
        ]);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $graduate = Graduate::find($id);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }
        Graduate::softDelete($id);
        $this->audit('delete', 'graduates', "Deleted graduate {$graduate['student_number']} ({$graduate['first_name']} {$graduate['last_name']}).");
        $this->success('Graduate record moved to trash.', 'admin/graduates');
    }

    public function validate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $graduate = Graduate::find($id);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        $adminId = (int) (auth()->id ?? 1);
        Graduate::validate($id, $adminId);

        $name = trim(($graduate['first_name'] ?? '') . ' ' . ($graduate['last_name'] ?? ''));
        $this->audit('validate', 'graduates', "Validated graduate information for {$graduate['student_number']} ({$name}).");

        // Send in-app notification to the graduate user if linked
        if (!empty($graduate['user_id'])) {
            Database::run(
                'INSERT INTO notifications (user_id, type, channel, subject, body, created_at)
                 VALUES (?, "system_alert", "in_app", "Graduate Information Validated", "Your graduate record and academic credentials have been verified and validated by the IAT Administration.", NOW())',
                [(int) $graduate['user_id']]
            );
        }

        flash('success', "Graduate record for {$name} ({$graduate['student_number']}) has been validated.");
        back();
    }

    public function unvalidate(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $graduate = Graduate::find($id);
        if (!$graduate) {
            abort(404, 'Graduate not found.');
        }

        Graduate::unvalidate($id);

        $name = trim(($graduate['first_name'] ?? '') . ' ' . ($graduate['last_name'] ?? ''));
        $this->audit('unvalidate', 'graduates', "Revoked validation for graduate {$graduate['student_number']} ({$name}).");

        flash('info', "Validation status revoked for {$name} ({$graduate['student_number']}).");
        back();
    }

    /**
     * Export the graduate list as CSV or XLSX.
     */
    public function export(Request $request): never
    {
        $filters = [
            'search'     => trim((string) $request->query('search')),
            'program_id' => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'major'      => trim((string) $request->query('major')),
            'batch_id'   => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'validated'  => $request->query('validated') !== null && $request->query('validated') !== '' ? (int) $request->query('validated') : null,
            'status'     => trim((string) $request->query('status')),
        ];
        $rows = Graduate::paginate(array_filter($filters, fn ($v) => $v !== null && $v !== ''), 1, 100000)['items'];

        $header = [
            'Student Number', 'Last Name', 'First Name', 'Middle Name', 'Suffix', 'Email',
            'Contact Number', 'Sex', 'Program', 'Major', 'Batch Year', 'Job Position', 'Address', 'Employment Status', 'Proof Image Link',
            'Uploaded Proof Image', 'Proof Uploaded Date', 'Validated',
        ];
        $data = array_map(static function ($g) {
            $uploadedProofUrl = !empty($g['proof_image_path']) && !empty($g['employment_profile_id'])
                ? url('admin/employment/' . (int) $g['employment_profile_id'] . '/proof')
                : '';
            $proofUploadedAt = !empty($g['proof_uploaded_at'])
                ? date('Y-m-d H:i:s', strtotime((string) $g['proof_uploaded_at']))
                : '';

            return [
                $g['student_number'], $g['last_name'], $g['first_name'], $g['middle_name'] ?? '', $g['suffix'] ?? '',
                $g['email'] ?? '', $g['contact_number'] ?? '', $g['sex'] ?? '', $g['program_code'],
                $g['major'] ?? '',
                $g['batch_year'],
                $g['job_position'] ?? ($g['job_title'] ?? ''),
                $g['address'] ?? '',
                $g['employment_status'] ?? '', $g['proof_image_url'] ?? '',
                $uploadedProofUrl, $proofUploadedAt, $g['is_validated'] ? 'Yes' : 'No',
            ];
        }, $rows);

        $format = strtolower((string) $request->query('format', 'csv'));
        if ($format === 'xlsx') {
            $this->exportXlsx($header, $data);
        }
        $this->exportCsv($header, $data);
    }

    private function exportCsv(array $header, array $rows): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="iat-graduates-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '');
        }
        fclose($out);
        exit;
    }

    private function exportXlsx(array $header, array $rows): never
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_merge([$header], $rows), null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="iat-graduates-' . date('Y-m-d') . '.xlsx"');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function import(Request $request): void
    {
        $this->view('admin/graduates/import', [
            'title'    => 'Import Graduates',
            'subtitle' => 'Bulk-add graduates from a CSV file',
        ]);
    }

    public function importProcess(Request $request): never
    {
        Csrf::validateOrAbort();
        $file = $request->file('csv_file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->error('Please choose a CSV file to import.', 'admin/graduates/import');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $this->error('Only CSV files are supported.', 'admin/graduates/import');
        }

        $programs = [];
        foreach (Program::all(false) as $p) {
            $programs[strtoupper($p['code'])] = (int) $p['id'];
        }
        $batches = [];
        foreach (Batch::all(false) as $b) {
            $batches[(int) $b['year']] = (int) $b['id'];
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->error('Unable to read the uploaded file.', 'admin/graduates/import');
        }
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->error('The CSV file appears to be empty.', 'admin/graduates/import');
        }
        // Normalize header keys (strip UTF-8 BOM and whitespace).
        $header = array_map(static function ($h) {
            return preg_replace('/^\xEF\xBB\xBF/', '', strtolower(trim((string) $h)));
        }, $header);

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }
            $assoc = [];
            foreach ($header as $i => $col) {
                $assoc[$col] = $row[$i] ?? '';
            }

            $payload = Graduate::payloadFromCsvRow($assoc, $programs, $batches);
            if ($payload === null) {
                $skipped++;
                continue;
            }
            if (Graduate::findByStudentNumber($payload['student_number'])) {
                $skipped++;
                continue;
            }
            $graduateId = Graduate::create($payload);

            // Automatically create current employment profile
            if (!empty($payload['job_position'])) {
                Database::run(
                    'INSERT INTO employment_profiles (graduate_id, status, job_title, is_current, created_at, updated_at)
                     VALUES (?, "employed", ?, 1, NOW(), NOW())',
                    [$graduateId, $payload['job_position']]
                );
            } else {
                Database::run(
                    'INSERT INTO employment_profiles (graduate_id, status, is_current, created_at, updated_at)
                     VALUES (?, "unemployed", 1, NOW(), NOW())',
                    [$graduateId]
                );
            }

            $imported++;
        }
        fclose($handle);

        $this->audit('import', 'graduates', "CSV import finished: {$imported} imported, {$skipped} skipped.");
        flash('success', "Import completed: {$imported} graduates added, {$skipped} rows skipped.");
        redirect('admin/graduates');
    }

    public function downloadTemplate(): never
    {
        $header = [
            'student_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'email',
            'contact_number', 'sex', 'program_code', 'major', 'batch_year', 'job_position', 'address',
        ];
        $sample = [
            'IAT-2026-0001', 'Juan', 'Dela', 'Cruz', '', 'juan.delacruz@example.com',
            '09171234567', 'Male', 'BAT', 'Animal Production', '2026', 'Farm Supervisor', 'San Fermin, Cauayan City, Isabela',
        ];
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="graduates-import-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ',', '"', '');
        fputcsv($out, $sample, ',', '"', '');
        fclose($out);
        exit;
    }
}
