<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f7f6f5;font-family:Arial,Helvetica,sans-serif;color:#272827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f6f5;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #ece7e6;">
                <tr>
                    <td style="background:linear-gradient(135deg,#272827,#161716);padding:28px 32px;">
                        <div style="color:#e2545b;font-size:12px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;">Applyd Academy</div>
                        <div style="color:#ffffff;font-size:22px;font-weight:bold;margin-top:6px;">Your application has started</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Dear {{ $firstName }},</p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">
                            You have started your <strong>{{ $courseTitle }}</strong> application. Please keep the credentials below safe — you'll need them to sign in and complete all stages of your application.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f6f5;border:1px dashed #d6c9c9;border-radius:10px;margin-bottom:24px;">
                            <tr><td style="padding:14px 18px;font-size:14px;color:#5f605f;">Serial Number</td>
                                <td style="padding:14px 18px;font-size:16px;font-weight:bold;color:#c73a41;text-align:right;letter-spacing:1px;">{{ $serialNo }}</td></tr>
                            <tr><td style="padding:14px 18px;font-size:14px;color:#5f605f;border-top:1px solid #eee2e2;">PIN</td>
                                <td style="padding:14px 18px;font-size:16px;font-weight:bold;color:#c73a41;text-align:right;letter-spacing:1px;border-top:1px solid #eee2e2;">{{ $pin }}</td></tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 8px;">
                            <tr><td style="border-radius:999px;background:#c73a41;">
                                <a href="{{ $link }}" style="display:inline-block;padding:13px 30px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;border-radius:999px;">Continue your application →</a>
                            </td></tr>
                        </table>
                        <p style="margin:16px 0 0;font-size:12px;color:#9a9a9a;line-height:1.5;text-align:center;">
                            If the button doesn't work, copy this link:<br>{{ $link }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px;background:#f7f6f5;border-top:1px solid #ece7e6;font-size:12px;color:#9a9a9a;">
                        This message was sent by Applyd Academy. If you didn't make this request, please ignore it.
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
