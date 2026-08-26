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

class ComplianceController extends Controller
{
    public function __construct(private ComplianceService $service) {}

    private function userScopeFilter(object $user): \Closure
    {
        $unitName = $user->unit->name ?? '';
        $unitCode = $user->unit->code ?? '';

        return function ($q) use ($unitName, $unitCode) {
            $q->where(function ($sq) use ($unitName, $unitCode) {
                $sq->orWhere('responsible_unit', 'like', '%All Units%')
                   ->orWhere('responsible_unit', 'like', '%All Departments%');
                if ($unitName) $sq->orWhere('responsible_unit', 'like', "%{$unitName}%");
                if ($unitCode) $sq->orWhere('responsible_unit', 'like', "%{$unitCode}%");
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
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id;
            $unitName   = $user->unit->name ?? '';
            $unitCode   = $user->unit->code ?? '';

            $viaLegacyField = (
                str_contains($compliance->responsible_unit ?? '', 'All Units') ||
                str_contains($compliance->responsible_unit ?? '', 'All Departments') ||
                ($unitName && str_contains($compliance->responsible_unit ?? '', $unitName)) ||
                ($unitCode && str_contains($compliance->responsible_unit ?? '', $unitCode))
            );

            $viaAssignment = $compliance->assignments
                ->where('responsible_unit_id', $userUnitId)
                ->isNotEmpty();

            return $viaLegacyField || $viaAssignment;
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
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id;
            $query->where(function ($q) use ($unitFilter, $user, $userUnitId) {
                $unitName = $user->unit->name ?? '';
                $unitCode = $user->unit->code ?? '';
                $q->where($unitFilter)
                  ->orWhereHas('assignments', function ($aq) use ($unitName, $unitCode, $userUnitId) {
                      if ($userUnitId) {
                          $aq->where('responsible_unit_id', $userUnitId);
                      }
                      $aq->orWhereHas('responsibleUnit', function ($ruq) use ($unitName, $unitCode) {
                          if ($unitName) $ruq->orWhere('name', $unitName);
                          if ($unitCode) $ruq->orWhere('code', $unitCode);
                      });
                  });
            });
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
        if ($request->filled('body'))             $query->where('accrediting_body', $request->input('body'));
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

        $this->notifyContactUser($compliance, 'approved',
            "Your compliance submission for \"{$compliance->title}\" has been approved by QA Admin."
        );

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

        $this->notifyContactUser($compliance, 'rejected',
            "Your compliance submission for \"{$compliance->title}\" was rejected. Reason: {$validated['rejection_reason']}"
        );

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
        ]);

        $item->update([
            'evidence_link' => $validated['evidence_link'],
            'status'        => ($item->status === 'approved' && $user->usertype !== 'QA Admin') ? 'under_review' : ($item->status === 'approved' ? 'approved' : 'under_review'),
            'admin_remarks' => null, // Clear past rejection remarks on new submission
        ]);

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

        $query = ComplianceRecord::with(['program', 'recommendationItems']);

        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->whereHas('program', fn ($q) => $q->where('college_id', $collegeId));
        } elseif ($user->usertype === 'Head of Unit') {
            $unitFilter = $this->userScopeFilter($user);
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
                  ->orWhereHas('program', fn ($pq) => $pq->where('program_code', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status'))   $query->where('status', $request->input('status'));
        if ($request->filled('body'))     $query->where('accrediting_body', $request->input('body'));
        if ($request->filled('category')) $query->where('category', 'like', '%' . $request->input('category') . '%');
        if ($request->filled('area'))     $query->where('area', 'like', '%' . $request->input('area') . '%');

        $records = $query->orderBy('due_date', 'asc')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="compliance_report_' . date('Ymd_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Academic Program', 'Accrediting Body', 'School/College',
                'Task Title', 'Area', 'Category', 'Priority', 'Status',
                'Due Date', 'Responsible Unit', 'Compliance Rate (%)',
                'Total Recommendations', 'Completed Recommendations',
            ]);

            foreach ($records as $r) {
                $total     = $r->recommendationItems->count();
                $completed = $r->recommendationItems->where('is_completed', true)->count();
                $rate      = $total > 0 ? round(($completed / $total) * 100) : 0;
                fputcsv($file, [
                    $r->compliance_record_id,
                    ($r->program->program_code ?? '') . ' - ' . ($r->program->program_name ?? 'N/A'),
                    $r->accrediting_body,
                    $r->school,
                    $r->title,
                    $r->area,
                    $r->category,
                    $r->priority,
                    $r->status,
                    $r->due_date ? $r->due_date->format('Y-m-d') : 'N/A',
                    $r->responsible_unit ?? 'Unassigned',
                    $rate, $total, $completed,
                ]);
            }

            fclose($file);
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
