<div class="max-w-2xl mx-auto">
    <div class="card p-10 text-center">
        <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="calendar-x" class="w-7 h-7"></i>
        </div>
        <h2 class="text-lg font-bold text-ink-900 mb-1"><?= e($survey['title']) ?> is now closed</h2>
        <p class="text-sm text-ink-500 max-w-md mx-auto mb-5">
            This survey is no longer accepting responses. Thank you for your participation in the tracer study.
        </p>
        <a href="<?= url('graduate/survey') ?>" class="btn-primary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Surveys</a>
    </div>
</div>
