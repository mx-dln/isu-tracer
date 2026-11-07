<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use Closure;

/**
 * Requires an authenticated user; otherwise redirect to login.
 * Enforces server-side auto-logout after inactivity.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Closure $next): void
    {
        if (!Auth::check()) {
            flash('warning', 'Please sign in to continue.');
            redirect('login');
        }

        // Auto logout timeout (default 15 minutes = 900 seconds)
        $timeoutMinutes = (int) setting('auto_logout_minutes', 15);
        if ($timeoutMinutes <= 0) {
            $timeoutMinutes = 15;
        }
        $timeoutSeconds = $timeoutMinutes * 60;

        $lastActivity = Session::get('_last_activity');
        $now = time();

        if ($lastActivity !== null && ($now - (int) $lastActivity > $timeoutSeconds)) {
            $user = Auth::user();
            if ($user) {
                Logger::info('User automatically logged out due to inactivity', [
                    'user_id' => $user->id ?? null,
                    'email'   => $user->email ?? null,
                    'idle_s'  => $now - (int) $lastActivity,
                ]);
            }
            Auth::logout();
            flash('warning', 'You have been automatically logged out due to inactivity.');
            redirect('login?timeout=1');
        }

        Session::put('_last_activity', $now);
        $next();
    }
}
