<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable HTTP request representation.
 */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support _method spoofing for PUT/PATCH/DELETE forms.
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $spoofed;
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = $this->detectBasePath();

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '') {
            $path = '/';
        }
        $this->path = $path;

        $this->query = $_GET ?? [];
        $this->body = $_POST ?? [];
        $this->files = $_FILES ?? [];
    }

    /**
     * Determine the base path when served from a subdirectory (e.g. /tracer/public).
     */
    private function detectBasePath(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return $scriptDir === '.' ? '' : rtrim($scriptDir, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    /**
     * Get a query string value.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a body (POST) value.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Get all body input (excluding tokens and spoofed method).
     */
    public function all(): array
    {
        $exclude = ['_token', '_method'];
        $data = $this->body;
        foreach ($exclude as $k) {
            unset($data[$k]);
        }
        return $data;
    }

    /**
     * Get raw JSON body if the request is JSON.
     */
    public function json(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    public function ip(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        foreach ($candidates as $header) {
            $value = $_SERVER[$header] ?? null;
            if ($value) {
                $first = explode(',', (string) $value)[0];
                $first = trim($first);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function url(): string
    {
        return (string) ($_SERVER['HTTP_HOST'] ?? '') . $this->path;
    }

    public function ajax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || (($_SERVER['HTTP_ACCEPT'] ?? '') !== '' && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }
}
