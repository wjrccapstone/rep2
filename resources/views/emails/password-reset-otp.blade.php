<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your password reset code</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f7fb; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f7fb; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#0c2540; padding:24px; text-align:center;">
                            <span style="color:#ffffff; font-size:16px; font-weight:600; letter-spacing:0.05em;">WJRC COMPUTER SERVICES</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 8px; font-size:20px; color:#0c2540;">Reset your password</h1>
                            <p style="margin:0 0 24px; font-size:14px; color:#64748b; line-height:1.6;">
                                Use the verification code below to continue resetting your password. This code will expire in {{ $expiresInMinutes }} minutes.
                            </p>
                            <div style="text-align:center; margin:0 0 24px;">
                                <span style="display:inline-block; padding:16px 32px; background-color:#f2f7fb; border-radius:10px; font-size:32px; font-weight:700; letter-spacing:0.3em; color:#123a5c;">
                                    {{ $otp }}
                                </span>
                            </div>
                            <p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
                                If you didn't request a password reset, you can safely ignore this email. Never share this code with anyone.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
