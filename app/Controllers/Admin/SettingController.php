<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Setting;

/**
 * System settings manager (admin).
 */
class SettingController extends Controller
{
    /**
     * Whitelist of editable setting keys with metadata.
     */
    private function fields(): array
    {
        return [
            // General
            ['key' => 'university_name', 'label' => 'University Name', 'group' => 'general', 'type' => 'text'],
            ['key' => 'campus_name', 'label' => 'Campus Name', 'group' => 'general', 'type' => 'text'],
            ['key' => 'institute_name', 'label' => 'Institute Name', 'group' => 'general', 'type' => 'text'],
            ['key' => 'study_title', 'label' => 'Study Title', 'group' => 'general', 'type' => 'text'],
            ['key' => 'current_tracer_year', 'label' => 'Current Tracer Year', 'group' => 'general', 'type' => 'number'],
            ['key' => 'system_timezone', 'label' => 'System Timezone', 'group' => 'general', 'type' => 'text'],
            ['key' => 'system_logo', 'label' => 'System Logo (URL/path)', 'group' => 'general', 'type' => 'text'],

            // Thresholds
            ['key' => 'response_target', 'label' => 'Response Rate Target (%)', 'group' => 'thresholds', 'type' => 'number'],
            ['key' => 'employment_target', 'label' => 'Employment Target (%)', 'group' => 'thresholds', 'type' => 'number'],
            ['key' => 'forecast_threshold', 'label' => 'Forecast Threshold (%)', 'group' => 'thresholds', 'type' => 'number'],
            ['key' => 'low_response_threshold', 'label' => 'Low Response Threshold (%)', 'group' => 'thresholds', 'type' => 'number'],
            ['key' => 'three_month_employment_target', 'label' => '3-Month Employment Benchmark (%)', 'group' => 'thresholds', 'type' => 'number'],
            ['key' => 'waiting_time_benchmark_months', 'label' => 'Waiting-Time Benchmark (months)', 'group' => 'thresholds', 'type' => 'number'],

            // Notifications
            ['key' => 'email_notifications_enabled', 'label' => 'Email Notifications Enabled', 'group' => 'notifications', 'type' => 'checkbox'],
            ['key' => 'in_app_notifications_enabled', 'label' => 'In-App Notifications Enabled', 'group' => 'notifications', 'type' => 'checkbox'],
            ['key' => 'reminder_interval_days', 'label' => 'Reminder Interval (days)', 'group' => 'notifications', 'type' => 'number'],
            ['key' => 'deadline_reminder_days', 'label' => 'Deadline Reminder (days)', 'group' => 'notifications', 'type' => 'number'],
            ['key' => 'profile_update_interval_days', 'label' => 'Profile Update Interval (days)', 'group' => 'notifications', 'type' => 'number'],
            ['key' => 'employment_update_interval_days', 'label' => 'Employment Update Interval (days)', 'group' => 'notifications', 'type' => 'number'],
        ];
    }

    public function index(Request $request): void
    {
        $groups = ['general' => 'General', 'thresholds' => 'Thresholds', 'notifications' => 'Notifications'];
        $values = Setting::all();

        $fields = [];
        foreach ($groups as $slug => $label) {
            $fields[$slug] = [
                'label'  => $label,
                'fields' => array_values(array_filter($this->fields(), fn ($f) => $f['group'] === $slug)),
            ];
        }

        $this->view('admin/settings/index', [
            'title'    => 'Settings',
            'subtitle' => 'System configuration',
            'groups'   => $fields,
            'values'   => $values,
        ]);
    }

    public function update(Request $request): never
    {
        Csrf::validateOrAbort();
        $data = $request->all();
        $fields = $this->fields();

        foreach ($fields as $field) {
            $key = $field['key'];
            if ($field['type'] === 'checkbox') {
                $value = isset($data[$key]) ? 'true' : 'false';
            } else {
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                $value = trim((string) $data[$key]);
            }
            Setting::set($key, $value);
        }

        Setting::flush();
        $this->audit('update', 'settings', 'Updated system settings.');
        $this->success('Settings saved successfully.', 'admin/settings');
    }
}
