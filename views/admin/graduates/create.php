<?= \App\Core\View::partial('admin/graduates/_form', [
    'graduate' => null,
    'programs' => $programs ?? [],
    'batches'  => $batches ?? [],
    'action'   => url('admin/graduates'),
    'submit'   => 'Add Graduate',
]) ?>
