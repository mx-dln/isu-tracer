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
                    g.address, g.job_position,
                    g.program_id, g.major, g.batch_id, g.graduation_year, g.is_validated, g.phone_verified_at, g.validated_at, g.contact_number,
                    p.name AS program_name, b.year AS batch_year
             FROM users u
             LEFT JOIN graduates g ON g.id = u.graduate_id
             LEFT JOIN programs p ON p.id = g.program_id
             LEFT JOIN batches b ON b.id = g.batch_id
             WHERE u.id = ?',
            [$userId]
        ) ?: [];

        $graduateId = (int) ($user['graduate_id'] ?? 0);
        $gradYear = (int) ($user['graduation_year'] ?? 0);

        $employment = $graduateId > 0 ? Database::fetch(
            'SELECT ep.*, es.name AS sector_name
             FROM employment_profiles ep
             LEFT JOIN employment_sectors es ON es.id = ep.sector_id
             WHERE ep.graduate_id = ? AND ep.deleted_at IS NULL AND ep.is_current = 1',
            [$graduateId]
        ) : null;

        $activeSurvey = $gradYear > 0 ? Database::fetch(
            'SELECT s.* FROM surveys s
             WHERE s.status = "active" AND s.deleted_at IS NULL
               AND s.tracer_year = ?
             ORDER BY s.created_at DESC LIMIT 1',
            [$gradYear]
        ) : null;

        $submitted = false;
        if ($activeSurvey && $graduateId > 0) {
            $submitted = (bool) Database::fetch(
                'SELECT id FROM survey_responses WHERE survey_id = ? AND graduate_id = ?',
                [$activeSurvey['id'], $graduateId]
            );
        }

        $competencyCount = $graduateId > 0 ? (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competency_responses WHERE graduate_id = ?',
            [$graduateId]
        )['c'] : 0;
        $totalCompetencies = (int) Database::fetch(
            'SELECT COUNT(*) AS c FROM competencies WHERE deleted_at IS NULL AND is_active = 1'
        )['c'];

        $curriculumSubmitted = $graduateId > 0 && (bool) Database::fetch(
            'SELECT id FROM curriculum_feedback WHERE graduate_id = ? LIMIT 1',
            [$graduateId]
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
