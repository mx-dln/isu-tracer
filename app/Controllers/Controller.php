<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Session;
use App\Core\View;

/**
 * Base controller with common view/session helpers.
 */
abstract class Controller
{
    /**
     * Render a view with the default layout.
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    /**
     * Render a JSON response.
     */
    protected function json(array $data, int $status = 200): never
    {
        \App\Core\Response::json($data, $status);
    }

    /**
     * Redirect to a path.
     */
    protected function redirect(string $to): never
    {
        redirect($to);
    }

    /**
     * Flash a success message and redirect back.
     */
    protected function success(string $message, ?string $to = null): never
    {
        flash('success', $message);
        redirect($to ?? ($_SERVER['HTTP_REFERER'] ?? url('')));
    }

    /**
     * Flash an error message and redirect back.
     */
    protected function error(string $message, ?string $to = null): never
    {
        flash('error', $message);
        redirect($to ?? ($_SERVER['HTTP_REFERER'] ?? url('')));
    }

    /**
     * Record an audit log entry.
     */
    protected function audit(string $action, string $module, string $description): void
    {
        Audit::log($action, $module, $description);
    }
}
