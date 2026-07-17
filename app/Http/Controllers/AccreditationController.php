<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\RecommendationItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\StoreAccreditationRequest;
use App\Http\Requests\UpdateAccreditationRequest;

class AccreditationController extends Controller
{
    /**
     * Enforce QA Admin role.
     */
    private function enforceAdmin()
    {
        if (!auth()->check() || auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action. Only QA Admins can access accreditations.');
        }
    }

    /**
     * Display a listing of the resource (Dashboard + list).
     */
    public function index(Request $request)
    {
        $this->enforceAdmin();
        $programs = Program::orderBy('program_code')->get();

        // Calculate dashboard stats
        $totalPrograms = Program::count();
        $activeAccreditations = Accreditation::where('status', 'Active')->count();
        
        // PAASCU Accredited Programs: unique programs with a PAASCU accreditation
        $paascuProgramsCount = Program::whereHas('accreditations', function ($query) {
            $query->where('accrediting_body', 'PAASCU');
        })->count();

        $expiringOrExpired = Accreditation::whereIn('status', ['Expiring Soon', 'Expired'])->count();

        // Calculate dynamic recommendation accomplishment rates by type (Local, International, Regulatory)
        $localBodies = Accreditation::where('type', 'Local')->distinct()->pluck('accrediting_body')->toArray();
        $localRecords = ComplianceRecord::whereIn('accrediting_body', $localBodies)->pluck('compliance_record_id');
        $localTotalItems = RecommendationItem::whereIn('compliance_record_id', $localRecords)->count();
        $localCompleted = RecommendationItem::whereIn('compliance_record_id', $localRecords)->where('is_completed', true)->count();
        $localPercentage = $localTotalItems > 0 ? round(($localCompleted / $localTotalItems) * 100) : 0;

        $intlBodies = Accreditation::where('type', 'International')->distinct()->pluck('accrediting_body')->toArray();
        $intlRecords = ComplianceRecord::whereIn('accrediting_body', $intlBodies)->pluck('compliance_record_id');
        $intlTotalItems = RecommendationItem::whereIn('compliance_record_id', $intlRecords)->count();
        $intlCompleted = RecommendationItem::whereIn('compliance_record_id', $intlRecords)->where('is_completed', true)->count();
        $intlPercentage = $intlTotalItems > 0 ? round(($intlCompleted / $intlTotalItems) * 100) : 0;

        $regBodies = Accreditation::where('type', 'Regulatory')->distinct()->pluck('accrediting_body')->toArray();
        $regRecords = ComplianceRecord::whereIn('accrediting_body', $regBodies)->pluck('compliance_record_id');
        $regTotalItems = RecommendationItem::whereIn('compliance_record_id', $regRecords)->count();
        $regCompleted = RecommendationItem::whereIn('compliance_record_id', $regRecords)->where('is_completed', true)->count();
        $regulatoryPercentage = $regTotalItems > 0 ? round(($regCompleted / $regTotalItems) * 100) : 0;

        // Start querying accreditations with program relation
        $query = Accreditation::with('program');

        // Apply server-side filters if query params are present
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('accrediting_body', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('level_or_tier', 'like', "%{$search}%")
                  ->orWhereHas('program', function ($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%")
                        ->orWhere('program_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->input('paascu') == '1') {
            $query->where('accrediting_body', 'PAASCU');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $accreditations = $query->orderBy('expiry_date', 'asc')->get();
        $accreditingBodies = \App\Models\AccreditingBody::orderBy('code')->get();

        return view('accreditations.index', compact(
            'accreditations',
            'programs',
            'totalPrograms',
            'activeAccreditations',
            'paascuProgramsCount',
            'expiringOrExpired',
            'localPercentage',
            'intlPercentage',
            'regulatoryPercentage',
            'accreditingBodies'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccreditationRequest $request)
    {
        $validated = $request->validated();

        Accreditation::create($validated);

        return redirect()->route('accreditations.index')->with('success', 'Accreditation added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccreditationRequest $request, Accreditation $accreditation)
    {
        $validated = $request->validated();

        $accreditation->update($validated);

        return redirect()->route('accreditations.index')->with('success', 'Accreditation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accreditation $accreditation)
    {
        $this->enforceAdmin();
        $accreditation->delete();

        return redirect()->route('accreditations.index')->with('success', 'Accreditation deleted successfully.');
    }
}
