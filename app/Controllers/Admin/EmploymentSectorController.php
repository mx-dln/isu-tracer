<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\EmploymentSector;
use App\Validators\Validator;

/**
 * Employment sector reference management (admin).
 */
class EmploymentSectorController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $page = max(1, (int) $request->query('page', 1));

        $this->view('admin/employment/sectors', [
            'title'    => 'Employment Sectors',
            'subtitle' => 'Industry sectors used to classify employment',
            'sectors'  => EmploymentSector::paginate($search, $page, 15),
            'search'   => $search,
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('name')
            ->maxLength('name', 191)
            ->unique('name', 'employment_sectors');
        $validator->validateOrFail();

        $id = EmploymentSector::create($data['name']);
        $this->audit('create', 'employment', "Created employment sector: {$data['name']}.");
        $this->success('Sector added.', 'admin/employment/sectors');
    }

    public function update(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $sector = EmploymentSector::find((int) $params['id']);
        if (!$sector) {
            abort(404, 'Sector not found.');
        }

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('name')
            ->maxLength('name', 191)
            ->unique('name', 'employment_sectors', null, (int) $params['id']);
        $validator->validateOrFail();

        EmploymentSector::update((int) $params['id'], $data['name'], !empty($data['is_active']));
        $this->audit('update', 'employment', "Updated employment sector: {$data['name']}.");
        $this->success('Sector updated.', 'admin/employment/sectors');
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $sector = EmploymentSector::find((int) $params['id']);
        if (!$sector) {
            abort(404, 'Sector not found.');
        }
        EmploymentSector::softDelete((int) $params['id']);
        $this->audit('delete', 'employment', "Deleted employment sector: {$sector['name']}.");
        $this->success('Sector removed.', 'admin/employment/sectors');
    }
}
