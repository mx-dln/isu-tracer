<?php
/**
 * Slide-over user manual.
 *
 * @var string $role
 */
$role = $role ?? 'guest';

$adminSections = [
    'Dashboard' => [
        'Use this page as the first health check of the tracer system.',
        'Review total graduates, respondents, employment indicators, job relevance, and competency summaries.',
        'Use filters when available to narrow the dashboard by program or batch.',
        'Read charts from left to right: counts show volume, rates show performance, and distributions show where graduates are concentrated.',
        'Use the dashboard before generating reports so you can spot missing data or unusual results early.',
    ],
    'Graduates' => [
        'This is the master list of graduate records used by surveys, invitations, analytics, and reports.',
        'Create a graduate when one record is missing from the database.',
        'Edit a graduate to correct names, student number, program, batch, email, or contact number.',
        'Use import when adding many graduates at once from the provided template.',
        'Use export when you need a spreadsheet copy for checking, backup, or offline validation.',
        'Keep email and mobile number clean because invitation sending depends on these fields.',
        'Open a graduate record to review profile details and connected tracer information.',
    ],
    'Programs' => [
        'Programs define the academic courses used for graduate grouping and analytics.',
        'Create one program record per official program or specialization used by the institute.',
        'Program code appears in lists, filters, reports, and response detail pages.',
        'Edit names or codes carefully because changes affect how existing graduates are displayed.',
        'Avoid deleting a program if graduates are already assigned to it.',
    ],
    'Batches' => [
        'Batches represent graduation years or cohorts.',
        'Use batches to filter graduates, send invitations by year, and compare outcomes across groups.',
        'Create a batch before importing graduates from that year.',
        'Edit a batch when the label or year was entered incorrectly.',
        'Deleting a batch can affect assigned graduate records, so review linked data first.',
    ],
    'Surveys' => [
        'The Surveys page manages tracer survey forms and their lifecycle.',
        'Create a survey for each tracer study period, such as a specific year.',
        'Edit survey details such as title, description, dates, thank-you message, and invitation expiry.',
        'Activate a survey when it is ready to receive responses.',
        'Close a survey when the collection period is finished.',
        'Duplicate a survey when you want to reuse a previous structure for a new year.',
        'Open a survey to access Builder, Preview, Responses, and Invitations.',
    ],
    'Survey Builder' => [
        'Builder is where admins create the actual questionnaire.',
        'Use sections to group related questions such as Employment Profile, Program Feedback, or Competency Ratings.',
        'Add questions inside the correct section to keep the respondent experience organized.',
        'Choose the question type based on the expected answer: short text, paragraph, number, date, time, single choice, multiple choice, dropdown, linear scale, rating, Likert matrix, or image upload.',
        'Mark a question required only when the respondent must answer it before submitting.',
        'For choices, keep option labels short and mutually exclusive when possible.',
        'For image upload, tell respondents exactly what proof is expected, such as proof of employment.',
        'Use individual Save changes on each question after editing its label, help text, type, requirement, or options.',
        'Reorder questions when the flow needs adjustment; saved question edits should preserve the current order.',
        'Preview the survey after major builder changes.',
    ],
    'Survey Preview' => [
        'Preview shows the survey as respondents will see it.',
        'Use preview to check wording, section order, question order, required fields, option labels, rating controls, and upload fields.',
        'Preview does not replace testing with a real invitation when you need to verify token access.',
        'Check mobile readability because many graduates may answer from phones.',
    ],
    'Survey Responses' => [
        'Responses lists submitted survey forms.',
        'Use the list to see who submitted, when they submitted, and whether the response came from an invitation or portal access.',
        'Open a response to review answers in a readable layout.',
        'The response detail shows respondent identity, student number, program, batch, submission date, answer values, ratings, and uploaded proof images.',
        'Use response details to validate proof of employment and inspect individual answers before reporting.',
    ],
    'Survey Invitations' => [
        'Invitations are secure one-time survey links for graduates.',
        'First select and save target graduates from the graduate selection page.',
        'Generate invitations only after the target list is correct.',
        'Send by email, SMS, or both depending on available contact details.',
        'Email includes a full formatted message and survey link.',
        'SMS uses a short plain-text message with a clickable URL to stay within one credit where possible.',
        'Regenerate creates a fresh token when the old link should no longer be used.',
        'Resend sends the invitation again without manually copying the link.',
        'Revoke disables an invitation link.',
        'Completed invitations can be opened directly to the submitted response.',
    ],
    'Employment' => [
        'Employment consolidates job information from graduate records and survey responses.',
        'Review current employment status, job title, employer, industry, employment type, location, hire date, salary range, and job relevance.',
        'Use this page to validate employment data before analytics or reports.',
        'Sync or review survey-derived employment details when respondent answers should update employment records.',
    ],
    'Competencies' => [
        'Competencies show how graduates rate skills connected to the program.',
        'Use this page to identify strengths and weak areas in graduate preparation.',
        'Review summary ratings and analysis by category.',
        'Use competency results as evidence for curriculum review and program improvement.',
    ],
    'Curriculum Feedback' => [
        'Curriculum Feedback collects comments and ratings about program relevance and satisfaction.',
        'Use it to inspect qualitative suggestions and satisfaction patterns.',
        'Compare feedback with employment and competency results to find curriculum gaps.',
    ],
    'Analytics' => [
        'Analytics turns records and responses into charts and indicators.',
        'Use filters to focus on a program, batch, or subgroup.',
        'Review employment rate, sector distribution, job relevance, competency trends, and time-to-employment patterns.',
        'Use this page before reporting to understand the story behind the data.',
    ],
    'Forecasting' => [
        'Forecasting generates projected trends from available tracer data.',
        'Use it for planning, presentation, and administrative decision support.',
        'Generated forecasts should be treated as estimates, not confirmed outcomes.',
        'Review the data source and date before using a forecast in a report.',
    ],
    'Reports' => [
        'Reports create downloadable outputs from survey and tracer data.',
        'Generate reports after checking that responses, graduate records, and filters are correct.',
        'Open a generated report to review its contents before downloading or sharing.',
        'Delete outdated reports when they should no longer be used.',
    ],
    'Notifications' => [
        'Notifications lets admins send reminders and manage notification rules.',
        'Use reminders for pending surveys, incomplete data, or important announcements.',
        'Run notification rules when you want the system to process scheduled reminder logic.',
        'Check notification status if graduates say they did not receive a notice.',
    ],
    'Users' => [
        'Users manages login accounts and roles.',
        'Create admin accounts only for authorized staff.',
        'Create or maintain graduate accounts when portal login is needed.',
        'Edit user details to update names, emails, role assignments, or account state.',
        'Delete or deactivate accounts carefully because access changes immediately.',
    ],
    'Audit Logs and Login Logs' => [
        'Audit Logs show important actions performed in the system, such as changes to records or survey workflows.',
        'Login Logs show access history and help trace who signed in and when.',
        'Use these pages for accountability, troubleshooting, and security review.',
    ],
    'Settings' => [
        'Settings controls institution names, campus labels, study labels, and other system-wide display values.',
        'Changes here appear across headers, emails, reports, and public-facing pages.',
        'Review settings before sending invitations or generating official reports.',
    ],
];

$graduateSections = [
    'Dashboard' => [
        'Shows the graduate account overview, recent reminders, and pending tracer tasks.',
        'Use it to quickly find whether a survey, profile update, or feedback form still needs attention.',
    ],
    'Profile' => [
        'Contains personal and contact information.',
        'Graduates should keep email and mobile number updated so the school can send tracer notices.',
        'Profile information supports reporting and accurate graduate records.',
    ],
    'Tracer Survey' => [
        'This is where graduates answer assigned tracer survey forms.',
        'Required questions must be completed before submission.',
        'Upload fields may ask for proof such as employment evidence.',
        'After submission, answers are used for employment tracking, analytics, and institutional reports.',
    ],
    'Employment' => [
        'Graduates can record current employment status and job details.',
        'Important fields include job title, employer, industry, work location, salary range, hire date, and job relevance.',
        'Keeping this updated improves the accuracy of tracer analytics.',
    ],
    'Competencies' => [
        'Graduates rate skills and competencies gained from the program.',
        'Responses help the institute understand strengths and training gaps.',
    ],
    'Curriculum Feedback' => [
        'Graduates provide satisfaction ratings and suggestions about program content.',
        'Feedback supports curriculum improvement and accreditation evidence.',
    ],
    'Notifications' => [
        'Shows reminders, survey notices, and other system messages.',
        'Use the notification bell or this page to review unread messages.',
    ],
];

$commonSections = [
    'Top Bar' => [
        'The top bar shows the current page title and quick actions.',
        'Use the User Manual button to open this guide without leaving the page.',
        'Use the notification bell to view recent messages.',
        'Your name and role appear on the right side on larger screens.',
    ],
    'Sidebar' => [
        'The sidebar is the main navigation area.',
        'Admin users see records, research data, intelligence, and administration pages.',
        'Graduate users see their personal tracer pages.',
        'On small screens, use the menu button to open or close the sidebar.',
    ],
    'Search, Filters, and Pagination' => [
        'Search boxes help find records by name, title, email, or related text.',
        'Filters narrow lists by program, batch, year, status, or other page-specific fields.',
        'Pagination appears when a list has many records.',
        'Clear filters when expected records do not appear.',
    ],
    'Saving Changes' => [
        'Most forms require pressing Save, Update, Create, or Generate before changes are stored.',
        'Survey questions have their own Save changes button so each question can be edited independently.',
        'Success or error messages appear near the top after actions.',
        'If validation errors appear, read each message and correct the highlighted data.',
    ],
    'Deleting and Revoking' => [
        'Delete removes records where allowed.',
        'Some deletes are blocked when records are connected to responses, invitations, or logs.',
        'Revoking an invitation disables the link without deleting historical records.',
        'Use destructive actions carefully because they can affect reporting data.',
    ],
    'Importing and Exporting' => [
        'Use the provided graduate import template to avoid column errors.',
        'Check names, student numbers, emails, contact numbers, programs, and batches before import.',
        'Export data when you need offline review, backup, or spreadsheet checking.',
    ],
    'Good Data Practices' => [
        'Use official names, program codes, and batch years consistently.',
        'Keep contact numbers in a valid mobile format for SMS sending.',
        'Avoid duplicate graduate records because they can distort analytics.',
        'Preview surveys before sending invitations.',
        'Review responses and uploaded proof before using data in reports.',
    ],
];
?>

<div id="manual-backdrop" class="hidden fixed inset-0 bg-ink-900/50" style="z-index: 80;" aria-hidden="true"></div>
<aside id="manual-slider" class="hidden fixed inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl border-l border-ink-200 transition-transform duration-200 flex-col" style="z-index: 90; transform: translateX(100%);" aria-label="User Manual" aria-hidden="true">
    <div class="shrink-0 border-b border-ink-200 px-5 py-4 bg-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">IAT Tracer Study System</p>
                <h2 class="text-lg font-bold text-ink-950 mt-1">User Manual</h2>
                <p class="text-sm text-ink-600 mt-1">Detailed guide for every page, action, and workflow.</p>
            </div>
            <button type="button" id="manual-close" class="p-2 rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label="Close user manual">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-5 space-y-5" style="scrollbar-gutter: stable;">
        <section class="rounded-lg border border-brand-200 bg-brand-50 p-4">
            <h3 class="text-sm font-bold text-brand-900">How to use this manual</h3>
            <p class="text-sm text-brand-900/80 mt-2 leading-6">
                Open the section that matches the page you are using. Each section explains what the page is for,
                what actions are available, and what to check before saving, sending, generating, or reporting data.
            </p>
        </section>

        <?php if ($role === 'admin'): ?>
            <section>
                <h3 class="text-sm font-bold text-ink-900 mb-3">Administrator Pages</h3>
                <div class="space-y-2">
                    <?php foreach ($adminSections as $title => $items): ?>
                        <details class="group rounded-lg border border-ink-200 bg-white">
                            <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-ink-900"><?= e($title) ?></span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-ink-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <div class="border-t border-ink-100 px-6 py-4" style="background-color: rgba(248, 250, 252, 0.7);">
                                <ul class="space-y-2 text-sm text-ink-600 leading-6">
                                    <?php foreach ($items as $item): ?>
                                        <li class="flex gap-3">
                                            <span class="mt-2 rounded-full shrink-0" style="width: 6px; height: 6px; background-color: #64748b;"></span>
                                            <span><?= e($item) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section>
            <h3 class="text-sm font-bold text-ink-900 mb-3">Graduate Pages</h3>
            <div class="space-y-2">
                <?php foreach ($graduateSections as $title => $items): ?>
                    <details class="group rounded-lg border border-ink-200 bg-white">
                        <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-ink-900"><?= e($title) ?></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-ink-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="border-t border-ink-100 px-6 py-4" style="background-color: rgba(248, 250, 252, 0.7);">
                            <ul class="space-y-2 text-sm text-ink-600 leading-6">
                                <?php foreach ($items as $item): ?>
                                    <li class="flex gap-3">
                                        <span class="mt-2 rounded-full shrink-0" style="width: 6px; height: 6px; background-color: #64748b;"></span>
                                        <span><?= e($item) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-ink-900 mb-3">Common Functions</h3>
            <div class="space-y-2">
                <?php foreach ($commonSections as $title => $items): ?>
                    <details class="group rounded-lg border border-ink-200 bg-white">
                        <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-ink-900"><?= e($title) ?></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-ink-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="border-t border-ink-100 px-6 py-4" style="background-color: rgba(248, 250, 252, 0.7);">
                            <ul class="space-y-2 text-sm text-ink-600 leading-6">
                                <?php foreach ($items as $item): ?>
                                    <li class="flex gap-3">
                                        <span class="mt-2 rounded-full shrink-0" style="width: 6px; height: 6px; background-color: #64748b;"></span>
                                        <span><?= e($item) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</aside>
