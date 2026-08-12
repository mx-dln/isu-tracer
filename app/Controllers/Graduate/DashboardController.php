<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\AnalyticsService;

/**
 * Graduate dashboard: personal overview and survey status.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $user = Database::fetch(
            'SELECT u.*, g.id AS graduate_id, g.student_number, g.first_name, g.middle_name, g.last_name,
                    g.program_id, g.batch_id, g.graduation_year, g.is_validated,
                    p.name AS program_name, b.year AS batch_year
             FROM users u
             JOIN graduates g ON g.id = u.graduate_id
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             WHERE u.id = ?',
            [$userId]
        );

        $employment = Database::fetch(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.graduate_id = ? AND ep.deleted_at IS NULL AND ep.is_current = 1',
            [$user['graduate_id']]
        );

        $activeSurvey = Database::fetch(
            'SELECT s.* FROM surveys s
             WHERE s.status = "active" AND s.deleted_at IS NULL
               AND s.tracer_year = ?
             ORDER BY s.created_at DESC LIMIT 1',
            [(int) $user['graduation_year']]
        );

        $submitted = false;
        if ($activeSurvey) {
            $submitted = (bool) Database::fetch(
                'SELECT id FROM survey_responses WHERE survey_id = ? AND graduate_id = ?',
                [$activeSurvey['id'], $user['graduate_id']]
            );
        }

        $competencyCount = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competency_responses WHERE graduate_id = ?',
            [$user['graduate_id']]
        )['c'];
        $totalCompetencies = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competencies WHERE deleted_at IS NULL AND is_active = 1'
        )['c'];

        $curriculumSubmitted = (bool) Database::fetch(
            'SELECT id FROM curriculum_feedback WHERE graduate_id = ? LIMIT 1',
            [$user['graduate_id']]
        );

        $unreadCount = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        )['c'];

        $this->view('graduate/dashboard', [
            'title'       => 'My Dashboard',
            'subtitle'    => 'Your IAT tracer study overview',
            'user'        => $user,
            'employment'  => $employment,
            'activeSurvey' => $activeSurvey,
            'surveySubmitted' => $submitted,
            'competencyCount' => $competencyCount,
            'totalCompetencies' => $totalCompetencies,
            'curriculumSubmitted' => $curriculumSubmitted,
            'unreadCount' => $unreadCount,
            'analytics'   => new AnalyticsService(),
        ]);
    }
}
