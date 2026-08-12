<?php
/** @var array $competencies @var int $total @var int $completed */
$competencies = $competencies ?? [];
$total = $total ?? 0;
$completed = $completed ?? 0;
$groups = [];
foreach ($competencies as $c) {
    $groups[$c['category_name']][] = $c;
}
$progress = $total > 0 ? (int) round($completed / $total * 100) : 0;
?>
<div class="space-y-6">

    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 h-3 bg-ink-100 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-600 rounded-full transition-all duration-700" style="width: <?= $progress ?>%"></div>
                </div>
                <span class="text-sm font-semibold text-ink-700"><?= $completed ?>/<?= $total ?> rated (<?= $progress ?>%)</span>
            </div>
            <p class="text-sm text-ink-500">Rate how strongly the IAT program developed each competency: <strong>1 = very low, 5 = very high</strong>.</p>
        </div>
    </div>

    <form method="POST" action="<?= url('graduate/competencies') ?>">
        <?= csrf_field() ?>
        <?php $idx = 0; foreach ($groups as $category => $items): ?>
            <div class="card mb-6">
                <div class="card-header"><h3 class="font-semibold text-ink-800"><?= e($category) ?></h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Competency</th>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <th class="text-center w-16"><?= $i ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $c): ?>
                            <tr>
                                <td>
                                    <p class="font-medium text-ink-800"><?= e($c['name']) ?></p>
                                    <?php if ($c['description']): ?>
                                        <p class="text-xs text-ink-500"><?= e($c['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <td class="text-center">
                                        <label class="inline-flex items-center justify-center">
                                            <input type="radio" name="ratings[<?= (int) $c['id'] ?>]" value="<?= $i ?>"
                                                   <?= (int) ($c['rating'] ?? 0) === $i ? 'checked' : '' ?>
                                                   class="w-4 h-4 accent-brand-600">
                                        </label>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($competencies)): ?>
            <div class="card">
                <div class="card-body text-center py-10 text-ink-500">
                    <i data-lucide="award" class="w-10 h-10 mx-auto mb-2 text-ink-300"></i>
                    <p>No competencies are currently active.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Self-Assessment</button>
        </div>
    </form>
</div>
