<?php
/**
 * Render a survey question input for a single question.
 *
 * @var array $q        Question row (with 'options').
 * @var string $name    Field name, e.g. "answers[12]".
 * @var mixed $value    Existing/old answer value (string or array) or null.
 */
$name = $name ?? ('answers[' . (int) ($q['id'] ?? 0) . ']');
$value = $value ?? null;
$options = $q['options'] ?? [];
$valid = \App\Models\SurveyQuestion::decodeValidation($q['validation'] ?? null);
$required = !empty($q['is_required']);

$isChecked = static function ($k, $value) {
    if (is_array($value)) {
        $vals = array_map('strval', $value);
        return in_array((string) $k, $vals, true) ? 'checked' : '';
    }
    return $value !== null && (string) $value === (string) $k ? 'checked' : '';
};
$isSelected = static function ($k, $value) {
    return $value !== null && (string) $value === (string) $k ? 'selected' : '';
};
$textValue = static function ($value) {
    if (is_array($value)) {
        return $value[0] ?? '';
    }
    return (string) ($value ?? '');
};

switch ($q['type']) {
    case 'text':
        $attrs = '';
        if (isset($valid['max_length'])) {
            $attrs .= ' maxlength="' . (int) $valid['max_length'] . '"';
        }
        if (!empty($valid['email'])) {
            $attrs .= ' type="email" placeholder="you@example.com"';
        } else {
            $attrs .= ' type="text"';
        }
        echo '<input ' . $attrs . ' name="' . e($name) . '" id="q-' . (int) $q['id'] . '" class="input" ' . ($required ? 'required' : '') . ' value="' . e($textValue($value)) . '">';
        break;

    case 'long_text':
        echo '<textarea name="' . e($name) . '" id="q-' . (int) $q['id'] . '" rows="4" class="input" ' . ($required ? 'required' : '') . '>' . e($textValue($value)) . '</textarea>';
        break;

    case 'number':
        $attrs = '';
        if (isset($valid['min'])) {
            $attrs .= ' min="' . e((string) $valid['min']) . '"';
        }
        if (isset($valid['max'])) {
            $attrs .= ' max="' . e((string) $valid['max']) . '"';
        }
        echo '<input type="number" step="any"' . $attrs . ' name="' . e($name) . '" id="q-' . (int) $q['id'] . '" class="input" ' . ($required ? 'required' : '') . ' value="' . e($textValue($value)) . '">';
        break;

    case 'date':
        echo '<input type="date" name="' . e($name) . '" id="q-' . (int) $q['id'] . '" class="input" ' . ($required ? 'required' : '') . ' value="' . e($textValue($value)) . '">';
        break;

    case 'time':
        echo '<input type="time" name="' . e($name) . '" id="q-' . (int) $q['id'] . '" class="input" ' . ($required ? 'required' : '') . ' value="' . e($textValue($value)) . '">';
        break;

    case 'dropdown':
        echo '<select name="' . e($name) . '" id="q-' . (int) $q['id'] . '" class="input" ' . ($required ? 'required' : '') . '>';
        echo '<option value="">-- Select --</option>';
        foreach ($options as $o) {
            echo '<option value="' . e($o['option_value']) . '" ' . $isSelected($o['option_value'], $value) . '>' . e($o['option_text']) . '</option>';
        }
        echo '</select>';
        break;

    case 'multiple_choice':
        $minSel = (int) ($valid['min_selections'] ?? 0);
        $maxSel = $valid['max_selections'] ?? 0;
        echo '<div class="space-y-2" data-multiselect data-min="' . $minSel . '" data-max="' . (int) $maxSel . '">';
        foreach ($options as $o) {
            echo '<label class="flex items-start gap-2 text-sm text-ink-700 cursor-pointer">';
            echo '<input type="checkbox" name="' . e($name) . '[]" value="' . e($o['option_value']) . '" class="w-4 h-4 mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500" ' . $isChecked($o['option_value'], $value) . '>';
            echo '<span>' . e($o['option_text']) . '</span></label>';
        }
        echo '</div>';
        break;

    case 'yes_no':
        foreach ([['Yes', 'Yes'], ['No', 'No']] as [$label, $val]) {
            echo '<label class="inline-flex items-center gap-2 text-sm text-ink-700 cursor-pointer mr-4">';
            echo '<input type="radio" name="' . e($name) . '" value="' . e($val) . '" class="w-4 h-4 border-ink-300 text-brand-700 focus:ring-brand-500" ' . ($required ? 'required' : '') . ' ' . $isChecked($val, $value) . '>';
            echo '<span>' . e($label) . '</span></label>';
        }
        break;

    case 'likert':
        echo '<div class="flex flex-wrap items-stretch gap-1.5" data-likert>';
        foreach ($options as $o) {
            $active = $isChecked($o['option_value'], $value);
            echo '<label class="likert-chip flex-1 min-w-[90px] text-center cursor-pointer rounded-lg border border-ink-200 px-2 py-2.5 hover:border-brand-400 hover:bg-brand-50 transition-colors">';
            echo '<input type="radio" name="' . e($name) . '" value="' . e($o['option_value']) . '" class="sr-only" ' . ($required ? 'required' : '') . ' ' . $active . '>';
            echo '<span class="text-xs font-medium text-ink-700 block">' . e($o['option_text']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        break;

    case 'linear_scale':
        $lo = (int) ($valid['scale_min'] ?? 0);
        $hi = (int) ($valid['scale_max'] ?? 10);
        if ($lo > $hi) {
            [$lo, $hi] = [$hi, $lo];
        }
        $minLabel = e((string) ($valid['min_label'] ?? ''));
        $maxLabel = e((string) ($valid['max_label'] ?? ''));
        echo '<div class="space-y-2" data-scale>';
        echo '<div class="flex flex-wrap items-stretch gap-1">';
        for ($i = $lo; $i <= $hi; $i++) {
            $active = $value !== null && (string) $value === (string) $i ? 'active' : '';
            echo '<label class="scale-chip flex-1 min-w-[42px] text-center cursor-pointer rounded-lg border border-ink-200 px-2 py-2 hover:border-brand-400 hover:bg-brand-50 transition-colors">';
            echo '<input type="radio" name="' . e($name) . '" value="' . $i . '" class="sr-only" ' . ($required ? 'required' : '') . ' ' . ($value !== null && (string) $value === (string) $i ? 'checked' : '') . '>';
            echo '<span class="text-sm font-semibold text-ink-700 block">' . $i . '</span>';
            echo '</label>';
        }
        echo '</div>';
        if ($minLabel || $maxLabel) {
            echo '<div class="flex justify-between text-xs text-ink-500"><span>' . $minLabel . '</span><span>' . $maxLabel . '</span></div>';
        }
        echo '</div>';
        break;

    case 'rating':
        $stars = max(1, (int) ($valid['stars'] ?? $q['likert_scale'] ?? 5));
        $current = $value !== null ? (int) $value : 0;
        echo '<div class="flex items-center gap-1" data-rating data-stars="' . $stars . '" data-current="' . $current . '">';
        for ($i = 1; $i <= $stars; $i++) {
            $active = $current >= $i;
            echo '<button type="button" data-star="' . $i . '" class="star-btn w-10 h-10 rounded-lg border border-ink-200 flex items-center justify-center ' . ($active ? 'active' : '') . '" title="' . $i . ' / ' . $stars . '">';
            echo '<i data-lucide="star" class="w-5 h-5"></i>';
            echo '</button>';
        }
        echo '<input type="hidden" name="' . e($name) . '" value="' . ($current > 0 ? $current : '') . '" ' . ($required ? 'required' : '') . '>';
        echo '</div>';
        break;

    default: // single_choice
        echo '<div class="space-y-2" data-single>';
        foreach ($options as $o) {
            echo '<label class="flex items-start gap-2 text-sm text-ink-700 cursor-pointer">';
            echo '<input type="radio" name="' . e($name) . '" value="' . e($o['option_value']) . '" class="w-4 h-4 mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500" ' . ($required ? 'required' : '') . ' ' . $isChecked($o['option_value'], $value) . '>';
            echo '<span>' . e($o['option_text']) . '</span></label>';
        }
        echo '</div>';
        break;
}
