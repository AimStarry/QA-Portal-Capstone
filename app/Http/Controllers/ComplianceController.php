<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
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
                $sq->orWhere('responsible_unit', 'like', '%All Units%')
                  ->orWhere('responsible_unit', 'like', '%All Departments%');
                if ($unitName) $sq->orWhere('responsible_unit', 'like', "%{$unitName}%");
                if ($unitCode) $sq->orWhere('responsible_unit', 'like', "%{$unitCode}%");
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

        // Query records with recommendation items and assignments eager-loaded
        $query = ComplianceRecord::with(['program', 'recommendationItems', 'assignments.program', 'assignments.responsibleUnit']);
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $query->where(function($q) use ($collegeId) {
                $q->whereHas('program', fn($pq) => $pq->where('college_id', $collegeId))
                  ->orWhereHas('assignments.program', fn($pq) => $pq->where('college_id', $collegeId));
            });
        } elseif ($user->usertype === 'Head of Unit') {
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id;
            $query->where(function($q) use ($unitFilter, $unitName, $unitCode, $userUnitId) {
                $q->where($unitFilter)
                  ->orWhereHas('assignments', function($aq) use ($unitName, $unitCode, $userUnitId) {
                      if ($userUnitId) {
                          $aq->where('responsible_unit_id', $userUnitId);
                      }
                      $aq->orWhereHas('responsibleUnit', function($ruq) use ($unitName, $unitCode) {
                          if ($unitName) $ruq->orWhere('name', $unitName);
                          if ($unitCode) $ruq->orWhere('code', $unitCode);
                      });
                  });
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
                  })
                  ->orWhereHas('assignments.program', function($pq) use ($search) {
                      $pq->where('program_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('assignments.responsibleUnit', function($ruq) use ($search) {
                      $ruq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
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
            $query->where(function($q) use ($request) {
                $unit = $request->input('responsible_unit');
                $q->where('responsible_unit', $unit)
                  ->orWhereHas('assignments.responsibleUnit', function($ruq) use ($unit) {
                      $ruq->where('name', $unit)->orWhere('code', $unit);
                  });
            });
        }

        $complianceRecords = $query->orderBy('due_date', 'asc')->get();

        $pendingApprovals = ($role === 'QA Admin')
            ? ComplianceRecord::with(['program', 'recommendationItems', 'assignments.program', 'assignments.responsibleUnit'])
                ->where(function($q) {
                    $q->where('approval_state', 'Pending Approval')
                      ->orWhereHas('assignments', fn($aq) => $aq->where('approval_state', 'Pending Approval'));
                })
                ->orderBy('updated_at', 'desc')->get()
            : collect();

        // Ensure all administrative units in the units table are synced to responsible_units table
        $allUnits = \App\Models\Unit::all();
        foreach ($allUnits as $u) {
            \App\Models\ResponsibleUnit::firstOrCreate(
                ['unit_id' => $u->unit_id],
                ['name' => $u->name, 'code' => $u->code]
            );
        }

        $responsibleUnits = ComplianceRecord::distinct()->pluck('responsible_unit')->filter()->sort()->values();
        $dbResponsibleUnits = \App\Models\ResponsibleUnit::with(['laboratories', 'users', 'parent', 'children'])->orderBy('name')->get();
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
    /**
     * Store a newly created resource in storage. (Admin or Responsible Unit)
     */
    public function store(Request $request)
    {
        if ($request->filled('category') && !$request->filled('categories')) {
            $request->merge(['categories' => [$request->input('category')]]);
        }
        if (!$request->filled('categories')) {
            $request->merge(['categories' => ['General']]);
        }
        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) {
                $request->merge(['responsible_unit' => $ru->name]);
            }
        }

        $validated = $request->validate([
            'program_id' => 'nullable|exists:programs,program_id',
            'program_ids' => 'nullable|array',
            'program_ids.*' => 'nullable',
            'responsible_unit_id' => 'nullable|exists:responsible_units,responsible_unit_id',
            'responsible_unit_ids' => 'nullable|array',
            'responsible_unit_ids.*' => 'nullable',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'priority' => ['nullable', Rule::in(['Critical', 'High', 'Medium', 'Low'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'document_link' => 'nullable|url',
            'accrediting_body' => 'required|string|max:255',
            'school' => 'nullable|string|max:255',
            'schools' => 'nullable|array',
            'schools.*' => 'nullable|string|max:255',
            'recommendation' => 'nullable|string',
            'recommendations' => 'required|array|min:1',
            'recommendations.*' => 'required|string|max:1000',
            'categories' => 'required|array|min:1',
            'categories.*' => 'required|string|max:255',
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'visit_date' => 'nullable|date',
        ]);

        if ($request->has('schools') && is_array($request->schools)) {
            $filteredSchools = array_values(array_filter(array_map('trim', $request->schools)));
            if (!empty($filteredSchools)) {
                $validated['school'] = implode('; ', $filteredSchools);
            }
        }
        unset($validated['schools']);
        if (empty($validated['school'])) {
            $validated['school'] = 'General';
        }

        $programIds = [];
        if (!empty($validated['program_ids'])) {
            $programIds = array_values(array_unique(array_filter(array_map('intval', $validated['program_ids']))));
        } elseif (!empty($validated['program_id'])) {
            $programIds = [(int)$validated['program_id']];
        }

        $rawUnitIds = $request->input('responsible_unit_ids', []);
        $isAllUnits = is_array($rawUnitIds) && (in_array('all', $rawUnitIds) || in_array('ALL_UNITS', $rawUnitIds));

        $unitIds = [];
        if ($isAllUnits) {
            $unitIds = \App\Models\ResponsibleUnit::pluck('responsible_unit_id')->toArray();
        } else {
            if (!empty($validated['responsible_unit_ids'])) {
                $unitIds = array_values(array_unique(array_filter(array_map('intval', $validated['responsible_unit_ids']))));
            } elseif (!empty($validated['responsible_unit_id'])) {
                $unitIds = [(int)$validated['responsible_unit_id']];
            }
        }

        unset($validated['program_ids'], $validated['responsible_unit_ids']);

        $validated['program_id'] = $programIds[0] ?? null;
        $validated['responsible_unit_id'] = $unitIds[0] ?? null;

        if ($isAllUnits) {
            $validated['responsible_unit'] = 'All Departments & Units';
        } elseif (!empty($unitIds)) {
            $unitNames = \App\Models\ResponsibleUnit::whereIn('responsible_unit_id', $unitIds)->pluck('name')->toArray();
            if (!empty($unitNames)) {
                $validated['responsible_unit'] = implode('; ', $unitNames);
            }
        }

        $validated['category'] = implode(', ', array_map('trim', $validated['categories']));
        unset($validated['categories']);

        $validated['area'] = implode(', ', array_map('trim', $validated['areas']));
        unset($validated['areas']);

        // Default contact person if empty:
        if (empty($validated['contact_person'])) {
            $assignedUser = $this->resolveContactUser($validated);
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

        $validated['recommendation'] = implode('; ', $validated['recommendations']);

        if ($role === 'Unit or Department') {
            $validated['approval_state'] = 'Pending Approval';
            $validated['pending_status'] = 'Compliant';
            $validated['pending_document_link'] = $validated['document_link'] ?? null;
            $validated['status'] = 'Pending';
            $validated['document_link'] = null;
            $validated['workflow_stage'] = 'admin_reviewing';
            $message = 'Compliance task logged and submitted to QA Admin for approval.';
        } else {
            $validated['approval_state'] = 'None';
            $validated['workflow_stage'] = 'recommendation_created';
            $message = 'Compliance task logged successfully.';
        }

        $recommendations = $validated['recommendations'];
        unset($validated['recommendations']);

        $compliance = ComplianceRecord::create($validated);

        // Create ComplianceAssignments per selected School / College (skipping if a specific program under that school is assigned)
        $schoolsList = [];
        if (!empty($validated['school']) && $validated['school'] !== 'General') {
            $schoolsList = array_values(array_filter(array_map('trim', explode(';', $validated['school']))));
        }

        $assignedProgramColleges = !empty($programIds)
            ? Program::whereIn('program_id', $programIds)->with('college')->get()->pluck('college.name')->filter()->toArray()
            : [];
        $filteredSchoolsList = array_diff($schoolsList, $assignedProgramColleges);

        foreach ($filteredSchoolsList as $sName) {
            $compliance->assignments()->create([
                'school_name' => $sName,
                'responsible_unit_id' => $validated['responsible_unit_id'] ?? null,
                'status' => $validated['status'],
                'approval_state' => $validated['approval_state'],
                'document_link' => ($validated['approval_state'] !== 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'pending_document_link' => ($validated['approval_state'] === 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'workflow_stage' => $validated['workflow_stage'],
            ]);
        }

        // Create ComplianceAssignments per selected Program
        foreach ($programIds as $pId) {
            $compliance->assignments()->create([
                'program_id' => $pId,
                'status' => $validated['status'],
                'approval_state' => $validated['approval_state'],
                'document_link' => ($validated['approval_state'] !== 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'pending_document_link' => ($validated['approval_state'] === 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'workflow_stage' => $validated['workflow_stage'],
            ]);
        }

        // Create ComplianceAssignments per selected Unit
        foreach ($unitIds as $uId) {
            $compliance->assignments()->create([
                'responsible_unit_id' => $uId,
                'status' => $validated['status'],
                'approval_state' => $validated['approval_state'],
                'document_link' => ($validated['approval_state'] !== 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'pending_document_link' => ($validated['approval_state'] === 'Pending Approval') ? ($validated['document_link'] ?? null) : null,
                'workflow_stage' => $validated['workflow_stage'],
            ]);
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

        // Warning Alert logic for active accreditations on assigned program
        $assignedProgramIds = array_filter(array_merge([$compliance->program_id], $programIds));
        foreach ($assignedProgramIds as $pId) {
            $prog = Program::find($pId);
            if ($prog && $compliance->status !== 'Compliant') {
                $hasActiveAccreditation = $prog->accreditations()->where('status', 'Active')->exists();
                if ($hasActiveAccreditation) {
                    session()->flash('warning', "Warning: Logging a deficient compliance task has flagged {$prog->program_code} as non-accreditable. Future accreditation roadmaps or status updates are locked until all compliance items are met.");
                    break;
                }
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

        if ($request->filled('category') && !$request->filled('categories')) {
            $request->merge(['categories' => [$request->input('category')]]);
        }
        if (!$request->filled('categories')) {
            $request->merge(['categories' => ['General']]);
        }
        if ($request->filled('responsible_unit_id')) {
            $ru = \App\Models\ResponsibleUnit::find($request->responsible_unit_id);
            if ($ru) {
                $request->merge(['responsible_unit' => $ru->name]);
            }
        }

        $validated = $request->validate([
            'program_id' => 'nullable|exists:programs,program_id',
            'program_ids' => 'nullable|array',
            'program_ids.*' => 'nullable',
            'responsible_unit_id' => 'nullable|exists:responsible_units,responsible_unit_id',
            'responsible_unit_ids' => 'nullable|array',
            'responsible_unit_ids.*' => 'nullable',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Compliant', 'Non-Compliant', 'Pending'])],
            'priority' => ['nullable', Rule::in(['Critical', 'High', 'Medium', 'Low'])],
            'due_date' => 'nullable|date',
            'responsible_unit' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'document_link' => 'nullable|url',
            'accrediting_body' => 'required|string|max:255',
            'school' => 'nullable|string|max:255',
            'schools' => 'nullable|array',
            'schools.*' => 'nullable|string|max:255',
            'recommendation' => 'nullable|string',
            'recommendations' => 'required|array|min:1',
            'recommendations.*' => 'required|string|max:1000',
            'categories' => 'required|array|min:1',
            'categories.*' => 'required|string|max:255',
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'visit_date' => 'nullable|date',
        ]);

        if ($request->has('schools') && is_array($request->schools)) {
            $filteredSchools = array_values(array_filter(array_map('trim', $request->schools)));
            if (!empty($filteredSchools)) {
                $validated['school'] = implode('; ', $filteredSchools);
            }
        }
        unset($validated['schools']);
        if (empty($validated['school'])) {
            $validated['school'] = 'General';
        }

        $programIds = [];
        if (!empty($validated['program_ids'])) {
            $programIds = array_values(array_unique(array_filter(array_map('intval', $validated['program_ids']))));
        } elseif (!empty($validated['program_id'])) {
            $programIds = [(int)$validated['program_id']];
        }

        $rawUnitIds = $request->input('responsible_unit_ids', []);
        $isAllUnits = is_array($rawUnitIds) && (in_array('all', $rawUnitIds) || in_array('ALL_UNITS', $rawUnitIds));

        $unitIds = [];
        if ($isAllUnits) {
            $unitIds = \App\Models\ResponsibleUnit::pluck('responsible_unit_id')->toArray();
        } else {
            if (!empty($validated['responsible_unit_ids'])) {
                $unitIds = array_values(array_unique(array_filter(array_map('intval', $validated['responsible_unit_ids']))));
            } elseif (!empty($validated['responsible_unit_id'])) {
                $unitIds = [(int)$validated['responsible_unit_id']];
            }
        }

        unset($validated['program_ids'], $validated['responsible_unit_ids']);

        $validated['program_id'] = $programIds[0] ?? null;
        $validated['responsible_unit_id'] = $unitIds[0] ?? null;

        if ($isAllUnits) {
            $validated['responsible_unit'] = 'All Departments & Units';
        } elseif (!empty($unitIds)) {
            $unitNames = \App\Models\ResponsibleUnit::whereIn('responsible_unit_id', $unitIds)->pluck('name')->toArray();
            if (!empty($unitNames)) {
                $validated['responsible_unit'] = implode('; ', $unitNames);
            }
        }

        $validated['category'] = implode(', ', array_map('trim', $validated['categories']));
        unset($validated['categories']);

        $validated['area'] = implode(', ', array_map('trim', $validated['areas']));
        unset($validated['areas']);

        $validated['approval_state'] = 'None';
        $validated['rejection_reason'] = null;

        if (empty($validated['contact_person'])) {
            $assignedUser = $this->resolveContactUser($validated);
            if ($assignedUser) {
                $validated['contact_person'] = $assignedUser->name;
                $validated['contact_email'] = $assignedUser->email ?? ($assignedUser->username . '@hau.edu.ph');
            }
        }

        $validated['recommendation'] = implode('; ', $validated['recommendations']);

        $recommendations = $validated['recommendations'];
        unset($validated['recommendations']);

        $compliance->update($validated);

        // Sync school assignments
        $schoolsList = [];
        if (!empty($validated['school']) && $validated['school'] !== 'General') {
            $schoolsList = array_values(array_filter(array_map('trim', explode(';', $validated['school']))));
        }

        $assignedProgramColleges = !empty($programIds)
            ? Program::whereIn('program_id', $programIds)->with('college')->get()->pluck('college.name')->filter()->toArray()
            : [];
        $filteredSchoolsList = array_diff($schoolsList, $assignedProgramColleges);

        $existingSchoolAss = $compliance->assignments()->whereNotNull('school_name')->pluck('school_name')->toArray();
        foreach (array_diff($filteredSchoolsList, $existingSchoolAss) as $sName) {
            $compliance->assignments()->create([
                'school_name' => $sName,
                'responsible_unit_id' => $validated['responsible_unit_id'] ?? null,
                'status' => $validated['status'],
                'approval_state' => 'None',
                'document_link' => $validated['document_link'] ?? null,
                'workflow_stage' => $compliance->workflow_stage ?? 'recommendation_created',
            ]);
        }
        $compliance->assignments()->whereNotNull('school_name')->whereNotIn('school_name', $filteredSchoolsList)->delete();

        // Sync program assignments
        $existingProgAss = $compliance->assignments()->whereNotNull('program_id')->pluck('program_id')->toArray();
        foreach (array_diff($programIds, $existingProgAss) as $pId) {
            $compliance->assignments()->create([
                'program_id' => $pId,
                'status' => $validated['status'],
                'approval_state' => 'None',
                'document_link' => $validated['document_link'] ?? null,
                'workflow_stage' => $compliance->workflow_stage ?? 'recommendation_created',
            ]);
        }
        $compliance->assignments()->whereNotNull('program_id')->whereNotIn('program_id', $programIds)->delete();

        // Sync unit assignments
        $existingUnitAss = $compliance->assignments()->whereNull('school_name')->whereNull('program_id')->whereNotNull('responsible_unit_id')->pluck('responsible_unit_id')->toArray();
        foreach (array_diff($unitIds, $existingUnitAss) as $uId) {
            $compliance->assignments()->create([
                'responsible_unit_id' => $uId,
                'status' => $validated['status'],
                'approval_state' => 'None',
                'document_link' => $validated['document_link'] ?? null,
                'workflow_stage' => $compliance->workflow_stage ?? 'recommendation_created',
            ]);
        }
        $compliance->assignments()->whereNull('school_name')->whereNull('program_id')->whereNotNull('responsible_unit_id')->whereNotIn('responsible_unit_id', $unitIds)->delete();

        // Update responsible_unit_id across all existing assignments for this task
        if (!empty($validated['responsible_unit_id'])) {
            $compliance->assignments()->whereNull('responsible_unit_id')->update(['responsible_unit_id' => $validated['responsible_unit_id']]);
        }

        // Sync recommendation items
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
     * Propose an update to status or document link (Responsible Unit / Program Head)
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
            'assignment_id' => 'nullable|exists:compliance_assignments,id',
            'pending_document_link' => 'required|url',
            'action_plan' => 'required|string',
            'responsible_unit' => 'nullable|string|max:255',
            'responsible_unit_id' => 'nullable|exists:responsible_units,responsible_unit_id',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
        ]);

        $assignment = null;
        if (!empty($validated['assignment_id'])) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)
                ->find($validated['assignment_id']);
        }

        if (!$assignment) {
            $user = auth()->user();
            $userUnitId = $user->responsible_unit_id ?? $user->unit_id;
            $userCollegeId = $user->college_id;

            $assignment = $compliance->assignments()
                ->where(function($q) use ($userUnitId, $userCollegeId) {
                    if ($userUnitId) {
                        $q->orWhere('responsible_unit_id', $userUnitId);
                    }
                    if ($userCollegeId) {
                        $q->orWhereHas('program', fn($pq) => $pq->where('college_id', $userCollegeId));
                    }
                })->first() ?? $compliance->assignments()->first();
        }

        if ($assignment) {
            $assignment->update([
                'pending_document_link' => $validated['pending_document_link'],
                'action_plan' => $validated['action_plan'],
                'approval_state' => 'Pending Approval',
                'workflow_stage' => 'admin_reviewing',
                'rejection_reason' => null,
            ]);
        }

        $compliancePayload = [
            'pending_status' => 'Compliant',
            'action_plan' => $validated['action_plan'],
            'responsible_unit' => $validated['responsible_unit'] ?? $compliance->responsible_unit,
            'responsible_unit_id' => $validated['responsible_unit_id'] ?? $compliance->responsible_unit_id,
            'contact_person' => $validated['contact_person'] ?? $compliance->contact_person,
            'contact_email' => $validated['contact_email'] ?? $compliance->contact_email,
            'approval_state' => 'Pending Approval',
            'workflow_stage' => 'admin_reviewing',
            'rejection_reason' => null,
        ];

        // Only set global pending_document_link if no sub-assignments exist
        if ($compliance->assignments()->count() === 0) {
            $compliancePayload['pending_document_link'] = $validated['pending_document_link'];
        }

        $compliance->update($compliancePayload);

        return redirect()->route('compliance.index')->with('success', 'Action plan & evidence link submitted for target assignment. Awaiting QA Admin review.');
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
        $assignmentId = $request->input('assignment_id');

        if ($assignmentId) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)->find($assignmentId);
            if ($assignment && $assignment->approval_state === 'Pending Approval') {
                $assignment->update([
                    'status' => 'Compliant',
                    'document_link' => $assignment->pending_document_link ?? $assignment->document_link,
                    'pending_document_link' => null,
                    'approval_state' => 'None',
                    'rejection_reason' => null,
                    'workflow_stage' => 'compliant',
                ]);
            }
        } else {
            foreach ($compliance->assignments()->where('approval_state', 'Pending Approval')->get() as $assignment) {
                $assignment->update([
                    'status' => 'Compliant',
                    'document_link' => $assignment->pending_document_link ?? $assignment->document_link,
                    'pending_document_link' => null,
                    'approval_state' => 'None',
                    'rejection_reason' => null,
                    'workflow_stage' => 'compliant',
                ]);
            }
        }

        $remainingPending = $compliance->assignments()->where('approval_state', 'Pending Approval')->count();
        $hasNonCompliant = $compliance->assignments()->where('status', '!=', 'Compliant')->exists();

        $compliancePayload = [
            'status' => $hasNonCompliant ? 'Pending' : 'Compliant',
            'pending_status' => null,
            'pending_document_link' => null,
            'approval_state' => $remainingPending > 0 ? 'Pending Approval' : 'None',
            'rejection_reason' => null,
            'workflow_stage' => $hasNonCompliant ? 'admin_reviewing' : 'compliant',
        ];

        if ($compliance->assignments()->count() === 0 && $compliance->pending_document_link) {
            $compliancePayload['document_link'] = $compliance->pending_document_link;
        }

        $compliance->update($compliancePayload);

        return redirect()->back()->with('success', 'Compliance update approved. Target status updated to Compliant.');
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
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
            'assignment_id' => 'nullable|exists:compliance_assignments,id',
        ]);

        $assignmentId = $validated['assignment_id'] ?? null;

        if ($assignmentId) {
            $assignment = ComplianceAssignment::where('compliance_record_id', $compliance->compliance_record_id)->find($assignmentId);
            if ($assignment) {
                $assignment->update([
                    'approval_state' => 'Rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'workflow_stage' => 'recommendation_created',
                ]);
            }
        } else {
            foreach ($compliance->assignments()->where('approval_state', 'Pending Approval')->get() as $assignment) {
                $assignment->update([
                    'approval_state' => 'Rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'workflow_stage' => 'recommendation_created',
                ]);
            }
        }

        $remainingPending = $compliance->assignments()->where('approval_state', 'Pending Approval')->count();

        $compliance->update([
            'approval_state' => $remainingPending > 0 ? 'Pending Approval' : 'Rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'workflow_stage' => 'recommendation_created',
        ]);

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

    /**
     * Resolve the Head of Unit / Dean / Principal user for given validation payload.
     */
    private function resolveContactUser(array $validated)
    {
        $assignedUser = null;
        if (!empty($validated['responsible_unit_id'])) {
            $ru = \App\Models\ResponsibleUnit::with(['users', 'parent.users'])->find($validated['responsible_unit_id']);
            if ($ru) {
                $assignedUser = $ru->users->first() 
                    ?? \App\Models\User::where('responsible_unit_id', $ru->responsible_unit_id)->first()
                    ?? ($ru->unit_id ? \App\Models\User::where('unit_id', $ru->unit_id)->first() : null)
                    ?? ($ru->parent ? $ru->parent->users->first() : null);
            }
        }
        if (!$assignedUser && !empty($validated['responsible_unit'])) {
            $ru = \App\Models\ResponsibleUnit::where('name', $validated['responsible_unit'])
                ->orWhere('code', $validated['responsible_unit'])
                ->first();
            if ($ru) {
                $assignedUser = $ru->users->first() 
                    ?? \App\Models\User::where('responsible_unit_id', $ru->responsible_unit_id)->first()
                    ?? ($ru->unit_id ? \App\Models\User::where('unit_id', $ru->unit_id)->first() : null)
                    ?? ($ru->parent ? $ru->parent->users->first() : null);
            }
        }
        return $assignedUser;
    }
}
