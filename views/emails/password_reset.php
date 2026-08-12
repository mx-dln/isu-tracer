<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.08);">
                    <tr>
                        <td style="background:#1a3b2f;padding:20px 24px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">ISU-Cauayan IAT Tracer Study</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 24px;">
                            <h2 style="margin:0 0 16px;color:#0f172a;font-size:20px;">Reset Your Password</h2>
                            <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.6;">
                                Hi <?= e($name ?? 'Graduate') ?>,
                            </p>
                            <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.6;">
                                We received a request to reset your password. Click the button below to create a new one.
                                This link expires in 60 minutes.
                            </p>
                            <a href="<?= e($link ?? '#') ?>"
                               style="display:inline-block;background:#2c6e52;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:600;">
                                Reset Password
                            </a>
                            <p style="margin:24px 0 0;color:#94a3b8;font-size:12px;line-height:1.6;">
                                If you did not request a password reset, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 24px;color:#94a3b8;font-size:12px;">
                            &copy; <?= date('Y') ?> Isabela State University - Cauayan Campus &middot; Institute of Agricultural Technology
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
