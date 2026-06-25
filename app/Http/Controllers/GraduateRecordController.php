<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\GraduateRecord;
use Illuminate\Http\Request;

class GraduateRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $role = session('active_role', 'QA Admin');
        $programs = Program::orderBy('program_code')->get();

        // Get unique school years and terms for search/filter dropdowns
        $schoolYears = GraduateRecord::distinct()->pluck('school_year')->sort()->values();
        $terms = GraduateRecord::distinct()->pluck('term')->sort()->values();

        // Query graduate records
        $query = GraduateRecord::with('program');

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

        $graduates = $query->orderBy('school_year', 'desc')->orderBy('term', 'asc')->get();

        return view('graduates.index', compact(
            'role',
            'graduates',
            'programs',
            'schoolYears',
            'terms'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
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
        $record = GraduateRecord::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
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
        $record = GraduateRecord::findOrFail($id);
        $record->delete();

        return redirect()->route('graduates.index')->with('success', 'Graduate count record deleted successfully.');
    }
}
