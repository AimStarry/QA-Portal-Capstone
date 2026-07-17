<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f7; }
        .wrapper { background-color: #f4f4f7; padding: 40px 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #7B1C2E 0%, #5a1020 100%); padding: 32px 40px; text-align: center; }
        .header-logo { display: inline-block; width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 50%; border: 3px solid rgba(255,255,255,0.4); line-height: 56px; font-size: 16px; font-weight: 900; color: #ffffff; letter-spacing: 1px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; }
        .header p { margin: 4px 0 0; font-size: 12px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1px; }
        .body { padding: 40px; }
        .greeting { font-size: 16px; color: #1a1a2e; font-weight: 600; margin-bottom: 8px; }
        .message { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 32px; }
        .otp-box { background: linear-gradient(135deg, #fff8f8 0%, #fef2f2 100%); border: 2px dashed #7B1C2E; border-radius: 12px; text-align: center; padding: 24px; margin-bottom: 28px; }
        .otp-label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
        .otp-code { font-size: 42px; font-weight: 900; letter-spacing: 10px; color: #7B1C2E; font-family: 'Courier New', monospace; }
        .expiry-note { font-size: 12px; color: #9ca3af; text-align: center; margin-bottom: 24px; }
        .expiry-note strong { color: #ef4444; }
        .warning-box { background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; }
        .warning-box p { margin: 0; font-size: 12px; color: #92400e; line-height: 1.5; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 11px; color: #9ca3af; margin: 0; line-height: 1.6; }
        .footer strong { color: #6b7280; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="header-logo">HAU</div>
                <h1>Password Reset Request</h1>
                <p>Quality Assurance Portal</p>
            </div>
            <div class="body">
                <p class="greeting">Hello, {{ $user->first_name }}!</p>
                <p class="message">
                    We received a request to reset the password for your HAU QA Portal account.
                    Use the verification code below to complete the process.
                </p>

                <div class="otp-box">
                    <div class="otp-label">Your Verification Code</div>
                    <div class="otp-code">{{ $otp }}</div>
                </div>

                <p class="expiry-note">
                    This code expires in <strong>10 minutes</strong> and can only be used once.
                </p>

                <div class="warning-box">
                    <p>
                        <strong>⚠️ Security Notice:</strong> If you did not request a password reset,
                        please ignore this email. Your account remains secure and no changes have been made.
                    </p>
                </div>
            </div>
            <div class="footer">
                <p>
                    <strong>Holy Angel University — Quality Assurance Office</strong><br>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
