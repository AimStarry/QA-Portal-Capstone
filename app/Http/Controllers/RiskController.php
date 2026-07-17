<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\RiskItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiskController extends Controller
{
    /**
     * Enforce access control.
     */
    private function enforceAccess($action)
    {
        $user = auth()->user();
        if ($user->usertype === 'Head of Unit') {
            abort(403, 'Unauthorized action. Support Units do not have access to Risk Monitor.');
        }

        // Restrict write actions to QA Admin only
        if (in_array($action, ['store', 'update', 'destroy'])) {
            if ($user->usertype !== 'QA Admin') {
                abort(403, 'Unauthorized action. Only QA Admins can manage Risk Monitor.');
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->enforceAccess('index');
        $user = auth()->user();
        $collegeId = $user->college_id;

        // Query programs based on college if Dean or Principal
        $programsQuery = Program::orderBy('program_code');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $programsQuery->where('college_id', $collegeId);
        }
        $programs = $programsQuery->get();

        // Calculate risk status counts
        $totalRisksQuery = RiskItem::query();
        $identifiedQuery = RiskItem::where('status', 'Identified');
        $mitigatedQuery = RiskItem::where('status', 'Mitigated');
        $monitoringQuery = RiskItem::where('status', 'Monitoring');

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $totalRisksQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
            $identifiedQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
            $mitigatedQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
            $monitoringQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }

        $totalRisks = $totalRisksQuery->count();
        $identifiedCount = $identifiedQuery->count();
        $mitigatedCount = $mitigatedQuery->count();
        $monitoringCount = $monitoringQuery->count();

        // Calculate Matrix counts for visual representation
        // Matrix grid: [Likelihood][Impact]
        $matrix = [
            'High' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
            'Medium' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
            'Low' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
        ];

        $matrixQuery = RiskItem::query();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $matrixQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }
        $risksForMatrix = $matrixQuery->get();

        foreach ($risksForMatrix as $r) {
            $l = $r->likelihood;
            $i = $r->impact;
            if (isset($matrix[$l][$i])) {
                $matrix[$l][$i]++;
            }
        }

        // Query records
        $query = RiskItem::with('program');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('mitigation_plan', 'like', "%{$search}%")
                  ->orWhereHas('program', function($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $riskItems = $query->orderBy('status', 'asc')->get();

        return view('risk.index', compact(
            'riskItems',
            'programs',
            'totalRisks',
            'identifiedCount',
            'mitigatedCount',
            'monitoringCount',
            'matrix'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAccess('store'); // enforceAccess() already checks usertype from database
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'description' => 'required|string',
            'likelihood' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'impact' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'mitigation_plan' => 'nullable|string',
            'status' => ['required', Rule::in(['Identified', 'Mitigated', 'Monitoring'])],
        ]);

        RiskItem::create($validated);

        return redirect()->route('risk.index')->with('success', 'QA Risk profile logged successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->enforceAccess('update'); // enforceAccess() already checks usertype from database

        $risk = RiskItem::findOrFail($id);
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'description' => 'required|string',
            'likelihood' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'impact' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'mitigation_plan' => 'nullable|string',
            'status' => ['required', Rule::in(['Identified', 'Mitigated', 'Monitoring'])],
        ]);

        $risk->update($validated);

        return redirect()->route('risk.index')->with('success', 'QA Risk profile updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->enforceAccess('destroy'); // enforceAccess() already checks usertype from database

        $risk = RiskItem::findOrFail($id);
        $risk->delete();

        return redirect()->route('risk.index')->with('success', 'QA Risk profile removed successfully.');
    }
}
