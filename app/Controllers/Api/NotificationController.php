<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;

/**
 * JSON endpoints for the in-app notification center.
 */
class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $unread = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        )['c'];

        $rows = Database::fetchAll(
            'SELECT id, type, title, message, is_read, created_at, link
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 12',
            [$userId]
        );

        $items = array_map(function ($row) {
            $row['created_at'] = date('M j, g:i A', strtotime($row['created_at']));
            return $row;
        }, $rows);

        $this->json([
            'success'     => true,
            'unread_count' => $unread,
            'notifications' => $items,
        ]);
    }

    public function markRead(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $userId = Auth::id();
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?',
            [(int) $params['id'], $userId]
        );
        $this->json(['success' => true]);
    }

    public function markAllRead(Request $request): void
    {
        Csrf::validateOrAbort();
        $userId = Auth::id();
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
        $this->json(['success' => true]);
    }
}
