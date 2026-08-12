<?php

declare(strict_types=1);

/**
 * API routes (JSON endpoints used by Fetch API).
 */

use App\Middleware\AuthMiddleware;

$router->get('/api/notifications', 'Api\NotificationController@index')->middleware(AuthMiddleware::class);
$router->post('/api/notifications/{id}/read', 'Api\NotificationController@markRead')->middleware(AuthMiddleware::class);
$router->post('/api/notifications/read-all', 'Api\NotificationController@markAllRead')->middleware(AuthMiddleware::class);
