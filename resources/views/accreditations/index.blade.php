@extends('layouts.app')

@section('content')
@php
    $bodies = $accreditations->pluck('accrediting_body')->unique()->sort()->values();
@endphp
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Accreditations Directory</h2>
            <p class="text-xs sm:text-sm text-gray-500">Manage program certificates, certification tiers, and upcoming evaluations.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openModal('add-accrediting-body-modal')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 hover:bg-gray-50 shadow-sm focus:outline-none transition font-bold">
                + Add Accrediting Body
            </button>
        </div>
    </div>

    <!-- Dashboard Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Accreditable Program(s) Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md relative group flex flex-col justify-between h-full">
            <div class="absolute -top-1 left-0 right-0 h-1 bg-hau-gold"></div>
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Accreditable Program(s)</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-hau-maroon font-mono leading-tight">
                                {{ $programs->where('is_accreditable', true)->count() }}
                            </span>
                            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Program(s)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-hau-gold/10 rounded-xl text-hau-gold-dark shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-3.5 border-t border-gray-100">
                <a href="{{ route('programs.index') }}" class="text-xs font-bold text-hau-maroon hover:text-hau-maroon-light transition inline-flex items-center gap-1">
                    Manage Programs &rarr;
                </a>
            </div>
        </div>

        <!-- Accredited Program(s) Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md relative group flex flex-col justify-between h-full">
            <div class="absolute -top-1 left-0 right-0 h-1 bg-hau-maroon"></div>
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Accredited Program(s)</p>
                        <div class="flex items-baseline gap-2">
                            <span id="card-total-accredited-count" class="text-3xl font-black text-gray-900 font-mono leading-tight">
                                {{ $accreditations->where('status', 'Active')->pluck('program_id')->unique()->count() }}
                            </span>
                            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Program(s)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-hau-maroon/5 rounded-xl text-hau-maroon shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-3 border-t border-gray-100">
                <select id="card-filter-body-select" onchange="updateCardFilter(this.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer font-bold text-gray-700">
                    <option value="">All Accrediting Bodies</option>
                    @foreach($bodies as $body)
                        <option value="{{ $body }}">{{ $body }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Accreditation Type Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md relative group flex flex-col justify-between h-full">
            <div class="absolute -top-1 left-0 right-0 h-1 bg-hau-maroon"></div>
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Accreditation Type</p>
                        <div class="flex items-baseline gap-2">
                            <span id="card-type-count" class="text-3xl font-black text-gray-900 font-mono leading-tight">
                                {{ $accreditations->where('status', 'Active')->pluck('program_id')->unique()->count() }}
                            </span>
                            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Program(s)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-hau-maroon/5 rounded-xl text-hau-maroon shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-3 border-t border-gray-100">
                <select id="card-filter-type-select" onchange="updateCardTypeFilter(this.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer font-bold text-gray-700">
                    <option value="">All Types</option>
                    <option value="Local">Local</option>
                    <option value="International">International</option>
                    <option value="Regulatory">Regulatory</option>
                </select>
            </div>
        </div>

        <!-- Expired/Expiring Soon Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md flex flex-col justify-between h-full">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Expired / Expiring</p>
                        <p class="text-3xl font-black text-rose-600 font-mono leading-tight">{{ $expiringOrExpired }}</p>
                    </div>
                    <div class="p-3 bg-rose-50 rounded-xl text-rose-500 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50/50 px-6 py-3.5 border-t border-gray-100">
                <button onclick="filterByExpiring()" class="text-xs font-bold text-rose-600 hover:text-rose-700 transition inline-flex items-center gap-1">
                    View Warnings &rarr;
                </button>
            </div>
        </div>
    </div>


    <!-- Recommendation Accomplishment Breakdown -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 space-y-3">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Recommendation Accomplishment by Type</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    <span>Local Recommendation(s)</span>
                    <span class="font-mono text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded font-black">{{ $localPercentage }}%</span>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-hau-maroon h-full rounded-full transition-all duration-500" style="width: {{ $localPercentage }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    <span>International Recommendation(s)</span>
                    <span class="font-mono text-hau-gold-dark bg-hau-gold/10 px-2 py-0.5 rounded font-black">{{ $intlPercentage }}%</span>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-hau-gold h-full rounded-full transition-all duration-500" style="width: {{ $intlPercentage }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    <span>Regulatory Recommendation(s)</span>
                    <span class="font-mono text-gray-600 bg-gray-100 px-2 py-0.5 rounded font-black">{{ $regulatoryPercentage }}%</span>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-gray-500 h-full rounded-full transition-all duration-500" style="width: {{ $regulatoryPercentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Realtime Filters -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-grow">
            <!-- Search -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="filter-search" oninput="applyFilters()" placeholder="Search program code, level..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>

            <!-- Accrediting Body Dropdown -->
            <div>
                <select id="filter-body" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Accrediting Bodies</option>
                    @foreach($bodies as $body)
                        <option value="{{ $body }}">{{ $body }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Type Selector -->
            <div>
                <select id="filter-type" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Types</option>
                    <option value="Local">Local</option>
                    <option value="International">International</option>
                    <option value="Regulatory">Regulatory</option>
                </select>
            </div>

            <!-- Status Selector -->
            <div>
                <select id="filter-status" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Expiring Soon">Expiring Soon</option>
                    <option value="Expired">Expired</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            <!-- School / College Selector -->
            <div>
                <select id="filter-college" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer font-medium text-gray-700">
                    <option value="">All Schools / Colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->college_id }}">{{ $college->code ? $college->code . ' - ' : '' }}{{ $college->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Add Button -->
        <div>
            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 bg-hau-maroon border border-transparent text-sm font-semibold rounded-lg text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                + Add Accreditation
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[220px]">Program</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Accrediting Body</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Level/Tier</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Certificate</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Last Visit</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Expiry</th>
                        <th scope="col" class="px-3 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-3 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Actions</th>
                    </tr>
                </thead>
                <tbody id="accreditation-rows" class="divide-y divide-gray-200 bg-white">
                    @forelse ($accreditations as $a)
                        <tr class="hover:bg-gray-50/50 transition duration-100"
                            data-id="{{ $a->id }}"
                            data-program-id="{{ $a->program_id }}"
                            data-program-code="{{ $a->program->program_code }}"
                            data-program-name="{{ $a->program->program_name }}"
                            data-college-id="{{ $a->program?->college_id ?? '' }}"
                            data-college-name="{{ strtolower($a->program?->college?->name ?? '') }}"
                            data-college-code="{{ strtolower($a->program?->college?->code ?? '') }}"
                            data-accrediting-body="{{ $a->accrediting_body }}"
                            data-type="{{ $a->type }}"
                            data-level-tier="{{ $a->level_or_tier }}"
                            data-last-visit="{{ $a->last_visit ? $a->last_visit->format('Y-m-d') : '' }}"
                            data-expiry-date="{{ $a->expiry_date ? $a->expiry_date->format('Y-m-d') : '' }}"
                            data-status="{{ $a->status }}"
                            data-certificate-link="{{ $a->certificate_link ?? '' }}"
                            data-certificate-file="{{ $a->certificate_file ? asset('storage/' . $a->certificate_file) : '' }}"
                            data-certificate-file-name="{{ $a->certificate_file ? basename($a->certificate_file) : '' }}">

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-hau-maroon text-sm hover:underline cursor-pointer"
                                         onclick="viewProgramDetails({{ $a->program_id }})">
                                        {{ $a->program->program_code }}
                                    </span>
                                    @if($a->program?->college)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-hau-maroon/5 text-hau-maroon border border-hau-maroon/15 cursor-help" title="{{ $a->program->college->name }}">
                                            {{ $a->program->college->code ?? 'School' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-600 mt-0.5 leading-snug" title="{{ $a->program->program_name }} @if($a->program?->college) ({{ $a->program->college->name }}) @endif">
                                    {{ $a->program->program_name }}
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-gray-900 text-sm whitespace-nowrap">{{ $a->accrediting_body }}</div>
                            </td>
                            <td class="px-3 py-3 text-xs font-semibold whitespace-nowrap">
                                @if($a->type == 'Local')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-800">Local</span>
                                @elseif($a->type == 'International')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-100">International</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700">Regulatory</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-600 hidden md:table-cell whitespace-nowrap">
                                {{ $a->level_or_tier ?? '—' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($a->certificate_file)
                                    <button type="button" 
                                        onclick="openCertificatePreview('{{ asset('storage/' . $a->certificate_file) }}', '{{ $a->program->program_code }} — {{ $a->accrediting_body }} Certificate', 'file', '{{ $a->certificate_link }}')"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg bg-hau-maroon/10 text-hau-maroon hover:bg-hau-maroon hover:text-white transition shadow-2xs border border-hau-maroon/20 cursor-pointer"
                                        title="Preview Certificate Document">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Preview</span>
                                    </button>
                                @elseif($a->certificate_link)
                                    <a href="{{ $a->certificate_link }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition border border-blue-200 cursor-pointer"
                                        title="Open SharePoint / Cloud Link">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        <span>SharePoint</span>
                                    </a>
                                @else
                                    <span class="text-xs text-gray-300 italic">None</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-500 hidden lg:table-cell font-mono whitespace-nowrap">
                                {{ $a->last_visit ? $a->last_visit->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-500 hidden lg:table-cell font-mono whitespace-nowrap">
                                {{ $a->expiry_date ? $a->expiry_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-3 py-3 text-xs font-semibold whitespace-nowrap">
                                @if ($a->status == 'Active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">Active</span>
                                @elseif ($a->status == 'Expiring Soon')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">Expiring Soon</span>
                                @elseif ($a->status == 'Expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-100">Expired</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">Pending</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick="openEditModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('accreditations.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this accreditation?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-gray-500 hover:text-rose-600 hover:bg-gray-100 rounded-lg transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="9" class="text-center text-gray-400 py-12 text-sm">No accreditations found in database. Seed sample data or add a record.</td>
                        </tr>
                    @endforelse

                    <tr id="no-matches-row" class="hidden">
                        <td colspan="9" class="text-center text-gray-400 py-12 text-sm">No accreditations match the current filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer Bar -->
        <div id="accreditations-pagination-bar" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3.5 bg-white border-t border-gray-200">
            <!-- Results counter -->
            <p id="accreditations-pagination-info" class="text-xs text-gray-500 font-medium"></p>

            <!-- Page controls -->
            <div id="accreditations-pagination-controls" class="flex items-center gap-1"></div>
        </div>
    </div>
<!-- ================= MODAL WINDOWS ================= -->

<!-- 1. Add Accreditation Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-2xl overflow-hidden transform scale-95 transition-all duration-200">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Add Accreditation</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Record new program accreditation status and certificate</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('add-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" title="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('accreditations.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div>
                    <label for="add-program_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Academic Program <span class="text-rose-500">*</span></label>
                    <select name="program_id" id="add-program_id" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                        <option value="">Select a Program</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" {{ !$p->is_accreditable ? 'disabled' : '' }}>
                                {{ $p->program_code }} &mdash; {{ $p->program_name }} {{ !$p->is_accreditable ? '(Non-Accreditable)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-accrediting_body" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Accrediting Body <span class="text-rose-500">*</span></label>
                        <select name="accrediting_body" id="add-accrediting_body" required onchange="onAccreditingBodyChange(this, 'add-type')" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            <option value="" disabled selected>Select Accrediting Body</option>
                            @foreach($accreditingBodies as $body)
                                <option value="{{ $body->code }}" data-type="{{ $body->type }}">{{ $body->code }} &mdash; {{ $body->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="add-type" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-rose-500">*</span></label>
                        <select name="type" id="add-type" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            <option value="Local">Local</option>
                            <option value="International">International</option>
                            <option value="Regulatory">Regulatory</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-level_or_tier" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Level / Tier</label>
                        <input type="text" name="level_or_tier" id="add-level_or_tier" placeholder="e.g. Level III" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                    <div>
                        <label for="add-status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select name="status" id="add-status" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            <option value="Active">Active</option>
                            <option value="Expiring Soon">Expiring Soon</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending" selected>Pending</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-last_visit" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Last Visit Date</label>
                        <input type="date" name="last_visit" id="add-last_visit" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                    <div>
                        <label for="add-expiry_date" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Expiry Date</label>
                        <input type="date" name="expiry_date" id="add-expiry_date" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                </div>

                <!-- Hybrid Certificate Attachment Section -->
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Accreditation Certificate</label>
                        <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">File or Cloud Link</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                </div>
                                <input type="url" name="certificate_link" id="add-certificate_link" 
                                    placeholder="Paste SharePoint / OneDrive link (https://...)" 
                                    class="block w-full pl-9 pr-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition">
                            </div>
                        </div>

                        <div class="relative flex py-1 items-center">
                            <div class="grow border-t border-gray-200"></div>
                            <span class="shrink-0 mx-2 text-[10px] font-bold text-gray-400 uppercase">OR Upload File</span>
                            <div class="grow border-t border-gray-200"></div>
                        </div>

                        <div>
                            <label for="add-certificate_file" class="group flex items-center justify-between p-3.5 border-2 border-dashed border-gray-300 hover:border-hau-maroon/60 rounded-xl bg-gray-50/70 hover:bg-hau-maroon/5 transition-all cursor-pointer">
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <div class="w-9 h-9 rounded-xl bg-hau-maroon/10 text-hau-maroon flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p id="add-file-name-label" class="text-xs font-semibold text-gray-700 truncate group-hover:text-hau-maroon transition-colors">Choose certificate file to upload</p>
                                        <p id="add-file-hint-label" class="text-[11px] text-gray-400">PDF, PNG, JPG (Max: 10MB)</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3.5 py-1.5 bg-hau-maroon text-white text-xs font-bold rounded-xl shadow-xs group-hover:bg-hau-maroon-dark transition-colors shrink-0">
                                    Browse File
                                </span>
                            </label>
                            <input type="file" name="certificate_file" id="add-certificate_file" accept=".pdf,.png,.jpg,.jpeg" class="hidden" onchange="handleCertificateFileChange(this, 'add-file-name-label', 'add-file-hint-label')">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition cursor-pointer">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm focus:outline-none transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Accreditation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Edit Accreditation Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-2xl overflow-hidden transform scale-95 transition-all duration-200">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Edit Accreditation</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Update program accreditation status and certificate document</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" title="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form" action="" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div>
                    <label for="edit-program_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Academic Program <span class="text-rose-500">*</span></label>
                    <select name="program_id" id="edit-program_id" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" {{ !$p->is_accreditable ? 'disabled' : '' }}>
                                {{ $p->program_code }} &mdash; {{ $p->program_name }} {{ !$p->is_accreditable ? '(Non-Accreditable)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-accrediting_body" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Accrediting Body <span class="text-rose-500">*</span></label>
                        <select name="accrediting_body" id="edit-accrediting_body" required onchange="onAccreditingBodyChange(this, 'edit-type')" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            @foreach($accreditingBodies as $body)
                                <option value="{{ $body->code }}" data-type="{{ $body->type }}">{{ $body->code }} &mdash; {{ $body->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit-type" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-rose-500">*</span></label>
                        <select name="type" id="edit-type" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            <option value="Local">Local</option>
                            <option value="International">International</option>
                            <option value="Regulatory">Regulatory</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-level_or_tier" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Level / Tier</label>
                        <input type="text" name="level_or_tier" id="edit-level_or_tier" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition placeholder-gray-400" />
                    </div>
                    <div>
                        <label for="edit-status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select name="status" id="edit-status" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition cursor-pointer">
                            <option value="Active">Active</option>
                            <option value="Expiring Soon">Expiring Soon</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-last_visit" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Last Visit Date</label>
                        <input type="date" name="last_visit" id="edit-last_visit" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                    <div>
                        <label for="edit-expiry_date" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Expiry Date</label>
                        <input type="date" name="expiry_date" id="edit-expiry_date" class="block w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                </div>

                <!-- Hybrid Certificate Attachment Section (Edit) -->
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Accreditation Certificate</label>
                        <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">File or Cloud Link</span>
                    </div>

                    <!-- Current File Display -->
                    <div id="edit-current-file-container" class="hidden mb-3 p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <svg class="w-4 h-4 text-hau-maroon shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span id="edit-current-file-name" class="text-xs font-semibold text-gray-700 truncate"></span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button type="button" onclick="previewCurrentEditFile()" class="text-xs font-bold text-hau-maroon hover:underline cursor-pointer">Preview</button>
                            <label class="flex items-center gap-1.5 text-xs text-rose-600 font-medium cursor-pointer">
                                <input type="checkbox" name="remove_certificate_file" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                <span>Remove</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                </div>
                                <input type="url" name="certificate_link" id="edit-certificate_link" 
                                    placeholder="Paste SharePoint / OneDrive link (https://...)" 
                                    class="block w-full pl-9 pr-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition">
                            </div>
                        </div>

                        <div class="relative flex py-1 items-center">
                            <div class="grow border-t border-gray-200"></div>
                            <span class="shrink-0 mx-2 text-[10px] font-bold text-gray-400 uppercase">OR Replace / Upload File</span>
                            <div class="grow border-t border-gray-200"></div>
                        </div>

                        <div>
                            <label for="edit-certificate_file" class="group flex items-center justify-between p-3.5 border-2 border-dashed border-gray-300 hover:border-hau-maroon/60 rounded-xl bg-gray-50/70 hover:bg-hau-maroon/5 transition-all cursor-pointer">
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <div class="w-9 h-9 rounded-xl bg-hau-maroon/10 text-hau-maroon flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p id="edit-file-name-label" class="text-xs font-semibold text-gray-700 truncate group-hover:text-hau-maroon transition-colors">Choose replacement certificate file</p>
                                        <p id="edit-file-hint-label" class="text-[11px] text-gray-400">PDF, PNG, JPG (Max: 10MB)</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3.5 py-1.5 bg-hau-maroon text-white text-xs font-bold rounded-xl shadow-xs group-hover:bg-hau-maroon-dark transition-colors shrink-0">
                                    Browse File
                                </span>
                            </label>
                            <input type="file" name="certificate_file" id="edit-certificate_file" accept=".pdf,.png,.jpg,.jpeg" class="hidden" onchange="handleCertificateFileChange(this, 'edit-file-name-label', 'edit-file-hint-label')">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition cursor-pointer">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm focus:outline-none transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Update Accreditation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Certificate Preview Modal -->
<div id="certificate-preview-modal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-gray-900/75 backdrop-blur-xs hidden transition duration-150 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full overflow-hidden transform scale-95 transition-all duration-200 flex flex-col my-auto" style="max-width: 900px; height: calc(100vh - 60px); max-height: 820px;">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10 shrink-0" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5 min-w-0 pr-2">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 id="cert-preview-title" class="text-base font-bold text-white tracking-wide truncate" style="color: #ffffff;">Accreditation Certificate</h3>
                    <p id="cert-preview-subtitle" class="text-xs text-white/80 font-normal mt-0.5 truncate" style="color: rgba(255, 255, 255, 0.85);">Document preview and verification</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" onclick="closeModal('certificate-preview-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" title="Close Preview">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Scrollable & Contained Content Body -->
        <div class="p-4 sm:p-6 bg-slate-100/80 flex-1 min-h-0 overflow-y-auto flex flex-col">
            <!-- PDF / Document Iframe Container -->
            <iframe id="cert-preview-frame" src="" class="w-full flex-1 min-h-[480px] bg-white rounded-xl shadow-xs border border-gray-200 hidden"></iframe>
            
            <!-- Image Container -->
            <div id="cert-preview-img-container" class="hidden w-full flex-1 min-h-0 flex flex-col items-center justify-start p-1">
                <img id="cert-preview-img" src="" alt="Accreditation Certificate" class="max-w-full max-h-full h-auto w-auto object-contain rounded-xl shadow-md border border-gray-200 bg-white m-auto block">
            </div>

            <!-- SharePoint Link Fallback Container -->
            <div id="cert-preview-link-container" class="hidden bg-white p-6 rounded-2xl border border-gray-200 text-center max-w-md w-full m-auto shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-gray-900 mb-1">Cloud Stored Document</h4>
                <p class="text-xs text-gray-500 mb-4">This certificate is securely hosted on Microsoft SharePoint / OneDrive cloud storage.</p>
                <a id="cert-preview-sharepoint-btn" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Open in SharePoint / OneDrive</span>
                </a>
            </div>
        </div>

        <!-- Footer Actions Bar -->
        <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-150 flex items-center justify-between shrink-0">
            <a id="cert-preview-open-link" href="#" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 hover:text-hau-maroon text-gray-700 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 transition shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Open in New Tab</span>
            </a>
            <button type="button" onclick="closeModal('certificate-preview-modal')" class="px-5 py-2 border border-gray-300 hover:bg-white text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Add Accrediting Body Modal -->
<div id="add-accrediting-body-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-200">
        <!-- Header with dark maroon gradient -->
        <div class="modal-dark-header flex items-center justify-between border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Add Accrediting Body</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Register an accreditation organization or agency</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('add-accrediting-body-modal')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" title="Close">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('accrediting-bodies.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                <div>
                    <label for="add-body-name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Accrediting Body Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="add-body-name" required placeholder="e.g. Philippine Accrediting Association of Schools, Colleges and Universities" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                </div>
                <div>
                    <label for="add-body-code" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Acronym / Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="add-body-code" required placeholder="e.g. PAASCU" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm uppercase text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                </div>
                <div>
                    <label for="add-body-type" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-rose-500">*</span></label>
                    <select name="type" id="add-body-type" required class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition">
                        <option value="Local" selected>Local</option>
                        <option value="International">International</option>
                        <option value="Regulatory">Regulatory</option>
                    </select>
                </div>
                <div>
                    <label for="add-body-description" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Description (Optional)</label>
                    <textarea name="description" id="add-body-description" rows="2" placeholder="Provide any details about this accrediting body..." class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Standard Areas (Optional)</label>
                    <div id="add-body-areas-list" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" name="areas[]" placeholder="e.g. Area I: Philosophy and Objectives" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                            <button type="button" onclick="this.closest('.flex').remove()" class="p-2 text-gray-400 hover:text-rose-600 rounded-xl transition shrink-0" title="Remove">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" onclick="addBodyAreaInput()" class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-hau-maroon hover:text-hau-maroon-dark transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add another area
                    </button>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closeModal('add-accrediting-body-modal')" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Body
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
    function addBodyAreaInput() {
        const container = document.getElementById('add-body-areas-list');
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 mt-2';
        div.innerHTML = `
            <input type="text" name="areas[]" placeholder="e.g. Next Area" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            <button type="button" onclick="this.closest('.flex').remove()" class="p-1.5 text-gray-400 hover:text-rose-600 rounded transition shrink-0" title="Remove">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;
        container.appendChild(div);
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

    let _currentEditCertFile = '';
    let _currentEditCertName = '';

    function openCertificatePreview(url, title, type, cloudLink) {
        const modal = document.getElementById('certificate-preview-modal');
        const titleEl = document.getElementById('cert-preview-title');
        const subtitleEl = document.getElementById('cert-preview-subtitle');
        const frame = document.getElementById('cert-preview-frame');
        const imgContainer = document.getElementById('cert-preview-img-container');
        const img = document.getElementById('cert-preview-img');
        const linkContainer = document.getElementById('cert-preview-link-container');
        const openLink = document.getElementById('cert-preview-open-link');
        const spBtn = document.getElementById('cert-preview-sharepoint-btn');

        titleEl.textContent = title || 'Accreditation Certificate';
        openLink.href = url || cloudLink || '#';

        frame.classList.add('hidden');
        frame.src = '';
        imgContainer.classList.add('hidden');
        img.src = '';
        linkContainer.classList.add('hidden');

        if (type === 'file' && url) {
            const isImage = url.match(/\.(jpeg|jpg|png|gif|webp)$/i);
            if (isImage) {
                img.src = url;
                imgContainer.classList.remove('hidden');
                subtitleEl.textContent = 'Image Certificate Document';
            } else {
                frame.src = url;
                frame.classList.remove('hidden');
                subtitleEl.textContent = 'PDF Certificate Document';
            }
        } else if (cloudLink || (type === 'link' && url)) {
            const targetUrl = cloudLink || url;
            spBtn.href = targetUrl;
            linkContainer.classList.remove('hidden');
            subtitleEl.textContent = 'SharePoint / OneDrive Link';
        }

        openModal('certificate-preview-modal');
    }

    function previewCurrentEditFile() {
        if (_currentEditCertFile) {
            openCertificatePreview(_currentEditCertFile, _currentEditCertName || 'Current Certificate', 'file', null);
        }
    }

    function onAccreditingBodyChange(selectEl, targetTypeId) {
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        if (selectedOption && selectedOption.dataset && selectedOption.dataset.type) {
            const typeSelect = document.getElementById(targetTypeId);
            if (typeSelect) {
                typeSelect.value = selectedOption.dataset.type;
            }
        }
    }

    function handleCertificateFileChange(input, labelId, hintId) {
        const labelEl = document.getElementById(labelId);
        const hintEl = document.getElementById(hintId);
        if (!labelEl) return;
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            labelEl.innerHTML = `<span class="text-emerald-700 font-bold flex items-center gap-1.5"><svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> ${file.name}</span>`;
            if (hintEl) hintEl.textContent = `Selected: ${fileSizeMB} MB`;
        } else {
            labelEl.textContent = labelId.includes('edit') ? 'Choose replacement certificate file' : 'Choose certificate file to upload';
            if (hintEl) hintEl.textContent = 'PDF, PNG, JPG (Max: 10MB)';
        }
    }

    function openAddModal() {
        const bodySelect = document.getElementById('add-accrediting_body');
        if (bodySelect) bodySelect.selectedIndex = 0;
        const typeSelect = document.getElementById('add-type');
        if (typeSelect) typeSelect.value = 'Local';

        const linkInput = document.getElementById('add-certificate_link');
        if (linkInput) linkInput.value = '';
        const fileInput = document.getElementById('add-certificate_file');
        if (fileInput) fileInput.value = '';
        
        const labelEl = document.getElementById('add-file-name-label');
        if (labelEl) labelEl.textContent = 'Choose certificate file to upload';
        const hintEl = document.getElementById('add-file-hint-label');
        if (hintEl) hintEl.textContent = 'PDF, PNG, JPG (Max: 10MB)';
        
        openModal('add-modal');
    }

    function openEditModal(row) {
        const id          = row.getAttribute('data-id');
        const programId   = row.getAttribute('data-program-id');
        const body        = row.getAttribute('data-accrediting-body');
        const type        = row.getAttribute('data-type');
        const tier        = row.getAttribute('data-level-tier');
        const lastVisit   = row.getAttribute('data-last-visit');
        const expiry      = row.getAttribute('data-expiry-date');
        const status      = row.getAttribute('data-status');
        const certLink    = row.getAttribute('data-certificate-link') || '';
        const certFile    = row.getAttribute('data-certificate-file') || '';
        const certFileName = row.getAttribute('data-certificate-file-name') || '';

        document.getElementById('edit-program_id').value      = programId;
        document.getElementById('edit-accrediting_body').value = body;
        document.getElementById('edit-type').value            = type;
        document.getElementById('edit-level_or_tier').value   = tier;
        document.getElementById('edit-last_visit').value      = lastVisit;
        document.getElementById('edit-expiry_date').value     = expiry;
        document.getElementById('edit-status').value          = status;

        _currentEditCertFile = certFile;
        _currentEditCertName = `${row.getAttribute('data-program-code')} — ${body} Certificate`;

        const linkInput = document.getElementById('edit-certificate_link');
        if (linkInput) linkInput.value = certLink;
        const fileInput = document.getElementById('edit-certificate_file');
        if (fileInput) fileInput.value = '';
        
        const labelEl = document.getElementById('edit-file-name-label');
        if (labelEl) labelEl.textContent = 'Choose replacement certificate file';
        const hintEl = document.getElementById('edit-file-hint-label');
        if (hintEl) hintEl.textContent = 'PDF, PNG, JPG (Max: 10MB)';
        
        const currentFileContainer = document.getElementById('edit-current-file-container');
        const currentFileNameEl = document.getElementById('edit-current-file-name');
        const removeCheckbox = document.querySelector('input[name="remove_certificate_file"]');
        if (removeCheckbox) removeCheckbox.checked = false;

        if (certFile) {
            currentFileContainer.classList.remove('hidden');
            currentFileNameEl.textContent = certFileName || 'Attached Certificate File';
        } else {
            currentFileContainer.classList.add('hidden');
        }

        document.getElementById('edit-form').action = `/accreditations/${id}`;
        openModal('edit-modal');
    }

    function viewProgramDetails(id) {
        window.location.href = `/programs/${id}`;
    }

    function filterByStatus(statusVal) {
        document.getElementById('filter-status').dataset.expiryMode = '';
        document.getElementById('filter-status').value = statusVal;
        applyFilters();
    }

    function filterByExpiring() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-status').dataset.expiryMode = 'true';
        applyFilters();
    }

    function updateCardFilter(bodyVal) {
        const cardSelect = document.getElementById('card-filter-body-select');
        if (cardSelect && cardSelect.value !== bodyVal) cardSelect.value = bodyVal;
        
        const mainSelect = document.getElementById('filter-body');
        if (mainSelect && mainSelect.value !== bodyVal) {
            mainSelect.value = bodyVal;
        }
        applyFilters();
    }

    function updateCardTypeFilter(typeVal) {
        const cardSelect = document.getElementById('card-filter-type-select');
        if (cardSelect && cardSelect.value !== typeVal) cardSelect.value = typeVal;
        
        const mainSelect = document.getElementById('filter-type');
        if (mainSelect && mainSelect.value !== typeVal) {
            mainSelect.value = typeVal;
        }
        applyFilters();
    }

    function applyFilters() {
        const totalAccreditableCount = {{ $programs->where('is_accreditable', true)->count() }};
        const searchInput    = document.getElementById('filter-search').value.toLowerCase();
        const bodyFilter     = document.getElementById('filter-body').value.toLowerCase();
        const typeFilter     = document.getElementById('filter-type').value;
        const collegeFilter  = document.getElementById('filter-college') ? document.getElementById('filter-college').value : '';
        const statusEl       = document.getElementById('filter-status');
        const statusFilter   = statusEl.value;
        // FIX 3 (continued): read the expiry mode flag to match both Expired + Expiring Soon
        const expiryMode     = statusEl.dataset.expiryMode === 'true';

        // Sync main body filter value back to card select and re-calculate count
        const cardSelect = document.getElementById('card-filter-body-select');
        const mainBodyVal = document.getElementById('filter-body').value;
        if (cardSelect && cardSelect.value !== mainBodyVal) {
            cardSelect.value = mainBodyVal;
        }

        // Sync main type filter value back to card type select
        const cardTypeSelect = document.getElementById('card-filter-type-select');
        const mainTypeVal = document.getElementById('filter-type').value;
        if (cardTypeSelect && cardTypeSelect.value !== mainTypeVal) {
            cardTypeSelect.value = mainTypeVal;
        }

        const rows = document.querySelectorAll('#accreditation-rows tr[data-id]');
        
        // Recompute unique program count for the accredited programs card and type breakdown
        const filterVal = mainBodyVal.toLowerCase();
        const uniqueAllAccredited = new Set();
        const uniqueLocalAccredited = new Set();
        const uniqueIntlAccredited = new Set();
        const uniqueRegAccredited = new Set();
        let activeAccreditationsCount = 0;

        rows.forEach(row => {
            const rowBody = row.getAttribute('data-accrediting-body').toLowerCase();
            const progId = row.getAttribute('data-program-id');
            const rowType = row.getAttribute('data-type');
            const rowStatus = row.getAttribute('data-status');
            
            if (rowStatus === 'Active') {
                if (!filterVal || rowBody === filterVal) {
                    activeAccreditationsCount++;
                    uniqueAllAccredited.add(progId);
                    if (rowType === 'Local') {
                        uniqueLocalAccredited.add(progId);
                    } else if (rowType === 'International') {
                        uniqueIntlAccredited.add(progId);
                    } else if (rowType === 'Regulatory') {
                        uniqueRegAccredited.add(progId);
                    }
                }
            }
        });
        const totalAccreditedEl = document.getElementById('card-total-accredited-count');
        if (totalAccreditedEl) totalAccreditedEl.innerText = uniqueAllAccredited.size;
        
        let typeCount = uniqueAllAccredited.size;
        if (mainTypeVal === 'Local') {
            typeCount = uniqueLocalAccredited.size;
        } else if (mainTypeVal === 'International') {
            typeCount = uniqueIntlAccredited.size;
        } else if (mainTypeVal === 'Regulatory') {
            typeCount = uniqueRegAccredited.size;
        }
        const cardTypeCountEl = document.getElementById('card-type-count');
        if (cardTypeCountEl) cardTypeCountEl.innerText = typeCount;

        accFilteredRows = [];

        rows.forEach(row => {
            const code    = row.getAttribute('data-program-code').toLowerCase();
            const name    = row.getAttribute('data-program-name').toLowerCase();
            const body    = row.getAttribute('data-accrediting-body').toLowerCase();
            const type    = row.getAttribute('data-type');
            const status  = row.getAttribute('data-status');
            const colId   = row.getAttribute('data-college-id') || '';
            const colName = (row.getAttribute('data-college-name') || '').toLowerCase();
            const colCode = (row.getAttribute('data-college-code') || '').toLowerCase();

            const matchesSearch  = !searchInput || code.includes(searchInput) || name.includes(searchInput) || body.includes(searchInput) || colName.includes(searchInput) || colCode.includes(searchInput);
            const matchesBody    = !bodyFilter  || body === bodyFilter;
            const matchesType    = !typeFilter  || type === typeFilter;
            const matchesStatus  = expiryMode
                ? (status === 'Expired' || status === 'Expiring Soon')
                : (!statusFilter || status === statusFilter);
            const matchesCollege = !collegeFilter || colId === collegeFilter;

            if (matchesSearch && matchesBody && matchesType && matchesStatus && matchesCollege) {
                accFilteredRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        const matchesCount = accFilteredRows.length;
        const emptyPlaceholder    = document.getElementById('empty-row');
        const noMatchesPlaceholder = document.getElementById('no-matches-row');

        if (matchesCount === 0) {
            if (emptyPlaceholder && rows.length === 0) {
                emptyPlaceholder.classList.remove('hidden');
                noMatchesPlaceholder.classList.add('hidden');
            } else {
                noMatchesPlaceholder.classList.remove('hidden');
                if (emptyPlaceholder) emptyPlaceholder.classList.add('hidden');
            }
        } else {
            if (emptyPlaceholder) emptyPlaceholder.classList.add('hidden');
            noMatchesPlaceholder.classList.add('hidden');
        }

        renderAccPagination();
    }

    // ===== ACCREDITATIONS CLIENT-SIDE PAGINATION =====
    const ACC_ITEMS_PER_PAGE = 10;
    let accCurrentPage = 1;
    let accFilteredRows = [];

    function goToAccPage(page) {
        accCurrentPage = page;
        renderAccPagination();
        const table = document.querySelector('table');
        if (table) {
            table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function renderAccPagination() {
        const totalItems = accFilteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / ACC_ITEMS_PER_PAGE));

        if (accCurrentPage > totalPages) accCurrentPage = totalPages;
        if (accCurrentPage < 1) accCurrentPage = 1;

        const start = (accCurrentPage - 1) * ACC_ITEMS_PER_PAGE;
        const end   = Math.min(start + ACC_ITEMS_PER_PAGE, totalItems);

        const allRows = document.querySelectorAll('#accreditation-rows tr[data-id]');
        allRows.forEach(row => {
            row.style.display = 'none';
        });

        accFilteredRows.forEach((row, idx) => {
            if (idx >= start && idx < end) {
                row.style.display = '';
            }
        });

        const infoEl = document.getElementById('accreditations-pagination-info');
        if (infoEl) {
            if (totalItems === 0) {
                infoEl.textContent = '';
            } else {
                infoEl.textContent = `Showing ${start + 1}–${end} of ${totalItems} program accreditation${totalItems !== 1 ? 's' : ''}`;
            }
        }

        const controlsEl = document.getElementById('accreditations-pagination-controls');
        if (!controlsEl) return;

        const btnBase = 'inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold transition focus:outline-none cursor-pointer';
        const btnActive = btnBase + ' bg-hau-maroon text-white shadow-sm';
        const btnInactive = btnBase + ' text-gray-600 hover:bg-gray-100 border border-gray-200 bg-white';
        const btnDisabled = btnBase + ' text-gray-300 cursor-not-allowed border border-gray-100 bg-gray-50';

        let pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (accCurrentPage > 3) pages.push('...');
            const lo = Math.max(2, accCurrentPage - 1);
            const hi = Math.min(totalPages - 1, accCurrentPage + 1);
            for (let i = lo; i <= hi; i++) pages.push(i);
            if (accCurrentPage < totalPages - 2) pages.push('...');
            pages.push(totalPages);
        }

        let html = '';

        // Prev button
        if (accCurrentPage <= 1) {
            html += `<button type="button" disabled class="${btnDisabled} mr-1" aria-label="Previous page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>`;
        } else {
            html += `<button type="button" onclick="goToAccPage(${accCurrentPage - 1})" class="${btnInactive} mr-1" aria-label="Previous page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>`;
        }

        // Page buttons
        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="inline-flex items-center justify-center w-8 h-8 text-xs text-gray-400 select-none">…</span>`;
            } else {
                html += `<button type="button" onclick="goToAccPage(${p})" class="${p === accCurrentPage ? btnActive : btnInactive}" aria-label="Page ${p}">${p}</button>`;
            }
        });

        // Next button
        if (accCurrentPage >= totalPages) {
            html += `<button type="button" disabled class="${btnDisabled} ml-1" aria-label="Next page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>`;
        } else {
            html += `<button type="button" onclick="goToAccPage(${accCurrentPage + 1})" class="${btnInactive} ml-1" aria-label="Next page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>`;
        }

        controlsEl.innerHTML = html;

        const bar = document.getElementById('accreditations-pagination-bar');
        if (bar) {
            bar.style.display = totalItems > 0 ? '' : 'none';
        }
    }

    // Clear expiry mode when the user manually changes the status dropdown
    document.getElementById('filter-status').addEventListener('change', function () {
        this.dataset.expiryMode = '';
    });

    // Initialize pagination on load
    document.addEventListener('DOMContentLoaded', function() {
        applyFilters();
    });
</script>
@endsection