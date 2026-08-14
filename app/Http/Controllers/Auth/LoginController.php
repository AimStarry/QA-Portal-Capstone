<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle authentication attempt.
     */
    public function login(Request $request)
    {
        $loginInput = trim($request->input('login') ?? $request->input('username', ''));

        if (empty($loginInput)) {
            return back()->withErrors([
                'login' => 'The email address field is required.',
                'username' => 'The email address field is required.',
            ])->onlyInput('login', 'username');
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $remember = $request->filled('remember');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $fieldType => $loginInput,
            'password'  => $request->input('password'),
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Set active_role in session based on usertype for backward compatibility/viewport styling
            $user = Auth::user();
            if ($user->usertype === 'QA Admin') {
                session(['active_role' => 'QA Admin']);
            } else {
                // Dean, Principal, Head of Unit all get the Unit or Department viewport
                session(['active_role' => 'Unit or Department']);
            }

            $nameParts = preg_split('/\s+/', trim($user->name));
            $firstName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 0, -1)) : $user->name;

            return redirect()->route('dashboard')
                ->with('success', 'Welcome back, ' . $firstName . '!');
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('login', 'username');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
