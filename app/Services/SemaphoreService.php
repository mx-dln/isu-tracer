<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * SMS delivery via the Semaphore API.
 *
 * Credentials are read from configuration (environment variables) only —
 * never from source code. The API key is never logged or returned.
 */
final class SemaphoreService
{
    /**
     * Send an SMS. Returns delivery metadata (no secrets).
     *
     * @return array{success:bool, status:string, message_id:?string, error:?string}
     *   status is one of: sent|simulated|failed|skipped
     */
    public function send(string $number, string $message): array
    {
        $config = config('sms');
        $normalized = self::normalizeNumber($number);

        if ($normalized === null) {
            return ['success' => false, 'status' => 'skipped', 'message_id' => null, 'error' => 'no_valid_mobile_number'];
        }

        if (!$config['enabled'] || $config['test_mode']) {
            Logger::info('SMS (simulated - provider disabled or test mode)', [
                'to'      => $normalized,
                'message' => mb_substr($message, 0, 200),
            ]);
            return ['success' => true, 'status' => 'simulated', 'message_id' => null, 'error' => null];
        }

        $payload = [
            'apikey'  => (string) $config['api_key'],
            'number'  => $normalized,
            'message' => $message,
        ];
        if (!empty($config['sender_name'])) {
            $payload['sendername'] = (string) $config['sender_name'];
        }

        $ch = curl_init((string) $config['api_base']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => http_build_query($payload),
            CURLOPT_TIMEOUT         => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            Logger::error('SMS delivery failed (unreachable)', ['to' => $normalized, 'curl_error' => $curlError]);
            return ['success' => false, 'status' => 'failed', 'message_id' => null, 'error' => 'provider_unreachable'];
        }

        $decoded = json_decode((string) $response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $messageId = $decoded[0]['message_id'] ?? $decoded['message_id'] ?? null;
            return ['success' => true, 'status' => 'sent', 'message_id' => $messageId !== null ? (string) $messageId : null, 'error' => null];
        }

        $safeError = $decoded['message'] ?? $decoded[0]['message'] ?? ('http_' . $httpCode);
        Logger::error('SMS delivery failed', ['to' => $normalized, 'http' => $httpCode]);
        return ['success' => false, 'status' => 'failed', 'message_id' => null, 'error' => mb_substr((string) $safeError, 0, 500)];
    }

    /**
     * Normalize a PH mobile number to E.164-ish (e.g. 09171234567 -> 639171234567).
     * Returns null when the number is missing/too short.
     */
    public static function normalizeNumber(string $number): ?string
    {
        $digits = preg_replace('/\D/', '', $number);
        if ($digits === null || $digits === '' || strlen($digits) < 10) {
            return null;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '63' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '63' . substr($digits, 1);
        }
        return $digits;
    }
}
