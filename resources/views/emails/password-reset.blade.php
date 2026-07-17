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
            background-color: #7A1A2C; /* HAU Maroon */
            padding: 30px 40px;
            text-align: center;
            border-bottom: 4px solid #D6A628; /* HAU Gold */
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
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
            background-color: #7A1A2C;
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(122, 26, 44, 0.2);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #5d1321;
        }
        .footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-t: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>HAU QA PORTAL</h1>
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
