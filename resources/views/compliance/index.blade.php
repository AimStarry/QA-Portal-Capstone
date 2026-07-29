@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Recommendations & Compliance Tracker</h2>
            <p class="text-xs sm:text-sm text-gray-500">Track documentation audits and compliance tasks. Recommendations are managed as checklists to drive compliance rates.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openAddModal()" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Log Compliance Task
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
            <!-- Search -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="comp-search" oninput="applyFilters()" placeholder="Search title, recommendation, responsible..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>

            <!-- Accrediting Body filter -->
            <div>
                <select id="comp-body" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Accrediting Bodies</option>
                    @foreach($bodies as $body)
                        <option value="{{ $body }}">{{ $body }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Category filter -->
            <div>
                <select id="comp-category" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Area filter -->
            <div>
                <select id="comp-area" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Areas</option>
                    @foreach($areas as $ar)
                        <option value="{{ $ar }}">{{ $ar }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status filter -->
            <div>
                <select id="comp-status" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Statuses</option>
                    <option value="Compliant">Compliant</option>
                    <option value="Non-Compliant">Non-Compliant</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            <!-- Priority filter -->
            <div>
                <select id="comp-priority" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Priorities</option>
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <!-- Unit or Department filter -->
            <div>
                <select id="comp-unit" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Units & Depts</option>
                    @foreach($responsibleUnits as $unit)
                        <option value="{{ $unit }}">{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="text-xs text-gray-400 font-medium">
            Active Filter Results: <span id="visible-count" class="font-bold text-gray-600">{{ $complianceRecords->count() }}</span> items
        </div>
    </div>

    <!-- Tasks Grid -->
    <div id="compliance-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($complianceRecords as $c)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between hover:shadow-md hover:border-hau-maroon/40 transition cursor-pointer animate-fade-slide-up"
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
                 <div class="p-5 space-y-4 flex-1">
                     <div class="flex items-start justify-between gap-2">
                          <div class="flex flex-wrap gap-1">
                              @php
                                  $isAllUnitsTask = str_contains($c->responsible_unit ?? '', 'All Departments') || str_contains($c->responsible_unit ?? '', 'All Units');
                              @endphp
                              @if ($isAllUnitsTask)
                                  <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-150">
                                      All Departments &amp; Units
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

                                      $maxVisible = 4;
                                      $visibleBadges = array_slice($uniqueBadges, 0, $maxVisible);
                                      $hiddenCount = count($uniqueBadges) - count($visibleBadges);
                                      $allLabels = array_map(fn($b) => $b['label'], $uniqueBadges);
                                  @endphp

                                  @foreach($visibleBadges as $b)
                                      @if(isset($b['link']))
                                          <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon hover:underline">
                                              <a href="{{ $b['link'] }}" onclick="event.stopPropagation();">{{ $b['label'] }}</a>
                                          </span>
                                      @elseif($b['type'] === 'unit')
                                          <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700">
                                              {{ $b['label'] }}
                                          </span>
                                      @else
                                          <span title="{{ $b['title'] ?? '' }}" class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                              {{ $b['label'] }}
                                          </span>
                                      @endif
                                  @endforeach

                                  @if($hiddenCount > 0)
                                      <span title="{{ implode(', ', $allLabels) }}" class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200 cursor-help">
                                          +{{ $hiddenCount }} more
                                      </span>
                                  @endif
                              @endif
                              @if ($c->accrediting_body)
                                  <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-hau-gold/15 text-hau-maroon-dark">
                                      {{ $c->accrediting_body }}
                                  </span>
                              @endif
                          </div>
                         
                         <div class="flex items-center gap-1 shrink-0">
                             <!-- Priority Badge -->
                             <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black
                                 @if ($c->priority === 'Critical') bg-rose-100 text-rose-800 border border-rose-200
                                 @elseif ($c->priority === 'High') bg-amber-50 text-amber-800 border border-amber-200
                                 @elseif ($c->priority === 'Low') bg-slate-150 text-slate-700 border border-slate-200
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

                     <!-- 4-Stage Workflow Pipeline Stepper -->
                     @php
                         $stage = $c->workflow_stage ?? 'recommendation_created';
                         $stages = [
                             'recommendation_created' => 0,
                             'action_plan_submitted'  => 1,
                             'admin_reviewing'        => 2,
                             'compliant'              => 3,
                         ];
                         $currentIdx = $stages[$stage] ?? 0;
                     @endphp
                     <div class="flex items-center gap-0 w-full">
                         @php
                             $stepDefs = [
                                 ['label' => 'Recommendation', 'iconSvg' => '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>', 'colors' => ['done' => 'bg-hau-maroon text-white', 'active' => 'bg-hau-maroon/10 text-hau-maroon border border-hau-maroon/30', 'idle' => 'bg-gray-100 text-gray-400']],
                                 ['label' => 'Action Plan', 'iconSvg' => '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 'colors' => ['done' => 'bg-hau-gold text-hau-maroon-dark', 'active' => 'bg-hau-gold/15 text-hau-gold-dark border border-hau-gold/40', 'idle' => 'bg-gray-100 text-gray-400']],
                                 ['label' => 'Admin Review', 'iconSvg' => '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 'colors' => ['done' => 'bg-hau-maroon-dark text-white', 'active' => 'bg-hau-maroon/10 text-hau-maroon-dark border border-hau-maroon/30', 'idle' => 'bg-gray-100 text-gray-400']],
                                 ['label' => 'Compliant', 'iconSvg' => '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>', 'colors' => ['done' => 'bg-emerald-500 text-white', 'active' => 'bg-emerald-50 text-emerald-700 border border-emerald-300', 'idle' => 'bg-gray-100 text-gray-400']],
                             ];
                         @endphp
                         @foreach($stepDefs as $si => $step)
                             @php
                                 if ($si < $currentIdx) $cls = $step['colors']['done'];
                                 elseif ($si === $currentIdx) $cls = $step['colors']['active'];
                                 else $cls = $step['colors']['idle'];
                             @endphp
                             <div class="flex flex-col items-center flex-1 min-w-0">
                                 <div class="flex items-center w-full">
                                     @if($si > 0)
                                         <div class="flex-1 h-px {{ $si <= $currentIdx ? 'bg-hau-maroon/40' : 'bg-gray-200' }}"></div>
                                     @endif
                                     <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-black {{ $cls }} shrink-0">
                                         @if($si < $currentIdx)
                                             <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                         @else
                                             {!! $step['iconSvg'] !!}
                                         @endif
                                     </span>
                                     @if($si < count($stepDefs) - 1)
                                         <div class="flex-1 h-px {{ $si < $currentIdx ? 'bg-hau-maroon/40' : 'bg-gray-200' }}"></div>
                                     @endif
                                 </div>
                                 <span class="text-[8px] font-bold text-center mt-0.5 leading-tight
                                     @if($si < $currentIdx) text-gray-500
                                     @elseif($si === $currentIdx) text-hau-maroon
                                     @else text-gray-350
                                     @endif
                                     ">
                                     {{ $step['label'] }}
                                 </span>
                             </div>
                         @endforeach
                     </div>

                      <!-- Info -->
                      <div class="space-y-2">
                          @if ($c->area || $c->category)
                              <div class="flex flex-wrap gap-1 items-center">
                                  @if($c->area)
                                      @foreach(preg_split('/[,;]+/', $c->area) as $areaTag)
                                          @if(trim($areaTag) !== '')
                                              <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-bold bg-slate-100 text-slate-700 tracking-wider uppercase border border-slate-200/50">{{ trim($areaTag) }}</span>
                                          @endif
                                      @endforeach
                                  @endif
                                  @if($c->category)
                                      @foreach(preg_split('/[,;]+/', $c->category) as $catTag)
                                          @if(trim($catTag) !== '')
                                              <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-50 text-amber-800 border border-amber-200 tracking-wider uppercase leading-none">{{ trim($catTag) }}</span>
                                          @endif
                                      @endforeach
                                  @endif
                              </div>
                          @endif
                          <h4 class="font-bold text-gray-900 text-sm leading-snug truncate" title="{{ $c->title }}">{{ $c->title }}</h4>
                      </div>

                     <!-- Recommendation Checklist -->
                     @if ($c->recommendationItems->count() > 0)
                         <div class="space-y-2" onclick="event.stopPropagation()">
                             <div class="flex items-center justify-between">
                                 <span class="text-[9px] font-bold text-hau-maroon uppercase tracking-wider flex items-center gap-1">
                                     <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                     Recommendations ({{ $c->recommendationItems->where('is_completed', true)->count() }}/{{ $c->recommendationItems->count() }})
                                 </span>
                                 <span class="text-[9px] font-bold text-hau-maroon completion-rate-{{ $c->id }}">{{ $c->recommendationItems->count() > 0 ? round(($c->recommendationItems->where('is_completed', true)->count() / $c->recommendationItems->count()) * 100) : 0 }}%</span>
                             </div>
                             <!-- Completion Progress Bar -->
                             <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                 <div class="completion-bar bg-hau-maroon h-full rounded-full" id="bar-{{ $c->id }}" style="width: {{ $c->recommendationItems->count() > 0 ? round(($c->recommendationItems->where('is_completed', true)->count() / $c->recommendationItems->count()) * 100) : 0 }}%"></div>
                             </div>
                             <!-- Checklist Items -->
                             <div class="space-y-1">
                                 @foreach($c->recommendationItems->take(1) as $item)
                                     <label class="checklist-item flex items-start gap-2 py-1 px-2 rounded-lg cursor-pointer group" data-item-id="{{ $item->id }}" data-record-id="{{ $c->id }}">
                                         <input type="checkbox" class="checklist-checkbox mt-0.5 shrink-0" {{ $item->is_completed ? 'checked' : '' }} onchange="toggleRecommendation({{ $item->id }}, {{ $c->id }})" />
                                         <span class="text-xs text-gray-700 leading-relaxed {{ $item->is_completed ? 'checklist-text-completed' : '' }}" id="reco-text-card-{{ $item->id }}">{{ $item->text }}</span>
                                     </label>
                                 @endforeach
                                 @if($c->recommendationItems->count() > 1)
                                     <div class="text-[10px] text-hau-maroon font-bold pl-2 pt-0.5 flex items-center gap-1 hover:underline cursor-pointer">
                                         <svg class="w-3 h-3 text-hau-maroon shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                         +{{ $c->recommendationItems->count() - 1 }} more recommendation(s)...
                                     </div>
                                 @endif
                             </div>
                         </div>
                     @endif

                     @if ($c->description)
                         <div>
                             <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Task Description:</span>
                             <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed mt-0.5" title="{{ $c->description }}">{{ $c->description }}</p>
                         </div>
                     @endif

                     <!-- Action Plan -->
                     <div class="bg-gray-50 rounded-lg p-3 border border-gray-150 space-y-1">
                         <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block">Action Plan</span>
                         <p class="text-xs text-gray-700 font-medium line-clamp-2 leading-relaxed">
                             {{ $c->action_plan ?? 'No action plan formulated yet.' }}
                         </p>
                     </div>

                     <!-- Document / Evidence Links -->
                     <div class="pt-2 border-t border-gray-100 text-xs space-y-1">
                         @php
                             $hasAnyLink = false;
                         @endphp
                         @if ($c->assignments && $c->assignments->count() > 0)
                             <span class="text-gray-400 font-semibold block text-[10px] uppercase tracking-wider">Evidence Links:</span>
                             @foreach($c->assignments as $ass)
                                 @php
                                     $activeLink = $ass->pending_document_link ?? $ass->document_link;
                                     $targetLabel = $ass->program->program_code ?? ($ass->school_name ? $getSchoolCode($ass->school_name) : ($ass->responsibleUnit->code ?? $ass->responsibleUnit->name ?? 'Unit'));
                                 @endphp
                                 @if($activeLink)
                                     @php $hasAnyLink = true; @endphp
                                     <div class="flex items-center justify-between gap-2 text-[11px]">
                                         <span class="font-bold text-gray-700 truncate max-w-[120px]" title="{{ $targetLabel }}">{{ $targetLabel }}:</span>
                                         <a href="{{ $activeLink }}" target="_blank" onclick="event.stopPropagation();" class="text-hau-maroon hover:underline font-mono font-semibold truncate block max-w-[180px]" title="{{ $activeLink }}">
                                             {{ $ass->pending_document_link ? 'Proposed Link 🔗' : 'Open Link 🔗' }}
                                         </a>
                                     </div>
                                 @endif
                             @endforeach
                         @endif

                         @if(!$hasAnyLink && $c->document_link)
                             <div class="flex items-center justify-between">
                                 <span class="text-gray-400 font-semibold">Evidence Link:</span>
                                 <a href="{{ $c->document_link }}" target="_blank" onclick="event.stopPropagation();" class="text-hau-maroon hover:underline font-mono font-medium truncate block max-w-[200px]" title="{{ $c->document_link }}">
                                     Open Link <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                 </a>
                             </div>
                         @elseif(!$hasAnyLink)
                             <div class="flex items-center justify-between">
                                 <span class="text-gray-400 font-semibold">Evidence Link:</span>
                                 <span class="text-gray-400 font-medium italic">No document attached</span>
                             </div>
                         @endif
                     </div>

                      <div class="flex items-center justify-between text-xs">
                          <span class="text-gray-450 font-semibold">Unit or Department:</span>
                          <span class="font-bold text-gray-700">{{ $c->responsible_unit ?? 'Unassigned' }}</span>
                      </div>
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
                              <span class="whitespace-nowrap">No deadline</span>
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


</div>

<!-- ================= MODAL WINDOWS ================= -->

    <!-- 1. Add Compliance Modal (Accessible to both roles) -->
    <div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full overflow-hidden transform scale-95 transition-all flex flex-col" style="max-width: 960px; max-height: calc(100vh - 40px);">
            <div class="bg-hau-maroon px-5 py-3 text-white flex items-center justify-between border-b-2 border-hau-gold shrink-0">
                <h3 class="text-base font-bold">Log Compliance Item</h3>
                <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('compliance.store') }}" method="POST" class="flex flex-col min-h-0 flex-1">
                @csrf
                <div class="p-4 space-y-3.5 overflow-y-auto flex-1 min-h-0">
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
            <div class="bg-hau-maroon px-5 py-3 text-white flex items-center justify-between border-b-2 border-hau-gold shrink-0">
                <h3 class="text-base font-bold">Edit Compliance Item</h3>
                <button onclick="closeModal('edit-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
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
                                <option value="Pending">Pending Audit</option>
                                <option value="Compliant">Compliant</option>
                                <option value="Non-Compliant">Non-Compliant</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="edit-contact-person" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Person</label>
                            <input type="text" name="contact_person" id="edit-contact-person" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-contact-email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Contact Email</label>
                            <input type="email" name="contact_email" id="edit-contact-email" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="edit-link" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Document Link (Evidence URL)</label>
                            <input type="url" name="document_link" id="edit-link" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>

                    <div>
                        <label for="edit-action_plan" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-0.5">Action Plan</label>
                        <textarea name="action_plan" id="edit-action_plan" rows="2" class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3 flex justify-end gap-3 border-t border-gray-200 shrink-0">
                    <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Update Task</button>
                </div>
            </form>
        </div>
    </div>

@else
    <!-- 3. Propose Update Modal (Unit or Department only, with Action Plan edit support) -->
    <div id="propose-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-md font-bold">Propose Compliance Update</h3>
                <button onclick="closeModal('propose-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
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
                    <button type="button" onclick="closeModal('propose-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow transition">Propose Changes</button>
                </div>
            </form>
        </div>
    </div>
@endif

    <!-- 4. Compliance Item Details Modal (Accessible to both) -->
    <div id="detail-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-base font-bold">Compliance Recommendation Details</h3>
                <button onclick="closeModal('detail-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                
                <!-- Title & Program -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span id="detail-program" class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon"></span>
                        <span id="detail-body" class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-hau-gold/15 text-hau-maroon-dark ml-2"></span>
                        <h4 id="detail-title" class="text-lg font-black text-gray-900 mt-2"></h4>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <span id="detail-priority" class="inline-flex px-2 py-0.5 rounded text-[10px] font-black border"></span>
                        <span id="detail-status" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border"></span>
                    </div>
                </div>

                <!-- Rejection Alerts (If Rejected) -->
                <div id="detail-rejected-alert" class="bg-rose-50 border border-rose-200 rounded-xl p-4 hidden">
                    <h5 class="text-xs font-bold text-rose-800 uppercase tracking-wider mb-1">Rejection Reason from QA Admin</h5>
                    <p id="detail-rejection-reason" class="text-xs text-rose-700 font-medium italic"></p>
                </div>

                <!-- Workflow Timeline -->
                <div id="detail-workflow-tracker" class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-4">
                    <h5 class="text-[10px] font-black text-gray-500 uppercase tracking-wider mb-3">Recommendation Workflow</h5>
                    <div id="detail-workflow-steps" class="space-y-0">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Recommendation Checklist -->
                <div>
                    <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider block flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Recommendations Checklist
                    </span>
                    <div id="detail-recommendation" class="text-sm text-gray-700 leading-relaxed bg-gray-50 p-3 rounded-lg border border-gray-150 mt-1"></div>
                </div>

                <!-- Description -->
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Task Description</span>
                    <p id="detail-description" class="text-xs text-gray-600 mt-1 whitespace-pre-line"></p>
                </div>

                <!-- Action Plan -->
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Action Plan / Action Taken</span>
                    <p id="detail-action-plan" class="text-xs text-gray-700 bg-gray-50 border border-gray-150 p-3 rounded-lg leading-relaxed mt-1 font-medium whitespace-pre-line"></p>
                </div>

                <!-- Submissions & SharePoint Links Breakdown Table -->
                <div class="pt-3 border-t border-gray-150 space-y-2">
                    <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider block flex items-center justify-between">
                        <span>Assigned Target Submissions &amp; SharePoint Evidence Links</span>
                    </span>
                    <div id="detail-assignments-list">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Grid Details -->
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">School / College</span>
                        <span id="detail-school" class="font-semibold text-gray-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Unit or Department</span>
                        <span id="detail-resp" class="font-semibold text-gray-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Contact Person</span>
                        <span id="detail-contact-person" class="font-semibold text-gray-800"></span>
                        <span id="detail-contact-email" class="block text-gray-500 font-mono text-[10px] mt-0.5"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Category / Area</span>
                        <span id="detail-cat-area" class="font-semibold text-gray-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Deadline / Due Date</span>
                        <span id="detail-due" class="font-semibold text-gray-800"></span>
                    </div>
                </div>

                <!-- Evidence Link -->
                <div class="pt-4 border-t border-gray-150 flex items-center justify-between text-xs">
                    <span class="text-gray-500 font-bold">Evidence Document Link:</span>
                    <div id="detail-link-container">
                        <!-- Clickable link injected here -->
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                <button type="button" onclick="closeModal('detail-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Close Details</button>
                <div id="detail-action-buttons">
                    <!-- Action buttons dynamically loaded here based on role -->
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Categories & Labs Modal -->
    <div id="manage-labs-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full overflow-hidden transform scale-95 transition-all flex flex-col" style="max-width: 540px; max-height: calc(100vh - 80px);">
            <div class="bg-hau-maroon px-5 py-3 text-white flex items-center justify-between border-b-2 border-hau-gold shrink-0">
                <h3 class="text-base font-bold">Manage Categories &amp; Departments</h3>
                <button onclick="closeModal('manage-labs-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 bg-gray-50 px-5 shrink-0">
                <button type="button" onclick="switchManageLabsTab('list')" id="manage-labs-tab-list" class="px-3 py-3 text-xs font-bold text-hau-maroon border-b-2 border-hau-maroon focus:outline-none transition">
                    Labs &amp; Categories
                </button>
                <button type="button" onclick="switchManageLabsTab('add')" id="manage-labs-tab-add" class="px-3 py-3 text-xs font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 focus:outline-none transition">
                    Add Lab / Category
                </button>
                <button type="button" onclick="switchManageLabsTab('depts')" id="manage-labs-tab-depts" class="px-3 py-3 text-xs font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 focus:outline-none transition">
                    Departments
                </button>
            </div>

            <!-- Panel Container -->
            <div class="flex-1 flex flex-col overflow-hidden min-h-0 bg-white">

                <!-- Panel: List (Labs) -->
                <div id="manage-labs-panel-list" class="p-5 flex flex-col min-h-0 overflow-hidden flex-1">
                    <div class="flex justify-between items-center border-b border-gray-150 pb-1 mb-3 shrink-0">
                        <h4 class="text-[11px] font-black text-hau-maroon uppercase tracking-wider">Existing Labs &amp; Categories</h4>
                        <span id="manage-labs-count" class="text-[10px] font-bold bg-hau-maroon/5 text-hau-maroon px-2 py-0.5 rounded font-mono">0 Total</span>
                    </div>
                    <div class="mb-3 shrink-0">
                        <input type="text" id="manage-labs-search" oninput="filterManageLabsList()" placeholder="Filter categories..." class="block w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div class="flex-1 overflow-y-auto min-h-0 pr-1" id="manage-labs-list-container">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Panel: Add Lab -->
                <div id="manage-labs-panel-add" class="p-5 space-y-4 flex-1 hidden overflow-y-auto">
                    <div class="border-b border-gray-150 pb-1">
                        <h4 class="text-[11px] font-black text-hau-maroon uppercase tracking-wider">Add Lab / Category</h4>
                    </div>
                    <form id="create-lab-form" onsubmit="saveNewCategoryLab(event)" class="space-y-4">
                        @csrf
                        <div>
                            <label for="new-lab-name" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1">Lab / Category Name</label>
                            <input type="text" name="name" id="new-lab-name" required placeholder="e.g. Cisco Lab 1" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                        <div>
                            <label for="new-lab-unit" class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1">Parent Department / Unit</label>
                            <select name="responsible_unit_id" id="new-lab-unit" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="">Select Parent Unit</option>
                                @foreach($dbResponsibleUnits->whereNull('parent_unit_id') as $school)
                                    @if($school->children->count() > 0)
                                        <optgroup label="{{ $school->name }}">
                                            @foreach($school->children->sortBy('name') as $dept)
                                                <option value="{{ $dept->responsible_unit_id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        <option value="{{ $school->responsible_unit_id }}">{{ $school->name }}{{ $school->code ? ' ('.$school->code.')' : '' }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                            Add Lab / Category
                        </button>
                    </form>
                </div>

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

            <div class="bg-gray-50 px-5 py-3 flex justify-end border-t border-gray-200 shrink-0">
                <button type="button" onclick="closeModal('manage-labs-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Done</button>
            </div>
        </div>
    </div>


<!-- ================= JAVASCRIPT ================= -->
<script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    let manageLaboratoriesData = [];

    function openManageLabsModal() {
        openModal('manage-labs-modal');
        switchManageLabsTab('list');
    }

    function switchManageLabsTab(tab) {
        const listTab   = document.getElementById('manage-labs-tab-list');
        const addTab    = document.getElementById('manage-labs-tab-add');
        const deptsTab  = document.getElementById('manage-labs-tab-depts');
        const listPanel  = document.getElementById('manage-labs-panel-list');
        const addPanel   = document.getElementById('manage-labs-panel-add');
        const deptsPanel = document.getElementById('manage-labs-panel-depts');

        const activeClass   = 'px-3 py-3 text-xs font-bold text-hau-maroon border-b-2 border-hau-maroon focus:outline-none transition';
        const inactiveClass = 'px-3 py-3 text-xs font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 focus:outline-none transition';

        // Reset all
        [listTab, addTab, deptsTab].forEach(t => { if(t) t.className = inactiveClass; });
        [listPanel, addPanel, deptsPanel].forEach(p => { if(p) p.classList.add('hidden'); });

        if (tab === 'list') {
            listTab.className = activeClass;
            listPanel.classList.remove('hidden');
            loadManageLabsList();
        } else if (tab === 'add') {
            addTab.className = activeClass;
            addPanel.classList.remove('hidden');
        } else if (tab === 'depts') {
            deptsTab.className = activeClass;
            deptsPanel.classList.remove('hidden');
            loadDeptsList();
        }
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

    function loadManageLabsList() {
        const container = document.getElementById('manage-labs-list-container');
        container.innerHTML = '<div class="text-center py-6 text-xs text-gray-400">Loading categories...</div>';

        fetch("{{ route('admin.categories.index') }}", {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            manageLaboratoriesData = data.laboratories || [];
            
            // Sync current state into responsibleUnitsMap
            if (data.responsibleUnits) {
                data.responsibleUnits.forEach(unit => {
                    responsibleUnitsMap[unit.responsible_unit_id] = unit;
                });
            }

            renderManageLabsList(manageLaboratoriesData);
        })
        .catch(err => {
            container.innerHTML = '<div class="text-center py-6 text-xs text-rose-500">Failed to load categories.</div>';
        });
    }

    function renderManageLabsList(labs) {
        const container = document.getElementById('manage-labs-list-container');
        const countEl = document.getElementById('manage-labs-count');
        countEl.textContent = labs.length + ' Total';

        if (labs.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-xs text-gray-400">No categories found. Add one to begin.</div>';
            return;
        }

        let html = '<div class="space-y-2">';
        labs.forEach(lab => {
            const unitName = lab.responsible_unit ? lab.responsible_unit.name : 'Unassigned';
            html += `
                <div class="bg-gray-50 rounded-xl p-3 border border-gray-150 flex items-center justify-between gap-3 text-xs lab-item-row" data-search-term="${(lab.name + ' ' + unitName).toLowerCase()}">
                    <div>
                        <div class="font-bold text-gray-900">${escapeHtml(lab.name)}</div>
                        <div class="text-[10px] text-gray-500 font-semibold mt-0.5">Parent: ${escapeHtml(unitName)}</div>
                    </div>
                    <button type="button" onclick="deleteCategoryLab(${lab.laboratory_id}, ${lab.responsible_unit_id})" class="p-1 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Delete Category">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function saveNewCategoryLab(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const formData = new FormData(form);

        fetch("{{ route('admin.categories.store') }}", {
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
            if (!res.ok) throw new Error(data.message || 'Validation error');
            return data;
        })
        .then(data => {
            alert('Category / Laboratory added successfully!');
            form.reset();
            
            // Sync with local maps
            const newLab = data.data;
            if (newLab && responsibleUnitsMap[newLab.responsible_unit_id]) {
                if (!responsibleUnitsMap[newLab.responsible_unit_id].laboratories) {
                    responsibleUnitsMap[newLab.responsible_unit_id].laboratories = [];
                }
                responsibleUnitsMap[newLab.responsible_unit_id].laboratories.push(newLab);
            }

            // Sync add/edit active dropdown selection options
            const currentAddUnit = document.getElementById('add-resp').value;
            if (currentAddUnit) updateLaboratories(currentAddUnit, 'add-laboratory_id');
            const currentEditUnit = document.getElementById('edit-resp').value;
            if (currentEditUnit) updateLaboratories(currentEditUnit, 'edit-laboratory_id');

            switchManageLabsTab('list');
        })
        .catch(err => {
            alert('Error: ' + err.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Lab / Category';
        });
    }

    function deleteCategoryLab(id, unitId) {
        if (!confirm('Are you sure you want to delete this category/laboratory? This will unlink it from all associated compliance items.')) {
            return;
        }

        fetch(`/admin/manage-categories/${id}`, {
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
        .then(data => {
            alert('Category / Laboratory deleted successfully.');
            
            // Remove from local responsibleUnitsMap
            if (responsibleUnitsMap[unitId] && responsibleUnitsMap[unitId].laboratories) {
                responsibleUnitsMap[unitId].laboratories = responsibleUnitsMap[unitId].laboratories.filter(lab => Number(lab.laboratory_id) !== Number(id));
            }

            // Sync active dropdown selection options
            const currentAddUnit = document.getElementById('add-resp').value;
            if (currentAddUnit) updateLaboratories(currentAddUnit, 'add-laboratory_id');
            const currentEditUnit = document.getElementById('edit-resp').value;
            if (currentEditUnit) updateLaboratories(currentEditUnit, 'edit-laboratory_id');

            loadManageLabsList();
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    }

    function filterManageLabsList() {
        const query = document.getElementById('manage-labs-search').value.toLowerCase();
        const rows = document.querySelectorAll('#manage-labs-list-container .lab-item-row');

        rows.forEach(row => {
            const term = row.getAttribute('data-search-term');
            if (!query || term.includes(query)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
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

    // ── Toggle Recommendation Checklist Item (AJAX) ──────────────────────
    function toggleRecommendation(itemId, recordId) {
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
                // Update text styling on card and in modal
                const textElCard = document.getElementById('reco-text-card-' + itemId);
                if (textElCard) {
                    if (data.is_completed) {
                        textElCard.classList.add('checklist-text-completed');
                    } else {
                        textElCard.classList.remove('checklist-text-completed');
                    }
                }
                const textElModal = document.getElementById('reco-text-modal-' + itemId);
                if (textElModal) {
                    if (data.is_completed) {
                        textElModal.classList.add('line-through', 'text-gray-400');
                    } else {
                        textElModal.classList.remove('line-through', 'text-gray-400');
                    }
                }

                // Sync checkbox state on card
                const cbCard = document.querySelector('label[data-item-id="' + itemId + '"] input[type="checkbox"]');
                if (cbCard) cbCard.checked = data.is_completed;

                // Sync checkbox state in modal
                const cbModal = document.querySelector('#detail-recommendation input[onchange*="' + itemId + '"]');
                if (cbModal) cbModal.checked = data.is_completed;

                // Update progress bar
                const bar = document.getElementById('bar-' + recordId);
                if (bar) bar.style.width = data.completion_rate + '%';
                // Update rate text
                const rateEl = document.querySelector('.completion-rate-' + recordId);
                if (rateEl) rateEl.innerText = data.completion_rate + '%';
            }
        })
        .catch(err => console.error('Toggle failed:', err));
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
            let html = '<div class="space-y-1.5">';
            recommendations.forEach(function(item) {
                const completed = item.is_completed;

                html += '<div class="py-2 border-b border-gray-50 last:border-0">';
                html += '<label class="flex items-start gap-2 cursor-pointer group" onclick="event.stopPropagation();">';
                html += '<input type="checkbox" ' + (completed ? 'checked' : '') + 
                        ' onchange="toggleRecommendation(' + item.id + ', ' + id + ')"' +
                        ' class="w-4 h-4 rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon mt-0.5 cursor-pointer shrink-0" />';
                html += '<span id="reco-text-modal-' + item.id + '" class="text-sm flex-1 ' + (completed ? 'line-through text-gray-400' : 'text-gray-700') + '">' + escapeHtml(item.text) + '</span>';
                html += '</label>';
                html += '</div>';
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

        // Render sub-assignments breakdown table
        const assignmentsList = document.getElementById('detail-assignments-list');
        let assignments = [];
        try { assignments = JSON.parse(card.getAttribute('data-assignments') || '[]'); } catch(e) {}
        _currentDetailAssignments = assignments;

        if (assignmentsList) {
            if (assignments.length > 0) {
                let html = '<div class="overflow-x-auto border border-gray-200 rounded-lg"><table class="w-full text-left text-xs">';
                html += '<thead class="bg-gray-50 text-[10px] uppercase font-bold text-gray-500 border-b border-gray-200"><tr>';
                html += '<th class="px-3 py-2">Assigned Target</th>';
                html += '<th class="px-3 py-2">Status</th>';
                html += '<th class="px-3 py-2">SharePoint Link</th>';
                html += '<th class="px-3 py-2 text-right">Actions</th>';
                html += '</tr></thead><tbody class="divide-y divide-gray-150 bg-white">';

                assignments.forEach(function(a) {
                    const targetName = a.program_code ? (a.program_code + ' — ' + (a.program_name || '')) : (a.school_name ? a.school_name : (a.unit_name || 'Department'));
                    const activeLink = a.pending_document_link || a.document_link;

                    let statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ';
                    if (a.status === 'Compliant') statusBadge += 'bg-emerald-50 text-emerald-700 border border-emerald-200">Compliant</span>';
                    else if (a.status === 'Non-Compliant') statusBadge += 'bg-rose-50 text-rose-700 border border-rose-200">Non-Compliant</span>';
                    else statusBadge += 'bg-amber-50 text-amber-700 border border-amber-200">Pending</span>';

                    let linkHtml = '—';
                    if (activeLink) {
                        const linkLabel = a.pending_document_link ? 'Proposed Link 🔗' : 'Evidence Link 🔗';
                        linkHtml = '<a href="' + escapeHtml(activeLink) + '" target="_blank" class="text-hau-maroon hover:underline font-mono font-semibold truncate block max-w-[170px]" title="' + escapeHtml(activeLink) + '">' + linkLabel + '</a>';
                    }

                    let actionHtml = '';
                    if ("{{ $role }}" === 'QA Admin' && a.approval_state === 'Pending Approval') {
                        actionHtml += '<form method="POST" action="/compliance/' + id + '/approve" class="inline mr-1">' +
                                      '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                      '<input type="hidden" name="assignment_id" value="' + a.id + '">' +
                                      '<button type="submit" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[10px]">Approve</button></form>';
                    } else if ("{{ $role }}" !== 'QA Admin') {
                        actionHtml += '<button type="button" data-assignment-id="' + a.id + '" onclick="openSubmitLinkModal(this)" class="px-2 py-0.5 bg-hau-maroon text-white font-bold rounded text-[10px] hover:bg-hau-maroon-dark">Submit Link</button>';
                    }

                    html += '<tr>';
                    html += '<td class="px-3 py-2 font-semibold text-gray-800">' + escapeHtml(targetName) + '</td>';
                    html += '<td class="px-3 py-2">' + statusBadge + '</td>';
                    html += '<td class="px-3 py-2">' + linkHtml + '</td>';
                    html += '<td class="px-3 py-2 text-right">' + actionHtml + '</td>';
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

    function openAddModal() {
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

    function applyFilters() {
        const search = document.getElementById('comp-search').value.toLowerCase();
        const body = document.getElementById('comp-body').value.toLowerCase();
        const category = document.getElementById('comp-category').value.toLowerCase();
        const area = document.getElementById('comp-area').value.toLowerCase();
        const status = document.getElementById('comp-status').value;
        const priority = document.getElementById('comp-priority').value.toLowerCase();
        const unit = document.getElementById('comp-unit').value.toLowerCase();

        const cards = document.querySelectorAll('#compliance-grid > div[data-id]');
        let count = 0;

        cards.forEach(card => {
            const code = card.getAttribute('data-program-code').toLowerCase();
            const cardBody = (card.getAttribute('data-body') || '').toLowerCase();
            const cardSchool = (card.getAttribute('data-school') || '').toLowerCase();
            const cardReco = (card.getAttribute('data-recommendation') || '').toLowerCase();
            const cardCategory = (card.getAttribute('data-category') || '').toLowerCase();
            const cardArea = (card.getAttribute('data-area') || '').toLowerCase();
            const cardAction = (card.getAttribute('data-action-plan') || '').toLowerCase();
            const title = card.getAttribute('data-title').toLowerCase();
            const desc = card.getAttribute('data-desc').toLowerCase();
            const cardStatus = card.getAttribute('data-status');
            const cardPriority = (card.getAttribute('data-priority') || '').toLowerCase();
            const resp = card.getAttribute('data-resp').toLowerCase();

            const matchesSearch = !search || 
                                  code.includes(search) || 
                                  title.includes(search) || 
                                  desc.includes(search) || 
                                  cardBody.includes(search) ||
                                  cardSchool.includes(search) ||
                                  cardReco.includes(search) ||
                                  cardCategory.includes(search) ||
                                  cardArea.includes(search) ||
                                  cardAction.includes(search) ||
                                  resp.includes(search);
            
            const matchesBody = !body || cardBody === body;
            const matchesCategory = !category || cardCategory.includes(category);
            const matchesArea = !area || cardArea.includes(area);
            const matchesStatus = !status || cardStatus === status;
            const matchesPriority = !priority || cardPriority === priority;
            const matchesUnit = !unit || resp === unit;

            if (matchesSearch && matchesBody && matchesCategory && matchesArea && matchesStatus && matchesPriority && matchesUnit) {
                card.classList.remove('hidden');
                count++;
            } else {
                card.classList.add('hidden');
            }
        });

        const visibleCountSpan = document.getElementById('visible-count');
        if (visibleCountSpan) visibleCountSpan.innerText = count;

        const empty = document.getElementById('empty-row');
        const noMatches = document.getElementById('no-matches-row');

        if (count === 0) {
            if (cards.length === 0) {
                if (empty) empty.classList.remove('hidden');
                if (noMatches) noMatches.classList.add('hidden');
            } else {
                if (empty) empty.classList.add('hidden');
                if (noMatches) noMatches.classList.remove('hidden');
            }
        } else {
            if (empty) empty.classList.add('hidden');
            if (noMatches) noMatches.classList.add('hidden');
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
                    const schoolSelect = document.getElementById('add-school');
                    if (schoolSelect) {
                        for (let i = 0; i < schoolSelect.options.length; i++) {
                            if (schoolSelect.options[i].value === unit.college.name) {
                                schoolSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }
            } else if (isEditUnit) {
                const unitId = target.value;
                resolveContact(unitId, 'edit-contact-person', 'edit-contact-email');

                const unit = responsibleUnitsMap[unitId];
                if (unit && unit.college) {
                    const schoolSelect = document.getElementById('edit-school');
                    if (schoolSelect) {
                        for (let i = 0; i < schoolSelect.options.length; i++) {
                            if (schoolSelect.options[i].value === unit.college.name) {
                                schoolSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
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

    function filterProgramsBySchool(programSelectId, schoolName) {
        const programSelect = document.getElementById(programSelectId);
        if (!programSelect) return;

        const currentVal = programSelect.value;
        let hasVisible = false;

        Array.from(programSelect.options).forEach(opt => {
            if (opt.value === '') {
                // Always show the placeholder
                opt.style.display = '';
                return;
            }
            const college = opt.getAttribute('data-college') || '';
            const matches = !schoolName || college === schoolName;
            opt.style.display = matches ? '' : 'none';
            if (matches) hasVisible = true;
        });

        // If current selection no longer visible, reset to placeholder
        const currentOpt = programSelect.options[programSelect.selectedIndex];
        if (currentOpt && currentOpt.value && currentOpt.style.display === 'none') {
            programSelect.value = '';
        }
    }

    function setupAutoSchoolPopulate() {
        // Program → auto-fill School
        const addProgram = document.getElementById('add-program_id');
        if (addProgram) {
            addProgram.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const collegeName = selectedOption.getAttribute('data-college');
                if (collegeName) {
                    const schoolSelect = document.getElementById('add-school');
                    if (schoolSelect) {
                        for (let i = 0; i < schoolSelect.options.length; i++) {
                            if (schoolSelect.options[i].value === collegeName) {
                                schoolSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }
            });
        }

        const editProgram = document.getElementById('edit-program_id');
        if (editProgram) {
            editProgram.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const collegeName = selectedOption.getAttribute('data-college');
                if (collegeName) {
                    const schoolSelect = document.getElementById('edit-school');
                    if (schoolSelect) {
                        for (let i = 0; i < schoolSelect.options.length; i++) {
                            if (schoolSelect.options[i].value === collegeName) {
                                schoolSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }
            });
        }

        // School → filter Program list
        const addSchool = document.getElementById('add-school');
        if (addSchool) {
            addSchool.addEventListener('change', function() {
                filterProgramsBySchool('add-program_id', this.value);
            });
            // Apply immediately in case school is pre-filled
            filterProgramsBySchool('add-program_id', addSchool.value);
        }

        const editSchool = document.getElementById('edit-school');
        if (editSchool) {
            editSchool.addEventListener('change', function() {
                filterProgramsBySchool('edit-program_id', this.value);
            });
        }
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
        if (parent && parent.children.length > 1) {
            row.remove();
        } else {
            const select = row.querySelector('select');
            if (select) select.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupAutoContactPopulate();
        setupAutoSchoolPopulate();
        setupAccreditingBodyAreas();
    });
</script>

<!-- ================= REJECTION REASON MODAL ================= -->
<div id="reject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon-dark px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-md font-bold text-white">Reject Compliance Update</h3>
            <button onclick="closeRejectModal()" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
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
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow transition">Reject Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
