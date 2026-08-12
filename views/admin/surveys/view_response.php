<?php
/**
 * Admin response detail: show every answer mapped back to its label.
 * @var array $survey @var array $sections @var array $answers @var array $response
 */
$sections = $sections ?? [];
$answers = $answers ?? [];
$response = $response ?? [];
$base = 'admin/surveys/' . (int) $survey['id'];

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
    if (in_array($q['type'], ['single_choice', 'dropdown', 'likert', 'yes_no'], true)) {
        return $texts[$value] ?? $value;
    }
    return $value !== '' ? $value : '—';
};
?>
<div class="space-y-5">
    <div class="card">
        <div class="card-header">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-ink-900">Response #<?= (int) $response['id'] ?> &middot; <?= e($survey['title']) ?></h2>
                <p class="text-xs text-ink-400 mt-1">Submitted <?= e($response['submitted_at'] ?? '') ?></p>
            </div>
            <a href="<?= url($base . '/responses') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-ink-800"><?= e($section['title']) ?></h3>
                <?php if (!empty($section['description'])): ?>
                    <p class="text-xs text-ink-500 mt-0.5"><?= e($section['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-body space-y-4">
                <?php foreach ($section['questions'] as $q): ?>
                    <div>
                        <p class="text-sm font-medium text-ink-800"><?= e($q['question_text']) ?></p>
                        <p class="text-sm text-ink-600 mt-0.5 whitespace-pre-line"><?= nl2br(e($formatAnswer($q))) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($section['questions'])): ?>
                    <p class="text-sm text-ink-500">No questions in this section.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>