<?php
/** @var array $survey @var array $responses @var int $responseCount @var array $insights */
$survey = $survey ?? [];
$responses = $responses ?? ['items' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
$responseCount = $responseCount ?? 0;
$insights = $insights ?? [];
$base = 'admin/surveys/' . (int) $survey['id'];
?>
<div class="space-y-5">
    <?= \App\Core\View::partial('admin/surveys/_workspace-header', [
        'survey'          => $survey,
        'activeTab'       => 'responses',
        'sectionCount'    => (int) ($sectionCount ?? 0),
        'questionCount'   => (int) ($questionCount ?? 0),
        'responseCount'   => $responseCount,
        'invitationStats' => $invitationStats ?? [],
        'insightsButton'  => !empty($insights),
    ]) ?>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Submitted At</th>
                        <th>Source</th>
                        <th>IP Address</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($responses['items'])): ?>
                    <tr><td colspan="7" class="!text-center !py-10 text-ink-500">No responses submitted yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($responses['items'] as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= url($base . '/responses/' . (int) $r['id']) ?>" class="font-medium text-brand-700 hover:underline">
                                <?= e($r['last_name']) ?>, <?= e($r['first_name']) ?>
                            </a>
                            <p class="text-xs text-ink-400"><?= e($r['student_number']) ?></p>
                        </td>
                        <td><?= e($r['program_code']) ?></td>
                        <td><?= (int) $r['batch_year'] ?></td>
                        <td><?= e($r['submitted_at']) ?></td>
                        <td><?= $r['invitation_id'] ? '<span class="badge-purple">Invitation</span>' : '<span class="badge-gray">Portal</span>' ?></td>
                        <td class="text-xs text-ink-500"><?= e($r['ip_address']) ?></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url($base . '/responses/' . (int) $r['id']) ?>" class="icon-btn" title="View response" aria-label="View response"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $responses,
            'path'      => $base . '/responses',
            'query'     => [],
        ]) ?>
    </div>
</div>

<!-- Survey Insights modal -->
<div id="insights-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-ink-900/40" data-close-insights></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:m-auto sm:max-w-4xl sm:h-fit sm:max-h-[85vh] overflow-y-auto bg-white sm:rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100 sticky top-0 bg-white">
            <div>
                <h3 class="font-bold text-ink-900">Survey Insights</h3>
                <p class="text-xs text-ink-500 mt-0.5">Answer breakdowns keyed by each question's analytics key.</p>
            </div>
            <button type="button" class="icon-btn" data-close-insights aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($insights as $q): ?>
                <?php if ((int) $q['response_count'] === 0) continue; ?>
                <div class="rounded-lg border border-ink-100 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink-900 leading-snug"><?= e($q['text']) ?></p>
                            <?php if (!empty($q['key'])): ?>
                                <p class="text-[11px] font-mono text-ink-400 mt-0.5"><?= e($q['key']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="badge-gray"><?= e(\App\Models\SurveyQuestion::typeLabel($q['type'])) ?></span>
                            <p class="text-[11px] text-ink-500 mt-1"><?= number_format((int) $q['response_count']) ?> response<?= $q['response_count'] === 1 ? '' : 's' ?></p>
                        </div>
                    </div>

                    <?php if ($q['average'] !== null): ?>
                        <p class="text-xs text-ink-600 mt-3">
                            <span class="font-semibold text-ink-900 text-base"><?= number_format((float) $q['average'], 2) ?></span>
                            average
                        </p>
                    <?php endif; ?>

                    <?php if ($q['breakdown']): ?>
                        <div class="mt-3 space-y-2">
                            <?php foreach ($q['breakdown'] as $b): ?>
                                <div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-ink-700 truncate"><?= e($b['label']) ?></span>
                                        <span class="text-ink-500 shrink-0"><?= (int) $b['count'] ?> &middot; <?= (float) $b['percentage'] ?>%</span>
                                    </div>
                                    <div class="h-1.5 bg-ink-100 rounded-full overflow-hidden mt-1">
                                        <div class="h-full bg-brand-700 rounded-full" style="width:<?= min(100, (float) $b['percentage']) ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($q['average'] === null): ?>
                        <p class="text-xs text-ink-500 mt-3">Free-text answers — <?= number_format((int) $q['response_count']) ?> recorded.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('insights-modal');
        if (!modal) return;
        function openModal() { modal.classList.remove('hidden'); }
        function closeModal() { modal.classList.add('hidden'); }
        document.querySelectorAll('[data-open-insights]').forEach(function (b) { b.addEventListener('click', openModal); });
        document.querySelectorAll('[data-close-insights]').forEach(function (el) { el.addEventListener('click', closeModal); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    });
</script>