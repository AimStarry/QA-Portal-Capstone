<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
use App\Models\RecommendationItem;
use App\Models\RiskItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the portal dashboard based on user role.
     */
    public function index()
    {
        // Get active role from session, default to QA Admin
        $role = session('active_role', 'QA Admin');
        $user = auth()->user();

        if ($user->usertype !== 'QA Admin') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        }

        // Initialize variables with university-wide counts as fallback
        $totalPrograms = Program::count();
        $totalAccreditations = Accreditation::count();
        $totalRisks = RiskItem::count();
        $totalAccreditingBodies = Accreditation::distinct()->pluck('accrediting_body')->filter()->count();
        $activeAccreditations = Accreditation::where('status', 'Active')->count();

        $liveOfferingsCount = Program::count();
        $liveAccreditableCount = Program::where('is_accreditable', true)->count();
        $liveLocallyAccreditedCount = Program::whereHas('accreditations', fn($q) => $q->where('type', 'Local')->where('status', 'Active'))->count();
        $liveInternationallyAccreditedCount = Program::whereHas('accreditations', fn($q) => $q->where('type', 'International')->where('status', 'Active'))->count();
        // Accredited = accreditable programs with at least one real active accreditation (not just Candidate/Associate)
        $liveAccreditedCount = Program::whereHas('accreditations', fn($q) => $q->where('status', 'Active')
                ->whereNotIn('level_or_tier', ['Candidate', 'Associate']))
            ->count();

        // Define query scopes / filters
        $collegeId = $user->college_id;
        $unitName = $user->unit->name ?? '';
        $unitCode = $user->unit->code ?? '';

        $unitFilter = function($q) use ($unitName, $unitCode) {
            $q->where(function($sq) use ($unitName, $unitCode) {
                if ($unitName) $sq->orWhere('responsible_unit', $unitName);
                if ($unitCode) $sq->orWhere('responsible_unit', $unitCode);
            });
        };

        // 1. Core Counts Scoping
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $totalPrograms = Program::where('college_id', $collegeId)->count();
            $totalAccreditations = Accreditation::whereHas('program', fn($q) => $q->where('college_id', $collegeId))->count();
            $totalRisks = RiskItem::whereHas('program', fn($q) => $q->where('college_id', $collegeId))->count();
            $totalAccreditingBodies = Accreditation::whereHas('program', fn($q) => $q->where('college_id', $collegeId))->distinct()->pluck('accrediting_body')->filter()->count();
            $activeAccreditations = Accreditation::where('status', 'Active')->whereHas('program', fn($q) => $q->where('college_id', $collegeId))->count();

            $liveOfferingsCount = Program::where('college_id', $collegeId)->count();
            $liveAccreditableCount = Program::where('college_id', $collegeId)->where('is_accreditable', true)->count();
            $liveLocallyAccreditedCount = Program::where('college_id', $collegeId)->whereHas('accreditations', fn($q) => $q->where('type', 'Local')->where('status', 'Active'))->count();
            $liveInternationallyAccreditedCount = Program::where('college_id', $collegeId)->whereHas('accreditations', fn($q) => $q->where('type', 'International')->where('status', 'Active'))->count();
            // Accredited = accreditable programs with at least one real active accreditation (not just Candidate/Associate)
            $liveAccreditedCount = Program::where('college_id', $collegeId)
                ->whereHas('accreditations', fn($q) => $q->where('status', 'Active')
                    ->whereNotIn('level_or_tier', ['Candidate', 'Associate']))
                ->count();
        }

        // 2. Percentage accomplishment by type
        // Base records for checking items:
        if ($user->usertype === 'QA Admin') {
            $baseRecordsQuery = ComplianceRecord::query();
        } elseif ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $baseRecordsQuery = ComplianceRecord::whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        } else {
            $baseRecordsQuery = ComplianceRecord::where($unitFilter);
        }

        $localBodies = Accreditation::where('type', 'Local')->distinct()->pluck('accrediting_body')->toArray();
        $localRecordIds = (clone $baseRecordsQuery)->whereIn('accrediting_body', $localBodies)->pluck('compliance_record_id');
        $localTotalItems = RecommendationItem::whereIn('compliance_record_id', $localRecordIds)->count();
        $localCompleted = RecommendationItem::whereIn('compliance_record_id', $localRecordIds)->where('is_completed', true)->count();
        $localPercentage = $localTotalItems > 0 ? round(($localCompleted / $localTotalItems) * 100) : 0;

        $intlBodies = Accreditation::where('type', 'International')->distinct()->pluck('accrediting_body')->toArray();
        $intlRecordIds = (clone $baseRecordsQuery)->whereIn('accrediting_body', $intlBodies)->pluck('compliance_record_id');
        $intlTotalItems = RecommendationItem::whereIn('compliance_record_id', $intlRecordIds)->count();
        $intlCompleted = RecommendationItem::whereIn('compliance_record_id', $intlRecordIds)->where('is_completed', true)->count();
        $intlPercentage = $intlTotalItems > 0 ? round(($intlCompleted / $intlTotalItems) * 100) : 0;

        $regBodies = Accreditation::where('type', 'Regulatory')->distinct()->pluck('accrediting_body')->toArray();
        $regRecordIds = (clone $baseRecordsQuery)->whereIn('accrediting_body', $regBodies)->pluck('compliance_record_id');
        $regTotalItems = RecommendationItem::whereIn('compliance_record_id', $regRecordIds)->count();
        $regCompleted = RecommendationItem::whereIn('compliance_record_id', $regRecordIds)->where('is_completed', true)->count();
        $regulatoryPercentage = $regTotalItems > 0 ? round(($regCompleted / $regTotalItems) * 100) : 0;

        // 3. Compliance rates per body
        $bodies = (clone $baseRecordsQuery)->distinct()->pluck('accrediting_body')->filter()->values();
        $bodyComplianceRates = [];
        foreach ($bodies as $body) {
            $recordIds = (clone $baseRecordsQuery)->where('accrediting_body', $body)->pluck('compliance_record_id');
            $totalItems = RecommendationItem::whereIn('compliance_record_id', $recordIds)->count();
            $completedItems = RecommendationItem::whereIn('compliance_record_id', $recordIds)->where('is_completed', true)->count();
            $bodyComplianceRates[$body] = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
        }

        // 4. Accredited programs list — only is_accreditable programs with any active accreditation (excluding Candidate/Associate)
        if ($user->usertype === 'QA Admin') {
            $accreditedPrograms = Program::whereHas('accreditations', function($q) {
                    $q->where('status', 'Active')
                      ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
                })->with(['college', 'accreditations' => function($q) {
                    $q->where('status', 'Active')
                      ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
                }])->get();
        } elseif ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $accreditedPrograms = Program::where('college_id', $collegeId)
                ->whereHas('accreditations', function($q) {
                    $q->where('status', 'Active')
                      ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
                })->with(['college', 'accreditations' => function($q) {
                    $q->where('status', 'Active')
                      ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
                }])->get();
        } else {
            $accreditedPrograms = collect();
        }

        $allAccreditingBodies = Accreditation::where('status', 'Active')->distinct()->pluck('accrediting_body')->filter()->values();
        $totalAccreditedProgramsCount = $accreditedPrograms->count();

        // Calculate dynamic unique accredited programs count (excluding Candidate/Associate)
        $programsCountsQuery = Program::whereHas('accreditations', function($q) {
                $q->where('status', 'Active')
                  ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
            });
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $programsCountsQuery->where('college_id', $collegeId);
        }
        $accreditationCounts = [
            '' => (clone $programsCountsQuery)->count(),
        ];
        foreach ($allAccreditingBodies as $body) {
            $accreditationCounts[$body] = (clone $programsCountsQuery)
                ->whereHas('accreditations', function($q) use ($body) {
                    $q->where('status', 'Active')
                      ->where('accrediting_body', $body)
                      ->whereNotIn('level_or_tier', ['Candidate', 'Associate']);
                })->count();
        }

        // 5. Overdue compliance
        $overdueQuery = ComplianceRecord::with('program')
            ->whereIn('status', ['Non-Compliant', 'Pending'])
            ->where('approval_state', '!=', 'Pending Approval')
            ->where('due_date', '<', now()->toDateString());
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $overdueQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        } elseif ($user->usertype === 'Head of Unit') {
            $overdueQuery->where($unitFilter);
        }
        $overdueCompliance = $overdueQuery->orderBy('due_date', 'asc')->get();

        // 6. Recently completed recommendations
        $recentRecsQuery = RecommendationItem::with(['complianceRecord.program'])->where('is_completed', true);
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $recentRecsQuery->whereHas('complianceRecord.program', fn($q) => $q->where('college_id', $collegeId));
        } elseif ($user->usertype === 'Head of Unit') {
            $recentRecsQuery->whereHas('complianceRecord', $unitFilter);
        }
        $recentlyCompletedRecommendations = $recentRecsQuery->orderBy('completed_at', 'desc')->take(10)->get();

        // 7. Levels summary
        $levelsQuery = Accreditation::selectRaw('level_or_tier, count(*) as count');
        if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
            $levelsQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
        }
        $levelsSummary = $levelsQuery->groupBy('level_or_tier')->orderBy('count', 'desc')->get();


        // 8. Viewport Specific Queries
        if ($role === 'QA Admin') {
            $pendingApprovals = ComplianceRecord::with(['program', 'recommendationItems'])
                ->where('approval_state', 'Pending Approval')
                ->orderBy('updated_at', 'desc')
                ->get();

            $warningAccreditations = Accreditation::with('program')
                ->whereIn('status', ['Expired', 'Expiring Soon'])
                ->orderBy('expiry_date', 'asc')
                ->take(5)
                ->get();

            $urgentCompliance = ComplianceRecord::with('program')
                ->whereIn('status', ['Non-Compliant', 'Pending'])
                ->where('approval_state', '!=', 'Pending Approval')
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();

            $criticalRisks = RiskItem::with('program')
                ->where(function($q) {
                    $q->where('likelihood', 'High')->orWhere('impact', 'High');
                })
                ->where('status', '!=', 'Mitigated')
                ->take(5)
                ->get();

            return view('dashboard', compact(
                'role',
                'totalPrograms',
                'totalAccreditations',
                'totalAccreditingBodies',
                'totalRisks',
                'activeAccreditations',
                'bodyComplianceRates',
                'accreditedPrograms',
                'allAccreditingBodies',
                'totalAccreditedProgramsCount',
                'pendingApprovals',
                'warningAccreditations',
                'urgentCompliance',
                'overdueCompliance',
                'criticalRisks',
                'localPercentage',
                'intlPercentage',
                'regulatoryPercentage',
                'recentlyCompletedRecommendations',
                'levelsSummary',
                'liveOfferingsCount',
                'liveAccreditableCount',
                'liveAccreditedCount',
                'liveLocallyAccreditedCount',
                'liveInternationallyAccreditedCount',
                'accreditationCounts'
            ));
        } else {
            // Unit or Department Viewport (For Deans or Heads of Units or QA Admin in viewport mode)
            $unitTotalQuery = ComplianceRecord::query();
            if ($user->usertype === 'Dean' || $user->usertype === 'Principal') {
                $unitTotalQuery->whereHas('program', fn($q) => $q->where('college_id', $collegeId));
            } elseif ($user->usertype === 'Head of Unit') {
                $unitTotalQuery->where($unitFilter);
            }

            $unitTotalTasks = (clone $unitTotalQuery)->count();
            $unitPendingApprovalsCount = (clone $unitTotalQuery)->where('approval_state', 'Pending Approval')->count();
            $unitRejectedCount = (clone $unitTotalQuery)->where('approval_state', 'Rejected')->count();
            $unitCompliantCount = (clone $unitTotalQuery)->where('status', 'Compliant')->count();

            $rejectedTasks = (clone $unitTotalQuery)->with('program')
                ->where('approval_state', 'Rejected')
                ->orderBy('updated_at', 'desc')
                ->get();

            $awaitingTasks = (clone $unitTotalQuery)->with('program')
                ->where('approval_state', 'Pending Approval')
                ->orderBy('updated_at', 'desc')
                ->get();

            // Pending tasks = items not yet submitted and not compliant (action required)
            $pendingTasks = (clone $unitTotalQuery)->with('program')
                ->whereIn('status', ['Pending', 'Non-Compliant'])
                ->where('approval_state', '!=', 'Pending Approval')
                ->orderBy('due_date', 'asc')
                ->get();

            $unitPendingCount = $pendingTasks->count();

            return view('dashboard', compact(
                'role',
                'unitTotalTasks',
                'unitPendingApprovalsCount',
                'unitRejectedCount',
                'unitCompliantCount',
                'bodyComplianceRates',
                'accreditedPrograms',
                'allAccreditingBodies',
                'totalAccreditedProgramsCount',
                'rejectedTasks',
                'awaitingTasks',
                'pendingTasks',
                'unitPendingCount',
                'overdueCompliance',
                'localPercentage',
                'intlPercentage',
                'regulatoryPercentage',
                'recentlyCompletedRecommendations',
                'levelsSummary',
                'liveOfferingsCount',
                'liveAccreditableCount',
                'liveAccreditedCount',
                'liveLocallyAccreditedCount',
                'liveInternationallyAccreditedCount',
                'totalPrograms',
                'totalAccreditations',
                'totalRisks',
                'totalAccreditingBodies',
                'activeAccreditations',
                'accreditationCounts'
            ));
        }
    }
}
