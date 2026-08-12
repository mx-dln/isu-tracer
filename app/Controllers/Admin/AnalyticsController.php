<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\AnalyticsService;

/**
 * Detailed analytics dashboard (KPIs + Chart.js visualizations).
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): void
    {
        $analytics = new AnalyticsService();
        $filters = [
            'batch_id'        => $request->query('batch_id') ? (int) $request->query('batch_id') : null,
            'program_id'      => $request->query('program_id') ? (int) $request->query('program_id') : null,
            'graduation_year' => $request->query('graduation_year') ? (int) $request->query('graduation_year') : null,
            'status'          => $request->query('status') ? (string) $request->query('status') : null,
            'sector_id'       => $request->query('sector_id') ? (int) $request->query('sector_id') : null,
        ];
        $filters = array_filter($filters, static fn ($v) => $v !== null && $v !== '');

        $years = range((int) date('Y') - 4, (int) date('Y'));

        $this->view('admin/analytics/index', [
            'title'     => 'Analytics',
            'subtitle'  => 'Employment analytics, competency and curriculum insights',
            'kpis'      => $analytics->kpis($filters),
            'employmentByYear' => $analytics->employmentRateByYear(),
            'statusDistribution' => $analytics->statusDistribution($filters),
            'sectorDistribution' => $analytics->sectorDistribution($filters),
            'timeToEmployment' => $analytics->timeToEmploymentDistribution($filters),
            'relevance'  => $analytics->jobRelevanceAnalysis($filters),
            'competency' => $analytics->competencyAnalysis($filters),
            'curriculum' => $analytics->curriculumAnalysis($filters),
            'filters'    => $analytics->filterOptions(),
            'selected'   => $filters,
            'years'      => $years,
        ]);
    }
}
