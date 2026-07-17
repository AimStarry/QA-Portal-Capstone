<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\GraduateRecordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;

// Guest Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    // Step 1 — Show "Forgot Password" email form
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    // Step 2 — Send OTP to email (rate limited: 5 requests per 5 minutes)
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendOtp'])->middleware('throttle:5,5')->name('password.email');
    // Step 3 — Show OTP entry form
    Route::get('/verify-otp', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showVerifyOtpForm'])->name('password.verify-otp');
    // Step 4 — Verify OTP (rate limited: 15 requests per minute)
    Route::post('/verify-otp', [\App\Http\Controllers\Auth\PasswordResetController::class, 'verifyOtp'])->middleware('throttle:15,1')->name('password.verify-otp.post');
    // Step 5 — Show new password form (session-guarded)
    Route::get('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset-form');
    // Step 6 — Save new password
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Root redirects to Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Role Switcher Route (Only for QA Admin users to toggle viewport)
    Route::post('/switch-role', function (\Illuminate\Http\Request $request) {
        if (auth()->user()->usertype !== 'QA Admin') {
            return back()->with('error', 'Access denied.');
        }
        $role = $request->input('role', 'QA Admin');
        session(['active_role' => $role]);
        return back()->with('success', "Switched viewport to: {$role}");
    })->name('switch-role');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Core Resources (CRUD)
    Route::resource('programs', ProgramController::class)->except(['create', 'edit']);
    Route::resource('accreditations', AccreditationController::class)->except(['create', 'edit', 'show']);
    Route::resource('risk', RiskController::class)->except(['create', 'edit', 'show']);
    Route::resource('graduates', GraduateRecordController::class)->except(['create', 'edit', 'show']);

    Route::get('/compliance/export', [ComplianceController::class, 'exportCsv'])->name('compliance.export');
    Route::resource('compliance', ComplianceController::class)->except(['create', 'edit', 'show']);
    Route::post('/compliance/{id}/submit-update', [ComplianceController::class, 'submitUpdate'])->name('compliance.submit-update');
    Route::post('/compliance/{id}/approve', [ComplianceController::class, 'approve'])->name('compliance.approve');
    Route::post('/compliance/{id}/reject', [ComplianceController::class, 'reject'])->name('compliance.reject');
    Route::post('/compliance/recommendations/{id}/toggle', [ComplianceController::class, 'toggleRecommendation'])->name('compliance.toggle-recommendation');
    Route::post('/compliance/recommendations/{id}/evidence', [ComplianceController::class, 'updateEvidence'])->name('compliance.recommendations.evidence');

    Route::post('/programs/{program}/toggle-accreditable', [ProgramController::class, 'toggleAccreditable'])->name('programs.toggle-accreditable');

    // Schools / Colleges
    Route::post('/colleges', [\App\Http\Controllers\CollegeController::class, 'store'])->name('colleges.store');
    Route::match(['put', 'patch'], '/colleges/{college}', [\App\Http\Controllers\CollegeController::class, 'update'])->name('colleges.update');

    // Offices / Units
    Route::post('/units', [\App\Http\Controllers\UnitController::class, 'store'])->name('units.store');
    Route::match(['put', 'patch'], '/units/{unit}', [\App\Http\Controllers\UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [\App\Http\Controllers\UnitController::class, 'destroy'])->name('units.destroy');

    // Accrediting Bodies
    Route::post('/accrediting-bodies', [\App\Http\Controllers\AccreditingBodyController::class, 'store'])->name('accrediting-bodies.store');

    // User Management (Admin Only)
    Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);

    // Admin Categories & Responsible Units Management
    Route::get('/admin/categories', [\App\Http\Controllers\AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('/admin/manage-categories', [\App\Http\Controllers\AdminCategoryController::class, 'storeCategory'])->name('admin.categories.store');
    Route::delete('/admin/manage-categories/{id}', [\App\Http\Controllers\AdminCategoryController::class, 'destroyCategory'])->name('admin.categories.destroy');
    Route::post('/admin/manage-units', [\App\Http\Controllers\AdminCategoryController::class, 'storeUnit'])->name('admin.categories.store-unit');
    Route::delete('/admin/manage-units/{id}', [\App\Http\Controllers\AdminCategoryController::class, 'destroyUnit'])->name('admin.categories.destroy-unit');

    // Notifications
    Route::post('/notifications/{id}/read', function ($id) {
        \App\Models\Notification::where('notification_id', $id)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    })->name('notifications.read');
});