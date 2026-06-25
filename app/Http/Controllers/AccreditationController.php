<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccreditationController extends Controller
{
    /**
     * Display a listing of the resource (Dashboard + list).
     */
    public function index(Request $request)
    {
        $programs = Program::orderBy('program_code')->get();

        // Calculate dashboard stats
        $totalPrograms = Program::count();
        $activeAccreditations = Accreditation::where('status', 'Active')->count();
        
        // PAASCU Accredited Programs: unique programs with a PAASCU accreditation
        $paascuProgramsCount = Program::whereHas('accreditations', function ($query) {
            $query->where('accrediting_body', 'PAASCU');
        })->count();

        $expiringOrExpired = Accreditation::whereIn('status', ['Expiring Soon', 'Expired'])->count();

        $totalAccreditations = Accreditation::count();
        $localAccreditations = Accreditation::where('type', 'Local')->count();
        $intlAccreditations = Accreditation::where('type', 'International')->count();
        $regulatoryAccreditations = Accreditation::where('type', 'Regulatory')->count();

        $localPercentage = $totalAccreditations > 0 ? round(($localAccreditations / $totalAccreditations) * 100) : 0;
        $intlPercentage = $totalAccreditations > 0 ? round(($intlAccreditations / $totalAccreditations) * 100) : 0;
        $regulatoryPercentage = $totalAccreditations > 0 ? round(($regulatoryAccreditations / $totalAccreditations) * 100) : 0;

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

        return view('accreditations.index', compact(
            'accreditations',
            'programs',
            'totalPrograms',
            'activeAccreditations',
            'paascuProgramsCount',
            'expiringOrExpired',
            'localPercentage',
            'intlPercentage',
            'regulatoryPercentage'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'accrediting_body' => 'required|string|max:255',
            'type' => ['required', Rule::in(['Local', 'International', 'Regulatory'])],
            'level_or_tier' => 'nullable|string|max:255',
            'last_visit' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => ['required', Rule::in(['Active', 'Expiring Soon', 'Expired', 'Pending'])],
        ]);

        Accreditation::create($validated);

        return redirect()->route('accreditations.index')->with('success', 'Accreditation added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Accreditation $accreditation)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'accrediting_body' => 'required|string|max:255',
            'type' => ['required', Rule::in(['Local', 'International', 'Regulatory'])],
            'level_or_tier' => 'nullable|string|max:255',
            'last_visit' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => ['required', Rule::in(['Active', 'Expiring Soon', 'Expired', 'Pending'])],
        ]);

        $accreditation->update($validated);

        return redirect()->route('accreditations.index')->with('success', 'Accreditation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accreditation $accreditation)
    {
        $accreditation->delete();

        return redirect()->route('accreditations.index')->with('success', 'Accreditation deleted successfully.');
    }
}
