<?php
/** @var array $survey @var array $sections @var array $answers @var array $response */
$sections = $sections ?? [];
$answers = $answers ?? [];

$formatAnswer = static function (array $q, array $answers): string {
    $qid = (int) $q['id'];
    if (!isset($answers[$qid])) {
        return '—';
    }
    $row = $answers[$qid];
    $options = $q['options'] ?? [];
    $optionTexts = [];
    foreach ($options as $o) {
        $optionTexts[(string) $o['option_value']] = $o['option_text'];
    }

    if ($q['type'] === 'multiple_choice') {
        $decoded = json_decode((string) ($row['answer_options'] ?? '[]'), true);
        if (!is_array($decoded)) {
            return '—';
        }
        $labels = array_map(static fn ($v) => $optionTexts[(string) $v] ?? (string) $v, $decoded);
        return implode(', ', $labels);
    }
    $value = (string) ($row['answer_value'] ?? '');
    if (in_array($q['type'], ['single_choice', 'dropdown', 'likert', 'yes_no'], true)) {
        return $optionTexts[$value] ?? $value;
    }
    return $value !== '' ? $value : '—';
};
?>
<div class="space-y-6 max-w-3xl">

    <div class="card">
        <div class="card-body flex flex-wrap items-center gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-bold text-ink-900"><?= e($survey['title']) ?></h2>
                <p class="text-sm text-ink-500">Your submitted response</p>
            </div>
            <div class="text-sm text-ink-500">
                <span class="badge-green"><i data-lucide="check-circle" class="w-3 h-3"></i> Submitted</span>
                <p class="text-xs mt-1"><?= e($response['submitted_at'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-ink-800"><?= e($section['title']) ?></h3>
            </div>
            <div class="card-body space-y-4">
                <?php foreach ($section['questions'] as $q): ?>
                    <div>
                        <p class="text-sm font-medium text-ink-800"><?= e($q['question_text']) ?></p>
                        <p class="text-sm text-ink-600 mt-0.5"><?= nl2br(e($formatAnswer($q, $answers))) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($section['questions'])): ?>
                    <p class="text-sm text-ink-500">No questions in this section.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="flex">
        <a href="<?= url('graduate/survey') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Surveys</a>
    </div>
</div>
