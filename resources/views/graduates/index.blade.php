@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Graduates Tracker Directory</h2>
            <p class="text-xs sm:text-sm text-gray-500">Track and manage number of graduates per program, school year, and term (semesters/trimesters).</p>
        </div>
        @if ($role === 'QA Admin')
            <div>
                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                    + Log Graduates Count
                </button>
            </div>
        @endif
    </div>

    <!-- Summary Stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Recorded Graduates</p>
            <p class="text-3xl font-black text-gray-900 mt-2 font-mono">
                <span id="stat-total-graduates">{{ number_format($graduates->sum('graduates_count')) }}</span>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Record Entries</p>
            <p class="text-3xl font-black text-hau-maroon mt-2 font-mono">
                <span id="stat-total-entries">{{ $graduates->count() }}</span>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Average Graduates per Term</p>
            <p class="text-3xl font-black text-emerald-600 mt-2 font-mono">
                <span id="stat-average-graduates">{{ $graduates->count() > 0 ? round($graduates->average('graduates_count')) : 0 }}</span>
            </p>
        </div>
    </div>

    <!-- Toolbar & Realtime Filters -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center gap-3 flex-grow">
            <!-- Search -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="filter-search" oninput="applyFilters()" placeholder="Search school year, program..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>

            <!-- Department Selector -->
            <div>
                <select id="filter-college" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Departments</option>
                    @foreach($colleges as $col)
                        <option value="{{ $col->college_id }}">{{ $col->code ?: $col->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Program Selector -->
            <div>
                <select id="filter-program" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Programs</option>
                    @foreach($programs as $p)
                        <option value="{{ $p->program_code }}">{{ $p->program_code }}</option>
                    @endforeach
                </select>
            </div>

            <!-- School Year Selector -->
            <div>
                <select id="filter-sy" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All School Years</option>
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}">{{ $sy }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Term Selector -->
            <div>
                <select id="filter-term" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Terms</option>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer">Summer</option>
                    <option value="1st Trimester">1st Trimester</option>
                    <option value="2nd Trimester">2nd Trimester</option>
                    <option value="3rd Trimester">3rd Trimester</option>
                    <option value="1st Term">1st Term</option>
                    <option value="2nd Term">2nd Term</option>
                    <option value="3rd Term">3rd Term</option>
                </select>
            </div>
        </div>
        <div class="text-xs text-gray-400 font-medium">
            Active Filter Results: <span id="visible-count" class="font-bold text-gray-600">{{ $graduates->count() }}</span> items
        </div>
    </div>

    <!-- Bulk Action Bar -->
    @if ($role === 'QA Admin')
        <div id="bulk-action-bar" class="hidden bg-hau-maroon text-white px-5 py-3 rounded-xl shadow-md flex items-center justify-between border-l-4 border-hau-gold transition-all duration-200">
            <div class="flex items-center gap-3">
                <span class="text-xs sm:text-sm font-semibold"><span id="selected-count" class="font-bold text-hau-gold">0</span> items selected</span>
                <button type="button" onclick="deselectAll()" class="text-xs text-hau-gold hover:underline">Deselect All</button>
            </div>
            <form id="bulk-delete-form" action="{{ route('graduates.bulk-destroy') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete the selected graduate records?')">
                @csrf
                <div id="bulk-ids-container"></div>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Selected (<span class="selected-count-badge">0</span>)
                </button>
            </form>
        </div>
    @endif

    <!-- Table Container -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if ($role === 'QA Admin')
                            <th scope="col" class="px-4 py-3.5 text-center w-10">
                                <input type="checkbox" id="select-all-cb" onclick="toggleSelectAll(this)" class="rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon cursor-pointer" title="Select All">
                            </th>
                        @endif
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Level</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">School Year</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Term</th>
                        <th scope="col" class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Number of Graduates</th>
                        @if ($role === 'QA Admin')
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="graduate-rows" class="divide-y divide-gray-200 bg-white">
                    @forelse ($graduates as $g)
                        <tr class="hover:bg-gray-50/50 transition duration-100" 
                            data-id="{{ $g->id }}"
                            data-program-id="{{ $g->program_id }}"
                            data-program-code="{{ $g->program->program_code }}"
                            data-program-name="{{ $g->program->program_name }}"
                            data-level="{{ $g->program->program_level }}"
                            data-college-id="{{ $g->program->college_id }}"
                            data-sy="{{ $g->school_year }}"
                            data-term="{{ $g->term }}"
                            data-count="{{ $g->graduates_count }}">
                            
                            @if ($role === 'QA Admin')
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" class="row-cb rounded border-gray-300 text-hau-maroon focus:ring-hau-maroon cursor-pointer" value="{{ $g->getKey() }}" onchange="updateBulkBar()">
                                </td>
                            @endif
                            <td class="px-6 py-4">
                                <div class="font-bold text-hau-maroon text-sm hover:underline">
                                    <a href="{{ route('programs.show', $g->program_id) }}">{{ $g->program->program_code }}</a>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $g->program->program_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-800">{{ $g->program->program_level }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 font-mono">{{ $g->school_year }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $g->term }}</td>
                            <td class="px-6 py-4 text-center font-bold text-sm text-gray-900 font-mono">{{ number_format($g->graduates_count) }}</td>
                            
                            @if ($role === 'QA Admin')
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                            </svg>
                                        </button>
                                        <!-- Delete Button -->
                                        <form action="{{ route('graduates.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this graduates count record?')" class="inline">
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
                            @endif
                        </tr>
                    @empty
                        <tr id="empty-row"><td colspan="6" class="text-center text-gray-400 py-12 text-sm">No graduates records found in database. Seed sample data or log a record.</td></tr>
                    @endforelse
                    
                    <!-- JS No Matches row placeholder -->
                    <tr id="no-matches-row" class="hidden"><td colspan="6" class="text-center text-gray-400 py-12 text-sm">No records match the current filters.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($role === 'QA Admin')
    <!-- ================= MODAL WINDOWS ================= -->

    <!-- 1. Add Graduates Count Modal -->
    <div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-200 flex flex-col max-h-[92vh]">
            <!-- Rich Dark Maroon Header -->
            <div class="modal-dark-header flex items-center justify-between text-white border-b border-black/20 shrink-0 shadow-md"
                 style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Log Graduates Count</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Record graduate statistics for an academic term</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('add-modal')" 
                    class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer focus:outline-none shrink-0" 
                    style="color: #ffffff;"
                    title="Close modal">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('graduates.store') }}" method="POST" class="p-6 space-y-6 overflow-y-auto flex-1">
                @csrf
                
                <!-- Section 1: Academic Scope -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Program & Academic Period
                        </span>
                    </div>

                    <div>
                        <label for="add-program_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Academic Program <span class="text-rose-500">*</span></label>
                        <select name="program_id" id="add-program_id" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                            <option value="">-- Select Academic Program --</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="add-school_year" class="block text-xs font-semibold text-gray-700 mb-1.5">School Year <span class="text-rose-500">*</span></label>
                            <input type="text" name="school_year" id="add-school_year" required placeholder="e.g. 2025-2026" 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" />
                        </div>

                        <div>
                            <label for="add-term" class="block text-xs font-semibold text-gray-700 mb-1.5">Term <span class="text-rose-500">*</span></label>
                            <select name="term" id="add-term" required 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="1st Semester" selected>1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                                <option value="1st Trimester">1st Trimester</option>
                                <option value="2nd Trimester">2nd Trimester</option>
                                <option value="3rd Trimester">3rd Trimester</option>
                                <option value="1st Term">1st Term</option>
                                <option value="2nd Term">2nd Term</option>
                                <option value="3rd Term">3rd Term</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Graduates Statistics -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Graduate Statistics
                        </span>
                    </div>

                    <div>
                        <label for="add-count" class="block text-xs font-semibold text-gray-700 mb-1.5">Number of Graduates <span class="text-rose-500">*</span></label>
                        <input type="number" name="graduates_count" id="add-count" required min="0" placeholder="e.g. 50" 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" />
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-200 mt-6 shrink-0">
                    <button type="button" onclick="closeModal('add-modal')" 
                        class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-100 transition cursor-pointer shadow-xs">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="inline-flex items-center gap-2 px-5 py-2 bg-hau-maroon text-white text-sm font-bold rounded-xl hover:bg-hau-maroon-dark transition cursor-pointer shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit Graduates Count Modal -->
    <div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-200 flex flex-col max-h-[92vh]">
            <!-- Rich Dark Maroon Header -->
            <div class="modal-dark-header flex items-center justify-between text-white border-b border-black/20 shrink-0 shadow-md"
                 style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Edit Graduates Count Record</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Update graduate figures or academic period</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('edit-modal')" 
                    class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer focus:outline-none shrink-0" 
                    style="color: #ffffff;"
                    title="Close modal">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="edit-form" action="" method="POST" class="p-6 space-y-6 overflow-y-auto flex-1">
                @csrf
                @method('PUT')
                
                <!-- Section 1: Academic Scope -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Program & Academic Period
                        </span>
                    </div>

                    <div>
                        <label for="edit-program_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Academic Program <span class="text-rose-500">*</span></label>
                        <select name="program_id" id="edit-program_id" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-school_year" class="block text-xs font-semibold text-gray-700 mb-1.5">School Year <span class="text-rose-500">*</span></label>
                            <input type="text" name="school_year" id="edit-school_year" required 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" />
                        </div>

                        <div>
                            <label for="edit-term" class="block text-xs font-semibold text-gray-700 mb-1.5">Term <span class="text-rose-500">*</span></label>
                            <select name="term" id="edit-term" required 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                                <option value="1st Trimester">1st Trimester</option>
                                <option value="2nd Trimester">2nd Trimester</option>
                                <option value="3rd Trimester">3rd Trimester</option>
                                <option value="1st Term">1st Term</option>
                                <option value="2nd Term">2nd Term</option>
                                <option value="3rd Term">3rd Term</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Graduates Statistics -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Graduate Statistics
                        </span>
                    </div>

                    <div>
                        <label for="edit-count" class="block text-xs font-semibold text-gray-700 mb-1.5">Number of Graduates <span class="text-rose-500">*</span></label>
                        <input type="number" name="graduates_count" id="edit-count" required min="0" 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" />
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-200 mt-6 shrink-0">
                    <button type="button" onclick="closeModal('edit-modal')" 
                        class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-100 transition cursor-pointer shadow-xs">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="inline-flex items-center gap-2 px-5 py-2 bg-hau-maroon text-white text-sm font-bold rounded-xl hover:bg-hau-maroon-dark transition cursor-pointer shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- ================= JAVASCRIPT ================= -->
<script>
    // Modal Helpers
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
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
        const id = row.getAttribute('data-id');
        const programId = row.getAttribute('data-program-id');
        const sy = row.getAttribute('data-sy');
        const term = row.getAttribute('data-term');
        const count = row.getAttribute('data-count');

        document.getElementById('edit-program_id').value = programId;
        document.getElementById('edit-school_year').value = sy;
        document.getElementById('edit-term').value = term;
        document.getElementById('edit-count').value = count;

        document.getElementById('edit-form').action = `/graduates/${id}`;

        openModal('edit-modal');
    }

    function applyFilters() {
        const searchInput = document.getElementById('filter-search').value.toLowerCase();
        const programFilter = document.getElementById('filter-program').value.toLowerCase();
        const syFilter = document.getElementById('filter-sy').value.toLowerCase();
        const termFilter = document.getElementById('filter-term').value;
        const collegeFilter = document.getElementById('filter-college').value;

        const rows = document.querySelectorAll('#graduate-rows tr[data-id]');
        let matchesCount = 0;
        let totalGraduates = 0;
        let visibleEntries = 0;

        rows.forEach(row => {
            const programCode = row.getAttribute('data-program-code').toLowerCase();
            const programName = row.getAttribute('data-program-name').toLowerCase();
            const sy = row.getAttribute('data-sy').toLowerCase();
            const term = row.getAttribute('data-term');
            const collegeId = row.getAttribute('data-college-id');

            // Conditions
            const matchesSearch = !searchInput || 
                                  programCode.includes(searchInput) || 
                                  programName.includes(searchInput) || 
                                  sy.includes(searchInput);
            
            const matchesProgram = !programFilter || programCode === programFilter;
            const matchesSy = !syFilter || sy === syFilter;
            const matchesTerm = !termFilter || term === termFilter;
            const matchesCollege = !collegeFilter || collegeId === collegeFilter;

            if (matchesSearch && matchesProgram && matchesSy && matchesTerm && matchesCollege) {
                row.classList.remove('hidden');
                matchesCount++;
                const count = parseInt(row.getAttribute('data-count')) || 0;
                totalGraduates += count;
                visibleEntries++;
            } else {
                row.classList.add('hidden');
            }
        });

        // Update stats
        const totalGradsSpan = document.getElementById('stat-total-graduates');
        if (totalGradsSpan) {
            totalGradsSpan.innerText = totalGraduates.toLocaleString();
        }
        const totalEntriesSpan = document.getElementById('stat-total-entries');
        if (totalEntriesSpan) {
            totalEntriesSpan.innerText = visibleEntries.toLocaleString();
        }
        const avgGradsSpan = document.getElementById('stat-average-graduates');
        if (avgGradsSpan) {
            const avg = visibleEntries > 0 ? Math.round(totalGraduates / visibleEntries) : 0;
            avgGradsSpan.innerText = avg.toLocaleString();
        }

        const visibleCountSpan = document.getElementById('visible-count');
        if (visibleCountSpan) visibleCountSpan.innerText = matchesCount;

        const emptyPlaceholder = document.getElementById('empty-row');
        const noMatchesPlaceholder = document.getElementById('no-matches-row');

        if (matchesCount === 0) {
            if (rows.length === 0) {
                if (emptyPlaceholder) emptyPlaceholder.classList.remove('hidden');
                if (noMatchesPlaceholder) noMatchesPlaceholder.classList.add('hidden');
            } else {
                if (emptyPlaceholder) emptyPlaceholder.classList.add('hidden');
                if (noMatchesPlaceholder) noMatchesPlaceholder.classList.remove('hidden');
            }
        } else {
            if (emptyPlaceholder) emptyPlaceholder.classList.add('hidden');
            if (noMatchesPlaceholder) noMatchesPlaceholder.classList.add('hidden');
        }

        updateBulkBar();
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('#graduate-rows tr:not(.hidden) .row-cb');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateBulkBar();
    }

    function updateBulkBar() {
        const selected = document.querySelectorAll('#graduate-rows tr:not(.hidden) .row-cb:checked');
        const bar = document.getElementById('bulk-action-bar');
        if (!bar) return;
        const countSpan = document.getElementById('selected-count');
        const badgeSpans = document.querySelectorAll('.selected-count-badge');
        const container = document.getElementById('bulk-ids-container');
        
        if (selected.length > 0) {
            bar.classList.remove('hidden');
            if (countSpan) countSpan.textContent = selected.length;
            badgeSpans.forEach(b => b.textContent = selected.length);
            
            container.innerHTML = '';
            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        } else {
            bar.classList.add('hidden');
            const master = document.getElementById('select-all-cb');
            if (master) master.checked = false;
        }
    }

    function deselectAll() {
        const master = document.getElementById('select-all-cb');
        if (master) master.checked = false;
        document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
        updateBulkBar();
    }
</script>
@endsection
