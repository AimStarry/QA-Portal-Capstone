<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\College;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Enforce access control.
     */
    private function enforceAccess($action, $program = null)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        // Heads of Units do not have access to academic programs index show or writes,
        // but they can access the index page to manage units.
        if ($user->usertype === 'Head of Unit' && $action !== 'index') {
            abort(403, 'Unauthorized action. Heads of Units do not have access to academic programs.');
        }

        if (in_array($action, ['store', 'update', 'destroy', 'toggleAccreditable'])) {
            if ($user->usertype === 'QA Admin') {
                return;
            }

            if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
                if ($action === 'store') {
                    if ((int)request()->input('college_id') !== (int)$user->college_id) {
                        abort(403, 'Unauthorized action. You can only create programs for your own school.');
                    }
                    return;
                }

                if ($action === 'update') {
                    if (!$program || (int)$program->college_id !== (int)$user->college_id || (int)request()->input('college_id') !== (int)$user->college_id) {
                        abort(403, 'Unauthorized action. You can only modify programs within your own school.');
                    }
                    return;
                }

                if ($action === 'destroy') {
                    if (!$program || (int)$program->college_id !== (int)$user->college_id) {
                        abort(403, 'Unauthorized action. You can only delete programs within your own school.');
                    }
                    return;
                }

                if ($action === 'toggleAccreditable') {
                    if (!$program || (int)$program->college_id !== (int)$user->college_id) {
                        abort(403, 'Unauthorized action. You can only modify programs within your own school.');
                    }
                    return;
                }
            }

            abort(403, 'Unauthorized action. You do not have permissions to manage academic programs.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->enforceAccess('index');
        $user = auth()->user();
        $query = Program::with('college')->withCount(['accreditations', 'complianceRecords', 'riskItems']);

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->where('college_id', $user->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('program_code', 'like', "%{$search}%")
                  ->orWhere('program_name', 'like', "%{$search}%")
                  ->orWhereHas('college', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $programs = $query->orderBy('program_code')->get();

        // Calculate some program statistics, grouped by college name.
        $collegeCounts = $programs
            ->groupBy(fn ($p) => $p->college->name ?? 'Unassigned')
            ->map->count();

        // Deans and Principals only see their own college in the directory
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $colleges = College::where('college_id', $user->college_id)->orderBy('name')->get();
        } else {
            $colleges = College::orderBy('name')->get();
        }
        $units = \App\Models\Unit::orderBy('name')->get();
        $deans = \App\Models\User::whereIn('usertype', ['Dean', 'Principal'])->orderBy('name')->get();
        $unitHeads = \App\Models\User::where('usertype', 'Head of Unit')->orderBy('name')->get();
        
        // Fetch all registered accrediting bodies
        // Add a check in case migration hasn't run yet during early installation phases
        $accreditingBodies = \Illuminate\Support\Facades\Schema::hasTable('accrediting_bodies') 
            ? \App\Models\AccreditingBody::orderBy('code')->get() 
            : collect();

        $role = session('active_role', $user->usertype);

        return view('programs.index', compact('programs', 'collegeCounts', 'colleges', 'units', 'deans', 'unitHeads', 'role', 'accreditingBodies'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->enforceAccess('show');
        $role = session('active_role', 'QA Admin');
        if ($role === 'Responsible Unit') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }
        $user = auth()->user();
        $program = Program::with([
            'college',
            'accreditations' => function($q) {
                $q->orderBy('last_visit', 'desc');
            },
            'complianceRecords' => function($q) {
                $q->orderBy('due_date', 'asc');
            },
            'riskItems' => function($q) {
                $q->orderBy('likelihood', 'desc');
            },
            'graduateRecords' => function($q) {
                $q->orderBy('school_year', 'desc')->orderBy('term', 'asc');
            }
        ])->findOrFail($id);

        if (($user->usertype === 'Dean' || $user->usertype === 'Principal') && $program->college_id !== $user->college_id) {
            abort(403, 'Unauthorized action. You can only view programs in your own school.');
        }

        // Compute program specific stats
        $totalCompliance = $program->complianceRecords->count();
        $compliantCount = $program->complianceRecords->where('status', 'Compliant')->count();
        $nonCompliantCount = $program->complianceRecords->where('status', 'Non-Compliant')->count();
        $pendingCount = $program->complianceRecords->where('status', 'Pending')->count();

        $activeAccreditation = $program->accreditations->where('status', 'Active')->first();

        $activeRisksCount = $program->riskItems->where('status', '!=', 'Mitigated')->count();
        $cumulativeGraduates = $program->graduateRecords->sum('graduates_count');

        // Chronologically sort for Chart.js trend visualization
        $chartGraduates = $program->graduateRecords->sortBy(function($rec) {
            return $rec->school_year . ' ' . ($rec->term === '1st Semester' ? '1' : ($rec->term === '2nd Semester' ? '2' : '3'));
        })->values();

        $graduateLabels = $chartGraduates->map(function($g) {
            return $g->school_year . ' (' . $g->term . ')';
        })->toArray();

        $graduateCounts = $chartGraduates->pluck('graduates_count')->toArray();

        return view('programs.show', compact(
            'role',
            'program',
            'totalCompliance',
            'compliantCount',
            'nonCompliantCount',
            'pendingCount',
            'activeAccreditation',
            'activeRisksCount',
            'cumulativeGraduates',
            'graduateLabels',
            'graduateCounts'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAccess('store');
        $validated = $request->validate([
            'program_code' => 'required|string|unique:programs,program_code|max:15|alpha_num',
            'program_name' => 'required|string|max:255',
            'college_id' => 'required|exists:colleges,college_id',
            'department' => 'nullable|string|max:255',
            'program_level' => 'required|string|max:255',
            'is_accreditable' => 'required|boolean',
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
        $this->enforceAccess('update', $program);
        $validated = $request->validate([
            'program_code' => 'required|string|max:15|alpha_num|unique:programs,program_code,' . $program->program_id . ',program_id',
            'program_name' => 'required|string|max:255',
            'college_id' => 'required|exists:colleges,college_id',
            'department' => 'nullable|string|max:255',
            'program_level' => 'required|string|max:255',
            'is_accreditable' => 'required|boolean',
        ]);

        $validated['program_code'] = strtoupper($validated['program_code']);

        // Preserve the previous program name if it has changed.
        if ($validated['program_name'] !== $program->program_name) {
            $validated['former_name'] = $program->program_name;
        }

        // Preserve the previous department name if it has changed.
        if (isset($validated['department']) && $validated['department'] !== $program->department) {
            $validated['former_department'] = $program->department;
        }

        $program->update($validated);

        return redirect()->route('programs.index')->with('success', 'Academic program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        $this->enforceAccess('destroy', $program);
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Academic program deleted successfully.');
    }

    /**
     * Toggle the accreditable status of a program.
     */
    public function toggleAccreditable($id)
    {
        $program = Program::findOrFail($id);
        $this->enforceAccess('toggleAccreditable', $program);
        $program->is_accreditable = !$program->is_accreditable;
        $program->save();

        $status = $program->is_accreditable ? 'marked as accreditable' : 'marked as non-accreditable';
        return redirect()->back()->with('success', "Program {$program->program_code} successfully {$status}.");
    }
}