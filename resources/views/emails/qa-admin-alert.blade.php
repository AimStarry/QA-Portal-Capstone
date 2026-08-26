<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>{{ $subjectTitle }}</title>
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
                <table role="presentation" width="540" align="center" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td>
                <![endif]-->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 540px; width: 100%; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #800000; padding: 26px 20px; border-bottom: 3px solid #D4AF37; text-align: center;">
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
                                        QA Admin Notification
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size: 11px; font-weight: 700; color: #f3e4b2; text-transform: uppercase; letter-spacing: 1px; padding-top: 4px; line-height: 14px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        Quality Assurance Office
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 26px 24px 20px;">
                            @if(!empty($badge))
                                @php
                                    $badgeStyle = match($badgeType) {
                                        'warning' => 'background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;',
                                        'success' => 'background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;',
                                        'danger'  => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;',
                                        default   => 'background-color: #e0f2fe; color: #075985; border: 1px solid #bae6fd;',
                                    };
                                @endphp
                                <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12px; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                    <tr>
                                        <td style="padding: 4px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; border-radius: 12px; {{ $badgeStyle }} line-height: 14px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                            {{ $badge }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                <tr>
                                    <td style="font-size: 18px; color: #0f172a; font-weight: 700; padding-bottom: 8px; line-height: 24px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                        {{ $headline }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #475569; line-height: 22px; mso-line-height-rule: exactly; padding-bottom: 20px; font-family: 'Segoe UI', Arial, sans-serif;">
                                        {{ $messageBody }}
                                    </td>
                                </tr>

                                @if(!empty($details) && count($details) > 0)
                                    <tr>
                                        <td style="padding-bottom: 20px;">
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                <tr>
                                                    <td style="padding: 12px 14px;">
                                                        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                            @foreach($details as $label => $value)
                                                                <tr>
                                                                    <td style="padding: 6px 0; width: 35%; vertical-align: top; font-size: 13px; font-weight: 600; color: #64748b; border-bottom: 1px solid #edf2f7; line-height: 18px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                                        {{ $label }}
                                                                    </td>
                                                                    <td style="padding: 6px 0 6px 10px; width: 65%; vertical-align: top; font-size: 13px; color: #1e293b; border-bottom: 1px solid #edf2f7; line-height: 18px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                                        @if(is_string($value) && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')))
                                                                            <a href="{{ $value }}" target="_blank" style="color: #800000; font-weight: 600; text-decoration: underline;">
                                                                                Open Link ↗
                                                                            </a>
                                                                        @else
                                                                            {!! nl2br(e($value)) !!}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                @if(!empty($actionUrl))
                                    <tr>
                                        <td align="center" style="padding-bottom: 20px;">
                                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin: 0 auto; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                <tr>
                                                    <td align="center" style="background-color: #800000; border-radius: 6px;">
                                                        <a href="{{ $actionUrl }}" target="_blank" style="display: inline-block; padding: 11px 24px; font-size: 13px; font-weight: 600; color: #ffffff !important; text-decoration: none; line-height: 16px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                            {{ $actionText }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td style="padding-top: 4px;">
                                        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                            <tr>
                                                <td style="padding: 9px 12px; font-size: 12px; color: #92400e; line-height: 17px; mso-line-height-rule: exactly; font-family: 'Segoe UI', Arial, sans-serif;">
                                                    <strong>Admin Notice:</strong> This notification was automatically dispatched to QA Administrators.
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
                                <strong style="color: #64748b;">Holy Angel University — Quality Assurance Portal</strong><br />
                                This is an automated system email. Please do not reply directly to this address.
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
