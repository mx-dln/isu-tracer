<?= \App\Core\View::partial('admin/graduates/_form', [
    'graduate' => $graduate ?? null,
    'programs' => $programs ?? [],
    'batches'  => $batches ?? [],
    'action'   => url('admin/graduates/' . (int) ($graduate['id'] ?? 0)),
    'submit'   => 'Save Changes',
]) ?>
