<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Accreditation;
use App\Models\ComplianceRecord;
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

        // 1. Core Counts
        $totalPrograms = Program::count();
        $totalAccreditations = Accreditation::count();
        $totalRisks = RiskItem::count();

        $activeAccreditations = Accreditation::where('status', 'Active')->count();

        // 1b. Accreditation Types breakdown (Local, International, Regulatory)
        $localAccreditations = Accreditation::where('type', 'Local')->count();
        $intlAccreditations = Accreditation::where('type', 'International')->count();
        $regulatoryAccreditations = Accreditation::where('type', 'Regulatory')->count();

        $localPercentage = $totalAccreditations > 0 ? round(($localAccreditations / $totalAccreditations) * 100) : 0;
        $intlPercentage = $totalAccreditations > 0 ? round(($intlAccreditations / $totalAccreditations) * 100) : 0;
        $regulatoryPercentage = $totalAccreditations > 0 ? round(($regulatoryAccreditations / $totalAccreditations) * 100) : 0;

        // 2. Compliance Rate per Accrediting Body (Upgraded!)
        // Get distinct accrediting bodies registered in the system
        $bodies = Accreditation::distinct()->pluck('accrediting_body');
        $bodyComplianceRates = [];

        foreach ($bodies as $body) {
            // Find programs associated with this body
            $programIds = Accreditation::where('accrediting_body', $body)->pluck('program_id');
            
            // Get compliance statistics for these programs
            $totalCompliance = ComplianceRecord::whereIn('program_id', $programIds)->count();
            $compliantCount = ComplianceRecord::whereIn('program_id', $programIds)->where('status', 'Compliant')->count();
            
            $bodyComplianceRates[$body] = $totalCompliance > 0 
                ? round(($compliantCount / $totalCompliance) * 100) 
                : 100;
        }

        // 3. PAASCU Programs List (Requirement!)
        $paascuPrograms = Program::whereHas('accreditations', function ($q) {
            $q->where('accrediting_body', 'PAASCU');
        })->with(['accreditations' => function ($q) {
            $q->where('accrediting_body', 'PAASCU');
        }])->get();

        // 4. Role-Based Queries
        if ($role === 'QA Admin') {
            // QA Admin Viewport

            // a. Pending Compliance Approvals Queue (New Workflow!)
            $pendingApprovals = ComplianceRecord::with('program')
                ->where('approval_state', 'Pending Approval')
                ->orderBy('updated_at', 'desc')
                ->get();

            // b. Warning/Action Alerts
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
                'totalRisks',
                'activeAccreditations',
                'bodyComplianceRates',
                'paascuPrograms',
                'pendingApprovals',
                'warningAccreditations',
                'urgentCompliance',
                'criticalRisks',
                'localPercentage',
                'intlPercentage',
                'regulatoryPercentage'
            ));

        } else {
            // Responsible Unit Viewport

            // a. Proposing updates stats
            $unitTotalTasks = ComplianceRecord::count();
            $unitPendingApprovalsCount = ComplianceRecord::where('approval_state', 'Pending Approval')->count();
            $unitRejectedCount = ComplianceRecord::where('approval_state', 'Rejected')->count();
            $unitCompliantCount = ComplianceRecord::where('status', 'Compliant')->count();

            // b. Tasks assigned that are currently Rejected (need attention)
            $rejectedTasks = ComplianceRecord::with('program')
                ->where('approval_state', 'Rejected')
                ->orderBy('updated_at', 'desc')
                ->get();

            // c. Awaiting approvals list
            $awaitingTasks = ComplianceRecord::with('program')
                ->where('approval_state', 'Pending Approval')
                ->orderBy('updated_at', 'desc')
                ->get();

            return view('dashboard', compact(
                'role',
                'unitTotalTasks',
                'unitPendingApprovalsCount',
                'unitRejectedCount',
                'unitCompliantCount',
                'bodyComplianceRates',
                'paascuPrograms',
                'rejectedTasks',
                'awaitingTasks',
                'localPercentage',
                'intlPercentage',
                'regulatoryPercentage'
            ));
        }
    }
}
