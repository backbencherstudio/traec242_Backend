<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Service Added – {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF5F0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFF5F0;padding:48px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

                    {{-- Logo / Brand --}}
                    <tr>
                        <td align="center" style="padding:0 0 28px;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color:#FF6B2C;border-radius:10px;width:40px;height:40px;text-align:center;vertical-align:middle;">
                                        <span style="color:#ffffff;font-size:22px;font-weight:800;line-height:40px;display:block;">E</span>
                                    </td>
                                    <td style="padding-left:10px;vertical-align:middle;">
                                        <span style="font-size:22px;font-weight:800;color:#1a1a2e;letter-spacing:-0.5px;">{{ config('app.name') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(255,107,44,0.10);overflow:hidden;">

                            {{-- Hero banner with orange gradient --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#FF6B2C,#FF8C55);padding:36px 48px 32px;text-align:center;">
                                        <div style="font-size:40px;margin-bottom:12px;">🎉</div>
                                        <h2 style="margin:0;font-size:24px;font-weight:700;color:#ffffff;">
                                            New Service Available!
                                        </h2>
                                        <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.85);">
                                            A new service has just been added to {{ config('app.name') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Body --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:36px 48px 40px;">

                                        {{-- Service detail card --}}
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFF5F0;border-radius:12px;border:1px solid #FFE0D0;">
                                            <tr>
                                                <td style="padding:24px 28px;">
                                                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#FF6B2C;text-transform:uppercase;letter-spacing:1px;">
                                                        Service Title
                                                    </p>
                                                    <p style="margin:0 0 20px;font-size:18px;font-weight:700;color:#1a1a2e;">
                                                        {{ $title }}
                                                    </p>

                                                    <div style="height:1px;background-color:#FFE0D0;margin-bottom:20px;"></div>

                                                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#FF6B2C;text-transform:uppercase;letter-spacing:1px;">
                                                        Description
                                                    </p>
                                                    <p style="margin:0;font-size:15px;color:#374151;line-height:1.7;">
                                                        {{ $description }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- CTA Button --}}
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding:28px 0 0;">
                                                    <a href="{{ config('app.url') }}" style="display:inline-block;background:linear-gradient(90deg,#FF6B2C,#FF8C55);color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 36px;border-radius:8px;">
                                                        Explore Service &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:28px 0 0;font-size:13px;color:#9ca3af;text-align:center;line-height:1.7;">
                                            You received this email because you are subscribed to service updates from {{ config('app.name') }}.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:28px 0 0;">
                            <p style="margin:0 0 4px;font-size:12px;color:#9ca3af;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                            <p style="margin:0;font-size:12px;color:#c4c4c4;">
                                Your one-stop destination for every event.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
