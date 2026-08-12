<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Validators\Validator;

/**
 * User account management (admin).
 */
class UserController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $role = trim((string) $request->query('role'));
        $page = max(1, (int) $request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $where = ['u.deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($role !== '') {
            $where[] = 'r.slug = ?';
            $params[] = $role;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.id = u.role_id WHERE ' . $whereSql,
            $params
        )['c'];

        $users = Database::fetchAll(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    g.student_number, g.program_id, g.batch_id, p.code AS program_code, b.year AS batch_year
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN graduates g ON g.id = u.graduate_id
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             WHERE ' . $whereSql . '
             ORDER BY u.created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        $this->view('admin/users/index', [
            'title'    => 'Users',
            'subtitle' => 'Manage system user accounts',
            'users'    => $users,
            'roles'    => Database::fetchAll('SELECT * FROM roles ORDER BY name'),
            'search'   => $search,
            'role'     => $role,
            'page'     => $page,
            'pages'    => (int) ceil($total / $limit),
            'total'    => $total,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/users/create', [
            'title'    => 'Add User',
            'subtitle' => 'Create a new user account',
            'roles'    => Database::fetchAll('SELECT * FROM roles ORDER BY name'),
        ]);
    }

    public function store(Request $request): never
    {
        Csrf::validateOrAbort();
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('name', 'email', 'password', 'role_id')
            ->email('email')
            ->unique('email', 'users')
            ->password('password')
            ->in('role_id', array_column(Database::fetchAll('SELECT id FROM roles'), 'id'));
        $validator->validateOrFail();

        Database::run(
            'INSERT INTO users (role_id, graduate_id, name, email, password, is_active, must_change_password)
             VALUES (?, NULL, ?, ?, ?, ?, 0)',
            [
                $data['role_id'],
                trim((string) $data['name']),
                trim((string) $data['email']),
                password_hash((string) $data['password'], PASSWORD_DEFAULT),
                isset($data['is_active']) ? 1 : 0,
            ]
        );

        $this->audit('create', 'users', "Created user account for {$data['email']}.");
        $this->success('User created successfully.', 'admin/users');
    }

    public function edit(Request $request, array $params): void
    {
        $user = $this->findUser((int) $params['id']);
        $this->view('admin/users/create', [
            'title'    => 'Edit User',
            'subtitle' => $user['name'],
            'user'     => $user,
            'roles'    => Database::fetchAll('SELECT * FROM roles ORDER BY name'),
        ]);
    }

    public function update(Request $request, array $params): never
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $user = $this->findUser($id);
        $data = $request->all();

        $validator = (new Validator($data))
            ->required('name', 'email', 'role_id')
            ->email('email')
            ->unique('email', 'users', null, $id)
            ->in('role_id', array_column(Database::fetchAll('SELECT id FROM roles'), 'id'));
        $validator->validateOrFail();

        if (!empty($data['password'])) {
            if (strlen((string) $data['password']) < 8) {
                $this->error('Password must be at least 8 characters.', 'admin/users/' . $id . '/edit');
            }
        }

        Database::run(
            'UPDATE users SET name = ?, email = ?, role_id = ?, is_active = ? WHERE id = ?',
            [
                trim((string) $data['name']),
                trim((string) $data['email']),
                $data['role_id'],
                isset($data['is_active']) ? 1 : 0,
                $id,
            ]
        );

        if (!empty($data['password'])) {
            Database::run('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?', [
                password_hash((string) $data['password'], PASSWORD_DEFAULT),
                $id,
            ]);
        }

        $this->audit('update', 'users', "Updated user account for {$data['email']}.");
        $this->success('User updated successfully.', 'admin/users');
    }

    public function destroy(Request $request, array $params): never
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $user = $this->findUser($id);

        if ($id === (int) (auth()?->id ?? 0)) {
            $this->error('You cannot delete your own account.', 'admin/users');
        }
        if ((string) $user['email'] === 'admin@example.com') {
            $this->error('The primary administrator account cannot be deleted.', 'admin/users');
        }

        // Soft delete; reassign nothing (notifications/audit logs cascade-set-null).
        Database::run('UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = ?', [$id]);
        $this->audit('delete', 'users', "Deleted user account for {$user['email']}.");
        $this->success('User removed.', 'admin/users');
    }

    private function findUser(int $id): array
    {
        $user = Database::fetch(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.deleted_at IS NULL',
            [$id]
        );
        if (!$user) {
            abort(404, 'User not found.');
        }
        return $user;
    }
}
