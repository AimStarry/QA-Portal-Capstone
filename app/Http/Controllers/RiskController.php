<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\RiskItem;
use App\Models\User;
use App\Mail\QaAdminAlertMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

        $role = session('active_role', 'QA Admin');
        if ($user->usertype !== 'QA Admin') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }

        return view('risk.index', compact(
            'role',
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

        $risk = RiskItem::create($validated);
        $risk->load('program');

        // Dispatch email notification to QA Admins
        $recipients = User::getQaAdminRecipients();
        foreach ($recipients as $recipient) {
            try {
                $badgeType = match($risk->likelihood) {
                    'High' => 'danger',
                    'Medium' => 'warning',
                    default => 'info',
                };

                Mail::to($recipient->email)->send(new QaAdminAlertMail(
                    subjectTitle: "[QA Portal] New Risk Profile: " . ($risk->program?->program_code ?? 'General') . " - {$risk->status}",
                    badge: "Risk: {$risk->status}",
                    headline: 'New QA Risk Profile Logged',
                    messageBody: "A new risk item has been logged for academic program \"{$risk->program?->program_name}\" (" . ($risk->program?->program_code ?? 'N/A') . ").",
                    details: [
                        'Program' => ($risk->program?->program_code ? "[{$risk->program->program_code}] " : '') . ($risk->program?->program_name ?? 'N/A'),
                        'Risk Description' => $risk->description,
                        'Likelihood / Impact' => "{$risk->likelihood} Likelihood / {$risk->impact} Impact",
                        'Status' => $risk->status,
                        'Mitigation Plan' => $risk->mitigation_plan ?? 'None specified',
                    ],
                    actionUrl: route('risk.index'),
                    actionText: 'Open Risk Monitor',
                    badgeType: $badgeType
                ));
            } catch (\Throwable $e) {
                Log::error("Failed to send risk logged email to QA Admin ({$recipient->email}): " . $e->getMessage());
            }
        }

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
        $risk->load('program');

        // Dispatch email notification to QA Admins
        $recipients = User::getQaAdminRecipients();
        foreach ($recipients as $recipient) {
            try {
                $badgeType = match($risk->likelihood) {
                    'High' => 'danger',
                    'Medium' => 'warning',
                    default => 'info',
                };

                Mail::to($recipient->email)->send(new QaAdminAlertMail(
                    subjectTitle: "[QA Portal] Risk Profile Updated: " . ($risk->program?->program_code ?? 'General') . " - {$risk->status}",
                    badge: "Risk: {$risk->status}",
                    headline: 'QA Risk Profile Updated',
                    messageBody: "The risk profile for \"{$risk->program?->program_name}\" (" . ($risk->program?->program_code ?? 'N/A') . ") was updated.",
                    details: [
                        'Program' => ($risk->program?->program_code ? "[{$risk->program->program_code}] " : '') . ($risk->program?->program_name ?? 'N/A'),
                        'Risk Description' => $risk->description,
                        'Likelihood / Impact' => "{$risk->likelihood} Likelihood / {$risk->impact} Impact",
                        'Status' => $risk->status,
                        'Mitigation Plan' => $risk->mitigation_plan ?? 'None specified',
                    ],
                    actionUrl: route('risk.index'),
                    actionText: 'Open Risk Monitor',
                    badgeType: $badgeType
                ));
            } catch (\Throwable $e) {
                Log::error("Failed to send risk updated email to QA Admin ({$recipient->email}): " . $e->getMessage());
            }
        }

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

    /**
     * Remove multiple specified resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $this->enforceAccess('destroy');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:risk_items,risk_item_id',
        ]);

        $count = RiskItem::whereKey($validated['ids'])->delete();

        return redirect()->route('risk.index')->with('success', "{$count} QA risk profiles removed successfully.");
    }
}
