<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use App\Models\RecommendationItem;
use App\Models\Notification;
use App\Models\User;
use App\Mail\QaAdminAlertMail;
use App\Services\ComplianceService;
use App\Services\RiskAutoLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ComplianceController extends Controller
{
    public function __construct(private ComplianceService $service) {}

    private function userScopeFilter(object $user): \Closure
    {
        // Resolve the user's department/unit ID and its parent (school) ID
        $userUnitId   = $user->responsible_unit_id ?? $user->unit_id ?? null;
        $unitName     = $user->unit->name ?? '';
        $unitCode     = $user->unit->code ?? '';

        // Also pull the name from the ResponsibleUnit record if available
        $ru = $userUnitId ? \App\Models\ResponsibleUnit::with('parent')->find($userUnitId) : null;
        $ruName   = $ru->name ?? $unitName;
        $ruCode   = $ru->code ?? $unitCode;

        return function ($q) use ($userUnitId, $ruName, $ruCode) {
            $q->where(function ($sq) use ($userUnitId, $ruName, $ruCode) {
                // Always show items targeted at "all units"
                $sq->orWhere('responsible_unit', 'like', '%All Units%')
                   ->orWhere('responsible_unit', 'like', '%All Departments%');

                // Match by FK on the compliance record itself (most accurate)
                if ($userUnitId) {
                    $sq->orWhere('responsible_unit_id', $userUnitId);
                }

                // Match via assignment to this specific unit
                if ($userUnitId) {
                    $sq->orWhereHas('assignments', fn ($aq) =>
                        $aq->where('responsible_unit_id', $userUnitId)
                    );
                }

                // Legacy text-based fallback
                if ($ruName) $sq->orWhere('responsible_unit', 'like', "%{$ruName}%");
                if ($ruCode) $sq->orWhere('responsible_unit', 'like', "%{$ruCode}%");
            });
        };
    }

    private function userCanAccessRecord(ComplianceRecord $compliance, object $user): bool
    {
        if ($user->usertype === 'QA Admin') {
            return true;
        }

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $collegeId = $user->college_id;
            $viaProgram = $compliance->program && $compliance->program->college_id == $collegeId;
            $viaAssignment = $compliance->assignments
                ->filter(fn ($a) => $a->program && $a->program->college_id == $collegeId)
                ->isNotEmpty();
            return $viaProgram || $viaAssignment;
        }

        if ($user->usertype === 'Head of Unit') {
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id ?? null;
            $ru         = $userUnitId ? \App\Models\ResponsibleUnit::find($userUnitId) : null;
            $ruName     = $ru->name ?? ($user->unit->name ?? '');
            $ruCode     = $ru->code ?? ($user->unit->code ?? '');

            // Match by FK on the compliance record
            if ($userUnitId && $compliance->responsible_unit_id == $userUnitId) {
                return true;
            }

            // Match via assignment
            if ($userUnitId && $compliance->assignments->where('responsible_unit_id', $userUnitId)->isNotEmpty()) {
                return true;
            }

            // Legacy text-based check
            $viaLegacy = (
                str_contains($compliance->responsible_unit ?? '', 'All Units') ||
                str_contains($compliance->responsible_unit ?? '', 'All Departments') ||
                ($ruName && str_contains($compliance->responsible_unit ?? '', $ruName)) ||
                ($ruCode && str_contains($compliance->responsible_unit ?? '', $ruCode))
            );

            return $viaLegacy;
        }

        return false;
    }

    public function index(Request $request)
    {
        $user      = auth()->user();
        $role      = $user->usertype === 'QA Admin' ? session('active_role', 'QA Admin') : 'Unit or Department';
        $collegeId = $user->college_id;
        $unitFilter = $this->userScopeFilter($user);

        if ($user->usertype !== 'QA Admin') {
            session(['active_role' => 'Unit or Department']);
        }

        $programsQuery = Program::with('college')->orderBy('program_code');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $programsQuery->where('college_id', $collegeId);
        }
        $programs = $programsQuery->get();

        $college = $user->college;
        $collegeName = $college->name ?? '';
        $collegeCode = $college->code ?? '';

        $deanScope = function ($q) use ($collegeId, $collegeName, $collegeCode) {
            $q->whereHas('program', fn ($pq) => $pq->where('college_id', $collegeId))
              ->orWhereHas('assignments.program', fn ($pq) => $pq->where('college_id', $collegeId));

            if ($collegeName) {
                $q->orWhere('school', 'like', "%{$collegeName}%")
                  ->orWhereHas('assignments', fn ($aq) => $aq->where('school_name', 'like', "%{$collegeName}%"));
            }
            if ($collegeCode) {
                $q->orWhere('school', 'like', "%{$collegeCode}%")
                  ->orWhereHas('assignments', fn ($aq) => $aq->where('school_name', 'like', "%{$collegeCode}%"));
            }
            $q->orWhere('school', 'like', '%All Schools%')
              ->orWhere('school', 'like', '%All Colleges%');
        };

        $baseQuery = ComplianceRecord::query();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $baseQuery->where($deanScope);
        } elseif ($user->usertype === 'Head of Unit') {
            $baseQuery->where($unitFilter);
        }

        $bodies     = (clone $baseQuery)->distinct()->pluck('accrediting_body')->filter()->sort()->values();
        $categories = (clone $baseQuery)->pluck('category')
            ->flatMap(fn ($c) => preg_split('/[,;]+/', $c))
            ->map(fn ($c) => trim($c))->filter()->unique()->sort()->values();
        $areas = (clone $baseQuery)->pluck('area')
            ->flatMap(fn ($a) => preg_split('/[,;]+/', $a))
            ->map(fn ($a) => trim($a))->filter()->unique()->sort()->values();

        $totalCompliance    = (clone $baseQuery)->count();
        $compliantCount     = (clone $baseQuery)->where('status', 'Compliant')->count();
        $nonCompliantCount  = (clone $baseQuery)->where('status', 'Non-Compliant')->count();
        $pendingCount       = (clone $baseQuery)->where('status', 'Pending')->count();

        $query = ComplianceRecord::with(['program', 'recommendationItems', 'assignments.program', 'assignments.responsibleUnit']);
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->where($deanScope);
        } elseif ($user->usertype === 'Head of Unit') {
            // $unitFilter already scopes to: direct responsible_unit_id match,
            // assignment-level match, and legacy text-based match.
            $query->where($unitFilter);
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('responsible_unit', 'like', "%{$s}%")
                  ->orWhere('recommendation', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('area', 'like', "%{$s}%")
                  ->orWhere('school', 'like', "%{$s}%")
                  ->orWhereHas('program', fn ($pq) => $pq->where('program_code', 'like', "%{$s}%"))
                  ->orWhereHas('assignments.program', fn ($pq) => $pq->where('program_code', 'like', "%{$s}%"))
                  ->orWhereHas('assignments.responsibleUnit', fn ($ruq) => $ruq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status'))           $query->where('status', $request->input('status'));
        if ($request->filled('body'))             $query->where('accrediting_body', 'like', '%' . $request->input('body') . '%');
        if ($request->filled('category'))         $query->where('category', 'like', '%' . $request->input('category') . '%');
        if ($request->filled('area'))             $query->where('area', 'like', '%' . $request->input('area') . '%');
        if ($request->filled('priority'))         $query->where('priority', $request->input('priority'));
        if ($request->filled('responsible_unit')) {
            $unit = $request->input('responsible_unit');
            $query->where(function ($q) use ($unit) {
                $q->where('responsible_unit', $unit)
                  ->orWhereHas('assignments.responsibleUnit', fn ($ruq) => $ruq->where('name', $unit)->orWhere('code', $unit));
            });
        }

        $complianceRecords = $query->orderBy('due_date', 'asc')->get();

        $pendingApprovals = ($role === 'QA Admin')
            ? ComplianceRecord::with(['program', 'recommendationItems', 'assignments.program', 'assignments.responsibleUnit'])
                ->where(function ($q) {
                    $q->where('approval_state', 'Pending Approval')
                      ->orWhereHas('assignments', fn ($aq) => $aq->where('approval_state', 'Pending Approval'));
                })
                ->orderBy('updated_at', 'desc')->get()
            : collect();

        $responsibleUnits   = ComplianceRecord::distinct()->pluck('responsible_unit')->filter()->sort()->values();
        $dbResponsibleUnits = \App\Models\ResponsibleUnit::with(['users', 'parent', 'children'])->orderBy('name')->get();
        $colleges           = \App\Models\College::orderBy('name')->get();
        $units              = \App\Models\Unit::orderBy('name')->get();
        $dbAccreditingBodies = \App\Models\AccreditingBody::orderBy('code')->get();

        $contactsMap  = [];
        $usersForMap  = User::whereIn('usertype', ['Dean', 'Head of Unit', 'Principal'])->with(['college', 'unit'])->get();
        $emailDomain  = config('institution.email_domain', 'hau.edu.ph');
        foreach ($usersForMap as $u) {
            if (($u->usertype === 'Dean' || $u->usertype === 'Principal') && $u->college) {
                $contactsMap[$u->college->name] = [
                    'name'  => $u->name,
                    'email' => $u->email ?? ($u->username . '@' . $emailDomain),
                ];
            } elseif ($u->usertype === 'Head of Unit' && $u->unit) {
                $contactsMap[$u->unit->name] = [
                    'name'  => $u->name,
                    'email' => $u->email ?? ($u->username . '@' . $emailDomain),
                ];
            }
        }

        $recentQuery = RecommendationItem::with(['complianceRecord.program'])->where('is_completed', true);
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $recentQuery->whereHas('complianceRecord.program', fn ($q) => $q->where('college_id', $collegeId));
        } elseif ($user->usertype === 'Head of Unit') {
            $recentQuery->whereHas('complianceRecord', $unitFilter);
        }
        $recentlyCompletedRecommendations = $recentQuery->orderBy('completed_at', 'desc')->take(10)->get();

        $summaryQuery = Program::query();
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $summaryQuery->where('college_id', $collegeId)->with(['complianceRecords.recommendationItems']);
        } elseif ($user->usertype === 'Head of Unit') {
            $summaryQuery->whereHas('complianceRecords', $unitFilter)
                ->with(['complianceRecords' => $unitFilter, 'complianceRecords.recommendationItems']);
        } else {
            $summaryQuery->with(['complianceRecords.recommendationItems']);
        }

        $programComplianceSummary = $summaryQuery->get()->map(function ($p) {
            $total     = 0;
            $completed = 0;
            foreach ($p->complianceRecords as $record) {
                $total     += $record->recommendationItems->count();
                $completed += $record->recommendationItems->where('is_completed', true)->count();
            }
            $rate   = $total > 0 ? round(($completed / $total) * 100) : 0;
            $status = $total === 0 ? 'No Tasks' : ($rate === 100 ? 'Compliant' : 'In Progress');
            return (object) [
                'code'      => $p->program_code,
                'name'      => $p->program_name,
                'total'     => $total,
                'completed' => $completed,
                'rate'      => $rate,
                'status'    => $status,
            ];
        })->filter(fn ($p) => $p->total > 0)->sortByDesc('rate')->values();

        return view('compliance.index', compact(
            'role', 'complianceRecords', 'programs', 'bodies', 'categories', 'areas',
            'totalCompliance', 'compliantCount', 'nonCompliantCount', 'pendingCount',
            'pendingApprovals', 'responsibleUnits', 'dbResponsibleUnits', 'colleges', 'units',
            'recentlyCompletedRecommendations', 'programComplianceSummary', 'contactsMap', 'dbAccreditingBodies'
        ));
    }

    public function show($id)
    {
        $user       = auth()->user();
        $compliance = ComplianceRecord::with(['program', 'recommendationItems', 'assignments.program', 'assignments.responsibleUnit'])->findOrFail($id);

        if (!$this->userCanAccessRecord($compliance, $user)) {
            abort(403, 'You do not have access to this compliance record.');
        }

        return redirect()->route('compliance.index')->with('highlight_record', $id);
    }

    public function store(Request $request)
    {
        if ($request->filled('category') && !$request->filled('categories')) {
            $request->merge(['categories' => [$request->input('category')]]);
        }
        if (!$request->filled('categories')) {
            $request->merge(['categories' => ['General']]);
        }

        $validated = $request->validate($this->service->validationRules());

        [$validated, $programIds, $unitIds, $isAllUnits, $recommendations]
            = $this->service->processPayload($validated, $request->input('responsible_unit_ids', []));

        $isAdmin = auth()->user()->usertype === 'QA Admin';

        if (!$isAdmin) {
            $validated['approval_state']       = 'Pending Approval';
            $validated['pending_status']        = 'Compliant';
            $validated['pending_document_link'] = $validated['document_link'] ?? null;
            $validated['status']                = 'Pending';
            $validated['document_link']         = null;
            $validated['workflow_stage']        = 'admin_reviewing';
            $message = 'Compliance task logged and submitted to QA Admin for approval.';
        } else {
            $validated['approval_state']  = 'None';
            $validated['workflow_stage']  = 'recommendation_created';
            $message = 'Compliance task logged successfully.';
        }

        if (empty($validated['contact_person'])) {
            $assignedUser = $this->service->resolveContactUser($validated);
            if ($assignedUser) {
                $emailDomain = config('institution.email_domain', 'hau.edu.ph');
                $validated['contact_person'] = $assignedUser->name;
                $validated['contact_email']  = $assignedUser->email ?? ($assignedUser->username . '@' . $emailDomain);
            }
        }

        $compliance = ComplianceRecord::create($validated);

        $this->service->syncAssignments($compliance, $programIds, $unitIds, $isAllUnits, $validated, true);
        $this->service->createRecommendationItems($compliance, $recommendations);

        // Auto-log or resolve risk based on the compliance record status
        RiskAutoLogService::syncFromCompliance($compliance);

        return redirect()->route('compliance.index')->with('success', $message);
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);

        if ($request->filled('category') && !$request->filled('categories')) {
            $request->merge(['categories' => [$request->input('category')]]);
        }
        if (!$request->filled('categories')) {
            $request->merge(['categories' => ['General']]);
        }

        $validated = $request->validate($this->service->validationRules());

        [$validated, $programIds, $unitIds, $isAllUnits, $recommendations]
            = $this->service->processPayload($validated, $request->input('responsible_unit_ids', []));

        $validated['approval_state']  = 'None';
        $validated['rejection_reason'] = null;

        if (empty($validated['contact_person'])) {
            $assignedUser = $this->service->resolveContactUser($validated);
            if ($assignedUser) {
                $emailDomain = config('institution.email_domain', 'hau.edu.ph');
                $validated['contact_person'] = $assignedUser->name;
                $validated['contact_email']  = $assignedUser->email ?? ($assignedUser->username . '@' . $emailDomain);
            }
        }

        $compliance->update($validated);

        $this->service->syncAssignments($compliance, $programIds, $unitIds, $isAllUnits, $validated, false);
        $this->service->syncRecommendationItems($compliance, $recommendations);

        // Auto-log or resolve risk based on updated compliance status
        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        return redirect()->route('compliance.index')->with('success', 'Compliance task updated successfully.');
    }

    public function submitUpdate(Request $request, $id)
    {
        $compliance = ComplianceRecord::findOrFail($id);

        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) $request->merge(['responsible_unit' => $ru->name]);
        }

        $validated = $request->validate([
            'assignment_id'         => 'nullable|exists:compliance_assignments,id',
            'pending_document_link' => 'required|url',
            'action_plan'           => 'required|string',
            'responsible_unit'      => 'nullable|string|max:255',
            'responsible_unit_id'   => 'nullable|exists:responsible_units,responsible_unit_id',
            'contact_person'        => 'nullable|string|max:255',
            'contact_email'         => 'nullable|email|max:255',
        ]);

        $assignment = null;
        if (!empty($validated['assignment_id'])) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)
                ->find($validated['assignment_id']);
        }

        if (!$assignment) {
            $user        = auth()->user();
            $userUnitId  = $user->responsible_unit_id ?? $user->unit_id;
            $userCollegeId = $user->college_id;

            $assignment = $compliance->assignments()
                ->where(function ($q) use ($userUnitId, $userCollegeId) {
                    if ($userUnitId)   $q->orWhere('responsible_unit_id', $userUnitId);
                    if ($userCollegeId) $q->orWhereHas('program', fn ($pq) => $pq->where('college_id', $userCollegeId));
                })->first() ?? $compliance->assignments()->first();
        }

        if ($assignment) {
            if (!$assignment->canUserSubmit(auth()->user())) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized: School departments may only submit evidence for their own department, school, or programs, and offices may only submit for their assigned units.',
                    ], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized: School departments may only submit evidence for their own department, school, or programs.');
            }

            $assignment->update([
                'pending_document_link' => $validated['pending_document_link'],
                'action_plan'           => $validated['action_plan'],
                'approval_state'        => 'Pending Approval',
                'workflow_stage'        => 'admin_reviewing',
                'rejection_reason'      => null,
            ]);
        }

        $compliancePayload = [
            'pending_status'       => 'Compliant',
            'action_plan'          => $validated['action_plan'],
            'responsible_unit'     => $validated['responsible_unit'] ?? $compliance->responsible_unit,
            'responsible_unit_id'  => $validated['responsible_unit_id'] ?? $compliance->responsible_unit_id,
            'contact_person'       => $validated['contact_person'] ?? $compliance->contact_person,
            'contact_email'        => $validated['contact_email'] ?? $compliance->contact_email,
            'approval_state'       => 'Pending Approval',
            'workflow_stage'       => 'admin_reviewing',
            'rejection_reason'     => null,
        ];

        if ($compliance->assignments()->count() === 0) {
            $compliancePayload['pending_document_link'] = $validated['pending_document_link'];
        }

        $compliance->update($compliancePayload);

        // Dispatch in-app notification to QA Admins
        $adminUsers = User::where('usertype', 'QA Admin')->get();
        foreach ($adminUsers as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type'    => 'action_plan_submitted',
                'message' => "Unit submitted action plan & evidence link for \"{$compliance->title}\".",
                'link'    => route('compliance.index'),
                'is_read' => false,
            ]);
        }

        // Dispatch email notification to QA Admins
        $recipients = User::getQaAdminRecipients();
        $submitterName = auth()->user()?->name ?? 'Department/Unit User';
        $unitName = $compliance->responsible_unit ?? ($compliance->program->program_name ?? 'Responsible Unit');

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new QaAdminAlertMail(
                    subjectTitle: "[QA Portal] Action Plan Submitted: {$compliance->title}",
                    badge: 'Pending Review',
                    headline: 'Action Plan & Evidence Link Submitted',
                    messageBody: "A unit has submitted an action plan and evidence documentation for compliance task \"{$compliance->title}\". This submission is now awaiting QA Admin review and approval.",
                    details: [
                        'Compliance Task' => $compliance->title,
                        'Unit / Program' => $unitName,
                        'Submitted By' => $submitterName . (auth()->user()?->email ? ' (' . auth()->user()->email . ')' : ''),
                        'Action Plan' => $validated['action_plan'],
                        'Evidence Link' => $validated['pending_document_link'] ?? 'N/A',
                    ],
                    actionUrl: route('compliance.index'),
                    actionText: 'Review in Compliance Tracker',
                    badgeType: 'warning'
                ));
            } catch (\Throwable $e) {
                Log::error("Failed to send action plan submission email to QA Admin ({$recipient->email}): " . $e->getMessage());
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Action plan & evidence link submitted. Awaiting QA Admin review.',
                'assignment'     => $assignment,
                'overall_status' => $compliance->fresh()->status,
                'approval_state' => $compliance->fresh()->approval_state,
            ]);
        }

        return redirect()->route('compliance.index')->with('success', 'Action plan & evidence link submitted. Awaiting QA Admin review.');
    }

    public function approve(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance   = ComplianceRecord::findOrFail($id);
        $assignmentId = $request->input('assignment_id');

        if ($assignmentId) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)->find($assignmentId);
            if ($assignment && $assignment->approval_state === 'Pending Approval') {
                $assignment->update([
                    'status'                => 'Compliant',
                    'document_link'         => $assignment->pending_document_link ?? $assignment->document_link,
                    'pending_document_link' => null,
                    'approval_state'        => 'None',
                    'rejection_reason'      => null,
                    'workflow_stage'        => 'compliant',
                ]);
            }
        } else {
            foreach ($compliance->assignments()->where('approval_state', 'Pending Approval')->get() as $assignment) {
                $assignment->update([
                    'status'                => 'Compliant',
                    'document_link'         => $assignment->pending_document_link ?? $assignment->document_link,
                    'pending_document_link' => null,
                    'approval_state'        => 'None',
                    'rejection_reason'      => null,
                    'workflow_stage'        => 'compliant',
                ]);
            }
        }

        $remainingPending = $compliance->assignments()->where('approval_state', 'Pending Approval')->count();
        $hasNonCompliant  = $compliance->assignments()->where('status', '!=', 'Compliant')->exists();

        $compliancePayload = [
            'status'                => $hasNonCompliant ? 'Pending' : 'Compliant',
            'pending_status'        => null,
            'pending_document_link' => null,
            'approval_state'        => $remainingPending > 0 ? 'Pending Approval' : 'None',
            'rejection_reason'      => null,
            'workflow_stage'        => $hasNonCompliant ? 'admin_reviewing' : 'compliant',
        ];

        if ($compliance->assignments()->count() === 0 && $compliance->pending_document_link) {
            $compliancePayload['document_link'] = $compliance->pending_document_link;
        }

        $compliance->update($compliancePayload);
        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        $this->notifyContactUser($compliance, 'approved',
            "Your compliance submission for \"{$compliance->title}\" has been approved by QA Admin."
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Compliance update approved.',
                'overall_status' => $compliance->fresh()->status,
                'approval_state' => $compliance->fresh()->approval_state,
            ]);
        }

        return redirect()->back()->with('success', 'Compliance update approved.');
    }

    public function reject(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);
        $validated  = $request->validate([
            'rejection_reason' => 'required|string|max:255',
            'assignment_id'    => 'nullable|exists:compliance_assignments,id',
        ]);

        $assignmentId = $validated['assignment_id'] ?? null;

        if ($assignmentId) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)->find($assignmentId);
            if ($assignment) {
                $assignment->update([
                    'approval_state'   => 'Rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'workflow_stage'   => 'recommendation_created',
                ]);
            }
        } else {
            foreach ($compliance->assignments()->where('approval_state', 'Pending Approval')->get() as $assignment) {
                $assignment->update([
                    'approval_state'   => 'Rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'workflow_stage'   => 'recommendation_created',
                ]);
            }
        }

        $remainingPending = $compliance->assignments()->where('approval_state', 'Pending Approval')->count();
        $compliance->update([
            'approval_state'   => $remainingPending > 0 ? 'Pending Approval' : 'Rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'workflow_stage'   => 'recommendation_created',
        ]);

        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        $this->notifyContactUser($compliance, 'rejected',
            "Your compliance submission for \"{$compliance->title}\" was rejected. Reason: {$validated['rejection_reason']}"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'          => true,
                'message'          => 'Compliance update rejected. Unit will be prompted to revise.',
                'rejection_reason' => $validated['rejection_reason'],
                'overall_status'   => $compliance->fresh()->status,
                'approval_state'   => $compliance->fresh()->approval_state,
            ]);
        }

        return redirect()->back()->with('success', 'Compliance update rejected. Unit will be prompted to revise.');
    }

    public function toggleRecommendation(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->usertype !== 'QA Admin') {
            abort(403, 'Only QA Admin can directly mark recommendations as completed. Units may submit evidence for review.');
        }

        $item       = RecommendationItem::with(['complianceRecord.program', 'complianceRecord.assignments'])->findOrFail($id);
        $compliance = $item->complianceRecord;

        $newCompletedState = !$item->is_completed;
        $item->update([
            'is_completed'  => $newCompletedState,
            'completed_at'  => $newCompletedState ? now() : null,
            'status'        => $newCompletedState ? 'approved' : ($item->evidence_link ? 'under_review' : 'pending'),
            'admin_remarks' => $newCompletedState ? null : $item->admin_remarks,
        ]);

        $total     = $compliance->recommendationItems()->count();
        $completed = $compliance->recommendationItems()->where('is_completed', true)->count();
        $rate      = $total > 0 ? round(($completed / $total) * 100) : 0;

        // Auto-update overall compliance record status if all recommendations are completed
        if ($total > 0 && $completed === $total) {
            $compliance->update([
                'status'         => 'Compliant',
                'workflow_stage' => 'compliant',
            ]);
        } elseif ($compliance->status === 'Compliant' && $completed < $total) {
            $compliance->update([
                'status'         => 'Pending',
                'workflow_stage' => 'admin_reviewing',
            ]);
        }

        // Auto-log or resolve risk based on the compliance record status
        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        return response()->json([
            'success'          => true,
            'is_completed'     => $item->is_completed,
            'status'           => $item->status,
            'completed_count'  => $completed,
            'total_count'      => $total,
            'completion_rate'  => $rate,
            'overall_status'   => $compliance->fresh()->status,
        ]);
    }

    public function updateEvidence(Request $request, $id)
    {
        $item       = RecommendationItem::with(['complianceRecord.program', 'complianceRecord.assignments'])->findOrFail($id);
        $compliance = $item->complianceRecord;
        $user       = auth()->user();

        if (!$this->userCanAccessRecord($compliance->load(['program', 'assignments.program', 'assignments.responsibleUnit']), $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'evidence_link' => 'required|url|max:2048',
            'action_plan'   => 'nullable|string',
        ]);

        $item->update([
            'evidence_link' => $validated['evidence_link'],
            'status'        => ($item->status === 'approved' && $user->usertype !== 'QA Admin') ? 'under_review' : ($item->status === 'approved' ? 'approved' : 'under_review'),
            'admin_remarks' => null, // Clear past rejection remarks on new submission
        ]);

        if (!empty($validated['action_plan'])) {
            $compliance->update([
                'action_plan' => $validated['action_plan'],
            ]);
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id;
            if ($userUnitId) {
                $compliance->assignments()->where('responsible_unit_id', $userUnitId)->update([
                    'action_plan' => $validated['action_plan'],
                ]);
            }
        }

        // Dispatch in-app notification to QA Admins
        if ($user->usertype !== 'QA Admin') {
            $adminUsers = User::where('usertype', 'QA Admin')->get();
            foreach ($adminUsers as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type'    => 'action_plan_submitted',
                    'message' => "Unit submitted evidence for recommendation \"{$item->text}\" on \"{$compliance->title}\".",
                    'link'    => route('compliance.index'),
                    'is_read' => false,
                ]);
            }

            // Dispatch email alert to QA Admins
            $recipients = User::getQaAdminRecipients();
            $submitterName = $user->name ?: 'Unit User';
            $unitName = $compliance->responsible_unit ?? ($compliance->program->program_name ?? 'Responsible Unit');

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(new QaAdminAlertMail(
                        subjectTitle: "[QA Portal] Recommendation Evidence Submitted: {$compliance->title}",
                        badge: 'Evidence Under Review',
                        headline: 'Recommendation Evidence Submitted',
                        messageBody: "A unit has submitted evidence documentation for a specific recommendation on \"{$compliance->title}\".",
                        details: [
                            'Compliance Task' => $compliance->title,
                            'Unit / Program' => $unitName,
                            'Submitted By' => $submitterName . ($user->email ? " ({$user->email})" : ''),
                            'Recommendation' => $item->text,
                            'Evidence Link' => $validated['evidence_link'],
                        ],
                        actionUrl: route('compliance.index'),
                        actionText: 'Review in Compliance Tracker',
                        badgeType: 'warning'
                    ));
                } catch (\Throwable $e) {
                    Log::error("Failed to send recommendation evidence submission email to QA Admin ({$recipient->email}): " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success'       => true,
            'status'        => $item->status,
            'evidence_link' => $item->evidence_link,
            'message'       => 'Evidence link submitted for review.',
        ]);
    }

    public function approveRecommendationItem(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $item       = RecommendationItem::with(['complianceRecord.program', 'complianceRecord.assignments'])->findOrFail($id);
        $compliance = $item->complianceRecord;

        $item->update([
            'status'        => 'approved',
            'is_completed'  => true,
            'completed_at'  => now(),
            'admin_remarks' => null,
        ]);

        $total     = $compliance->recommendationItems()->count();
        $completed = $compliance->recommendationItems()->where('is_completed', true)->count();
        $rate      = $total > 0 ? round(($completed / $total) * 100) : 0;

        // If all items are completed, mark the whole task as Compliant
        if ($total > 0 && $completed === $total) {
            $compliance->update([
                'status'         => 'Compliant',
                'workflow_stage' => 'compliant',
            ]);
        }

        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        $this->notifyContactUser($compliance, 'approved',
            "Your evidence for recommendation: \"{$item->text}\" on \"{$compliance->title}\" was accepted and approved."
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'          => true,
                'status'           => 'approved',
                'is_completed'     => true,
                'completed_count'  => $completed,
                'total_count'      => $total,
                'completion_rate'  => $rate,
                'overall_status'   => $compliance->fresh()->status,
                'message'          => 'Recommendation approved successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Recommendation item approved.');
    }

    public function rejectRecommendationItem(Request $request, $id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'admin_remarks' => 'required|string|max:1000',
        ]);

        $item       = RecommendationItem::with(['complianceRecord.program', 'complianceRecord.assignments'])->findOrFail($id);
        $compliance = $item->complianceRecord;

        $item->update([
            'status'        => 'needs_revision',
            'is_completed'  => false,
            'completed_at'  => null,
            'admin_remarks' => $validated['admin_remarks'],
        ]);

        $total     = $compliance->recommendationItems()->count();
        $completed = $compliance->recommendationItems()->where('is_completed', true)->count();
        $rate      = $total > 0 ? round(($completed / $total) * 100) : 0;

        // If compliance record was previously marked Compliant, revert to Pending since an item needs revision
        if ($compliance->status === 'Compliant') {
            $compliance->update([
                'status'         => 'Pending',
                'workflow_stage' => 'admin_reviewing',
            ]);
        }

        RiskAutoLogService::syncFromCompliance($compliance->fresh());

        $this->notifyContactUser($compliance, 'rejected',
            "Revision requested on recommendation \"{$item->text}\" for \"{$compliance->title}\". Remark: {$validated['admin_remarks']}"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'          => true,
                'status'           => 'needs_revision',
                'is_completed'     => false,
                'admin_remarks'    => $item->admin_remarks,
                'completed_count'  => $completed,
                'total_count'      => $total,
                'completion_rate'  => $rate,
                'overall_status'   => $compliance->fresh()->status,
                'message'          => 'Revision requested for recommendation item.',
            ]);
        }

        return redirect()->back()->with('success', 'Revision requested on recommendation item. Submitting unit has been notified.');
    }

    public function destroy($id)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $compliance = ComplianceRecord::findOrFail($id);
        $compliance->delete();

        return redirect()->route('compliance.index')->with('success', 'Compliance task deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        if (auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:compliance_records,compliance_record_id',
        ]);

        $count = \App\Models\ComplianceRecord::whereKey($validated['ids'])->delete();

        return redirect()->route('compliance.index')->with('success', "{$count} compliance tasks deleted successfully.");
    }

    public function exportCsv(Request $request)
    {
        $user      = auth()->user();
        $collegeId = $user->college_id;

        $college = $user->college;
        $collegeName = $college->name ?? '';
        $collegeCode = $college->code ?? '';

        $deanScope = function ($q) use ($collegeId, $collegeName, $collegeCode) {
            $q->whereHas('program', fn ($pq) => $pq->where('college_id', $collegeId))
              ->orWhereHas('assignments.program', fn ($pq) => $pq->where('college_id', $collegeId));

            if ($collegeName) {
                $q->orWhere('school', 'like', "%{$collegeName}%")
                  ->orWhereHas('assignments', fn ($aq) => $aq->where('school_name', 'like', "%{$collegeName}%"));
            }
            if ($collegeCode) {
                $q->orWhere('school', 'like', "%{$collegeCode}%")
                  ->orWhereHas('assignments', fn ($aq) => $aq->where('school_name', 'like', "%{$collegeCode}%"));
            }
            $q->orWhere('school', 'like', '%All Schools%')
              ->orWhere('school', 'like', '%All Colleges%');
        };

        $unitFilter = $this->userScopeFilter($user);

        $query = ComplianceRecord::with([
            'program.college',
            'recommendationItems',
            'assignments.program.college',
            'assignments.responsibleUnit',
            'responsibleUnitRelation'
        ]);

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->where($deanScope);
        } elseif ($user->usertype === 'Head of Unit') {
            $query->where($unitFilter);
        }

        if ($request->filled('ids')) {
            $ids = is_array($request->input('ids')) ? $request->input('ids') : explode(',', $request->input('ids'));
            $query->whereIn('compliance_record_id', array_filter($ids));
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('responsible_unit', 'like', "%{$s}%")
                  ->orWhere('recommendation', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('area', 'like', "%{$s}%")
                  ->orWhere('school', 'like', "%{$s}%")
                  ->orWhereHas('program', fn ($pq) => $pq->where('program_code', 'like', "%{$s}%")->orWhere('program_name', 'like', "%{$s}%"))
                  ->orWhereHas('assignments.program', fn ($pq) => $pq->where('program_code', 'like', "%{$s}%")->orWhere('program_name', 'like', "%{$s}%"))
                  ->orWhereHas('assignments.responsibleUnit', fn ($ruq) => $ruq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('body') || $request->filled('accrediting_body')) {
            $bodyVal = $request->input('body') ?: $request->input('accrediting_body');
            $query->where('accrediting_body', 'like', '%' . $bodyVal . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->input('category') . '%');
        }
        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->input('area') . '%');
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('responsible_unit') || $request->filled('unit')) {
            $unit = $request->input('responsible_unit') ?: $request->input('unit');
            $query->where(function ($q) use ($unit) {
                $q->where('responsible_unit', 'like', "%{$unit}%")
                  ->orWhereHas('assignments.responsibleUnit', fn ($ruq) => $ruq->where('name', 'like', "%{$unit}%")->orWhere('code', 'like', "%{$unit}%"));
            });
        }
        if ($request->filled('school')) {
            $sch = $request->input('school');
            $query->where(function ($q) use ($sch) {
                $q->where('school', 'like', "%{$sch}%")
                  ->orWhereHas('assignments', fn ($aq) => $aq->where('school_name', 'like', "%{$sch}%"))
                  ->orWhereHas('assignments.program.college', fn ($cq) => $cq->where('name', 'like', "%{$sch}%")->orWhere('code', 'like', "%{$sch}%"))
                  ->orWhereHas('program.college', fn ($cq) => $cq->where('name', 'like', "%{$sch}%")->orWhere('code', 'like', "%{$sch}%"));
            });
        }

        $records = $query->orderBy('compliance_record_id', 'asc')->get();

        $format = strtolower($request->input('format', 'xlsx'));

        $stageLabels = [
            'recommendation_created' => 'Recommendation Created',
            'action_plan_submitted'  => 'Action Plan Submitted',
            'admin_reviewing'        => 'Under QA Review',
            'compliant'              => 'Compliant',
        ];

        $cleanText = function (?string $text): string {
            if ($text === null) return '';
            $t = strip_tags($text);
            $t = str_replace(["\r\n", "\r"], "\n", $t);
            return trim($t);
        };

        // Prepare data rows
        $dataRows = [];
        foreach ($records as $r) {
            // 1. Programs list
            $programs = [];
            if ($r->program) {
                $progCode = $r->program->program_code ? $r->program->program_code : '';
                $progName = $r->program->program_name ? $r->program->program_name : '';
                $progStr = trim($progCode . ($progCode && $progName ? ' - ' : '') . $progName);
                if ($progStr) $programs[] = $progStr;
            }
            foreach ($r->assignments as $a) {
                if ($a->program) {
                    $pCode = $a->program->program_code ?: '';
                    $pName = $a->program->program_name ?: '';
                    $pStr = trim($pCode . ($pCode && $pName ? ' - ' : '') . $pName);
                    if ($pStr && !in_array($pStr, $programs)) {
                        $programs[] = $pStr;
                    }
                }
            }
            $programDisplay = !empty($programs) ? implode('; ', $programs) : 'General / Institutional';

            // 2. Schools list
            $schools = [];
            if ($r->school && $r->school !== 'General') {
                foreach (preg_split('/[,;]+/', $r->school) as $s) {
                    $s = trim($s);
                    if ($s && !in_array($s, $schools)) $schools[] = $s;
                }
            }
            foreach ($r->assignments as $a) {
                if (!empty($a->school_name) && !in_array(trim($a->school_name), $schools)) {
                    $schools[] = trim($a->school_name);
                } elseif ($a->program && $a->program->college && !in_array(trim($a->program->college->name), $schools)) {
                    $schools[] = trim($a->program->college->name);
                }
            }
            $schoolDisplay = !empty($schools) ? implode('; ', $schools) : ($r->school ?: 'General');

            // 3. Units list
            $units = [];
            if ($r->responsible_unit && $r->responsible_unit !== 'Unassigned') {
                foreach (preg_split('/[,;]+/', $r->responsible_unit) as $u) {
                    $u = trim($u);
                    if ($u && !in_array($u, $units)) $units[] = $u;
                }
            }
            if ($r->responsibleUnitRelation && !in_array($r->responsibleUnitRelation->name, $units)) {
                $units[] = $r->responsibleUnitRelation->name;
            }
            foreach ($r->assignments as $a) {
                if ($a->responsibleUnit && !in_array($a->responsibleUnit->name, $units)) {
                    $units[] = $a->responsibleUnit->name;
                }
            }
            $unitDisplay = !empty($units) ? implode('; ', $units) : ($r->responsible_unit ?: 'Unassigned');

            // 4. Checklist Progress & Items
            $totalRecs     = $r->recommendationItems->count();
            $completedRecs = $r->recommendationItems->where('is_completed', true)->count();
            $rate          = $totalRecs > 0 ? (int) round(($completedRecs / $totalRecs) * 100) : 0;

            $checklistItems = [];
            foreach ($r->recommendationItems as $item) {
                $checkMark = $item->is_completed ? '[✓]' : '[ ]';
                $statusNote = ($item->status && $item->status !== 'approved' && $item->status !== 'pending') ? " ({$item->status})" : '';
                $checklistItems[] = $checkMark . ' ' . trim($item->text) . $statusNote;
            }
            $checklistDisplay = !empty($checklistItems)
                ? implode("\n", $checklistItems)
                : ($cleanText($r->recommendation) ?: 'None');

            // 5. Evidence Links
            $links = [];
            if ($r->document_link) $links[] = $r->document_link;
            if ($r->pending_document_link && $r->pending_document_link !== $r->document_link) {
                $links[] = 'Pending: ' . $r->pending_document_link;
            }
            foreach ($r->assignments as $a) {
                if ($a->document_link && !in_array($a->document_link, $links)) {
                    $links[] = $a->document_link;
                }
                if ($a->pending_document_link && !in_array('Pending: ' . $a->pending_document_link, $links)) {
                    $links[] = 'Pending: ' . $a->pending_document_link;
                }
            }
            $evidenceDisplay = !empty($links) ? implode("\n", $links) : 'None';

            // 6. Workflow stage
            $stageDisplay = $stageLabels[$r->workflow_stage] ?? ucwords(str_replace('_', ' ', $r->workflow_stage ?: 'Recommendation Created'));

            $dataRows[] = [
                'id'             => $r->compliance_record_id,
                'title'          => $cleanText($r->title),
                'status'         => $r->status ?: 'Pending',
                'stage'          => $stageDisplay,
                'approval_state' => $r->approval_state ?: 'None',
                'priority'       => $r->priority ?: 'Medium',
                'body'           => $r->accrediting_body ?: 'N/A',
                'school'         => $schoolDisplay,
                'programs'       => $programDisplay,
                'area'           => $r->area ?: 'N/A',
                'category'       => $r->category ?: 'N/A',
                'unit'           => $unitDisplay,
                'contact_person' => $r->contact_person ?: '',
                'contact_email'  => $r->contact_email ?: '',
                'due_date'       => $r->due_date ? $r->due_date->format('Y-m-d') : '',
                'visit_date'     => $r->visit_date ? $r->visit_date->format('Y-m-d') : '',
                'rate'           => $rate . '%',
                'completed_recs' => $completedRecs,
                'total_recs'     => $totalRecs,
                'checklist'      => $checklistDisplay,
                'description'    => $cleanText($r->description),
                'action_plan'    => $cleanText($r->action_plan),
                'evidence'       => $evidenceDisplay,
                'rejection'      => $cleanText($r->rejection_reason),
                'created_at'     => $r->created_at ? $r->created_at->format('Y-m-d') : '',
                'updated_at'     => $r->updated_at ? $r->updated_at->format('Y-m-d') : '',
            ];
        }

        $headersList = [
            'Compliance ID',
            'Task Title',
            'Status',
            'Workflow Stage',
            'Approval State',
            'Priority',
            'Accrediting Body',
            'School / College',
            'Academic Program(s)',
            'Area / Standard',
            'Category',
            'Unit or Department',
            'Contact Person',
            'Contact Email',
            'Due Date',
            'Visit Date',
            'Checklist Progress (%)',
            'Completed Checklist Items',
            'Total Checklist Items',
            'Recommendation Checklist',
            'Description',
            'Action Plan',
            'Document / Evidence Links',
            'Rejection / Revision Reason',
            'Created Date',
            'Last Updated',
        ];

        // If explicitly requested as CSV
        if ($format === 'csv') {
            $filename = 'compliance_report_' . date('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $callback = function () use ($headersList, $dataRows) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $headersList);
                foreach ($dataRows as $row) {
                    fputcsv($file, array_values($row));
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Default: Generate Rich Multi-Sheet Area-Tabbed Excel XLSX Spreadsheet
        // -----------------------------------------------------------------------
        // Groups sheets by Accrediting Body → Area.
        // - Single body in results: clean tabs (AREA 1, AREA 2, … CONSOLIDATED)
        // - Multiple bodies:        prefixed tabs (PAASCU-A1, PACUCOA-AI, … CONSOLIDATED)
        // -----------------------------------------------------------------------

        // Detect active filters (for title note on sheet)
        $activeFilters = array_filter([
            'Search'    => $request->input('search'),
            'Status'    => $request->input('status'),
            'Body'      => $request->input('body') ?: $request->input('accrediting_body'),
            'Category'  => $request->input('category'),
            'Area'      => $request->input('area'),
            'Priority'  => $request->input('priority'),
            'Unit'      => $request->input('responsible_unit') ?: $request->input('unit'),
            'School'    => $request->input('school'),
        ]);
        $hasFilter = !empty($activeFilters);

        // -----------------------------------------------------------------------
        // Build per-body → per-area groupings
        // Each body can have its own area naming convention
        // -----------------------------------------------------------------------

        // Load AccreditingBody area definitions from DB (keyed by code)
        $bodyAreaDefs = \App\Models\AccreditingBody::all()->keyBy('code');

        // Generic short-label extractor for an area string
        // Tries to pull the leading "Area N:" / "Criterion N:" / "Standard N:" / "Area N" part
        $shortAreaLabel = function (string $area): string {
            // Match patterns like "Area 1", "Area I", "Criterion 3", "Standard VII", "Principle 2"
            if (preg_match('/^(Area|Criterion|Principle|Standard)\s+([\dIVXivx]+)/i', $area, $m)) {
                $prefix = strtoupper(substr($m[1], 0, 1)); // A / C / P / S
                return $prefix . $m[2];                    // e.g. A1, C3, P2, SII
            }
            // Fallback: first 6 chars uppercased
            return strtoupper(substr(trim($area), 0, 6));
        };

        // Group data rows: [bodyCode][areaLabel] => [rows...]
        $rowsByBodyArea = [];
        $bodyOrder      = [];   // maintains insertion order per body

        foreach ($dataRows as $dr) {
            // A compliance record has exactly one accrediting body value
            // (the field may contain a comma-separated list if multi-body records
            //  are ever introduced — we handle that too)
            $bodiesRaw = $dr['body'] !== 'N/A' ? $dr['body'] : '';
            $bodies    = array_filter(array_map('trim', preg_split('/[,;\/]+/', $bodiesRaw)));
            if (empty($bodies)) $bodies = ['General'];

            $areaRaw = $dr['area'] !== 'N/A' ? trim($dr['area']) : 'Other';
            // A record can also span multiple areas (comma-separated)
            $areas = array_filter(array_map('trim', preg_split('/[,;]+/', $areaRaw)));
            if (empty($areas)) $areas = ['Other'];

            foreach ($bodies as $bodyCode) {
                if (!isset($rowsByBodyArea[$bodyCode])) {
                    $rowsByBodyArea[$bodyCode] = [];
                    $bodyOrder[]               = $bodyCode;
                }
                foreach ($areas as $areaLabel) {
                    $rowsByBodyArea[$bodyCode][$areaLabel][] = $dr;
                }
            }
        }

        // Deduplicate bodyOrder
        $bodyOrder = array_unique($bodyOrder);
        $multiBody = count($bodyOrder) > 1;

        // -----------------------------------------------------------------------
        // Build ordered sheet definitions: ['tabName' => ['body' => X, 'area' => Y, 'rows' => []]]
        // -----------------------------------------------------------------------
        $sheetDefs = [];

        foreach ($bodyOrder as $bodyCode) {
            $bodyDef = $bodyAreaDefs->get($bodyCode);

            // Build area order for this body: DB-defined order first, then any extras
            $canonicalAreas = $bodyDef ? ($bodyDef->areas ?? []) : [];
            $presentAreas   = array_keys($rowsByBodyArea[$bodyCode]);

            // Sort present areas by their canonical index (unknown areas go last)
            usort($presentAreas, function ($a, $b) use ($canonicalAreas) {
                $iA = array_search($a, $canonicalAreas);
                $iB = array_search($b, $canonicalAreas);
                $iA = ($iA === false) ? PHP_INT_MAX : $iA;
                $iB = ($iB === false) ? PHP_INT_MAX : $iB;
                return $iA <=> $iB;
            });

            foreach ($presentAreas as $areaLabel) {
                // Build tab name
                if ($multiBody) {
                    $areaShort = $shortAreaLabel($areaLabel);
                    // Max Excel sheet name = 31 chars; body code max ~8, separator 1, area short ~5 => safe
                    $tabName = $bodyCode . '-' . $areaShort;
                } else {
                    // Single body: clean tabs — extract short label from area string
                    $areaShort = $shortAreaLabel($areaLabel);
                    // Build "AREA 1" style: replace leading letter+digit with "AREA N"
                    if (preg_match('/^A(\d+)$/i', $areaShort, $m)) {
                        $tabName = 'AREA ' . $m[1];
                    } else {
                        $tabName = $areaShort;
                    }
                }

                // Ensure tab name uniqueness (Excel requires unique names)
                $base = $tabName;
                $suffix = 2;
                while (isset($sheetDefs[$tabName])) {
                    $tabName = $base . '_' . $suffix++;
                }

                $sheetDefs[$tabName] = [
                    'body'  => $bodyCode,
                    'area'  => $areaLabel,
                    'rows'  => $rowsByBodyArea[$bodyCode][$areaLabel],
                ];
            }
        }

        // -----------------------------------------------------------------------
        // Column definitions (shared across all sheets)
        // -----------------------------------------------------------------------
        $headersList = [
            'Compliance ID',
            'Task Title',
            'Status',
            'Workflow Stage',
            'Approval State',
            'Priority',
            'Accrediting Body',
            'School / College',
            'Academic Program(s)',
            'Area / Standard',
            'Category',
            'Unit or Department',
            'Contact Person',
            'Contact Email',
            'Due Date',
            'Visit Date',
            'Checklist Progress (%)',
            'Completed Checklist Items',
            'Total Checklist Items',
            'Recommendation Checklist',
            'Description',
            'Action Plan',
            'Document / Evidence Links',
            'Rejection / Revision Reason',
            'Created Date',
            'Last Updated',
        ];

        $columnWidths = [
            'A' => 16, 'B' => 36, 'C' => 16, 'D' => 24, 'E' => 18, 'F' => 15,
            'G' => 18, 'H' => 32, 'I' => 32, 'J' => 22, 'K' => 22, 'L' => 32,
            'M' => 24, 'N' => 28, 'O' => 15, 'P' => 15, 'Q' => 22, 'R' => 24,
            'S' => 20, 'T' => 45, 'U' => 40, 'V' => 40, 'W' => 38, 'X' => 32,
            'Y' => 15, 'Z' => 15,
        ];

        // Helper to write one sheet of rows
        $writeSheet = function (
            \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
            string $areaTitle,
            array $sheetRows,
            bool $isConsolidated = false
        ) use ($headersList, $columnWidths, $activeFilters, $hasFilter) {

            // --- Banner ---
            $ws->mergeCells('A1:Z1');
            $bannerText = $isConsolidated
                ? 'COMPLIANCE TRACKER — CONSOLIDATED' . ($hasFilter ? ' (FILTERED)' : ' (ALL RECORDS)')
                : strtoupper($areaTitle);
            $ws->setCellValue('A1', $bannerText);
            $ws->getStyle('A1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension(1)->setRowHeight(28);

            // --- Filter note row (row 2) ---
            $ws->mergeCells('A2:Z2');
            if ($hasFilter) {
                $filterParts = [];
                foreach ($activeFilters as $label => $val) {
                    $filterParts[] = "{$label}: \"{$val}\"";
                }
                $ws->setCellValue('A2', 'Active Filters: ' . implode('  |  ', $filterParts));
                $ws->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6B7280'], 'name' => 'Calibri'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $ws->getRowDimension(2)->setRowHeight(16);
            } else {
                $ws->setCellValue('A2', config('institution.name', 'Holy Angel University') . ' — Quality Assurance Portal  |  Generated: ' . date('F d, Y h:i A'));
                $ws->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '9CA3AF'], 'name' => 'Calibri'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $ws->getRowDimension(2)->setRowHeight(16);
            }

            // --- Column widths ---
            foreach ($columnWidths as $col => $w) {
                $ws->getColumnDimension($col)->setWidth($w);
            }

            // --- Header Row (row 3) ---
            $ws->fromArray($headersList, null, 'A3');
            $ws->getRowDimension(3)->setRowHeight(32);
            $ws->getStyle('A3:Z3')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Calibri'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D4AF37']]],
            ]);

            $ws->freezePane('A4');

            // --- Data Rows (start at row 4) ---
            $currentRow = 4;
            foreach ($sheetRows as $row) {
                $ws->fromArray(array_values($row), null, "A{$currentRow}");
                $ws->getRowDimension($currentRow)->setRowHeight(-1);

                $isEven = ($currentRow % 2 === 0);
                $bgHex = $isEven ? 'F8FAFC' : 'FFFFFF';

                $ws->getStyle("A{$currentRow}:Z{$currentRow}")->applyFromArray([
                    'font'    => ['size' => 10.5, 'name' => 'Calibri'],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgHex]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);

                // Alignment
                $ws->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle("C{$currentRow}:G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle("O{$currentRow}:S{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle("Y{$currentRow}:Z{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                $ws->getStyle("H{$currentRow}:N{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                $ws->getStyle("T{$currentRow}:X{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                // Task ID bold
                $ws->getStyle("A{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB('1E293B');

                // Status badge
                $statusVal = $row['status'];
                if ($statusVal === 'Compliant') {
                    $ws->getStyle("C{$currentRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1E7DD']], 'font' => ['bold' => true, 'color' => ['rgb' => '0F5132']]]);
                } elseif ($statusVal === 'Non-Compliant') {
                    $ws->getStyle("C{$currentRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']], 'font' => ['bold' => true, 'color' => ['rgb' => '842029']]]);
                } else {
                    $ws->getStyle("C{$currentRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']], 'font' => ['bold' => true, 'color' => ['rgb' => '664D03']]]);
                }

                // Priority color
                $pVal = $row['priority'];
                if ($pVal === 'Critical')     $ws->getStyle("F{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB('991B1B');
                elseif ($pVal === 'High')     $ws->getStyle("F{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB('C2410C');
                elseif ($pVal === 'Medium')   $ws->getStyle("F{$currentRow}")->getFont()->getColor()->setRGB('1E40AF');

                // 100% checklist highlight
                if ($row['rate'] === '100%') {
                    $ws->getStyle("Q{$currentRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']], 'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']]]);
                }

                $currentRow++;
            }

            $lastRow = max(3, $currentRow - 1);

            // Empty state note
            if (empty($sheetRows)) {
                $ws->mergeCells('A4:Z4');
                $ws->setCellValue('A4', 'No compliance records found for this area' . ($hasFilter ? ' with the active filters.' : '.'));
                $ws->getStyle('A4')->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['rgb' => '9CA3AF'], 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
                $lastRow = 4;
            }

            // Auto-filter & freeze
            $ws->setAutoFilter("A3:Z{$lastRow}");

            // Summary footer
            $footerRow = $lastRow + 2;
            $ws->mergeCells("A{$footerRow}:D{$footerRow}");
            $totalRows  = count($sheetRows);
            $compliant  = count(array_filter($sheetRows, fn($r) => $r['status'] === 'Compliant'));
            $pending    = count(array_filter($sheetRows, fn($r) => $r['status'] === 'Pending'));
            $nonComp    = count(array_filter($sheetRows, fn($r) => $r['status'] === 'Non-Compliant'));
            $ws->setCellValue("A{$footerRow}", "Total: {$totalRows} | Compliant: {$compliant} | Pending: {$pending} | Non-Compliant: {$nonComp}");
            $ws->getStyle("A{$footerRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($footerRow)->setRowHeight(20);
        };

        // -----------------------------------------------------------------------
        // Build the Spreadsheet
        // -----------------------------------------------------------------------
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Create one sheet per body+area combination
        foreach ($sheetDefs as $sheetTabName => $def) {
            $ws = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetTabName);
            $spreadsheet->addSheet($ws);
            // Banner title: for multi-body include body code, for single-body just the area name
            $bannerTitle = $multiBody
                ? $def['body'] . ' — ' . $def['area']
                : $def['area'];
            $writeSheet($ws, $bannerTitle, $def['rows'], false);
        }

        // Create CONSOLIDATED sheet (last tab)
        $consolidatedLabel = 'CONSOLIDATED (' . count($dataRows) . ')';
        $wsConsolidated = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $consolidatedLabel);
        $spreadsheet->addSheet($wsConsolidated);
        $writeSheet($wsConsolidated, 'Consolidated', $dataRows, true);

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'compliance_report_' . date('Y-m-d_His') . '.xlsx';






        $headers = [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }

    private function notifyContactUser(ComplianceRecord $compliance, string $outcome, string $message): void
    {
        $contactEmail = $compliance->contact_email;
        $targetUser   = $contactEmail ? User::where('email', $contactEmail)->first() : null;

        if (!$targetUser && $compliance->responsible_unit_id) {
            $ru = \App\Models\ResponsibleUnit::with('users')->find($compliance->responsible_unit_id);
            $targetUser = $ru?->users->first();
        }

        if ($targetUser) {
            Notification::create([
                'user_id' => $targetUser->id,
                'type'    => "compliance_{$outcome}",
                'message' => $message,
                'link'    => route('compliance.index'),
                'is_read' => false,
            ]);
        }
    }
}
