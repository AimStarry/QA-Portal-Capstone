<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // ─────────────────────────────────────────────
    // STEP 1 — Show "Forgot Password" form
    // ─────────────────────────────────────────────
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    // ─────────────────────────────────────────────
    // STEP 2 — Generate & email a 6-digit OTP
    // ─────────────────────────────────────────────
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'We could not find a user with that email address.',
        ]);

        $email = $request->input('email');
        $user  = User::where('email', $email)->first();

        // Generate a cryptographically secure 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Upsert OTP record — expire in 10 minutes, reset attempt counter
        DB::table('password_reset_otps')->updateOrInsert(
            ['email' => $email],
            [
                'otp'        => Hash::make($otp),
                'attempts'   => 0,
                'expires_at' => Carbon::now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Store email in session so the OTP page knows who to verify
        session(['otp_email' => $email]);

        try {
            // Send the OTP email
            Mail::to($email)->send(new PasswordResetMail($user, $otp));
            $message = 'A 6-digit verification code has been sent to your email address. It expires in 10 minutes.';
        } catch (\Throwable $e) {
            // Log the error internally — never expose OTP or technical details to the user
            \Illuminate\Support\Facades\Log::error('Password reset mail send failed: ' . $e->getMessage());
            return redirect()->route('password.request')
                ->with('error', 'We could not send a verification email at this time. Please try again in a few minutes or contact IT support.');
        }

        return redirect()->route('password.verify-otp')
                         ->with('success', $message);
    }

    // ─────────────────────────────────────────────
    // STEP 3 — Show OTP verification form
    // ─────────────────────────────────────────────
    public function showVerifyOtpForm()
    {
        if (!session('otp_email')) {
            return redirect()->route('password.request')
                             ->with('error', 'Please request a new verification code.');
        }
        return view('auth.verify-otp');
    }

    // ─────────────────────────────────────────────
    // STEP 4 — Verify OTP and issue a short-lived reset token
    // ─────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $email = session('otp_email');

        if (!$email) {
            return redirect()->route('password.request')
                             ->with('error', 'Session expired. Please request a new verification code.');
        }

        $record = DB::table('password_reset_otps')->where('email', $email)->first();

        if (!$record) {
            return back()->withErrors(['otp' => 'No verification code found. Please request a new one.']);
        }

        // Block after 5 failed attempts
        if ($record->attempts >= 5) {
            DB::table('password_reset_otps')->where('email', $email)->delete();
            session()->forget('otp_email');
            return redirect()->route('password.request')
                             ->with('error', 'Too many failed attempts. Please request a new verification code.');
        }

        // Check expiry
        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('password_reset_otps')->where('email', $email)->delete();
            session()->forget('otp_email');
            return redirect()->route('password.request')
                             ->with('error', 'Your verification code has expired. Please request a new one.');
        }

        // Verify OTP
        if (!Hash::check($request->input('otp'), $record->otp)) {
            DB::table('password_reset_otps')->where('email', $email)
                ->increment('attempts');
            $remaining = 5 - ($record->attempts + 1);
            return back()->withErrors(['otp' => "Incorrect code. You have {$remaining} attempt(s) remaining."]);
        }

        // OTP is valid — mark it as verified and allow the reset form
        DB::table('password_reset_otps')->where('email', $email)->delete();
        session()->forget('otp_email');
        session(['otp_verified_email' => $email]);

        return redirect()->route('password.reset-form');
    }

    // ─────────────────────────────────────────────
    // STEP 5 — Show the new password form
    // ─────────────────────────────────────────────
    public function showResetForm()
    {
        if (!session('otp_verified_email')) {
            return redirect()->route('password.request')
                             ->with('error', 'Please complete the verification step first.');
        }
        return view('auth.reset-password');
    }

    // ─────────────────────────────────────────────
    // STEP 6 — Save the new password
    // ─────────────────────────────────────────────
    public function reset(Request $request)
    {
        $email = session('otp_verified_email');

        if (!$email) {
            return redirect()->route('password.request')
                             ->with('error', 'Session expired. Please start over.');
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.request')
                             ->with('error', 'User not found. Please try again.');
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        session()->forget('otp_verified_email');

        return redirect()->route('login')
                         ->with('success', 'Your password has been successfully reset! You can now log in.');
    }
}
