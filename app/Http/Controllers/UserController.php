<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\College;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Enforce QA Admin role on all actions.
     */
    private function enforceAdmin()
    {
        if (!auth()->check() || auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action. Only QA Admins can manage accounts.');
        }
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $this->enforceAdmin();
        $users = User::with(['college', 'unit'])->orderBy('name')->get();
        $colleges = College::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('users.index', compact('users', 'colleges', 'units'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAdmin();
        $validated = $request->validate([
            'username' => 'required|string|alpha_dash|max:50|unique:users,username',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'usertype' => ['required', Rule::in(['Dean', 'Head of Unit', 'Principal', 'QA Admin'])],
            'college_id' => 'required_if:usertype,Dean|required_if:usertype,Principal|nullable|exists:colleges,college_id',
            'unit_id' => 'required_if:usertype,Head of Unit|nullable|exists:units,unit_id',
        ]);

        // Enforce secondary admin safety: only primary admin (admin) can create other QA Admin accounts
        if ($validated['usertype'] === 'QA Admin' && auth()->user()->username !== 'admin') {
            abort(403, 'Unauthorized action. Only the primary admin can create other QA Admin accounts.');
        }

        $validated['password'] = Hash::make($validated['password']);

        // Nullify other scope fields depending on type
        if ($validated['usertype'] === 'Dean' || $validated['usertype'] === 'Principal') {
            $validated['unit_id'] = null;
        } else {
            $validated['college_id'] = null;
        }

        $user = User::create($validated);

        // Resolve and update responsible_unit_id
        if ($user->college_id) {
            $ru = \App\Models\ResponsibleUnit::where('college_id', $user->college_id)->first();
            if ($ru) {
                $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
            }
        } elseif ($user->unit_id) {
            $ru = \App\Models\ResponsibleUnit::where('unit_id', $user->unit_id)->first();
            if ($ru) {
                $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
            }
        }

        return redirect()->route('users.index')->with('success', 'User account created successfully.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->enforceAdmin();

        // Enforce hierarchy: only primary admin ('admin' username) can edit other QA Admins
        if ($user->usertype === 'QA Admin') {
            if (auth()->user()->username !== 'admin') {
                return redirect()->route('users.index')->with('error', 'Only the primary admin can edit QA Admin accounts.');
            }
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'usertype' => ['required', Rule::in(['Dean', 'Head of Unit', 'Principal', 'QA Admin'])],
            'college_id' => 'required_if:usertype,Dean|required_if:usertype,Principal|nullable|exists:colleges,college_id',
            'unit_id' => 'required_if:usertype,Head of Unit|nullable|exists:units,unit_id',
        ]);

        // Only primary admin can assign/maintain QA Admin role
        if ($validated['usertype'] === 'QA Admin' && auth()->user()->username !== 'admin') {
            abort(403, 'Unauthorized action. Only the primary admin can assign the QA Admin role.');
        }

        // Safety: prevent admin from accidentally removing their own QA Admin role (would lock them out)
        if ($user->id === auth()->id() && auth()->user()->username === 'admin') {
            $validated['usertype'] = 'QA Admin';
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Nullify other scope fields depending on type
        if ($validated['usertype'] === 'Dean' || $validated['usertype'] === 'Principal') {
            $validated['unit_id'] = null;
        } else {
            $validated['college_id'] = null;
        }

        $user->update($validated);

        // Resolve and update responsible_unit_id
        if ($user->college_id) {
            $ru = \App\Models\ResponsibleUnit::where('college_id', $user->college_id)->first();
            if ($ru) {
                $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
            } else {
                $user->update(['responsible_unit_id' => null]);
            }
        } elseif ($user->unit_id) {
            $ru = \App\Models\ResponsibleUnit::where('unit_id', $user->unit_id)->first();
            if ($ru) {
                $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
            } else {
                $user->update(['responsible_unit_id' => null]);
            }
        } else {
            $user->update(['responsible_unit_id' => null]);
        }

        return redirect()->route('users.index')->with('success', 'User account updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->enforceAdmin();

        // Enforce hierarchy: only primary admin ('admin' username) can delete other QA Admins
        if ($user->usertype === 'QA Admin') {
            if (auth()->user()->username !== 'admin') {
                return redirect()->route('users.index')->with('error', 'Only the primary admin can delete QA Admin accounts.');
            }
            if ($user->username === 'admin') {
                return redirect()->route('users.index')->with('error', 'The primary admin account cannot be deleted.');
            }
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User account deleted successfully.');
    }
}
