<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Services\Mailer;

/**
 * Notification service: templates, sending, and automated rule evaluation.
 */
final class NotificationService
{
    /**
     * Create a notification record (and optionally email it).
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $channel = 'in_app',
        ?string $link = null,
        array $data = []
    ): int {
        Database::run(
            'INSERT INTO notifications (user_id, type, title, message, channel, is_read, email_sent, email_sent_at, link, data)
             VALUES (?, ?, ?, ?, ?, 0, 0, NULL, ?, ?)',
            [$userId, $type, $title, $message, $channel, $link, json_encode($data)]
        );
        $id = (int) Database::lastInsertId();

        if (in_array($channel, ['email', 'both'], true) && setting('email_notifications_enabled', 'true') === 'true') {
            $template = Database::fetch('SELECT * FROM notification_templates WHERE type = ?', [$type]);
            $to = Database::fetch('SELECT email FROM users WHERE id = ?', [$userId])['email'] ?? null;
            if ($to && $template) {
                $subject = $this->fill((string) ($template['subject'] ?? $title), $data);
                $body = $this->fill((string) ($template['body'] ?? $message), $data);
                $sent = Mailer::send($to, $subject, nl2br(e($body)));
                if ($sent) {
                    Database::run('UPDATE notifications SET email_sent = 1, email_sent_at = NOW() WHERE id = ?', [$id]);
                }
            }
        }

        return $id;
    }

    /**
     * Send to every graduate (or filtered subset).
     */
    public function notifyAllGraduates(
        string $type,
        string $title,
        string $message,
        string $channel = 'in_app',
        ?string $link = null,
        ?int $programId = null,
        ?int $batchId = null
    ): int {
        $where = ['u.role_id = (SELECT id FROM roles WHERE slug = "graduate")', 'u.deleted_at IS NULL', 'u.is_active = 1'];
        $params = [];
        if ($programId) {
            $where[] = 'g.program_id = ?';
            $params[] = $programId;
        }
        if ($batchId) {
            $where[] = 'g.batch_id = ?';
            $params[] = $batchId;
        }

        $users = Database::fetchAll(
            'SELECT u.id FROM users u
             LEFT JOIN graduates g ON g.id = u.graduate_id
             WHERE ' . implode(' AND ', $where),
            $params
        );

        $count = 0;
        foreach ($users as $user) {
            $this->notify((int) $user['id'], $type, $title, $message, $channel, $link);
            $count++;
        }
        return $count;
    }

    /**
     * Evaluate the automated notification rules.
     *
     * @return array<int, array{rule:string, name:string, count:int}>
     */
    public function runRules(): array
    {
        $results = [];
        $rules = Database::fetchAll('SELECT * FROM notification_rules WHERE is_active = 1');

        foreach ($rules as $rule) {
            $count = $this->evaluateRule($rule['rule_key']);
            if ($count > 0) {
                $results[] = ['rule' => $rule['rule_key'], 'name' => $rule['name'], 'count' => $count];
            }
        }
        return $results;
    }

    /**
     * Run a single rule and dispatch its notifications.
     */
    private function evaluateRule(string $ruleKey): int
    {
        switch ($ruleKey) {
            case 'incomplete_survey':
                $count = $this->ruleIncompleteSurvey();
                break;
            case 'low_response_rate':
                $count = $this->ruleLowResponseRate();
                break;
            case 'approaching_deadline':
                $count = $this->ruleApproachingDeadline();
                break;
            case 'profile_outdated':
                $count = $this->ruleProfileOutdated();
                break;
            case 'employment_outdated':
                $count = $this->ruleEmploymentOutdated();
                break;
            case 'forecast_below_target':
                $count = $this->ruleForecastBelowTarget();
                break;
            default:
                $count = 0;
        }
        if ($count > 0) {
            Logger::info('Notification rule evaluated', ['rule' => $ruleKey, 'dispatched' => $count]);
        }
        return $count;
    }

    private function ruleIncompleteSurvey(): int
    {
        $active = Database::fetch('SELECT * FROM surveys WHERE status = "active" AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1');
        if (!$active || setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }

        $rows = Database::fetchAll(
            'SELECT g.id AS graduate_id, u.id AS user_id
             FROM graduates g
             JOIN users u ON u.graduate_id = g.id AND u.deleted_at IS NULL
             LEFT JOIN survey_responses sr ON sr.survey_id = ? AND sr.graduate_id = g.id
             WHERE sr.id IS NULL AND g.deleted_at IS NULL
             LIMIT 25',
            [(int) $active['id']]
        );

        $count = 0;
        foreach ($rows as $row) {
            $exists = Database::fetch(
                'SELECT id FROM notifications WHERE user_id = ? AND type = "survey_reminder" AND created_at >= NOW() - INTERVAL 7 DAY',
                [(int) $row['user_id']]
            );
            if ($exists) {
                continue;
            }
            $this->notify(
                (int) $row['user_id'],
                'survey_reminder',
                'Complete the IAT Tracer Survey',
                'This is a reminder to complete the current IAT Tracer Survey. Your response helps improve the IAT program.',
                'in_app',
                'graduate/survey'
            );
            $count++;
        }
        return $count;
    }

    private function ruleLowResponseRate(): int
    {
        if (setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }
        $analytics = new AnalyticsService();
        $kpis = $analytics->kpis();
        $target = (int) setting('response_target', 85);
        if (($kpis['response_rate'] ?? 100) >= $target) {
            return 0;
        }

        $count = 0;
        foreach ($this->adminIds() as $adminId) {
            $exists = Database::fetch(
                'SELECT id FROM notifications WHERE user_id = ? AND type = "low_response_alert" AND created_at >= NOW() - INTERVAL 1 DAY',
                [$adminId]
            );
            if ($exists) {
                continue;
            }
            $this->notify(
                $adminId,
                'low_response_alert',
                'Survey response rate below target',
                'The current survey response rate is ' . round($kpis['response_rate'], 1) . '% (target ' . $target . '%). Consider sending reminders.',
                'in_app',
                'admin/analytics'
            );
            $count++;
        }
        return $count;
    }

    private function ruleApproachingDeadline(): int
    {
        if (setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }
        $days = (int) setting('deadline_reminder_days', 3);
        $surveys = Database::fetchAll(
            'SELECT * FROM surveys WHERE status = "active" AND deleted_at IS NULL AND end_date IS NOT NULL
             AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)',
            [$days]
        );
        if (empty($surveys)) {
            return 0;
        }

        $count = 0;
        foreach ($surveys as $survey) {
            foreach ($this->adminIds() as $adminId) {
                // Deduplicate: only one deadline alert per survey+admin while the survey is active.
                $exists = Database::fetch(
                    'SELECT id FROM notifications
                     WHERE user_id = ? AND type = "survey_deadline_alert"
                     AND json_unquote(json_extract(data, "$.survey_id")) = ?
                     AND created_at >= NOW() - INTERVAL ? DAY',
                    [$adminId, (string) $survey['id'], max(1, $days)]
                );
                if ($exists) {
                    continue;
                }
                $this->notify(
                    $adminId,
                    'survey_deadline_alert',
                    'Survey deadline is approaching',
                    'The survey "' . $survey['title'] . '" closes on ' . date('M j, Y', strtotime((string) $survey['end_date'])) . '. Review the response rate.',
                    'in_app',
                    'admin/analytics',
                    ['survey_id' => $survey['id']]
                );
                $count++;
            }
        }
        return $count;
    }

    /**
     * Remind graduates whose record/contact info is outdated.
     */
    private function ruleProfileOutdated(): int
    {
        if (setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }
        $interval = max(1, (int) setting('profile_update_interval_days', 180));

        $rows = Database::fetchAll(
            'SELECT g.id AS graduate_id, u.id AS user_id
             FROM graduates g
             JOIN users u ON u.graduate_id = g.id AND u.deleted_at IS NULL AND u.is_active = 1
             WHERE g.deleted_at IS NULL
               AND (g.contact_number IS NULL OR g.contact_number = "" OR g.email IS NULL OR g.email = ""
                    OR g.address IS NULL OR g.address = "")
             LIMIT 25',
            []
        );

        $count = 0;
        foreach ($rows as $row) {
            $exists = Database::fetch(
                'SELECT id FROM notifications WHERE user_id = ? AND type = "profile_update_reminder" AND created_at >= NOW() - INTERVAL ? DAY',
                [(int) $row['user_id'], $interval]
            );
            if ($exists) {
                continue;
            }
            $this->notify(
                (int) $row['user_id'],
                'profile_update_reminder',
                'Update your graduate profile',
                'Please review and update your contact information to keep your graduate record current.',
                'in_app',
                'graduate/profile'
            );
            $count++;
        }
        return $count;
    }

    /**
     * Remind graduates whose employment information is outdated.
     */
    private function ruleEmploymentOutdated(): int
    {
        if (setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }
        $interval = max(1, (int) setting('employment_update_interval_days', 365));

        $rows = Database::fetchAll(
            'SELECT g.id AS graduate_id, u.id AS user_id, ep.id AS profile_id, ep.updated_at
             FROM graduates g
             JOIN users u ON u.graduate_id = g.id AND u.deleted_at IS NULL AND u.is_active = 1
             LEFT JOIN employment_profiles ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
             WHERE g.deleted_at IS NULL
               AND (ep.id IS NULL OR ep.updated_at < NOW() - INTERVAL ? DAY)
             LIMIT 25',
            [$interval]
        );

        $count = 0;
        foreach ($rows as $row) {
            $exists = Database::fetch(
                'SELECT id FROM notifications WHERE user_id = ? AND type = "employment_update_reminder" AND created_at >= NOW() - INTERVAL ? DAY',
                [(int) $row['user_id'], $interval]
            );
            if ($exists) {
                continue;
            }
            $this->notify(
                (int) $row['user_id'],
                'employment_update_reminder',
                'Update your employment information',
                'It has been a while since your employment information was updated. Please submit your current employment details.',
                'in_app',
                'graduate/employment'
            );
            $count++;
        }
        return $count;
    }

    /**
     * Alert administrators when the most recent employment forecast is below target.
     */
    private function ruleForecastBelowTarget(): int
    {
        if (setting('in_app_notifications_enabled', 'true') !== 'true') {
            return 0;
        }
        $target = (int) (setting('employment_target', 70) ?: 70);
        $forecasting = new ForecastingService();

        $models = Database::fetchAll(
            'SELECT fm.*, fr.value AS latest_value, fr.year AS latest_year
             FROM forecast_models fm
             JOIN forecast_results fr ON fr.model_id = fm.id
             WHERE fm.indicator = "employment_rate"
               AND fr.is_forecast = 1
               AND fr.year = (
                   SELECT MAX(fr2.year) FROM forecast_results fr2
                   WHERE fr2.model_id = fm.id AND fr2.is_forecast = 1
               )
             ORDER BY fm.created_at DESC
             LIMIT 3'
        );

        $count = 0;
        foreach ($models as $model) {
            if ($model['latest_value'] === null || (float) $model['latest_value'] >= $target) {
                continue;
            }
            foreach ($this->adminIds() as $adminId) {
                // Deduplicate: only one below-target alert per model+admin (7-day window).
                $exists = Database::fetch(
                    'SELECT id FROM notifications
                     WHERE user_id = ? AND type = "forecast_warning"
                     AND json_unquote(json_extract(data, "$.model_id")) = ?
                     AND created_at >= NOW() - INTERVAL 7 DAY',
                    [$adminId, (string) $model['id']]
                );
                if ($exists) {
                    continue;
                }
                $this->notify(
                    $adminId,
                    'forecast_warning',
                    'Employment forecast below target',
                    'The ' . $model['name'] . ' forecast projects ' . round((float) $model['latest_value'], 1) . '% employment by ' . $model['latest_year'] . ', below the ' . $target . '% target.',
                    'in_app',
                    'admin/forecasting',
                    ['model_id' => $model['id']]
                );
                $count++;
            }
        }
        return $count;
    }

    /**
     * Active admin user IDs.
     *
     * @return int[]
     */
    private function adminIds(): array
    {
        $rows = Database::fetchAll(
            'SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug = "admin" AND u.deleted_at IS NULL AND u.is_active = 1'
        );
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /**
     * Replace {placeholder} tokens in template text.
     */
    private function fill(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}
