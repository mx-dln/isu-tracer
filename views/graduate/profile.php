<?php
/** @var array $graduate @var string $accountEmail @var array $old */
$graduate = $graduate ?? [];
$accountEmail = $accountEmail ?? '';
$old = $old ?? [];
?>
<div class="space-y-6 max-w-3xl">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800">My Profile</h3>
                <p class="text-xs text-ink-500 mt-0.5">Update your contact information. Academic details (student number, program, batch) are maintained by the institute.</p>
            </div>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-ink-500">Student Number</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['student_number'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-ink-500">Program</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['program_code'] ?? ($graduate['program_name'] ?? '—')) ?></p>
            </div>
            <div>
                <p class="text-xs text-ink-500">Graduation Year</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['graduation_year'] ?? '—') ?></p>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= url('graduate/profile') ?>" class="card">
        <?= csrf_field() ?>
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800">Personal Information</h3>
            </div>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label" for="p-first">First Name <span class="text-red-600">*</span></label>
                <input type="text" name="first_name" id="p-first" class="input" required maxlength="100" value="<?= e(old('first_name', $graduate['first_name'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-middle">Middle Name</label>
                <input type="text" name="middle_name" id="p-middle" class="input" maxlength="100" value="<?= e(old('middle_name', $graduate['middle_name'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-last">Last Name <span class="text-red-600">*</span></label>
                <input type="text" name="last_name" id="p-last" class="input" required maxlength="100" value="<?= e(old('last_name', $graduate['last_name'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-suffix">Suffix</label>
                <input type="text" name="suffix" id="p-suffix" class="input" maxlength="20" placeholder="e.g. Jr., III" value="<?= e(old('suffix', $graduate['suffix'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-email">Email</label>
                <input type="email" name="email" id="p-email" class="input" maxlength="191" value="<?= e(old('email', $graduate['email'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-contact">Contact Number</label>
                <input type="text" name="contact_number" id="p-contact" class="input" maxlength="30" placeholder="e.g. 09171234567" value="<?= e(old('contact_number', $graduate['contact_number'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-sex">Sex</label>
                <select name="sex" id="p-sex" class="input">
                    <option value="">—</option>
                    <?php foreach (['Male', 'Female'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= old('sex', $graduate['sex'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="p-civil">Civil Status</label>
                <select name="civil_status" id="p-civil" class="input">
                    <option value="">—</option>
                    <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= old('civil_status', $graduate['civil_status'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="p-birth">Birth Date</label>
                <input type="date" name="birth_date" id="p-birth" class="input" value="<?= e(old('birth_date', $graduate['birth_date'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-municipality">Municipality</label>
                <input type="text" name="municipality" id="p-municipality" class="input" maxlength="100" value="<?= e(old('municipality', $graduate['municipality'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="p-province">Province</label>
                <input type="text" name="province" id="p-province" class="input" maxlength="100" value="<?= e(old('province', $graduate['province'] ?? '')) ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="p-address">Address</label>
                <textarea name="address" id="p-address" rows="2" class="input"><?= e(old('address', $graduate['address'] ?? '')) ?></textarea>
            </div>
        </div>
        <div class="card-footer">
            <p class="text-xs text-ink-500">Login email: <?= e($accountEmail ?: '—') ?></p>
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Profile</button>
        </div>
    </form>
</div>