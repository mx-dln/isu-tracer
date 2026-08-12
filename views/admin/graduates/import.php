<div class="space-y-6 max-w-3xl">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800">Import Graduates from CSV</h3>
                <p class="text-xs text-ink-500">Bulk-add graduate records using a comma-separated values file.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= url('admin/graduates/import') ?>" enctype="multipart/form-data" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label class="label" for="csv_file">CSV File <span class="text-red-600">*</span></label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required class="input">
                    <p class="text-xs text-ink-500 mt-1">Maximum upload size is set by the server (php.ini).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= url('admin/graduates/import/template') ?>" class="btn-secondary">
                        <i data-lucide="file-down" class="w-4 h-4"></i> Download Template
                    </a>
                    <button type="submit" class="btn-primary"><i data-lucide="upload" class="w-4 h-4"></i> Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-ink-800">File Format</h3></div>
        <div class="card-body text-sm text-ink-600 space-y-2">
            <p>The CSV must include a header row with these columns:</p>
            <div class="table-wrap rounded-lg border border-ink-200">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Required</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cols = [
                            'student_number' => ['Yes', 'Unique student ID number'],
                            'first_name'     => ['Yes', ''],
                            'middle_name'    => ['No', ''],
                            'last_name'      => ['Yes', ''],
                            'suffix'         => ['No', 'e.g. Jr., III'],
                            'email'          => ['No', 'Optional contact email'],
                            'contact_number' => ['No', ''],
                            'sex'            => ['No', 'Male / Female'],
                            'program_code'   => ['Yes', 'Must match an existing program code'],
                            'batch_year'     => ['Yes', 'Must match an existing batch year'],
                        ];
                        ?>
                        <?php foreach ($cols as $col => [$req, $note]): ?>
                            <tr>
                                <td class="font-mono"><?= e($col) ?></td>
                                <td><?= $req === 'Yes' ? '<span class="badge-red">Yes</span>' : '<span class="badge-gray">No</span>' ?></td>
                                <td><?= e($note) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p>Rows with a student number that already exists in the system are skipped.</p>
        </div>
    </div>

    <div class="flex">
        <a href="<?= url('admin/graduates') ?>" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Graduates</a>
    </div>
</div>
