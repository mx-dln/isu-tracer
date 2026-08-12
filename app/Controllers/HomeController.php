<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;

/**
 * Root route: send users to the appropriate dashboard.
 */
class HomeController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        redirect(Auth::user()->role_slug === 'admin' ? 'admin/dashboard' : 'graduate/dashboard');
    }
}
