<?php
/** @var array $questions @var array $feedback @var bool $submitted */
$questions = $questions ?? [];
$feedback = $feedback ?? [];
$submitted = $submitted ?? false;
?>
<div class="space-y-6">

    <?php if ($submitted): ?>
        <div class="alert-box flex items-start gap-2 rounded-lg border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-800">
            <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <div class="flex-1">
                <p>You have already submitted curriculum feedback. You may update your ratings below and save again.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p class="text-sm text-ink-600">Rate each aspect of the IAT curriculum on its relevance to your employment: <strong>1 = strongly disagree, 5 = strongly agree</strong>. Add an optional comment for each item.</p>
        </div>
    </div>

    <form method="POST" action="<?= url('graduate/curriculum-feedback') ?>">
        <?= csrf_field() ?>
        <?php foreach ($questions as $i => $q): ?>
            <?php $qid = (int) $q['id']; $existing = $feedback[$qid] ?? null; ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-[240px]">
                            <p class="font-semibold text-ink-800"><?= $i + 1 ?>. <?= e($q['question_text']) ?></p>
                            <?php if ($q['description']): ?>
                                <p class="text-xs text-ink-500 mt-0.5"><?= e($q['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                <label class="flex flex-col items-center gap-1 text-xs text-ink-500">
                                    <input type="radio" name="ratings[<?= $qid ?>]" value="<?= $r ?>"
                                           <?= (int) ($existing['rating'] ?? 0) === $r ? 'checked' : '' ?>
                                           class="w-4 h-4 accent-brand-600">
                                    <span><?= $r ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="label !mb-1" for="comment-<?= $qid ?>">Comment (optional)</label>
                        <textarea name="comments[<?= $qid ?>]" id="comment-<?= $qid ?>" rows="2" class="input"><?= e($existing['comment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($questions)): ?>
            <div class="card">
                <div class="card-body text-center py-10 text-ink-500">
                    <i data-lucide="book-open-check" class="w-10 h-10 mx-auto mb-2 text-ink-300"></i>
                    <p>No curriculum feedback questions are currently active.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Submit Feedback</button>
        </div>
    </form>
</div>
