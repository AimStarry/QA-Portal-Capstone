<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\GraduateRecordController;

// Root redirects to Dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Role Switcher Route
Route::post('/switch-role', function (\Illuminate\Http\Request $request) {
    $role = $request->input('role', 'QA Admin');
    session(['active_role' => $role]);
    return back()->with('success', "Switched viewport to: {$role}");
})->name('switch-role');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Core Resources (CRUD)
Route::resource('programs', ProgramController::class)->except(['create', 'edit', 'show']);
Route::resource('accreditations', AccreditationController::class)->except(['create', 'edit', 'show']);
Route::resource('risk', RiskController::class)->except(['create', 'edit', 'show']);
Route::resource('graduates', GraduateRecordController::class)->except(['create', 'edit', 'show']);

// Compliance Resource & Approval Workflow Routes
Route::resource('compliance', ComplianceController::class)->except(['create', 'edit', 'show']);
Route::post('/compliance/{id}/submit-update', [ComplianceController::class, 'submitUpdate'])->name('compliance.submit-update');
Route::post('/compliance/{id}/approve', [ComplianceController::class, 'approve'])->name('compliance.approve');
Route::post('/compliance/{id}/reject', [ComplianceController::class, 'reject'])->name('compliance.reject');
