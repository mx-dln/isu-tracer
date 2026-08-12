<?php
$programs = $programs ?? [];
$batches = $batches ?? [];
?>
<div>
    <h2 class="text-lg font-bold text-ink-900">Graduate Registration</h2>
    <p class="text-sm text-ink-500 mt-1">Register to participate in the IAT tracer study.</p>

    <form method="POST" action="<?= url('register') ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="student_number" class="label">Student Number <span class="text-red-600">*</span></label>
                <input type="text" id="student_number" name="student_number" value="<?= e(old('student_number', '')) ?>" class="input" required>
            </div>
            <div>
                <label for="email" class="label">Email Address <span class="text-red-600">*</span></label>
                <input type="email" id="email" name="email" value="<?= e(old('email', '')) ?>" class="input" required>
            </div>
            <div>
                <label for="first_name" class="label">First Name <span class="text-red-600">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= e(old('first_name', '')) ?>" class="input" required>
            </div>
            <div>
                <label for="middle_name" class="label">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= e(old('middle_name', '')) ?>" class="input">
            </div>
            <div>
                <label for="last_name" class="label">Last Name <span class="text-red-600">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= e(old('last_name', '')) ?>" class="input" required>
            </div>
            <div>
                <label for="contact_number" class="label">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?= e(old('contact_number', '')) ?>" class="input">
            </div>
            <div>
                <label for="program_id" class="label">Program <span class="text-red-600">*</span></label>
                <select id="program_id" name="program_id" class="input" required>
                    <option value="">Select program</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) old('program_id', 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?> &ndash; <?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="batch_id" class="label">Graduation Year <span class="text-red-600">*</span></label>
                <select id="batch_id" name="batch_id" class="input" required>
                    <option value="">Select year</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= (int) old('batch_id', 0) === (int) $b['id'] ? 'selected' : '' ?>><?= (int) $b['year'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="password" class="label">Password <span class="text-red-600">*</span></label>
                <input type="password" id="password" name="password" class="input" required>
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm Password <span class="text-red-600">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input" required>
            </div>
        </div>

        <p class="text-xs text-ink-500">Password must be at least 8 characters and include one uppercase letter and one number.</p>

        <button type="submit" class="btn-primary w-full !py-2.5">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Create Account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Already have an account?
        <a href="<?= url('login') ?>" class="text-brand-700 hover:text-brand-800 font-semibold">Sign in</a>
    </p>
</div>
