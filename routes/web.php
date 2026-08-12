<?php

declare(strict_types=1);

/**
 * Web routes.
 *
 * Available helpers: $router->get/post/put/delete/any($uri, $action)
 *   $action: 'Controller@method', [Controller::class, 'method'], or Closure.
 *   Middleware: ->middleware('Auth', 'Admin')  (names under App\Middleware).
 *
 * NOTE: Routes for later phases (surveys, employment, forecasting, etc.)
 * are registered as their modules are implemented.
 */

use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GraduateMiddleware;
use App\Middleware\GuestMiddleware;

// ------------------------------------------------------------------
// Public / Auth
// ------------------------------------------------------------------
$router->get('/', 'HomeController@index');

$router->get('/login', 'AuthController@showLoginForm')->middleware(GuestMiddleware::class);
$router->post('/login', 'AuthController@login')->middleware(GuestMiddleware::class);
$router->get('/register', 'AuthController@showRegisterForm')->middleware(GuestMiddleware::class);
$router->post('/register', 'AuthController@register')->middleware(GuestMiddleware::class);
$router->post('/logout', 'AuthController@logout')->middleware(AuthMiddleware::class);

$router->get('/auth/forgot', 'AuthController@showForgotForm')->middleware(GuestMiddleware::class);
$router->post('/auth/forgot', 'AuthController@sendResetLink')->middleware(GuestMiddleware::class);
$router->get('/auth/password-reset/{token}', 'AuthController@showResetForm')->middleware(GuestMiddleware::class);
$router->post('/auth/password-reset/{token}', 'AuthController@resetPassword')->middleware(GuestMiddleware::class);

// ------------------------------------------------------------------
// Admin dashboard
// ------------------------------------------------------------------
$router->get('/admin/dashboard', 'Admin\DashboardController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: analytics (Phase 7)
// ------------------------------------------------------------------
$router->get('/admin/analytics', 'Admin\AnalyticsController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: forecasting (Phase 8)
// ------------------------------------------------------------------
$router->get('/admin/forecasting', 'Admin\ForecastingController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/forecasting/generate', 'Admin\ForecastingController@generate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/forecasting/{id}', 'Admin\ForecastingController@show')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/forecasting/{id}', 'Admin\ForecastingController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: reports (Phase 10)
// ------------------------------------------------------------------
$router->get('/admin/reports', 'Admin\ReportController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/reports/generate', 'Admin\ReportController@generate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/reports/{id}', 'Admin\ReportController@show')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/reports/{id}/download', 'Admin\ReportController@download')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/reports/{id}', 'Admin\ReportController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: notifications (Phase 9)
// ------------------------------------------------------------------
$router->get('/admin/notifications', 'Admin\NotificationController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/notifications/send', 'Admin\NotificationController@send')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/notifications/rules/run', 'Admin\NotificationController@runRules')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/notifications/rules/{id}/toggle', 'Admin\NotificationController@toggleRule')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/notifications/templates', 'Admin\NotificationController@storeTemplate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/notifications/templates/{id}/toggle', 'Admin\NotificationController@toggleTemplate')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: users (Phase 11)
// ------------------------------------------------------------------
$router->get('/admin/users', 'Admin\UserController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/users/create', 'Admin\UserController@create')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/users', 'Admin\UserController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/users/{id}/edit', 'Admin\UserController@edit')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/users/{id}', 'Admin\UserController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/users/{id}', 'Admin\UserController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: audit logs (Phase 11)
// ------------------------------------------------------------------
$router->get('/admin/audit-logs', 'Admin\AuditLogController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: login logs (Phase 11)
// ------------------------------------------------------------------
$router->get('/admin/login-logs', 'Admin\LoginLogController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: settings (Phase 11)
// ------------------------------------------------------------------
$router->get('/admin/settings', 'Admin\SettingController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/settings', 'Admin\SettingController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: graduate management (Phase 3)
// ------------------------------------------------------------------
$router->get('/admin/graduates', 'Admin\GraduateController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/create', 'Admin\GraduateController@create')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/graduates', 'Admin\GraduateController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/export', 'Admin\GraduateController@export')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/import', 'Admin\GraduateController@import')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/import/template', 'Admin\GraduateController@downloadTemplate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/graduates/import', 'Admin\GraduateController@importProcess')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/{id}', 'Admin\GraduateController@show')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/graduates/{id}/edit', 'Admin\GraduateController@edit')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/graduates/{id}', 'Admin\GraduateController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/graduates/{id}', 'Admin\GraduateController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: programs (Phase 3)
// ------------------------------------------------------------------
$router->get('/admin/programs', 'Admin\ProgramController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/programs/create', 'Admin\ProgramController@create')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/programs', 'Admin\ProgramController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/programs/{id}/edit', 'Admin\ProgramController@edit')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/programs/{id}', 'Admin\ProgramController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/programs/{id}', 'Admin\ProgramController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: batches (Phase 3)
// ------------------------------------------------------------------
$router->get('/admin/batches', 'Admin\BatchController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/batches/create', 'Admin\BatchController@create')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/batches', 'Admin\BatchController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/batches/{id}/edit', 'Admin\BatchController@edit')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/batches/{id}', 'Admin\BatchController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/batches/{id}', 'Admin\BatchController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: survey engine (Phase 4)
// ------------------------------------------------------------------
$router->get('/admin/surveys', 'Admin\SurveyController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/create', 'Admin\SurveyController@create')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys', 'Admin\SurveyController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/{id}', 'Admin\SurveyController@show')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/{id}/edit', 'Admin\SurveyController@edit')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/surveys/{id}', 'Admin\SurveyController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/activate', 'Admin\SurveyController@activate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/close', 'Admin\SurveyController@close')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/duplicate', 'Admin\SurveyController@duplicate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/surveys/{id}', 'Admin\SurveyController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

$router->post('/admin/surveys/sections', 'Admin\SurveySectionController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/surveys/sections/{id}', 'Admin\SurveySectionController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/surveys/sections/{id}', 'Admin\SurveySectionController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

$router->post('/admin/surveys/questions', 'Admin\SurveyQuestionController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/surveys/questions/{id}', 'Admin\SurveyQuestionController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/surveys/questions/{id}', 'Admin\SurveyQuestionController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/questions/{id}/duplicate', 'Admin\SurveyQuestionController@duplicate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/questions/reorder', 'Admin\SurveyQuestionController@reorder')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/sections/reorder', 'Admin\SurveySectionController@reorder')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: survey responses & preview (Phase 13)
// ------------------------------------------------------------------
$router->get('/admin/surveys/{id}/responses', 'Admin\SurveyController@responses')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/{id}/responses/{rid}', 'Admin\SurveyController@showResponse')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/{id}/preview', 'Admin\SurveyController@preview')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Admin: survey invitations (Phase 13 - public no-login respondents)
// ------------------------------------------------------------------
$router->get('/admin/surveys/{id}/invitations', 'Admin\SurveyInvitationController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/{id}/invitations/graduates', 'Admin\SurveyInvitationController@graduates')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/invitations', 'Admin\SurveyInvitationController@generate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/invitations/selection', 'Admin\SurveyInvitationController@saveSelection')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/invitations/selection/clear', 'Admin\SurveyInvitationController@clearTargets')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/invitations/selection/{tid}/remove', 'Admin\SurveyInvitationController@removeTarget')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/{id}/invitations/generate', 'Admin\SurveyInvitationController@generate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/invitations/{iid}/revoke', 'Admin\SurveyInvitationController@revoke')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/invitations/{iid}/regenerate', 'Admin\SurveyInvitationController@regenerate')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/invitations/{iid}/resend', 'Admin\SurveyInvitationController@resend')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/surveys/invitations/{iid}/notify', 'Admin\SurveyInvitationController@notify')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/surveys/invitations/{iid}/response', 'Admin\SurveyInvitationController@viewResponse')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Public: no-login survey access via secure invitation tokens (Phase 13/14)
// The receiptent clicks this link and fills the survey — no account needed.
// ------------------------------------------------------------------
$router->get('/survey/respond/{token}', 'PublicSurveyController@show');
$router->post('/survey/respond/{token}/submit', 'PublicSurveyController@submit');
// Short aliases kept for previously-shared links.
$router->get('/s/{token}', 'PublicSurveyController@show');
$router->post('/s/{token}/submit', 'PublicSurveyController@submit');

// ------------------------------------------------------------------
// Admin: employment (Phase 5)
// ------------------------------------------------------------------
$router->get('/admin/employment', 'Admin\EmploymentController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/employment/{id}', 'Admin\EmploymentController@show')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/employment/{id}', 'Admin\EmploymentController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/employment/{id}/sync', 'Admin\EmploymentController@syncFromSurvey')->middleware(AuthMiddleware::class, AdminMiddleware::class);

$router->get('/admin/employment-sectors', 'Admin\EmploymentSectorController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/employment-sectors', 'Admin\EmploymentSectorController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/employment-sectors/{id}', 'Admin\EmploymentSectorController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/employment-sectors/{id}', 'Admin\EmploymentSectorController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Graduate: employment (Phase 5)
// ------------------------------------------------------------------
$router->get('/graduate/employment', 'Graduate\EmploymentController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/employment', 'Graduate\EmploymentController@update')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/employment/history', 'Graduate\EmploymentController@storeHistory')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->delete('/graduate/employment/history/{id}', 'Graduate\EmploymentController@destroyHistory')->middleware(AuthMiddleware::class, GraduateMiddleware::class);

// ------------------------------------------------------------------
// Admin: competencies & curriculum feedback (Phase 6)
// ------------------------------------------------------------------
$router->get('/admin/competencies', 'Admin\CompetencyController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->get('/admin/competencies/analysis', 'Admin\CompetencyController@analysis')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/competencies/categories', 'Admin\CompetencyController@storeCategory')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/competencies/categories/{id}', 'Admin\CompetencyController@updateCategory')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/competencies/categories/{id}', 'Admin\CompetencyController@destroyCategory')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/competencies', 'Admin\CompetencyController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/competencies/{id}', 'Admin\CompetencyController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/competencies/{id}', 'Admin\CompetencyController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

$router->get('/admin/curriculum-feedback', 'Admin\CurriculumController@index')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->post('/admin/curriculum-feedback', 'Admin\CurriculumController@store')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->put('/admin/curriculum-feedback/{id}', 'Admin\CurriculumController@update')->middleware(AuthMiddleware::class, AdminMiddleware::class);
$router->delete('/admin/curriculum-feedback/{id}', 'Admin\CurriculumController@destroy')->middleware(AuthMiddleware::class, AdminMiddleware::class);

// ------------------------------------------------------------------
// Graduate: competencies & curriculum feedback (Phase 6)
// ------------------------------------------------------------------
$router->get('/graduate/competencies', 'Graduate\CompetencyController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/competencies', 'Graduate\CompetencyController@submit')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->get('/graduate/curriculum-feedback', 'Graduate\CurriculumController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/curriculum-feedback', 'Graduate\CurriculumController@submit')->middleware(AuthMiddleware::class, GraduateMiddleware::class);

// ------------------------------------------------------------------
// Graduate dashboard
// ------------------------------------------------------------------
$router->get('/graduate/dashboard', 'Graduate\DashboardController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);

// ------------------------------------------------------------------
// Graduate: notifications (Phase 9)
// ------------------------------------------------------------------
$router->get('/graduate/notifications', 'Graduate\NotificationController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/notifications/{id}/read', 'Graduate\NotificationController@markRead')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/notifications/mark-all-read', 'Graduate\NotificationController@markAllRead')->middleware(AuthMiddleware::class, GraduateMiddleware::class);

// ------------------------------------------------------------------
// Graduate: tracer survey (Phase 4)
// ------------------------------------------------------------------
$router->get('/graduate/survey', 'Graduate\SurveyController@index')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->get('/graduate/survey/{id}', 'Graduate\SurveyController@show')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
$router->post('/graduate/survey/{id}/submit', 'Graduate\SurveyController@submit')->middleware(AuthMiddleware::class, GraduateMiddleware::class);
