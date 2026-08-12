<?php
/** @var array|null $graduate @var array $programs @var array $batches @var string $action @var string $submit */
$graduate = $graduate ?? null;
$programs = $programs ?? [];
$batches = $batches ?? [];
$action = $action ?? '';
$submit = $submit ?? 'Save';
$g = $graduate ?? [];
$selectedProgram = (int) old('program_id', $g['program_id'] ?? 0);
$selectedBatch = (int) old('batch_id', $g['batch_id'] ?? 0);
?>
<div class="card max-w-4xl">
    <form method="POST" action="<?= e($action) ?>" class="p-6 space-y-6">
        <?= csrf_field() ?>
        <?php if ($graduate): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>

        <div>
            <h3 class="font-semibold text-ink-800 mb-1">Academic Information</h3>
            <p class="text-xs text-ink-500 mb-4">Program, cohort and student identification.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="student_number">Student Number <span class="text-red-600">*</span></label>
                    <input type="text" name="student_number" id="student_number" class="input" required
                           value="<?= e(old('student_number', $g['student_number'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="program_id">Program <span class="text-red-600">*</span></label>
                    <select name="program_id" id="program_id" class="input" required>
                        <option value="">-- Select program --</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= $selectedProgram === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?> &mdash; <?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="batch_id">Batch <span class="text-red-600">*</span></label>
                    <select name="batch_id" id="batch_id" class="input" required>
                        <option value="">-- Select batch --</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int) $b['id'] ?>" <?= $selectedBatch === (int) $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?><?= !empty($b['label']) ? ' — ' . e($b['label']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="graduation_year">Graduation Year</label>
                    <input type="number" name="graduation_year" id="graduation_year" class="input" min="1950" max="<?= (int) date('Y') + 1 ?>"
                           placeholder="Defaults to batch year"
                           value="<?= e(old('graduation_year', $g['graduation_year'] ?? '')) ?>">
                </div>
            </div>
        </div>

        <hr class="border-ink-100">

        <div>
            <h3 class="font-semibold text-ink-800 mb-1">Personal Information</h3>
            <p class="text-xs text-ink-500 mb-4">Demographic details of the graduate.</p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="label" for="last_name">Last Name <span class="text-red-600">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="input" required
                           value="<?= e(old('last_name', $g['last_name'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="first_name">First Name <span class="text-red-600">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="input" required
                           value="<?= e(old('first_name', $g['first_name'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="middle_name">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" class="input"
                           value="<?= e(old('middle_name', $g['middle_name'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="suffix">Suffix</label>
                    <input type="text" name="suffix" id="suffix" class="input" placeholder="Jr., Sr., III"
                           value="<?= e(old('suffix', $g['suffix'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="input"
                           value="<?= e(old('email', $g['email'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="contact_number">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" class="input" maxlength="30"
                           value="<?= e(old('contact_number', $g['contact_number'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="sex">Sex</label>
                    <select name="sex" id="sex" class="input">
                        <option value="">-- Select --</option>
                        <option value="Male" <?= old('sex', $g['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= old('sex', $g['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="civil_status">Civil Status</label>
                    <select name="civil_status" id="civil_status" class="input">
                        <option value="">-- Select --</option>
                        <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $cs): ?>
                            <option value="<?= $cs ?>" <?= old('civil_status', $g['civil_status'] ?? '') === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="birth_date">Birth Date</label>
                    <input type="date" name="birth_date" id="birth_date" class="input"
                           value="<?= e(old('birth_date', $g['birth_date'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="municipality">Municipality</label>
                    <input type="text" name="municipality" id="municipality" class="input" maxlength="100"
                           value="<?= e(old('municipality', $g['municipality'] ?? '')) ?>">
                </div>
                <div>
                    <label class="label" for="province">Province</label>
                    <input type="text" name="province" id="province" class="input" maxlength="100"
                           value="<?= e(old('province', $g['province'] ?? '')) ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="label" for="address">Address</label>
                    <textarea name="address" id="address" rows="2" class="input"><?= e(old('address', $g['address'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <hr class="border-ink-100">

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer">
                <input type="checkbox" name="is_validated" value="1"
                       class="w-4 h-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                       <?= old('is_validated', $g['is_validated'] ?? false) ? 'checked' : '' ?>>
                Mark as validated (email verified)
            </label>
            <div class="flex items-center gap-2">
                <a href="<?= url('admin/graduates') ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?= e($submit) ?></button>
            </div>
        </div>
    </form>
</div>
