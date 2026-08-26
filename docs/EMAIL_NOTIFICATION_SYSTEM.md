# QA Portal — Email Notification System Reference

This document provides a complete overview of the Email Notification and OTP Verification architecture implemented in the Holy Angel University Quality Assurance Portal (QA Portal).

---

## 1. System Architecture & Flow

```
[ Trigger Event ]
  ├─ Password Reset Request (User)
  ├─ Compliance Action Plan Submitted (Unit/Dept)
  ├─ Compliance Recommendation Checklist Item Ticked (Unit/Dept)
  └─ QA Risk Profile Created / Updated / Auto-Logged
        │
        ▼
[ Controller / Service Logic ]
  ├─ PasswordResetController@sendOtp
  ├─ ComplianceController@submitActionPlan
  ├─ ComplianceController@toggleRecommendationItem
  ├─ RiskController@store / update
  └─ RiskAutoLogService::syncFromCompliance / syncFromAccreditation
        │
        ▼
[ Recipient Resolution ]
  ├─ Password Reset: Direct to requesting user's `email`
  └─ System Alerts: User::getQaAdminRecipients() (all QA Admins)
        │
        ▼
[ Mailable Build with Multi-Part MIME ]
  ├─ PasswordResetMail (HTML: emails.otp-reset, Text: emails.otp-reset-plain)
  └─ QaAdminAlertMail  (HTML: emails.qa-admin-alert, Text: emails.qa-admin-alert-plain)
        │
        ▼
[ SMTP Transport ]
  ├─ Local Dev: Mailtrap Sandbox (sandbox.smtp.mailtrap.io:2525)
  └─ Production: Hostinger SSL SMTP (smtp.hostinger.com:465)
        │
        ▼
[ Email Delivery ]
  └─ Delivered to Outlook / Microsoft 365 / Gmail Inboxes
```

---

## 2. Key Files & Directory Map

### A. Mailables (`app/Mail/`)
1. **`app/Mail/PasswordResetMail.php`**
   - Handles self-service password reset OTPs (6-digit code).
   - Multi-part MIME enabled with `->view('emails.otp-reset')->text('emails.otp-reset-plain')`.
2. **`app/Mail/QaAdminAlertMail.php`**
   - Reusable notification mailable for all QA Admin system alerts (action plans, compliance items, risks).
   - Parameters: `$subjectTitle`, `$badge`, `$headline`, `$messageBody`, `$details`, `$actionUrl`, `$actionText`, `$badgeType`.
   - Multi-part MIME enabled with `->view('emails.qa-admin-alert')->text('emails.qa-admin-alert-plain')`.

### B. Blade Email Templates (`resources/views/emails/`)
1. **`resources/views/emails/otp-reset.blade.php`**
   - Rich HTML template designed with table-based architecture, inline CSS styles, and MSO conditional comments for Outlook Desktop/Mobile compatibility.
   - Clean circular HAU seal header with deep maroon background (`#800000`) and gold accent (`#D4AF37`).
2. **`resources/views/emails/otp-reset-plain.blade.php`**
   - Clean plain-text fallback version to eliminate the `MIME_HTML_ONLY` spam penalty on Microsoft 365 Exchange Online Protection (EOP).
3. **`resources/views/emails/qa-admin-alert.blade.php`**
   - Rich HTML alert template with dynamic status badges (`badge-warning`, `badge-success`, `badge-danger`, `badge-info`), structured details table, and deep-link action button.
4. **`resources/views/emails/qa-admin-alert-plain.blade.php`**
   - Plain-text fallback for all QA Admin alerts.

### C. Controllers & Services Triggering Emails
1. **`app/Http/Controllers/Auth/PasswordResetController.php`**
   - `sendOtp(Request $request)`: Validates user email, generates a secure 6-digit OTP, stores hashed OTP in `password_reset_otps` table (10-min expiry), and sends `PasswordResetMail`.
   - `verifyOtp(Request $request)`: Validates OTP with rate-limiting (max 5 attempts).
   - `reset(Request $request)`: Updates password securely using `Hash::make()`.
2. **`app/Http/Controllers/ComplianceController.php`**
   - `submitActionPlan()`: Sends `QaAdminAlertMail` when a unit submits an action plan and proof link for review.
   - `toggleRecommendationItem()`: Sends `QaAdminAlertMail` when a checklist recommendation item is marked as completed.
3. **`app/Http/Controllers/RiskController.php`**
   - `store()` & `update()`: Sends `QaAdminAlertMail` when QA risks are created or modified, color-coded by likelihood (`High` = danger, `Medium` = warning, `Low` = info).
4. **`app/Services/RiskAutoLogService.php`**
   - Automatically logs and sends `QaAdminAlertMail` when system scans detect overdue tasks or accreditations.

### D. Models (`app/Models/`)
1. **`app/Models/User.php`**
   - `getQaAdminRecipients()`: Dynamically queries all active QA Administrators (`where('usertype', 'QA Admin')->whereNotNull('email')`) for alert dispatch.

### E. Configuration (`config/` & `.env`)
1. **`config/mail.php`**
   - Configured with `smtp` driver supporting `encryption` (`ssl` on port 465 / `tls` on port 2525/587).

---

## 3. Environment Configurations

### Local Development (`.env`)
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=2a6d7b71aebe6e
MAIL_PASSWORD=c0c802cb5e78d4
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="qaportal-no-reply@hau-oie-idmo.com"
MAIL_FROM_NAME="HAU QA Portal"
```

### Production Live Server (`.env`)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=qaportal-no-reply@hau-oie-idmo.com
MAIL_PASSWORD="QAPortal_NoRepP4ss"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="qaportal-no-reply@hau-oie-idmo.com"
MAIL_FROM_NAME="HAU QA Portal"
```

---

## 4. Outlook & Microsoft 365 Deliverability Best Practices

1. **Multi-Part MIME (`HTML` + `Plain Text`)**:
   Always provide both HTML and plain-text templates. Without a plain-text alternative, Microsoft 365 treats external emails as suspicious and frequently drops or quarantines them.

2. **Table-Based Layout**:
   Outlook on Windows uses Microsoft Word's rendering engine. Avoid CSS Flexbox and CSS Grid in emails; use traditional `<table>`, `<tr>`, and `<td>` with inlined styles and `mso-line-height-rule: exactly;`.

3. **Domain Matching (`APP_URL`)**:
   In production, ensure all links and assets in the email point to the live HTTPS domain (`https://hau-oie-idmo.com`) rather than `http://127.0.0.1:8000` to prevent anti-phishing flags.
