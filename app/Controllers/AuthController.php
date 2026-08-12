<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\Mailer;
use App\Validators\Validator;

/**
 * Authentication controller: login, logout, registration, password reset.
 */
class AuthController extends Controller
{
    public function showLoginForm(Request $request): void
    {
        if (Session::get('_login_locked_until') && time() < Session::get('_login_locked_until')) {
            $remaining = Session::get('_login_locked_until') - time();
            View::render('auth/login', [
                'lockoutRemaining' => $remaining,
            ], 'auth');
            return;
        }
        View::render('auth/login', [], 'auth');
    }

    public function login(Request $request): void
    {
        Csrf::validateOrAbort();

        if (Auth::isLockedOut()) {
            flash('error', 'Too many failed login attempts. Please try again later.');
            redirect('login');
        }

        $validator = (new Validator($request->all()))
            ->required('email', 'password')
            ->email('email');
        $validator->validateOrFail('login');

        $credentials = $request->all();
        if (Auth::attempt((string) $credentials['email'], (string) $credentials['password'])) {
            $user = Auth::user();
            $destination = $user->role_slug === 'admin' ? 'admin/dashboard' : 'graduate/dashboard';

            if ((int) $user->must_change_password === 1) {
                flash('warning', 'Please change your password before continuing.');
                redirect('auth/password-change');
            }

            $this->audit('login', 'auth', "User logged in: {$user->email}");
            redirect($destination);
        }

        flash('error', 'Invalid email or password.');
        redirect('login');
    }

    public function logout(Request $request): void
    {
        Csrf::validateOrAbort();
        $user = Auth::user();
        if ($user) {
            $this->audit('logout', 'auth', "User logged out: {$user->email}");
        }
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('login');
    }

    public function showRegisterForm(Request $request): void
    {
        View::render('auth/register', [
            'title'    => 'Create an Account',
            'programs' => Database::fetchAll('SELECT id, code, name FROM programs WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name'),
            'batches'  => Database::fetchAll('SELECT id, year, label FROM batches WHERE is_active = 1 AND deleted_at IS NULL ORDER BY year DESC'),
        ], 'auth');
    }

    public function register(Request $request): void
    {
        Csrf::validateOrAbort();

        $data = $request->all();
        $validator = (new Validator($data))
            ->required('student_number', 'first_name', 'last_name', 'email', 'password', 'program_id', 'batch_id')
            ->email('email')
            ->unique('email', 'users')
            ->unique('student_number', 'graduates', 'student_number')
            ->in('program_id', $this->optionIds('programs'))
            ->in('batch_id', $this->optionIds('batches'))
            ->password('password')
            ->confirmed('password');

        $validator->validateOrFail('register');

        try {
            $graduateId = Database::transaction(function () use ($data) {
                // Try to link an existing graduate record (imported by admin).
                $existing = Database::fetch(
                    'SELECT id FROM graduates WHERE student_number = ? AND deleted_at IS NULL',
                    [$data['student_number']]
                );

                if ($existing) {
                    $graduateId = (int) $existing['id'];
                    Database::run(
                        'UPDATE graduates SET email = ?, first_name = ?, middle_name = ?, last_name = ?, contact_number = ? WHERE id = ?',
                        [
                            $data['email'],
                            trim((string) $data['first_name']),
                            !empty($data['middle_name']) ? trim((string) $data['middle_name']) : null,
                            trim((string) $data['last_name']),
                            $data['contact_number'] ?? null,
                            $graduateId,
                        ]
                    );
                } else {
                    Database::run(
                        'INSERT INTO graduates (student_number, first_name, middle_name, last_name, email, contact_number, program_id, batch_id, graduation_year, is_validated, is_demo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)',
                        [
                            $data['student_number'],
                            trim((string) $data['first_name']),
                            !empty($data['middle_name']) ? trim((string) $data['middle_name']) : null,
                            trim((string) $data['last_name']),
                            $data['email'],
                            $data['contact_number'] ?? null,
                            $data['program_id'],
                            $data['batch_id'],
                            (int) Database::fetch('SELECT year FROM batches WHERE id = ?', [$data['batch_id']])['year'],
                        ]
                    );
                    $graduateId = (int) Database::lastInsertId();
                }

                Database::run(
                    'INSERT INTO users (role_id, graduate_id, name, email, password, is_active, must_change_password)
                     SELECT id, ?, ?, ?, ?, 1, 0 FROM roles WHERE slug = "graduate"',
                    [$graduateId, trim((string) $data['first_name']) . ' ' . trim((string) $data['last_name']), $data['email'], password_hash((string) $data['password'], PASSWORD_DEFAULT)]
                );

                return $graduateId;
            });
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Registration failed: ' . $e->getMessage());
            flash('error', 'Registration failed. Please try again.');
            redirect('register');
        }

        Auth::login((int) Database::fetch('SELECT id FROM users WHERE graduate_id = ?', [$graduateId])['id']);
        $this->audit('register', 'auth', "New graduate account registered (student #{$data['student_number']})");
        flash('success', 'Your account has been created. Welcome!');
        redirect('graduate/dashboard');
    }

    /**
     * Helper: fetch valid option ids for a table for the 'in' rule.
     */
    private function optionIds(string $table): array
    {
        return array_column(Database::fetchAll("SELECT id FROM {$table} WHERE deleted_at IS NULL"), 'id');
    }

    // ------------------------------------------------------------------
    // Password reset structure
    // ------------------------------------------------------------------

    public function showForgotForm(Request $request): void
    {
        View::render('auth/forgot', ['title' => 'Forgot Password'], 'auth');
    }

    public function sendResetLink(Request $request): void
    {
        Csrf::validateOrAbort();
        $email = (string) $request->input('email', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please provide a valid email address.');
            redirect('auth/forgot');
        }

        $user = Database::fetch('SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::run(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))',
                [$user['id'], $token]
            );
            Mailer::send(
                (string) $user['email'],
                'Password Reset Request',
                \App\Core\View::partial('emails/password_reset', [
                    'name'  => 'Graduate',
                    'link'  => url('auth/password-reset/' . $token),
                ])
            );
            $this->audit('password_reset_request', 'auth', "Password reset requested for {$email}");
        }

        // Always show the same message to avoid account enumeration.
        flash('success', 'If that email exists, a password reset link has been sent.');
        redirect('login');
    }

    public function showResetForm(Request $request, array $params): void
    {
        $token = $params['token'];
        $reset = Database::fetch(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW()',
            [$token]
        );
        if (!$reset) {
            flash('error', 'This password reset link is invalid or has expired.');
            redirect('auth/forgot');
        }
        View::render('auth/reset', ['token' => $token, 'title' => 'Set New Password'], 'auth');
    }

    public function resetPassword(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $token = (string) $params['token'];
        $validator = (new Validator($request->all()))->required('password')->password('password')->confirmed('password');
        $validator->validateOrFail();

        $reset = Database::fetch(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW()',
            [$token]
        );
        if (!$reset) {
            flash('error', 'This password reset link is invalid or has expired.');
            redirect('auth/forgot');
        }

        Database::transaction(function () use ($reset, $request, $token): void {
            Database::run('UPDATE users SET password = ? WHERE id = ?', [
                password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
                $reset['user_id'],
            ]);
            Database::run('UPDATE password_resets SET used_at = NOW() WHERE token = ?', [$token]);
        });

        $this->audit('password_reset', 'auth', 'Password reset completed');
        flash('success', 'Your password has been reset. Please sign in.');
        redirect('login');
    }
}
