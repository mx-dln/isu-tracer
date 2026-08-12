<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response helpers.
 */
final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $extra = []): never
    {
        self::json(array_merge(['success' => true], $extra));
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    public static function download(string $filePath, string $downloadName = ''): void
    {
        if (!is_file($filePath)) {
            abort(404, 'File not found.');
        }
        $downloadName = $downloadName ?: basename($filePath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
