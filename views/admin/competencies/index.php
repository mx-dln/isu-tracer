<?php
/** @var array $categories @var array $competencies @var array $allCategories @var array $filters @var string $searchCat */
$categories = $categories ?? [];
$competencies = $competencies ?? [];
$allCategories = $allCategories ?? [];
$filters = $filters ?? [];
$searchCat = $searchCat ?? '';
?>
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-ink-900">Competencies</h2>
            <p class="text-sm text-ink-500">Competency framework &amp; self-assessment results</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/competencies/analysis') ?>" class="btn-secondary">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analysis
            </a>
        </div>
    </div>

    <!-- Add category -->
    <form method="POST" action="<?= url('admin/competencies/categories') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <?= csrf_field() ?>
        <div class="min-w-[240px] flex-1">
            <label class="label" for="new-cat-name">Competency Category</label>
            <input type="text" name="name" id="new-cat-name" class="input" placeholder="e.g. Agricultural Production" required>
        </div>
        <div class="min-w-[160px]">
            <label class="label" for="new-cat-sort">Sort Order</label>
            <input type="number" name="sort_order" id="new-cat-sort" class="input" value="0">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-ink-700 pb-1">
            <input type="checkbox" name="is_active" value="1" class="rounded" checked> Active
        </label>
        <button type="submit" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Category</button>
    </form>

    <!-- Add competency -->
    <form method="POST" action="<?= url('admin/competencies') ?>" class="card p-4 flex flex-wrap items-end gap-3">
        <?= csrf_field() ?>
        <div>
            <label class="label" for="new-cat">Category *</label>
            <select name="category_id" id="new-cat" class="input !w-auto" required>
                <option value="">— Select —</option>
                <?php foreach ($allCategories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ($filters['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="min-w-[240px] flex-1">
            <label class="label" for="new-name">Competency Name</label>
            <input type="text" name="name" id="new-name" class="input" placeholder="e.g. Crop Production and Management" required>
        </div>
        <div class="min-w-[160px]">
            <label class="label" for="new-sort">Sort Order</label>
            <input type="number" name="sort_order" id="new-sort" class="input" value="0">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-ink-700 pb-1">
            <input type="checkbox" name="is_active" value="1" class="rounded" checked> Active
        </label>
        <button type="submit" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Add Competency</button>
    </form>

    <!-- Categories table -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Categories</h3>
            <form method="GET" action="<?= url('admin/competencies') ?>" class="flex items-center gap-2">
                <input type="search" name="search_cat" class="input !w-56 !py-1.5" placeholder="Search category" value="<?= e($searchCat) ?>">
                <button type="submit" class="btn-secondary !py-1.5">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Competencies</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No categories found.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories['items'] as $c): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($c['name']) ?></td>
                        <td><?= number_format((int) $c['competency_count']) ?></td>
                        <td><?= (int) $c['sort_order'] ?></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="<?= url('admin/competencies/categories/' . (int) $c['id']) ?>" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="text" name="name" value="<?= e($c['name']) ?>" class="input !w-auto !py-1.5" required>
                                    <input type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" class="input !w-20 !py-1.5">
                                    <label class="inline-flex items-center gap-1 text-xs text-ink-600">
                                        <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?> class="rounded"> Active
                                    </label>
                                    <button type="submit" class="icon-btn" title="Save"><i data-lucide="save" class="w-4 h-4"></i></button>
                                </form>
                                <form method="POST" action="<?= url('admin/competencies/categories/' . (int) $c['id']) ?>" onsubmit="return confirmAction('Remove category <?= e(addslashes($c['name'])) ?>? Its competencies will be removed too.', this);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $categories,
            'path'      => 'admin/competencies',
            'query'     => ['search_cat' => $searchCat],
        ]) ?>
    </div>

    <!-- Competencies table -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-ink-800">Competencies</h3>
            <form method="GET" action="<?= url('admin/competencies') ?>" class="flex flex-wrap items-center gap-2">
                <select name="category_id" class="input !w-auto !py-1.5">
                    <option value="">All categories</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($filters['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="search" class="input !w-56 !py-1.5" placeholder="Search competency" value="<?= e($filters['search'] ?? '') ?>">
                <button type="submit" class="btn-secondary !py-1.5">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($competencies['items'])): ?>
                    <tr><td colspan="5" class="!text-center !py-8 text-ink-500">No competencies found.</td></tr>
                <?php endif; ?>
                <?php foreach ($competencies['items'] as $c): ?>
                    <tr>
                        <td class="font-medium text-ink-800"><?= e($c['name']) ?></td>
                        <td><?= e($c['category_name']) ?></td>
                        <td><?= (int) $c['sort_order'] ?></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="<?= url('admin/competencies/' . (int) $c['id']) ?>" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="PUT">
                                    <select name="category_id" class="input !w-auto !py-1.5">
                                        <?php foreach ($allCategories as $cat): ?>
                                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $c['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="name" value="<?= e($c['name']) ?>" class="input !w-56 !py-1.5" required>
                                    <input type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" class="input !w-20 !py-1.5">
                                    <label class="inline-flex items-center gap-1 text-xs text-ink-600">
                                        <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?> class="rounded"> Active
                                    </label>
                                    <button type="submit" class="icon-btn" title="Save"><i data-lucide="save" class="w-4 h-4"></i></button>
                                </form>
                                <form method="POST" action="<?= url('admin/competencies/' . (int) $c['id']) ?>" onsubmit="return confirmAction('Remove competency <?= e(addslashes($c['name'])) ?>?', this);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="icon-btn !text-red-600 hover:!bg-red-50" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Core\View::partial('partials/pagination', [
            'paginator' => $competencies,
            'path'      => 'admin/competencies',
            'query'     => array_filter($filters, static fn ($v) => $v !== null && $v !== ''),
        ]) ?>
    </div>
</div>
