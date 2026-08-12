<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Report generation service (PDF / Excel / CSV).
 *
 * Report types:
 *   - graduates        (graduate roster with employment status)
 *   - employment       (employment statistics by status & sector)
 *   - survey_responses (survey response summary)
 *   - competencies     (competency analysis)
 *   - curriculum       (curriculum feedback analysis)
 *   - analytics        (full analytics KPI report)
 */
final class ReportService
{
    public const TYPES = [
        'graduates'    => ['label' => 'Graduate Roster',    'formats' => ['pdf', 'excel', 'csv']],
        'employment'   => ['label' => 'Employment Statistics', 'formats' => ['pdf', 'excel', 'csv']],
        'analytics'    => ['label' => 'Analytics KPI Report',  'formats' => ['pdf', 'excel']],
        'competencies' => ['label' => 'Competency Analysis',   'formats' => ['pdf', 'excel']],
        'curriculum'   => ['label' => 'Curriculum Feedback',   'formats' => ['pdf', 'excel']],
    ];

    /**
     * Build the report dataset for a type + filters.
     */
    public function build(string $type, array $filters): array
    {
        switch ($type) {
            case 'graduates':
                return $this->graduates($filters);
            case 'employment':
                return $this->employment($filters);
            case 'analytics':
                return $this->analytics($filters);
            case 'competencies':
                return $this->competencies($filters);
            case 'curriculum':
                return $this->curriculum($filters);
            default:
                abort(400, 'Unknown report type.');
        }
    }

    private function graduates(array $filters): array
    {
        $where = ['g.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['program_id'])) {
            $where[] = 'g.program_id = ?';
            $params[] = (int) $filters['program_id'];
        }
        if (!empty($filters['batch_id'])) {
            $where[] = 'g.batch_id = ?';
            $params[] = (int) $filters['batch_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ep.status = ?';
            $params[] = $filters['status'];
        }

        $rows = Database::fetchAll(
            'SELECT g.student_number, g.last_name, g.first_name, g.middle_name, g.suffix,
                    g.email, g.contact_number, g.sex, g.graduation_year,
                    p.code AS program, b.year AS batch,
                    ep.status AS employment_status, ep.job_title, ep.employer,
                    es.name AS sector
             FROM graduates g
             JOIN programs p ON p.id = g.program_id
             JOIN batches b ON b.id = g.batch_id
             LEFT JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY g.student_number',
            $params
        );

        return [
            'title'  => 'Graduate Roster',
            'subtitle' => 'ISU-Cauayan IAT graduates and their employment status',
            'headers' => ['Student #', 'Name', 'Email', 'Contact', 'Sex', 'Program', 'Batch', 'Grad Year', 'Status', 'Job Title', 'Employer', 'Sector'],
            'columns' => function (array $r): array {
                return [
                    $r['student_number'],
                    trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '')),
                    $r['email'] ?? '',
                    $r['contact_number'] ?? '',
                    $r['sex'] ?? '',
                    $r['program'],
                    $r['batch'],
                    $r['graduation_year'],
                    ucwords(str_replace('_', ' ', (string) ($r['employment_status'] ?? 'no_profile'))),
                    $r['job_title'] ?? '',
                    $r['employer'] ?? '',
                    $r['sector'] ?? '',
                ];
            },
            'rows' => $rows,
        ];
    }

    private function employment(array $filters): array
    {
        $analytics = new AnalyticsService();
        $status = $analytics->statusDistribution($filters);
        $sectors = $analytics->sectorDistribution($filters);

        $labels = [
            'employed'       => 'Employed',
            'self_employed'  => 'Self-Employed',
            'unemployed'     => 'Unemployed',
            'further_studies' => 'Further Studies',
        ];

        $headers = ['Status', 'Graduates', 'Percent'];
        $rows = [];
        foreach ($status as $key => $row) {
            $rows[] = [
                'status' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                'status_value' => $key,
                'count' => $row['count'],
                'percent' => $row['percentage'],
            ];
        }

        $sectorHeaders = ['Employment Sector', 'Graduates', 'Percent'];
        $sectorRows = array_map(static function ($name, array $s) {
            return [
                'sector' => $name,
                'count' => $s['count'],
                'percent' => $s['percentage'],
            ];
        }, array_keys($sectors), array_values($sectors));

        $kpis = $analytics->kpis($filters);

        return [
            'title'   => 'Employment Statistics',
            'subtitle' => 'Employment status distribution and sector breakdown',
            'kpis'    => $kpis,
            'status'  => ['headers' => $headers, 'rows' => $rows],
            'sectors' => ['headers' => $sectorHeaders, 'rows' => $sectorRows],
        ];
    }

    private function analytics(array $filters): array
    {
        $analytics = new AnalyticsService();
        $kpis = $analytics->kpis($filters);
        $byYear = $analytics->employmentRateByYear();
        $status = $analytics->statusDistribution($filters);
        $sectors = $analytics->sectorDistribution($filters);
        $relevance = $analytics->jobRelevanceAnalysis($filters);
        $competency = $analytics->competencyAnalysis($filters);
        $curriculum = $analytics->curriculumAnalysis($filters);

        return [
            'title'   => 'Analytics KPI Report',
            'subtitle' => 'Tracer study key performance indicators',
            'kpis'    => $kpis,
            'byYear'  => $byYear,
            'status'  => $status,
            'sectors' => $sectors,
            'relevance' => $relevance,
            'competency' => $competency,
            'curriculum' => $curriculum,
        ];
    }

    private function competencies(array $filters): array
    {
        $analytics = new AnalyticsService();
        $analysis = $analytics->competencyAnalysis($filters);
        $ranking = $analysis['ranking'] ?? [];

        $headers = ['Competency', 'Mean Rating', 'Interpretation'];
        $rows = [];
        foreach ($ranking as $name => $rating) {
            $rows[] = [
                'name' => $name,
                'rating' => $rating,
                'interpretation' => $analytics->interpret((float) $rating, 'competency'),
            ];
        }

        return [
            'title'   => 'Competency Analysis',
            'subtitle' => 'Graduate competency self-assessments',
            'average' => $analysis['average'] ?? null,
            'headers' => $headers,
            'rows'    => $rows,
        ];
    }

    private function curriculum(array $filters): array
    {
        $analytics = new AnalyticsService();
        $analysis = $analytics->curriculumAnalysis($filters);
        $items = $analysis['items'] ?? [];

        $headers = ['Curriculum Area', 'Responses', 'Mean Rating', 'Interpretation'];
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'question' => $item['question'],
                'responses' => $item['responses'],
                'mean' => $item['mean'],
                'interpretation' => $item['interpretation'],
            ];
        }

        return [
            'title'   => 'Curriculum Feedback Report',
            'subtitle' => 'Curriculum relevance and feedback from graduates',
            'overall' => $analysis['overall'] ?? null,
            'headers' => $headers,
            'rows'    => $rows,
        ];
    }

    /**
     * Render a report to its target format and save to storage/exports.
     *
     * @return array{path:string, size:int}
     */
    public function generate(string $type, array $filters, string $format): array
    {
        $dataset = $this->build($type, $filters);
        $format = strtolower($format);

        $exportsDir = (string) config('app.paths.storage_exports');
        if (!is_dir($exportsDir)) {
            @mkdir($exportsDir, 0775, true);
        }

        $slug = $type;
        $filename = 'report-' . $slug . '-' . date('Ymd-His') . '.' . $this->extension($format);
        $fullPath = $exportsDir . '/' . $filename;

        switch ($format) {
            case 'pdf':
                $this->renderPdf($dataset, $fullPath);
                break;
            case 'excel':
                $this->renderXlsx($dataset, $fullPath);
                break;
            case 'csv':
                $this->renderCsv($dataset, $fullPath);
                break;
            default:
                abort(400, 'Unsupported report format.');
        }

        return ['path' => $filename, 'size' => (int) filesize($fullPath)];
    }

    private function extension(string $format): string
    {
        return $format === 'excel' ? 'xlsx' : $format;
    }

    private function renderPdf(array $dataset, string $fullPath): void
    {
        // dompdf is memory-hungry on wide tables; allow extra headroom.
        $limit = ini_get('memory_limit');
        if ($limit !== false && $limit !== '-1') {
            $current = (int) $limit;
            $newLimit = max($current, 512);
            @ini_set('memory_limit', $newLimit . 'M');
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);

        $html = $this->renderHtml($dataset);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        file_put_contents($fullPath, $dompdf->output());
    }

    private function renderXlsx(array $dataset, string $fullPath): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($dataset['title'], 0, 31));

        if (isset($dataset['headers'], $dataset['columns'], $dataset['rows'])) {
            // Tabular report.
            $header = $dataset['headers'];
            $rows = array_map($dataset['columns'], $dataset['rows']);
            $sheet->fromArray(array_merge([$header], $rows), null, 'A1');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        } else {
            // Multi-section report (analytics / employment).
            $r = 1;
            if (!empty($dataset['kpis'])) {
                $sheet->fromArray([['Indicator', 'Value']], null, 'A' . $r);
                $r++;
                foreach ($dataset['kpis'] as $label => $value) {
                    $sheet->fromArray([[$label, $value]], null, 'A' . $r);
                    $r++;
                }
                $r++;
            }
            foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors'] as $key => $sectionTitle) {
                if (!empty($dataset[$key]['rows'])) {
                    $sheet->fromArray([[$sectionTitle]], null, 'A' . $r);
                    $sheet->getStyle('A' . $r)->getFont()->setBold(true);
                    $r++;
                    $sheet->fromArray([$dataset[$key]['headers']], null, 'A' . $r);
                    $sheet->getStyle('A' . $r . ':' . $sheet->getHighestColumn() . $r)->getFont()->setBold(true);
                    $r++;
                    foreach ($dataset[$key]['rows'] as $row) {
                        $sheet->fromArray([array_values($row)], null, 'A' . $r);
                        $r++;
                    }
                    $r++;
                }
            }
            if (isset($dataset['headers'], $dataset['rows'])) {
                $sheet->fromArray([$dataset['headers']], null, 'A' . $r);
                $sheet->getStyle('A' . $r . ':' . $sheet->getHighestColumn() . $r)->getFont()->setBold(true);
                $r++;
                foreach ($dataset['rows'] as $row) {
                    $sheet->fromArray([array_values($row)], null, 'A' . $r);
                    $r++;
                }
            }
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
    }

    private function renderCsv(array $dataset, string $fullPath): void
    {
        $out = fopen($fullPath, 'w');
        if ($out === false) {
            abort(500, 'Could not open report file for writing.');
        }

        if (isset($dataset['headers'], $dataset['columns'], $dataset['rows'])) {
            fputcsv($out, $dataset['headers'], ',', '"', '');
            foreach ($dataset['rows'] as $row) {
                fputcsv($out, $dataset['columns']($row), ',', '"', '');
            }
        } else {
            foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors'] as $key => $sectionTitle) {
                if (empty($dataset[$key]['rows'])) {
                    continue;
                }
                fputcsv($out, [$sectionTitle], ',', '"', '');
                fputcsv($out, $dataset[$key]['headers'], ',', '"', '');
                foreach ($dataset[$key]['rows'] as $row) {
                    fputcsv($out, array_values($row), ',', '"', '');
                }
                fputcsv($out, [], ',', '"', '');
            }
        }

        fclose($out);
    }

    /**
     * Minimal HTML template for PDF export.
     */
    private function renderHtml(array $dataset): string
    {
        $appName = (string) setting('university_name', 'Isabela State University');
        $campus = (string) setting('campus_name', 'Cauayan Campus');
        $institute = (string) setting('institute_name', 'Institute of Agricultural Technology (IAT)');

        $title = htmlspecialchars($dataset['title'], ENT_QUOTES);
        $subtitle = htmlspecialchars($dataset['subtitle'] ?? '', ENT_QUOTES);
        $generated = date('F j, Y g:i A');

        $body = '';

        // KPI cards for analytics report.
        if (!empty($dataset['kpis'])) {
            $body .= '<h3>Key Performance Indicators</h3><table>';
            foreach ($dataset['kpis'] as $label => $value) {
                $body .= '<tr><td>' . htmlspecialchars((string) $label) . '</td><td><strong>' . htmlspecialchars((string) $value) . '</strong></td></tr>';
            }
            $body .= '</table>';
        }

        // Tabular sections.
        foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors'] as $key => $sectionTitle) {
            if (!empty($dataset[$key]['rows'])) {
                $body .= '<h3>' . $sectionTitle . '</h3><table><thead><tr>';
                foreach ($dataset[$key]['headers'] as $h) {
                    $body .= '<th>' . htmlspecialchars((string) $h) . '</th>';
                }
                $body .= '</tr></thead><tbody>';
                foreach ($dataset[$key]['rows'] as $row) {
                    $body .= '<tr>';
                    foreach (array_values($row) as $v) {
                        $body .= '<td>' . htmlspecialchars((string) $v) . '</td>';
                    }
                    $body .= '</tr>';
                }
                $body .= '</tbody></table>';
            }
        }

        // Generic tabular dataset.
        if (isset($dataset['headers'], $dataset['columns'], $dataset['rows'])) {
            $body .= '<table><thead><tr>';
            foreach ($dataset['headers'] as $h) {
                $body .= '<th>' . htmlspecialchars((string) $h) . '</th>';
            }
            $body .= '</tr></thead><tbody>';
            foreach ($dataset['rows'] as $row) {
                $body .= '<tr>';
                foreach ($dataset['columns']($row) as $v) {
                    $body .= '<td>' . htmlspecialchars((string) $v) . '</td>';
                }
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        }

        // Competency / curriculum single-table datasets.
        if (isset($dataset['headers'], $dataset['rows']) && !isset($dataset['columns'])) {
            $body .= '<table><thead><tr>';
            foreach ($dataset['headers'] as $h) {
                $body .= '<th>' . htmlspecialchars((string) $h) . '</th>';
            }
            $body .= '</tr></thead><tbody>';
            foreach ($dataset['rows'] as $row) {
                $body .= '<tr>';
                foreach (array_values($row) as $v) {
                    $body .= '<td>' . htmlspecialchars((string) $v) . '</td>';
                }
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #1f2937; }
            h1 { font-size: 15pt; margin: 0 0 2px 0; }
            h2 { font-size: 11pt; font-weight: normal; color: #6b7280; margin: 0 0 12px 0; }
            h3 { font-size: 10pt; margin: 16px 0 6px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
            th, td { border: 1px solid #d1d5db; padding: 3px 6px; text-align: left; }
            th { background: #f3f4f6; }
            .meta { font-size: 8pt; color: #6b7280; margin-bottom: 10px; }
        </style></head><body>
            <h1>' . htmlspecialchars($appName) . '</h1>
            <h2>' . htmlspecialchars($campus . ' &middot; ' . $institute) . '</h2>
            <h1>' . $title . '</h1>
            <h2>' . $subtitle . '</h2>
            <div class="meta">Generated on ' . $generated . '</div>
            ' . $body . '
        </body></html>';
    }
}
