@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 pb-5">
        <div class="flex items-start gap-4">
            @if($program->college && $program->college->logo)
                <img src="{{ asset('storage/' . $program->college->logo) }}" alt="School Logo" class="w-16 h-16 rounded-2xl object-contain border border-gray-200 p-1 bg-white flex-shrink-0 shadow-2xs" />
            @endif
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-sm font-bold font-mono bg-hau-maroon text-white border border-hau-gold/30">
                        {{ $program->program_code }}
                    </span>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $program->program_level }} Program</span>
                    @if(!$program->is_accreditable)
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black bg-gray-100 text-gray-500 border border-gray-200 uppercase tracking-wide">Non-Accreditable</span>
                    @endif
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5">{{ $program->program_name }}</h2>
                @if($program->former_name)
                    <p class="text-[10px] text-gray-400 italic font-normal">formerly: {{ $program->former_name }}</p>
                @endif
            <p class="text-xs sm:text-sm text-gray-500 font-medium">
                School / College: <span class="text-gray-700 font-semibold">{{ $program->college->name ?? 'Unassigned' }}</span>
                @if($program->college && $program->college->former_name)
                    <span class="text-[10px] text-gray-400 italic font-normal block sm:inline sm:ml-1">(formerly: {{ $program->college->former_name }})</span>
                @endif
                @if($program->department)
                    <span class="text-gray-300 mx-1.5 hidden sm:inline">&bull;</span>
                    <span class="block sm:inline">Department / Committee: <span class="text-gray-700 font-semibold">{{ $program->department }}</span></span>
                @endif
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
            @if ($role === 'QA Admin')
                <form action="{{ route('programs.toggle-accreditable', $program->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition shadow-2xs">
                        @if($program->is_accreditable)
                            Exclude from Accreditation Summary
                        @else
                            Include in Accreditation Summary
                        @endif
                    </button>
                </form>
            @endif
            <a href="{{ route('programs.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                &larr; Back to Directory
            </a>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1: Accreditation status -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Accreditation Status</p>
            <p class="text-lg font-bold text-gray-900 mt-1 truncate">
                {{ $activeAccreditation ? $activeAccreditation->level_or_tier : 'No Active Accreditation' }}
            </p>
            <span class="text-[10px] text-gray-400 font-semibold block mt-1">
                {{ $activeAccreditation ? ($activeAccreditation->expiry_date ? 'Expires: ' . $activeAccreditation->expiry_date->format('M Y') : 'No expiry set') : 'Evaluation pending' }}
            </span>
        </div>

        <!-- Card 2: Compliance rate -->
        @php
            $complianceRate = $totalCompliance > 0 ? round(($compliantCount / $totalCompliance) * 100) : 100;
        @endphp
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-emerald-500">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Compliance Checklist Rate</p>
            <p class="text-2xl font-mono font-bold text-emerald-600 mt-1">{{ $complianceRate }}%</p>
            <span class="text-[10px] text-gray-400 font-semibold block mt-1">
                {{ $compliantCount }} of {{ $totalCompliance }} items compliant
            </span>
        </div>

        <!-- Card 3: Active Risks -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-rose-500">
            <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Quality Risk Items</p>
            <p class="text-2xl font-mono font-bold text-rose-700 mt-1">{{ $activeRisksCount }}</p>
            <span class="text-[10px] text-gray-400 font-semibold block mt-1">
                Unmitigated threats registered
            </span>
        </div>

        <!-- Card 4: Cumulative Graduates -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-gray-400">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Total Recorded Graduates</p>
            <p class="text-2xl font-mono font-bold text-gray-900 mt-1">{{ number_format($cumulativeGraduates) }}</p>
            <span class="text-[10px] text-gray-400 font-semibold block mt-1">
                Cumulative across registered semesters
            </span>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
        <!-- Tabs Header -->
        <div class="bg-gray-50 border-b border-gray-200 flex flex-wrap">
            <button onclick="switchTab('accreditations')" id="tab-btn-accreditations" class="tab-btn px-6 py-4 text-sm font-bold border-b-2 border-hau-maroon text-hau-maroon hover:bg-gray-100 transition focus:outline-none">
                Accreditation History ({{ $program->accreditations->count() }})
            </button>
            <button onclick="switchTab('compliance')" id="tab-btn-compliance" class="tab-btn px-6 py-4 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-hau-maroon hover:bg-gray-100 transition focus:outline-none">
                Recommendations & Compliance ({{ $program->complianceRecords->count() }})
            </button>
            <button onclick="switchTab('risks')" id="tab-btn-risks" class="tab-btn px-6 py-4 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-hau-maroon hover:bg-gray-100 transition focus:outline-none">
                Quality Risk Monitor ({{ $program->riskItems->count() }})
            </button>
            <button onclick="switchTab('graduates')" id="tab-btn-graduates" class="tab-btn px-6 py-4 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-hau-maroon hover:bg-gray-100 transition focus:outline-none">
                Graduate Records ({{ $program->graduateRecords->count() }})
            </button>
        </div>

        <!-- Tab contents -->
        <div class="p-6">
            <!-- 1. Accreditation History Tab -->
            <div id="tab-accreditations" class="tab-pane space-y-4">
                <h3 class="text-base font-bold text-gray-900">Accrediting Visits & Statuses</h3>
                <div class="overflow-x-auto border border-gray-150 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Accrediting Body</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Level/Tier</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Last Visit</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider font-mono">Expiry</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white text-sm">
                            @forelse ($program->accreditations as $a)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ $a->accrediting_body }}</td>
                                    <td class="px-6 py-4 text-xs font-semibold text-gray-600">{{ $a->type }}</td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">{{ $a->level_or_tier ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-500 font-mono">{{ $a->last_visit ? $a->last_visit->format('M d, Y') : '—' }}</td>
                                    <td class="px-6 py-4 text-gray-500 font-mono">{{ $a->expiry_date ? $a->expiry_date->format('M d, Y') : '—' }}</td>
                                    <td class="px-6 py-4 text-xs font-semibold">
                                        <span class="inline-flex px-2 py-0.5 rounded-full
                                            @if ($a->status == 'Active') bg-emerald-50 text-emerald-700 border border-emerald-100
                                            @elseif ($a->status == 'Expiring Soon') bg-amber-50 text-amber-700 border border-amber-100
                                            @elseif ($a->status == 'Expired') bg-rose-50 text-rose-700 border border-rose-100
                                            @else bg-gray-50 text-gray-500 border border-gray-150
                                            @endif">
                                            {{ $a->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-400 py-8 text-xs italic bg-white">No accreditation visits registered for this program.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Recommendations & Compliance Tab -->
            <div id="tab-compliance" class="tab-pane hidden space-y-4">
                <h3 class="text-base font-bold text-gray-900">Accreditation Recommendations & Compliance Checklist</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @forelse ($program->complianceRecords as $c)
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-2xs hover:shadow-xs transition p-5 space-y-4 flex flex-col justify-between">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-hau-gold/15 text-hau-maroon-dark">
                                        {{ $c->accrediting_body ?? 'Accreditor' }}
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                        @if ($c->status == 'Compliant') bg-emerald-50 text-emerald-700 border border-emerald-100
                                        @elseif ($c->status == 'Non-Compliant') bg-rose-50 text-rose-700 border border-rose-100
                                        @else bg-gray-50 text-gray-600 border border-gray-150
                                        @endif">
                                        {{ $c->status }}
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <h4 class="font-bold text-gray-900 text-sm leading-snug truncate" title="{{ $c->title }}">{{ $c->title }}</h4>
                                    
                                    @if ($c->recommendation)
                                        <div class="mt-2 text-xs">
                                            <span class="text-[9px] font-bold text-hau-maroon uppercase tracking-wider block">Accreditor Recommendation:</span>
                                            <p class="italic text-gray-600 mt-0.5 font-medium">{{ $c->recommendation }}</p>
                                        </div>
                                    @endif

                                    @if ($c->description)
                                        <div class="mt-2 text-xs">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Task Description:</span>
                                            <p class="text-gray-500 mt-0.5">{{ $c->description }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="bg-gray-50 border border-gray-150 rounded-lg p-3 space-y-1">
                                    <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block">Action Plan</span>
                                    <p class="text-xs text-gray-700 font-medium line-clamp-2 leading-relaxed">
                                        {{ $c->action_plan ?? 'No action plan formulated yet.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 space-y-2 text-xs text-gray-500">
                                <div class="flex items-center justify-between">
                                    <span>Evidence File:</span>
                                    @if ($c->document_link)
                                        <a href="{{ $c->document_link }}" target="_blank" class="text-blue-600 hover:underline font-mono font-medium truncate block max-w-[150px]" title="{{ $c->document_link }}">
                                            Open Link &rarr;
                                        </a>
                                    @else
                                        <span class="italic text-gray-400">No document</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Unit or Department:</span>
                                    <span class="font-bold text-gray-700">{{ $c->responsible_unit ?? 'Unassigned' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Due Date:</span>
                                    <span class="font-mono text-gray-700 font-semibold">{{ $c->due_date ? $c->due_date->format('M d, Y') : 'None' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-400 py-8 text-xs italic bg-white rounded-xl border border-gray-150">No compliance items logged.</div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Risks Tab -->
            <div id="tab-risks" class="tab-pane hidden space-y-4">
                <h3 class="text-base font-bold text-gray-900">Academic & Administrative Quality Risks</h3>
                <div class="overflow-x-auto border border-gray-150 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Threat / Risk Description</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Likelihood</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Impact</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mitigation Plan</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white text-sm text-gray-700">
                            @forelse ($program->riskItems as $r)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-medium text-gray-900 max-w-sm truncate" title="{{ $r->description }}">{{ $r->description }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono
                                            @if ($r->likelihood == 'High') bg-rose-50 text-rose-700 border border-rose-100
                                            @elseif ($r->likelihood == 'Medium') bg-amber-50 text-amber-700 border border-amber-100
                                            @else bg-emerald-50 text-emerald-700 border border-emerald-100
                                            @endif">
                                            {{ $r->likelihood }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono
                                            @if ($r->impact == 'High') bg-rose-50 text-rose-700 border border-rose-100
                                            @elseif ($r->impact == 'Medium') bg-amber-50 text-amber-700 border border-amber-100
                                            @else bg-emerald-50 text-emerald-700 border border-emerald-100
                                            @endif">
                                            {{ $r->impact }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs max-w-sm truncate" title="{{ $r->mitigation_plan }}">{{ $r->mitigation_plan ?? '—' }}</td>
                                    <td class="px-6 py-4 text-center text-xs font-semibold">
                                        @if ($r->status == 'Identified')
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-100">Identified</span>
                                        @elseif ($r->status == 'Monitoring')
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">Monitoring</span>
                                        @else
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">Mitigated</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8 text-xs italic bg-white">No risks registered for this program.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Graduate Records Tab -->
            <div id="tab-graduates" class="tab-pane hidden space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Graduates Historical Records</h3>
                    @if ($role === 'QA Admin')
                        <button onclick="openAddGraduateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-hau-maroon border border-transparent text-xs font-semibold rounded-lg text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Log Graduates Count
                        </button>
                    @endif
                </div>

                @if (count($graduateCounts) > 0)
                    <!-- Graduates Line Chart Trend -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div class="h-64 relative">
                            <canvas id="graduatesChart"></canvas>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto border border-gray-150 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">School Year</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Term</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Number of Graduates</th>
                                @if ($role === 'QA Admin')
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white text-sm text-gray-700">
                            @forelse ($program->graduateRecords as $g)
                                <tr class="hover:bg-gray-50/50 transition duration-100"
                                    data-id="{{ $g->id }}"
                                    data-program-id="{{ $g->program_id }}"
                                    data-sy="{{ $g->school_year }}"
                                    data-term="{{ $g->term }}"
                                    data-count="{{ $g->graduates_count }}">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-900">{{ $g->school_year }}</td>
                                    <td class="px-6 py-4">{{ $g->term }}</td>
                                    <td class="px-6 py-4 text-center font-bold font-mono text-gray-900">{{ number_format($g->graduates_count) }}</td>
                                    @if ($role === 'QA Admin')
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick="openEditGraduateModal(this.closest('tr'))" class="p-1 text-gray-550 hover:text-hau-maroon hover:bg-gray-100 rounded transition" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                    </svg>
                                                </button>
                                                <form action="{{ route('graduates.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this graduates count record?')" class="inline" onclick="event.stopPropagation();">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-gray-555 hover:text-rose-600 hover:bg-gray-100 rounded transition" title="Delete">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $role === 'QA Admin' ? 4 : 3 }}" class="text-center text-gray-400 py-8 text-xs italic bg-white">No graduates data registered for this program.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function switchTab(tabId) {
        // Hide all panes
        const panes = document.querySelectorAll('.tab-pane');
        panes.forEach(pane => pane.classList.add('hidden'));

        // Show targets pane
        document.getElementById('tab-' + tabId).classList.remove('hidden');

        // Reset all buttons style
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.classList.remove('border-b-2', 'border-hau-maroon', 'text-hau-maroon');
            btn.classList.add('border-transparent', 'text-gray-500');
        });

        // Set active button style
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('border-b-2', 'border-hau-maroon', 'text-hau-maroon');
    }

    // Initialize graduates chart if records exist
    @if (count($graduateCounts) > 0)
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('graduatesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($graduateLabels),
                    datasets: [{
                        label: 'Graduates Count',
                        data: @json($graduateCounts),
                        borderColor: '#800000', // HAU Maroon
                        backgroundColor: 'rgba(128, 0, 0, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#d4af37', // HAU Gold
                        pointBorderColor: '#800000',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            boxPadding: 6,
                            cornerRadius: 8,
                            titleFont: { weight: 'bold' }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                font: {
                                    family: 'Instrument Sans, sans-serif',
                                    size: 10
                                },
                                color: '#64748b'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Instrument Sans, sans-serif',
                                    size: 10
                                },
                                color: '#64748b'
                            }
                        }
                    }
                }
            });
        });
    @endif

    // Modal Control Functions
    function openGraduateModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeGraduateModal(id) {
        const modal = document.getElementById(id);
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 100);
    }

    function openAddGraduateModal() {
        openGraduateModal('add-graduate-modal');
    }

    function openEditGraduateModal(row) {
        const id = row.getAttribute('data-id');
        const sy = row.getAttribute('data-sy');
        const term = row.getAttribute('data-term');
        const count = row.getAttribute('data-count');

        document.getElementById('edit-sy').value = sy;
        document.getElementById('edit-term').value = term;
        document.getElementById('edit-count').value = count;

        document.getElementById('edit-graduate-form').action = `/graduates/${id}`;

        openGraduateModal('edit-graduate-modal');
    }
</script>

<!-- Log Graduate Modal -->
<div id="add-graduate-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-md font-bold">Log Graduates Count</h3>
            <button onclick="closeGraduateModal('add-graduate-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('graduates.store') }}" method="POST">
            @csrf
            <input type="hidden" name="program_id" value="{{ $program->id }}">
            <div class="p-6 space-y-4">
                <div>
                    <label for="add-sy" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School Year</label>
                    <input type="text" name="school_year" id="add-sy" required placeholder="e.g. 2025-2026" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-term" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Term</label>
                    <select name="term" id="add-term" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="1st Trimester">1st Trimester</option>
                        <option value="2nd Trimester">2nd Trimester</option>
                        <option value="3rd Trimester">3rd Trimester</option>
                    </select>
                </div>
                <div>
                    <label for="add-count" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Number of Graduates</label>
                    <input type="number" name="graduates_count" id="add-count" required min="0" placeholder="e.g. 150" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeGraduateModal('add-graduate-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow transition">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Graduate Modal -->
<div id="edit-graduate-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-md font-bold">Edit Graduates Record</h3>
            <button onclick="closeGraduateModal('edit-graduate-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form id="edit-graduate-form" action="" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="program_id" value="{{ $program->id }}">
            <div class="p-6 space-y-4">
                <div>
                    <label for="edit-sy" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School Year</label>
                    <input type="text" name="school_year" id="edit-sy" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-term" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Term</label>
                    <select name="term" id="edit-term" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="1st Trimester">1st Trimester</option>
                        <option value="2nd Trimester">2nd Trimester</option>
                        <option value="3rd Trimester">3rd Trimester</option>
                    </select>
                </div>
                <div>
                    <label for="edit-count" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Number of Graduates</label>
                    <input type="number" name="graduates_count" id="edit-count" required min="0" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeGraduateModal('edit-graduate-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow transition">Update Record</button>
            </div>
        </form>
    </div>
</div>

@endsection