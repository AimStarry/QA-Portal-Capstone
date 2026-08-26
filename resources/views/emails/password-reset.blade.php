<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px border-gray-200;
        }
        .header {
            background: linear-gradient(135deg, #800000 0%, #5c0000 100%);
            padding: 30px 40px;
            text-align: center;
            border-bottom: 3px solid #D4AF37;
        }
        .header-logo-container {
            margin-bottom: 12px;
        }
        .header-logo-badge {
            display: inline-block;
            width: 56px;
            height: 56px;
            background-color: #ffffff;
            border-radius: 50%;
            border: 2.5px solid #D4AF37;
            overflow: hidden;
            padding: 2px;
            box-sizing: border-box;
            vertical-align: middle;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        .header-logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            display: block;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 11px;
            color: #f3e4b2;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        .content {
            padding: 40px;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 20px;
            color: #111827;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 24px;
            color: #4b5563;
            font-size: 15px;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            background: linear-gradient(135deg, #800000 0%, #5c0000 100%);
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(128, 0, 0, 0.25);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #5c0000;
        }
        .footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-logo-container">
            <div class="header-logo-badge">
                @if(isset($message) && method_exists($message, 'embed') && file_exists(public_path('images/hau_logo.png')))
                    <img src="{{ $message->embed(public_path('images/hau_logo.png')) }}" alt="HAU Logo" width="50" height="50">
                @else
                    <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" width="50" height="50">
                @endif
            </div>
        </div>
        <h1>HAU QA Portal</h1>
        <p>Quality Assurance Office</p>
    </div>
    <div class="content">
        <h2>Hello, {{ $user->first_name ?? $user->name }}!</h2>
        <p>You are receiving this email because we received a password reset request for your account on the Holy Angel University Quality Assurance Portal.</p>
        
        <div class="btn-container">
            <a href="{{ $resetUrl }}" class="btn" target="_blank">Reset Password</a>
        </div>
        
        <p>This password reset link will expire in 60 minutes.</p>
        <p>If you did not request a password reset, no further action is required.</p>
        
        <p>Best regards,<br><strong>Office of Academic Quality</strong><br>Holy Angel University</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Holy Angel University. All rights reserved.</p>
        <p>Angeles City, Pampanga, Philippines</p>
    </div>
</div>

</body>
</html>
