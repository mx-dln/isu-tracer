<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * Login log viewer (admin).
 */
class LoginLogController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $page = max(1, (int) $request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR ll.ip_address LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status === 'success' || $status === 'failed') {
            $where[] = 'll.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM login_logs ll LEFT JOIN users u ON u.id = ll.user_id WHERE ' . $whereSql,
            $params
        )['c'];

        $logs = Database::fetchAll(
            'SELECT ll.*, u.name AS user_name, u.email AS user_email
             FROM login_logs ll
             LEFT JOIN users u ON u.id = ll.user_id
             WHERE ' . $whereSql . '
             ORDER BY ll.login_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        $recentFailures = Database::fetchAll(
            'SELECT ip_address, COUNT(*) AS attempts, MAX(login_at) AS last_attempt
             FROM login_logs
             WHERE status = "failed" AND login_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY ip_address
             HAVING attempts >= 3
             ORDER BY attempts DESC
             LIMIT 10'
        );

        $this->view('admin/login-logs/index', [
            'title'          => 'Login Logs',
            'subtitle'       => 'Authentication activity and failed attempts',
            'logs'           => $logs,
            'recentFailures' => $recentFailures,
            'search'         => $search,
            'status'         => $status,
            'page'           => $page,
            'pages'          => (int) ceil($total / $limit),
            'total'          => $total,
        ]);
    }
}
