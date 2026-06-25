<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Program::withCount(['accreditations', 'complianceRecords', 'riskItems']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('program_code', 'like', "%{$search}%")
                  ->orWhere('program_name', 'like', "%{$search}%")
                  ->orWhere('college', 'like', "%{$search}%");
        }

        $programs = $query->orderBy('program_code')->get();

        // Calculate some program statistics
        $collegeCounts = Program::selectRaw('college, count(*) as count')
            ->groupBy('college')
            ->pluck('count', 'college');

        return view('programs.index', compact('programs', 'collegeCounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_code' => 'required|string|unique:programs,program_code|max:15|alpha_num',
            'program_name' => 'required|string|max:255',
            'college' => 'required|string|max:255',
            'program_level' => 'required|string|max:255',
        ]);

        // Standardize uppercase for program codes
        $validated['program_code'] = strtoupper($validated['program_code']);

        Program::create($validated);

        return redirect()->route('programs.index')->with('success', 'Academic program created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'program_code' => 'required|string|max:15|alpha_num|unique:programs,program_code,' . $program->id,
            'program_name' => 'required|string|max:255',
            'college' => 'required|string|max:255',
            'program_level' => 'required|string|max:255',
        ]);

        $validated['program_code'] = strtoupper($validated['program_code']);

        $program->update($validated);

        return redirect()->route('programs.index')->with('success', 'Academic program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Academic program deleted successfully.');
    }
}
