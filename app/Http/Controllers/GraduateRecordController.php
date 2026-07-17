<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\GraduateRecord;
use Illuminate\Http\Request;

class GraduateRecordController extends Controller
{
    /**
     * Enforce access control.
     */
    private function enforceAccess($action)
    {
        $user = auth()->user();
        if ($user->usertype === 'Head of Unit') {
            abort(403, 'Unauthorized action. Support Units do not have access to graduate records.');
        }

        // Restrict write actions to QA Admin only
        if (in_array($action, ['store', 'update', 'destroy'])) {
            if ($user->usertype !== 'QA Admin') {
                abort(403, 'Unauthorized action. Only QA Admins can manage graduate records.');
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->enforceAccess('index');
        $role = session('active_role', 'QA Admin');
        $user = auth()->user();

        if ($user->usertype !== 'QA Admin') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }

        $collegeId = $user->college_id;

        // Query programs based on college if Dean or Principal
        $programsQuery = Program::orderBy('program_code');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $programsQuery->where('college_id', $collegeId);
        }
        $programs = $programsQuery->get();

        // Get unique school years and terms for search/filter dropdowns
        $schoolYearsQuery = GraduateRecord::distinct();
        $termsQuery = GraduateRecord::distinct();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $schoolYearsQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
            $termsQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }
        $schoolYears = $schoolYearsQuery->pluck('school_year')->sort()->values();
        $terms = $termsQuery->pluck('term')->sort()->values();

        // Query graduate records
        $query = GraduateRecord::with('program');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('school_year', 'like', "%{$search}%")
                  ->orWhere('term', 'like', "%{$search}%")
                  ->orWhereHas('program', function ($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%")
                        ->orWhere('program_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->input('program_id'));
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->input('school_year'));
        }

        if ($request->filled('term')) {
            $query->where('term', $request->input('term'));
        }

        if ($request->filled('college_id')) {
            $query->whereHas('program', fn($q) => $q->where('college_id', $request->input('college_id')));
        }

        $graduates = $query->orderBy('school_year', 'desc')->orderBy('term', 'asc')->get();

        $collegesQuery = \App\Models\College::orderBy('name');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $collegesQuery->where('college_id', $collegeId);
        }
        $colleges = $collegesQuery->get();

        return view('graduates.index', compact(
            'role',
            'graduates',
            'programs',
            'schoolYears',
            'terms',
            'colleges'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAccess('store'); // enforceAccess already ensures QA Admin via usertype

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'school_year' => 'required|string|max:255',
            'term' => 'required|string|max:255',
            'graduates_count' => 'required|integer|min:0',
        ]);

        GraduateRecord::create($validated);

        return redirect()->route('graduates.index')->with('success', 'Graduate count record logged successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->enforceAccess('update'); // enforceAccess already ensures QA Admin via usertype

        $record = GraduateRecord::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'school_year' => 'required|string|max:255',
            'term' => 'required|string|max:255',
            'graduates_count' => 'required|integer|min:0',
        ]);

        $record->update($validated);

        return redirect()->route('graduates.index')->with('success', 'Graduate count record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->enforceAccess('destroy'); // enforceAccess already ensures QA Admin via usertype

        $record = GraduateRecord::findOrFail($id);
        $record->delete();

        return redirect()->route('graduates.index')->with('success', 'Graduate count record deleted successfully.');
    }
}
