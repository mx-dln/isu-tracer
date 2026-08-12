<?php

declare(strict_types=1);

/**
 * Development seeder.
 *
 * Usage:
 *   php database/seeders/seed.php            # seed only if not seeded yet
 *   php database/seeders/seed.php --fresh    # wipe and re-seed
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePath = dirname(__DIR__, 2);
require $basePath . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable($basePath)->safeLoad();

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', env('DB_HOST'), (int) env('DB_PORT'), env('DB_DATABASE')),
    env('DB_USERNAME'),
    env('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$fresh = in_array('--fresh', $argv, true);

echo "=== ISU IAT Tracer - Seeder ===\n";

$seedMarker = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
if ((int) $seedMarker > 0 && !$fresh) {
    echo "Database already contains data. Use --fresh to re-seed.\n";
    exit(0);
}

$run = function (string $sql, array $params = []) use ($pdo): void {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
};
$lastId = fn (): int => (int) $pdo->lastInsertId();

// ------------------------------------------------------------------
// WIPE
// ------------------------------------------------------------------
if ($fresh) {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE `{$t}`");
    }
    echo "[OK] Existing tables truncated.\n";
}

try {
    $pdo->beginTransaction();

    // ------------------------------------------------------------------
    // 1. ROLES
    // ------------------------------------------------------------------
    $adminRoleId = $graduateRoleId = null;
    $run('INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)', ['Administrator', 'admin', 'System administrator with full access']);
    $adminRoleId = $lastId();
    $run('INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)', ['IAT Graduate', 'graduate', 'Registered IAT graduate']);
    $graduateRoleId = $lastId();
    echo "[OK] Roles seeded.\n";

    // ------------------------------------------------------------------
    // 2. ADMIN USER
    // ------------------------------------------------------------------
    $adminPassword = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $run(
        'INSERT INTO users (role_id, name, email, password, is_active, must_change_password) VALUES (?, ?, ?, ?, 1, 0)',
        [$adminRoleId, 'System Administrator', 'admin@example.com', $adminPassword]
    );
    $adminUserId = $lastId();
    echo "[OK] Admin user created: admin@example.com / Admin@12345 (DEMO CREDENTIALS)\n";

    // ------------------------------------------------------------------
    // 3. PROGRAMS
    // ------------------------------------------------------------------
    $programs = [
        ['BAT-IAT', 'Bachelor of Agricultural Technology (IAT)', 'Four-year degree program under the Institute of Agricultural Technology focusing on agricultural production and technology.'],
        ['BAT-II', 'Bachelor of Agricultural Technology II', 'Variant program of the IAT with emphasis on agribusiness and technology.'],
        ['BSA', 'Bachelor of Science in Agriculture', 'Comprehensive agriculture program covering crop, animal, and soil sciences.'],
        ['AGRI-DIP', 'Diploma in Agricultural Technology', 'Short-term technical diploma in agricultural technology.'],
    ];
    $programIds = [];
    foreach ($programs as $p) {
        $run('INSERT INTO programs (code, name, description, is_active) VALUES (?, ?, ?, 1)', $p);
        $programIds[] = $lastId();
    }
    echo "[OK] Programs seeded.\n";

    // ------------------------------------------------------------------
    // 4. BATCHES
    // ------------------------------------------------------------------
    $batchIds = [];
    for ($y = 2021; $y <= 2026; $y++) {
        $run('INSERT INTO batches (year, label, is_active) VALUES (?, ?, 1)', [$y, "Batch {$y}"]);
        $batchIds[$y] = $lastId();
    }
    echo "[OK] Batches 2021-2026 seeded.\n";

    // ------------------------------------------------------------------
    // 5. EMPLOYMENT SECTORS
    // ------------------------------------------------------------------
    $sectors = [
        'Agriculture Production', 'Agribusiness', 'Agricultural Extension', 'Government',
        'Research', 'Education', 'Food Processing', 'Agricultural Technology',
        'Farm Management', 'Agricultural Sales/Marketing', 'Entrepreneurship', 'Other',
    ];
    $sectorIds = [];
    foreach ($sectors as $s) {
        $run('INSERT INTO employment_sectors (name, is_active) VALUES (?, 1)', [$s]);
        $sectorIds[] = $lastId();
    }
    echo "[OK] Employment sectors seeded.\n";

    // ------------------------------------------------------------------
    // 6. COMPETENCY CATEGORIES & COMPETENCIES
    // ------------------------------------------------------------------
    $competencyData = [
        'Agricultural Production' => ['Crop Production and Management', 'Livestock and Poultry Production', 'Soil and Water Management', 'Post-Harvest Handling'],
        'Technical Skills' => ['Equipment Operation and Maintenance', 'Farm Mechanization', 'Irrigation Systems', 'Agricultural Engineering Practices'],
        'Communication' => ['Oral Communication', 'Written Communication', 'Community Engagement'],
        'Problem Solving' => ['Critical Thinking', 'Analytical Skills', 'Decision Making'],
        'Leadership' => ['Team Leadership', 'Organizational Skills', 'Initiative'],
        'Entrepreneurship' => ['Business Planning', 'Marketing Skills', 'Financial Management', 'Innovation and Risk Taking'],
        'Digital Skills' => ['Computer Literacy', 'Data Management', 'Digital Tools for Agriculture'],
        'Research Skills' => ['Research Methodology', 'Data Analysis', 'Technical Writing'],
        'Financial Literacy' => ['Budgeting', 'Record Keeping', 'Accessing Credit and Financing'],
        'Professional Ethics' => ['Work Ethic', 'Professional Conduct', 'Integrity'],
        'Innovation' => ['Creative Problem Solving', 'Adoption of New Technologies', 'Sustainable Practices'],
        'Workplace Skills' => ['Adaptability', 'Time Management', 'Teamwork', 'Attention to Detail'],
    ];
    $competencyIds = [];
    foreach ($competencyData as $category => $comps) {
        $run('INSERT INTO competency_categories (name, description, sort_order) VALUES (?, ?, 0)', [$category, '']);
        $catId = $lastId();
        foreach ($comps as $i => $comp) {
            $run('INSERT INTO competencies (category_id, name, description, sort_order) VALUES (?, ?, ?, ?)', [$catId, $comp, '', $i + 1]);
            $competencyIds[] = $lastId();
        }
    }
    echo "[OK] Competencies seeded (" . count($competencyIds) . ").\n";

    // ------------------------------------------------------------------
    // 7. CURRICULUM QUESTIONS
    // ------------------------------------------------------------------
    $curriculumQuestions = [
        'Relevance of the curriculum to current industry needs',
        'Practical training and skills development',
        'Laboratory activities and facilities',
        'Field activities and on-farm training',
        'Internship / On-the-Job Training (OJT)',
        'Technical preparation for the workplace',
        'Soft skills preparation (communication, teamwork, leadership)',
        'Digital skills and technology preparation',
        'Entrepreneurship preparation',
        'Overall curriculum relevance to your current employment',
    ];
    $curriculumQuestionIds = [];
    foreach ($curriculumQuestions as $i => $q) {
        $run('INSERT INTO curriculum_questions (question_text, sort_order, is_active) VALUES (?, ?, 1)', [$q, $i + 1]);
        $curriculumQuestionIds[] = $lastId();
    }
    echo "[OK] Curriculum feedback questions seeded.\n";

    // ------------------------------------------------------------------
    // 8. LIKERT SCALES (5-point, standard research interpretation)
    // ------------------------------------------------------------------
    // 5 = Highly Aligned (4.20-5.00)
    // 4 = Aligned        (3.40-4.19)
    // 3 = Moderately Aligned (2.60-3.39)
    // 2 = Weakly Aligned (1.80-2.59)
    // 1 = Not Aligned    (1.00-1.79)
    $likertRows = [
        [5, 'Highly Aligned', 'Highly Aligned / Highly Relevant', 4.20, 5.00],
        [4, 'Aligned', 'Aligned / Relevant', 3.40, 4.19],
        [3, 'Moderately Aligned', 'Moderately Aligned / Moderately Relevant', 2.60, 3.39],
        [2, 'Weakly Aligned', 'Weakly Aligned / Slightly Relevant', 1.80, 2.59],
        [1, 'Not Aligned', 'Not Aligned / Not Relevant', 1.00, 1.79],
    ];
    foreach (['job_relevance', 'competency', 'curriculum'] as $scaleType) {
        foreach ($likertRows as [$score, $label, $interp, $lo, $hi]) {
            $run(
                'INSERT INTO likert_scales (scale_type, score, label, interpretation, lower_bound, upper_bound) VALUES (?, ?, ?, ?, ?, ?)',
                [$scaleType, $score, $label, $interp, $lo, $hi]
            );
        }
    }
    echo "[OK] Likert scales seeded.\n";

    // ------------------------------------------------------------------
    // 9. SYSTEM SETTINGS
    // ------------------------------------------------------------------
    $settings = [
        ['university_name', 'Isabela State University', 'general'],
        ['campus_name', 'Cauayan Campus', 'general'],
        ['institute_name', 'Institute of Agricultural Technology (IAT)', 'general'],
        ['study_title', 'Tracer Study with Forecasting and Notification for IAT Graduates', 'general'],
        ['current_tracer_year', '2026', 'general'],
        ['demo_mode', 'true', 'general'],
        ['response_target', '85', 'thresholds'],
        ['employment_target', '70', 'thresholds'],
        ['forecast_threshold', '60', 'thresholds'],
        ['low_response_threshold', '50', 'thresholds'],
        ['profile_update_interval_days', '180', 'notifications'],
        ['reminder_interval_days', '7', 'notifications'],
        ['deadline_reminder_days', '3', 'notifications'],
        ['employment_update_interval_days', '365', 'notifications'],
        ['email_notifications_enabled', 'true', 'notifications'],
        ['in_app_notifications_enabled', 'true', 'notifications'],
        ['system_timezone', 'Asia/Manila', 'general'],
        ['system_logo', '', 'general'],
    ];
    foreach ($settings as [$key, $value, $group]) {
        $run('INSERT INTO system_settings (`key`, `value`, `group_name`, is_encrypted) VALUES (?, ?, ?, 0)', [$key, $value, $group]);
    }
    echo "[OK] System settings seeded.\n";

    // ------------------------------------------------------------------
    // 10. DEFAULT SURVEY
    // ------------------------------------------------------------------
    $run(
        'INSERT INTO surveys (title, description, status, start_date, end_date, tracer_year, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            'IAT Graduate Tracer Survey ' . date('Y'),
            'This tracer study survey gathers information on the employment profile, job relevance, competencies, and curriculum feedback of IAT graduates. (DEMO SURVEY)',
            'active', date('Y-01-01'), date('Y-12-31'), (int) date('Y'), $adminUserId,
        ]
    );
    $surveyId = $lastId();

    $sections = [
        'A' => ['Personal Information', 'Basic demographic information of the graduate.'],
        'B' => ['Educational Background', 'Academic information from your time at IAT.'],
        'C' => ['Employment Profile', 'Current employment status and job details.'],
        'D' => ['Time to First Employment', 'How quickly you secured your first job after graduation.'],
        'E' => ['Job Relevance / Job-Field Congruence', 'Alignment of your job with your IAT education.'],
        'F' => ['Competency Alignment', 'Assessment of competencies developed by the program.'],
        'G' => ['Curriculum Feedback', 'Feedback on the IAT curriculum and training.'],
        'H' => ['Additional Comments', 'Any additional comments or suggestions.'],
    ];
    $sectionIds = [];
    $sectionOrder = 0;
    foreach ($sections as $letter => $section) {
        [$title, $desc] = $section;
        $sectionOrder++;
        $run('INSERT INTO survey_sections (survey_id, title, description, sort_order) VALUES (?, ?, ?, ?)', [$surveyId, $title, $desc, $sectionOrder]);
        $sectionIds[$title] = $lastId();
    }

    $questions = [
        // Section A - Personal Information
        ['Personal Information', 'graduation_year', 'What is your year of graduation?', 'dropdown', 1, ['2021','2022','2023','2024','2025','2026']],
        ['Personal Information', 'program_taken', 'Which IAT program did you complete?', 'dropdown', 1, ['Bachelor of Agricultural Technology (IAT)','Bachelor of Agricultural Technology II','Bachelor of Science in Agriculture','Diploma in Agricultural Technology']],
        ['Personal Information', 'sex', 'Sex', 'single_choice', 1, ['Male','Female']],
        ['Personal Information', 'civil_status', 'Civil status', 'dropdown', 1, ['Single','Married','Widowed','Separated']],
        ['Personal Information', 'contact_number', 'Current contact number', 'text', 0, []],
        ['Personal Information', 'email_address', 'Current email address', 'text', 1, []],
        ['Personal Information', 'current_address', 'Current municipality/city and province', 'text', 1, []],

        // Section B - Educational Background
        ['Educational Background', 'awards', 'Were you an honor student / received any academic distinction?', 'yes_no', 0, []],
        ['Educational Background', 'scholarship', 'Did you receive any scholarship during your studies?', 'yes_no', 0, []],

        // Section C - Employment Profile
        ['Employment Profile', 'employment_status', 'What is your current employment status?', 'single_choice', 1, ['Employed','Self-employed','Unemployed','Pursuing further studies']],
        ['Employment Profile', 'job_title', 'Current job title / position', 'text', 0, []],
        ['Employment Profile', 'employer', 'Name of employer / company / institution', 'text', 0, []],
        ['Employment Profile', 'industry_sector', 'Industry / employment sector', 'dropdown', 0, $sectors],
        ['Employment Profile', 'employment_type', 'Type of employment', 'dropdown', 0, ['Regular/Permanent','Contractual','Temporary','Casual','Part-time','Self-employed']],
        ['Employment Profile', 'work_location', 'Work location (municipality/city, province)', 'text', 0, []],
        ['Employment Profile', 'date_hired', 'Date hired in current job', 'date', 0, []],
        ['Employment Profile', 'salary_range', 'Monthly salary range (Php)', 'dropdown', 0, ['Below 10,000','10,000 - 20,000','20,001 - 30,000','30,001 - 40,000','40,001 - 50,000','Above 50,000','Prefer not to say']],
        ['Employment Profile', 'job_description', 'Brief description of your job duties', 'long_text', 0, []],

        // Section D - Time to First Employment
        ['Time to First Employment', 'first_job_date', 'Date of your FIRST employment after graduation', 'date', 0, []],
        ['Time to First Employment', 'time_to_first_job', 'How long did it take to find your first job after graduation?', 'single_choice', 0, ['Less than 1 month','1-3 months','4-6 months','7-12 months','More than 1 year']],
        ['Time to First Employment', 'job_search_method', 'How did you find your first job?', 'dropdown', 0, ['University career placement','Internet/job portals','Newspaper ads','Friend/relative referral','Walk-in application','Government PESO/Job Fair','Self-employed/started own business','Other']],

        // Section E - Job Relevance
        ['Job Relevance / Job-Field Congruence', 'job_relation', 'Is your current job related to your IAT program/field of study?', 'single_choice', 1, ['Yes','No','Somewhat']],
        ['Job Relevance / Job-Field Congruence', 'job_relevance_rating', 'How relevant is your current job to the competencies you developed in the IAT program?', 'likert', 1, ['Not Aligned','Weakly Aligned','Moderately Aligned','Aligned','Highly Aligned']],
        ['Job Relevance / Job-Field Congruence', 'skills_utilization', 'Rate how much of what you learned in the IAT program you apply in your current job.', 'likert', 1, ['Not Aligned','Weakly Aligned','Moderately Aligned','Aligned','Highly Aligned']],

        // Section F - Competency Alignment
        ['Competency Alignment', 'competency_note', 'Rate your level of competency developed by the IAT program (1 = very low, 5 = very high):', 'long_text', 0, []],

        // Section G - Curriculum Feedback
        ['Curriculum Feedback', 'curriculum_note', 'Provide feedback on the IAT curriculum (relevance, practical training, facilities, OJT, etc.):', 'long_text', 0, []],

        // Section H - Additional Comments
        ['Additional Comments', 'additional_comments', 'Additional comments or suggestions for the IAT program:', 'long_text', 0, []],
    ];

    foreach ($questions as $i => [$sectionTitle, $key, $text, $type, $required, $options]) {
        $run(
            'INSERT INTO survey_questions (survey_id, section_id, question_key, question_text, type, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$surveyId, $sectionIds[$sectionTitle], $key, $text, $type, $required, $i + 1]
        );
        $qid = $lastId();
        foreach ($options as $j => $opt) {
            $run('INSERT INTO survey_options (question_id, option_text, option_value, sort_order) VALUES (?, ?, ?, ?)', [$qid, $opt, $j + 1, $j + 1]);
        }
    }
    echo "[OK] Default tracer survey seeded ({$surveyId}).\n";

    // ------------------------------------------------------------------
    // 11. NOTIFICATION TEMPLATES & RULES
    // ------------------------------------------------------------------
    $templates = [
        ['survey_reminder', 'both', 'Friendly reminder: Complete your IAT Tracer Survey', 'Dear graduate, this is a reminder to complete the IAT Tracer Survey for the current year. Your response is valuable to the study.'],
        ['survey_deadline_reminder', 'both', 'The tracer survey deadline is approaching', 'The IAT Tracer Survey will close soon. Please submit your response before the deadline.'],
        ['profile_update_reminder', 'in_app', 'Update your graduate profile', 'Please review and update your contact information to keep your graduate record current.'],
        ['employment_update_reminder', 'in_app', 'Update your employment information', 'It has been a while since your employment information was updated. Please submit your current employment details.'],
        ['survey_submission_confirmation', 'both', 'Tracer survey submitted successfully', 'Thank you! Your IAT Tracer Survey response has been received and recorded.'],
        ['low_response_alert', 'in_app', 'Low survey response rate', 'The current survey response rate has fallen below the configured target. Please send reminders.'],
        ['incomplete_records_alert', 'in_app', 'Incomplete graduate records', 'There are graduate records with missing employment information that require attention.'],
        ['survey_deadline_alert', 'in_app', 'Survey deadline is approaching', 'An active survey deadline is approaching. Review the response rate before closing.'],
        ['forecast_warning', 'in_app', 'Employment forecast below target', 'The generated employment forecast is below the configured employment target.'],
        ['system_alert', 'in_app', 'System alert', 'A system alert has been triggered.'],
    ];
    foreach ($templates as $t) {
        $run('INSERT INTO notification_templates (type, channel, subject, body, is_active) VALUES (?, ?, ?, ?, 1)', $t);
    }

    $rules = [
        ['incomplete_survey', 'Incomplete survey reminder', 'Remind graduates who have not completed the active survey.'],
        ['approaching_deadline', 'Approaching deadline', 'Warn when an active survey deadline is near.'],
        ['profile_outdated', 'Outdated profile', 'Remind graduates whose contact information is outdated.'],
        ['employment_outdated', 'Outdated employment', 'Remind graduates whose employment information is outdated.'],
        ['low_response_rate', 'Low response rate', 'Alert administrators when response rate falls below target.'],
        ['forecast_below_target', 'Forecast below target', 'Alert administrators when a forecast falls below the employment target.'],
    ];
    foreach ($rules as $r) {
        $run('INSERT INTO notification_rules (rule_key, name, description, is_active) VALUES (?, ?, ?, 1)', $r);
    }
    echo "[OK] Notification templates and rules seeded.\n";

    // ------------------------------------------------------------------
    // 12. SAMPLE GRADUATES + RESPONSES + EMPLOYMENT (DEMO DATA)
    // ------------------------------------------------------------------
    mt_srand(20261); // deterministic demo data

    $perBatch = [40, 45, 48, 52, 55, 60]; // 2021..2026
    $firstName = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Liza', 'Carlos', 'Rosa', 'Miguel', 'Elena', 'Ramon', 'Celia', 'Paolo', 'Diana', 'Marco', 'Sofia', 'Dennis', 'Grace', 'Vicente', 'Nina'];
    $lastName = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Ramos', 'Aquino', 'Domingo', 'Villanueva', 'Navarro', 'Salazar', 'Dela Cruz', 'Roxas', 'Padilla', 'Manalo', 'Santiago'];

    $totalGraduates = 0;
    $respondentIds = [];
    $employmentIds = [];
    $graduateProgramIds = [];
    $graduateBatchIds = [];
    $usedEmails = [];

    $i = 0;
    foreach ($perBatch as $bIdx => $count) {
        $year = 2021 + $bIdx;
        $batchId = $batchIds[$year];

        // Employment probability grows across years to produce a rising trend.
        $employProb = 0.58 + ($bIdx * 0.045);
        $selfEmployProb = 0.14;
        $furtherStudiesProb = 0.10;

        for ($g = 0; $g < $count; $g++) {
            $i++;
            $respond = mt_rand(1, 100) <= 82; // ~82% response rate
            $fn = $firstName[array_rand($firstName)];
            $ln = $lastName[array_rand($lastName)];
            $mn = $firstName[array_rand($firstName)];
            $sex = mt_rand(0, 1) ? 'Male' : 'Female';
            $studentNumber = sprintf('%04d-%04d', $year, $i);

            $emailBase = strtolower($fn . '.' . $ln . $year);
            $email = $emailBase . '@gmail.com';
            $emailCounter = 1;
            while (isset($usedEmails[$email])) {
                $emailCounter++;
                $email = $emailBase . $emailCounter . '@gmail.com';
            }
            $usedEmails[$email] = true;

            $run(
                'INSERT INTO graduates (student_number, first_name, middle_name, last_name, email, contact_number, sex, civil_status, address, municipality, province, program_id, batch_id, graduation_year, is_validated, is_demo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
                [
                    $studentNumber, $fn, $mn, $ln, $email,
                    '09' . mt_rand(100000000, 999999999),
                    $sex,
                    ['Single','Married','Single','Married','Single'][mt_rand(0, 4)],
                    'Sample Address, Barangay X',
                    ['Cauayan City','Santiago City','Roxas','San Mateo','Ilagan City','Aurora'][mt_rand(0, 5)],
                    'Isabela',
                    $programIds[mt_rand(0, count($programIds) - 1)],
                    $batchId,
                    $year,
                    mt_rand(0, 1) === 1 ? 1 : 0,
                ]
            );
            $graduateId = $lastId();
            $totalGraduates++;
            $graduateProgramIds[$graduateId] = $programIds[mt_rand(0, count($programIds) - 1)];
            $graduateBatchIds[$graduateId] = $batchId;

            if (!$respond) {
                continue; // non-respondent: graduate record only
            }
            $respondentIds[] = $graduateId;

            // Create user account + link for respondents.
            $passwordHash = password_hash('Graduate@12345', PASSWORD_DEFAULT);
            $run(
                'INSERT INTO users (role_id, graduate_id, name, email, password, is_active, must_change_password) VALUES (?, ?, ?, ?, ?, 1, 0)',
                [$graduateRoleId, $graduateId, "{$fn} {$ln}", $email, $passwordHash]
            );
            $graduateUserId = $lastId();
            $run('UPDATE graduates SET user_id = ? WHERE id = ?', [$graduateUserId, $graduateId]);

            // Survey response + answers.
            $run(
                'INSERT INTO survey_responses (survey_id, graduate_id, status, ip_address, user_agent, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [$surveyId, $graduateId, 'submitted', '127.0.0.1', 'Seeder']
            );
            $responseId = $lastId();

            // Determine employment status.
            $roll = mt_rand(1, 100);
            if ($roll <= $employProb * 100) {
                $status = mt_rand(1, 100) <= $selfEmployProb * 100 ? 'self_employed' : 'employed';
            } elseif ($roll <= ($employProb + $furtherStudiesProb) * 100) {
                $status = 'further_studies';
            } else {
                $status = 'unemployed';
            }

            $sectorId = $sectorIds[mt_rand(0, count($sectorIds) - 1)];
            $firstEmploymentDate = null;
            $dateHired = null;
            $jobRelevance = null;
            $isRelated = null;

            if (in_array($status, ['employed', 'self_employed'], true)) {
                // Time to first employment decreases slightly over the years.
                $monthsToFirstJob = max(0, 9 - $bIdx + mt_rand(-2, 3));
                $firstEmploymentDate = date('Y-m-d', strtotime("{$year}-06-15 +{$monthsToFirstJob} months"));
                $dateHired = date('Y-m-d', strtotime($firstEmploymentDate . ' +' . mt_rand(0, 12) . ' months'));
                if (strtotime($dateHired) > time()) {
                    $dateHired = date('Y-m-d', time() - mt_rand(86400, 86400 * 90));
                }
                $jobRelevance = mt_rand(1, 5);
                $isRelated = $jobRelevance >= 3 ? 1 : (mt_rand(0, 1));
            }

            $run(
                'INSERT INTO employment_profiles (graduate_id, status, job_title, employer, sector_id, sector_other, employment_type, work_location, date_hired, first_employment_date, salary_range, job_description, is_related_to_program, job_relevance_rating, is_current, is_demo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())',
                [
                    $graduateId,
                    $status,
                    in_array($status, ['employed', 'self_employed'], true) ? ['Farm Technician','Agricultural Technician','Extension Worker','Farm Manager','Sales Representative','Research Assistant','Agribusiness Owner','Production Supervisor'][mt_rand(0, 7)] : null,
                    in_array($status, ['employed', 'self_employed'], true) ? (['AgriCorp Inc.','Gov. Service','Isabela Provincial Office','Self-Owned Farm','Rural Bank','AgriTech PH','ISU','Local Coop'][mt_rand(0, 7)]) : null,
                    $sectorId,
                    null,
                    in_array($status, ['employed', 'self_employed'], true) ? ['regular','contractual','temporary','casual','part_time','self_employed'][mt_rand(0, 5)] : null,
                    in_array($status, ['employed', 'self_employed'], true) ? 'Cauayan City, Isabela' : null,
                    $dateHired,
                    $firstEmploymentDate,
                    in_array($status, ['employed', 'self_employed'], true) ? ['Below 10,000','10,000 - 20,000','20,001 - 30,000','30,001 - 40,000','40,001 - 50,000','Above 50,000'][mt_rand(0, 5)] : null,
                    in_array($status, ['employed', 'self_employed'], true) ? 'Handles agricultural production and extension activities. (DEMO DATA)' : null,
                    $isRelated,
                    $jobRelevance,
                ]
            );
            $employmentIds[] = $lastId();

            // Survey answers (subset for demo).
            $answers = [
                'graduation_year' => (string) $year,
                'sex' => $sex,
                'civil_status' => ['Single','Married','Widowed','Separated'][mt_rand(0, 3)],
                'email_address' => $email,
                'employment_status' => ucfirst(str_replace('_', ' ', $status)),
                'job_relation' => $isRelated === null ? 'No' : ($isRelated ? 'Yes' : 'No'),
                'job_relevance_rating' => (string) ($jobRelevance ?? mt_rand(1, 5)),
            ];
            $qByKey = $pdo->query("SELECT id, question_key FROM survey_questions WHERE survey_id = {$surveyId}")->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($answers as $key => $val) {
                if (!isset($qByKey[$key])) {
                    continue;
                }
                $run(
                    'INSERT INTO survey_answers (response_id, question_id, answer_value, answer_options) VALUES (?, ?, ?, NULL)',
                    [$responseId, $qByKey[$key], $val]
                );
            }

            // Competency responses.
            $shuffled = $competencyIds;
            shuffle($shuffled);
            foreach (array_slice($shuffled, 0, 8) as $cid) {
                $rating = mt_rand(2, 5);
                $run('INSERT INTO competency_responses (graduate_id, competency_id, rating, submitted_at) VALUES (?, ?, ?, NOW())', [$graduateId, $cid, $rating]);
            }

            // Curriculum feedback.
            foreach ($curriculumQuestionIds as $cqid) {
                $rating = mt_rand(2, 5);
                $run('INSERT INTO curriculum_feedback (graduate_id, question_id, rating, submitted_at) VALUES (?, ?, ?, NOW())', [$graduateId, $cqid, $rating]);
            }
        }
    }

    echo "[OK] Sample graduates created: {$totalGraduates} total, " . count($respondentIds) . " respondents.\n";
    echo "[OK] Sample employment profiles created: " . count($employmentIds) . ".\n";

    // ------------------------------------------------------------------
    // 13. SAMPLE NOTIFICATIONS
    // ------------------------------------------------------------------
    $gradUsers = $pdo->query('SELECT id FROM users WHERE role_id = ' . $graduateRoleId . ' LIMIT 5')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($gradUsers as $uid) {
        $run('INSERT INTO notifications (user_id, type, title, message, channel, link) VALUES (?, ?, ?, ?, ?, ?)', [
            $uid, 'survey_reminder', 'Complete the IAT Tracer Survey',
            'This is a reminder to complete the current IAT Tracer Survey. Your response helps improve the IAT program. (DEMO DATA)',
            'in_app', 'graduate/survey',
        ]);
        $run('INSERT INTO notifications (user_id, type, title, message, channel, link, is_read, read_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())', [
            $uid, 'survey_submission_confirmation', 'Tracer survey submitted',
            'Thank you for submitting your tracer survey response. (DEMO DATA)',
            'in_app', 'graduate/survey',
        ]);
    }
    $adminNotify = $pdo->query('SELECT id FROM users WHERE role_id = ' . $adminRoleId)->fetchColumn();
    $run('INSERT INTO notifications (user_id, type, title, message, channel, link) VALUES (?, ?, ?, ?, ?, ?)', [
        (int) $adminNotify, 'low_response_alert', 'Survey response rate below target',
        'The current survey response rate is below the configured target. Consider sending reminders. (DEMO DATA)',
        'in_app', 'admin/analytics',
    ]);
    echo "[OK] Sample notifications seeded.\n";

    $pdo->commit();

    echo "\n=== SEED COMPLETE ===\n";
    echo "Admin login:  admin@example.com / Admin@12345\n";
    echo "Demo graduate login: check graduates table (password for demo graduate accounts: Graduate@12345)\n";
    echo "NOTE: All sample records are labeled DEMO DATA and are not real ISU data.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
