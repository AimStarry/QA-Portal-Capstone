<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $role = session('active_role', 'QA Admin');
        $programs = Program::orderBy('program_code')->get();

        // Retrieve registered accrediting bodies for the dropdown filter (e.g. PAASCU, PACUCOA)
        $bodies = Accreditation::distinct()->pluck('accrediting_body');

        // Calculate compliance status stats
        $totalCompliance = ComplianceRecord::count();
        $compliantCount = ComplianceRecord::where('status', 'Compliant')->count();
        $nonCompliantCount = ComplianceRecord::where('status', 'Non-Compliant')->count();
        $pendingCount = ComplianceRecord::where('status', 'Pending')->count();

        // Query records
        $query = ComplianceRecord::with('program');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('responsible_unit', 'like', "%{$search}%")
                  ->orWhereHas('program', function($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Dropdown Accrediting Body Filter (Upgraded!)
        if ($request->filled('body')) {
            $body = $request->input('body');
            // Filter compliance tasks associated with programs accredited by the selected body
            $programIds = Accreditation::where('accrediting_body', $body)->pluck('program_id');
            $query->whereIn('program_id', $programIds);
        }

        $complianceRecords = $query->orderBy('due_date', 'asc')->get();

        return view('compliance.index', compact(
            'role',
            'complianceRecords',
            'programs',
            'bodies',
            'totalCompliance',
            'compliantCount',
            'nonCompliantCount',
            'pendingCount'
        ));
    }

    /**
     * Store a newly created resource in storage. (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'document_link' => 'nullable|url',
        ]);

        $validated['approval_state'] = 'None';

        ComplianceRecord::create($validated);

        return redirect()->route('compliance.index')->with('success', 'Compliance task logged successfully.');
    }

    /**
     * Update the specified resource in storage. (Admin only)
     */
    public function update(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'document_link' => 'nullable|url',
        ]);

        $validated['approval_state'] = 'None';
        $validated['rejection_reason'] = null;

        $compliance->update($validated);

        return redirect()->route('compliance.index')->with('success', 'Compliance task updated successfully.');
    }

    /**
     * Propose an update to status or document link (Responsible Unit only)
     */
    public function submitUpdate(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        $validated = $request->validate([
            'pending_status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'pending_document_link' => 'required|url',
        ]);

        $compliance->update([
            'pending_status' => $validated['pending_status'],
            'pending_document_link' => $validated['pending_document_link'],
            'approval_state' => 'Pending Approval',
            'rejection_reason' => null, // Clear old rejection reason
        ]);

        return redirect()->route('compliance.index')->with('success', 'Update proposal submitted to QA Admin for approval.');
    }

    /**
     * Approve the proposed update (Admin only)
     */
    public function approve(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        if ($compliance->approval_state !== 'Pending Approval') {
            return redirect()->route('dashboard')->with('error', 'This record does not have a pending approval.');
        }

        // Apply proposed changes
        $compliance->update([
            'status' => $compliance->pending_status,
            'document_link' => $compliance->pending_document_link,
            'pending_status' => null,
            'pending_document_link' => null,
            'approval_state' => 'None',
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Compliance update approved successfully.');
    }

    /**
     * Reject the proposed update (Admin only)
     */
    public function reject(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        if ($compliance->approval_state !== 'Pending Approval') {
            return redirect()->route('dashboard')->with('error', 'This record does not have a pending approval.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        // Retain proposed changes in pending columns so the unit can see/edit them, but mark as Rejected
        $compliance->update([
            'approval_state' => 'Rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->back()->with('success', 'Compliance update rejected.');
    }

    /**
     * Remove the specified resource from storage. (Admin only)
     */
    public function destroy($id)
    {
        $compliance = ComplianceRecord::findOrFail($id);
        $compliance->delete();

        return redirect()->route('compliance.index')->with('success', 'Compliance task deleted successfully.');
    }
}
