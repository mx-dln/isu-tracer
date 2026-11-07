<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Graduate;

/**
 * Handles generating, dispatching via Semaphore SMS, and verifying OTP codes.
 */
final class OtpService
{
    /**
     * Generate and send an OTP code via Semaphore SMS.
     *
     * @return array{success:bool, error:?string, phone:?string, status:string, simulated:bool, code?:string}
     */
    public function send(string $phone, ?int $graduateId = null, ?int $userId = null, string $action = 'verification'): array
    {
        $normalized = SemaphoreService::normalizeNumber($phone);
        if ($normalized === null) {
            return [
                'success'   => false,
                'error'     => 'Invalid mobile number. Please enter a valid 11-digit Philippine mobile number (e.g. 09171234567).',
                'phone'     => null,
                'status'    => 'invalid_number',
                'simulated' => false,
            ];
        }

        // 60-second cooldown per phone & action
        $recent = Database::fetch(
            'SELECT created_at FROM otp_codes
             WHERE phone = ? AND action = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
             ORDER BY id DESC LIMIT 1',
            [$normalized, $action]
        );
        if ($recent) {
            return [
                'success'   => false,
                'error'     => 'Please wait at least 60 seconds before requesting another code.',
                'phone'     => $normalized,
                'status'    => 'cooldown',
                'simulated' => false,
            ];
        }

        // Invalidate older active codes for this phone & action
        Database::run(
            'UPDATE otp_codes SET is_used = 1 WHERE phone = ? AND action = ? AND is_used = 0',
            [$normalized, $action]
        );

        // Generate cryptographically secure 6-digit numeric OTP
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        Database::run(
            'INSERT INTO otp_codes (user_id, graduate_id, phone, code, action, is_used, expires_at)
             VALUES (?, ?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL 10 MINUTE))',
            [$userId, $graduateId, $normalized, $code, $action]
        );

        $message = "ISU-IAT Tracer Study: Your verification code is {$code}. Valid for 10 minutes. Do not share this OTP.";

        $semaphore = new SemaphoreService();
        $res = $semaphore->send($normalized, $message);

        $simulated = ($res['status'] === 'simulated');
        $isDebug = (bool) (config('app.debug', false) || env('APP_DEBUG', false));

        if (!$res['success']) {
            if ($isDebug) {
                Logger::warning('SMS dispatch failed, providing fallback OTP in debug mode', ['error' => $res['error']]);
                return [
                    'success'   => true,
                    'error'     => null,
                    'phone'     => $normalized,
                    'status'    => 'debug_fallback',
                    'simulated' => true,
                    'code'      => $code,
                ];
            }

            return [
                'success'   => false,
                'error'     => 'Failed to deliver SMS verification code: ' . ($res['error'] ?? 'provider error'),
                'phone'     => $normalized,
                'status'    => 'failed',
                'simulated' => false,
            ];
        }

        Logger::info('OTP generated and dispatched', [
            'phone'     => self::maskPhone($normalized),
            'action'    => $action,
            'status'    => $res['status'],
            'simulated' => $simulated,
        ]);

        return [
            'success'   => true,
            'error'     => null,
            'phone'     => $normalized,
            'status'    => $res['status'],
            'simulated' => $simulated,
            'code'      => ($simulated || $isDebug) ? $code : null,
        ];
    }

    /**
     * Verify an OTP code.
     */
    public function verify(string $phone, string $code, string $action = 'verification'): bool
    {
        $normalized = SemaphoreService::normalizeNumber($phone);
        if ($normalized === null) {
            return false;
        }

        $cleanCode = trim($code);
        if (strlen($cleanCode) !== 6 || !ctype_digit($cleanCode)) {
            return false;
        }

        $row = Database::fetch(
            'SELECT * FROM otp_codes
             WHERE phone = ? AND code = ? AND action = ? AND is_used = 0 AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1',
            [$normalized, $cleanCode, $action]
        );

        if (!$row) {
            return false;
        }

        // Mark as used
        Database::run('UPDATE otp_codes SET is_used = 1 WHERE id = ?', [$row['id']]);

        // If linked to graduate, mark phone_verified_at
        if (!empty($row['graduate_id'])) {
            Graduate::markPhoneVerified((int) $row['graduate_id']);
        }

        Logger::info('OTP verified successfully', [
            'phone'  => self::maskPhone($normalized),
            'action' => $action,
        ]);

        return true;
    }

    /**
     * Mask a phone number for display (e.g. 639171234567 -> +63 917 •••• 567 or 0917••••567).
     */
    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return $phone;
        }
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+63 ' . substr($digits, 2, 3) . ' •••• ' . substr($digits, -3);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return substr($digits, 0, 4) . ' •••• ' . substr($digits, -3);
        }
        return substr($digits, 0, 3) . '••••' . substr($digits, -3);
    }
}
