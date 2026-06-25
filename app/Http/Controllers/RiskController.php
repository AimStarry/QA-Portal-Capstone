<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\RiskItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $programs = Program::orderBy('program_code')->get();

        // Calculate risk status counts
        $totalRisks = RiskItem::count();
        $identifiedCount = RiskItem::where('status', 'Identified')->count();
        $mitigatedCount = RiskItem::where('status', 'Mitigated')->count();
        $monitoringCount = RiskItem::where('status', 'Monitoring')->count();

        // Calculate Matrix counts for visual representation
        // Matrix grid: [Likelihood][Impact]
        $matrix = [
            'High' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
            'Medium' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
            'Low' => ['Low' => 0, 'Medium' => 0, 'High' => 0],
        ];

        $risksForMatrix = RiskItem::all();
        foreach ($risksForMatrix as $r) {
            $l = $r->likelihood;
            $i = $r->impact;
            if (isset($matrix[$l][$i])) {
                $matrix[$l][$i]++;
            }
        }

        // Query records
        $query = RiskItem::with('program');

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
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
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
        $risk = RiskItem::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
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
        $risk = RiskItem::findOrFail($id);
        $risk->delete();

        return redirect()->route('risk.index')->with('success', 'QA Risk profile removed successfully.');
    }
}
