<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;

/**
 * Notification inbox (graduate).
 */
class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $page = max(1, (int) $request->query('page', 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $total = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ?',
            [$userId]
        )['c'];

        $notifications = Database::fetchAll(
            'SELECT * FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            [$userId]
        );

        $unread = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        )['c'];

        $this->view('graduate/notifications/index', [
            'title'         => 'Notifications',
            'subtitle'      => 'Your notification inbox',
            'notifications' => $notifications,
            'unread'        => $unread,
            'page'          => $page,
            'pages'         => (int) ceil($total / $limit),
            'total'         => $total,
        ]);
    }

    public function markRead(Request $request, array $params): never
    {
        Csrf::validateOrAbort();
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?',
            [(int) $params['id'], Auth::id()]
        );
        redirect('graduate/notifications');
    }

    public function markAllRead(Request $request): never
    {
        Csrf::validateOrAbort();
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0',
            [Auth::id()]
        );
        redirect('graduate/notifications');
    }
}
