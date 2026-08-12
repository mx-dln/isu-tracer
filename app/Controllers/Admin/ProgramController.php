<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\Program;
use App\Validators\Validator;

/**
 * Program (degree offering) management (admin).
 */
class ProgramController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/programs/index', [
            'title'    => 'Programs',
            'subtitle' => 'Degree and curriculum offerings of the IAT',
            'programs' => Program::paginate($search, $page, 15),
            'search'   => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/programs/create', [
            'title'    => 'Add Program',
            'subtitle' => 'Register a new degree offering',
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('code', 'name')
            ->maxLength('code', 20)
            ->maxLength('name', 191)
            ->unique('code', 'programs');
        $validator->validateOrFail();

        $id = Program::create($data);
        $this->audit('create', 'programs', "Created program {$data['code']} — {$data['name']}.");
        $this->success('Program added successfully.', 'admin/programs');
    }

    public function edit(Request $request, array $params): void
    {
        $program = Program::find((int) $params['id']);
        if (!$program) {
            abort(404, 'Program not found.');
        }
        $this->view('admin/programs/edit', [
            'title'   => 'Edit Program',
            'subtitle' => $program['code'],
            'program' => $program,
        ]);
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $program = Program::find($id);
        if (!$program) {
            abort(404, 'Program not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('code', 'name')
            ->maxLength('code', 20)
            ->maxLength('name', 191)
            ->unique('code', 'programs', null, $id);
        $validator->validateOrFail();

        Program::update($id, $data);
        $this->audit('update', 'programs', "Updated program {$data['code']} — {$data['name']}.");
        $this->success('Program updated successfully.', 'admin/programs');
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $program = Program::find($id);
        if (!$program) {
            abort(404, 'Program not found.');
        }
        if ((int) Database::fetch('SELECT COUNT(*) AS c FROM graduates WHERE program_id = ? AND deleted_at IS NULL', [$id])['c'] > 0) {
            $this->error('This program still has graduates assigned and cannot be deleted.', 'admin/programs');
        }
        Program::softDelete($id);
        $this->audit('delete', 'programs', "Deleted program {$program['code']}.");
        $this->success('Program removed.', 'admin/programs');
    }
}
