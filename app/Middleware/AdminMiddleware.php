<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use Closure;

/**
 * Restricts a route to administrators only.
 */
class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Closure $next): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        if (Auth::user()->role_slug !== 'admin') {
            abort(403, 'You do not have permission to access this page.');
        }
        $next();
    }
}
