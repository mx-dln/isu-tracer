<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

/**
 * Notification center (admin): templates, rules, manual broadcast.
 */
class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('search'));
        $page = max(1, (int) $request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where = '(n.title LIKE ? OR n.message LIKE ?)';
            $params = ["%{$search}%", "%{$search}%"];
        }

        $total = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM notifications n WHERE ' . $where,
            $params
        )['c'];

        $notifications = Database::fetchAll(
            'SELECT n.*, u.name AS user_name
             FROM notifications n
             LEFT JOIN users u ON u.id = n.user_id
             WHERE ' . $where . '
             ORDER BY n.created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        $this->view('admin/notifications/index', [
            'title'     => 'Notifications',
            'subtitle'  => 'In-app notifications, templates and automated rules',
            'notifications' => $notifications,
            'templates' => Database::fetchAll('SELECT * FROM notification_templates ORDER BY type'),
            'rules'     => Database::fetchAll('SELECT * FROM notification_rules ORDER BY name'),
            'recentResults' => $request->query('ran') ? (new NotificationService())->runRules() : [],
            'search'    => $search,
            'page'      => $page,
            'pages'     => (int) ceil($total / $limit),
            'total'     => $total,
        ]);
    }

    public function storeTemplate(Request $request): never
    {
        Csrf::validateOrAbort();
        $data = $request->all();
        if (trim((string) ($data['type'] ?? '')) === '' || trim((string) ($data['body'] ?? '')) === '') {
            $this->error('Type and body are required.', 'admin/notifications');
        }

        $exists = Database::fetch('SELECT id FROM notification_templates WHERE type = ?', [$data['type']]);
        if ($exists) {
            Database::run(
                'UPDATE notification_templates SET channel = ?, subject = ?, body = ?, is_active = ? WHERE id = ?',
                [$data['channel'] ?? 'in_app', $data['subject'] ?? null, $data['body'], isset($data['is_active']) ? 1 : 0, $exists['id']]
            );
            $this->audit('update', 'notifications', "Updated notification template '{$data['type']}'.");
            $this->success('Template updated.', 'admin/notifications');
        }

        Database::run(
            'INSERT INTO notification_templates (type, channel, subject, body, is_active) VALUES (?, ?, ?, ?, ?)',
            [$data['type'], $data['channel'] ?? 'in_app', $data['subject'] ?? null, $data['body'], isset($data['is_active']) ? 1 : 0]
        );
        $this->audit('create', 'notifications', "Created notification template '{$data['type']}'.");
        $this->success('Template created.', 'admin/notifications');
    }

    public function toggleTemplate(Request $request, array $params): never
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $template = Database::fetch('SELECT * FROM notification_templates WHERE id = ?', [$id]);
        if (!$template) {
            abort(404, 'Template not found.');
        }
        Database::run('UPDATE notification_templates SET is_active = ? WHERE id = ?', [$template['is_active'] ? 0 : 1, $id]);
        $this->audit('update', 'notifications', "Toggled notification template '{$template['type']}'.");
        $this->success('Template updated.', 'admin/notifications');
    }

    public function toggleRule(Request $request, array $params): never
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $rule = Database::fetch('SELECT * FROM notification_rules WHERE id = ?', [$id]);
        if (!$rule) {
            abort(404, 'Rule not found.');
        }
        Database::run('UPDATE notification_rules SET is_active = ? WHERE id = ?', [$rule['is_active'] ? 0 : 1, $id]);
        $this->audit('update', 'notifications', "Toggled notification rule '{$rule['rule_key']}'.");
        $this->success('Rule updated.', 'admin/notifications');
    }

    public function send(Request $request): never
    {
        Csrf::validateOrAbort();
        $title = trim((string) $request->input('title'));
        $message = trim((string) $request->input('message'));
        if ($title === '' || $message === '') {
            $this->error('Title and message are required.', 'admin/notifications');
        }

        $service = new NotificationService();
        $count = $service->notifyAllGraduates(
            'manual_broadcast',
            $title,
            $message,
            'in_app',
            'graduate/dashboard',
            $request->input('program_id') !== '' && $request->input('program_id') !== null ? (int) $request->input('program_id') : null,
            $request->input('batch_id') !== '' && $request->input('batch_id') !== null ? (int) $request->input('batch_id') : null
        );

        $this->audit('send', 'notifications', "Broadcast notification to {$count} graduate(s): {$title}");
        $this->success("Notification sent to {$count} graduate(s).", 'admin/notifications');
    }

    public function runRules(Request $request): never
    {
        Csrf::validateOrAbort();
        $service = new NotificationService();
        $results = $service->runRules();
        $total = array_sum(array_column($results, 'count'));

        $this->audit('run', 'notifications', "Ran automated notification rules ({$total} dispatched).");
        $this->success("Rules run: {$total} notification(s) dispatched.", 'admin/notifications?ran=1');
    }
}
