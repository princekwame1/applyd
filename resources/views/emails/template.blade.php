<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $heading ?: config('app.name') }}</title>
    <style>
        /* Images come from the editor without sizing, so keep them inside the
           560px shell instead of stretching the layout on phones. */
        .email-body img { max-width: 100% !important; height: auto !important; display: block; margin: 16px auto; border-radius: 8px; }
    </style>
</head>
<body style="margin:0;padding:0;background:#f7f6f5;font-family:Arial,Helvetica,sans-serif;color:#272827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f6f5;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #ece7e6;">
                <tr>
                    <td style="background:linear-gradient(135deg,#272827,#161716);padding:28px 32px;">
                        <div style="color:#e2545b;font-size:12px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;">Applyd Academy</div>
                        @if ($heading)
                            <div style="color:#ffffff;font-size:22px;font-weight:bold;margin-top:6px;">{{ $heading }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;font-size:15px;line-height:1.6;">
                        <div class="email-body">{!! $bodyHtml !!}</div>

                        @if ($ctaLabel && $ctaUrl)
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 4px;">
                                <tr><td style="border-radius:999px;background:#c73a41;">
                                    <a href="{{ $ctaUrl }}" style="display:inline-block;padding:13px 30px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;border-radius:999px;">{{ $ctaLabel }}</a>
                                </td></tr>
                            </table>
                            <p style="margin:14px 0 0;font-size:12px;color:#9a9a9a;line-height:1.5;text-align:center;">
                                If the button doesn't work, copy this link:<br>{{ $ctaUrl }}
                            </p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px;background:#f7f6f5;border-top:1px solid #ece7e6;font-size:12px;color:#9a9a9a;">
                        This message was sent by Applyd Academy. If you didn't expect it, please ignore it.
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
