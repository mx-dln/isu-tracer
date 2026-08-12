<?php
/**
 * Google-Forms-style survey builder.
 * @var array $survey @var array $sections @var array $questions @var int $responseCount @var array $invitationStats
 */
$survey = $survey ?? [];
$sections = $sections ?? [];
$questions = $questions ?? [];
$responseCount = $responseCount ?? 0;
$invitationStats = $invitationStats ?? [];
$questionCount = count($questions);
?>
<div class="space-y-5" data-builder>

    <?= \App\Core\View::partial('admin/surveys/_workspace-header', [
        'survey'          => $survey,
        'activeTab'       => 'builder',
        'sectionCount'    => (int) ($sectionCount ?? count($sections)),
        'questionCount'   => $questionCount,
        'responseCount'   => $responseCount,
        'invitationStats' => $invitationStats,
    ]) ?>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn-primary" data-new-question><i data-lucide="plus" class="w-4 h-4"></i> Add question</button>
        <details class="relative">
            <summary class="btn-secondary cursor-pointer list-none"><i data-lucide="columns-2" class="w-4 h-4"></i> Add section</summary>
            <div class="card absolute left-0 z-20 mt-2 w-80">
                <form method="POST" action="<?= url('admin/surveys/sections') ?>" class="p-4 space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="survey_id" value="<?= (int) $survey['id'] ?>">
                    <div>
                        <label class="label" for="sec-title">Section title <span class="text-red-600">*</span></label>
                        <input type="text" name="title" id="sec-title" class="input" required maxlength="191" placeholder="e.g. Employment Profile">
                    </div>
                    <div>
                        <label class="label" for="sec-desc">Description</label>
                        <textarea name="description" id="sec-desc" rows="2" class="input"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Add section</button>
                    </div>
                </form>
            </div>
        </details>
    </div>

    <!-- Sections -->
    <?php if (empty($sections)): ?>
        <div class="card p-12 text-center">
            <i data-lucide="columns-2" class="w-10 h-10 mx-auto text-ink-300 mb-3"></i>
            <p class="text-sm text-ink-500">This survey has no sections yet. Add a section to group your questions, or just add questions directly.</p>
        </div>
    <?php endif; ?>

    <div id="section-list" class="space-y-4">
    <?php foreach ($sections as $section): ?>
        <section class="builder-section card overflow-visible" data-section-id="<?= (int) $section['id'] ?>">
            <div class="flex items-center gap-3 px-5 py-3 border-b border-ink-100">
                <div class="section-drag-handle drag-handle cursor-grab text-ink-300 hover:text-ink-500 hover:bg-ink-50 rounded-md px-1 py-2 -ml-1 self-stretch flex items-center" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <form method="POST" action="<?= url('admin/surveys/sections/' . (int) $section['id']) ?>" class="flex flex-wrap items-center gap-2" data-section-form>
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="PUT">
                        <input type="text" name="title" class="input !font-semibold !text-ink-900 flex-1 min-w-[180px]" value="<?= e($section['title']) ?>" maxlength="191">
                        <input type="text" name="description" class="input flex-1 min-w-[200px]" value="<?= e($section['description'] ?? '') ?>" placeholder="Section description (optional)">
                        <button type="submit" class="btn-secondary" title="Save section"><i data-lucide="save" class="w-4 h-4"></i></button>
                    </form>
                </div>
                <button type="button" class="btn-secondary shrink-0" data-add-question-in="<?= (int) $section['id'] ?>"><i data-lucide="plus" class="w-4 h-4"></i> Add question</button>
                <form method="POST" action="<?= url('admin/surveys/sections/' . (int) $section['id']) ?>" onsubmit="return confirmAction('Delete this section and all of its questions?', this);" class="shrink-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete section"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
            </div>
            <div class="p-5" data-question-list data-section="<?= (int) $section['id'] ?>">
                <?php if (empty($section['questions'])): ?>
                    <p class="text-sm text-ink-400 text-center py-6">No questions in this section.</p>
                <?php endif; ?>
                <?php foreach ($section['questions'] as $q): ?>
                    <?= \App\Core\View::partial('admin/surveys/_builder_question', [
                        'q' => $q,
                        'surveyId' => (int) $survey['id'],
                        'sections' => $sections,
                    ]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>

    <!-- Add-question anchor -->
    <div id="new-question-anchor" style="scroll-margin-top:6rem">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg font-semibold text-ink-800">New Question</span>
        </div>
        <?= \App\Core\View::partial('admin/surveys/_builder_question', [
            'q' => null,
            'surveyId' => (int) $survey['id'],
            'sections' => $sections,
        ]) ?>
    </div>
</div>

<style>
    /* Only the dedicated handles are draggable — cards themselves are NOT. */
    .drag-handle { cursor: grab; touch-action: none; }
    .drag-handle:active { cursor: grabbing; }
    .drag-handle:hover { background-color: #f8fafc; }
    .builder-section.dragging, .builder-question.dragging { opacity: .4; }
    .drop-target { outline: 2px dashed #4338ca; outline-offset: -2px; border-radius: .75rem; }
    .drop-placeholder { height: 56px; }
</style>

<script>
    window.SURVEY_BUILDER = {
        sectionsReorderUrl: '<?= url('admin/surveys/sections/reorder') ?>',
        questionsReorderUrl: '<?= url('admin/surveys/questions/reorder') ?>',
        surveyId: <?= (int) $survey['id'] ?>,
        csrf: '<?= e(csrf_token()) ?>',
    };
</script>
<script src="<?= asset('js/admin-surveys.js') ?>"></script>