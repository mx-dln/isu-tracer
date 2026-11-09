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
                <p class="text-sm font-semibold text-ink-900">
                    <?= e($graduate['program_code'] ?? ($graduate['program_name'] ?? '—')) ?>
                    <?php if (!empty($graduate['major'])): ?>
                        <span class="block text-xs font-normal text-ink-500">Major: <?= e($graduate['major']) ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-ink-500">Graduation Year</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['graduation_year'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-ink-500">Job Position</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['job_position'] ?: 'Not indicated / Unemployed') ?></p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs text-ink-500">Address</p>
                <p class="text-sm font-semibold text-ink-900"><?= e($graduate['address'] ?: 'Not indicated') ?></p>
            </div>
            <div class="sm:col-span-3 pt-3 border-t border-ink-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-ink-500">Validation Status:</span>
                    <?php if (!empty($graduate['is_validated'])): ?>
                        <span class="badge-green"><i data-lucide="badge-check" class="w-3.5 h-3.5"></i> Validated Graduate Record</span>
                    <?php else: ?>
                        <span class="badge-amber"><i data-lucide="clock" class="w-3.5 h-3.5"></i> Pending Administrative Validation</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-ink-500">Phone Verification:</span>
                    <?php if (!empty($graduate['phone_verified_at'])): ?>
                        <span class="badge-green"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Verified via OTP</span>
                    <?php else: ?>
                        <span class="badge-gray"><i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Unverified</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Phone Verification (Semaphore OTP) -->
    <div id="verification" class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-ink-800 flex items-center gap-2">
                    <i data-lucide="smartphone" class="w-4 h-4 text-brand-600"></i>
                    Mobile Number Verification (SMS OTP)
                </h3>
                <p class="text-xs text-ink-500 mt-0.5">Verify your Philippine mobile number via SMS OTP to receive tracer alerts and authenticate your graduate profile.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border <?= !empty($graduate['phone_verified_at']) ? 'border-emerald-200 bg-emerald-50/60' : 'border-ink-200 bg-ink-50/60' ?>">
                <div>
                    <p class="text-xs text-ink-500">Current Mobile Number</p>
                    <p class="text-base font-bold text-ink-900"><?= e($graduate['contact_number'] ?: 'Not yet provided') ?></p>
                    <?php if (!empty($graduate['phone_verified_at'])): ?>
                        <p class="text-xs text-emerald-700 mt-1 flex items-center gap-1 font-medium">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Verified on <?= date('M j, Y g:i A', strtotime($graduate['phone_verified_at'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-amber-700 mt-1 flex items-center gap-1">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Unverified &middot; Request an OTP code below to verify this number
                        </p>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="<?= url('graduate/profile/send-otp') ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="contact_number" value="<?= e($graduate['contact_number'] ?? '') ?>">
                        <button type="submit" class="btn-primary !py-2 !text-xs">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i> <?= !empty($graduate['phone_verified_at']) ? 'Re-verify with OTP' : 'Send OTP Code' ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (\App\Core\Session::has('profile_otp_phone')): ?>
                <form method="POST" action="<?= url('graduate/profile/verify-otp') ?>" class="mt-4 p-4 rounded-xl border border-brand-200 bg-brand-50/40">
                    <?= csrf_field() ?>
                    <p class="text-xs font-semibold text-brand-900 mb-2">Enter the 6-digit OTP code sent to <?= e(\App\Services\OtpService::maskPhone(\App\Core\Session::get('profile_otp_phone'))) ?>:</p>
                    <div class="flex items-center gap-2 max-w-sm">
                        <input type="text" name="otp_code" required maxlength="6" pattern="[0-9]{6}" placeholder="6-digit code" class="input !py-1.5 font-mono tracking-widest text-center text-base" autofocus>
                        <button type="submit" class="btn-primary !py-2 !text-xs shrink-0">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Confirm OTP
                        </button>
                    </div>
                </form>
            <?php endif; ?>
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
                <label class="label" for="p-job-position">Current Job Position / Title</label>
                <input type="text" name="job_position" id="p-job-position" class="input" maxlength="191"
                       placeholder="e.g. Farm Supervisor, Agronomist, Agricultural Officer, Self-employed"
                       value="<?= e(old('job_position', $graduate['job_position'] ?? '')) ?>">
                <p class="text-xs text-ink-500 mt-1">Specify your current job title or position if currently employed or self-employed.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="p-address">Address</label>
                <textarea name="address" id="p-address" rows="2" class="input" placeholder="Barangay / Street, Municipality, Province"><?= e(old('address', $graduate['address'] ?? '')) ?></textarea>
            </div>
        </div>
        <div class="card-footer">
            <p class="text-xs text-ink-500">Login email: <?= e($accountEmail ?: '—') ?></p>
            <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> Save Profile</button>
        </div>
    </form>
</div>