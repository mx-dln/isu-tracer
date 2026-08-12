<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * Audit log viewer (admin).
 */
class AuditLogController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $module = trim((string) $request->query('module'));
        $action = trim((string) $request->query('action'));
        $page = max(1, (int) $request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(al.description LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR al.ip_address LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($module !== '') {
            $where[] = 'al.module = ?';
            $params[] = $module;
        }
        if ($action !== '') {
            $where[] = 'al.action = ?';
            $params[] = $action;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id WHERE ' . $whereSql,
            $params
        )['c'];

        $logs = Database::fetchAll(
            'SELECT al.*, u.name AS user_name, u.email AS user_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE ' . $whereSql . '
             ORDER BY al.created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        $modules = array_column(Database::fetchAll(
            'SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module'
        ), 'module');
        $actions = array_column(Database::fetchAll(
            'SELECT DISTINCT action FROM audit_logs ORDER BY action'
        ), 'action');

        $this->view('admin/audit-logs/index', [
            'title'    => 'Audit Logs',
            'subtitle' => 'System activity trail',
            'logs'     => $logs,
            'modules'  => $modules,
            'actions'  => $actions,
            'search'   => $search,
            'module'   => $module,
            'action'   => $action,
            'page'     => $page,
            'pages'    => (int) ceil($total / $limit),
            'total'    => $total,
        ]);
    }
}
