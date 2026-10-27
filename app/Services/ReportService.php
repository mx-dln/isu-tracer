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
                    g.email, g.contact_number, g.sex, g.graduation_year, g.major,
                    p.code AS program, b.year AS batch,
                    ep.status AS employment_status, ep.job_title, ep.employer,
                    ep.proof_uploaded_at,
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
            // Printable PDF columns (essential, readable, no overflow):
            'pdf_headers' => ['Student #', 'Name', 'Program', 'Major', 'Batch', 'Status', 'Job Title', 'Employer', 'Sector'],
            'pdf_columns' => function (array $r): array {
                return [
                    $r['student_number'],
                    trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ? mb_substr($r['middle_name'], 0, 1) . '.' : '')),
                    $r['program'],
                    $r['major'] ?? '—',
                    $r['batch'],
                    ucwords(str_replace('_', ' ', (string) ($r['employment_status'] ?? 'no_profile'))),
                    $r['job_title'] ?? '—',
                    $r['employer'] ?? '—',
                    $r['sector'] ?? '—',
                ];
            },
            // Full headers & columns for Excel / CSV raw export:
            'headers' => ['Student #', 'Name', 'Email', 'Contact', 'Sex', 'Program', 'Major', 'Batch', 'Grad Year', 'Status', 'Job Title', 'Employer', 'Sector', 'Proof Date'],
            'columns' => function (array $r): array {
                return [
                    $r['student_number'],
                    trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '')),
                    $r['email'] ?? '',
                    $r['contact_number'] ?? '',
                    $r['sex'] ?? '',
                    $r['program'],
                    $r['major'] ?? '—',
                    $r['batch'],
                    $r['graduation_year'],
                    ucwords(str_replace('_', ' ', (string) ($r['employment_status'] ?? 'no_profile'))),
                    $r['job_title'] ?? '',
                    $r['employer'] ?? '',
                    $r['sector'] ?? '',
                    !empty($r['proof_uploaded_at']) ? date('Y-m-d', strtotime($r['proof_uploaded_at'])) : '—',
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

        $timeToFirstJob = $analytics->timeToEmploymentDistribution($filters);
        $timeRows = array_map(static function ($name, array $s) {
            return [
                'range' => $name,
                'count' => $s['count'],
                'percent' => $s['percentage'],
            ];
        }, array_keys($timeToFirstJob), array_values($timeToFirstJob));

        $where = ['g.deleted_at IS NULL', 'ep.deleted_at IS NULL', 'ep.is_current = 1'];
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
        if (!empty($filters['sector_id'])) {
            $where[] = 'ep.sector_id = ?';
            $params[] = (int) $filters['sector_id'];
        }

        $detailRows = Database::fetchAll(
            "SELECT g.student_number, g.last_name, g.first_name, p.code AS program, b.year AS batch,
                    ep.status, ep.job_title, ep.employer, COALESCE(es.name, ep.sector_other) AS sector,
                    ep.first_employment_date,
                    TIMESTAMPDIFF(MONTH, STR_TO_DATE(CONCAT(g.graduation_year, '-06-15'), '%Y-%m-%d'), ep.first_employment_date) AS first_job_months,
                    CASE WHEN ep.proof_image_path IS NOT NULL OR ep.proof_image_url IS NOT NULL THEN 'Yes' ELSE 'No' END AS has_proof
             FROM employment_profiles ep
             JOIN graduates g ON g.id = ep.graduate_id
             JOIN programs p ON p.id = g.program_id
             JOIN batches b ON b.id = g.batch_id
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY g.last_name, g.first_name",
            $params
        );

        $kpis = $analytics->kpis($filters);

        return [
            'title'   => 'Employment Statistics',
            'subtitle' => 'Employment status, sector breakdown, and time to first job in months',
            'kpis'    => $kpis,
            'status'  => ['headers' => $headers, 'rows' => $rows],
            'sectors' => ['headers' => $sectorHeaders, 'rows' => $sectorRows],
            'time_to_first_job' => ['headers' => ['Time to First Job', 'Graduates', 'Percent'], 'rows' => $timeRows],
            'details' => [
                'headers' => ['Student #', 'Name', 'Program', 'Batch', 'Status', 'Job Title', 'Employer', 'Sector', 'First Employment Date', 'Time to First Job (Months)', 'Proof Submitted'],
                'rows' => array_map(static function (array $row) use ($labels): array {
                    return [
                        $row['student_number'],
                        trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '')),
                        $row['program'],
                        $row['batch'],
                        $labels[$row['status']] ?? ucwords(str_replace('_', ' ', (string) $row['status'])),
                        $row['job_title'] ?? '',
                        $row['employer'] ?? '',
                        $row['sector'] ?? '',
                        $row['first_employment_date'] ?? '',
                        $row['first_job_months'] !== null ? (int) $row['first_job_months'] : '',
                        $row['has_proof'],
                    ];
                }, $detailRows),
            ],
            'pdf_details' => [
                'headers' => ['Student #', 'Name', 'Program', 'Batch', 'Status', 'Job Title', 'Employer', 'Sector'],
                'rows' => array_map(static function (array $row) use ($labels): array {
                    return [
                        $row['student_number'],
                        trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '')),
                        $row['program'],
                        $row['batch'],
                        $labels[$row['status']] ?? ucwords(str_replace('_', ' ', (string) $row['status'])),
                        $row['job_title'] ?? '—',
                        $row['employer'] ?? '—',
                        $row['sector'] ?? '—',
                    ];
                }, $detailRows),
            ],
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
            foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors', 'time_to_first_job' => 'Time to First Job', 'details' => 'Employment Details'] as $key => $sectionTitle) {
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
            foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors', 'time_to_first_job' => 'Time to First Job', 'details' => 'Employment Details'] as $key => $sectionTitle) {
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
     * Formal HTML template with dual-logo institutional letterhead for PDF export.
     */
    private function renderHtml(array $dataset): string
    {
        $appName = (string) setting('university_name', 'Isabela State University');
        $campus = (string) setting('campus_name', 'Cauayan Campus');
        $institute = (string) setting('institute_name', 'Institute of Agricultural Technology');

        $isuLogoPath = base_path('public/assets/images/logo.png');
        $iatLogoPath = base_path('public/assets/images/iat-logo.png');

        $isuLogoData = is_file($isuLogoPath) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($isuLogoPath)) : '';
        $iatLogoData = is_file($iatLogoPath) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($iatLogoPath)) : '';

        $title = htmlspecialchars($dataset['title'], ENT_QUOTES);
        $subtitle = htmlspecialchars($dataset['subtitle'] ?? '', ENT_QUOTES);
        $generated = date('F j, Y g:i A');

        $body = '';

        // KPI cards for analytics report.
        if (!empty($dataset['kpis'])) {
            $body .= '<h3>Key Performance Indicators</h3><table class="data-table"><thead><tr><th style="width: 70%;">Indicator</th><th style="width: 30%;">Value</th></tr></thead><tbody>';
            foreach ($dataset['kpis'] as $label => $value) {
                $body .= '<tr><td>' . htmlspecialchars((string) $label) . '</td><td><strong>' . htmlspecialchars((string) $value) . '</strong></td></tr>';
            }
            $body .= '</tbody></table>';
        }

        // Tabular sections.
        foreach (['status' => 'Employment Status', 'sectors' => 'Employment Sectors', 'time_to_first_job' => 'Time to First Job', 'details' => 'Employment Details'] as $key => $sectionTitle) {
            $section = ($key === 'details' && isset($dataset['pdf_details'])) ? $dataset['pdf_details'] : ($dataset[$key] ?? null);
            if (!empty($section['rows'])) {
                $body .= '<h3>' . $sectionTitle . '</h3><table class="data-table"><thead><tr>';
                foreach ($section['headers'] as $h) {
                    $body .= '<th>' . htmlspecialchars((string) $h) . '</th>';
                }
                $body .= '</tr></thead><tbody>';
                foreach ($section['rows'] as $row) {
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
            $headers = $dataset['pdf_headers'] ?? $dataset['headers'];
            $colFn = $dataset['pdf_columns'] ?? $dataset['columns'];
            $body .= '<table class="data-table"><thead><tr>';
            foreach ($headers as $h) {
                $body .= '<th>' . htmlspecialchars((string) $h) . '</th>';
            }
            $body .= '</tr></thead><tbody>';
            foreach ($dataset['rows'] as $row) {
                $body .= '<tr>';
                foreach ($colFn($row) as $v) {
                    $body .= '<td>' . htmlspecialchars((string) $v) . '</td>';
                }
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        }

        // Competency / curriculum single-table datasets.
        if (isset($dataset['headers'], $dataset['rows']) && !isset($dataset['columns'])) {
            $body .= '<table class="data-table"><thead><tr>';
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
            @page { margin: 10mm 12mm 12mm 12mm; }
            body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color: #1f2937; line-height: 1.3; }
            .header-table { width: 70%; margin: 0 auto; border-collapse: collapse; border: none; margin-bottom: 0; }
            .header-table td { border: none; padding: 0; vertical-align: middle; }
            .letterhead-rule { border-bottom: 3px double #15803d; margin: 8px 0 12px 0; }
            .report-title-block { text-align: center; margin-bottom: 12px; }
            .report-title-block h1 { font-size: 13pt; font-weight: bold; color: #111827; margin: 0 0 3px 0; text-transform: uppercase; letter-spacing: 0.5px; }
            .report-title-block h2 { font-size: 8.5pt; font-weight: normal; color: #4b5563; margin: 0 0 2px 0; }
            .report-title-block .meta { font-size: 7.5pt; color: #6b7280; }
            h3 { font-size: 9.5pt; color: #166534; margin: 14px 0 6px 0; border-bottom: 1.5px solid #15803d; padding-bottom: 3px; font-weight: bold; }
            table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: auto; }
            table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; font-size: 7.5pt; word-wrap: break-word; }
            table.data-table th { background-color: #15803d; color: #ffffff; font-weight: bold; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; }
            table.data-table tr:nth-child(even) td { background-color: #f8fafc; }
        </style></head><body>
            <table class="header-table" align="center">
                <tr>
                    <td style="width: 95px; text-align: center; vertical-align: middle;">
                        ' . ($isuLogoData !== '' ? '<img src="' . $isuLogoData . '" style="width: 88px; height: 88px;">' : '') . '
                    </td>
                    <td style="text-align: center; padding: 0 15px; vertical-align: middle;">
                        <div style="font-size: 8.5pt; text-transform: uppercase; color: #4b5563; letter-spacing: 0.5px;">Republic of the Philippines</div>
                        <div style="font-size: 13.5pt; font-weight: bold; color: #15803d; letter-spacing: 0.5px; margin: 2px 0;">' . htmlspecialchars($appName) . '</div>
                        <div style="font-size: 9.5pt; font-weight: bold; color: #1f2937;">' . htmlspecialchars($campus) . '</div>
                        <div style="font-size: 9pt; font-weight: bold; color: #166534; text-transform: uppercase; margin-top: 2px;">' . htmlspecialchars($institute) . '</div>
                        <div style="font-size: 7.5pt; color: #6b7280; font-style: italic; margin-top: 2px;">Cauayan City, Isabela</div>
                    </td>
                    <td style="width: 95px; text-align: center; vertical-align: middle;">
                        ' . ($iatLogoData !== '' ? '<img src="' . $iatLogoData . '" style="width: 88px; height: 88px;">' : '') . '
                    </td>
                </tr>
            </table>
            <div class="letterhead-rule"></div>

            <div class="report-title-block">
                <h1>' . $title . '</h1>
                ' . ($subtitle !== '' ? '<h2>' . $subtitle . '</h2>' : '') . '
                <div class="meta">Date Generated: ' . $generated . '</div>
            </div>

            ' . $body . '
        </body></html>';
    }
}
