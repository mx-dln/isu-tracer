<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use Closure;

/**
 * Redirects authenticated users away from guest-only pages.
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Closure $next): void
    {
        if (Auth::check()) {
            redirect(Auth::user()->role_slug === 'admin' ? 'admin/dashboard' : 'graduate/dashboard');
        }
        $next();
    }
}
