@extends('layouts.app')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.03);
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Recommendations & Compliance Tracker</h2>
            <p class="text-xs sm:text-sm text-gray-500">Track documentation audits and compliance tasks. Recommendations are managed as checklists to drive compliance rates.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <!-- Single-page Compliance CSV Export -->
            <button type="button" onclick="exportComplianceCsv()" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 hover:bg-gray-50 hover:text-hau-maroon hover:border-hau-maroon/30 shadow-xs focus:outline-none transition cursor-pointer whitespace-nowrap" title="Export compliance filtered tasks as CSV">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Export CSV</span>
            </button>

            <!-- Log Compliance Task -->
            <button type="button" onclick="openAddModal()" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition cursor-pointer whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Log Compliance Task</span>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    @php
        $totalRecs = $complianceRecords->sum(fn($r) => $r->recommendationItems->count());
        $completedRecs = $complianceRecords->sum(fn($r) => $r->recommendationItems->where('is_completed', true)->count());
        $overallRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;

        $getSchoolCode = function($name) {
            if (empty($name)) return '';
            $map = [
                'School of Business and Accountancy' => 'SBA',
                'School of Arts and Sciences' => 'SAS',
                'School of Education' => 'SED',
                'School of Engineering and Architecture' => 'SEA',
                'School of Hospitality and Tourism Management' => 'SHTM',
                'School of Nursing and Allied Medical Sciences' => 'SNAMS',
                'School of Computing' => 'SOC',
                'College of Criminal Justice Education and Forensic Sciences' => 'CCJEF',
                'Basic Education' => 'BED',
                'College of Nursing (CON)' => 'CON',
                'College of Information and Communications Technology (CICT)' => 'CICT',
            ];
            $trimmed = trim($name);
            if (isset($map[$trimmed])) return $map[$trimmed];
            
            if (str_contains($trimmed, ';')) {
                $parts = array_map('trim', explode(';', $trimmed));
                $codes = [];
                foreach ($parts as $p) {
                    $codes[] = $map[$p] ?? (strlen($p) > 8 ? preg_replace('/(?<=\\w)\\w*\\s*/', '', $p) : $p);
                }
                return implode(', ', array_unique(array_filter($codes)));
            }
            
            return strlen($trimmed) > 8 ? preg_replace('/(?<=\\w)\\w*\\s*/', '', $trimmed) : $trimmed;
        };
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Tasks</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 font-mono">{{ $totalCompliance }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 border-l-4 border-l-emerald-500 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Compliant</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1 font-mono">{{ $compliantCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 border-l-4 border-l-rose-500 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Non-Compliant</p>
            <p class="text-2xl font-bold text-rose-600 mt-1 font-mono">{{ $nonCompliantCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 border-l-4 border-l-gray-400 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Pending Audit</p>
            <p class="text-2xl font-bold text-gray-500 mt-1 font-mono">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 border-l-4 border-l-hau-maroon hover:shadow-md transition">
            <p class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider">Checklist Compliance</p>
            <p class="text-2xl font-bold text-hau-maroon mt-1 font-mono">{{ $overallRate }}%</p>
        </div>
    </div>

    <!-- Pending Approvals Queue Section (QA Admin Only) -->
    @if ($role === 'QA Admin' && $pendingApprovals->isNotEmpty())
        <div class="bg-gradient-to-br from-hau-maroon/5 to-hau-gold/5 border border-hau-maroon/15 rounded-2xl overflow-hidden p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-hau-maroon/15 pb-2">
                <h3 class="font-bold text-sm text-hau-maroon uppercase tracking-wider flex items-center gap-2">
                    <span class="inline-flex rounded-full h-2 w-2 bg-hau-gold animate-pulse"></span>
                    Pending Compliance Approvals Queue
                </h3>
                <span class="text-xs font-mono bg-hau-maroon text-hau-gold-light px-2.5 py-0.5 rounded-md border border-hau-gold/30">
                    {{ $pendingApprovals->count() }} Request(s)
                </span>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
                @foreach($pendingApprovals as $pending)
                    @php
                        $pendingTotal = $pending->recommendationItems->count();
                        $pendingCompleted = $pending->recommendationItems->where('is_completed', true)->count();
                        $pendingRate = $pendingTotal > 0 ? round(($pendingCompleted / $pendingTotal) * 100) : 0;
                    @endphp
                    <div class="bg-white rounded-xl p-4 border border-hau-maroon/10 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-hau-maroon/5 text-hau-maroon" title="{{ $pending->school }}">{{ $pending->program->program_code ?? ($getSchoolCode($pending->school) ?: 'General') }}</span>
                                @if($pending->accrediting_body)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-hau-gold/15 text-hau-maroon-dark">{{ $pending->accrediting_body }}</span>
                                @endif
                                <span class="text-[11px] text-gray-400">Unit: <strong class="text-gray-600">{{ $pending->responsible_unit }}</strong></span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-hau-maroon bg-hau-maroon/5 border border-hau-maroon/20 rounded px-1.5 py-0.25 font-mono">
                                    {{ $pendingRate }}% Completed
                                </span>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm leading-snug">{{ $pending->title }}</h4>
                            @if($pending->pending_document_link)
                                <div class="text-[11px] font-mono text-gray-500 truncate">
                                    Proposed Evidence Link: <a href="{{ $pending->pending_document_link }}" target="_blank" class="text-hau-maroon hover:underline font-semibold">{{ $pending->pending_document_link }}</a>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <form action="{{ route('compliance.approve', $pending->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3.5 py-1.5 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    Approve
                                </button>
                            </form>
                            <button onclick="openRejectModal('{{ $pending->id }}', '{{ $pending->title }}')" class="inline-flex items-center px-3.5 py-1.5 border border-rose-200 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-lg transition">
                                Reject
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Summary Analytics & Checked Off Queue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Program Compliance Summary Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="p-1.5 bg-hau-maroon/10 text-hau-maroon rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Program Checklist Performance</h3>
            </div>
            <div class="overflow-y-auto overflow-x-auto max-h-64 rounded-xl border border-gray-200 pr-1">
                <table class="min-w-full divide-y divide-gray-200 text-xs relative">
                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase tracking-wider w-24">Completed</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase tracking-wider w-24">Total</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase tracking-wider w-24">Success Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($programComplianceSummary as $p)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-3 space-y-0.5">
                                    <div class="font-bold text-hau-maroon font-mono text-sm">{{ $p->code }}</div>
                                    <div class="text-[10px] text-gray-450 font-medium leading-tight truncate max-w-[200px]" title="{{ $p->name }}">{{ $p->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $p->completed }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-500">{{ $p->total }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $p->rate === 100 ? 'bg-emerald-50 text-emerald-700' : ($p->rate > 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                                        {{ $p->rate }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-400">No program performance logs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Checked Off Checklist Queue -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3 justify-between">
                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <div class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    Checked Off Checklist Queue
                </h3>
                <span class="text-xs text-gray-500 font-semibold font-mono">{{ $recentlyCompletedRecommendations->count() }} Checked Off</span>
            </div>
            <div class="overflow-y-auto max-h-64 divide-y divide-gray-100 pr-1">
                @forelse($recentlyCompletedRecommendations as $item)
                    <div class="py-2.5 flex items-start justify-between gap-3 text-xs">
                        <div class="space-y-0.5 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.25 rounded font-mono text-[9px] font-bold bg-hau-maroon/5 text-hau-maroon">{{ $item->complianceRecord->program->program_code ?? 'N/A' }}</span>
                                <span class="text-[10px] text-gray-400 font-medium truncate max-w-[180px]">{{ $item->complianceRecord->title ?? 'N/A' }}</span>
                            </div>
                            <p class="text-gray-700 font-semibold truncate max-w-[320px]" title="{{ $item->text }}">{{ $item->text }}</p>
                        </div>
                        <span class="text-[9px] text-gray-400 shrink-0 font-mono text-right leading-relaxed">{{ $item->completed_at ? $item->completed_at->format('M d h:i A') : $item->updated_at->format('M d h:i A') }}</span>
                    </div>
                @empty
                    <div class="py-6 text-center text-gray-450 italic font-sans">No recommendations checked off yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-4">
        <!-- Filter Bar Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-hau-maroon/10 text-hau-maroon rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                </div>
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Filter Tasks & Recommendations</h3>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- View Switcher -->
                <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl border border-gray-200">
                    <button type="button" id="view-btn-table" onclick="setComplianceView('table')" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-hau-maroon text-white shadow-xs flex items-center gap-1.5 transition" title="Dense Table View">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <span>Table</span>
                    </button>
                    <button type="button" id="view-btn-matrix" onclick="setComplianceView('matrix')" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition" title="Cross-Tab / Matrix Grid View">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Matrix</span>
                    </button>
                    <button type="button" id="view-btn-board" onclick="setComplianceView('board')" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition" title="Kanban Stages Board">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        <span>Board</span>
                    </button>
                    <button type="button" id="view-btn-area" onclick="setComplianceView('area')" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition" title="Grouped by Accreditation Area">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        <span>By Area</span>
                    </button>
                    <button type="button" id="view-btn-grid" onclick="setComplianceView('grid')" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition" title="Card Grid View">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span>Cards</span>
                    </button>
                </div>

                <div class="text-xs text-gray-400 font-medium flex items-center gap-1.5">
                    <span>Results:</span>
                    <span id="visible-count" class="font-bold text-hau-maroon font-mono text-sm">{{ $complianceRecords->count() }}</span>
                    <button type="button" onclick="clearAllFilters()" class="ml-2 text-xs font-semibold text-hau-maroon hover:underline flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="space-y-3">
            <!-- Search Bar (Big & Prominent) -->
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="comp-search" oninput="applyFilters(true)" placeholder="Search title, recommendation, responsible unit..." class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition shadow-xs" />
            </div>

            <!-- Filter Dropdowns Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Accrediting Body filter -->
                <div>
                    <select id="comp-body" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Accrediting Bodies">
                        <option value="">All Accrediting Bodies</option>
                        @foreach($bodies as $body)
                            <option value="{{ $body }}">{{ $body }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category filter -->
                <div>
                    <select id="comp-category" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Categories">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Area filter -->
                <div>
                    <select id="comp-area" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Areas">
                        <option value="">All Areas</option>
                        @foreach($areas as $ar)
                            <option value="{{ $ar }}">{{ $ar }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status filter -->
                <div>
                    <select id="comp-status" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Statuses">
                        <option value="">All Statuses</option>
                        <option value="Compliant">Compliant</option>
                        <option value="Non-Compliant">Non-Compliant</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>

                <!-- Priority filter -->
                <div>
                    <select id="comp-priority" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Priorities">
                        <option value="">All Priorities</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <!-- Unit or Department filter -->
                <div>
                    <select id="comp-unit" onchange="applyFilters(true)" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer text-gray-700 font-medium truncate" title="All Units & Depts">
                        <option value="">All Units & Depts</option>
                        @foreach($responsibleUnits as $unit)
                            <option value="{{ $unit }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Grid -->
    <div id="compliance-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($complianceRecords as $c)
            @php
                $leftBorderColor = 'border-l-4 border-l-hau-maroon';
                if ($c->status === 'Compliant') {
                    $leftBorderColor = 'border-l-4 border-l-emerald-500';
                } elseif ($c->status === 'Non-Compliant') {
                    $leftBorderColor = 'border-l-4 border-l-rose-500';
                } elseif ($c->due_date && $c->due_date->isPast()) {
                    $leftBorderColor = 'border-l-4 border-l-rose-400';
                }
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 {{ $leftBorderColor }} overflow-hidden flex flex-col justify-between hover:shadow-md hover:border-hau-maroon/40 transition cursor-pointer animate-fade-slide-up"
                 onclick="openDetailModal(this)"
                 data-id="{{ $c->compliance_record_id }}"
                 data-program-id="{{ $c->program_id }}"
                 data-program-code="{{ $c->program->program_code ?? 'N/A' }}"
                 data-title="{{ $c->title }}"
                 data-desc="{{ $c->description }}"
                 data-status="{{ $c->status }}"
                 data-due="{{ $c->due_date ? $c->due_date->format('Y-m-d') : '' }}"
                 data-resp="{{ $c->responsible_unit }}"
                 data-responsible-unit-id="{{ $c->responsible_unit_id }}"
                 data-contact-person="{{ $c->contact_person }}"
                 data-contact-email="{{ $c->contact_email }}"
                 data-link="{{ $c->document_link }}"
                 data-pending-status="{{ $c->pending_status }}"
                 data-pending-link="{{ $c->pending_document_link }}"
                 data-approval-state="{{ $c->approval_state }}"
                 data-rejection-reason="{{ $c->rejection_reason }}"
                 data-body="{{ $c->accrediting_body }}"
                 data-school="{{ $c->school }}"
                 data-recommendation="{{ $c->recommendation }}"
                 data-category="{{ $c->category }}"
                 data-area="{{ $c->area }}"
                 data-action-plan="{{ $c->action_plan }}"
                 data-visit-date="{{ $c->visit_date ? $c->visit_date->format('Y-m-d') : '' }}"
                 data-workflow-stage="{{ $c->workflow_stage ?? 'recommendation_created' }}"
                 data-priority="{{ $c->priority ?? 'Medium' }}"
                 data-recommendations="{{ $c->recommendationItems->toJson() }}"
                 data-assignments="{{ json_encode($c->assignments->map(fn($a) => [
                     'id' => $a->id,
                     'program_id' => $a->program_id,
                     'program_code' => $a->program->program_code ?? null,
                     'program_name' => $a->program->program_name ?? null,
                     'school_name' => $a->school_name,
                     'unit_id' => $a->responsible_unit_id,
                     'unit_name' => $a->responsibleUnit->name ?? null,
                     'unit_code' => $a->responsibleUnit->code ?? null,
                     'status' => $a->status,
                     'approval_state' => $a->approval_state,
                     'document_link' => $a->document_link,
                     'pending_document_link' => $a->pending_document_link,
                     'action_plan' => $a->action_plan,
                     'rejection_reason' => $a->rejection_reason,
                 ])) }}"
                 data-completion-rate="{{ $c->recommendationItems->count() > 0 ? round(($c->recommendationItems->where('is_completed', true)->count() / $c->recommendationItems->count()) * 100) : 0 }}">
                 
                 <!-- Top Body -->
                 <div class="p-5 space-y-3.5 flex-1 flex flex-col justify-between">
                     <div class="space-y-3">
                         <!-- Badges Row: Program / School / Agency on Left, Priority & Status on Right -->
                         <div class="flex items-center justify-between gap-2">
                             <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                 @php
                                     $isAllUnitsTask = str_contains($c->responsible_unit ?? '', 'All Departments') || str_contains($c->responsible_unit ?? '', 'All Units');
                                 @endphp
                                 @if ($isAllUnitsTask)
                                     <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-150">
                                         All Depts
                                     </span>
                                 @else
                                     @php
                                         $badgeItems = [];
                                         if ($c->assignments && $c->assignments->count() > 0) {
                                             foreach($c->assignments as $ass) {
                                                 if($ass->program) {
                                                     $badgeItems[] = ['type' => 'prog', 'id' => $ass->program_id, 'label' => $ass->program->program_code, 'link' => route('programs.show', $ass->program_id)];
                                                 } elseif($ass->school_name) {
                                                     $sCode = $getSchoolCode($ass->school_name);
                                                     if (!empty($sCode)) {
                                                         $badgeItems[] = ['type' => 'sch', 'id' => $sCode, 'label' => $sCode, 'title' => $ass->school_name];
                                                     }
                                                 } elseif($ass->responsibleUnit) {
                                                     $label = $ass->responsibleUnit->code ?? $ass->responsibleUnit->name;
                                                     $badgeItems[] = ['type' => 'unit', 'id' => $ass->responsible_unit_id, 'label' => $label];
                                                 }
                                             }
                                         } elseif($c->program) {
                                             $badgeItems[] = ['type' => 'prog', 'id' => $c->program_id, 'label' => $c->program->program_code, 'link' => route('programs.show', $c->program_id)];
                                         } elseif($c->school) {
                                             foreach(explode(';', $c->school) as $sItem) {
                                                 $sName = trim($sItem);
                                                 $sCode = $getSchoolCode($sName);
                                                 if (!empty($sCode)) {
                                                     $badgeItems[] = ['type' => 'sch', 'id' => $sCode, 'label' => $sCode, 'title' => $sName];
                                                 }
                                             }
                                         }
                                         
                                         $uniqueBadges = [];
                                         $seenKeys = [];
                                         foreach ($badgeItems as $item) {
                                             $key = $item['type'] . '_' . $item['id'];
                                             if (!in_array($key, $seenKeys)) {
                                                 $seenKeys[] = $key;
                                                 $uniqueBadges[] = $item;
                                             }
                                         }

                                         $maxVisible = 3;
                                         $visibleBadges = array_slice($uniqueBadges, 0, $maxVisible);
                                         $hiddenCount = count($uniqueBadges) - count($visibleBadges);
                                         $allLabels = array_map(fn($b) => $b['label'], $uniqueBadges);
                                     @endphp

                                     @foreach($visibleBadges as $b)
                                         @if(isset($b['link']))
                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon hover:underline">
                                                 <a href="{{ $b['link'] }}" onclick="event.stopPropagation();">{{ $b['label'] }}</a>
                                             </span>
                                         @elseif($b['type'] === 'unit')
                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700">
                                                 {{ $b['label'] }}
                                             </span>
                                         @else
                                             <span title="{{ $b['title'] ?? '' }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                                 {{ $b['label'] }}
                                             </span>
                                         @endif
                                     @endforeach

                                     @if($hiddenCount > 0)
                                         <span title="{{ implode(', ', $allLabels) }}" class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-200 cursor-help">
                                             +{{ $hiddenCount }}
                                         </span>
                                     @endif
                                 @endif
                                 @if ($c->accrediting_body)
                                     <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-hau-gold/20 text-hau-maroon border border-hau-gold/30">
                                         {{ $c->accrediting_body }}
                                     </span>
                                 @endif
                             </div>
                            
                            <div class="flex items-center gap-1.5 shrink-0">
                                <!-- Priority Badge -->
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold tracking-wide
                                    @if ($c->priority === 'Critical') bg-rose-50 text-rose-700 border border-rose-200
                                    @elseif ($c->priority === 'High') bg-amber-50 text-amber-800 border border-amber-200
                                    @elseif ($c->priority === 'Low') bg-slate-100 text-slate-600 border border-slate-200
                                    @else bg-blue-50 text-blue-700 border border-blue-150
                                    @endif">
                                    {{ $c->priority ?? 'Medium' }}
                                </span>
                                
                                <!-- Status Badge -->
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold
                                    @if ($c->status == 'Compliant') bg-emerald-50 text-emerald-700 border border-emerald-100
                                    @elseif ($c->status == 'Non-Compliant') bg-rose-50 text-rose-700 border border-rose-100
                                    @else bg-gray-50 text-gray-600 border border-gray-200
                                    @endif">
                                    {{ $c->status }}
                                </span>
                            </div>
                         </div>

                         <!-- Area & Category Badges + Title -->
                         <div class="space-y-1.5">
                             @if ($c->area || $c->category)
                                 <div class="flex flex-wrap gap-1 items-center">
                                     @if($c->area)
                                         @foreach(preg_split('/[,;]+/', $c->area) as $areaTag)
                                             @if(trim($areaTag) !== '')
                                                 <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 tracking-wider uppercase border border-slate-200/60">{{ trim($areaTag) }}</span>
                                             @endif
                                         @endforeach
                                     @endif
                                     @if($c->category)
                                         @foreach(preg_split('/[,;]+/', $c->category) as $catTag)
                                             @if(trim($catTag) !== '')
                                                 <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200 tracking-wider uppercase leading-none">{{ trim($catTag) }}</span>
                                             @endif
                                         @endforeach
                                     @endif
                                 </div>
                             @endif
                             <h4 class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 hover:text-hau-maroon transition" title="{{ $c->title }}">{{ $c->title }}</h4>
                         </div>

                         <!-- Design 3: Linear Chevron Ribbon -->
                         @php
                             $stage = $c->workflow_stage ?? 'recommendation_created';
                             $stageIndices = [
                                 'recommendation_created' => 0,
                                 'action_plan_submitted'  => 1,
                                 'admin_reviewing'        => 2,
                                 'compliant'              => 3,
                             ];
                             $currentIdx = $stageIndices[$stage] ?? 0;
                             $steps = [
                                 ['num' => '01', 'label' => 'Rec', 'full' => 'Recommendation Logged'],
                                 ['num' => '02', 'label' => 'Plan', 'full' => 'Action Plan Submitted'],
                                 ['num' => '03', 'label' => 'Review', 'full' => 'Under Admin Review'],
                                 ['num' => '04', 'label' => 'Done', 'full' => 'Compliant / Completed'],
                             ];
                         @endphp
                         <div class="bg-gray-50/70 border border-gray-150/80 rounded-lg p-1.5 flex items-center justify-between gap-1 overflow-x-auto text-[10px]">
                             @foreach($steps as $si => $st)
                                 <div class="flex items-center gap-1 shrink-0 {{ $si === $currentIdx ? 'bg-white px-2 py-0.5 rounded shadow-2xs font-bold text-hau-maroon border border-gray-200' : ($si < $currentIdx ? 'text-emerald-700 font-semibold' : 'text-gray-400 font-normal') }}" title="{{ $st['full'] }}">
                                     @if($si < $currentIdx)
                                         <span class="text-emerald-600 font-bold">✓</span>
                                     @else
                                         <span class="font-mono text-[9px] opacity-70">{{ $st['num'] }}</span>
                                     @endif
                                     <span>{{ $st['label'] }}</span>
                                 </div>

                                 @if($si < count($steps) - 1)
                                     <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                 @endif
                             @endforeach
                         </div>

                         <!-- Recommendation Checklist Progress & Active Item -->
                         @php
                             $totalRecs = $c->recommendationItems->count();
                             $completedRecs = $c->recommendationItems->where('is_completed', true)->count();
                             $recPercentage = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;
                             $pendingReco = $c->recommendationItems->firstWhere('is_completed', false) ?? $c->recommendationItems->first();
                         @endphp
                         @if ($totalRecs > 0)
                             <div class="space-y-2 pt-0.5">
                                 <div class="flex items-center justify-between text-[11px]">
                                     <span class="font-bold text-gray-700 flex items-center gap-1.5">
                                         <svg class="w-3.5 h-3.5 text-hau-maroon shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                         Recommendations
                                     </span>
                                     <span class="font-mono font-bold text-hau-maroon">
                                         <span class="completion-rate-{{ $c->id }}">{{ $recPercentage }}%</span>
                                         <span class="text-gray-400 font-normal">({{ $completedRecs }}/{{ $totalRecs }})</span>
                                     </span>
                                 </div>
                                 <!-- Completion Progress Bar -->
                                 <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                     <div class="completion-bar bg-hau-maroon h-full rounded-full transition-all duration-300" id="bar-{{ $c->id }}" style="width: {{ $recPercentage }}%"></div>
                                 </div>

                                 @if($pendingReco)
                                     <div class="flex items-center gap-2 bg-gray-50/60 p-1.5 rounded-md text-xs text-gray-600 border border-gray-150/70" onclick="event.stopPropagation()">
                                         @if($role === 'QA Admin')
                                             <input type="checkbox" class="w-3.5 h-3.5 rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon cursor-pointer shrink-0" {{ $pendingReco->is_completed ? 'checked' : '' }} onchange="toggleRecommendation({{ $pendingReco->recommendation_item_id ?? $pendingReco->id }}, {{ $c->compliance_record_id ?? $c->id }})" title="QA Admin: Click to toggle" />
                                         @else
                                             <input type="checkbox" class="w-3.5 h-3.5 rounded border-gray-300 text-hau-maroon mt-0.5 cursor-not-allowed opacity-75 shrink-0" {{ $pendingReco->is_completed ? 'checked' : '' }} disabled title="Only QA Admin can directly check off items." />
                                         @endif
                                         <span class="truncate text-[11px] flex-1 {{ $pendingReco->is_completed ? 'line-through text-gray-400' : 'text-gray-700 font-medium' }}" title="{{ $pendingReco->text }}">
                                             {{ $pendingReco->text }}
                                         </span>
                                         @if($totalRecs > 1)
                                             <span class="text-[10px] text-hau-maroon font-bold shrink-0">+{{ $totalRecs - 1 }} more</span>
                                         @endif
                                     </div>
                                 @endif
                             </div>
                         @endif
                     </div>

                     <!-- Smart Content Badges (Action Plan & Evidence Indicators if present) -->
                     @php
                         $hasActionPlan = !empty(trim($c->action_plan ?? '')) && !str_contains(strtolower($c->action_plan), 'no action plan formulated');
                         $hasEvidence = false;
                         $primaryEvidenceLink = null;
                         if ($c->assignments && $c->assignments->count() > 0) {
                             foreach($c->assignments as $ass) {
                                 if ($ass->pending_document_link || $ass->document_link) {
                                     $hasEvidence = true;
                                     $primaryEvidenceLink = $ass->pending_document_link ?? $ass->document_link;
                                     break;
                                 }
                             }
                         }
                         if (!$hasEvidence && !empty($c->document_link)) {
                             $hasEvidence = true;
                             $primaryEvidenceLink = $c->document_link;
                         }
                     @endphp
                     @if($hasActionPlan || $hasEvidence)
                         <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-100">
                             @if($hasActionPlan)
                                 <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="{{ $c->action_plan }}">
                                     <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                     Action Plan Added
                                 </span>
                             @endif
                             @if($hasEvidence)
                                 <a href="{{ $primaryEvidenceLink }}" target="_blank" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition" title="{{ $primaryEvidenceLink }}">
                                     <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                     Evidence Attached 🔗
                                 </a>
                             @endif
                         </div>
                     @endif
                 </div>

                 <!-- Footer Controls -->
                 <div class="bg-gray-50/70 px-4 py-2.5 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                     <div class="flex items-center gap-1.5 text-xs text-gray-550 font-semibold flex-wrap min-w-0">
                         <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                         </svg>
                         @if ($c->due_date)
                              <span class="font-mono whitespace-nowrap">Due: {{ $c->due_date->format('M d, Y') }}</span>
                              @if($c->status !== 'Compliant')
                                  @if($c->due_date->isPast())
                                      <span class="text-rose-600 font-extrabold text-[10px] bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100 animate-pulse whitespace-nowrap shrink-0">Overdue!</span>
                                  @elseif($c->due_date->diffInDays(now()) <= 7)
                                      <span class="text-amber-700 font-extrabold text-[10px] bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 whitespace-nowrap shrink-0">Due Soon</span>
                                  @endif
                              @endif
                          @else
                              <span class="whitespace-nowrap text-gray-400 font-normal">No deadline</span>
                          @endif
                     </div>
                     
                     <div class="flex items-center gap-2 shrink-0 ml-auto">
                         @if ($role === 'QA Admin')
                             <!-- Admin Full CRUD -->
                             <button onclick="event.stopPropagation(); openEditModal(this.closest('[data-id]'))" class="p-1 text-gray-400 hover:text-hau-maroon hover:bg-gray-200/50 rounded transition" title="Edit Task">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                 </svg>
                             </button>
                             <form action="{{ route('compliance.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this compliance record?')" class="inline" onclick="event.stopPropagation();">
                                 @csrf
                                 @method('DELETE')
                                 <button type="submit" class="p-1 text-gray-400 hover:text-rose-600 hover:bg-gray-200/50 rounded transition" title="Delete Task">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                     </svg>
                                 </button>
                             </form>
                         @else
                             <!-- Unit or Department Draft Propose Update Button -->
                             <button onclick="event.stopPropagation(); openProposeModal(this.closest('[data-id]'))" 
                                     {{ $c->approval_state === 'Pending Approval' ? 'disabled' : '' }}
                                     class="inline-flex items-center justify-center whitespace-nowrap shrink-0 px-3 py-1.5 border border-hau-maroon hover:bg-hau-maroon/5 text-hau-maroon font-bold text-xs rounded-lg transition disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-200 disabled:cursor-not-allowed shadow-2xs">
                                 Propose Update
                             </button>
                         @endif
                     </div>
                 </div>
            </div>
        @empty
            <div id="empty-row" class="col-span-full text-center text-gray-400 py-12 text-sm bg-white rounded-xl border border-gray-200">No compliance items logged yet.</div>
        @endforelse
        
        <div id="no-matches-row" class="col-span-full text-center text-gray-400 py-12 text-sm bg-white rounded-xl border border-gray-200 hidden">No compliance items match your filters.</div>
    </div>

    <!-- ===== 1. ENHANCED TABLE VIEW ===== -->
    <div id="compliance-table-view" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-3 py-3 w-8 text-center"></th>
                        <th class="px-4 py-3 text-left">Task Title &amp; Area</th>
                        <th class="px-4 py-3 text-left w-36">Unit / Program</th>
                        <th class="px-4 py-3 text-left w-36">Stage</th>
                        <th class="px-4 py-3 text-left w-36">Checklist</th>
                        <th class="px-4 py-3 text-center w-24">Priority</th>
                        <th class="px-4 py-3 text-center w-28">Status</th>
                        <th class="px-4 py-3 text-left w-32">Due Date</th>
                        <th class="px-4 py-3 text-center w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white" id="compliance-table-body">
                    @forelse($complianceRecords as $c)
                        @php
                            $stage = $c->workflow_stage ?? 'recommendation_created';
                            $stageLabels = [
                                'recommendation_created' => '1: Rec Logged',
                                'action_plan_submitted'  => '2: Action Plan',
                                'admin_reviewing'        => '3: In Review',
                                'compliant'              => '4: Compliant',
                            ];
                            $stageColor = [
                                'recommendation_created' => 'bg-hau-maroon/5 text-hau-maroon border-hau-maroon/20',
                                'action_plan_submitted'  => 'bg-amber-50 text-amber-800 border-amber-200',
                                'admin_reviewing'        => 'bg-blue-50 text-blue-700 border-blue-200',
                                'compliant'              => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            ];
                            $totalRecs = $c->recommendationItems->count();
                            $completedRecs = $c->recommendationItems->where('is_completed', true)->count();
                            $recRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;
                            $hasActionPlan = !empty(trim($c->action_plan ?? '')) && !str_contains(strtolower($c->action_plan), 'no action plan formulated');
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition cursor-pointer table-task-row"
                            onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))"
                            data-table-id="{{ $c->compliance_record_id }}">
                            <td class="px-3 py-3 text-center" onclick="event.stopPropagation(); toggleTableSubrow('{{ $c->compliance_record_id }}')">
                                <button type="button" class="p-1 text-gray-400 hover:text-hau-maroon rounded transition" title="Toggle Checklist & Details">
                                    <svg class="w-3.5 h-3.5 transform transition-transform duration-200" id="subrow-chevron-{{ $c->compliance_record_id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </td>
                            <td class="px-4 py-3 space-y-0.5 max-w-xs">
                                <div class="font-bold text-gray-900 text-xs hover:text-hau-maroon transition line-clamp-1" title="{{ $c->title }}">
                                    {{ $c->title }}
                                </div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($c->area)
                                        <span class="text-[9px] font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200/60 truncate max-w-[200px]">
                                            {{ $c->area }}
                                        </span>
                                    @endif
                                    @if($c->category)
                                        <span class="text-[9px] font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/60">
                                            {{ $c->category }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                    {{ $c->program->program_code ?? ($getSchoolCode($c->school) ?: ($c->responsible_unit ?? 'General')) }}
                                </span>
                                @if($c->accrediting_body)
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 ml-1">
                                        {{ $c->accrediting_body }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $stageColor[$stage] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                    {{ $stageLabels[$stage] ?? '1: Rec Logged' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 w-full max-w-[120px]">
                                    <div class="flex items-center justify-between text-[10px] font-mono">
                                        <span class="font-bold text-hau-maroon">{{ $recRate }}%</span>
                                        <span class="text-gray-400">({{ $completedRecs }}/{{ $totalRecs }})</span>
                                    </div>
                                    <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-hau-maroon h-full rounded-full" style="width: {{ $recRate }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                    @if ($c->priority === 'Critical') bg-rose-50 text-rose-700 border border-rose-200
                                    @elseif ($c->priority === 'High') bg-amber-50 text-amber-800 border border-amber-200
                                    @elseif ($c->priority === 'Low') bg-slate-100 text-slate-600 border border-slate-200
                                    @else bg-blue-50 text-blue-700 border border-blue-150
                                    @endif">
                                    {{ $c->priority ?? 'Medium' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                    @if ($c->status == 'Compliant') bg-emerald-50 text-emerald-700 border border-emerald-100
                                    @elseif ($c->status == 'Non-Compliant') bg-rose-50 text-rose-700 border border-rose-100
                                    @else bg-gray-50 text-gray-600 border border-gray-200
                                    @endif">
                                    {{ $c->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 font-mono text-[11px] whitespace-nowrap">
                                @if($c->due_date)
                                    {{ $c->due_date->format('M d, Y') }}
                                    @if($c->status !== 'Compliant' && $c->due_date->isPast())
                                        <span class="text-rose-600 font-bold block text-[9px]">Overdue</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 font-sans italic">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))" class="p-1 text-gray-400 hover:text-hau-maroon hover:bg-gray-100 rounded transition" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @if ($role === 'QA Admin')
                                        <button onclick="openEditModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))" class="p-1 text-gray-400 hover:text-hau-maroon hover:bg-gray-100 rounded transition" title="Edit Task">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <form action="{{ route('compliance.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this compliance record?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @else
                                        <button onclick="openProposeModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))" {{ $c->approval_state === 'Pending Approval' ? 'disabled' : '' }} class="px-2 py-0.5 border border-hau-maroon hover:bg-hau-maroon/5 text-hau-maroon font-bold text-[10px] rounded transition disabled:opacity-50">
                                            Propose
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <!-- Expandable Sub-Row for Checklist & Action Plan -->
                        <tr id="subrow-{{ $c->compliance_record_id }}" class="bg-gray-50/70 border-b border-gray-100 hidden" data-table-subrow-id="{{ $c->compliance_record_id }}">
                            <td colspan="9" class="px-6 py-4 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Recommendations Checklist -->
                                    <div class="space-y-2 bg-white p-3 rounded-xl border border-gray-200">
                                        <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                                Recommendations ({{ $completedRecs }}/{{ $totalRecs }})
                                            </span>
                                            <span class="text-hau-maroon font-mono">{{ $recRate }}% Done</span>
                                        </div>
                                        @if($c->recommendationItems->isNotEmpty())
                                            <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1 text-xs">
                                                @foreach($c->recommendationItems as $recItem)
                                                    <div class="flex items-start gap-2 p-1.5 rounded-lg bg-gray-50 border border-gray-150/60">
                                                        @if($role === 'QA Admin')
                                                            <input type="checkbox" {{ $recItem->is_completed ? 'checked' : '' }} onchange="toggleRecommendation({{ $recItem->recommendation_item_id ?? $recItem->id }}, {{ $c->compliance_record_id }})" class="w-3.5 h-3.5 rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon mt-0.5 cursor-pointer shrink-0" />
                                                        @else
                                                            <input type="checkbox" {{ $recItem->is_completed ? 'checked' : '' }} disabled class="w-3.5 h-3.5 rounded border-gray-300 text-hau-maroon mt-0.5 opacity-75 cursor-not-allowed shrink-0" />
                                                        @endif
                                                        <span class="text-gray-700 {{ $recItem->is_completed ? 'line-through text-gray-400' : '' }} flex-1 leading-snug">{{ $recItem->text }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-gray-400 text-xs italic">No specific recommendations recorded.</p>
                                        @endif
                                    </div>

                                    <!-- Action Plan & Evidence Summary -->
                                    <div class="space-y-2 bg-white p-3 rounded-xl border border-gray-200 flex flex-col justify-between">
                                        <div class="space-y-1.5">
                                            <span class="text-xs font-bold text-gray-700 block">Action Plan &amp; Notes</span>
                                            <p class="text-xs text-gray-600 leading-relaxed line-clamp-3">{{ $c->action_plan ?: 'No action plan formulated yet.' }}</p>
                                        </div>
                                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2 flex-wrap text-xs">
                                            @if($c->document_link)
                                                <a href="{{ $c->document_link }}" target="_blank" class="inline-flex items-center gap-1 text-hau-maroon hover:underline font-bold font-mono text-[11px] truncate max-w-[200px]">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                                    View Evidence Document
                                                </a>
                                            @else
                                                <span class="text-gray-400 italic text-[11px]">No evidence attached</span>
                                            @endif
                                            <button onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))" class="text-hau-maroon hover:underline font-bold text-[11px] ml-auto">
                                                Open Full Workspace &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-gray-400">No compliance items logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== 1.5 CROSS-TAB / MATRIX GRID VIEW (Option 3) ===== -->
    <div id="compliance-matrix-view" class="space-y-6 hidden">
        @forelse($complianceRecords as $c)
            @php
                $gridData = $c->getMatrixGridData();
                $stage = $c->workflow_stage ?? 'recommendation_created';
                $stageLabels = [
                    'recommendation_created' => '1: Rec Logged',
                    'action_plan_submitted'  => '2: Action Plan',
                    'admin_reviewing'        => '3: In Review',
                    'compliant'              => '4: Compliant',
                ];
                $stageColor = [
                    'recommendation_created' => 'bg-hau-maroon/5 text-hau-maroon border-hau-maroon/20',
                    'action_plan_submitted'  => 'bg-amber-50 text-amber-800 border-amber-200',
                    'admin_reviewing'        => 'bg-blue-50 text-blue-700 border-blue-200',
                    'compliant'              => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                ];
                $totalRecs = $c->recommendationItems->count();
                $completedRecs = $c->recommendationItems->where('is_completed', true)->count();
                $recRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition matrix-task-card"
                 data-matrix-id="{{ $c->compliance_record_id }}">
                <!-- Task Header Summary -->
                <div class="p-4 bg-gray-50/70 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-hau-maroon text-white">
                                Task #{{ $c->compliance_record_id }}
                            </span>
                            @if($c->accrediting_body)
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $c->accrediting_body }}
                                </span>
                            @endif
                            @if($c->area)
                                <span class="text-[10px] font-semibold text-gray-600 bg-white px-2 py-0.5 rounded border border-gray-200">
                                    {{ $c->area }}
                                </span>
                            @endif
                            @if($c->category)
                                <span class="text-[10px] font-bold text-gray-600 bg-white px-2 py-0.5 rounded border border-gray-200">
                                    {{ $c->category }}
                                </span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $stageColor[$stage] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                {{ $stageLabels[$stage] ?? '1: Rec Logged' }}
                            </span>
                        </div>
                        <h4 class="text-sm font-black text-gray-900 hover:text-hau-maroon transition cursor-pointer"
                            onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))">
                            {{ $c->title }}
                        </h4>
                    </div>

                    <!-- Overall Rollup Status & Quick Actions -->
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="text-right space-y-1">
                            <div class="flex items-center justify-end gap-2 text-xs font-mono">
                                <span class="text-gray-500 font-sans text-[11px] font-medium">Matrix Rollup:</span>
                                <span class="font-black {{ $gridData['matrix_rate'] === 100 ? 'text-emerald-700' : 'text-hau-maroon' }}">
                                    {{ $gridData['completed_cells'] }} / {{ $gridData['total_cells'] }} Approved ({{ $gridData['matrix_rate'] }}%)
                                </span>
                            </div>
                            <div class="w-36 bg-gray-200 h-2 rounded-full overflow-hidden ml-auto">
                                <div class="{{ $gridData['matrix_rate'] === 100 ? 'bg-emerald-500' : 'bg-hau-maroon' }} h-full rounded-full transition-all duration-300"
                                     style="width: {{ $gridData['matrix_rate'] }}%"></div>
                            </div>
                        </div>

                        <button type="button"
                                onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))"
                                class="px-3 py-1.5 bg-hau-maroon/10 hover:bg-hau-maroon hover:text-white text-hau-maroon text-xs font-bold rounded-xl transition flex items-center gap-1">
                            <span>Workspace</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Cross-Tab Matrix Grid Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                        <thead>
                            <tr class="bg-gray-50/90 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                                <th class="px-4 py-3 border-r border-gray-200 min-w-[220px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span>Target School / College</span>
                                    </div>
                                </th>
                                @foreach($gridData['units'] as $u)
                                    <th class="px-4 py-3 text-center border-r border-gray-200 min-w-[200px]">
                                        <div class="flex flex-col items-center">
                                            <span class="font-extrabold text-gray-800">{{ $u['name'] }}</span>
                                            <span class="text-[9px] font-mono text-gray-400 font-normal">Assigned Evidence</span>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-center min-w-[160px] bg-gray-100/60 font-black text-gray-800">
                                    Combined Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 bg-white">
                            @foreach($gridData['matrix'] as $schoolName => $row)
                                <tr class="hover:bg-gray-50/70 transition">
                                    <!-- Row Header: School -->
                                    <td class="px-4 py-3.5 border-r border-gray-150 font-bold text-gray-900 bg-gray-50/30">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full {{ $row['is_complete'] ? 'bg-emerald-500' : ($row['completed_count'] > 0 ? 'bg-amber-500' : 'bg-gray-300') }}"></span>
                                            <span class="text-xs">{{ $schoolName }}</span>
                                        </div>
                                    </td>

                                    <!-- Matrix Cells (One per assigned unit) -->
                                    @foreach($gridData['units'] as $u)
                                        @php
                                            $cellKey = $u['id'] !== null ? "unit_{$u['id']}" : 'unit_general';
                                            $cell = $row['cells'][$cellKey] ?? null;
                                        @endphp
                                        <td class="px-4 py-3 text-center border-r border-gray-150 align-middle">
                                            @if($cell)
                                                @php
                                                    $activeLink = $cell->pending_document_link ?: $cell->document_link;
                                                @endphp
                                                <div class="flex flex-col items-center justify-center gap-1.5 py-1">
                                                    @if($cell->isCompliant())
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Approved
                                                        </span>
                                                        @if($cell->document_link)
                                                            <a href="{{ $cell->document_link }}" target="_blank"
                                                               class="inline-flex items-center gap-1 text-hau-maroon hover:underline font-mono text-[10px] font-semibold truncate max-w-[170px]"
                                                               title="{{ $cell->document_link }}">
                                                                <span>Open Evidence</span>
                                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                            </a>
                                                        @endif
                                                    @elseif($cell->isPendingApproval())
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">
                                                            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            In Review
                                                        </span>
                                                        @if($cell->pending_document_link)
                                                            <a href="{{ $cell->pending_document_link }}" target="_blank"
                                                               class="inline-flex items-center gap-1 text-blue-700 hover:underline font-mono text-[10px] font-semibold truncate max-w-[170px]"
                                                               title="{{ $cell->pending_document_link }}">
                                                                <span>Review Doc</span>
                                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                            </a>
                                                        @endif

                                                        @if($role === 'QA Admin')
                                                            <div class="flex items-center gap-1 mt-1">
                                                                <button type="button"
                                                                        onclick="quickApproveAssignment({{ $cell->id }}, {{ $c->compliance_record_id }})"
                                                                        class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[10px] shadow-2xs transition">
                                                                    Approve
                                                                </button>
                                                                <button type="button"
                                                                        onclick="quickRejectAssignment({{ $cell->id }}, {{ $c->compliance_record_id }})"
                                                                        class="px-2 py-0.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-[10px] shadow-2xs transition">
                                                                    Reject
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @elseif($cell->isRejected())
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                                              title="{{ $cell->rejection_reason ?? 'Revision required by QA Admin' }}">
                                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Needs Revision
                                                        </span>
                                                        @if($cell->rejection_reason)
                                                            <span class="text-[9px] text-rose-600 italic line-clamp-1 max-w-[160px]" title="{{ $cell->rejection_reason }}">
                                                                "{{ $cell->rejection_reason }}"
                                                            </span>
                                                        @endif
                                                        <button type="button"
                                                                onclick="openCellSubmitModal({{ $cell->id }}, {{ $c->compliance_record_id }}, '{{ addslashes($schoolName) }}', '{{ addslashes($u['name']) }}', '{{ addslashes($cell->rejection_reason ?? '') }}')"
                                                                class="px-2 py-0.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-[10px] shadow-2xs transition mt-0.5">
                                                            Submit Revision
                                                        </button>
                                                    @else
                                                        {{-- Pending submission --}}
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                            Pending
                                                        </span>
                                                        <button type="button"
                                                                onclick="openCellSubmitModal({{ $cell->id }}, {{ $c->compliance_record_id }}, '{{ addslashes($schoolName) }}', '{{ addslashes($u['name']) }}', '')"
                                                                class="px-2 py-0.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold rounded text-[10px] shadow-2xs transition mt-0.5 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                            <span>Add Link</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-gray-300 italic text-[11px] py-2">—</div>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Row Combined Status -->
                                    <td class="px-4 py-3.5 text-center bg-gray-50/40 align-middle">
                                        <div class="space-y-1.5 flex flex-col items-center">
                                            <span class="font-mono text-xs font-black {{ $row['is_complete'] ? 'text-emerald-700' : ($row['completed_count'] > 0 ? 'text-amber-700' : 'text-gray-600') }}">
                                                {{ $row['completed_count'] }} / {{ $row['total_count'] }} Complete
                                            </span>
                                            <div class="w-24 bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                                <div class="{{ $row['is_complete'] ? 'bg-emerald-500' : 'bg-hau-maroon' }} h-full rounded-full transition-all duration-300"
                                                     style="width: {{ $row['rate'] }}%"></div>
                                            </div>
                                            <span class="inline-flex px-1.5 py-0.25 rounded text-[9px] font-extrabold {{ $row['is_complete'] ? 'bg-emerald-100 text-emerald-800' : ($row['completed_count'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-150 text-gray-600') }}">
                                                {{ $row['is_complete'] ? '100% Done' : ($row['completed_count'] > 0 ? 'In Progress' : 'Pending') }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm text-gray-400 text-sm">
                No compliance tasks logged yet.
            </div>
        @endforelse
    </div>
    <div id="compliance-board-view" class="hidden">
        @php
            $stagesConfig = [
                'recommendation_created' => [
                    'title' => '01 Recommendation Logged',
                    'sub' => 'New findings & recs',
                    'badge' => 'bg-hau-maroon/10 text-hau-maroon border-hau-maroon/20',
                    'header_bg' => 'border-t-4 border-t-hau-maroon bg-white',
                    'id_count' => 'board-count-rec',
                ],
                'action_plan_submitted' => [
                    'title' => '02 Action Plan Formulated',
                    'sub' => 'Plan formulated / in progress',
                    'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
                    'header_bg' => 'border-t-4 border-t-amber-500 bg-white',
                    'id_count' => 'board-count-plan',
                ],
                'admin_reviewing' => [
                    'title' => '03 Under QA Review',
                    'sub' => 'Evidence submitted for audit',
                    'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'header_bg' => 'border-t-4 border-t-blue-500 bg-white',
                    'id_count' => 'board-count-review',
                ],
                'compliant' => [
                    'title' => '04 Compliant / Completed',
                    'sub' => 'Fully verified & closed',
                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'header_bg' => 'border-t-4 border-t-emerald-500 bg-white',
                    'id_count' => 'board-count-compliant',
                ],
            ];
        @endphp

        <!-- Vertical Stack of Stage Sections with Internal Vertical Scrolling -->
        <div class="space-y-4">
            @foreach($stagesConfig as $stKey => $stMeta)
                @php
                    $stageRecords = $complianceRecords->filter(fn($r) => ($r->workflow_stage ?? 'recommendation_created') === $stKey);
                    $slugStage = Str::slug($stKey);
                @endphp
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden" data-board-column="{{ $stKey }}" id="board-stage-card-{{ $slugStage }}">
                    <!-- Stage Header (Click to Collapse/Expand) -->
                    <div class="p-4 {{ $stMeta['header_bg'] }} border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer hover:bg-gray-50/80 transition shadow-2xs"
                         onclick="toggleBoardStage('{{ $slugStage }}')">
                        <div class="flex items-center gap-3">
                            <div class="space-y-0.5 min-w-0">
                                <h4 class="text-sm font-black text-gray-900 tracking-tight uppercase flex items-center gap-2">
                                    <span>{{ $stMeta['title'] }}</span>
                                    <span id="{{ $stMeta['id_count'] }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold {{ $stMeta['badge'] }}">
                                        {{ $stageRecords->count() }} Task(s)
                                    </span>
                                </h4>
                                <p class="text-xs text-gray-400 font-medium">{{ $stMeta['sub'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-[11px] text-gray-400 font-semibold hidden sm:inline">Click to collapse/expand</span>
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200 board-chevron-icon" id="board-chevron-{{ $slugStage }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Scrollable Cards Container (Vertical Scroll per Stage) -->
                    <div class="p-4 overflow-y-auto max-h-[460px] custom-scrollbar board-cards-container" id="board-stage-body-{{ $slugStage }}" data-stage-container="{{ $stKey }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                            @forelse($stageRecords as $c)
                                @php
                                    $totalRecs = $c->recommendationItems->count();
                                    $completedRecs = $c->recommendationItems->where('is_completed', true)->count();
                                    $recRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;
                                @endphp
                                <div class="bg-gray-50/60 rounded-xl p-3.5 border border-gray-200/80 shadow-xs hover:shadow-md hover:border-hau-maroon/40 hover:bg-white transition cursor-pointer space-y-2.5 flex flex-col justify-between board-item-card"
                                     onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))"
                                     data-board-id="{{ $c->compliance_record_id }}"
                                     data-board-stage="{{ $stKey }}">
                                    
                                    <div class="space-y-2">
                                        <!-- Tags Top -->
                                        <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                                {{ $c->program->program_code ?? ($getSchoolCode($c->school) ?: ($c->responsible_unit ?? 'General')) }}
                                            </span>
                                            <div class="flex items-center gap-1">
                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold
                                                    @if ($c->priority === 'Critical') bg-rose-50 text-rose-700 border border-rose-200
                                                    @elseif ($c->priority === 'High') bg-amber-50 text-amber-800 border border-amber-200
                                                    @elseif ($c->priority === 'Low') bg-slate-100 text-slate-600 border border-slate-200
                                                    @else bg-blue-50 text-blue-700 border border-blue-150
                                                    @endif">
                                                    {{ $c->priority ?? 'Medium' }}
                                                </span>
                                                @if($c->accrediting_body)
                                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-800">
                                                        {{ $c->accrediting_body }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Title -->
                                        <h5 class="text-xs font-bold text-gray-900 leading-snug line-clamp-2 hover:text-hau-maroon transition" title="{{ $c->title }}">
                                            {{ $c->title }}
                                        </h5>

                                        <!-- Area Badge -->
                                        @if($c->area)
                                            <div class="text-[9px] text-gray-500 font-semibold bg-white px-1.5 py-0.5 rounded border border-gray-200 truncate">
                                                {{ $c->area }}
                                            </div>
                                        @endif

                                        <!-- Checklist Progress Bar -->
                                        @if($totalRecs > 0)
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-[10px] font-mono">
                                                    <span class="text-gray-500">Recs: {{ $completedRecs }}/{{ $totalRecs }}</span>
                                                    <span class="font-bold text-hau-maroon">{{ $recRate }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                                    <div class="bg-hau-maroon h-full rounded-full" style="width: {{ $recRate }}%"></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Footer: Due Date & Actions -->
                                    <div class="pt-2 border-t border-gray-200/60 flex items-center justify-between gap-1 text-[10px] text-gray-400">
                                        <div class="flex items-center gap-1 font-mono">
                                            <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            @if($c->due_date)
                                                <span class="{{ $c->status !== 'Compliant' && $c->due_date->isPast() ? 'text-rose-600 font-bold' : '' }}">{{ $c->due_date->format('M d, Y') }}</span>
                                            @else
                                                <span>No due date</span>
                                            @endif
                                        </div>
                                        <span class="text-hau-maroon font-bold hover:underline">View &rarr;</span>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full py-8 text-center text-xs text-gray-400 italic board-empty-indicator">
                                    No items in this stage.
                                </div>
                            @endforelse
                            <div class="col-span-full py-8 text-center text-xs text-gray-400 italic board-no-match-indicator hidden">
                                No matching items in this stage.
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ===== 3. GROUPED BY ACCREDITATION AREA VIEW ===== -->
    <div id="compliance-area-view" class="space-y-4 hidden">
        <!-- Accrediting Body Sub-Pills Bar -->
        <div class="bg-white rounded-2xl p-3.5 border border-gray-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 bg-hau-maroon/10 text-hau-maroon rounded-xl shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <span class="text-xs font-black text-gray-800 uppercase tracking-wider block">Accreditation Standards &amp; Taxonomy</span>
                    <span class="text-[10px] text-gray-400">Select an accrediting body to inspect its specific area criteria and survey standards.</span>
                </div>
            </div>

            <!-- Pill Buttons -->
            <div class="flex items-center gap-1.5 flex-wrap" id="area-body-pills">
                <button type="button" onclick="setAreaBodyFilter('all')" data-body-pill="all" class="px-3 py-1 text-xs font-bold rounded-xl bg-hau-maroon text-white shadow-2xs flex items-center gap-1.5 transition">
                    <span>All Bodies</span>
                    <span class="font-mono text-[10px] opacity-80" id="area-pill-count-all">({{ $complianceRecords->count() }})</span>
                </button>
                @foreach($bodies as $b)
                    @php
                        $bodyCount = $complianceRecords->filter(fn($r) => strcasecmp(trim($r->accrediting_body ?? ''), trim($b)) === 0)->count();
                    @endphp
                    @if($bodyCount > 0)
                        <button type="button" onclick="setAreaBodyFilter('{{ strtolower(trim($b)) }}')" data-body-pill="{{ strtolower(trim($b)) }}" class="px-3 py-1 text-xs font-bold rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition">
                            <span>{{ $b }}</span>
                            <span class="font-mono text-[10px] bg-white text-gray-700 px-1.5 py-0.25 rounded-md border border-gray-200" id="area-pill-count-{{ Str::slug($b) }}">({{ $bodyCount }})</span>
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        @php
            $groupedByBodyAndArea = $complianceRecords->groupBy(function($item) {
                $body = trim($item->accrediting_body ?: 'General Standards');
                $area = trim($item->area ?: 'Uncategorized Area');
                return $body . '|||' . $area;
            })->sortKeys();
        @endphp

        @forelse($groupedByBodyAndArea as $groupKey => $areaRecords)
            @php
                $parts = explode('|||', $groupKey);
                $bodyName = $parts[0] ?? 'General Standards';
                $areaName = $parts[1] ?? 'Uncategorized Area';

                $areaTotalRecs = $areaRecords->sum(fn($r) => $r->recommendationItems->count());
                $areaCompletedRecs = $areaRecords->sum(fn($r) => $r->recommendationItems->where('is_completed', true)->count());
                $areaRate = $areaTotalRecs > 0 ? round(($areaCompletedRecs / $areaTotalRecs) * 100) : 0;
                $areaCompliantCount = $areaRecords->where('status', 'Compliant')->count();
                $slugArea = Str::slug($groupKey);
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden area-group-card"
                 data-area-body="{{ strtolower(trim($bodyName)) }}"
                 data-area-group="{{ strtolower(trim($areaName)) }}"
                 id="area-card-{{ $slugArea }}">
                <!-- Accordion Header -->
                <div class="p-4 bg-gray-50/90 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer hover:bg-gray-100/80 transition"
                     onclick="toggleAreaAccordion('{{ $slugArea }}')">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-hau-maroon text-white rounded-xl shadow-xs shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-50 text-amber-800 border border-amber-200 tracking-wider font-mono uppercase">
                                    {{ $bodyName }}
                                </span>
                                <span>{{ $areaName }}</span>
                                <span class="text-xs font-mono font-bold bg-white text-hau-maroon px-2 py-0.5 rounded-full border border-gray-200 shadow-2xs area-item-counter">
                                    {{ $areaRecords->count() }} Task(s)
                                </span>
                            </h3>
                            <p class="text-[11px] text-gray-400 font-medium">Compliance: {{ $areaCompliantCount }}/{{ $areaRecords->count() }} Compliant &bull; {{ $areaRate }}% Checklist completion</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <div class="w-32 hidden sm:block">
                            <div class="flex items-center justify-between text-[10px] font-mono font-bold text-gray-600 mb-1">
                                <span>Progress</span>
                                <span class="text-hau-maroon">{{ $areaRate }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-hau-maroon h-full rounded-full" style="width: {{ $areaRate }}%"></div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200 area-chevron-icon" id="area-chevron-{{ $slugArea }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Accordion Body -->
                <div class="p-4 space-y-3 area-body-content" id="area-body-{{ $slugArea }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($areaRecords as $c)
                            @php
                                $totalRecs = $c->recommendationItems->count();
                                $completedRecs = $c->recommendationItems->where('is_completed', true)->count();
                                $recRate = $totalRecs > 0 ? round(($completedRecs / $totalRecs) * 100) : 0;
                            @endphp
                            <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs hover:shadow-md hover:border-hau-maroon/40 transition cursor-pointer space-y-3 flex flex-col justify-between area-item-card"
                                 onclick="openDetailModal(document.querySelector('#compliance-grid > [data-id=\'{{ $c->compliance_record_id }}\']'))"
                                 data-area-item-id="{{ $c->compliance_record_id }}"
                                 data-area-item-body="{{ strtolower(trim($c->accrediting_body ?? 'general')) }}">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                            {{ $c->program->program_code ?? ($getSchoolCode($c->school) ?: ($c->responsible_unit ?? 'General')) }}
                                        </span>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                            @if ($c->status == 'Compliant') bg-emerald-50 text-emerald-700 border border-emerald-100
                                            @elseif ($c->status == 'Non-Compliant') bg-rose-50 text-rose-700 border border-rose-100
                                            @else bg-gray-50 text-gray-600 border border-gray-200
                                            @endif">
                                            {{ $c->status }}
                                        </span>
                                    </div>
                                    <h5 class="text-xs font-bold text-gray-900 leading-snug line-clamp-2 hover:text-hau-maroon transition" title="{{ $c->title }}">
                                        {{ $c->title }}
                                    </h5>
                                    @if($totalRecs > 0)
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[10px] font-mono">
                                                <span class="text-gray-500">Checklist: {{ $completedRecs }}/{{ $totalRecs }}</span>
                                                <span class="font-bold text-hau-maroon">{{ $recRate }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-hau-maroon h-full rounded-full" style="width: {{ $recRate }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400 font-mono">
                                    <span>{{ $c->due_date ? $c->due_date->format('M d, Y') : 'No deadline' }}</span>
                                    <span class="text-hau-maroon font-bold font-sans">Details &rarr;</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-200 text-sm">
                No compliance items logged.
            </div>
        @endforelse
    </div>

    <!-- ===== PAGINATION BAR ===== -->
    <div id="pagination-bar" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
        <!-- Results counter -->
        <p id="pagination-info" class="text-xs text-gray-500 font-medium"></p>

        <!-- Page controls -->
        <div id="pagination-controls" class="flex items-center gap-1"></div>
    </div>


</div>

<!-- ================= MODAL WINDOWS ================= -->

    <!-- 1. Add Compliance Modal (Accessible to both roles) -->
    <div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full overflow-hidden transform scale-95 transition-all duration-200 flex flex-col" style="max-width: 960px; max-height: calc(100vh - 40px);">
            <!-- Header with dark maroon gradient -->
            <div class="modal-dark-header flex items-center justify-between border-b border-white/10 shrink-0" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Log Compliance Item</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Register a new compliance task, recommendation, or action item</p>
                    </div>
                </div>
                <button onclick="closeModal('add-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" aria-label="Close modal">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('compliance.store') }}" method="POST" class="flex flex-col min-h-0 flex-1">
                @csrf
                <div class="p-5 space-y-3.5 overflow-y-auto flex-1 min-h-0">
                    <!-- Section I: Context & Categorization -->
                    <div class="border-b border-gray-150 pb-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">I. Context & Categorization</h4>
                    </div>

                    <!-- Row 1: School & Accrediting Body -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">School(s) / College(s)</label>
                            <div id="add-schools-list" class="space-y-1.5">
                                <div class="flex items-center gap-1.5 school-dropdown-row">
                                    <select name="schools[]" id="add-school" class="add-school-select block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                        <option value="" disabled {{ !(Auth::user()->usertype === 'Dean' && Auth::user()->college) ? 'selected' : '' }}>Select School / College</option>
                                        @php
                                            $schoolsList = [
                                                "School of Business and Accountancy",
                                                "School of Engineering and Architecture",
                                                "School of Arts and Sciences",
                                                "School of Education",
                                                "School of Hospitality and Tourism Management",
                                                "School of Nursing and Allied Medical Sciences",
                                                "School of Computing",
                                                "College of Criminal Justice Education and Forensic Sciences",
                                                "Basic Education"
                                            ];
                                        @endphp
                                        @foreach($schoolsList as $sch)
                                            <option value="{{ $sch }}" {{ (Auth::user()->usertype === 'Dean' && Auth::user()->college && Auth::user()->college->name === $sch) ? 'selected' : '' }}>{{ $sch }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove school">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addSchoolDropdownRow('add-schools-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another school / college
                            </button>
                        </div>
                        <div>
                            <label for="add-accrediting_body" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Accrediting Body</label>
                            <div class="flex items-center gap-1.5">
                                <select name="accrediting_body" id="add-accrediting_body" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                    <option value="">Select Accrediting Body</option>
                                    @foreach($dbAccreditingBodies as $ab)
                                        <option value="{{ $ab->code }}">{{ $ab->code }} &mdash; {{ $ab->name }}</option>
                                    @endforeach
                                </select>
                                <span class="w-5 h-5 shrink-0 inline-block"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Programs & Units Repeaters -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-150">
                        <!-- Academic Programs Repeater -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Academic Program(s) (Optional)</label>
                            <div id="add-programs-list" class="space-y-1.5">
                                <div class="flex items-center gap-1.5 program-dropdown-row">
                                    <select name="program_ids[]" id="add-program_id" class="add-program-select block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                        <option value="">Select a Program</option>
                                        @foreach ($programs as $p)
                                            <option value="{{ $p->id }}" data-college="{{ $p->college->name ?? '' }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove program">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addProgramDropdownRow('add-programs-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another program
                            </button>
                        </div>

                        <!-- Departments / Units Repeater -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Unit(s) or Department(s)</label>
                            <div id="add-units-list" class="space-y-1.5">
                                <div class="flex items-center gap-1.5 unit-dropdown-row">
                                    <select name="responsible_unit_ids[]" id="add-resp" class="add-unit-select block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                        <option value="">Select Responsible Department/Unit</option>
                                        <option value="all">All Departments &amp; Units (University-Wide)</option>
                                        @php
                                            $schoolsWithDepts = $dbResponsibleUnits->whereNull('parent_unit_id')->filter(fn($u) => $u->children->count() > 0);
                                            $adminOffices = $dbResponsibleUnits->whereNull('parent_unit_id')->filter(fn($u) => $u->children->count() === 0);
                                        @endphp
                                        @foreach($schoolsWithDepts->sortBy('name') as $school)
                                            <optgroup label="{{ $school->name }}">
                                                <option value="{{ $school->responsible_unit_id }}"
                                                    {{ (Auth::user()->responsible_unit_id === $school->responsible_unit_id) ? 'selected' : '' }}>
                                                    {{ $school->name }} (Whole School / College)
                                                </option>
                                                @foreach($school->children->sortBy('name') as $dept)
                                                    <option value="{{ $dept->responsible_unit_id }}"
                                                        {{ (Auth::user()->responsible_unit_id === $dept->responsible_unit_id) ? 'selected' : '' }}>
                                                        {{ $dept->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                        @if($adminOffices->count() > 0)
                                            <optgroup label="Administrative Offices &amp; Units">
                                                @foreach($adminOffices->sortBy('name') as $office)
                                                    <option value="{{ $office->responsible_unit_id }}"
                                                        {{ (Auth::user()->responsible_unit_id === $office->responsible_unit_id) ? 'selected' : '' }}>
                                                        {{ $office->name }}{{ $office->code ? ' ('.$office->code.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                    <button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove unit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addUnitDropdownRow('add-units-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another department / unit
                            </button>
                        </div>
                    </div>

                    <!-- Row 3: Category & Areas -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-150">
                        <div>
                            <label for="add-category" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Category</label>
                            <div class="flex items-center gap-1.5">
                                <select name="categories[]" id="add-category" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                    <option value="" disabled selected>Select Category</option>
                                    <option value="Resurvey Feedback">Resurvey Feedback</option>
                                    <option value="FUA (Follow-Up Action)">FUA (Follow-Up Action)</option>
                                    <option value="Self-Survey Findings">Self-Survey Findings</option>
                                    <option value="Accreditor Recommendation">Accreditor Recommendation</option>
                                    <option value="Internal Quality Audit">Internal Quality Audit</option>
                                    <option value="Curriculum & Instruction">Curriculum &amp; Instruction</option>
                                    <option value="Governance & Leadership">Governance &amp; Leadership</option>
                                    <option value="Faculty & Staff Development">Faculty &amp; Staff Development</option>
                                    <option value="Student Services">Student Services</option>
                                    <option value="General Compliance">General Compliance</option>
                                </select>
                                <span class="w-5 h-5 shrink-0 inline-block"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5 flex justify-between items-center">
                                <span>Areas</span>
                                <span class="text-[9px] text-amber-600 font-bold normal-case block" id="add-area-notice">⚠️ Select Body first</span>
                            </label>
                            <div id="add-areas-list" class="space-y-1.5">
                                <div class="flex items-center gap-1.5">
                                    <select name="areas[]" required disabled class="area-select block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                        <option value="" disabled selected>Select Accrediting Body first</option>
                                    </select>
                                    <button type="button" onclick="removeAreaRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addAreaRow('add-areas-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another area
                            </button>
                        </div>
                    </div>

                    <!-- Section II: Recommendation & Tasks -->
                    <div class="border-b border-gray-150 pb-1 pt-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">II. Recommendation & Tasks</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label for="add-title" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Task Title</label>
                            <input type="text" name="title" id="add-title" required placeholder="e.g. Submit Alumni Board Minutes" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="add-priority" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Priority</label>
                            <select name="priority" id="add-priority" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Critical">Critical</option>
                                <option value="High">High</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="add-desc" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Task Description</label>
                        <textarea name="description" id="add-desc" rows="2" placeholder="Describe compliance details..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5 flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Recommendation Checklist Items
                        </label>
                        <div id="add-recommendations-list" class="space-y-1.5">
                            <div class="flex items-center gap-2 reco-row-animate">
                                <input type="text" name="recommendations[]" required placeholder="Enter a recommendation..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                                <button type="button" onclick="removeRecoRow(this)" class="p-1 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addRecoRow('add-recommendations-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add another recommendation
                        </button>
                    </div>

                    <!-- Section III: Resolution & Tracking -->
                    <div class="border-b border-gray-150 pb-1 pt-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">III. Resolution & Tracking</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="add-visit_date" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Visit Date</label>
                            <input type="date" name="visit_date" id="add-visit_date" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="add-due" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Due Date</label>
                            <input type="date" name="due_date" id="add-due" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="add-status" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Status</label>
                            <select name="status" id="add-status" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Pending" selected>Pending Audit</option>
                                <option value="Compliant">Compliant</option>
                                <option value="Non-Compliant">Non-Compliant</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="add-contact-person" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Person</label>
                            <input type="text" name="contact_person" id="add-contact-person" value="{{ in_array(Auth::user()->usertype, ['Dean', 'Head of Unit']) ? Auth::user()->name : '' }}" placeholder="e.g. Juan dela Cruz" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="add-contact-email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Email</label>
                            <input type="email" name="contact_email" id="add-contact-email" value="{{ in_array(Auth::user()->usertype, ['Dean', 'Head of Unit']) ? (Auth::user()->email ?? (Auth::user()->username . '@hau.edu.ph')) : '' }}" placeholder="e.g. jdelacruz@hau.edu.ph" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="add-link" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Document Link (Evidence URL)</label>
                            <input type="url" name="document_link" id="add-link" placeholder="e.g. https://drive.google.com/..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>

                    <div>
                        <label for="add-action_plan" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Action Plan</label>
                        <textarea name="action_plan" id="add-action_plan" rows="2" placeholder="Formulate response or action item..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3 flex justify-end gap-3 border-t border-gray-200 shrink-0">
                    <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Save Task</button>
                </div>
            </form>
        </div>
    </div>

@if ($role === 'QA Admin')

    <!-- 2. Edit Compliance Modal (Admin only) -->
    <div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full overflow-hidden transform scale-95 transition-all flex flex-col" style="max-width: 960px; max-height: calc(100vh - 40px);">
            <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Edit Compliance Item</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Update compliance task details, assignments, and requirements</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('edit-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit-form" action="" method="POST" class="flex flex-col min-h-0 flex-1">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3.5 overflow-y-auto flex-1 min-h-0">
                    <!-- Section I: Context & Categorization -->
                    <div class="border-b border-gray-150 pb-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">I. Context & Categorization</h4>
                    </div>

                    <!-- Row 1: School & Accrediting Body -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">School(s) / College(s)</label>
                            <div id="edit-schools-list" class="space-y-1.5">
                                <!-- Dynamically populated via JS -->
                            </div>
                            <button type="button" onclick="addSchoolDropdownRow('edit-schools-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another school / college
                            </button>
                        </div>
                        <div>
                            <label for="edit-accrediting_body" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Accrediting Body</label>
                            <div class="flex items-center gap-1.5">
                                <select name="accrediting_body" id="edit-accrediting_body" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                    <option value="">Select Accrediting Body</option>
                                    @foreach($dbAccreditingBodies as $ab)
                                        <option value="{{ $ab->code }}">{{ $ab->code }} &mdash; {{ $ab->name }}</option>
                                    @endforeach
                                </select>
                                <span class="w-5 h-5 shrink-0 inline-block"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Programs & Units Repeaters -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-150">
                        <!-- Academic Programs Repeater -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Academic Program(s) (Optional)</label>
                            <div id="edit-programs-list" class="space-y-1.5">
                                <!-- Dynamically populated via JS -->
                            </div>
                            <button type="button" onclick="addProgramDropdownRow('edit-programs-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another program
                            </button>
                        </div>

                        <!-- Departments / Units Repeater -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Unit(s) or Department(s)</label>
                            <div id="edit-units-list" class="space-y-1.5">
                                <!-- Dynamically populated via JS -->
                            </div>
                            <button type="button" onclick="addUnitDropdownRow('edit-units-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another department / unit
                            </button>
                        </div>
                    </div>

                    <!-- Row 3: Category & Areas -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-150">
                        <div>
                            <label for="edit-category" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Category</label>
                            <div class="flex items-center gap-1.5">
                                <select name="categories[]" id="edit-category" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                    <option value="" disabled>Select Category</option>
                                    <option value="Resurvey Feedback">Resurvey Feedback</option>
                                    <option value="FUA (Follow-Up Action)">FUA (Follow-Up Action)</option>
                                    <option value="Self-Survey Findings">Self-Survey Findings</option>
                                    <option value="Accreditor Recommendation">Accreditor Recommendation</option>
                                    <option value="Internal Quality Audit">Internal Quality Audit</option>
                                    <option value="Curriculum & Instruction">Curriculum &amp; Instruction</option>
                                    <option value="Governance & Leadership">Governance &amp; Leadership</option>
                                    <option value="Faculty & Staff Development">Faculty &amp; Staff Development</option>
                                    <option value="Student Services">Student Services</option>
                                    <option value="General Compliance">General Compliance</option>
                                </select>
                                <span class="w-5 h-5 shrink-0 inline-block"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5 flex justify-between items-center">
                                <span>Areas</span>
                                <span class="text-[9px] text-amber-600 font-bold normal-case block" id="edit-area-notice">⚠️ Select Body first</span>
                            </label>
                            <div id="edit-areas-list" class="space-y-1.5">
                                <!-- Populated dynamically by openEditModal -->
                            </div>
                            <button type="button" onclick="addAreaRow('edit-areas-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add another area
                            </button>
                        </div>
                    </div>

                    <!-- Section II: Recommendation & Tasks -->
                    <div class="border-b border-gray-150 pb-1 pt-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">II. Recommendation & Tasks</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label for="edit-title" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Task Title</label>
                            <input type="text" name="title" id="edit-title" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-priority" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Priority</label>
                            <select name="priority" id="edit-priority" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Critical">Critical</option>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="edit-desc" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Task Description</label>
                        <textarea name="description" id="edit-desc" rows="2" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>

                    <!-- Dynamic Recommendations Checklist Input for Edit -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5 flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Recommendation Checklist Items
                        </label>
                        <div id="edit-recommendations-list" class="space-y-1.5">
                            <!-- Populated by JS -->
                        </div>
                        <button type="button" onclick="addRecoRow('edit-recommendations-list')" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-hau-maroon hover:text-hau-maroon-light transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add another recommendation
                        </button>
                    </div>

                    <!-- Section III: Resolution & Tracking -->
                    <div class="border-b border-gray-150 pb-1 pt-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">III. Resolution & Tracking</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="edit-visit_date" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Visit Date</label>
                            <input type="date" name="visit_date" id="edit-visit_date" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-due" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Due Date</label>
                            <input type="date" name="due_date" id="edit-due" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-status" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Status</label>
                            <select name="status" id="edit-status" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Complied">Complied</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending Review">Pending Review</option>
                                <option value="Not Complied">Not Complied</option>
                            </select>
                        </div>
                    </div>

                    <!-- Section III: Contact & Submission Details -->
                    <div class="border-b border-gray-150 pb-1 pt-1">
                        <h4 class="text-[10px] font-black text-hau-maroon uppercase tracking-wider">III. Contact & Submission Details</h4>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="edit-contact-person" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Person</label>
                            <input type="text" name="contact_person" id="edit-contact-person" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-contact-email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Email</label>
                            <input type="email" name="contact_email" id="edit-contact-email" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>

                    <div>
                        <label for="edit-link" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Document Link (Evidence URL)</label>
                        <input type="url" name="document_link" id="edit-link" placeholder="https://drive.google.com/..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>

                    <div>
                        <label for="edit-action_plan" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Action Plan</label>
                        <textarea name="action_plan" id="edit-action_plan" rows="2" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200 shrink-0">
                    <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">Update Task</button>
                </div>
            </form>
        </div>
    </div>

@else
    <!-- 3. Propose Update Modal (Unit or Department only, with Action Plan edit support) -->
    <div id="propose-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Propose New Compliance Item</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Submit a new recommendation or task for administrative review</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('propose-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="propose-form" action="" method="POST">
                @csrf
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="bg-hau-maroon/5 border border-hau-maroon/10 p-4 rounded-xl space-y-2 text-xs text-gray-700">
                        <div class="flex justify-between items-center font-bold">
                            <span id="propose-task-program" class="text-hau-maroon"></span>
                            <span id="propose-task-body" class="bg-hau-gold/20 px-2 py-0.5 rounded text-hau-maroon-dark"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold uppercase text-[9px]">Task:</span>
                            <h5 id="propose-task-title" class="font-bold text-gray-800"></h5>
                        </div>
                        <div id="propose-reco-container">
                            <span class="text-gray-400 font-bold uppercase text-[9px]">Recommendation:</span>
                            <p id="propose-task-reco" class="italic text-gray-600 font-medium"></p>
                        </div>
                    </div>
                    
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-800 space-y-1 mb-4">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Status Transition Notice
                        </p>
                        <p class="font-medium text-emerald-700 leading-relaxed">Submitting your Action Plan and Evidence Link will log them for QA Admin approval. Upon Admin review and approval, the task status will automatically update to <strong>Compliant</strong>.</p>
                    </div>

                    <div id="propose-target-container" class="mb-3" style="display: none;">
                        <label for="propose-target-select" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Target School / Program</label>
                        <select id="propose-target-select" onchange="onProposeTargetChange(this)" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        </select>
                    </div>

                    <div>
                        <label for="propose-link" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Document Link (Evidence URL)</label>
                        <input type="url" name="pending_document_link" id="propose-link" required placeholder="e.g., https://drive.google.com/..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        <span class="text-[10px] text-gray-400 mt-1 block">A valid document URL must be provided to submit changes for approval.</span>
                    </div>

                    <hr class="border-gray-200" />

                    <div>
                        <label for="propose-action_plan" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Action Plan</label>
                        <textarea name="action_plan" id="propose-action_plan" required rows="3" placeholder="Formulate response or action items..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>

                    <div>
                        <label for="propose-resp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Unit or Department</label>
                        <select name="responsible_unit_id" id="propose-resp" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="">Select Responsible Department/Unit</option>
                            @foreach($dbResponsibleUnits as $ru)
                                <option value="{{ $ru->responsible_unit_id }}">{{ $ru->name }} @if($ru->code)({{ $ru->code }})@endif</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="propose-contact-person" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Contact Person</label>
                            <input type="text" name="contact_person" id="propose-contact-person" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="propose-contact-email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Contact Email</label>
                            <input type="email" name="contact_email" id="propose-contact-email" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('propose-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">Propose Changes</button>
                </div>
            </form>
        </div>
    </div>
@endif

    <!-- 4.5 Single Cell Evidence Submission Modal -->
    <div id="cell-submit-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Submit Evidence</h3>
                        <p id="cell-submit-subtitle" class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Upload documentation and proof of compliance</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('cell-submit-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="cell-submit-form" onsubmit="submitCellEvidence(event)" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="cell-submit-compliance-id" name="compliance_id" value="" />
                <input type="hidden" id="cell-submit-assignment-id" name="assignment_id" value="" />

                <!-- Rejection Alert if revising -->
                <div id="cell-submit-rejection-alert" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-700 hidden">
                    <span class="font-bold block text-rose-800 uppercase tracking-wider text-[10px] mb-0.5">QA Admin Feedback / Reason</span>
                    <span id="cell-submit-rejection-text"></span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        SharePoint / Evidence Document Link <span class="text-rose-500">*</span>
                    </label>
                    <input type="url" id="cell-submit-link" name="pending_document_link" required
                           placeholder="https://haueduph.sharepoint.com/..."
                           class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs font-mono bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Action Plan / Summary of Evidence <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="cell-submit-action-plan" name="action_plan" rows="3" required
                              placeholder="Describe the attached evidence documentation and actions implemented..."
                              class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition leading-relaxed"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-150 flex items-center justify-between">
                    <button type="button" onclick="closeModal('cell-submit-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="cell-submit-btn" class="px-5 py-2 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>Submit for QA Review</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Compliance Item Details Modal (Accessible to both) -->
    <div id="detail-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs overflow-y-auto hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full overflow-hidden transform scale-95 transition-all flex flex-col my-auto" style="max-width: 780px; max-height: calc(100vh - 40px);">
            <!-- Modal Header (Pinned at Top) -->
            <div class="relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem;">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide">Compliance Task Workspace &amp; Matrix</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5">Detailed task view, recommendations, assignments, and evidence</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('detail-modal')" class="p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Scrollable Content (Bounded with min-h-0) -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1 min-h-0">
                
                <!-- Title & Status Header Bar -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 bg-gray-50/80 p-3.5 rounded-xl border border-gray-200">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span id="detail-program" class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold font-mono bg-hau-maroon/10 text-hau-maroon"></span>
                            <span id="detail-body" class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900"></span>
                            <span id="detail-priority" class="inline-flex px-2 py-0.5 rounded text-[10px] font-black border"></span>
                        </div>
                        <h4 id="detail-title" class="text-base font-black text-gray-900 leading-snug"></h4>
                    </div>
                    <div class="shrink-0 flex items-center sm:items-end">
                        <span id="detail-status" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-black border shadow-2xs"></span>
                    </div>
                </div>

                <!-- Rejection Alerts (If Rejected) -->
                <div id="detail-rejected-alert" class="bg-rose-50 border border-rose-200 rounded-xl p-3.5 hidden">
                    <h5 class="text-xs font-bold text-rose-800 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Rejection Reason from QA Admin</span>
                    </h5>
                    <p id="detail-rejection-reason" class="text-xs text-rose-700 font-medium italic"></p>
                </div>

                <!-- Workflow Timeline Tracker -->
                <div id="detail-workflow-tracker" class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
                    <h5 class="text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Recommendation Workflow Stage</h5>
                    <div id="detail-workflow-steps" class="space-y-0">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Recommendation Checklist -->
                <div>
                    <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider block flex items-center gap-1 mb-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Recommendations Checklist</span>
                    </span>
                    <div id="detail-recommendation" class="text-xs text-gray-700 leading-relaxed bg-gray-50 p-3 rounded-xl border border-gray-200"></div>
                </div>

                <!-- Action Plan & Description in Compact Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Action Plan / Action Taken -->
                    <div>
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Action Plan / Action Taken</span>
                        <p id="detail-action-plan" class="text-xs text-gray-700 bg-gray-50 border border-gray-200 p-2.5 rounded-xl leading-relaxed font-medium whitespace-pre-line min-h-[60px]"></p>
                    </div>

                    <!-- Task Description -->
                    <div>
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Task Description</span>
                        <p id="detail-description" class="text-xs text-gray-600 bg-white border border-gray-200 p-2.5 rounded-xl leading-relaxed whitespace-pre-line min-h-[60px]"></p>
                    </div>
                </div>

                <!-- Submissions & SharePoint Links Breakdown Table (Full Width) -->
                <div class="pt-2 border-t border-gray-200 space-y-1.5">
                    <span class="text-[10px] font-black text-hau-maroon uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Assigned Target Submissions &amp; SharePoint Evidence Links</span>
                    </span>
                    <div id="detail-assignments-list">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Metadata Card -->
                <div class="bg-gray-50/70 border border-gray-200 rounded-xl p-3.5 space-y-2.5">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block border-b border-gray-200 pb-1.5">
                        Assignment &amp; Department Metadata
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">School / College</span>
                            <span id="detail-school" class="font-semibold text-gray-900 block mt-0.5"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Unit or Department</span>
                            <span id="detail-resp" class="font-semibold text-gray-900 block mt-0.5"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Contact Person</span>
                            <span id="detail-contact-person" class="font-semibold text-gray-900 block mt-0.5"></span>
                            <span id="detail-contact-email" class="block text-gray-500 font-mono text-[10px] mt-0.5"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Category / Area</span>
                            <span id="detail-cat-area" class="font-semibold text-gray-900 block mt-0.5"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Deadline / Due Date</span>
                            <span id="detail-due" class="font-semibold text-gray-900 block mt-0.5"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Evidence Document Link</span>
                            <div id="detail-link-container" class="mt-0.5">
                                <!-- Clickable link injected here -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200 shrink-0">
                <button type="button" onclick="closeModal('detail-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Close Details</button>
                <div id="detail-action-buttons">
                    <!-- Action buttons dynamically loaded here based on role -->
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Categories & Labs Modal -->
    <div id="manage-labs-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full overflow-hidden transform scale-95 transition-all flex flex-col" style="max-width: 540px; max-height: calc(100vh - 80px);">
            <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Manage Laboratories / Facilities</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Add, edit, or configure facility and lab options</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('manage-labs-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 bg-gray-50 px-5 shrink-0">
                <button type="button" onclick="switchManageLabsTab('depts')" id="manage-labs-tab-depts" class="px-3 py-3 text-xs font-bold text-hau-maroon border-b-2 border-hau-maroon focus:outline-none transition">
                    Departments &amp; Units
                </button>
            </div>

            <!-- Panel Container -->
            <div class="flex-1 flex flex-col overflow-hidden min-h-0 bg-white">

                <!-- Panel: Departments -->
                <div id="manage-labs-panel-depts" class="p-5 flex flex-col min-h-0 overflow-hidden flex-1 hidden">

                    <!-- Add Department Form -->
                    <div class="border-b border-gray-150 pb-3 mb-4 shrink-0">
                        <h4 class="text-[11px] font-black text-hau-maroon uppercase tracking-wider mb-3">Add Department</h4>
                        <form id="create-dept-form" onsubmit="saveNewDepartment(event)" class="space-y-2">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Parent School</label>
                                    <select name="parent_unit_id" id="new-dept-school" required class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                        <option value="">Select School</option>
                                        @foreach($dbResponsibleUnits->whereNull('parent_unit_id')->where('college_id', '!=', null)->sortBy('name') as $school)
                                            <option value="{{ $school->responsible_unit_id }}">{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Short Code <span class="text-gray-400 normal-case">(optional)</span></label>
                                    <input type="text" name="code" id="new-dept-code" placeholder="e.g. SOC-CS" maxlength="20" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1">Department Name</label>
                                <input type="text" name="name" id="new-dept-name" required placeholder="e.g. Department of Computer Science" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                            </div>
                            <button type="submit" id="create-dept-btn" class="w-full py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                                + Add Department
                            </button>
                        </form>
                    </div>

                    <!-- Departments List -->
                    <div class="flex justify-between items-center mb-2 shrink-0">
                        <h4 class="text-[11px] font-black text-hau-maroon uppercase tracking-wider">Existing Departments</h4>
                        <span id="manage-depts-count" class="text-[10px] font-bold bg-hau-maroon/5 text-hau-maroon px-2 py-0.5 rounded font-mono">0 Total</span>
                    </div>
                    <div class="mb-2 shrink-0">
                        <input type="text" id="manage-depts-search" oninput="filterDeptsList()" placeholder="Filter departments..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div class="flex-1 overflow-y-auto min-h-0 pr-1" id="manage-depts-list-container">
                        <!-- Populated by JS -->
                    </div>
                </div>

            </div>

            <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200 shrink-0">
                <button type="button" onclick="closeModal('manage-labs-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Done</button>
            </div>
        </div>
    </div>


<!-- ================= JAVASCRIPT ================= -->
<script>
    // ===== PAGINATION & HYBRID VIEW STATE =====
    const ITEMS_PER_PAGE = 12;
    let currentPage = 1;
    let filteredCards = []; // holds currently visible (filtered) card elements
    let currentComplianceView = localStorage.getItem('compliance_view_mode') || 'table';
    if (currentComplianceView === 'list') currentComplianceView = 'table';

    /**
     * Core pagination renderer.
     * Re-slices and displays cards & table rows for the current page.
     */
    function renderPagination() {
        const totalItems = filteredCards.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));

        // Clamp page
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = Math.min(start + ITEMS_PER_PAGE, totalItems);

        // Show/hide cards and table rows based on current page slice
        filteredCards.forEach((card, idx) => {
            const isVisiblePage = (idx >= start && idx < end);
            card.style.display = isVisiblePage ? '' : 'none';
            const id = card.getAttribute('data-id');
            const tableRow = document.querySelector(`tr[data-table-id="${id}"]`);
            if (tableRow) {
                tableRow.style.display = isVisiblePage ? '' : 'none';
            }
            // If main table row is hidden by pagination, ensure its subrow is hidden as well
            if (!isVisiblePage) {
                const subRow = document.getElementById('subrow-' + id);
                if (subRow) subRow.classList.add('hidden');
                const chevron = document.getElementById('subrow-chevron-' + id);
                if (chevron) chevron.classList.remove('rotate-90');
            }
        });

        // Update info text
        const infoEl = document.getElementById('pagination-info');
        if (infoEl) {
            if (totalItems === 0) {
                infoEl.textContent = '';
            } else {
                infoEl.textContent = `Showing ${start + 1}–${end} of ${totalItems} compliance task${totalItems !== 1 ? 's' : ''}`;
            }
        }

        // Render page number buttons
        const controlsEl = document.getElementById('pagination-controls');
        if (!controlsEl) return;

        const btnBase = 'inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold transition focus:outline-none';
        const btnActive = btnBase + ' bg-hau-maroon text-white shadow-sm';
        const btnInactive = btnBase + ' text-gray-600 hover:bg-gray-100 border border-gray-200 bg-white';
        const btnDisabled = btnBase + ' text-gray-300 cursor-not-allowed border border-gray-100 bg-gray-50';

        // Determine which page numbers to show (max 5 visible)
        let pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > 3) pages.push('...');
            const lo = Math.max(2, currentPage - 1);
            const hi = Math.min(totalPages - 1, currentPage + 1);
            for (let i = lo; i <= hi; i++) pages.push(i);
            if (currentPage < totalPages - 2) pages.push('...');
            pages.push(totalPages);
        }

        let html = '';

        // Prev button
        if (currentPage <= 1) {
            html += `<button disabled class="${btnDisabled} mr-1" aria-label="Previous page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>`;
        } else {
            html += `<button onclick="goToPage(${currentPage - 1})" class="${btnInactive} mr-1" aria-label="Previous page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>`;
        }

        // Page number buttons
        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="inline-flex items-center justify-center w-8 h-8 text-xs text-gray-400 select-none">…</span>`;
            } else {
                html += `<button onclick="goToPage(${p})" class="${p === currentPage ? btnActive : btnInactive}" aria-label="Page ${p}">${p}</button>`;
            }
        });

        // Next button
        if (currentPage >= totalPages) {
            html += `<button disabled class="${btnDisabled} ml-1" aria-label="Next page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>`;
        } else {
            html += `<button onclick="goToPage(${currentPage + 1})" class="${btnInactive} ml-1" aria-label="Next page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>`;
        }

        controlsEl.innerHTML = html;

        // Show/hide the whole pagination bar based on current view and items
        const bar = document.getElementById('pagination-bar');
        if (bar) {
            const isPagedView = (currentComplianceView === 'table' || currentComplianceView === 'grid');
            bar.style.display = (isPagedView && totalItems > 0) ? '' : 'none';
        }
    }

    function goToPage(page) {
        currentPage = page;
        renderPagination();
        const scrollTarget = document.getElementById('compliance-table-view') || document.getElementById('compliance-grid');
        if (scrollTarget) scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Table Sub-Row Accordion Toggle ──
    function toggleTableSubrow(id) {
        const subRow = document.getElementById('subrow-' + id);
        const chevron = document.getElementById('subrow-chevron-' + id);
        if (!subRow) return;

        const isHidden = subRow.classList.contains('hidden');
        if (isHidden) {
            subRow.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-90', 'text-hau-maroon');
        } else {
            subRow.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-90', 'text-hau-maroon');
        }
    }

    // ── Area Accordion Toggle ──
    function toggleAreaAccordion(slug) {
        const body = document.getElementById('area-body-' + slug);
        const chevron = document.getElementById('area-chevron-' + slug);
        if (!body) return;

        const isHidden = body.classList.contains('hidden');
        if (isHidden) {
            body.classList.remove('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        } else {
            body.classList.add('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    // ── Board Stage Accordion Toggle ──
    function toggleBoardStage(slug) {
        const body = document.getElementById('board-stage-body-' + slug);
        const chevron = document.getElementById('board-chevron-' + slug);
        if (!body) return;

        const isHidden = body.classList.contains('hidden');
        if (isHidden) {
            body.classList.remove('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        } else {
            body.classList.add('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    // ── Area View Accrediting Body Sub-Pill Filter ──
    let currentAreaBodyFilter = 'all';

    function setAreaBodyFilter(bodyKey) {
        currentAreaBodyFilter = bodyKey ? bodyKey.toLowerCase().trim() : 'all';

        const activeClass = 'px-3 py-1 text-xs font-bold rounded-xl bg-hau-maroon text-white shadow-2xs flex items-center gap-1.5 transition';
        const inactiveClass = 'px-3 py-1 text-xs font-bold rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition';

        document.querySelectorAll('#area-body-pills [data-body-pill]').forEach(btn => {
            const pillKey = btn.getAttribute('data-body-pill');
            if (pillKey === currentAreaBodyFilter) {
                btn.className = activeClass;
            } else {
                btn.className = inactiveClass;
            }
        });

        applyFilters(false);
    }

    /**
     * View Mode Switcher: 'table' | 'matrix' | 'board' | 'area' | 'grid'
     */
    function setComplianceView(mode) {
        currentComplianceView = mode;
        try { localStorage.setItem('compliance_view_mode', mode); } catch (e) {}

        const gridEl = document.getElementById('compliance-grid');
        const tableEl = document.getElementById('compliance-table-view');
        const matrixEl = document.getElementById('compliance-matrix-view');
        const boardEl = document.getElementById('compliance-board-view');
        const areaEl = document.getElementById('compliance-area-view');
        const paginationBar = document.getElementById('pagination-bar');

        const btnTable = document.getElementById('view-btn-table');
        const btnMatrix = document.getElementById('view-btn-matrix');
        const btnBoard = document.getElementById('view-btn-board');
        const btnArea = document.getElementById('view-btn-area');
        const btnGrid = document.getElementById('view-btn-grid');

        const activeClass = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-hau-maroon text-white shadow-xs flex items-center gap-1.5 transition';
        const inactiveClass = 'px-2.5 py-1 text-xs font-bold rounded-lg text-gray-600 hover:bg-gray-200 flex items-center gap-1.5 transition';

        // Reset all buttons
        if (btnTable) btnTable.className = inactiveClass;
        if (btnMatrix) btnMatrix.className = inactiveClass;
        if (btnBoard) btnBoard.className = inactiveClass;
        if (btnArea) btnArea.className = inactiveClass;
        if (btnGrid) btnGrid.className = inactiveClass;

        // Hide all containers
        if (tableEl) tableEl.classList.add('hidden');
        if (matrixEl) matrixEl.classList.add('hidden');
        if (boardEl) boardEl.classList.add('hidden');
        if (areaEl) areaEl.classList.add('hidden');
        if (gridEl) gridEl.classList.add('hidden');

        if (mode === 'table') {
            if (tableEl) tableEl.classList.remove('hidden');
            if (btnTable) btnTable.className = activeClass;
            if (paginationBar) paginationBar.style.display = filteredCards.length > 0 ? '' : 'none';
        } else if (mode === 'matrix') {
            if (matrixEl) matrixEl.classList.remove('hidden');
            if (btnMatrix) btnMatrix.className = activeClass;
            if (paginationBar) paginationBar.style.display = 'none';
        } else if (mode === 'board') {
            if (boardEl) boardEl.classList.remove('hidden');
            if (btnBoard) btnBoard.className = activeClass;
            if (paginationBar) paginationBar.style.display = 'none';
        } else if (mode === 'area') {
            if (areaEl) areaEl.classList.remove('hidden');
            if (btnArea) btnArea.className = activeClass;
            if (paginationBar) paginationBar.style.display = 'none';
        } else {
            // 'grid' (Cards)
            if (gridEl) gridEl.classList.remove('hidden');
            if (btnGrid) btnGrid.className = activeClass;
            if (paginationBar) paginationBar.style.display = filteredCards.length > 0 ? '' : 'none';
        }

        renderPagination();
    }

    /**
     * Smart string search matcher.
     */
    function smartMatch(cardVal, filterVal) {
        if (!filterVal) return true;
        if (!cardVal || cardVal.trim() === '') return false;
        cardVal = cardVal.toLowerCase().trim();
        filterVal = filterVal.toLowerCase().trim();
        return cardVal.includes(filterVal) || filterVal.includes(cardVal);
    }

    function exportComplianceCsv() {
        const search   = (document.getElementById('comp-search')   || {}).value || '';
        const body     = (document.getElementById('comp-body')     || {}).value || '';
        const category = (document.getElementById('comp-category') || {}).value || '';
        const area     = (document.getElementById('comp-area')     || {}).value || '';
        const status   = (document.getElementById('comp-status')   || {}).value || '';
        const priority = (document.getElementById('comp-priority') || {}).value || '';
        const unit     = (document.getElementById('comp-unit')     || {}).value || '';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (body) params.append('body', body);
        if (category) params.append('category', category);
        if (area) params.append('area', area);
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);
        if (unit) params.append('responsible_unit', unit);

        const url = "{{ route('compliance.export') }}" + (params.toString() ? '?' + params.toString() : '');
        window.location.href = url;
    }

    function clearAllFilters() {
        const search = document.getElementById('comp-search');
        const body = document.getElementById('comp-body');
        const category = document.getElementById('comp-category');
        const area = document.getElementById('comp-area');
        const status = document.getElementById('comp-status');
        const priority = document.getElementById('comp-priority');
        const unit = document.getElementById('comp-unit');

        if (search) search.value = '';
        if (body) body.value = '';
        if (category) category.value = '';
        if (area) area.value = '';
        if (status) status.value = '';
        if (priority) priority.value = '';
        if (unit) unit.value = '';

        applyFilters(false);
    }

    /**
     * Central filter entry point.
     * Evaluates criteria and synchronizes Cards, Table, Kanban Board, and Area views.
     */
    function applyFilters(isUserAction = false) {
        const search   = (document.getElementById('comp-search')   || {}).value || '';
        const body     = (document.getElementById('comp-body')     || {}).value || '';
        const category = (document.getElementById('comp-category') || {}).value || '';
        const area     = (document.getElementById('comp-area')     || {}).value || '';
        const status   = (document.getElementById('comp-status')   || {}).value || '';
        const priority = (document.getElementById('comp-priority') || {}).value || '';
        const unit     = (document.getElementById('comp-unit')     || {}).value || '';

        const allCards = document.querySelectorAll('#compliance-grid > [data-id]');
        const noMatches = document.getElementById('no-matches-row');

        filteredCards = [];
        const matchedIds = new Set();

        allCards.forEach(card => {
            const cardId       = card.getAttribute('data-id');
            const cardTitle    = (card.getAttribute('data-title') || '').toLowerCase();
            const cardDesc     = (card.getAttribute('data-desc') || '').toLowerCase();
            const cardResp     = (card.getAttribute('data-resp') || '').toLowerCase();
            const cardReco     = (card.getAttribute('data-recommendation') || '').toLowerCase();
            const cardCat      = (card.getAttribute('data-category') || '').toLowerCase();
            const cardArea     = (card.getAttribute('data-area') || '').toLowerCase();
            const cardSchool   = (card.getAttribute('data-school') || '').toLowerCase();
            const cardBody     = (card.getAttribute('data-body') || '');
            const cardStatus   = (card.getAttribute('data-status') || '');
            const cardPriority = (card.getAttribute('data-priority') || '');
            const cardProgCode = (card.getAttribute('data-program-code') || '').toLowerCase();

            // Extract multi-target program/school/unit assignments
            let assignmentPrograms = [];
            let assignmentSchools = [];
            let assignmentUnits = [];
            try {
                const rawAssignments = card.getAttribute('data-assignments');
                if (rawAssignments) {
                    const assignments = JSON.parse(rawAssignments);
                    assignments.forEach(a => {
                        if (a.program_code) assignmentPrograms.push(a.program_code.toLowerCase());
                        if (a.program_name) assignmentPrograms.push(a.program_name.toLowerCase());
                        if (a.school_name) assignmentSchools.push(a.school_name.toLowerCase());
                        if (a.unit_name) assignmentUnits.push(a.unit_name.toLowerCase());
                        if (a.unit_code) assignmentUnits.push(a.unit_code.toLowerCase());
                    });
                }
            } catch (e) {}

            const allProgramsText = cardProgCode + ' ' + assignmentPrograms.join(' ');
            const allSchoolsText = cardSchool + ' ' + assignmentSchools.join(' ');
            const allUnitsText = cardResp + ' ' + assignmentUnits.join(' ');

            const searchLower = search.toLowerCase().trim();

            const matchSearch = !searchLower ||
                cardTitle.includes(searchLower) ||
                cardDesc.includes(searchLower) ||
                allUnitsText.includes(searchLower) ||
                cardReco.includes(searchLower) ||
                cardCat.includes(searchLower) ||
                cardArea.includes(searchLower) ||
                allSchoolsText.includes(searchLower) ||
                allProgramsText.includes(searchLower);

            const matchBody     = smartMatch(cardBody, body);
            const matchCategory = smartMatch(cardCat, category);
            const matchArea     = smartMatch(cardArea, area);
            const matchStatus   = !status   || cardStatus.toLowerCase() === status.toLowerCase();
            const matchPriority = !priority || cardPriority.toLowerCase() === priority.toLowerCase();
            const matchUnit     = smartMatch(allUnitsText, unit);

            const visible = matchSearch && matchBody && matchCategory && matchArea && matchStatus && matchPriority && matchUnit;

            // Hide card initially — renderPagination will reveal active page slice
            card.style.display = 'none';

            if (visible) {
                filteredCards.push(card);
                matchedIds.add(String(cardId));
            }
        });

        // 1. Sync Kanban Board View cards & stage counters
        const stageCounts = {
            'recommendation_created': 0,
            'action_plan_submitted': 0,
            'admin_reviewing': 0,
            'compliant': 0
        };

        document.querySelectorAll('#compliance-board-view .board-item-card').forEach(boardCard => {
            const bId = boardCard.getAttribute('data-board-id');
            const bStage = boardCard.getAttribute('data-board-stage') || 'recommendation_created';
            const isMatch = matchedIds.has(String(bId));
            boardCard.style.display = isMatch ? '' : 'none';
            if (isMatch && stageCounts[bStage] !== undefined) {
                stageCounts[bStage]++;
            }
        });

        // Update Board Counter badges & empty state indicators
        const boardCountRec = document.getElementById('board-count-rec');
        const boardCountPlan = document.getElementById('board-count-plan');
        const boardCountReview = document.getElementById('board-count-review');
        const boardCountCompliant = document.getElementById('board-count-compliant');

        if (boardCountRec) boardCountRec.textContent = stageCounts['recommendation_created'];
        if (boardCountPlan) boardCountPlan.textContent = stageCounts['action_plan_submitted'];
        if (boardCountReview) boardCountReview.textContent = stageCounts['admin_reviewing'];
        if (boardCountCompliant) boardCountCompliant.textContent = stageCounts['compliant'];

        document.querySelectorAll('#compliance-board-view [data-board-column]').forEach(col => {
            const st = col.getAttribute('data-board-column');
            const count = stageCounts[st] || 0;
            const noMatchMsg = col.querySelector('.board-no-match-indicator');
            const emptyMsg = col.querySelector('.board-empty-indicator');
            if (noMatchMsg) {
                noMatchMsg.style.display = (count === 0 && (!emptyMsg || emptyMsg.style.display === 'none')) ? '' : 'none';
            }
        });

        // 1.5. Sync Matrix Grid View cards
        document.querySelectorAll('#compliance-matrix-view .matrix-task-card').forEach(mCard => {
            const mId = mCard.getAttribute('data-matrix-id');
            const isMatch = matchedIds.has(String(mId));
            mCard.style.display = isMatch ? '' : 'none';
        });

        // 2. Sync Grouped by Area View cards & area counters + Body Pills filter
        document.querySelectorAll('#compliance-area-view .area-group-card').forEach(areaCard => {
            const cardBody = (areaCard.getAttribute('data-area-body') || '').toLowerCase().trim();
            const matchesBodyFilter = (currentAreaBodyFilter === 'all' || cardBody === currentAreaBodyFilter || cardBody.includes(currentAreaBodyFilter));

            let areaVisibleCount = 0;
            areaCard.querySelectorAll('.area-item-card').forEach(itemCard => {
                const aId = itemCard.getAttribute('data-area-item-id');
                const isMatch = matchedIds.has(String(aId)) && matchesBodyFilter;
                itemCard.style.display = isMatch ? '' : 'none';
                if (isMatch) areaVisibleCount++;
            });

            const counterEl = areaCard.querySelector('.area-item-counter');
            if (counterEl) counterEl.textContent = `${areaVisibleCount} Task${areaVisibleCount !== 1 ? 's' : ''}`;
            
            // Show area card only if at least one matching item is inside and matches the selected body pill
            areaCard.style.display = (areaVisibleCount > 0 && matchesBodyFilter) ? '' : 'none';
        });

        // 3. Reset to page 1 and render table & card pagination
        currentPage = 1;
        renderPagination();

        // 4. Update visible-count badge in filter toolbar
        const countEl = document.getElementById('visible-count');
        if (countEl) countEl.textContent = filteredCards.length;

        // 5. Toggle empty/no-matches indicators
        if (noMatches) {
            noMatches.style.display = (filteredCards.length === 0 && allCards.length > 0) ? '' : 'none';
        }

        if (isUserAction && filteredCards.length > 0) {
            const container = document.getElementById('compliance-table-view') || document.getElementById('compliance-grid');
            if (container && container.style.display !== 'none') {
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    // Initialize pagination & view on page load
    document.addEventListener('DOMContentLoaded', () => {
        // Build initial filteredCards from all cards
        filteredCards = Array.from(document.querySelectorAll('#compliance-grid > [data-id]'));
        renderPagination();
        setComplianceView(currentComplianceView);

        // Set initial visible-count to total records
        const countEl = document.getElementById('visible-count');
        if (countEl) countEl.textContent = filteredCards.length;

        setupAutoContactPopulate();
        setupAutoSchoolPopulate();
        setupAccreditingBodyAreas();
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openManageLabsModal() {
        openModal('manage-labs-modal');
        switchManageLabsTab('depts');
    }

    function switchManageLabsTab(tab) {
        const deptsTab   = document.getElementById('manage-labs-tab-depts');
        const deptsPanel = document.getElementById('manage-labs-panel-depts');

        const activeClass   = 'px-3 py-3 text-xs font-bold text-hau-maroon border-b-2 border-hau-maroon focus:outline-none transition';

        if (deptsTab) deptsTab.className = activeClass;
        if (deptsPanel) deptsPanel.classList.remove('hidden');
        loadDeptsList();
    }

    // ---- Departments CRUD ----
    let manageDepartmentsData = [];

    function loadDeptsList() {
        const container = document.getElementById('manage-depts-list-container');
        container.innerHTML = '<div class="text-center py-6 text-xs text-gray-400">Loading departments...</div>';

        fetch("{{ route('admin.categories.index') }}", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            // Only department-level units (those with a parent_unit_id)
            manageDepartmentsData = (data.responsibleUnits || []).filter(u => u.parent_unit_id != null);
            renderDeptsList(manageDepartmentsData);
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center py-6 text-xs text-rose-500">Failed to load departments.</div>';
        });
    }

    function renderDeptsList(depts) {
        const container = document.getElementById('manage-depts-list-container');
        const countEl   = document.getElementById('manage-depts-count');
        countEl.textContent = depts.length + ' Total';

        if (depts.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-xs text-gray-400">No departments found.</div>';
            return;
        }

        // Group by parent school name
        const grouped = {};
        depts.forEach(dept => {
            const schoolName = dept.parent ? dept.parent.name : (dept.college ? dept.college.name : 'Other');
            if (!grouped[schoolName]) grouped[schoolName] = [];
            grouped[schoolName].push(dept);
        });

        let html = '';
        Object.keys(grouped).sort().forEach(schoolName => {
            html += `<div class="mb-3">
                <div class="text-[9px] font-black uppercase tracking-widest text-hau-maroon/60 mb-1.5 px-1">${escapeHtml(schoolName)}</div>
                <div class="space-y-1.5">`;
            grouped[schoolName].sort((a,b)=>a.name.localeCompare(b.name)).forEach(dept => {
                html += `<div class="bg-gray-50 rounded-xl p-2.5 border border-gray-150 flex items-center justify-between gap-3 text-xs dept-item-row" data-search-term="${escapeHtml((dept.name + ' ' + schoolName).toLowerCase())}">
                    <div>
                        <div class="font-bold text-gray-900">${escapeHtml(dept.name)}</div>
                        ${dept.code ? `<div class="text-[10px] text-hau-maroon font-mono mt-0.5">${escapeHtml(dept.code)}</div>` : ''}
                    </div>
                    <button type="button" onclick="deleteDepartment(${dept.responsible_unit_id})" class="p-1 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Delete Department">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>`;
            });
            html += '</div></div>';
        });

        container.innerHTML = html;
    }

    function filterDeptsList() {
        const query = document.getElementById('manage-depts-search').value.toLowerCase();
        const rows = document.querySelectorAll('#manage-depts-list-container .dept-item-row');
        rows.forEach(row => {
            const term = row.getAttribute('data-search-term') || '';
            row.style.display = term.includes(query) ? '' : 'none';
        });
    }

    function saveNewDepartment(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('create-dept-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const formData = new FormData(form);

        fetch("{{ route('admin.categories.store-unit') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Validation error'));
            return data;
        })
        .then(data => {
            alert('Department added successfully!');
            form.reset();
            loadDeptsList();
            // Reload the page dropdowns after a short delay so the new dept appears
            setTimeout(() => location.reload(), 800);
        })
        .catch(err => alert('Error: ' + err.message))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '+ Add Department';
        });
    }

    function deleteDepartment(id) {
        if (!confirm('Delete this department? Compliance items assigned to it will lose their department assignment.')) return;

        fetch(`/admin/manage-units/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error occurred');
            return data;
        })
        .then(() => {
            alert('Department deleted.');
            loadDeptsList();
            setTimeout(() => location.reload(), 800);
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 100);
    }

    // ── Dynamic Recommendation Rows ──────────────────────────────────────
    function addRecoRow(containerId) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2 reco-row-animate';
        row.innerHTML = '<input type="text" name="recommendations[]" required placeholder="Enter a recommendation..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />' +
            '<button type="button" onclick="removeRecoRow(this)" class="p-1.5 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Remove">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>';
        container.appendChild(row);
        row.querySelector('input').focus();
    }

    function removeRecoRow(btn) {
        const container = btn.closest('[id$="-recommendations-list"]');
        const rows = container.querySelectorAll('div');
        if (rows.length > 1) {
            btn.closest('div').remove();
        }
    }

    // ── Dynamic Category / Laboratory Rows ─────────────────────────────────
    function addCategoryRow(containerId, value = '') {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML = '<input type="text" name="categories[]" required value="' + value.replace(/"/g, '&quot;') + '" placeholder="e.g. Faculty" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />' +
            '<button type="button" onclick="removeCategoryRow(this)" class="p-1.5 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Remove">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>';
        container.appendChild(row);
        if (!value) {
            row.querySelector('input').focus();
        }
    }

    function removeCategoryRow(btn) {
        const container = btn.closest('[id$="-categories-list"]');
        const rows = container.querySelectorAll('div');
        if (rows.length > 1) {
            btn.closest('div').remove();
        }
    }

    let currentDetailId = null;

    function getRecoStatusBadge(status, isCompleted) {
        if (status === 'approved' || isCompleted) {
            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approved</span>';
        }
        if (status === 'under_review') {
            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><svg class="w-3 h-3 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Under Review</span>';
        }
        if (status === 'needs_revision') {
            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"><svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Needs Revision</span>';
        }
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">Pending Evidence</span>';
    }

    function updateCardRecoCache(recordId, itemId, updatedProperties) {
        const card = document.querySelector(`#compliance-grid > [data-id="${recordId}"]`);
        if (!card) return;

        let recos = [];
        try {
            recos = JSON.parse(card.getAttribute('data-recommendations') || '[]');
        } catch (e) {}

        recos = recos.map(item => {
            const currentId = item.recommendation_item_id || item.id;
            if (String(currentId) === String(itemId)) {
                return Object.assign({}, item, updatedProperties);
            }
            return item;
        });

        card.setAttribute('data-recommendations', JSON.stringify(recos));

        // Update item on card preview if present
        const textElCard = document.getElementById('reco-text-card-' + itemId);
        if (textElCard && updatedProperties.is_completed !== undefined) {
            if (updatedProperties.is_completed) {
                textElCard.classList.add('checklist-text-completed');
            } else {
                textElCard.classList.remove('checklist-text-completed');
            }
        }

        const badgeElCard = document.getElementById('reco-badge-card-' + itemId);
        if (badgeElCard && updatedProperties.status) {
            const st = updatedProperties.status;
            if (st === 'approved' || updatedProperties.is_completed) {
                badgeElCard.innerHTML = '<span class="inline-flex px-1.5 py-0.25 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-150">Approved</span>';
            } else if (st === 'under_review') {
                badgeElCard.innerHTML = '<span class="inline-flex px-1.5 py-0.25 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-150">Reviewing</span>';
            } else if (st === 'needs_revision') {
                badgeElCard.innerHTML = '<span class="inline-flex px-1.5 py-0.25 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-150">Needs Rev</span>';
            } else {
                badgeElCard.innerHTML = '<span class="inline-flex px-1.5 py-0.25 rounded text-[9px] font-bold bg-gray-100 text-gray-500">Pending</span>';
            }
        }

        // Checkbox sync on card
        const cbCard = document.querySelector(`.checklist-item[data-item-id="${itemId}"] input[type="checkbox"]`);
        if (cbCard && updatedProperties.is_completed !== undefined) {
            cbCard.checked = updatedProperties.is_completed;
        }
    }

    // ── Toggle Recommendation Checklist Item (QA Admin AJAX) ──────────────────────
    function toggleRecommendation(itemId, recordId) {
        if ("{{ $role }}" !== 'QA Admin') {
            alert('Only QA Admin can directly mark recommendations as completed. Use "Submit Evidence" to provide your documentation.');
            return;
        }

        fetch(`/compliance/recommendations/${itemId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update cached state
                updateCardRecoCache(recordId, itemId, {
                    is_completed: data.is_completed,
                    status: data.status
                });

                // Update text styling in modal
                const textElModal = document.getElementById('reco-text-modal-' + itemId);
                if (textElModal) {
                    if (data.is_completed) {
                        textElModal.classList.add('line-through', 'text-gray-400');
                    } else {
                        textElModal.classList.remove('line-through', 'text-gray-400');
                    }
                }

                // Sync status badge in modal
                const badgeModal = document.getElementById('reco-status-badge-' + itemId);
                if (badgeModal) {
                    badgeModal.innerHTML = getRecoStatusBadge(data.status, data.is_completed);
                }

                // Update progress bar & percentage
                const bar = document.getElementById('bar-' + recordId);
                if (bar) bar.style.width = data.completion_rate + '%';
                const rateEl = document.querySelector('.completion-rate-' + recordId);
                if (rateEl) rateEl.innerText = data.completion_rate + '%';

                // If overall task status changed
                if (data.overall_status) {
                    const card = document.querySelector(`#compliance-grid > [data-id="${recordId}"]`);
                    if (card) card.setAttribute('data-status', data.overall_status);
                }
            } else {
                alert(data.message || 'Action failed.');
            }
        })
        .catch(err => {
            console.error('Toggle failed:', err);
            alert('Unable to toggle recommendation. Please check permissions.');
        });
    }

    // ── Approve Recommendation Item (QA Admin) ──────────────────────────
    function approveRecommendationItem(itemId, recordId) {
        if (!confirm('Approve this recommendation item as completed?')) return;

        fetch(`/compliance/recommendations/${itemId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCardRecoCache(recordId, itemId, {
                    is_completed: true,
                    status: 'approved',
                    admin_remarks: null
                });

                // Update modal row UI
                const textElModal = document.getElementById('reco-text-modal-' + itemId);
                if (textElModal) textElModal.classList.add('line-through', 'text-gray-400');

                const badgeModal = document.getElementById('reco-status-badge-' + itemId);
                if (badgeModal) badgeModal.innerHTML = getRecoStatusBadge('approved', true);

                const cbModal = document.querySelector(`#modal-reco-row-${itemId} input[type="checkbox"]`);
                if (cbModal) cbModal.checked = true;

                const remarksBox = document.getElementById('reco-remarks-box-' + itemId);
                if (remarksBox) remarksBox.remove();

                // Update progress bar & rate
                const bar = document.getElementById('bar-' + recordId);
                if (bar) bar.style.width = data.completion_rate + '%';
                const rateEl = document.querySelector('.completion-rate-' + recordId);
                if (rateEl) rateEl.innerText = data.completion_rate + '%';

                if (data.overall_status) {
                    const card = document.querySelector(`#compliance-grid > [data-id="${recordId}"]`);
                    if (card) card.setAttribute('data-status', data.overall_status);
                    const statusBadge = document.getElementById('detail-status');
                    if (statusBadge && data.overall_status === 'Compliant') {
                        statusBadge.innerText = 'Compliant';
                        statusBadge.className = 'inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-700 border-emerald-100';
                    }
                }

                alert('Recommendation approved successfully!');
                // Re-render the detail modal to update actions
                if (_currentDetailCard) openDetailModal(_currentDetailCard);
            } else {
                alert(data.message || 'Error approving recommendation.');
            }
        })
        .catch(err => {
            console.error('Approve failed:', err);
            alert('Failed to approve recommendation.');
        });
    }

    // ── Reject Recommendation Item Modal & Handler ──────────────────────
    let _activeRejectItemId = null;

    function openRejectItemModal(itemId, recoText) {
        _activeRejectItemId = itemId;
        document.getElementById('reject-item-text-preview').innerText = recoText;
        document.getElementById('reject-item-remarks').value = '';
        openModal('reject-item-modal');
    }

    function closeRejectItemModal() {
        _activeRejectItemId = null;
        closeModal('reject-item-modal');
    }

    function submitRejectItem(e) {
        e.preventDefault();
        if (!_activeRejectItemId) return;

        const remarks = document.getElementById('reject-item-remarks').value.trim();
        if (!remarks) {
            alert('Please provide feedback remarks for the unit.');
            return;
        }

        const btn = document.getElementById('reject-item-submit-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch(`/compliance/recommendations/${_activeRejectItemId}/reject`, {
            method: 'POST',
            body: JSON.stringify({ admin_remarks: remarks }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (currentDetailId) {
                    updateCardRecoCache(currentDetailId, _activeRejectItemId, {
                        is_completed: false,
                        status: 'needs_revision',
                        admin_remarks: remarks
                    });
                }
                closeRejectItemModal();
                alert('Revision requested. The submitting unit has been notified.');
                if (_currentDetailCard) openDetailModal(_currentDetailCard);
            } else {
                alert(data.message || 'Error requesting revision.');
            }
        })
        .catch(err => {
            console.error('Reject item failed:', err);
            alert('Failed to submit revision request.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Send Revision Request';
        });
    }

    // ── Submit Recommendation Evidence Modal & Handler ──────────────────
    let _activeEvidenceItemId = null;

    function openSubmitItemEvidenceModal(itemId, recoText, currentLink = '') {
        _activeEvidenceItemId = itemId;
        document.getElementById('evidence-item-text-preview').innerText = recoText;
        document.getElementById('evidence-item-link').value = currentLink || '';
        openModal('submit-item-evidence-modal');
    }

    function closeSubmitItemEvidenceModal() {
        _activeEvidenceItemId = null;
        closeModal('submit-item-evidence-modal');
    }

    function submitItemEvidence(e) {
        e.preventDefault();
        if (!_activeEvidenceItemId) return;

        const link = document.getElementById('evidence-item-link').value.trim();
        if (!link) {
            alert('Please enter a valid evidence URL.');
            return;
        }

        const btn = document.getElementById('evidence-item-submit-btn');
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        fetch(`/compliance/recommendations/${_activeEvidenceItemId}/evidence`, {
            method: 'POST',
            body: JSON.stringify({ evidence_link: link }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (currentDetailId) {
                    updateCardRecoCache(currentDetailId, _activeEvidenceItemId, {
                        status: data.status,
                        evidence_link: data.evidence_link,
                        admin_remarks: null
                    });
                }
                closeSubmitItemEvidenceModal();
                alert('Evidence link submitted successfully! It is now queued for QA Admin review.');
                if (_currentDetailCard) openDetailModal(_currentDetailCard);
            } else {
                alert(data.message || 'Error submitting evidence link.');
            }
        })
        .catch(err => {
            console.error('Evidence submission failed:', err);
            alert('Failed to submit evidence.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Submit Evidence Link';
        });
    }

    const accreditingBodiesMap = @json($dbAccreditingBodies->keyBy('code')->map(function($ab) {
        return [
            'code' => $ab->code,
            'name' => $ab->name,
            'areas' => $ab->areas ?? []
        ];
    }));

    function addAreaRow(containerId, value = '') {
        const container = document.getElementById(containerId);
        
        // Find which accrediting body is selected based on container (add-areas-list or edit-areas-list)
        const isEdit = containerId === 'edit-areas-list';
        const bodySelectId = isEdit ? 'edit-accrediting_body' : 'add-accrediting_body';
        const bodySelect = document.getElementById(bodySelectId);
        const selectedBody = bodySelect ? bodySelect.value : '';

        const row = document.createElement('div');
        row.className = 'flex items-center gap-1.5';

        // Get areas list for selected body
        let areas = [];
        if (selectedBody && accreditingBodiesMap[selectedBody]) {
            areas = accreditingBodiesMap[selectedBody].areas || [];
        }

        // If no body is selected, disable the dropdown
        const disabledAttr = !selectedBody ? 'disabled' : '';
        const placeholderText = !selectedBody ? 'Select Accrediting Body first' : 'Select Area';

        let selectHtml = `<select name="areas[]" required ${disabledAttr} class="area-select block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">`;
        selectHtml += `<option value="" disabled ${!value ? 'selected' : ''}>${placeholderText}</option>`;
        
        areas.forEach(function(areaName) {
            const selected = (value === areaName) ? 'selected' : '';
            selectHtml += `<option value="${areaName}" ${selected}>${areaName}</option>`;
        });

        // Fallback option in case the value isn't in the standard areas list (so we don't lose data)
        if (value && !areas.includes(value)) {
            selectHtml += `<option value="${value}" selected>${value}</option>`;
        }

        selectHtml += `</select>`;

        row.innerHTML = selectHtml +
            '<button type="button" onclick="removeAreaRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>';
        
        container.appendChild(row);
    }

    function removeAreaRow(btn) {
        const container = btn.closest('[id$="-areas-list"]');
        const rows = container.querySelectorAll('.flex');
        if (rows.length > 1) {
            btn.closest('.flex').remove();
        }
    }

    function toggleDropdownNotice(modalPrefix, fieldType, hasValue) {
        const notice = document.getElementById(modalPrefix + '-' + fieldType + '-notice');
        if (notice) {
            notice.style.display = hasValue ? 'none' : 'inline-block';
        }
    }

    function updateAreasForModal(modalPrefix) {
        const bodySelect = document.getElementById(modalPrefix + '-accrediting_body');
        const areasListContainer = document.getElementById(modalPrefix + '-areas-list');
        if (!bodySelect || !areasListContainer) return;

        const selectedBody = bodySelect.value;
        toggleDropdownNotice(modalPrefix, 'area', !!selectedBody);

        const areas = (selectedBody && accreditingBodiesMap[selectedBody]) 
            ? (accreditingBodiesMap[selectedBody].areas || []) 
            : [];

        const selectEls = areasListContainer.querySelectorAll('.area-select');
        selectEls.forEach(function(selectEl) {
            const currentValue = selectEl.value;
            selectEl.innerHTML = '';
            
            const placeholderText = !selectedBody ? 'Select Accrediting Body first' : 'Select Area';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.disabled = true;
            defaultOpt.text = placeholderText;
            if (!currentValue) {
                defaultOpt.selected = true;
            }
            selectEl.appendChild(defaultOpt);

            if (!selectedBody) {
                selectEl.disabled = true;
            } else {
                selectEl.disabled = false;
                areas.forEach(function(areaName) {
                    const opt = document.createElement('option');
                    opt.value = areaName;
                    opt.text = areaName;
                    if (currentValue === areaName) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });

                // Fallback for custom values
                if (currentValue && !areas.includes(currentValue)) {
                    const opt = document.createElement('option');
                    opt.value = currentValue;
                    opt.text = currentValue;
                    opt.selected = true;
                    selectEl.appendChild(opt);
                }
            }
        });
    }



    let _currentDetailCard = null;
    let _currentDetailAssignments = [];

    function openSubmitLinkModal(btn) {
        const assignmentId = btn.getAttribute('data-assignment-id');
        if (!_currentDetailCard) return;
        const assignment = _currentDetailAssignments.find(a => String(a.id) === String(assignmentId));
        const activeLink = assignment ? (assignment.pending_document_link || assignment.document_link || '') : '';
        const actionPlan = assignment ? (assignment.action_plan || '') : '';
        closeModal('detail-modal');
        openProposeModal(_currentDetailCard, assignmentId, activeLink, actionPlan);
    }

    function openDetailModal(card) {
        _currentDetailCard = card;
        const id = card.getAttribute('data-id');
        const code = card.getAttribute('data-program-code');
        const title = card.getAttribute('data-title');
        const desc = card.getAttribute('data-desc') || 'No description provided.';
        const status = card.getAttribute('data-status');
        const priority = card.getAttribute('data-priority') || 'Medium';
        const due = card.getAttribute('data-due') || 'No deadline';
        const resp = card.getAttribute('data-resp') || 'Unassigned';
        const contactPerson = card.getAttribute('data-contact-person') || 'None';
        const contactEmail = card.getAttribute('data-contact-email') || '';
        const link = card.getAttribute('data-link');
        const pendingLink = card.getAttribute('data-pending-link');
        const approvalState = card.getAttribute('data-approval-state');
        const rejectionReason = card.getAttribute('data-rejection-reason');
        const body = card.getAttribute('data-body') || '';
        const school = card.getAttribute('data-school') || '';
        const recommendation = card.getAttribute('data-recommendation') || 'No recommendation statement provided.';
        const category = card.getAttribute('data-category') || '';
        const area = card.getAttribute('data-area') || '';
        const actionPlan = card.getAttribute('data-action-plan') || 'No action plan formulated yet.';
        const visitDate = card.getAttribute('data-visit-date') || '';
        const workflowStage = card.getAttribute('data-workflow-stage') || 'recommendation_created';

        // Parse recommendation items
        let recommendations = [];
        try { recommendations = JSON.parse(card.getAttribute('data-recommendations') || '[]'); } catch(e) {}

        // Inject content
        document.getElementById('detail-program').innerText = code;
        document.getElementById('detail-body').innerText = body;
        document.getElementById('detail-title').innerText = title;
        
        currentDetailId = id; // Store ID globally for refresh support

        // Build recommendations checklist display
        const recoContainer = document.getElementById('detail-recommendation');
        if (recommendations.length > 0) {
            let html = '<div class="space-y-3">';
            recommendations.forEach(function(item, idx) {
                const itemId = item.recommendation_item_id || item.id;
                const completed = item.is_completed || item.status === 'approved';
                const itemStatus = item.status || (completed ? 'approved' : 'pending');

                html += `<div class="bg-white rounded-xl p-3.5 border border-gray-200 shadow-2xs space-y-2.5 transition" id="modal-reco-row-${itemId}">`;
                
                // Header: Checkbox + Text + Status Badge
                html += '<div class="flex items-start justify-between gap-3">';
                html += '<div class="flex items-start gap-2.5 flex-1 min-w-0">';
                if ("{{ $role }}" === 'QA Admin') {
                    html += `<input type="checkbox" ${completed ? 'checked' : ''} onchange="toggleRecommendation(${itemId}, ${id})" class="w-4 h-4 rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon mt-0.5 cursor-pointer shrink-0" title="QA Admin: Click to toggle approval completion" />`;
                } else {
                    html += `<input type="checkbox" ${completed ? 'checked' : ''} disabled class="w-4 h-4 rounded border-gray-300 text-hau-maroon mt-0.5 cursor-not-allowed opacity-75 shrink-0" title="Only QA Admin can directly check off items." />`;
                }
                html += `<div class="space-y-0.5 flex-1 min-w-0">`;
                html += `<div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Recommendation #${idx + 1}</div>`;
                html += `<p id="reco-text-modal-${itemId}" class="text-xs font-bold leading-relaxed ${completed ? 'line-through text-gray-400' : 'text-gray-900'}">${escapeHtml(item.text)}</p>`;
                html += `</div></div>`;
                html += `<div class="shrink-0" id="reco-status-badge-${itemId}">${getRecoStatusBadge(itemStatus, completed)}</div>`;
                html += '</div>';

                // Evidence link if present
                if (item.evidence_link) {
                    html += `<div class="bg-gray-50 rounded-lg p-2 border border-gray-150 flex items-center justify-between text-xs gap-2">`;
                    html += `<span class="text-gray-500 font-semibold flex items-center gap-1"><svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg> Evidence:</span>`;
                    html += `<a href="${escapeHtml(item.evidence_link)}" target="_blank" class="text-hau-maroon hover:underline font-mono font-bold truncate max-w-[280px]" title="${escapeHtml(item.evidence_link)}">${escapeHtml(item.evidence_link)} 🔗</a>`;
                    html += `</div>`;
                }

                // Admin Remarks (if revision requested)
                if (item.admin_remarks) {
                    html += `<div class="bg-rose-50 border border-rose-200 rounded-lg p-2.5 text-xs text-rose-800 space-y-1" id="reco-remarks-box-${itemId}">`;
                    html += `<strong class="font-bold flex items-center gap-1.5 text-rose-700"><svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> QA Admin Revision Request Feedback:</strong>`;
                    html += `<p class="font-medium text-rose-800 leading-relaxed">${escapeHtml(item.admin_remarks)}</p>`;
                    html += `</div>`;
                }

                // Action Toolbar for this item
                html += `<div class="flex items-center justify-between pt-2 border-t border-gray-100 flex-wrap gap-2 text-xs">`;
                if ("{{ $role }}" === 'QA Admin') {
                    html += `<div class="flex items-center gap-1.5">`;
                    if (!completed) {
                        html += `<button type="button" onclick="approveRecommendationItem(${itemId}, ${id})" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs">`;
                        html += `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approve Item`;
                        html += `</button>`;
                        html += `<button type="button" onclick="openRejectItemModal(${itemId}, '${escapeHtml(item.text).replace(/'/g, "\\'")}')" class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-[11px] rounded-lg transition">`;
                        html += `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Request Revision`;
                        html += `</button>`;
                    }
                    html += `</div>`;
                    html += `<button type="button" onclick="openSubmitItemEvidenceModal(${itemId}, '${escapeHtml(item.text).replace(/'/g, "\\'")}', '${escapeHtml(item.evidence_link || '').replace(/'/g, "\\'")}')" class="text-[11px] font-bold text-gray-600 hover:text-hau-maroon transition underline ml-auto">`;
                    html += item.evidence_link ? 'Edit Evidence Link' : '+ Add Evidence Link';
                    html += `</button>`;
                } else {
                    html += `<button type="button" onclick="openSubmitItemEvidenceModal(${itemId}, '${escapeHtml(item.text).replace(/'/g, "\\'")}', '${escapeHtml(item.evidence_link || '').replace(/'/g, "\\'")}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-hau-maroon hover:bg-hau-maroon-light text-white font-bold text-[11px] rounded-lg transition shadow-2xs">`;
                    html += `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>`;
                    html += item.evidence_link ? 'Update Evidence Link' : 'Submit Evidence Link';
                    html += `</button>`;
                }
                html += `</div>`;

                html += `</div>`;
            });
            html += '</div>';
            recoContainer.innerHTML = html;
        } else {
            recoContainer.innerText = recommendation;
        }

        document.getElementById('detail-description').innerText = desc;
        document.getElementById('detail-action-plan').innerText = actionPlan;
        
        // School & Past name note
        const formerNames = {
            'School of Computing': 'formerly College of Information and Communications Technology (CICT)',
            'School of Nursing and Allied Medical Sciences': 'formerly College of Nursing (CON)'
        };
        const pastName = formerNames[school] ? '<br><span class="text-[10px] text-gray-400 font-normal italic">(' + formerNames[school] + ')</span>' : '';
        document.getElementById('detail-school').innerHTML = school + pastName;
        
        document.getElementById('detail-resp').innerText = resp;
        document.getElementById('detail-contact-person').innerText = contactPerson;
        if (contactEmail) {
            document.getElementById('detail-contact-email').innerHTML = '<a href="mailto:' + contactEmail + '" class="text-hau-maroon hover:underline font-mono text-[10px]">' + contactEmail + '</a>';
        } else {
            document.getElementById('detail-contact-email').innerText = '';
        }

        // Split and display Category and Area tags
        let catAreaHTML = '';
        if (area) {
            area.split(/[,;]+/).forEach(a => {
                if (a.trim()) catAreaHTML += '<span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 tracking-wider uppercase mr-1 border border-slate-200/50">' + a.trim() + '</span>';
            });
        }
        if (category) {
            category.split(/[,;]+/).forEach(c => {
                if (c.trim()) catAreaHTML += '<span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 tracking-wider uppercase mr-1">' + c.trim() + '</span>';
            });
        }
        document.getElementById('detail-cat-area').innerHTML = catAreaHTML || '—';

        document.getElementById('detail-due').innerText = due;

        // Status badge styling
        const statusBadge = document.getElementById('detail-status');
        statusBadge.innerText = status;
        statusBadge.className = 'inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border ';
        if (status === 'Compliant') {
            statusBadge.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-100');
        } else if (status === 'Non-Compliant') {
            statusBadge.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-100');
        } else {
            statusBadge.classList.add('bg-gray-50', 'text-gray-600', 'border-gray-150');
        }

        // Priority badge styling
        const priorityBadge = document.getElementById('detail-priority');
        if (priorityBadge) {
            priorityBadge.innerText = priority;
            priorityBadge.className = 'inline-flex px-2 py-0.5 rounded text-[10px] font-black border ';
            if (priority === 'Critical') {
                priorityBadge.classList.add('bg-rose-100', 'text-rose-800', 'border-rose-200');
            } else if (priority === 'High') {
                priorityBadge.classList.add('bg-amber-50', 'text-amber-800', 'border-amber-200');
            } else if (priority === 'Low') {
                priorityBadge.classList.add('bg-slate-150', 'text-slate-700', 'border-slate-200');
            } else {
                priorityBadge.classList.add('bg-blue-50', 'text-blue-700', 'border-blue-150');
            }
        }

        // Rejection alert
        const rejectedAlert = document.getElementById('detail-rejected-alert');
        if (approvalState === 'Rejected') {
            rejectedAlert.classList.remove('hidden');
            document.getElementById('detail-rejection-reason').innerText = '"' + rejectionReason + '"';
        } else {
            rejectedAlert.classList.add('hidden');
        }

        // ── 4-Stage Workflow Timeline (with SVG icons) ────────────────────
        const stageOrder = ['recommendation_created', 'action_plan_submitted', 'admin_reviewing', 'compliant'];
        const stageMeta = {
            recommendation_created: {
                label: 'Recommendation Created',
                descText: 'QA Admin logged the accreditor recommendation. Awaiting unit or department action plan.',
                iconSvg: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
                dotDone: 'bg-hau-maroon text-white', dotActive: 'bg-hau-maroon/10 border-2 border-hau-maroon/40 text-hau-maroon', dotIdle: 'bg-gray-200 text-gray-400',
                labelDone: 'text-gray-500 font-semibold', labelActive: 'text-hau-maroon font-black', labelIdle: 'text-gray-400 font-semibold',
                descDone: 'text-gray-400', descActive: 'text-gray-600', descIdle: 'text-gray-300',
                barDone: 'bg-hau-maroon/50', barIdle: 'bg-gray-200',
            },
            action_plan_submitted: {
                label: 'Action Plan Submitted',
                descText: 'Unit or Department submitted an action plan and evidence document for admin review.',
                iconSvg: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                dotDone: 'bg-hau-gold text-hau-maroon-dark', dotActive: 'bg-hau-gold/15 border-2 border-hau-gold text-hau-gold-dark', dotIdle: 'bg-gray-200 text-gray-400',
                labelDone: 'text-gray-500 font-semibold', labelActive: 'text-hau-gold-dark font-black', labelIdle: 'text-gray-400 font-semibold',
                descDone: 'text-gray-400', descActive: 'text-hau-gold-dark', descIdle: 'text-gray-300',
                barDone: 'bg-hau-gold/60', barIdle: 'bg-gray-200',
            },
            admin_reviewing: {
                label: 'Admin Reviews & Approves',
                descText: 'QA Admin is reviewing the submitted action plan and evidence. Pending approval decision.',
                iconSvg: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                dotDone: 'bg-hau-maroon-dark text-white', dotActive: 'bg-hau-maroon/10 border-2 border-hau-maroon text-hau-maroon-dark', dotIdle: 'bg-gray-200 text-gray-400',
                labelDone: 'text-gray-500 font-semibold', labelActive: 'text-hau-maroon-dark font-black', labelIdle: 'text-gray-400 font-semibold',
                descDone: 'text-gray-400', descActive: 'text-hau-maroon', descIdle: 'text-gray-300',
                barDone: 'bg-hau-maroon/50', barIdle: 'bg-gray-200',
            },
            compliant: {
                label: 'Status: Compliant',
                descText: 'Admin approved the action plan. Status has been automatically updated to Compliant.',
                iconSvg: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                dotDone: 'bg-emerald-500 text-white', dotActive: 'bg-emerald-50 border-2 border-emerald-400 text-emerald-700', dotIdle: 'bg-gray-200 text-gray-400',
                labelDone: 'text-gray-500 font-semibold', labelActive: 'text-emerald-800 font-black', labelIdle: 'text-gray-400 font-semibold',
                descDone: 'text-gray-400', descActive: 'text-emerald-700', descIdle: 'text-gray-300',
                barDone: 'bg-emerald-400', barIdle: 'bg-gray-200',
            },
        };

        const currentStageIdx = stageOrder.indexOf(workflowStage);
        let timelineHTML = '';
        stageOrder.forEach(function(s, i) {
            const meta = stageMeta[s];
            const isDone = i < currentStageIdx;
            const isActive = i === currentStageIdx;
            const isLast = i === stageOrder.length - 1;
            const dotClass = isDone ? meta.dotDone : isActive ? meta.dotActive : meta.dotIdle;
            const labelClass = isDone ? meta.labelDone : isActive ? meta.labelActive : meta.labelIdle;
            const descClass = isDone ? meta.descDone : isActive ? meta.descActive : meta.descIdle;
            const barClass = isDone ? meta.barDone : meta.barIdle;
            const iconContent = isDone ? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : meta.iconSvg;
            let stepDesc = meta.descText;
            if (isActive && approvalState === 'Rejected' && s === 'recommendation_created') {
                stepDesc = 'Rejected by admin — please revise and resubmit your action plan.';
            }
            const pendingEvidenceLink = (isActive && pendingLink && s === 'admin_reviewing')
                ? '<a href="' + pendingLink + '" target="_blank" class="text-[10px] text-hau-maroon hover:underline font-mono mt-0.5 block flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg> Pending Evidence Link</a>'
                : '';
            timelineHTML += '<div class="flex gap-3' + (isLast ? '' : ' pb-3') + '">'
                + '<div class="flex flex-col items-center shrink-0">'
                + '<span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-black ' + dotClass + ' shrink-0">' + iconContent + '</span>'
                + (!isLast ? '<div class="w-0.5 flex-1 mt-1 rounded ' + barClass + '"></div>' : '')
                + '</div>'
                + '<div class="pt-0.5 pb-3 min-w-0">'
                + '<p class="text-xs ' + labelClass + ' leading-none mb-0.5">' + meta.label + '</p>'
                + '<p class="text-[10px] ' + descClass + ' leading-relaxed">' + stepDesc + '</p>'
                + pendingEvidenceLink
                + '</div>'
                + '</div>';
        });
        document.getElementById('detail-workflow-steps').innerHTML = timelineHTML;

        // Link container
        const linkContainer = document.getElementById('detail-link-container');
        if (link) {
            linkContainer.innerHTML = '<a href="' + link + '" target="_blank" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 px-3 py-1 bg-hau-maroon/5 hover:bg-hau-maroon/10 text-hau-maroon font-bold rounded-lg border border-hau-maroon/15 transition font-mono text-xs">Open Link <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></a>';
        } else {
            linkContainer.innerHTML = '<span class="italic text-gray-400">No document attached</span>';
        }

        // Render sub-assignments breakdown table (Cross-Tab Matrix Grid)
        const assignmentsList = document.getElementById('detail-assignments-list');
        let assignments = [];
        try { assignments = JSON.parse(card.getAttribute('data-assignments') || '[]'); } catch(e) {}
        _currentDetailAssignments = assignments;

        if (assignmentsList) {
            if (assignments.length > 0) {
                // 1. Extract Unique Schools
                let schools = [];
                assignments.forEach(a => {
                    if (a.school_name && a.school_name.trim()) schools.push(a.school_name.trim());
                    else if (a.program_code) schools.push(a.program_code);
                });
                if (schools.length === 0 && school && school !== 'General') {
                    schools = school.split(';').map(s => s.trim()).filter(Boolean);
                }
                schools = [...new Set(schools)];
                if (schools.length === 0) schools = ['General'];

                // 2. Extract Unique Units
                let units = [];
                let seenUnitIds = new Set();
                assignments.forEach(a => {
                    if (a.unit_id && !seenUnitIds.has(a.unit_id)) {
                        seenUnitIds.add(a.unit_id);
                        units.push({
                            id: a.unit_id,
                            name: a.unit_name || ('Unit #' + a.unit_id),
                            code: a.unit_code || a.unit_name
                        });
                    }
                });
                if (units.length === 0) {
                    units = [{ id: null, name: 'Evidence Document', code: 'GEN' }];
                }

                // 3. Build Matrix HTML
                let html = '<div class="overflow-x-auto border border-gray-200 rounded-xl shadow-2xs">';
                html += '<table class="min-w-full text-left text-xs divide-y divide-gray-200">';
                html += '<thead class="bg-gray-50 text-[10px] uppercase font-bold text-gray-600"><tr>';
                html += '<th class="px-3.5 py-2.5 border-r border-gray-200 min-w-[160px]">Target School / College</th>';
                units.forEach(u => {
                    html += '<th class="px-3 py-2.5 text-center border-r border-gray-200 min-w-[140px]">' + escapeHtml(u.name) + '</th>';
                });
                html += '<th class="px-3 py-2.5 text-center bg-gray-100/60 font-black text-gray-800 min-w-[110px]">Status</th>';
                html += '</tr></thead><tbody class="divide-y divide-gray-150 bg-white">';

                schools.forEach(sName => {
                    let rowCompleted = 0;
                    let rowTotal = units.length;
                    let cellsHtml = '';

                    units.forEach(u => {
                        // Find matching assignment
                        const match = assignments.find(a => {
                            const matchSchool = (a.school_name && a.school_name.trim().toLowerCase() === sName.toLowerCase()) ||
                                                (a.program_code && a.program_code.toLowerCase() === sName.toLowerCase()) ||
                                                (sName === 'General' && !a.school_name && !a.program_id);
                            const matchUnit = u.id !== null ? (a.unit_id === u.id) : !a.unit_id;
                            return matchSchool && matchUnit;
                        }) || (units.length === 1 && units[0].id === null ? assignments.find(a => a.school_name && a.school_name.trim().toLowerCase() === sName.toLowerCase()) : null);

                        cellsHtml += '<td class="px-3 py-2.5 text-center border-r border-gray-150 align-middle">';
                        if (match) {
                            const isApproved = (match.status === 'Compliant' && match.approval_state !== 'Pending Approval');
                            const isReview = (match.approval_state === 'Pending Approval');
                            const isRej = (match.approval_state === 'Rejected');

                            if (isApproved) rowCompleted++;

                            cellsHtml += '<div class="flex flex-col items-center justify-center gap-1 py-0.5">';
                            if (isApproved) {
                                cellsHtml += '<span class="inline-flex items-center gap-1 px-2 py-0.25 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ Approved</span>';
                                if (match.document_link) {
                                    cellsHtml += '<a href="' + escapeHtml(match.document_link) + '" target="_blank" class="text-hau-maroon hover:underline font-mono text-[10px] font-semibold truncate max-w-[140px]" title="' + escapeHtml(match.document_link) + '">Evidence Link ↗</a>';
                                }
                            } else if (isReview) {
                                cellsHtml += '<span class="inline-flex items-center gap-1 px-2 py-0.25 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">⏳ In Review</span>';
                                if (match.pending_document_link) {
                                    cellsHtml += '<a href="' + escapeHtml(match.pending_document_link) + '" target="_blank" class="text-blue-700 hover:underline font-mono text-[10px] font-semibold truncate max-w-[140px]" title="' + escapeHtml(match.pending_document_link) + '">Review Doc ↗</a>';
                                }
                                if ("{{ $role }}" === 'QA Admin') {
                                    cellsHtml += '<div class="flex items-center gap-1 mt-0.5">' +
                                                 '<button type="button" onclick="quickApproveAssignment(' + match.id + ', ' + id + ')" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[9px]">Approve</button>' +
                                                 '<button type="button" onclick="quickRejectAssignment(' + match.id + ', ' + id + ')" class="px-2 py-0.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-[9px]">Reject</button>' +
                                                 '</div>';
                                }
                            } else if (isRej) {
                                cellsHtml += '<span class="inline-flex items-center gap-1 px-2 py-0.25 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="' + escapeHtml(match.rejection_reason || '') + '">✕ Needs Revision</span>';
                                if (match.rejection_reason) {
                                    cellsHtml += '<span class="text-[9px] text-rose-600 italic line-clamp-1 max-w-[140px]">"' + escapeHtml(match.rejection_reason) + '"</span>';
                                }
                                cellsHtml += '<button type="button" onclick="openCellSubmitModal(' + match.id + ', ' + id + ', \'' + escapeHtml(sName) + '\', \'' + escapeHtml(u.name) + '\', \'' + escapeHtml(match.rejection_reason || '') + '\')" class="px-2 py-0.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-[9px] transition mt-0.5">Submit Revision</button>';
                            } else {
                                cellsHtml += '<span class="inline-flex items-center gap-1 px-1.5 py-0.25 rounded text-[9px] font-medium bg-gray-100 text-gray-600 border border-gray-200">Pending</span>';
                                cellsHtml += '<button type="button" onclick="openCellSubmitModal(' + match.id + ', ' + id + ', \'' + escapeHtml(sName) + '\', \'' + escapeHtml(u.name) + '\', \'\')" class="px-2.5 py-0.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold rounded text-[9px] shadow-2xs transition mt-0.5">+ Add Link</button>';
                            }
                            cellsHtml += '</div>';
                        } else {
                            cellsHtml += '<span class="text-gray-300 italic text-[10px]">—</span>';
                        }
                        cellsHtml += '</td>';
                    });

                    const isComplete = (rowTotal > 0 && rowCompleted === rowTotal);
                    const rowRate = rowTotal > 0 ? Math.round((rowCompleted / rowTotal) * 100) : 0;

                    html += '<tr class="hover:bg-gray-50/70 transition">';
                    html += '<td class="px-3.5 py-2.5 border-r border-gray-150 font-bold text-gray-900 bg-gray-50/30 text-xs">' + escapeHtml(sName) + '</td>';
                    html += cellsHtml;
                    html += '<td class="px-3 py-2.5 text-center bg-gray-50/40 align-middle"><div class="space-y-1 flex flex-col items-center">' +
                            '<span class="font-mono text-[11px] font-black ' + (isComplete ? 'text-emerald-700' : 'text-gray-700') + '">' + rowCompleted + ' / ' + rowTotal + '</span>' +
                            '<div class="w-16 bg-gray-200 h-1.5 rounded-full overflow-hidden"><div class="' + (isComplete ? 'bg-emerald-500' : 'bg-hau-maroon') + ' h-full rounded-full" style="width: ' + rowRate + '%"></div></div>' +
                            '<span class="inline-flex px-1.5 py-0.25 rounded text-[8px] font-extrabold ' + (isComplete ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-150 text-gray-600') + '">' + (isComplete ? '100%' : 'In Progress') + '</span>' +
                            '</div></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                assignmentsList.innerHTML = html;
            } else {
                assignmentsList.innerHTML = '<span class="text-xs text-gray-400 italic">No specific target assignments attached.</span>';
            }
        }

        // Action buttons inside modal
        const buttonsContainer = document.getElementById('detail-action-buttons');
        const role = "{{ $role }}";
        if (role === 'QA Admin') {
            buttonsContainer.innerHTML = '<button onclick="closeModal(\'detail-modal\'); openEditModal(_currentDetailCard)" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">Edit Task</button>';
        } else {
            if (approvalState !== 'Pending Approval') {
                buttonsContainer.innerHTML = '<button onclick="closeModal(\'detail-modal\'); openProposeModal(_currentDetailCard)" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">Submit Action Plan</button>';
            } else {
                buttonsContainer.innerHTML = '<button disabled class="px-4 py-2 bg-hau-gold/15 text-hau-maroon text-xs font-bold rounded-lg border border-hau-gold/30 cursor-not-allowed flex items-center gap-1"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Awaiting Admin Approval</button>';
            }
        }

        openModal('detail-modal');
    }

    // ── Cell-Level Quick Submission Handlers ──
    function openCellSubmitModal(assignmentId, complianceId, schoolName, unitName, rejectionReason) {
        const compInput = document.getElementById('cell-submit-compliance-id');
        if (compInput) compInput.value = complianceId;

        const assInput = document.getElementById('cell-submit-assignment-id');
        if (assInput) assInput.value = assignmentId;

        const subTitle = document.getElementById('cell-submit-subtitle');
        if (subTitle) {
            subTitle.textContent = (schoolName || 'General') + ' — ' + (unitName || 'Responsible Unit');
        }

        const rejAlert = document.getElementById('cell-submit-rejection-alert');
        const rejText = document.getElementById('cell-submit-rejection-text');
        if (rejAlert && rejText) {
            if (rejectionReason && rejectionReason.trim()) {
                rejText.textContent = rejectionReason;
                rejAlert.classList.remove('hidden');
            } else {
                rejAlert.classList.add('hidden');
            }
        }

        const linkInput = document.getElementById('cell-submit-link');
        if (linkInput) linkInput.value = '';

        const planInput = document.getElementById('cell-submit-action-plan');
        if (planInput) planInput.value = '';

        openModal('cell-submit-modal');
    }

    async function submitCellEvidence(e) {
        e.preventDefault();
        const compId = document.getElementById('cell-submit-compliance-id').value;
        const assId = document.getElementById('cell-submit-assignment-id').value;
        const docLink = document.getElementById('cell-submit-link').value;
        const plan = document.getElementById('cell-submit-action-plan').value;
        const btn = document.getElementById('cell-submit-btn');

        btn.disabled = true;
        btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';

        try {
            const response = await fetch(`/compliance/${compId}/submit-update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    assignment_id: assId,
                    pending_document_link: docLink,
                    action_plan: plan
                })
            });

            const data = await response.json();
            if (data.success) {
                closeModal('cell-submit-modal');
                window.location.reload();
            } else {
                alert(data.message || 'Submission failed.');
            }
        } catch (err) {
            console.error(err);
            window.location.reload();
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>Submit for QA Review</span>';
        }
    }

    async function quickApproveAssignment(assignmentId, complianceId) {
        if (!confirm('Approve this unit evidence submission?')) return;
        try {
            const response = await fetch(`/compliance/${complianceId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ assignment_id: assignmentId })
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch(e) {
            window.location.reload();
        }
    }

    async function quickRejectAssignment(assignmentId, complianceId) {
        const reason = prompt('Please enter the reason for rejection / requested revision:');
        if (!reason || !reason.trim()) return;
        try {
            const response = await fetch(`/compliance/${complianceId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ assignment_id: assignmentId, rejection_reason: reason.trim() })
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch(e) {
            window.location.reload();
        }
    }

    function openAddModal() {
        filterProgramsBySchool('add');
        openModal('add-modal');
    }

    function openEditModal(card) {
        const id = card.getAttribute('data-id');
        const programId = card.getAttribute('data-program-id');
        const title = card.getAttribute('data-title');
        const desc = card.getAttribute('data-desc');
        const status = card.getAttribute('data-status');
        const priority = card.getAttribute('data-priority') || 'Medium';
        const due = card.getAttribute('data-due');
        const responsibleUnitId = card.getAttribute('data-responsible-unit-id') || '';
        const laboratoryId = card.getAttribute('data-laboratory-id') || '';
        const contactPerson = card.getAttribute('data-contact-person') || '';
        const contactEmail = card.getAttribute('data-contact-email') || '';
        const link = card.getAttribute('data-link');
        const body = card.getAttribute('data-body') || '';
        const school = card.getAttribute('data-school') || '';
        const area = card.getAttribute('data-area') || '';
        const category = card.getAttribute('data-category') || '';
        const actionPlan = card.getAttribute('data-action-plan') || '';
        const visitDate = card.getAttribute('data-visit-date') || '';

        // Parse recommendation items for edit
        let recommendations = [];
        try { recommendations = JSON.parse(card.getAttribute('data-recommendations') || '[]'); } catch(e) {}

        // Populate dynamic program & unit dropdowns in Edit Modal
        const editProgList = document.getElementById('edit-programs-list');
        const editUnitList = document.getElementById('edit-units-list');
        if (editProgList) editProgList.innerHTML = '';
        if (editUnitList) editUnitList.innerHTML = '';

        let assignments = [];
        try { assignments = JSON.parse(card.getAttribute('data-assignments') || '[]'); } catch(e) {}

        let progAssignments = assignments.filter(a => a.program_id);
        let unitAssignments = assignments.filter(a => a.unit_id);

        if (progAssignments.length > 0) {
            progAssignments.forEach(a => addProgramDropdownRow('edit-programs-list', a.program_id));
        } else if (programId) {
            addProgramDropdownRow('edit-programs-list', programId);
        } else {
            addProgramDropdownRow('edit-programs-list');
        }

        const respText = card.getAttribute('data-resp') || '';
        if (respText.includes('All Departments') || respText.includes('All Units')) {
            addUnitDropdownRow('edit-units-list', 'all');
        } else {
            let uniqueUnitIds = [];
            unitAssignments.forEach(a => {
                if (a.unit_id && !uniqueUnitIds.includes(String(a.unit_id))) {
                    uniqueUnitIds.push(String(a.unit_id));
                }
            });

            if (uniqueUnitIds.length > 0) {
                uniqueUnitIds.forEach(uId => addUnitDropdownRow('edit-units-list', uId));
            } else if (responsibleUnitId) {
                addUnitDropdownRow('edit-units-list', responsibleUnitId);
            } else {
                addUnitDropdownRow('edit-units-list');
            }
        }

        // Populate dynamic school dropdowns in Edit Modal
        const editSchoolList = document.getElementById('edit-schools-list');
        if (editSchoolList) editSchoolList.innerHTML = '';
        if (school) {
            const schoolsArr = school.split(/[,;]+/);
            schoolsArr.forEach(sch => {
                sch = sch.trim();
                if (sch) addSchoolDropdownRow('edit-schools-list', sch);
            });
        }
        if (!editSchoolList || editSchoolList.children.length === 0) {
            addSchoolDropdownRow('edit-schools-list');
        }

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('edit-title', title);
        setVal('edit-desc', desc);
        setVal('edit-status', status);
        setVal('edit-priority', priority);
        setVal('edit-due', due);
        setVal('edit-resp', responsibleUnitId);
        setVal('edit-category', category);
        setVal('edit-contact-person', contactPerson);
        setVal('edit-contact-email', contactEmail);

        const contactPersonEl = document.getElementById('edit-contact-person');
        const contactEmailEl = document.getElementById('edit-contact-email');
        if (contactPersonEl && contactEmailEl) {
            contactPersonEl.readOnly = false;
            contactEmailEl.readOnly = false;
            contactPersonEl.classList.remove('bg-gray-100', 'cursor-not-allowed');
            contactEmailEl.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }

        setVal('edit-link', link);
        setVal('edit-accrediting_body', body);
        setVal('edit-action_plan', actionPlan);
        setVal('edit-visit_date', visitDate);

        // Populate area rows
        const editAreasList = document.getElementById('edit-areas-list');
        editAreasList.innerHTML = '';
        if (area) {
            const areas = area.split(/[,;]+/);
            areas.forEach(function(ar) {
                ar = ar.trim();
                if (ar) {
                    addAreaRow('edit-areas-list', ar);
                }
            });
        }
        if (editAreasList.children.length === 0) {
            addAreaRow('edit-areas-list');
        }



        // Populate recommendation rows
        const editRecoList = document.getElementById('edit-recommendations-list');
        editRecoList.innerHTML = '';
        if (recommendations.length > 0) {
            recommendations.forEach(function(item) {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2 reco-row-animate';
                row.innerHTML = '<input type="text" name="recommendations[]" required value="' + (item.text || '').replace(/"/g, '&quot;') + '" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />' +
                    '<button type="button" onclick="removeRecoRow(this)" class="p-1.5 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Remove">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>';
                editRecoList.appendChild(row);
            });
        } else {
            addRecoRow('edit-recommendations-list');
        }

        document.getElementById('edit-form').action = `/compliance/${id}`;

        // Toggle dropdown helper notices based on loaded data
        toggleDropdownNotice('edit', 'area', !!body);

        filterProgramsBySchool('edit');
        openModal('edit-modal');
    }

    function openProposeModal(card, assignmentId = null, initialLink = '', initialPlan = '') {
        const id = card.getAttribute('data-id');
        const code = card.getAttribute('data-program-code');
        const body = card.getAttribute('data-body') || 'Accreditor';
        const title = card.getAttribute('data-title');
        const reco = card.getAttribute('data-recommendation') || '';
        const pendingStatus = card.getAttribute('data-pending-status') || card.getAttribute('data-status');
        const pendingLink = initialLink || card.getAttribute('data-pending-link') || card.getAttribute('data-link');
        const actionPlan = initialPlan || card.getAttribute('data-action-plan') || '';
        const responsibleUnitId = card.getAttribute('data-responsible-unit-id') || '';
        const contactPerson = card.getAttribute('data-contact-person') || '';
        const contactEmail = card.getAttribute('data-contact-email') || '';

        let assignInput = document.getElementById('propose-assignment-id');
        if (!assignInput) {
            assignInput = document.createElement('input');
            assignInput.type = 'hidden';
            assignInput.name = 'assignment_id';
            assignInput.id = 'propose-assignment-id';
            const form = document.getElementById('propose-form');
            if (form) form.appendChild(assignInput);
        }
        assignInput.value = assignmentId || '';

        document.getElementById('propose-task-program').innerText = code;
        document.getElementById('propose-task-body').innerText = body;
        document.getElementById('propose-task-title').innerText = title;
        
        if (reco) {
            document.getElementById('propose-reco-container').style.display = 'block';
            document.getElementById('propose-task-reco').innerText = reco;
        } else {
            document.getElementById('propose-reco-container').style.display = 'none';
        }

        document.getElementById('propose-link').value = pendingLink;
        document.getElementById('propose-action_plan').value = actionPlan;
        
        document.getElementById('propose-resp').value = responsibleUnitId;
        document.getElementById('propose-contact-person').value = contactPerson;
        document.getElementById('propose-contact-email').value = contactEmail;
        if (responsibleUnitId && responsibleUnitsMap[responsibleUnitId] && responsibleUnitsMap[responsibleUnitId].users && responsibleUnitsMap[responsibleUnitId].users.length > 0) {
            document.getElementById('propose-contact-person').readOnly = true;
            document.getElementById('propose-contact-email').readOnly = true;
            document.getElementById('propose-contact-person').classList.add('bg-gray-100', 'cursor-not-allowed');
            document.getElementById('propose-contact-email').classList.add('bg-gray-100', 'cursor-not-allowed');
        } else {
            document.getElementById('propose-contact-person').readOnly = false;
            document.getElementById('propose-contact-email').readOnly = false;
            document.getElementById('propose-contact-person').classList.remove('bg-gray-100', 'cursor-not-allowed');
            document.getElementById('propose-contact-email').classList.remove('bg-gray-100', 'cursor-not-allowed');
        }

        // Setup target selection dropdown if task has target assignments
        const targetContainer = document.getElementById('propose-target-container');
        const targetSelect = document.getElementById('propose-target-select');
        let assignments = [];
        try { assignments = JSON.parse(card.getAttribute('data-assignments') || '[]'); } catch(e) {}

        if (targetContainer && targetSelect) {
            targetSelect.innerHTML = '';
            if (assignments.length > 0) {
                targetContainer.style.display = 'block';
                assignments.forEach(function(a) {
                    const tName = a.program_code ? (a.program_code + ' — ' + (a.program_name || '')) : (a.school_name ? a.school_name : (a.unit_name || 'Department Target'));
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = tName + ' (' + (a.status || 'Pending') + ')';
                    opt.setAttribute('data-link', a.pending_document_link || a.document_link || '');
                    opt.setAttribute('data-plan', a.action_plan || '');
                    if (assignmentId && String(a.id) === String(assignmentId)) {
                        opt.selected = true;
                    }
                    targetSelect.appendChild(opt);
                });
                if (targetSelect.value) {
                    assignInput.value = targetSelect.value;
                    const selectedOpt = targetSelect.options[targetSelect.selectedIndex];
                    if (selectedOpt && !initialLink) {
                        document.getElementById('propose-link').value = selectedOpt.getAttribute('data-link') || '';
                        document.getElementById('propose-action_plan').value = selectedOpt.getAttribute('data-plan') || '';
                    }
                }
            } else {
                targetContainer.style.display = 'none';
            }
        }

        document.getElementById('propose-form').action = `/compliance/${id}/submit-update`;

        openModal('propose-modal');
    }

    function onProposeTargetChange(select) {
        const assignInput = document.getElementById('propose-assignment-id');
        if (assignInput) assignInput.value = select.value;
        const selectedOpt = select.options[select.selectedIndex];
        if (selectedOpt) {
            document.getElementById('propose-link').value = selectedOpt.getAttribute('data-link') || '';
            document.getElementById('propose-action_plan').value = selectedOpt.getAttribute('data-plan') || '';
        }
    }



    // ── Rejection Modal ──
    function openRejectModal(id, title) {
        document.getElementById('reject-form').action = `/compliance/${id}/reject`;
        document.getElementById('reject-reason').value = '';

        const modal = document.getElementById('reject-modal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 100);
    }

    // ── Auto-assign contact person when unit/department is selected ──
    const responsibleUnitsMap = @json($dbResponsibleUnits->keyBy('responsible_unit_id'));

    function resolveContact(unitId, contactPersonId, contactEmailId) {
        const contactPerson = document.getElementById(contactPersonId);
        const contactEmail  = document.getElementById(contactEmailId);
        if (!contactPerson || !contactEmail) return;

        let unit = responsibleUnitsMap[unitId];

        // Ensure fields are always editable by the user
        contactPerson.readOnly = false;
        contactEmail.readOnly  = false;
        contactPerson.classList.remove('bg-gray-100', 'cursor-not-allowed');
        contactEmail.classList.remove('bg-gray-100', 'cursor-not-allowed');

        if (unit && unit.users && unit.users.length > 0) {
            const user = unit.users[0];
            contactPerson.value = user.name  || '';
            contactEmail.value  = user.email || '';
        } else {
            contactPerson.value = '';
            contactEmail.value  = '';
        }
    }

    function setupAutoContactPopulate() {
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (!target || !target.tagName || target.tagName.toLowerCase() !== 'select') return;

            const isAddUnit = target.classList.contains('add-unit-select') || target.id === 'add-resp';
            const isEditUnit = target.classList.contains('edit-unit-select') || target.id === 'edit-resp';

            if (isAddUnit) {
                const unitId = target.value;
                resolveContact(unitId, 'add-contact-person', 'add-contact-email');

                const unit = responsibleUnitsMap[unitId];
                if (unit && unit.college) {
                    selectSchoolInContainer('add', unit.college.name);
                }
            } else if (isEditUnit) {
                const unitId = target.value;
                resolveContact(unitId, 'edit-contact-person', 'edit-contact-email');

                const unit = responsibleUnitsMap[unitId];
                if (unit && unit.college) {
                    selectSchoolInContainer('edit', unit.college.name);
                }
            }
        });

        const proposeResp = document.getElementById('propose-resp');
        if (proposeResp) {
            proposeResp.addEventListener('change', function() {
                const unitId = this.value;
                resolveContact(unitId, 'propose-contact-person', 'propose-contact-email');
            });
        }
    }

    function selectSchoolInContainer(prefix, collegeName) {
        if (!collegeName) return;
        const containerId = prefix === 'edit' ? 'edit-schools-list' : 'add-schools-list';
        const container = document.getElementById(containerId);
        if (!container) return;

        const selects = container.querySelectorAll('select[name="schools[]"]');
        let alreadySelected = false;
        let emptySelect = null;

        selects.forEach(sel => {
            if (sel.value === collegeName) {
                alreadySelected = true;
            } else if (!sel.value && !emptySelect) {
                emptySelect = sel;
            }
        });

        if (!alreadySelected) {
            if (emptySelect) {
                emptySelect.value = collegeName;
            } else {
                addSchoolDropdownRow(containerId, collegeName);
            }
        }
        filterProgramsBySchool(prefix);
    }

    function filterProgramsBySchool(prefixOrId, schoolName = null) {
        let prefix = 'add';
        if (prefixOrId === 'edit' || prefixOrId === 'edit-program_id' || (typeof prefixOrId === 'string' && prefixOrId.includes('edit'))) {
            prefix = 'edit';
        }

        const schoolsListId = prefix + '-schools-list';
        const programsListId = prefix + '-programs-list';

        const schoolsList = document.getElementById(schoolsListId);
        const programsList = document.getElementById(programsListId);
        if (!schoolsList || !programsList) return;

        const schoolSelects = schoolsList.querySelectorAll('select[name="schools[]"]');
        const selectedSchools = [];
        schoolSelects.forEach(sel => {
            if (sel.value && sel.value.trim() !== '') {
                selectedSchools.push(sel.value.trim());
            }
        });

        const programSelects = programsList.querySelectorAll('select[name="program_ids[]"]');
        programSelects.forEach(progSelect => {
            Array.from(progSelect.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = '';
                    return;
                }
                const college = opt.getAttribute('data-college') || '';
                const matches = selectedSchools.length === 0 || selectedSchools.includes(college);
                opt.style.display = matches ? '' : 'none';
            });

            const selectedOpt = progSelect.options[progSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value && selectedOpt.style.display === 'none') {
                progSelect.value = '';
            }
        });
    }

    function setupAutoSchoolPopulate() {
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (!target || !target.tagName || target.tagName.toLowerCase() !== 'select') return;

            if (target.name === 'schools[]') {
                const container = target.closest('#edit-schools-list, #add-schools-list');
                if (container) {
                    const prefix = container.id.startsWith('edit') ? 'edit' : 'add';
                    filterProgramsBySchool(prefix);
                }
            } else if (target.name === 'program_ids[]') {
                const container = target.closest('#edit-programs-list, #add-programs-list');
                if (container) {
                    const prefix = container.id.startsWith('edit') ? 'edit' : 'add';
                    const selectedOption = target.options[target.selectedIndex];
                    if (selectedOption) {
                        const collegeName = selectedOption.getAttribute('data-college');
                        if (collegeName) {
                            selectSchoolInContainer(prefix, collegeName);
                        }
                    }
                }
            }
        });

        // Apply initial filter
        filterProgramsBySchool('add');
        filterProgramsBySchool('edit');
    }

    function setupAccreditingBodyAreas() {
        const addBodySelect = document.getElementById('add-accrediting_body');
        if (addBodySelect) {
            addBodySelect.addEventListener('change', function() {
                updateAreasForModal('add');
            });
        }

        const editBodySelect = document.getElementById('edit-accrediting_body');
        if (editBodySelect) {
            editBodySelect.addEventListener('change', function() {
                updateAreasForModal('edit');
            });
        }
    }

    function addSchoolDropdownRow(containerId, selectedVal = '') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const firstSelect = document.querySelector('.add-school-select') || document.querySelector('select[name="schools[]"]');
        const optionsHtml = firstSelect ? firstSelect.innerHTML : '<option value="">Select School / College</option>';

        const row = document.createElement('div');
        row.className = 'flex items-center gap-1.5 school-dropdown-row';
        row.innerHTML = '<select name="schools[]" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">' +
                        optionsHtml +
                        '</select>' +
                        '<button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove school">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' +
                        '</button>';

        container.appendChild(row);
        if (selectedVal) {
            row.querySelector('select').value = selectedVal;
        }

        const prefix = containerId.startsWith('edit') ? 'edit' : 'add';
        filterProgramsBySchool(prefix);
    }

    function addProgramDropdownRow(containerId, selectedVal = '') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const firstSelect = document.querySelector('.add-program-select') || document.querySelector('select[name="program_ids[]"]');
        const optionsHtml = firstSelect ? firstSelect.innerHTML : '<option value="">Select a Program</option>';

        const row = document.createElement('div');
        row.className = 'flex items-center gap-1.5 program-dropdown-row';
        row.innerHTML = '<select name="program_ids[]" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">' +
                        optionsHtml +
                        '</select>' +
                        '<button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove program">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' +
                        '</button>';

        container.appendChild(row);
        if (selectedVal) {
            row.querySelector('select').value = selectedVal;
        }

        const prefix = containerId.startsWith('edit') ? 'edit' : 'add';
        filterProgramsBySchool(prefix);
    }

    function addUnitDropdownRow(containerId, selectedVal = '') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const firstSelect = document.querySelector('.add-unit-select') || document.querySelector('select[name="responsible_unit_ids[]"]');
        let optionsHtml = firstSelect ? firstSelect.innerHTML : '<option value="">Select Responsible Department/Unit</option>';
        if (!selectedVal) {
            optionsHtml = optionsHtml.replace(/\s+selected(=["']selected["'])?/gi, '');
        }

        const row = document.createElement('div');
        row.className = 'flex items-center gap-1.5 unit-dropdown-row';
        row.innerHTML = '<select name="responsible_unit_ids[]" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">' +
                        optionsHtml +
                        '</select>' +
                        '<button type="button" onclick="removeDropdownRow(this)" class="p-0.5 text-gray-400 hover:text-rose-600 rounded transition w-5 h-5 flex items-center justify-center shrink-0" title="Remove unit">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' +
                        '</button>';

        container.appendChild(row);
        const sel = row.querySelector('select');
        if (sel) {
            sel.value = selectedVal ? String(selectedVal) : '';
        }
    }

    function removeDropdownRow(btn) {
        const row = btn.closest('.school-dropdown-row, .program-dropdown-row, .unit-dropdown-row');
        if (!row) return;
        const parent = row.parentElement;
        const isSchoolRow = row.classList.contains('school-dropdown-row');
        const prefix = parent && parent.id && parent.id.startsWith('edit') ? 'edit' : 'add';

        if (parent && parent.children.length > 1) {
            row.remove();
        } else {
            const select = row.querySelector('select');
            if (select) select.value = '';
        }

        if (isSchoolRow) {
            filterProgramsBySchool(prefix);
        }
    }


</script>

<!-- ================= REJECTION REASON MODAL (Record Level) ================= -->
<div id="reject-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Reject Proposal</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">State the reason for rejecting this proposed compliance item</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('reject-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="reject-form" action="" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <p class="text-xs text-gray-500">Provide constructive feedback or a reason for rejecting the proposed compliance changes. The Unit or Department will see this feedback in order to submit revisions.</p>
                <div>
                    <label for="reject-reason" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Rejection Reason</label>
                    <textarea name="rejection_reason" id="reject-reason" required rows="3" placeholder="e.g. Please upload the document with authorized signatures." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">Reject Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= PER-RECOMMENDATION ITEM REJECTION / REVISION MODAL ================= -->
<div id="reject-item-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Request Revision</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Provide feedback on specific recommendation</p>
                </div>
            </div>
            <button type="button" onclick="closeRejectItemModal()" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form onsubmit="submitRejectItem(event)">
            <div class="p-6 space-y-4">
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-900">
                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block mb-1">Target Recommendation:</span>
                    <p id="reject-item-text-preview" class="font-semibold leading-relaxed"></p>
                </div>
                <div>
                    <label for="reject-item-remarks" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Revision Reason / Feedback Notes</label>
                    <textarea id="reject-item-remarks" required rows="3" placeholder="e.g. Evidence link is missing required dean endorsement. Please attach updated file." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"></textarea>
                    <span class="text-[10px] text-gray-400 mt-1 block">This feedback will be displayed directly to the submitting unit on this recommendation item.</span>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeRejectItemModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Cancel</button>
                <button type="submit" id="reject-item-submit-btn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">Send Revision Request</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= PER-RECOMMENDATION EVIDENCE SUBMISSION MODAL ================= -->
<div id="submit-item-evidence-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="modal-dark-header relative flex items-center justify-between text-white shrink-0 border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Attach Evidence</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Submit document link for recommendation item</p>
                </div>
            </div>
            <button type="button" onclick="closeSubmitItemEvidenceModal()" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form onsubmit="submitItemEvidence(event)">
            <div class="p-6 space-y-4">
                <div class="bg-hau-maroon/5 border border-hau-maroon/10 rounded-xl p-3 text-xs text-gray-800">
                    <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider block mb-1">Recommendation Item:</span>
                    <p id="evidence-item-text-preview" class="font-semibold leading-relaxed text-gray-900"></p>
                </div>
                <div>
                    <label for="evidence-item-link" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Evidence Document URL (SharePoint / Google Drive / Cloud Link)</label>
                    <input type="url" id="evidence-item-link" required placeholder="https://onedrive.live.com/... or https://drive.google.com/..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon font-mono" />
                    <span class="text-[10px] text-gray-400 mt-1 block">Submitting this evidence link will notify QA Admin and move the item status to <strong>Under Review</strong>.</span>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeSubmitItemEvidenceModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition cursor-pointer">Cancel</button>
                <button type="submit" id="evidence-item-submit-btn" class="px-5 py-2 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">Submit Evidence Link</button>
            </div>
        </form>
    </div>
</div>
@endsection
