<?php
/** @var array $competencies @var int $total @var int $completed @var bool $isUnemployed @var string $employmentStatus */
$competencies = $competencies ?? [];
$total = $total ?? 0;
$completed = $completed ?? 0;
$isUnemployed = $isUnemployed ?? false;
$employmentStatus = $employmentStatus ?? 'unemployed';

$groups = [];
foreach ($competencies as $c) {
    $groups[$c['category_name']][] = $c;
}
$progress = $total > 0 ? (int) round($completed / $total * 100) : 0;
?>
<div class="space-y-6">

    <?php if ($isUnemployed): ?>
        <div class="card border border-amber-200 bg-amber-50/40">
            <div class="card-body p-8 sm:p-10 text-center max-w-xl mx-auto">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <span class="badge-amber mb-3">Exempted / Not Required</span>
                <h2 class="text-xl font-bold text-ink-900 mb-2">Competency Questions Are Hidden</h2>
                <p class="text-ink-600 text-sm leading-relaxed mb-6">
                    Dahil ang iyong kasalukuyang employment status ay nakatakda bilang <strong class="text-ink-900 font-semibold uppercase">Unemployed</strong>, hindi kinakailangang sagutan ang competency assessment ukol sa workplace skills.
                </p>
                <div class="p-4 bg-white rounded-xl border border-amber-200/70 text-xs text-ink-600 mb-6 text-left space-y-1.5 shadow-sm">
                    <p class="font-semibold text-ink-800 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-amber-600"></i> Bakit walang lumalabas na tanong?
                    </p>
                    <p>
                        Ang competency evaluation ay sumusukat sa paggamit at pagiging angkop ng mga natutunang kasanayan sa kasalukuyang trabaho. Awtomatikong kinikilala ng system bilang <em>Exempted</em> ang iyong profile upang makumpleto ang tracer study requirements nang hindi naaantala.
                    </p>
                </div>
                <div class="pt-2 border-t border-amber-200/60 flex flex-col sm:flex-row items-center justify-center gap-3 text-xs text-ink-500">
                    <span>Nagbago na ba ang iyong estado o may trabaho na?</span>
                    <a href="<?= url('graduate/employment') ?>" class="btn-primary !py-1.5 !px-3.5 !text-xs inline-flex items-center gap-1.5">
                        <i data-lucide="briefcase" class="w-3.5 h-3.5"></i> Update Employment
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>

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

    <?php endif; ?>
</div>
