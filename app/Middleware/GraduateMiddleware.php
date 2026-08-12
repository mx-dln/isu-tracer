<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use Closure;

/**
 * Restricts a route to IAT graduates only.
 */
class GraduateMiddleware implements MiddlewareInterface
{
    public function handle(Closure $next): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        if (Auth::user()->role_slug !== 'graduate') {
            abort(403, 'You do not have permission to access this page.');
        }
        $next();
    }
}
