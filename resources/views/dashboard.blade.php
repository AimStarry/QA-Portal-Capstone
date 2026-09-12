@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Portal Welcome Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#4a0000] via-hau-maroon to-[#8c1515] p-6 sm:p-7 shadow-xl shadow-hau-maroon-dark/20 border border-hau-gold/30">
        <!-- Ambient Glowing Highlights -->
        <div class="pointer-events-none absolute -top-20 -right-20 h-64 w-64 rounded-full bg-hau-gold/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-rose-500/15 blur-3xl"></div>
        
        <!-- Subtle Decorative Wave / Geometric Accent Overlay -->
        <div class="pointer-events-none absolute inset-0 opacity-10">
            <svg class="h-full w-full" viewBox="0 0 1000 200" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 80 C 250 160, 450 0, 750 100 C 900 150, 1000 70, 1000 70" stroke="#D4AF37" stroke-width="2" fill="none"/>
                <path d="M0 120 C 300 40, 600 180, 1000 110" stroke="#FFFFFF" stroke-width="1.5" fill="none"/>
            </svg>
        </div>

        <!-- Watermark HAU Emblem -->
        <div class="pointer-events-none absolute -right-6 -bottom-10 h-48 w-48 opacity-[0.08] transform rotate-12 select-none">
            <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Watermark" class="h-full w-full object-contain filter brightness-200">
        </div>

        <!-- Header Content -->
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <!-- Left: Brand, Viewport & Description -->
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-hau-gold/20 text-hau-gold-light border border-hau-gold/40 text-xs font-bold tracking-wider uppercase">
                        <svg class="w-3.5 h-3.5 text-hau-gold" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" />
                        </svg>
                        Quality Assurance
                    </span>
                    
                    @php
                        $viewportName = 'QA Admin';
                        $user = auth()->user();
                        if ($user) {
                            if ($user->usertype === 'Dean' && $user->college) {
                                $viewportName = $user->college->name;
                            } elseif ($user->usertype === 'Head of Unit' && $user->unit) {
                                $viewportName = $user->unit->name;
                            } elseif ($user->usertype === 'QA Admin') {
                                $viewportName = session('active_role', 'QA Admin') === 'Unit or Department' ? 'Unit or Department' : 'QA Admin';
                            }
                        }
                    @endphp
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-black/30 backdrop-blur-sm border border-white/15 text-xs font-semibold text-white/90">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-gray-300">Viewport:</span>
                        <strong class="text-hau-gold font-bold">{{ $viewportName }}</strong>
                    </div>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight drop-shadow-sm">
                    HAU QA Portal Dashboard
                </h2>

                <p class="text-xs sm:text-sm text-gray-200/90 max-w-2xl font-medium leading-relaxed">
                    Tracking accreditation metrics and university-wide documentation checklists.
                </p>
            </div>

            <!-- Right: Export Full Report Button & Live Timestamp -->
            <div class="flex flex-wrap items-center gap-3 self-start lg:self-center">
                <a href="{{ route('reports.export-full') }}" class="group relative inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-black/30 hover:bg-gradient-to-r hover:from-amber-300 hover:via-hau-gold hover:to-amber-400 backdrop-blur-md border border-hau-gold/40 hover:border-white text-white/90 hover:text-hau-maroon-dark shadow-sm hover:shadow-xl hover:shadow-amber-500/25 hover:-translate-y-1 hover:scale-105 active:scale-95 transition-all duration-300 ease-out cursor-pointer overflow-hidden" title="Click to download full multi-tab system report (.xlsx)">
                    <!-- Shimmer sweep effect on hover -->
                    <div class="pointer-events-none absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>

                    <!-- Icon Container -->
                    <div class="p-1 rounded-lg bg-hau-gold/20 group-hover:bg-hau-maroon text-hau-gold group-hover:text-white border border-hau-gold/35 group-hover:border-transparent group-hover:scale-110 transition-all duration-300 flex-shrink-0 shadow-xs relative z-10">
                        <svg class="w-4 h-4 transform group-hover:translate-y-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>

                    <!-- Text Container -->
                    <div class="text-left relative z-10">
                        <div class="text-[9px] uppercase font-bold tracking-wider text-hau-gold group-hover:text-hau-maroon flex items-center gap-1 transition-colors duration-200">
                            <span>Export</span>
                            <svg class="w-2.5 h-2.5 opacity-70 group-hover:opacity-100 group-hover:translate-y-0.5 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="text-white group-hover:text-hau-maroon-dark text-xs font-black tracking-tight transition-colors duration-200">Full System Report</div>
                    </div>
                </a>

                <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-black/30 backdrop-blur-md text-white/90 border border-white/20 shadow-sm hover:border-hau-gold/50 transition-all duration-200">
                    <div class="p-1 rounded-lg bg-hau-gold/20 text-hau-gold border border-hau-gold/35">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-bold tracking-wider text-hau-gold">System Date</div>
                        <div class="font-mono text-white text-xs font-bold tracking-tight">{{ date('F d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($role === 'QA Admin')
        <!-- ================= QA ADMIN VIEWPORT ================= -->

        <!-- 1. Executive Alerts & Action Center Ribbon -->
        @php
            $totalAlertCount = $warningAccreditations->count() + $overdueCompliance->count() + $criticalRisks->count();
        @endphp

        @if ($totalAlertCount > 0)
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                <!-- Ribbon Summary Header (Always Visible, Compact & Informative) -->
                <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 via-white to-gray-50/80">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-rose-50 text-rose-600 rounded-xl border border-rose-100 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">QA Attention Center</h3>
                                <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 font-mono">
                                    {{ $totalAlertCount }} Action Items
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">Quick overview of accreditation expirations, overdue compliance tasks, and active risks.</p>
                        </div>
                    </div>

                    <!-- Quick Glance Badges & Collapse Trigger -->
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap lg:flex-nowrap">
                        @if($warningAccreditations->isNotEmpty())
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200/80 text-rose-700 text-xs font-bold font-mono shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>{{ $warningAccreditations->count() }} Expiring/Lapsed</span>
                            </div>
                        @endif

                        @if($overdueCompliance->isNotEmpty())
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200/80 text-amber-800 text-xs font-bold font-mono shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>{{ $overdueCompliance->count() }} Overdue Tasks</span>
                            </div>
                        @endif

                        @if($criticalRisks->isNotEmpty())
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200/80 text-rose-700 text-xs font-bold font-mono shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                <span>{{ $criticalRisks->count() }} Critical Risk(s)</span>
                            </div>
                        @endif

                        <!-- Toggle Button -->
                        <button type="button" 
                                onclick="toggleAlertDetails()" 
                                id="alert-toggle-btn"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition ml-auto lg:ml-0 cursor-pointer">
                            <span id="alert-toggle-text">Expand Details</span>
                            <svg id="alert-toggle-icon" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Collapsible Detail Section (hidden by default for a clean high-level view) -->
                <div id="alert-detail-drawer" class="hidden border-t border-gray-200 bg-gray-50/50 p-5 lg:p-6 transition-all duration-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Column 1: Accreditation Warnings -->
                        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Accreditation Expirations
                                </h4>
                                <span class="text-xs font-bold font-mono px-2 py-0.5 bg-rose-50 text-rose-700 rounded">{{ $warningAccreditations->count() }}</span>
                            </div>
                            <div class="overflow-y-auto pr-1 space-y-2.5 max-h-64">
                                @forelse($warningAccreditations as $accred)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200/80 space-y-1 hover:border-hau-maroon/30 transition">
                                        <div class="flex justify-between items-center text-[10px]">
                                            <span class="font-bold text-hau-maroon font-mono">{{ $accred->program?->program_code ?? 'N/A' }}</span>
                                            <span class="font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">{{ $accred->status }}</span>
                                        </div>
                                        <p class="text-xs font-bold text-gray-800 leading-tight">Accreditor: {{ $accred->accrediting_body }}</p>
                                        <p class="text-[10px] text-gray-500 font-mono">Expires: {{ $accred->expiry_date ? $accred->expiry_date->format('M d, Y') : 'N/A' }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic py-3 text-center">No expiring accreditations.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Column 2: Overdue Tasks -->
                        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Overdue Compliance
                                </h4>
                                <span class="text-xs font-bold font-mono px-2 py-0.5 bg-amber-50 text-amber-700 rounded">{{ $overdueCompliance->count() }}</span>
                            </div>
                            <div class="overflow-y-auto pr-1 space-y-2.5 max-h-64">
                                @forelse($overdueCompliance as $task)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200/80 space-y-1 hover:border-amber-400 transition">
                                        <div class="flex justify-between items-center text-[10px]">
                                            <span class="font-bold text-hau-maroon font-mono">{{ $task->program?->program_code ?? ($task->school ?? 'General') }}</span>
                                            <span class="font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">Past Due</span>
                                        </div>
                                        <h5 class="text-xs font-bold text-gray-800 leading-tight truncate" title="{{ $task->title }}">{{ $task->title }}</h5>
                                        <p class="text-[10px] text-gray-500 font-mono">Due: {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic py-3 text-center">No overdue compliance tasks.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Column 3: Critical Risks -->
                        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Critical QA Risks
                                </h4>
                                <span class="text-xs font-bold font-mono px-2 py-0.5 bg-rose-50 text-rose-700 rounded">{{ $criticalRisks->count() }}</span>
                            </div>
                            <div class="overflow-y-auto pr-1 space-y-2.5 max-h-64">
                                @forelse($criticalRisks as $risk)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200/80 space-y-1 hover:border-rose-300 transition">
                                        <div class="flex justify-between items-center text-[10px]">
                                            <span class="font-bold text-hau-maroon font-mono">{{ $risk->program?->program_code ?? 'N/A' }}</span>
                                            <span class="font-black text-rose-600 bg-rose-50 px-1.5 py-0.25 rounded border border-rose-100">Critical</span>
                                        </div>
                                        <p class="text-xs text-gray-700 font-medium line-clamp-2 leading-relaxed" title="{{ $risk->description }}">{{ $risk->description }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic py-3 text-center">No critical risks flagged.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ================= 2. UNIFIED EXECUTIVE PERFORMANCE SCORECARD ================= -->
        @php
            $liveAccreditationRate = $liveAccreditableCount > 0 ? round(($liveAccreditedCount / $liveAccreditableCount) * 100) : 0;
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1: Degree Portfolio & Accreditability -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between hover:shadow-md transition relative overflow-hidden group">
                <div class="absolute -top-1 left-0 right-0 h-1.5 bg-hau-maroon"></div>
                
                <!-- 1. Header (Standardized Height) -->
                <div class="flex items-start justify-between gap-2 min-h-[44px]">
                    <span class="font-mono leading-tight" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Academic Offerings</span>
                    <span class="rounded-lg font-mono shrink-0 whitespace-nowrap" style="font-size: 0.78rem; font-weight: 800; padding: 0.2rem 0.55rem; background-color: rgba(128, 0, 0, 0.08); color: #800000; border: 1px solid rgba(128, 0, 0, 0.2);">
                        {{ $liveAccreditableCount }} Accreditable
                    </span>
                </div>

                <!-- 2. Hero Metric Body (Standardized Height) -->
                <div class="min-h-[90px] flex items-center">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-gray-900 font-mono tracking-tighter" style="font-size: 3.5rem; font-weight: 900; line-height: 1;">{{ $liveOfferingsCount }}</span>
                        <span class="font-sans" style="font-size: 0.88rem; font-weight: 700; color: #4b5563;">Total Programs</span>
                    </div>
                </div>

                <!-- 3. Footer (Standardized Height) -->
                <div class="min-h-[52px] pt-3 border-t border-gray-100 flex flex-col justify-center">
                    <div class="flex items-center justify-between font-medium" style="font-size: 0.85rem; color: #4b5563;">
                        <span>Accreditable programs:</span>
                        <span class="font-mono font-bold" style="font-size: 0.95rem; color: #111827;">{{ $liveAccreditableCount }} / {{ $liveOfferingsCount }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Accredited Programs & Interactive Filter -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between hover:shadow-md transition relative overflow-hidden group">
                <div class="absolute -top-1 left-0 right-0 h-1.5 bg-hau-gold"></div>
                
                <!-- 1. Header (Standardized Height) -->
                <div class="flex items-start justify-between gap-2 min-h-[44px]">
                    <span class="font-mono leading-tight" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Accredited Programs</span>
                    <span class="rounded-lg font-mono shrink-0 uppercase whitespace-nowrap" id="card-accreditor-tag" style="font-size: 0.78rem; font-weight: 900; padding: 0.2rem 0.55rem; background-color: rgba(212, 175, 55, 0.2); color: #8a6d12; border: 1px solid rgba(212, 175, 55, 0.35);">
                        All
                    </span>
                </div>

                <!-- 2. Hero Metric Body (Standardized Height) -->
                <div class="min-h-[90px] flex items-center">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span id="accredited-card-count" class="font-mono tracking-tighter" style="font-size: 3.5rem; font-weight: 900; line-height: 1; color: #800000;">{{ $accreditationCounts[''] }}</span>
                        <span id="accredited-card-unit" class="font-sans" style="font-size: 0.88rem; font-weight: 700; color: #4b5563;">Active Certifications</span>
                    </div>
                </div>

                <!-- 3. Footer (Standardized Height) -->
                <div class="min-h-[52px] pt-3 border-t border-gray-100 flex flex-col justify-center">
                    <select id="dashboard-filter-body" onchange="filterProgramsByBody(this.value)" class="block w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs font-bold text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer">
                        <option value="">All Accrediting Bodies</option>
                        @foreach($allAccreditingBodies as $body)
                            @if(is_string($body))
                                <option value="{{ $body }}">{{ $body }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Card 3: Institutional Accreditation Rate -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between hover:shadow-md transition relative overflow-hidden group">
                <div class="absolute -top-1 left-0 right-0 h-1.5 bg-emerald-500"></div>
                
                <!-- 1. Header (Standardized Height) -->
                <div class="flex items-start justify-between gap-2 min-h-[44px]">
                    <span class="font-mono leading-tight" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Accreditation Rate</span>
                    <span class="rounded-lg font-mono shrink-0 whitespace-nowrap" style="font-size: 0.78rem; font-weight: 800; padding: 0.2rem 0.55rem; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                        Overall
                    </span>
                </div>

                <!-- 2. Hero Metric Body (Standardized Height) -->
                <div class="min-h-[90px] flex items-center">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="font-mono tracking-tighter" style="font-size: 3.5rem; font-weight: 900; line-height: 1; color: #059669;">{{ $liveAccreditationRate }}%</span>
                        <span class="font-sans" style="font-size: 0.88rem; font-weight: 700; color: #4b5563;">of Accreditable</span>
                    </div>
                </div>

                <!-- 3. Footer (Standardized Height) -->
                <div class="min-h-[52px] pt-3 border-t border-gray-100 flex flex-col justify-center space-y-1.5">
                    <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $liveAccreditationRate) }}%"></div>
                    </div>
                    <div class="flex justify-between items-center font-medium" style="font-size: 0.78rem; color: #4b5563;">
                        <span>{{ $liveAccreditedCount }} of {{ $liveAccreditableCount }} accredited</span>
                        <span class="font-mono font-bold" style="color: #059669;">{{ $liveAccreditationRate }}%</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Scope & Distribution (Local vs. International) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between hover:shadow-md transition relative overflow-hidden group">
                <div class="absolute -top-1 left-0 right-0 h-1.5 bg-hau-maroon-dark"></div>
                
                <!-- 1. Header (Standardized Height) -->
                <div class="flex items-start justify-between gap-2 min-h-[44px]">
                    <span class="font-mono leading-tight" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280;">Accreditation Scope</span>
                    <span class="rounded font-mono shrink-0" style="font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.45rem; background-color: #f3f4f6; color: #6b7280;">Breakdown</span>
                </div>

                <!-- 2. Hero Metric Body (Standardized Height) -->
                <div class="min-h-[90px] flex items-center">
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <div class="bg-hau-maroon/5 rounded-xl p-2 border border-hau-maroon/15 text-center">
                            <span class="font-mono block" style="font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #800000;">Local</span>
                            <span class="font-mono block tracking-tighter" style="font-size: 2.1rem; font-weight: 900; line-height: 1.1; color: #800000;">{{ $liveLocallyAccreditedCount }}</span>
                            <span class="font-sans block truncate" style="font-size: 0.72rem; font-weight: 700; color: #4b5563;">PAASCU / PAC</span>
                        </div>
                        <div class="bg-hau-gold/10 rounded-xl p-2 border border-hau-gold/25 text-center">
                            <span class="font-mono block" style="font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #8a6d12;">Int'l</span>
                            <span class="font-mono block tracking-tighter" style="font-size: 2.1rem; font-weight: 900; line-height: 1.1; color: #8a6d12;">{{ $liveInternationallyAccreditedCount }}</span>
                            <span class="font-sans block truncate" style="font-size: 0.72rem; font-weight: 700; color: #4b5563;">AUN / IACBE</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Footer (Standardized Height) -->
                <div class="min-h-[52px] pt-3 border-t border-gray-100 flex flex-col justify-center">
                    <div class="flex items-center justify-between font-medium" style="font-size: 0.85rem; color: #4b5563;">
                        <span>Monitored QA Risks:</span>
                        <span class="font-mono font-bold" style="font-size: 0.95rem; color: {{ $totalRisks > 0 ? '#b45309' : '#047857' }};">{{ $totalRisks }} active</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Compliance Rate per Accrediting Body (Checklist-based) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-hau-maroon/10 rounded-lg">
                        <svg class="w-4 h-4 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Compliance Rates by Accrediting Body</h3>
                </div>
                <p class="text-[10px] text-gray-400 mb-4 -mt-2">Based on completed recommendation checklist items across all programs.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @forelse($bodyComplianceRates as $body => $rate)
                        <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-150 hover:border-hau-gold/50 transition">
                            <div class="relative w-16 h-16 flex-shrink-0 flex items-center justify-center">
                                <svg class="w-full h-full transform -rotate-90">
                                    <circle cx="32" cy="32" r="26" stroke-width="4" stroke="#e2e8f0" fill="transparent" />
                                    <circle cx="32" cy="32" r="26" stroke-width="5" stroke="#800000" fill="transparent"
                                            stroke-dasharray="163.36"
                                            stroke-dashoffset="{{ 163.36 - (163.36 * $rate) / 100 }}" />
                                </svg>
                                <span class="absolute text-xs font-bold text-gray-900">{{ $rate }}%</span>
                            </div>
                            <div>
                                <span class="text-xs font-black text-hau-maroon uppercase tracking-wider">{{ $body }}</span>
                                <p class="text-xs text-gray-500 font-semibold mt-0.5">Checklist completion</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-4 col-span-full">No accrediting body accreditations found to compute rates.</p>
                    @endforelse
                </div>
            </div>

            <!-- Accreditation Type Breakdown -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-hau-gold/15 rounded-lg">
                        <svg class="w-4 h-4 text-hau-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Recommendation Accomplishment by Type</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>Local Recommendation(s)</span>
                            <span class="font-mono text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded font-bold">{{ $localPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-hau-maroon h-full rounded-full transition-all duration-500" style="width: {{ $localPercentage }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>International Recommendation(s)</span>
                            <span class="font-mono text-hau-gold-dark bg-hau-gold/10 px-2 py-0.5 rounded font-bold">{{ $intlPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-hau-gold h-full rounded-full transition-all duration-500" style="width: {{ $intlPercentage }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>Regulatory Recommendation(s)</span>
                            <span class="font-mono text-gray-600 bg-gray-100 px-2 py-0.5 rounded font-bold">{{ $regulatoryPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-gray-500 h-full rounded-full transition-all duration-500" style="width: {{ $regulatoryPercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- END 3-col admin grid --}}

        <!-- Pending Compliance Approvals Queue -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-hau-maroon-dark px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-hau-gold"></span>
                    </span>
                    Pending Compliance Approvals Queue
                </h3>
                <span class="text-xs font-mono bg-hau-maroon text-hau-gold-light px-2.5 py-0.5 rounded-md border border-hau-gold/30">
                    {{ $pendingApprovals->count() }} Request(s)
                </span>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($pendingApprovals as $pending)
                    <!-- Clickable Row — opens full details modal -->
                    <div class="group p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-hau-maroon/3 transition duration-150 cursor-pointer"
                         onclick="openApprovalDetailModal(this)"
                         data-id="{{ $pending->id }}"
                         data-program-code="{{ $pending->program->program_code ?? ($pending->school ?? 'General') }}"
                         data-title="{{ $pending->title }}"
                         data-description="{{ $pending->description }}"
                         data-responsible-unit="{{ $pending->responsible_unit }}"
                         data-contact-person="{{ $pending->contact_person }}"
                         data-contact-email="{{ $pending->contact_email }}"
                         data-accrediting-body="{{ $pending->accrediting_body }}"
                         data-school="{{ $pending->school }}"
                         data-recommendation="{{ $pending->recommendation }}"
                         data-category="{{ $pending->category }}"
                         data-area="{{ $pending->area }}"
                         data-current-status="{{ $pending->status }}"
                         data-action-plan="{{ $pending->action_plan }}"
                         data-pending-link="{{ $pending->pending_document_link }}"
                         data-due-date="{{ $pending->due_date ? $pending->due_date->format('M d, Y') : 'No deadline' }}"
                         data-visit-date="{{ $pending->visit_date ? $pending->visit_date->format('M d, Y') : '—' }}"
                         data-submitted-at="{{ $pending->updated_at->format('M d, Y h:i A') }}"
                         data-approve-url="{{ route('compliance.approve', $pending->id) }}"
                         data-reject-url="/compliance/{{ $pending->id }}/reject"
                         data-recommendations="{{ $pending->recommendationItems->pluck('text')->toJson() }}"
                         data-recommendations-completed="{{ $pending->recommendationItems->where('is_completed', true)->count() }}"
                         data-recommendations-total="{{ $pending->recommendationItems->count() }}">

                        <!-- Left: compact summary -->
                        <div class="flex items-start gap-4 min-w-0">
                            <!-- Indicator dot -->
                            <div class="mt-1 shrink-0">
                                <span class="flex h-3 w-3">
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-hau-gold"></span>
                                </span>
                            </div>
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">{{ $pending->program->program_code ?? ($pending->school ?? 'General') }}</span>
                                    @if($pending->accrediting_body)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-hau-gold/15 text-hau-maroon-dark">{{ $pending->accrediting_body }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400">by: <strong class="text-gray-600">{{ $pending->responsible_unit }}</strong></span>
                                </div>
                                <h4 class="font-bold text-gray-900 text-sm leading-snug">{{ $pending->title }}</h4>
                                @if($pending->recommendationItems->count() > 0)
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1.5 text-xs text-hau-maroon/80 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            {{ $pending->recommendationItems->where('is_completed', true)->count() }}/{{ $pending->recommendationItems->count() }} recommendations completed
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2 text-[10px] text-gray-400 font-semibold pt-0.5">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $pending->status }}</span>
                                    <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    <span class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded border border-emerald-100 font-bold">Compliant</span>
                                    <span class="text-gray-300">&bull;</span>
                                    <span>Submitted {{ $pending->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: action buttons + click hint -->
                        <div class="flex flex-row md:flex-col lg:flex-row items-center gap-2.5 shrink-0" onclick="event.stopPropagation()">
                            <button onclick="openApprovalDetailModal(this.closest('[data-id]'))" class="hidden group-hover:inline-flex items-center gap-1 px-3 py-1.5 bg-hau-maroon/5 hover:bg-hau-maroon/10 text-hau-maroon border border-hau-maroon/15 text-xs font-bold rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Full Details
                            </button>
                            <form action="{{ route('compliance.approve', $pending->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Approve
                                </button>
                            </form>
                            <button onclick="openRejectModal('{{ $pending->id }}', '{{ $pending->title }}')" class="inline-flex items-center px-4 py-2 border border-rose-200 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-lg transition">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Reject
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 text-xs leading-relaxed">
                        No pending compliance task approvals. The queue is empty.
                    </div>
                @endforelse
            </div>
        </div>

    @else
        <!-- ================= RESPONSIBLE UNIT VIEWPORT ================= -->

        <!-- Action Alerts (Overdue tasks alert) -->
        @if ($overdueCompliance->isNotEmpty())
            <div class="bg-gradient-to-br from-hau-maroon/5 to-hau-gold/5 border border-hau-maroon/15 rounded-2xl p-6 space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-hau-maroon/15">
                    <div class="p-1.5 bg-hau-maroon/10 rounded-lg">
                        <svg class="w-4 h-4 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-hau-maroon uppercase tracking-wider font-sans">Overdue Compliance Alerts</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($overdueCompliance->take(6) as $task)
                        <div class="bg-white rounded-xl p-4 border border-hau-maroon/10 shadow-sm space-y-2 flex flex-col justify-between hover:border-hau-gold/50 transition">
                            <div class="space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded font-mono">{{ $task->program->program_code ?? ($task->school ?? 'General') }}</span>
                                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 px-1.5 py-0.25 rounded border border-rose-100">Past Due</span>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 leading-snug">{{ $task->title }}</h4>
                                <p class="text-xs text-gray-500 font-mono mt-1">Deadline: {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="pt-3 border-t border-gray-100 flex justify-end">
                                <a href="{{ route('compliance.index') }}?search={{ $task->program->program_code ?? ($task->school ?? '') }}" class="inline-flex items-center gap-1 px-3 py-1 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    Submit Evidence
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Checklists Assigned</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono">{{ $unitTotalTasks }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 border-l-4 border-l-amber-400 hover:shadow-md transition">
                <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Action</p>
                <p class="text-2xl font-black text-amber-600 mt-1 font-mono">{{ $unitPendingCount }}</p>
                <p class="text-[10px] text-gray-400 font-medium mt-1">Tasks requiring your attention</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 border-l-4 border-l-hau-gold hover:shadow-md transition">
                <p class="text-xs font-bold text-hau-maroon uppercase tracking-wider">Awaiting Approval</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono text-hau-maroon-dark">{{ $unitPendingApprovalsCount }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 border-l-4 border-l-emerald-500 hover:shadow-md transition">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Completed / Compliant</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono text-emerald-600">{{ $unitCompliantCount }}</p>
            </div>
        </div>

        {{-- ── Pending Compliance Items ─────────────────────────────────────── --}}
        @if ($pendingTasks->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-amber-50/60">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-amber-100 rounded-lg">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Pending Compliance Items</h3>
                            <p class="text-[10px] text-gray-500 font-medium">Tasks still requiring action — submit evidence via Compliance Tracker</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-lg border border-amber-200">
                            {{ $unitPendingCount }} Item(s)
                        </span>
                        <a href="{{ route('compliance.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">
                            Open Tracker
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Program</th>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Task Title</th>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Accreditor</th>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Responsible Unit</th>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-5 py-3 text-right font-bold text-gray-500 uppercase tracking-wider w-24">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($pendingTasks as $task)
                                @php
                                    $isOverdue = $task->due_date && $task->due_date->isPast();
                                    $isDueSoon = $task->due_date && !$isOverdue && $task->due_date->diffInDays(now()) <= 7;
                                @endphp
                                <tr class="hover:bg-amber-50/40 cursor-pointer transition duration-100 {{ $isOverdue ? 'bg-rose-50/20' : '' }}"
                                    onclick="openComplianceDetailModal({
                                        id: '{{ $task->compliance_record_id }}',
                                        program: '{{ addslashes($task->program->program_code ?? '—') }}',
                                        programName: '{{ addslashes($task->program->program_name ?? '') }}',
                                        title: '{{ addslashes($task->title) }}',
                                        description: '{{ addslashes($task->description ?? '') }}',
                                        accreditor: '{{ addslashes($task->accrediting_body ?? '—') }}',
                                        category: '{{ addslashes($task->category ?? '') }}',
                                        area: '{{ addslashes($task->area ?? '') }}',
                                        responsibleUnit: '{{ addslashes($task->responsible_unit ?? '—') }}',
                                        status: '{{ $task->status }}',
                                        priority: '{{ $task->priority ?? '' }}',
                                        dueDate: '{{ $task->due_date ? $task->due_date->format("M d, Y") : "No deadline" }}',
                                        isOverdue: {{ $isOverdue ? 'true' : 'false' }},
                                        isDueSoon: {{ $isDueSoon ? 'true' : 'false' }},
                                        recommendation: '{{ addslashes($task->recommendation ?? '') }}',
                                        actionPlan: '{{ addslashes($task->action_plan ?? '') }}',
                                        contactPerson: '{{ addslashes($task->contact_person ?? '') }}',
                                        contactEmail: '{{ addslashes($task->contact_email ?? '') }}',
                                        submitUrl: '{{ route('compliance.index') }}?search={{ $task->program->program_code ?? '' }}'
                                    })">
                                    <td class="px-5 py-3">
                                        <span class="font-bold text-hau-maroon font-mono">{{ $task->program->program_code ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-gray-900 max-w-[220px]">
                                        <span class="line-clamp-2" title="{{ $task->title }}">{{ $task->title }}</span>
                                        @if($task->category)
                                            <span class="text-[10px] text-gray-400 font-normal block">{{ $task->category }}{{ $task->area ? ' · ' . $task->area : '' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($task->accrediting_body)
                                            <span class="inline-flex px-2 py-0.5 rounded font-bold text-[10px] bg-hau-gold/10 text-hau-maroon-dark border border-hau-gold/20">{{ $task->accrediting_body }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 font-medium max-w-[160px] truncate" title="{{ $task->responsible_unit }}">
                                        {{ $task->responsible_unit ?: '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded font-bold text-[10px] {{ $task->status === 'Non-Compliant' ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                            {{ $task->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($task->due_date)
                                            <span class="font-mono {{ $isOverdue ? 'text-rose-600 font-bold' : ($isDueSoon ? 'text-amber-600 font-semibold' : 'text-gray-500') }}">
                                                {{ $task->due_date->format('M d, Y') }}
                                            </span>
                                            @if($isOverdue)
                                                <span class="block text-[9px] text-rose-500 font-bold">OVERDUE</span>
                                            @elseif($isDueSoon)
                                                <span class="block text-[9px] text-amber-500 font-bold">DUE SOON</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">No deadline</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right" onclick="event.stopPropagation()">
                                        <a href="{{ route('compliance.index') }}?search={{ $task->program->program_code ?? '' }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-hau-maroon hover:bg-hau-maroon-light text-white text-[10px] font-bold rounded-lg transition shadow-sm">
                                            Submit
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 flex items-center gap-4">
                <div class="p-3 bg-emerald-100 rounded-xl">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-emerald-800">All Clear!</h4>
                    <p class="text-xs text-emerald-700 font-medium">No pending compliance items requiring your action at this time.</p>
                </div>
            </div>
        @endif

        {{-- ── Compliance Item Detail Modal ─────────────────────────────────── --}}
        <div id="compliance-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-xs" onclick="closeComplianceDetailModal()"></div>
            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col transform scale-100 transition-all duration-200">
                <!-- Header with dark maroon gradient -->
                <div class="modal-dark-header flex items-center justify-between border-b border-white/10 shrink-0" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                    <div class="flex items-center gap-3.5 min-w-0 pr-2">
                        <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                            <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider" style="color: rgba(255, 255, 255, 0.85);">Compliance Item Details</p>
                            <h2 id="cdm-title" class="text-base font-bold text-white leading-tight truncate mt-0.5" style="color: #ffffff;">—</h2>
                        </div>
                    </div>
                    <button onclick="closeComplianceDetailModal()" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer shrink-0" style="color: #ffffff;" aria-label="Close modal">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Status banner -->
                <div id="cdm-status-banner" class="px-6 py-2.5 flex items-center gap-3 border-b border-gray-100 bg-gray-50/50 shrink-0"></div>
                <!-- Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <!-- Program & Accreditor -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Program</p>
                            <p id="cdm-program" class="text-sm font-black text-hau-maroon font-mono">—</p>
                            <p id="cdm-program-name" class="text-xs text-gray-500 font-medium">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Accrediting Body</p>
                            <p id="cdm-accreditor" class="text-sm font-semibold text-gray-800">—</p>
                        </div>
                    </div>
                    <!-- Category & Area -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Category</p>
                            <p id="cdm-category" class="text-sm text-gray-700 font-medium">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Area</p>
                            <p id="cdm-area" class="text-sm text-gray-700 font-medium">—</p>
                        </div>
                    </div>
                    <!-- Responsible Unit & Due Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Responsible Unit</p>
                            <p id="cdm-unit" class="text-sm text-gray-700 font-medium">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Due Date</p>
                            <p id="cdm-due" class="text-sm font-mono font-bold text-gray-900">—</p>
                        </div>
                    </div>
                    <!-- Contact -->
                    <div id="cdm-contact-row" class="grid grid-cols-2 gap-4 hidden">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Contact Person</p>
                            <p id="cdm-contact" class="text-sm text-gray-700 font-medium">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Contact Email</p>
                            <p id="cdm-email" class="text-sm text-gray-700 font-mono text-xs">—</p>
                        </div>
                    </div>
                    <!-- Description -->
                    <div id="cdm-desc-row" class="hidden">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Description</p>
                        <p id="cdm-description" class="text-sm text-gray-700 leading-relaxed bg-gray-50 border border-gray-200/60 rounded-xl p-3.5">—</p>
                    </div>
                    <!-- Recommendation -->
                    <div id="cdm-rec-row" class="hidden">
                        <p class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider mb-1">Recommendation / Requirement</p>
                        <p id="cdm-recommendation" class="text-sm text-gray-700 leading-relaxed bg-amber-50/70 border border-amber-200/60 rounded-xl p-3.5">—</p>
                    </div>
                    <!-- Action Plan -->
                    <div id="cdm-plan-row" class="hidden">
                        <p class="text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-1">Action Plan</p>
                        <p id="cdm-action-plan" class="text-sm text-gray-700 leading-relaxed bg-blue-50/70 border border-blue-200/60 rounded-xl p-3.5">—</p>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 shrink-0">
                    <button onclick="closeComplianceDetailModal()" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition cursor-pointer">
                        Close
                    </button>
                    <a id="cdm-submit-btn" href="#" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl transition shadow-sm">
                        Go to Compliance Tracker
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>


        @if ($rejectedTasks->isNotEmpty())
            <div class="bg-rose-50 border border-rose-200 rounded-2xl overflow-hidden p-6 space-y-4">
                <h3 class="font-bold text-sm text-rose-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Corrections Required ({{ $rejectedTasks->count() }} Rejected Submissions)
                </h3>
                <div class="space-y-4">
                    @foreach($rejectedTasks as $task)
                        <div class="bg-white rounded-xl p-4 border border-rose-200/60 shadow-xs flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded font-mono">{{ $task->program->program_code }}</span>
                                    <h4 class="text-sm font-bold text-gray-900">{{ $task->title }}</h4>
                                </div>
                                <p class="text-xs text-rose-700 font-semibold mt-1">Rejection Reason: <span class="italic text-gray-700 font-medium">"{{ $task->rejection_reason }}"</span></p>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="{{ route('compliance.index') }}?search={{ $task->program->program_code }}" class="inline-flex px-3.5 py-1.5 bg-hau-maroon hover:bg-hau-maroon-light text-white font-bold text-xs rounded-lg transition shadow-xs">
                                    Revise & Resubmit
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Awaiting Approvals tracker -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Awaiting QA Admin Approvals</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Program</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Task Title</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Target Status</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Proposed Link</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Submission Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($awaitingTasks as $at)
                            <tr class="hover:bg-hau-maroon/3 transition">
                                <td class="px-4 py-3 font-bold text-hau-maroon">{{ $at->program->program_code }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $at->title }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $at->pending_status }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-500 max-w-[200px] truncate" title="{{ $at->pending_document_link }}">{{ $at->pending_document_link }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $at->updated_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400">No active updates awaiting approval.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif {{-- END role check --}}

    <!-- Summary Dashboard Analytics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Accreditation Levels Summary Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4 flex flex-col h-full">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                <div class="p-1.5 bg-hau-gold/15 text-hau-gold-dark rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Accreditation Levels Summary</h3>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase tracking-wider">Accreditation Level / Tier</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-500 uppercase tracking-wider w-32">Program Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($levelsSummary as $level)
                            @php
                                $tier = is_array($level) ? ($level['level_or_tier'] ?? 'No Level Set') : ($level->level_or_tier ?? 'No Level Set');
                                $count = is_array($level) ? ($level['count'] ?? 0) : ($level->count ?? 0);
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $tier ?: 'No Level Set' }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-hau-maroon text-sm">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-4 text-gray-400">No active accreditation levels logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Checked Off Checklist Queue -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4 flex flex-col h-full">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-3 justify-between">
                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <div class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    Checked Off Recommendation(s) Notifications
                </h3>
                <span class="text-xs text-gray-500 font-semibold font-mono">{{ $recentlyCompletedRecommendations->count() }} Checked Off</span>
            </div>
            <div class="flex-1 overflow-y-auto min-h-0 divide-y divide-gray-100 pr-1">
                @forelse($recentlyCompletedRecommendations as $item)
                    <div class="py-2.5 flex items-start justify-between gap-3 text-xs">
                        <div class="space-y-0.5 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.25 rounded font-mono text-[9px] font-bold bg-hau-maroon/5 text-hau-maroon">{{ $item->complianceRecord->program->program_code ?? 'N/A' }}</span>
                                <span class="text-[10px] text-gray-400 font-medium truncate max-w-[150px]">{{ $item->complianceRecord->title ?? 'N/A' }}</span>
                            </div>
                            <p class="text-gray-700 font-semibold truncate max-w-[280px]" title="{{ $item->text }}">{{ $item->text }}</p>
                        </div>
                        <span class="text-[9px] text-gray-400 shrink-0 font-mono text-right leading-relaxed">{{ $item->completed_at ? $item->completed_at->format('M d h:i A') : $item->updated_at->format('M d h:i A') }}</span>
                    </div>
                @empty
                    <div class="py-6 text-center text-gray-400 italic">No recommendations checked off yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ================= ACCREDITED PROGRAMS DIRECTORY ================= -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-3 gap-2">
            <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                <span class="bg-hau-maroon text-hau-gold font-bold text-[10px] px-2 py-0.5 rounded border border-hau-gold/30">HAU</span>
                Accredited Programs Directory
            </h3>
            <div class="flex items-center gap-3">
                <label for="directory-filter-body" class="text-xs font-bold text-gray-400 uppercase tracking-wider hidden sm:inline">Filter:</label>
                <select id="directory-filter-body" onchange="filterProgramsByBody(this.value)" class="block px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer">
                    <option value="">All Accrediting Bodies</option>
                    @foreach($allAccreditingBodies as $body)
                        @if(is_string($body))
                            <option value="{{ $body }}">{{ $body }}</option>
                        @endif
                    @endforeach
                </select>
                <span class="text-xs text-gray-500 font-semibold"><span id="directory-count">{{ $accreditedPrograms->count() }}</span> Program(s) Total</span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 shadow-2xs" style="max-height: 480px; overflow-y: auto; overflow-x: auto;">
            <table class="w-full divide-y divide-gray-200 text-left">
                <thead class="bg-gray-50 border-b border-gray-200 shadow-xs" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 text-xs font-bold text-gray-600 uppercase tracking-wider w-4/12" style="background-color: #f9fafb;">Program</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-bold text-gray-600 uppercase tracking-wider w-3/12" style="background-color: #f9fafb;">School & Department</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-bold text-gray-600 uppercase tracking-wider w-5/12" style="background-color: #f9fafb;">Accreditations & Status</th>
                    </tr>
                </thead>
                <tbody id="directory-table-body" class="divide-y divide-gray-100 bg-white">
                    @forelse($accreditedPrograms as $prog)
                        <tr class="program-row hover:bg-gray-50/70 transition duration-150"
                            data-bodies="{{ json_encode($prog->accreditations->pluck('accrediting_body')->map(fn($b) => strtolower($b))->toArray()) }}">
                            
                            <!-- Program Code & Title -->
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded font-mono font-black text-xs bg-hau-maroon/10 text-hau-maroon border border-hau-maroon/20">
                                        {{ $prog->program_code }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold text-gray-900 mt-1.5 leading-snug">
                                    {{ $prog->program_name }}
                                </div>
                                @if($prog->former_name)
                                    <div class="text-[10px] text-gray-400 italic mt-0.5">
                                        formerly: {{ $prog->former_name }}
                                    </div>
                                @endif
                            </td>

                            <!-- School / College / Department -->
                            <td class="px-5 py-4 align-top">
                                @if($prog->college)
                                    <div class="flex items-start gap-1.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-gray-100 text-gray-700 border border-gray-200 mt-0.5 shrink-0">
                                            {{ $prog->college->code ?? 'School' }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-800 leading-snug">{{ $prog->college->name }}</div>
                                            @if($prog->college->former_name)
                                                <div class="text-[10px] text-gray-400 italic">formerly: {{ $prog->college->former_name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Unassigned</span>
                                @endif
                                @if($prog->department)
                                    <div class="text-[11px] text-gray-500 font-medium mt-1 pl-0.5">
                                        <span class="text-gray-400">Dept:</span> <span class="font-semibold text-gray-600">{{ $prog->department }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Accreditations & Tiers List -->
                            <td class="px-5 py-4 align-top">
                                <div class="space-y-2 w-full">
                                    @foreach($prog->accreditations as $audit)
                                        <div class="accreditation-pill flex items-center justify-between gap-3 px-3 py-2 bg-gray-50/80 hover:bg-hau-maroon/5 rounded-xl border border-gray-200/80 transition duration-150"
                                             data-body="{{ strtolower($audit->accrediting_body) }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="font-bold text-[11px] text-hau-gold-dark px-2 py-0.5 bg-hau-gold/15 rounded-md font-mono border border-hau-gold/30 shrink-0">
                                                    {{ $audit->accrediting_body }}
                                                </span>
                                                <span class="text-xs font-bold text-gray-800 whitespace-nowrap" title="{{ $audit->level_or_tier }}">
                                                    {{ $audit->level_or_tier ?? 'Awaiting visit' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2.5 shrink-0">
                                                <span class="font-mono text-[11px] text-gray-500 font-medium whitespace-nowrap">
                                                    {{ $audit->expiry_date ? $audit->expiry_date->format('M d, Y') : '—' }}
                                                </span>
                                                <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold whitespace-nowrap {{ $audit->status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($audit->status === 'Expiring Soon' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-rose-50 text-rose-700 border border-rose-100') }}">
                                                    {{ $audit->status }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-xs text-gray-400 text-center py-8 italic">No accredited programs logged in directory.</td>
                        </tr>
                    @endforelse
                    
                    <tr id="no-accredited-matches" class="hidden">
                        <td colspan="3" class="text-xs text-gray-400 text-center py-8 italic">No programs match the selected accrediting body.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= APPROVAL DETAIL POP-OUT MODAL ================= -->
<div id="approval-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-2xl overflow-hidden transform scale-95 transition-all duration-200 flex flex-col">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10 shrink-0" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5 min-w-0 pr-2">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span id="adm-program-code" class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold font-mono bg-white/15 text-white" style="color: #ffffff;"></span>
                        <span id="adm-body" class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-white/20 text-white" style="color: #ffffff;"></span>
                    </div>
                    <h3 id="adm-title" class="text-base font-bold text-white leading-snug truncate" style="color: #ffffff;"></h3>
                </div>
            </div>
            <button onclick="closeApprovalDetailModal()" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer shrink-0" style="color: #ffffff;" aria-label="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto flex-1">

            <!-- Workflow Banner -->
            <div class="flex items-center gap-2 bg-hau-maroon/5 border border-hau-maroon/15 rounded-xl px-4 py-3">
                <div class="flex items-center gap-1.5 text-xs font-bold text-hau-maroon-dark flex-wrap">
                    <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded-lg flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Recommendation
                    </span>
                    <svg class="w-3.5 h-3.5 text-hau-maroon/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="bg-hau-gold/20 text-hau-maroon-dark px-2 py-0.5 rounded-lg flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Action Plan
                    </span>
                    <svg class="w-3.5 h-3.5 text-hau-maroon/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="bg-hau-maroon text-white px-2 py-0.5 rounded-lg ring-2 ring-hau-gold/40 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Admin Reviews
                    </span>
                    <svg class="w-3.5 h-3.5 text-hau-maroon/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="bg-gray-200 text-gray-500 px-2 py-0.5 rounded-lg flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Compliant
                    </span>
                </div>
            </div>

            <!-- Status Transition -->
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-500 font-semibold text-xs">Status Transition:</span>
                <span id="adm-current-status" class="font-bold text-gray-600 bg-gray-100 px-2.5 py-0.5 rounded-lg border border-gray-200 text-xs"></span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200 text-xs">Compliant</span>
                <span class="text-[10px] text-gray-400 italic">(auto on approval)</span>
            </div>

            <!-- Recommendation Checklist -->
            <div>
                <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider mb-1 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Accreditor Recommendations
                </span>
                <div id="adm-recommendation" class="text-sm text-gray-700 leading-relaxed bg-hau-maroon/5 border border-hau-maroon/10 rounded-xl p-4 font-medium"></div>
            </div>

            <!-- Area / Category / School / Submitted -->
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Area / Category</span>
                    <span id="adm-area-cat" class="font-semibold text-gray-800"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">School / College</span>
                    <span id="adm-school" class="font-semibold text-gray-800"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Unit or Department</span>
                    <span id="adm-resp-unit" class="font-semibold text-gray-800"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Contact Person</span>
                    <span id="adm-contact-person" class="font-semibold text-gray-800"></span>
                    <span id="adm-contact-email" class="block text-gray-500 font-mono text-[10px] mt-0.5"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Submitted At</span>
                    <span id="adm-submitted-at" class="font-semibold text-gray-800 font-mono"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Due Date</span>
                    <span id="adm-due-date" class="font-semibold text-gray-800 font-mono"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Accreditation Visit Date</span>
                    <span id="adm-visit-date" class="font-semibold text-gray-800 font-mono"></span>
                </div>
            </div>

            <!-- Description -->
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Task Description</span>
                <p id="adm-description" class="text-xs text-gray-600 leading-relaxed whitespace-pre-line bg-gray-50 p-3 rounded-xl border border-gray-200/60"></p>
            </div>

            <!-- Submitted Action Plan -->
            <div class="bg-hau-gold/10 border border-hau-gold/30 rounded-xl p-4">
                <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Submitted Action Plan
                </span>
                <p id="adm-action-plan" class="text-sm text-gray-800 leading-relaxed font-medium whitespace-pre-line"></p>
            </div>

            <!-- Evidence Link -->
            <div class="bg-hau-maroon/5 border border-hau-maroon/10 rounded-xl p-4 flex items-center justify-between">
                <span class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    Evidence Document Link
                </span>
                <div id="adm-evidence-link-container"></div>
            </div>
        </div>

        <!-- Footer: Approve / Reject actions -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0">
            <button onclick="closeApprovalDetailModal()" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition">Close</button>
            <div class="flex items-center gap-3">
                <button id="adm-reject-btn"
                        onclick="closeApprovalDetailModal(); openRejectModal(window._admCurrentId, window._admCurrentTitle)"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-xl transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Reject Update
                </button>
                <form id="adm-approve-form" action="" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Approve & Mark Compliant
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= REJECTION REASON MODAL ================= -->
<div id="reject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-md overflow-hidden transform scale-95 transition-all duration-200">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Reject Compliance Update</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Return submission for revisions</p>
                </div>
            </div>
            <button onclick="closeRejectModal()" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" aria-label="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="reject-form" action="" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <p class="text-xs text-gray-600 leading-relaxed bg-rose-50 border border-rose-100 rounded-xl p-3.5">Provide constructive feedback or a reason for rejecting the proposed compliance changes. The Unit or Department will see this feedback in order to submit revisions.</p>
                <div>
                    <label for="reject-reason" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_reason" id="reject-reason" required rows="3" placeholder="e.g. Please upload the document with authorized signatures." class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition"></textarea>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle expandable alert drawer
    function toggleAlertDetails() {
        const drawer = document.getElementById('alert-detail-drawer');
        const text = document.getElementById('alert-toggle-text');
        const icon = document.getElementById('alert-toggle-icon');
        if (!drawer) return;
        if (drawer.classList.contains('hidden')) {
            drawer.classList.remove('hidden');
            if (text) text.innerText = 'Hide Details';
            if (icon) icon.classList.add('rotate-180');
        } else {
            drawer.classList.add('hidden');
            if (text) text.innerText = 'Expand Details';
            if (icon) icon.classList.remove('rotate-180');
        }
    }

    // Sync filter selects and filter program rows dynamically
    function filterProgramsByBody(bodyVal) {
        const filterVal = bodyVal.toLowerCase();
        
        // Sync the filter selects
        const cardSelect = document.getElementById('dashboard-filter-body');
        const headerSelect = document.getElementById('directory-filter-body');
        if (cardSelect && cardSelect.value !== bodyVal) cardSelect.value = bodyVal;
        if (headerSelect && headerSelect.value !== bodyVal) headerSelect.value = bodyVal;
        
        const rows = document.querySelectorAll('.program-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const pills = row.querySelectorAll('.accreditation-pill');
            let hasMatch = false;
            
            pills.forEach(pill => {
                const subBody = pill.getAttribute('data-body');
                if (!filterVal || subBody === filterVal) {
                    pill.classList.remove('hidden');
                    hasMatch = true;
                } else {
                    pill.classList.add('hidden');
                }
            });
            
            if (hasMatch) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });
        
        // Update counts
        const counts = @json($accreditationCounts);
        const count = counts[bodyVal] !== undefined ? counts[bodyVal] : counts[''];
        const cardCountEl = document.getElementById('accredited-card-count');
        if (cardCountEl) cardCountEl.innerText = count;
        
        const dirCountEl = document.getElementById('directory-count');
        if (dirCountEl) dirCountEl.innerText = visibleCount;
        
        // Update tag in card
        const tagEl = document.getElementById('card-accreditor-tag');
        if (tagEl) {
            tagEl.innerText = bodyVal ? bodyVal : 'All';
        }
        
        // Show/hide no-matches placeholder
        const noMatchesEl = document.getElementById('no-accredited-matches');
        if (noMatchesEl) {
            if (visibleCount === 0) {
                noMatchesEl.classList.remove('hidden');
            } else {
                noMatchesEl.classList.add('hidden');
            }
        }
    }
    // ── Approval Detail Pop-Out Modal ─────────────────────────────────────
    function openApprovalDetailModal(row) {
        const id            = row.getAttribute('data-id');
        const programCode   = row.getAttribute('data-program-code');
        const title         = row.getAttribute('data-title');
        const description   = row.getAttribute('data-description') || 'No description provided.';
        const respUnit      = row.getAttribute('data-responsible-unit') || 'Unassigned';
        const contactPerson = row.getAttribute('data-contact-person') || 'None';
        const contactEmail = row.getAttribute('data-contact-email') || '';
        const body          = row.getAttribute('data-accrediting-body') || '';
        const school        = row.getAttribute('data-school') || '—';
        const recommendation = row.getAttribute('data-recommendation') || 'No recommendation statement recorded.';
        const category      = row.getAttribute('data-category') || '—';
        const area          = row.getAttribute('data-area') || '—';
        const currentStatus = row.getAttribute('data-current-status') || 'Pending';
        const actionPlan    = row.getAttribute('data-action-plan') || 'No action plan submitted.';
        const pendingLink   = row.getAttribute('data-pending-link') || '';
        const dueDate       = row.getAttribute('data-due-date') || 'No deadline';
        const visitDate     = row.getAttribute('data-visit-date') || '—';
        const submittedAt   = row.getAttribute('data-submitted-at') || '—';
        const approveUrl    = row.getAttribute('data-approve-url');

        // Parse recommendations JSON for checklist display
        let recommendations = [];
        try { recommendations = JSON.parse(row.getAttribute('data-recommendations') || '[]'); } catch(e) {}

        // Store for reject button usage
        window._admCurrentId    = id;
        window._admCurrentTitle = title;

        // Populate modal fields
        document.getElementById('adm-program-code').innerText   = programCode;
        document.getElementById('adm-body').innerText           = body;
        document.getElementById('adm-title').innerText          = title;
        document.getElementById('adm-current-status').innerText = currentStatus;

        // Build recommendation checklist display
        const recoContainer = document.getElementById('adm-recommendation');
        if (recommendations.length > 0) {
            let html = '<ul class="space-y-2">';
            recommendations.forEach(function(text) {
                html += '<li class="flex items-start gap-2 text-sm text-gray-700">' +
                    '<svg class="w-4 h-4 text-hau-maroon mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>' +
                    '<span>' + text + '</span></li>';
            });
            html += '</ul>';
            recoContainer.innerHTML = html;
        } else {
            recoContainer.innerText = recommendation;
        }

        document.getElementById('adm-area-cat').innerText       = [area, category].filter(Boolean).join(' \u2022 ') || '—';
        document.getElementById('adm-school').innerText         = school;
        document.getElementById('adm-resp-unit').innerText      = respUnit;
        document.getElementById('adm-contact-person').innerText = contactPerson;
        if (contactEmail) {
            document.getElementById('adm-contact-email').innerHTML = '<a href="mailto:' + contactEmail + '" class="text-hau-maroon hover:underline font-mono text-[10px]">' + contactEmail + '</a>';
        } else {
            document.getElementById('adm-contact-email').innerText = '';
        }
        document.getElementById('adm-submitted-at').innerText   = submittedAt;
        document.getElementById('adm-due-date').innerText       = dueDate;
        document.getElementById('adm-visit-date').innerText     = visitDate;
        document.getElementById('adm-description').innerText    = description;
        document.getElementById('adm-action-plan').innerText    = actionPlan;

        // Evidence link
        const evContainer = document.getElementById('adm-evidence-link-container');
        if (pendingLink) {
            evContainer.innerHTML = '<a href="' + pendingLink + '" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-hau-maroon/5 text-hau-maroon font-bold rounded-lg border border-hau-maroon/20 text-xs transition font-mono">Open Evidence <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></a>';
        } else {
            evContainer.innerHTML = '<span class="text-xs text-gray-400 italic">No evidence document attached.</span>';
        }

        // Wire up approve form
        document.getElementById('adm-approve-form').action = approveUrl;

        // Open the modal
        const modal = document.getElementById('approval-detail-modal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeApprovalDetailModal() {
        const modal = document.getElementById('approval-detail-modal');
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 150);
    }

    // Close modal when clicking the backdrop
    document.getElementById('approval-detail-modal').addEventListener('click', function(e) {
        if (e.target === this) closeApprovalDetailModal();
    });

    // ── Rejection Modal ───────────────────────────────────────────────────
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

    // ── Compliance Item Detail Modal ──────────────────────────────────────────
    function openComplianceDetailModal(item) {
        // Title
        document.getElementById('cdm-title').textContent = item.title || '—';

        // Status banner
        const banner = document.getElementById('cdm-status-banner');
        let bannerHtml = '';
        if (item.isOverdue) {
            bannerHtml = `<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-100 text-rose-700 text-xs font-bold rounded-full border border-rose-200"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>OVERDUE</span>`;
        } else if (item.isDueSoon) {
            bannerHtml = `<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full border border-amber-200">⚠ DUE SOON</span>`;
        }
        const statusClass = item.status === 'Non-Compliant'
            ? 'inline-flex px-2 py-0.5 rounded font-bold text-xs bg-rose-50 text-rose-700 border border-rose-100'
            : 'inline-flex px-2 py-0.5 rounded font-bold text-xs bg-amber-50 text-amber-700 border border-amber-100';
        bannerHtml += `<span class="${statusClass}">${item.status}</span>`;
        if (item.priority) {
            bannerHtml += `<span class="inline-flex px-2 py-0.5 rounded font-bold text-xs bg-gray-100 text-gray-600 border border-gray-200 ml-auto">Priority: ${item.priority}</span>`;
        }
        banner.innerHTML = bannerHtml;

        // Program
        document.getElementById('cdm-program').textContent = item.program || '—';
        document.getElementById('cdm-program-name').textContent = item.programName || '';
        document.getElementById('cdm-accreditor').textContent = item.accreditor || '—';
        document.getElementById('cdm-category').textContent = item.category || '—';
        document.getElementById('cdm-area').textContent = item.area || '—';
        document.getElementById('cdm-unit').textContent = item.responsibleUnit || '—';

        // Due date with colour
        const dueEl = document.getElementById('cdm-due');
        dueEl.textContent = item.dueDate || '—';
        dueEl.className = 'text-sm font-mono font-bold ' + (item.isOverdue ? 'text-rose-600' : item.isDueSoon ? 'text-amber-600' : 'text-gray-800');

        // Contact (show row only if data present)
        const contactRow = document.getElementById('cdm-contact-row');
        if (item.contactPerson || item.contactEmail) {
            document.getElementById('cdm-contact').textContent = item.contactPerson || '—';
            document.getElementById('cdm-email').textContent = item.contactEmail || '—';
            contactRow.classList.remove('hidden');
        } else {
            contactRow.classList.add('hidden');
        }

        // Description
        const descRow = document.getElementById('cdm-desc-row');
        if (item.description) {
            document.getElementById('cdm-description').textContent = item.description;
            descRow.classList.remove('hidden');
        } else {
            descRow.classList.add('hidden');
        }

        // Recommendation
        const recRow = document.getElementById('cdm-rec-row');
        if (item.recommendation) {
            document.getElementById('cdm-recommendation').textContent = item.recommendation;
            recRow.classList.remove('hidden');
        } else {
            recRow.classList.add('hidden');
        }

        // Action Plan
        const planRow = document.getElementById('cdm-plan-row');
        if (item.actionPlan) {
            document.getElementById('cdm-action-plan').textContent = item.actionPlan;
            planRow.classList.remove('hidden');
        } else {
            planRow.classList.add('hidden');
        }

        // Submit link
        document.getElementById('cdm-submit-btn').href = item.submitUrl;

        // Show modal
        const modal = document.getElementById('compliance-detail-modal');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeComplianceDetailModal() {
        document.getElementById('compliance-detail-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeComplianceDetailModal();
        }
    });


</script>
@endsection