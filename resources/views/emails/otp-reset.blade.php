<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>Password Reset Code</title>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <style type="text/css">
        body, table, td, p, a, span { font-family: 'Segoe UI', Arial, sans-serif !important; }
        table { border-collapse: collapse; }
    </style>
    <![endif]-->
    <style type="text/css">
        body { margin: 0 !important; padding: 0 !important; -webkit-text-size-adjust: 100% !important; -ms-text-size-adjust: 100% !important; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <!-- Outer Wrapper Table -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f4f4f7; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
        <tr>
            <td align="center" style="padding: 30px 15px;">
                <!--[if (gte mso 9)|(IE)]>
                <table role="presentation" width="480" align="center" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td>
                <![endif]-->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 480px; width: 100%; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #800000; padding: 28px 20px; border-bottom: 3px solid #D4AF37; text-align: center;">
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin: 0 auto; text-align: center; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                <tr>
                                    <td align="center" style="padding-bottom: 12px;">
                                        @if(isset($message) && method_exists($message, 'embed') && file_exists(public_path('images/hau_logo.png')))
                                            <img src="{{ $message->embed(public_path('images/hau_logo.png')) }}" alt="HAU Logo" width="60" height="60" style="display: block; width: 60px; height: 60px; border: 0; outline: none; margin: 0 auto;" />
                                        @else
                                            <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" width="60" height="60" style="display: block; width: 60px; height: 60px; border: 0; outline: none; margin: 0 auto;" />
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size: 20px; font-weight: 700; color: #ffffff; line-height: 24px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        Password Reset Request
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size: 11px; font-weight: 700; color: #f3e4b2; text-transform: uppercase; letter-spacing: 1px; padding-top: 4px; line-height: 14px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        Quality Assurance Portal
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 28px 24px 20px;">
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                <tr>
                                    <td style="font-size: 16px; font-weight: 600; color: #1a1a2e; padding-bottom: 8px; line-height: 20px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        Hello, {{ $user->first_name }}!
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #475569; line-height: 22px; mso-line-height-rule: exactly; padding-bottom: 22px; font-family: 'Segoe UI', Arial, sans-serif;">
                                        We received a request to reset the password for your HAU QA Portal account. Use the verification code below to complete the process.
                                    </td>
                                </tr>

                                <!-- OTP Box Table -->
                                <tr>
                                    <td align="center" style="padding-bottom: 18px;">
                                        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fff8f8; border: 2px dashed #800000; border-radius: 8px; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                            <tr>
                                                <td align="center" style="padding: 18px 12px;">
                                                    <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; padding-bottom: 6px; line-height: 14px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                        Your Verification Code
                                                    </div>
                                                    <div style="font-size: 36px; font-weight: 800; letter-spacing: 6px; color: #800000; font-family: 'Courier New', Courier, monospace; line-height: 40px; mso-line-height-rule: exactly;">
                                                        {{ $otp }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="font-size: 12px; color: #64748b; padding-bottom: 20px; line-height: 16px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        This code expires in <strong style="color: #ef4444;">10 minutes</strong> and can only be used once.
                                    </td>
                                </tr>

                                <!-- Warning Notice Box -->
                                <tr>
                                    <td style="padding-bottom: 8px;">
                                        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                            <tr>
                                                <td style="padding: 10px 12px; font-size: 12px; color: #92400e; line-height: 18px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                    <strong>⚠️ Security Notice:</strong> If you did not request a password reset, please ignore this email. Your account remains secure and no changes have been made.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 16px 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="font-size: 11px; color: #94a3b8; margin: 0; line-height: 16px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                <strong style="color: #64748b;">Holy Angel University — Quality Assurance Office</strong><br />
                                This is an automated message. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
