<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Email dispatcher backed by PHPMailer.
 *
 * When SMTP is not configured in .env, emails are logged to
 * storage/logs/mail-YYYY-MM-DD.log instead of being sent.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, array $attachments = [], ?string $plainText = null): bool
    {
        $config = config('mail');

        if (!$config['enabled']) {
            Logger::info('Email (not sent - SMTP disabled)', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            self::logEmail($to, $subject, $plainText ?? strip_tags($htmlBody));
            return true; // treated as delivered for demo
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = (bool) $config['username'];
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['encryption'] ?: PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->isHTML(true);
            $mail->SMTPDebug  = $config['debug'] ? 2 : 0;

            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            if ($plainText !== null) {
                $mail->AltBody = $plainText;
            }

            foreach ($attachments as $path => $name) {
                $mail->addAttachment($path, $name);
            }

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            Logger::error('Mail delivery failed: ' . $e->getMessage(), ['to' => $to, 'subject' => $subject]);
            return false;
        }
    }

    private static function logEmail(string $to, string $subject, string $body): void
    {
        $dir = (string) config('app.paths.storage_logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/mail-' . date('Y-m-d') . '.log';
        $entry = "========================================\n"
            . 'To: ' . $to . "\n"
            . 'Subject: ' . $subject . "\n"
            . 'Time: ' . date('Y-m-d H:i:s') . "\n"
            . strip_tags($body) . "\n\n";
        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }
}
