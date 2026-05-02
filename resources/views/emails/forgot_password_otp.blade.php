<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP – {{ config('app.name') }}</title>
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

                            {{-- Orange top bar --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:linear-gradient(90deg,#FF6B2C,#FF8C55);height:5px;"></td>
                                </tr>
                            </table>

                            {{-- Body --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:44px 48px 40px;">

                                        {{-- Icon --}}
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding-bottom:28px;">
                                                    <div style="display:inline-block;width:64px;height:64px;background:linear-gradient(135deg,#FF6B2C,#FF8C55);border-radius:50%;text-align:center;line-height:64px;font-size:28px;">
                                                        🔑
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        <h2 style="margin:0 0 10px;font-size:24px;font-weight:700;color:#1a1a2e;text-align:center;">
                                            Reset Your Password
                                        </h2>
                                        <p style="margin:0 0 36px;font-size:15px;color:#6b7280;text-align:center;line-height:1.7;">
                                            We received a request to reset the password for your account.<br>
                                            Use the code below to proceed. Do not share it with anyone.
                                        </p>

                                        {{-- OTP Box --}}
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding-bottom:28px;">
                                                    <div style="display:inline-block;background-color:#FFF5F0;border:2px dashed #FF6B2C;border-radius:14px;padding:22px 52px;">
                                                        <span style="font-size:44px;font-weight:800;letter-spacing:14px;color:#FF6B2C;font-family:'Courier New',Courier,monospace;">
                                                            {{ $otp }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Expiry banner --}}
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color:#FFF3CD;border-left:4px solid #FF6B2C;border-radius:8px;padding:14px 20px;">
                                                    <p style="margin:0;font-size:13px;color:#7c4a00;">
                                                        ⏱&nbsp; This code is valid for <strong>5 minutes</strong> only. Request a new one if it expires.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Security warning --}}
                                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                                            <tr>
                                                <td style="background-color:#FEF2F2;border-left:4px solid #EF4444;border-radius:8px;padding:14px 20px;">
                                                    <p style="margin:0;font-size:13px;color:#991b1b;">
                                                        🚨&nbsp; If you did not request a password reset, please ignore this email and secure your account immediately.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:32px 0 0;font-size:13px;color:#9ca3af;text-align:center;line-height:1.7;">
                                            For your security, this request was logged. If you need further help, contact our support team.
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
