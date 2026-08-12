<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\AnalyticsService;

/**
 * Administrator dashboard.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $analytics = new AnalyticsService();
        $filters = [
            'batch_id' => $request->query('batch_id') ? (int) $request->query('batch_id') : null,
            'program_id' => $request->query('program_id') ? (int) $request->query('program_id') : null,
        ];
        $filters = array_filter($filters);

        $this->view('admin/dashboard', [
            'title'     => 'Dashboard',
            'subtitle'  => 'Overview of the IAT graduate tracer study',
            'kpis'      => $analytics->kpis($filters),
            'employmentByYear' => $analytics->employmentRateByYear(),
            'statusDistribution' => $analytics->statusDistribution($filters),
            'sectorDistribution' => $analytics->sectorDistribution($filters),
            'relevance'  => $analytics->jobRelevanceAnalysis($filters),
            'competency' => $analytics->competencyAnalysis($filters),
            'timeToEmployment' => $analytics->timeToEmploymentDistribution($filters),
            'filters'    => $analytics->filterOptions(),
            'selected'   => $filters,
        ]);
    }
}
