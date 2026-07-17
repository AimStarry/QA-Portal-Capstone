<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\RecommendationItem;
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
        $user = auth()->user();

        if ($user->usertype !== 'QA Admin') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }

        $collegeId = $user->college_id;
        $unitName = $user->unit->name ?? '';
        $unitCode = $user->unit->code ?? '';

        $unitFilter = function($q) use ($unitName, $unitCode) {
            $q->where(function($sq) use ($unitName, $unitCode) {
                if ($unitName) $sq->orWhere('responsible_unit', $unitName);
                if ($unitCode) $sq->orWhere('responsible_unit', $unitCode);
            });
        };

        // Query programs based on college if Dean or Principal
        $programsQuery = Program::with('college')->orderBy('program_code');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $programsQuery->where('college_id', $collegeId);
        }
        $programs = $programsQuery->get();

        // Base records for calculating filters, stats and retrieving list
        $baseQuery = ComplianceRecord::query();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $baseQuery->whereHas('program', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            });
        } elseif ($user->usertype === 'Head of Unit') {
            $baseQuery->where($unitFilter);
        }

        // Retrieve registered accrediting bodies, categories, and areas for the dropdown filters
        $bodies = (clone $baseQuery)->distinct()->pluck('accrediting_body')->filter()->sort()->values();
        $categories = (clone $baseQuery)->pluck('category')
            ->flatMap(fn($cat) => preg_split('/[,;]+/', $cat))
            ->map(fn($cat) => trim($cat))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $areas = (clone $baseQuery)->pluck('area')
            ->flatMap(fn($ar) => preg_split('/[,;]+/', $ar))
            ->map(fn($ar) => trim($ar))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Calculate compliance status stats
        $totalCompliance = (clone $baseQuery)->count();
        $compliantCount = (clone $baseQuery)->where('status', 'Compliant')->count();
        $nonCompliantCount = (clone $baseQuery)->where('status', 'Non-Compliant')->count();
        $pendingCount = (clone $baseQuery)->where('status', 'Pending')->count();

        // Query records with recommendation items eager-loaded
        $query = ComplianceRecord::with(['program', 'recommendationItems']);
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->whereHas('program', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            });
        } elseif ($user->usertype === 'Head of Unit') {
            $query->where($unitFilter);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('responsible_unit', 'like', "%{$search}%")
                  ->orWhere('recommendation', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%")
                  ->orWhere('school', 'like', "%{$search}%")
                  ->orWhereHas('program', function($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Dropdown Accrediting Body Filter
        if ($request->filled('body')) {
            $query->where('accrediting_body', $request->input('body'));
        }

        // Dropdown Category Filter
        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->input('category') . '%');
        }

        // Dropdown Area Filter
        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->input('area') . '%');
        }

        // Dropdown Priority Filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Dropdown Unit or Department Filter
        if ($request->filled('responsible_unit')) {
            $query->where('responsible_unit', $request->input('responsible_unit'));
        }

        $complianceRecords = $query->orderBy('due_date', 'asc')->get();

        $pendingApprovals = ($role === 'QA Admin')
            ? ComplianceRecord::with(['program', 'recommendationItems'])->where('approval_state', 'Pending Approval')->orderBy('updated_at', 'desc')->get()
            : collect();

        $responsibleUnits = ComplianceRecord::distinct()->pluck('responsible_unit')->filter()->sort()->values();
        $dbResponsibleUnits = \App\Models\ResponsibleUnit::with(['laboratories', 'users'])->orderBy('name')->get();
        $colleges = \App\Models\College::orderBy('name')->get();
        $units = \App\Models\Unit::orderBy('name')->get();
        $dbAccreditingBodies = \App\Models\AccreditingBody::orderBy('code')->get();

        $contactsMap = [];
        $usersForMap = \App\Models\User::whereIn('usertype', ['Dean', 'Head of Unit', 'Principal'])->with(['college', 'unit'])->get();
        foreach ($usersForMap as $u) {
            if (($u->usertype === 'Dean' || $u->usertype === 'Principal') && $u->college) {
                $contactsMap[$u->college->name] = [
                    'name' => $u->name,
                    'email' => $u->email ?? ($u->username . '@hau.edu.ph'),
                ];
            } elseif ($u->usertype === 'Head of Unit' && $u->unit) {
                $contactsMap[$u->unit->name] = [
                    'name' => $u->name,
                    'email' => $u->email ?? ($u->username . '@hau.edu.ph'),
                ];
            }
        }

        $recentQuery = RecommendationItem::with(['complianceRecord.program'])
            ->where('is_completed', true);

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $recentQuery->whereHas('complianceRecord.program', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            });
        } elseif ($user->usertype === 'Head of Unit') {
            $recentQuery->whereHas('complianceRecord', $unitFilter);
        }

        $recentlyCompletedRecommendations = $recentQuery
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        $summaryProgramsQuery = Program::query();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $summaryProgramsQuery->where('college_id', $collegeId)
                ->with(['complianceRecords.recommendationItems']);
        } elseif ($user->usertype === 'Head of Unit') {
            $summaryProgramsQuery->whereHas('complianceRecords', $unitFilter)
                ->with(['complianceRecords' => $unitFilter, 'complianceRecords.recommendationItems']);
        } else {
            $summaryProgramsQuery->with(['complianceRecords.recommendationItems']);
        }

        $programComplianceSummary = $summaryProgramsQuery->get()->map(function($p) {
            $totalRecommendations = 0;
            $completedRecommendations = 0;
            foreach ($p->complianceRecords as $record) {
                $totalRecommendations += $record->recommendationItems->count();
                $completedRecommendations += $record->recommendationItems->where('is_completed', true)->count();
            }
            $completionRate = $totalRecommendations > 0 ? round(($completedRecommendations / $totalRecommendations) * 100) : 0;
            
            if ($totalRecommendations === 0) {
                $status = 'No Tasks';
            } elseif ($completionRate === 100) {
                $status = 'Compliant';
            } else {
                $status = 'In Progress';
            }

            return (object)[
                'code' => $p->program_code,
                'name' => $p->program_name,
                'total' => $totalRecommendations,
                'completed' => $completedRecommendations,
                'rate' => $completionRate,
                'status' => $status,
            ];
        })->filter(fn($p) => $p->total > 0)->sortByDesc('rate')->values();

        return view('compliance.index', compact(
            'role',
            'complianceRecords',
            'programs',
            'bodies',
            'categories',
            'areas',
            'totalCompliance',
            'compliantCount',
            'nonCompliantCount',
            'pendingCount',
            'pendingApprovals',
            'responsibleUnits',
            'dbResponsibleUnits',
            'colleges',
            'units',
            'recentlyCompletedRecommendations',
            'programComplianceSummary',
            'contactsMap',
            'dbAccreditingBodies'
        ));
    }

    /**
     * Store a newly created resource in storage. (Admin or Responsible Unit)
     */
    public function store(Request $request)
    {
        if ($request->filled('laboratory_id')) {
            $lab = \App\Models\Laboratory::find($request->laboratory_id);
            if ($lab) {
                $request->merge(['categories' => [$lab->name]]);
            }
        }
        if (!$request->filled('categories') && !$request->filled('laboratory_id')) {
            $request->merge(['categories' => ['General']]);
        }
        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) {
                $request->merge(['responsible_unit' => $ru->name]);
            }
        }

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'priority' => ['nullable', Rule::in(['Critical', 'High', 'Medium', 'Low'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'responsible_unit_id' => 'required|exists:responsible_units,responsible_unit_id',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'document_link' => 'nullable|url',
            'accrediting_body' => 'required|string|max:255',
            'school' => 'required|string|max:255',
            'recommendation' => 'nullable|string',
            'recommendations' => 'required|array|min:1',
            'recommendations.*' => 'required|string|max:1000',
            'categories' => 'required|array|min:1',
            'categories.*' => 'required|string|max:255',
            'laboratory_id' => 'nullable|exists:laboratories,laboratory_id',
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'visit_date' => 'nullable|date',
        ]);

        $validated['category'] = implode(', ', array_map('trim', $validated['categories']));
        unset($validated['categories']);

        $validated['area'] = implode(', ', array_map('trim', $validated['areas']));
        unset($validated['areas']);

        // Default contact person to matched Head of Unit or Dean if left empty:
        if (empty($validated['contact_person'])) {
            $assignedUser = null;
            if (!empty($validated['responsible_unit'])) {
                $unit = \App\Models\Unit::where('name', $validated['responsible_unit'])
                    ->orWhere('code', $validated['responsible_unit'])
                    ->first();
                if ($unit) {
                    $assignedUser = \App\Models\User::where('usertype', 'Head of Unit')
                        ->where('unit_id', $unit->unit_id)
                        ->first();
                }
            }
            if (!$assignedUser && !empty($validated['program_id'])) {
                $program = \App\Models\Program::find($validated['program_id']);
                if ($program && $program->college_id) {
                    $assignedUser = \App\Models\User::where('usertype', 'Dean')
                        ->where('college_id', $program->college_id)
                        ->first();
                }
            }
            if ($assignedUser) {
                $validated['contact_person'] = $assignedUser->name;
                $validated['contact_email'] = $assignedUser->email ?? ($assignedUser->username . '@hau.edu.ph');
            }
        }

        $role = session('active_role', 'QA Admin');
        if ($role === 'Responsible Unit') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }

        // Set the legacy recommendation field to a summary
        $validated['recommendation'] = implode('; ', $validated['recommendations']);

        if ($role === 'Unit or Department') {
            $validated['approval_state'] = 'Pending Approval';
            $validated['pending_status'] = 'Compliant';
            $validated['pending_document_link'] = $validated['document_link'] ?? null;
            $validated['status'] = 'Pending';
            $validated['document_link'] = null;
            $validated['workflow_stage'] = 'admin_reviewing'; // RU logs → immediately needs admin review
            $message = 'Compliance task logged and submitted to QA Admin for approval.';
        } else {
            $validated['approval_state'] = 'None';
            $validated['workflow_stage'] = 'recommendation_created'; // Admin logs → recommendation created
            $message = 'Compliance task logged successfully.';
        }

        // Remove recommendations array before creating the record
        $recommendations = $validated['recommendations'];
        unset($validated['recommendations']);

        $compliance = ComplianceRecord::create($validated);

        // Warning Alert logic for active accreditations on deficient program
        $program = Program::find($compliance->program_id);
        if ($program && $compliance->status !== 'Compliant') {
            $hasActiveAccreditation = $program->accreditations()->where('status', 'Active')->exists();
            if ($hasActiveAccreditation) {
                session()->flash('warning', "Warning: Logging a deficient compliance task has flagged {$program->program_code} as non-accreditable. Future accreditation roadmaps or status updates are locked until all compliance items are met.");
            }
        }

        // Create individual recommendation checklist items
        foreach ($recommendations as $text) {
            $text = trim($text);
            if (!empty($text)) {
                $compliance->recommendationItems()->create([
                    'text' => $text,
                    'is_completed' => false,
                ]);
            }
        }

        return redirect()->route('compliance.index')->with('success', $message);
    }

    /**
     * Update the specified resource in storage. (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);

        if ($request->filled('laboratory_id')) {
            $lab = \App\Models\Laboratory::find($request->laboratory_id);
            if ($lab) {
                $request->merge(['categories' => [$lab->name]]);
            }
        }
        if (!$request->filled('categories') && !$request->filled('laboratory_id')) {
            $request->merge(['categories' => ['General']]);
        }
        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) {
                $request->merge(['responsible_unit' => $ru->name]);
            }
        }

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,program_id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'priority' => ['nullable', Rule::in(['Critical', 'High', 'Medium', 'Low'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'responsible_unit_id' => 'required|exists:responsible_units,responsible_unit_id',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'document_link' => 'nullable|url',
            'accrediting_body' => 'required|string|max:255',
            'school' => 'required|string|max:255',
            'recommendation' => 'nullable|string',
            'recommendations' => 'required|array|min:1',
            'recommendations.*' => 'required|string|max:1000',
            'categories' => 'required|array|min:1',
            'categories.*' => 'required|string|max:255',
            'laboratory_id' => 'nullable|exists:laboratories,laboratory_id',
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'visit_date' => 'nullable|date',
        ]);

        $validated['category'] = implode(', ', array_map('trim', $validated['categories']));
        unset($validated['categories']);

        $validated['area'] = implode(', ', array_map('trim', $validated['areas']));
        unset($validated['areas']);

        // Default contact person to matched Head of Unit or Dean if left empty:
        if (empty($validated['contact_person'])) {
            $assignedUser = null;
            if (!empty($validated['responsible_unit'])) {
                $unit = \App\Models\Unit::where('name', $validated['responsible_unit'])
                    ->orWhere('code', $validated['responsible_unit'])
                    ->first();
                if ($unit) {
                    $assignedUser = \App\Models\User::where('usertype', 'Head of Unit')
                        ->where('unit_id', $unit->unit_id)
                        ->first();
                }
            }
            if (!$assignedUser && !empty($validated['program_id'])) {
                $program = \App\Models\Program::find($validated['program_id']);
                if ($program && $program->college_id) {
                    $assignedUser = \App\Models\User::where('usertype', 'Dean')
                        ->where('college_id', $program->college_id)
                        ->first();
                }
            }
            if ($assignedUser) {
                $validated['contact_person'] = $assignedUser->name;
                $validated['contact_email'] = $assignedUser->email ?? ($assignedUser->username . '@hau.edu.ph');
            }
        }

        $validated['approval_state'] = 'None';
        $validated['rejection_reason'] = null;

        // Update legacy recommendation summary
        $validated['recommendation'] = implode('; ', $validated['recommendations']);

        $recommendations = $validated['recommendations'];
        unset($validated['recommendations']);

        $compliance->update($validated);

        // Warning Alert logic for active accreditations on deficient program
        $program = Program::find($compliance->program_id);
        if ($program && $compliance->status !== 'Compliant') {
            $hasActiveAccreditation = $program->accreditations()->where('status', 'Active')->exists();
            if ($hasActiveAccreditation) {
                session()->flash('warning', "Warning: Changing this compliance task status to deficient has flagged {$program->program_code} as non-accreditable. Future accreditation roadmaps or status updates are locked until all compliance items are met.");
            }
        }

        // Sync recommendation items: delete old, create new
        $compliance->recommendationItems()->delete();
        foreach ($recommendations as $text) {
            $text = trim($text);
            if (!empty($text)) {
                $compliance->recommendationItems()->create([
                    'text' => $text,
                    'is_completed' => false,
                ]);
            }
        }

        return redirect()->route('compliance.index')->with('success', 'Compliance task updated successfully.');
    }

    /**
     * Propose an update to status or document link (Responsible Unit only)
     */
    public function submitUpdate(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) {
                $request->merge(['responsible_unit' => $ru->name]);
            }
        }

        $validated = $request->validate([
            'pending_document_link' => 'required|url',
            'action_plan' => 'required|string',
            'responsible_unit' => 'nullable|string|max:255',
            'responsible_unit_id' => 'nullable|exists:responsible_units,responsible_unit_id',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
        ]);

        $compliance->update([
            'pending_status' => 'Compliant',
            'pending_document_link' => $validated['pending_document_link'],
            'action_plan' => $validated['action_plan'],
            'responsible_unit' => $validated['responsible_unit'] ?? $compliance->responsible_unit,
            'responsible_unit_id' => $validated['responsible_unit_id'] ?? $compliance->responsible_unit_id,
            'contact_person' => $validated['contact_person'] ?? $compliance->contact_person,
            'contact_email' => $validated['contact_email'] ?? $compliance->contact_email,
            'approval_state' => 'Pending Approval',
            'workflow_stage' => 'admin_reviewing', // Action plan submitted → moves to admin review stage
            'rejection_reason' => null, // Clear old rejection reason
        ]);

        return redirect()->route('compliance.index')->with('success', 'Action plan submitted. Awaiting QA Admin review and approval.');
    }

    /**
     * Approve the proposed update (Admin only)
     */
    public function approve(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);

        if ($compliance->approval_state !== 'Pending Approval') {
            return redirect()->route('dashboard')->with('error', 'This record does not have a pending approval.');
        }

        // Apply proposed changes — advance to final Compliant stage
        $compliance->update([
            'status' => 'Compliant',
            'document_link' => $compliance->pending_document_link,
            'pending_status' => null,
            'pending_document_link' => null,
            'approval_state' => 'None',
            'rejection_reason' => null,
            'workflow_stage' => 'compliant', // Final stage: Admin approved → Compliant
        ]);

        return redirect()->back()->with('success', 'Compliance update approved. Status updated to Compliant.');
    }

    /**
     * Reject the proposed update (Admin only)
     */
    public function reject(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);

        if ($compliance->approval_state !== 'Pending Approval') {
            return redirect()->route('dashboard')->with('error', 'This record does not have a pending approval.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        // Retain proposed changes in pending columns so the unit can see/edit them, but mark as Rejected
        // Reset workflow_stage to recommendation_created so the unit can revise and resubmit
        $compliance->update([
            'approval_state' => 'Rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'workflow_stage' => 'recommendation_created', // Rejected → back to start for resubmission
        ]);

        // Warning Alert logic for active accreditations on deficient program
        $program = $compliance->program;
        if ($program) {
            $hasActiveAccreditation = $program->accreditations()->where('status', 'Active')->exists();
            if ($hasActiveAccreditation) {
                session()->flash('warning', "Warning: Rejecting this compliance task has flagged {$program->program_code} as non-accreditable. Future accreditation roadmaps or status updates are locked until all compliance items are met.");
            }
        }

        return redirect()->back()->with('success', 'Compliance update rejected. Unit will be prompted to revise.');
    }

    /**
     * Toggle a recommendation item's completion status (AJAX).
     */
    public function toggleRecommendation(Request $request, $id)
    {
        $item = RecommendationItem::findOrFail($id);

        $item->update([
            'is_completed' => !$item->is_completed,
            'completed_at' => !$item->is_completed ? now() : null,
        ]);

        $compliance = $item->complianceRecord;

        // Log notification to QA Admin if toggled by a Unit or Department
        $role = session('active_role', 'QA Admin');
        if ($role === 'Unit or Department' || $role === 'Responsible Unit') {
            if ($item->is_completed) {
                \App\Models\Notification::create([
                    'type' => 'recommendation_completed',
                    'message' => "Unit checked off recommendation: \"" . $item->text . "\" on compliance task \"" . $compliance->title . "\" (Program: " . ($compliance->program->program_code ?? 'N/A') . ")",
                    'link' => route('compliance.index'),
                    'is_read' => false,
                ]);
            }
        }

        // Recalculate stats for the parent compliance record
        $total = $compliance->recommendationItems()->count();
        $completed = $compliance->recommendationItems()->where('is_completed', true)->count();
        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;

        return response()->json([
            'success' => true,
            'is_completed' => $item->is_completed,
            'completed_count' => $completed,
            'total_count' => $total,
            'completion_rate' => $rate,
        ]);
    }

    /**
     * Update a recommendation item's evidence link (AJAX).
     */


    /**
     * Remove the specified resource from storage. (Admin only)
     */
    public function destroy($id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);
        $compliance->delete();

        return redirect()->route('compliance.index')->with('success', 'Compliance task deleted successfully.');
    }

    /**
     * Export compliance records to CSV based on current filters.
     */
    public function exportCsv(Request $request)
    {
        // Enforce the same role scoping as index() — users only export what they can see
        $user = auth()->user();
        $collegeId = $user->college_id;
        $unitName = $user->unit->name ?? '';
        $unitCode = $user->unit->code ?? '';

        $query = ComplianceRecord::with(['program', 'recommendationItems']);

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->whereHas('program', function ($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            });
        } elseif ($user->usertype === 'Head of Unit') {
            $query->where(function ($q) use ($unitName, $unitCode) {
                if ($unitName) $q->orWhere('responsible_unit', $unitName);
                if ($unitCode) $q->orWhere('responsible_unit', $unitCode);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('responsible_unit', 'like', "%{$search}%")
                  ->orWhere('recommendation', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%")
                  ->orWhere('school', 'like', "%{$search}%")
                  ->orWhereHas('program', function($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('body')) {
            $query->where('accrediting_body', $request->input('body'));
        }

        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->input('category') . '%');
        }

        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->input('area') . '%');
        }

        $records = $query->orderBy('due_date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="compliance_report_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'ID', 
                'Academic Program', 
                'Accrediting Body', 
                'School/College', 
                'Task Title', 
                'Area', 
                'Category', 
                'Priority', 
                'Status', 
                'Due Date', 
                'Responsible Unit', 
                'Compliance Rate (%)', 
                'Total Recommendations', 
                'Completed Recommendations'
            ]);

            foreach ($records as $r) {
                $total = $r->recommendationItems->count();
                $completed = $r->recommendationItems->where('is_completed', true)->count();
                $rate = $total > 0 ? round(($completed / $total) * 100) : 0;

                fputcsv($file, [
                    $r->compliance_record_id,
                    $r->program->program_code . ' - ' . $r->program->program_name,
                    $r->accrediting_body,
                    $r->school,
                    $r->title,
                    $r->area,
                    $r->category,
                    $r->priority,
                    $r->status,
                    $r->due_date ? $r->due_date->format('Y-m-d') : 'N/A',
                    $r->responsible_unit ?? 'Unassigned',
                    $rate,
                    $total,
                    $completed
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
