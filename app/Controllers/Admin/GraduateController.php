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
            'batches'  => Batch::all(true),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

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

        $batch = Batch::find((int) $data['batch_id']);
        $data['graduation_year'] = !empty($data['graduation_year'])
            ? (int) $data['graduation_year']
            : (int) ($batch['year'] ?? date('Y'));

        $id = Graduate::create($data);
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

        $batch = Batch::find((int) $data['batch_id']);
        $data['graduation_year'] = !empty($data['graduation_year'])
            ? (int) $data['graduation_year']
            : (int) ($batch['year'] ?? date('Y'));

        Graduate::update($id, $data);
        $this->audit('update', 'graduates', "Updated graduate {$graduate['student_number']} ({$graduate['first_name']} {$graduate['last_name']}).");
        $this->success('Graduate record updated successfully.', 'admin/graduates');
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

    /**
     * Export the graduate list as CSV or XLSX.
     */
    public function export(Request $request): never
    {
        $filters = [
            'search'     => trim((string) $request->query('search')),
            'program_id' => $request->query('program_id') !== null && $request->query('program_id') !== '' ? (int) $request->query('program_id') : null,
            'batch_id'   => $request->query('batch_id') !== null && $request->query('batch_id') !== '' ? (int) $request->query('batch_id') : null,
            'validated'  => $request->query('validated') !== null && $request->query('validated') !== '' ? (int) $request->query('validated') : null,
            'status'     => trim((string) $request->query('status')),
        ];
        $rows = Graduate::paginate(array_filter($filters, fn ($v) => $v !== null && $v !== ''), 1, 100000)['items'];

        $header = [
            'Student Number', 'Last Name', 'First Name', 'Middle Name', 'Suffix', 'Email',
            'Contact Number', 'Sex', 'Program', 'Batch Year', 'Employment Status', 'Validated',
        ];
        $data = array_map(static function ($g) {
            return [
                $g['student_number'], $g['last_name'], $g['first_name'], $g['middle_name'] ?? '', $g['suffix'] ?? '',
                $g['email'] ?? '', $g['contact_number'] ?? '', $g['sex'] ?? '', $g['program_code'],
                $g['batch_year'], $g['employment_status'] ?? '', $g['is_validated'] ? 'Yes' : 'No',
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
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

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
            Graduate::create($payload);
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
            'contact_number', 'sex', 'program_code', 'batch_year',
        ];
        $sample = [
            'IAT-2026-0001', 'Juan', 'Dela', 'Cruz', '', 'juan.delacruz@example.com',
            '09171234567', 'Male', 'BAT', '2026',
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
