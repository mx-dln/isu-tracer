<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use Closure;

/**
 * Requires an authenticated user; otherwise redirect to login.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Closure $next): void
    {
        if (!Auth::check()) {
            flash('warning', 'Please sign in to continue.');
            redirect('login');
        }
        $next();
    }
}
