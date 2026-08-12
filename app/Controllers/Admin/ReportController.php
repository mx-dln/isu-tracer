<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Services\AnalyticsService;
use App\Services\ReportService;

/**
 * Report generation and management (admin).
 */
class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $analytics = new AnalyticsService();

        $this->view('admin/reports/index', [
            'title'    => 'Reports',
            'subtitle' => 'Generate and download PDF, Excel and CSV reports',
            'types'    => ReportService::TYPES,
            'reports'  => Database::fetchAll(
                'SELECT r.*, u.name AS generated_by_name
                 FROM reports r
                 LEFT JOIN users u ON u.id = r.generated_by
                 ORDER BY r.generated_at DESC
                 LIMIT 50'
            ),
            'filters'  => $analytics->filterOptions(),
        ]);
    }

    public function generate(Request $request): never
    {
        Csrf::validateOrAbort();

        $type = (string) $request->input('type');
        if (!isset(ReportService::TYPES[$type])) {
            $this->error('Unknown report type.', 'admin/reports');
        }

        $format = strtolower((string) $request->input('format', 'pdf'));
        if (!in_array($format, ReportService::TYPES[$type]['formats'], true)) {
            $this->error('That format is not supported for this report.', 'admin/reports');
        }

        $filters = [
            'program_id' => $request->input('program_id') !== '' && $request->input('program_id') !== null ? (int) $request->input('program_id') : null,
            'batch_id'   => $request->input('batch_id') !== '' && $request->input('batch_id') !== null ? (int) $request->input('batch_id') : null,
            'status'     => trim((string) $request->input('status')),
        ];

        try {
            $service = new ReportService();
            $meta = $service->generate($type, $filters, $format);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Report generation failed: ' . $e->getMessage());
            $this->error('Report generation failed. Please try again.', 'admin/reports');
        }

        $dataset = $service->build($type, $filters);

        Database::run(
            'INSERT INTO reports (type, title, format, filters, file_path, generated_by, generated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $type,
                $dataset['title'] . ' Report',
                $format === 'excel' ? 'excel' : $format,
                json_encode(array_filter($filters, fn ($v) => $v !== null && $v !== '')),
                $meta['path'],
                auth()?->id,
            ]
        );
        $reportId = (int) Database::lastInsertId();

        $this->audit('generate', 'reports', "Generated {$type} report ({$format}) #{$reportId}.");
        $this->success('Report generated successfully.', 'admin/reports/' . $reportId . '/download');
    }

    public function download(Request $request, array $params): never
    {
        $report = Database::fetch('SELECT * FROM reports WHERE id = ?', [(int) $params['id']]);
        if (!$report) {
            abort(404, 'Report not found.');
        }

        $file = (string) config('app.paths.storage_exports') . '/' . $report['file_path'];
        if (!is_file($file)) {
            abort(404, 'Report file no longer exists.');
        }

        $mime = [
            'pdf'   => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'   => 'text/csv; charset=UTF-8',
        ][$report['format']] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($report['file_path']) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function show(Request $request, array $params): void
    {
        $report = Database::fetch(
            'SELECT r.*, u.name AS generated_by_name
             FROM reports r
             LEFT JOIN users u ON u.id = r.generated_by
             WHERE r.id = ?',
            [(int) $params['id']]
        );
        if (!$report) {
            abort(404, 'Report not found.');
        }

        $this->view('admin/reports/show', [
            'title'    => 'Report',
            'subtitle' => 'Generated report',
            'report'   => $report,
        ]);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $id = (int) $params['id'];
        $report = Database::fetch('SELECT * FROM reports WHERE id = ?', [$id]);
        if (!$report) {
            abort(404, 'Report not found.');
        }

        $file = (string) config('app.paths.storage_exports') . '/' . $report['file_path'];
        if (is_file($file)) {
            @unlink($file);
        }
        Database::run('DELETE FROM reports WHERE id = ?', [$id]);
        $this->audit('delete', 'reports', "Deleted report #{$id}.");
        $this->success('Report deleted.', 'admin/reports');
    }
}
