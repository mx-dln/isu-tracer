<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal view renderer with layout support.
 */
final class View
{
    /**
     * Render a view inside a layout.
     *
     * @param string $view   View file relative to views/ (no extension).
     * @param array  $data   Variables made available to the view.
     * @param string $layout Layout file relative to views/layouts (no extension).
     */
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewsPath = (string) config('app.paths.views');

        $viewFile = $viewsPath . '/' . $view . '.php';
        if (!is_file($viewFile)) {
            Logger::error('View not found: ' . $viewFile);
            throw new RuntimeException("View [{$view}] not found.");
        }

        $shared = [];
        $content = self::capture($viewFile, $data, $shared);

        $layoutFile = $viewsPath . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            Logger::error('Layout not found: ' . $layoutFile);
            throw new RuntimeException("Layout [{$layout}] not found.");
        }

        echo self::capture($layoutFile, array_merge($data, ['content' => $content], $shared));
    }

    /**
     * Capture the output of a template file with extracted variables.
     *
     * Any variables assigned inside the template (e.g. `$scripts`) are
     * collected into &$shared so they remain available to the layout.
     */
    public static function capture(string $__file, array $__data, ?array &$__shared = null): string
    {
        ob_start();
        extract($__data, EXTR_SKIP);
        include $__file;
        $__out = (string) ob_get_clean();

        if ($__shared !== null) {
            foreach (get_defined_vars() as $__k => $__v) {
                if (array_key_exists($__k, $__data) || str_starts_with((string) $__k, '__')) {
                    continue;
                }
                $__shared[$__k] = $__v;
            }
        }

        return $__out;
    }

    /**
     * Render a partial (no layout) and return the HTML string.
     */
    public static function partial(string $view, array $data = []): string
    {
        $viewsPath = (string) config('app.paths.views');
        $file = $viewsPath . '/' . $view . '.php';
        if (!is_file($file)) {
            return '';
        }
        return self::capture($file, $data);
    }
}
