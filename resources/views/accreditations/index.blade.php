@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Accreditations Directory</h2>
            <p class="text-xs sm:text-sm text-gray-500">Manage program certificates, certification tiers, and upcoming evaluations.</p>
        </div>
    </div>

    <!-- Dashboard Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Programs Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md">
            <div class="p-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Programs</p>
                    <p class="mt-2 text-3xl font-black text-gray-900 font-mono">{{ $totalPrograms }}</p>
                </div>
                <div class="p-3 bg-hau-maroon/10 rounded-lg text-hau-maroon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                <a href="{{ route('programs.index') }}" class="text-xs font-bold text-hau-maroon hover:text-hau-maroon-light">
                    Go to Programs Directory &rarr;
                </a>
            </div>
        </div>

        <!-- Active Accreditations Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md">
            <div class="p-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Active Audits</p>
                    <p class="mt-2 text-3xl font-black text-emerald-600 font-mono">{{ $activeAccreditations }}</p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                <button onclick="filterByStatus('Active')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                    View active records &rarr;
                </button>
            </div>
        </div>

        <!-- PAASCU Accredited Programs Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md relative group">
            <div class="absolute -top-1 left-0 right-0 h-1 bg-hau-gold"></div>
            <div class="p-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">PAASCU Accredited</p>
                    <p class="mt-2 text-3xl font-black text-hau-maroon font-mono">{{ $paascuProgramsCount }}</p>
                </div>
                <div class="p-3 bg-hau-gold/15 rounded-lg text-hau-gold-dark">
                    <span class="font-bold text-xs">PAASCU</span>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                <button id="paascu-toggle-card" onclick="togglePaascuFilterFromCard()" class="text-xs font-bold text-hau-maroon hover:text-hau-maroon-light">
                    Filter by PAASCU only
                </button>
            </div>
        </div>

        <!-- Expired/Expiring Soon Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden transition hover:shadow-md">
            <div class="p-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Expired / Expiring</p>
                    <p class="mt-2 text-3xl font-black text-rose-600 font-mono">{{ $expiringOrExpired }}</p>
                </div>
                <div class="p-3 bg-rose-50 rounded-lg text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                <button onclick="filterByExpiring()" class="text-xs font-bold text-rose-600 hover:text-rose-700">
                    View warnings &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- Accreditation Type Breakdown -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                <span>Local Accreditations</span>
                <span class="font-mono text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded font-black">{{ $localPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-hau-maroon h-full rounded-full transition-all duration-500" style="width: {{ $localPercentage }}%"></div>
            </div>
        </div>
        <div>
            <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                <span>International Accreditations</span>
                <span class="font-mono text-hau-gold-dark bg-hau-gold/10 px-2 py-0.5 rounded font-black">{{ $intlPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-hau-gold h-full rounded-full transition-all duration-500" style="width: {{ $intlPercentage }}%"></div>
            </div>
        </div>
        <div>
            <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                <span>Regulatory Certifications</span>
                <span class="font-mono text-gray-600 bg-gray-100 px-2 py-0.5 rounded font-black">{{ $regulatoryPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-gray-500 h-full rounded-full transition-all duration-500" style="width: {{ $regulatoryPercentage }}%"></div>
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
            {{-- FIX 1: $bodies was derived from $accreditations->pluck()->unique()->sort() which returns
                 a keyed Collection. @foreach on a sorted keyed collection can produce duplicate or
                 misaligned option values. values() reindexes it to a clean 0-based list. --}}
            @php
                $bodies = $accreditations->pluck('accrediting_body')->unique()->sort()->values();
            @endphp
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

            <!-- PAASCU Toggle -->
            <div class="flex items-center ml-2 border border-gray-200 rounded-lg px-3 py-1.5 bg-gray-50/50 hover:bg-gray-50 transition cursor-pointer select-none">
                <input type="checkbox" id="filter-paascu" onchange="applyFilters()" class="w-4 h-4 text-hau-maroon border-gray-300 rounded focus:ring-hau-maroon cursor-pointer" />
                <label for="filter-paascu" class="ml-2 text-sm font-semibold text-gray-700 cursor-pointer">
                    PAASCU Only
                </label>
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Accrediting Body</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Level/Tier</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Last Visit</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Expiry</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody id="accreditation-rows" class="divide-y divide-gray-200 bg-white">
                    @forelse ($accreditations as $a)
                        <tr class="hover:bg-gray-50/50 transition duration-100"
                            data-id="{{ $a->id }}"
                            data-program-id="{{ $a->program_id }}"
                            data-program-code="{{ $a->program->program_code }}"
                            data-program-name="{{ $a->program->program_name }}"
                            data-accrediting-body="{{ $a->accrediting_body }}"
                            data-type="{{ $a->type }}"
                            data-level-tier="{{ $a->level_or_tier }}"
                            data-last-visit="{{ $a->last_visit ? $a->last_visit->format('Y-m-d') : '' }}"
                            data-expiry-date="{{ $a->expiry_date ? $a->expiry_date->format('Y-m-d') : '' }}"
                            data-status="{{ $a->status }}">

                            <td class="px-6 py-4">
                                {{-- FIX 2: program_name and college can contain single quotes (e.g. "St. Paul's College")
                                     which break the inline onclick JS string literals. e() escapes for HTML output
                                     but doesn't protect JS string context. Wrap values in json_encode() instead
                                     so any quotes/special chars are safely escaped for JS. --}}
                                <div class="font-bold text-hau-maroon text-sm hover:underline cursor-pointer"
                                     onclick="viewProgramDetails({{ json_encode($a->program->program_code) }}, {{ json_encode($a->program->program_name) }}, {{ json_encode($a->program->college) }})">
                                    {{ $a->program->program_code }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5 max-w-[200px] truncate" title="{{ $a->program->program_name }}">
                                    {{ $a->program->program_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 text-sm">{{ $a->accrediting_body }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold">
                                @if($a->type == 'Local')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-800">Local</span>
                                @elseif($a->type == 'International')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-100">International</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700">Regulatory</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 hidden md:table-cell">
                                {{ $a->level_or_tier ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 hidden lg:table-cell font-mono">
                                {{ $a->last_visit ? $a->last_visit->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 hidden lg:table-cell font-mono">
                                {{ $a->expiry_date ? $a->expiry_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold">
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
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
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
                            <td colspan="8" class="text-center text-gray-400 py-12 text-sm">No accreditations found in database. Seed sample data or add a record.</td>
                        </tr>
                    @endforelse

                    <tr id="no-matches-row" class="hidden">
                        <td colspan="8" class="text-center text-gray-400 py-12 text-sm">No accreditations match the current filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODAL WINDOWS ================= -->

<!-- 1. Add Accreditation Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Add Accreditation</h3>
            <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold transition">&times;</button>
        </div>
        <form action="{{ route('accreditations.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label for="add-program_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Academic Program</label>
                    <select name="program_id" id="add-program_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="">Select a Program</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add-accrediting_body" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Accrediting Body</label>
                        <input type="text" name="accrediting_body" id="add-accrediting_body" required placeholder="e.g. PAASCU" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="add-type" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Type</label>
                        <select name="type" id="add-type" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Local" selected>Local</option>
                            <option value="International">International</option>
                            <option value="Regulatory">Regulatory</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add-level_or_tier" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Level / Tier</label>
                        <input type="text" name="level_or_tier" id="add-level_or_tier" placeholder="e.g. Level III" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="add-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" id="add-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Active">Active</option>
                            <option value="Expiring Soon">Expiring Soon</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending" selected>Pending</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add-last_visit" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Last Visit Date</label>
                        <input type="date" name="last_visit" id="add-last_visit" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="add-expiry_date" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Expiry Date</label>
                        <input type="date" name="expiry_date" id="add-expiry_date" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow focus:outline-none transition">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Edit Accreditation Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Edit Accreditation</h3>
            <button onclick="closeModal('edit-modal')" class="text-white hover:text-hau-gold transition">&times;</button>
        </div>
        <form id="edit-form" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label for="edit-program_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Academic Program</label>
                    <select name="program_id" id="edit-program_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-accrediting_body" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Accrediting Body</label>
                        <input type="text" name="accrediting_body" id="edit-accrediting_body" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="edit-type" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Type</label>
                        <select name="edit-type" id="edit-type" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Local">Local</option>
                            <option value="International">International</option>
                            <option value="Regulatory">Regulatory</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-level_or_tier" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Level / Tier</label>
                        <input type="text" name="level_or_tier" id="edit-level_or_tier" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="edit-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" id="edit-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Active">Active</option>
                            <option value="Expiring Soon">Expiring Soon</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-last_visit" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Last Visit Date</label>
                        <input type="date" name="last_visit" id="edit-last_visit" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                    <div>
                        <label for="edit-expiry_date" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Expiry Date</label>
                        <input type="date" name="expiry_date" id="edit-expiry_date" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow focus:outline-none transition">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
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

    function openAddModal() {
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

        document.getElementById('edit-program_id').value      = programId;
        document.getElementById('edit-accrediting_body').value = body;
        document.getElementById('edit-type').value            = type;
        document.getElementById('edit-level_or_tier').value   = tier;
        document.getElementById('edit-last_visit').value      = lastVisit;
        document.getElementById('edit-expiry_date').value     = expiry;
        document.getElementById('edit-status').value          = status;

        document.getElementById('edit-form').action = `/accreditations/${id}`;
        openModal('edit-modal');
    }

    function viewProgramDetails(code, name, college) {
        alert(`${code} - ${name}\n\nCollege: ${college}`);
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

    function togglePaascuFilterFromCard() {
        const cb = document.getElementById('filter-paascu');
        cb.checked = !cb.checked;
        applyFilters();
    }

    function applyFilters() {
        const searchInput  = document.getElementById('filter-search').value.toLowerCase();
        const bodyFilter   = document.getElementById('filter-body').value.toLowerCase();
        const typeFilter   = document.getElementById('filter-type').value;
        const statusEl     = document.getElementById('filter-status');
        const statusFilter = statusEl.value;
        // FIX 3 (continued): read the expiry mode flag to match both Expired + Expiring Soon
        const expiryMode   = statusEl.dataset.expiryMode === 'true';
        const paascuFilter = document.getElementById('filter-paascu').checked;

        const cardBtn = document.getElementById('paascu-toggle-card');
        if (paascuFilter) {
            cardBtn.innerText = 'Clear PAASCU filter';
            cardBtn.classList.add('text-hau-gold-dark');
        } else {
            cardBtn.innerText = 'Filter by PAASCU only';
            cardBtn.classList.remove('text-hau-gold-dark');
        }

        const rows = document.querySelectorAll('#accreditation-rows tr[data-id]');
        let matchesCount = 0;

        rows.forEach(row => {
            const code   = row.getAttribute('data-program-code').toLowerCase();
            const name   = row.getAttribute('data-program-name').toLowerCase();
            const body   = row.getAttribute('data-accrediting-body').toLowerCase();
            const type   = row.getAttribute('data-type');
            const status = row.getAttribute('data-status');

            const matchesSearch  = !searchInput || code.includes(searchInput) || name.includes(searchInput) || body.includes(searchInput);
            const matchesBody    = !bodyFilter  || body === bodyFilter;
            const matchesType    = !typeFilter  || type === typeFilter;
            const matchesStatus  = expiryMode
                ? (status === 'Expired' || status === 'Expiring Soon')
                : (!statusFilter || status === statusFilter);
            const matchesPaascu  = !paascuFilter || body.includes('paascu');

            if (matchesSearch && matchesBody && matchesType && matchesStatus && matchesPaascu) {
                row.classList.remove('hidden');
                matchesCount++;
            } else {
                row.classList.add('hidden');
            }
        });

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
    }

    // Clear expiry mode when the user manually changes the status dropdown
    document.getElementById('filter-status').addEventListener('change', function () {
        this.dataset.expiryMode = '';
    });
</script>
@endsection