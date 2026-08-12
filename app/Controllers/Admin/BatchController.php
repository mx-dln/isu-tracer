<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\Batch;
use App\Validators\Validator;

/**
 * Graduation batch management (admin).
 */
class BatchController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/batches/index', [
            'title'    => 'Batches',
            'subtitle' => 'Graduation cohorts of the IAT',
            'batches'  => Batch::paginate($search, $page, 15),
            'search'   => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/batches/create', [
            'title'    => 'Add Batch',
            'subtitle' => 'Register a new graduation cohort',
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('year')
            ->integer('year')
            ->min('year', 1950)
            ->max('year', (int) date('Y') + 1)
            ->unique('year', 'batches');
        $validator->validateOrFail();

        $id = Batch::create($data);
        $this->audit('create', 'batches', "Created graduation batch for year {$data['year']}.");
        $this->success('Batch added successfully.', 'admin/batches');
    }

    public function edit(Request $request, array $params): void
    {
        $batch = Batch::find((int) $params['id']);
        if (!$batch) {
            abort(404, 'Batch not found.');
        }
        $this->view('admin/batches/edit', [
            'title'   => 'Edit Batch',
            'subtitle' => (string) $batch['year'],
            'batch'   => $batch,
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $batch = Batch::find($id);
        if (!$batch) {
            abort(404, 'Batch not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('year')
            ->integer('year')
            ->min('year', 1950)
            ->max('year', (int) date('Y') + 1)
            ->unique('year', 'batches', null, $id);
        $validator->validateOrFail();

        Batch::update($id, $data);
        $this->audit('update', 'batches', "Updated graduation batch to year {$data['year']}.");
        $this->success('Batch updated successfully.', 'admin/batches');
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $batch = Batch::find($id);
        if (!$batch) {
            abort(404, 'Batch not found.');
        }
        if ((int) Database::fetch('SELECT COUNT(*) AS c FROM graduates WHERE batch_id = ? AND deleted_at IS NULL', [$id])['c'] > 0) {
            $this->error('This batch still has graduates assigned and cannot be deleted.', 'admin/batches');
        }
        Batch::softDelete($id);
        $this->audit('delete', 'batches', "Deleted graduation batch {$batch['year']}.");
        $this->success('Batch removed.', 'admin/batches');
    }
}
