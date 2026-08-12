<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Core\Request;

/**
 * Audit logging service.
 */
final class Audit
{
    public static function log(string $action, string $module, string $description): void
    {
        $request = new Request();
        Database::run(
            'INSERT INTO audit_logs (user_id, action, module, description, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                Auth::id(),
                $action,
                $module,
                mb_substr($description, 0, 4000),
                $request->ip(),
                $request->userAgent(),
            ]
        );
    }
}
