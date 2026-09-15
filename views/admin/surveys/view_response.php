<?php
/**
 * Admin response detail: show every answer mapped back to its label.
 * @var array $survey @var array $sections @var array $answers @var array $response @var array|null $respondent
 */
$sections = $sections ?? [];
$answers = $answers ?? [];
$response = $response ?? [];
$respondent = $respondent ?? null;
$base = 'admin/surveys/' . (int) $survey['id'];

$respondentName = 'Unknown respondent';
if ($respondent) {
    $nameParts = array_filter([
        $respondent['first_name'] ?? '',
        $respondent['middle_name'] ?? '',
        $respondent['last_name'] ?? '',
        $respondent['suffix'] ?? '',
    ], static fn ($part) => trim((string) $part) !== '');
    $respondentName = trim(implode(' ', $nameParts)) ?: 'Unknown respondent';
}
$respondentInitial = strtoupper(mb_substr($respondentName, 0, 1));

$optionTexts = static function (array $q): array {
    $map = [];
    foreach ($q['options'] ?? [] as $o) {
        $map[(string) $o['option_value']] = $o['option_text'];
    }
    return $map;
};
$formatAnswer = static function (array $q) use ($answers, $optionTexts): string {
    $qid = (int) $q['id'];
    if (!isset($answers[$qid])) {
        return '—';
    }
    $row = $answers[$qid];
    $texts = $optionTexts($q);

    if ($q['type'] === 'multiple_choice') {
        $decoded = json_decode((string) ($row['answer_options'] ?? '[]'), true);
        if (!is_array($decoded)) {
            return '—';
        }
        $labels = array_map(static fn ($v) => $texts[(string) $v] ?? (string) $v, $decoded);
        return implode(', ', $labels);
    }
    $value = (string) ($row['answer_value'] ?? '');
    if ($q['type'] === 'rating') {
        $n = (int) $value;
        $valid = \App\Models\SurveyQuestion::decodeValidation($q['validation'] ?? null);
        $max = max(1, (int) ($valid['stars'] ?? $q['likert_scale'] ?? 5));
        $n = max(0, min($n, $max));
        return str_repeat('★', $n) . str_repeat('☆', $max - $n);
    }
    if ($q['type'] === 'image_upload') {
        return $value !== '' ? $value : '—';
    }
    if (in_array($q['type'], ['single_choice', 'dropdown', 'likert', 'yes_no'], true)) {
        return $texts[$value] ?? $value;
    }
    return $value !== '' ? $value : '—';
};
?>
<div class="space-y-6">
    <div class="card overflow-hidden">
        <div class="card-body">
            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs text-ink-500 mb-2">
                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 font-semibold text-brand-700">Response #<?= (int) $response['id'] ?></span>
                        <span><?= e($survey['title']) ?></span>
                    </div>
                    <h2 class="text-2xl font-bold text-ink-950 tracking-tight"><?= e($respondentName) ?></h2>
                    <p class="text-sm text-ink-500 mt-1">Submitted <?= e($response['submitted_at'] ?? '') ?></p>
                </div>
                <a href="<?= url($base . '/responses') ?>" class="btn-secondary" style="width:max-content"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-6">
                <div class="rounded-lg border border-ink-200 bg-ink-50 p-4 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold">
                            <?= e($respondentInitial) ?>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Filled up by</p>
                            <p class="text-sm font-semibold text-ink-900 truncate"><?= e($respondentName) ?></p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-ink-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Student Number</p>
                    <p class="text-sm font-semibold text-ink-900 mt-1"><?= e($respondent['student_number'] ?? '—') ?></p>
                </div>
                <div class="rounded-lg border border-ink-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Program / Batch</p>
                    <p class="text-sm font-semibold text-ink-900 mt-1">
                        <?= e($respondent['program_code'] ?? '—') ?>
                        <?= !empty($respondent['batch_year']) ? ' · ' . e($respondent['batch_year']) : '' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-ink-100 bg-ink-50/70">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <h3 class="text-base font-bold text-ink-900"><?= e($section['title']) ?></h3>
                    <?php if (!empty($section['description'])): ?>
                        <p class="text-xs text-ink-500 md:text-right"><?= e($section['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-3">
                <?php foreach ($section['questions'] as $q): ?>
                    <div class="rounded-lg border border-ink-200 bg-white p-4 shadow-sm <?= $q['type'] === 'image_upload' ? 'lg:col-span-2 2xl:col-span-1' : '' ?>">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500 leading-snug"><?= e($q['question_text']) ?></p>
                        <?php if ($q['type'] === 'image_upload' && $formatAnswer($q) !== '—'): ?>
                            <?php $proofUrl = url($base . '/responses/' . (int) $response['id'] . '/proofs/' . (int) $q['id']); ?>
                            <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
                                <a href="<?= e($proofUrl) ?>" target="_blank" class="block rounded-lg border border-ink-200 bg-ink-50 overflow-hidden shrink-0" style="width: 144px; height: 108px;">
                                    <img src="<?= e($proofUrl) ?>" alt="Uploaded proof" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                </a>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Uploaded image</p>
                                    <p class="text-sm font-medium text-ink-900 mt-1">Proof of employment attached</p>
                                    <a href="<?= e($proofUrl) ?>" target="_blank" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline mt-2">
                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                        Open full image
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="<?= $q['type'] === 'rating' ? 'text-2xl leading-none text-amber-500 mt-2 whitespace-pre-line' : 'text-sm font-medium text-ink-900 mt-2 whitespace-pre-line leading-relaxed' ?>"><?= nl2br(e($formatAnswer($q))) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php if (empty($section['questions'])): ?>
                    <p class="text-sm text-ink-500">No questions in this section.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
