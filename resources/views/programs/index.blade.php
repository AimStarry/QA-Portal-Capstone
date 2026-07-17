@extends('layouts.app')

@section('content')
@php $totalCount = $programs->count(); @endphp
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-gray-900 font-sans animate-fadeIn" id="page-directory-title">HAU Quality Assurance Directories</h2>
            <p class="text-sm text-gray-500 animate-fadeIn" id="page-directory-desc">Comprehensive overview of HAU degree programs and non-academic offices/units.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Directory Filter Selector -->
            @if($role === 'QA Admin')
                <div class="mr-2">
                    <select id="directory-view-select" onchange="toggleDirectoryView(this.value)" class="block px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon cursor-pointer font-bold text-gray-700 shadow-sm">
                        <option value="programs">Academic Programs Directory</option>
                        <option value="units">Offices &amp; Units Directory</option>
                        <option value="all" selected>All Directories (Programs First)</option>
                    </select>
                </div>
            @else
                <input type="hidden" id="directory-view-select" value="{{ $role === 'Head of Unit' ? 'units' : 'programs' }}">
            @endif
        </div>
    </div>

    @if($role !== 'Head of Unit')
    <!-- ================= ACADEMIC PROGRAMS DIRECTORY SECTION ================= -->
    <div id="academic-programs-section" class="space-y-8 animate-fadeIn">
        
        <!-- Academic Programs Inner Header -->
        <div id="academic-programs-header" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-150 pb-3">
            <div class="space-y-1">
                <h3 class="text-xl font-bold text-gray-900 font-sans">Academic Programs Directory</h3>
                <p class="text-xs text-gray-500">Manage degree programs, department assignments, and track QA stats.</p>
            </div>
            <div class="flex items-center gap-2">
                @if($role === 'QA Admin')
                    <button onclick="openModal('manage-colleges-modal'); switchManageTab('colleges');" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 hover:bg-gray-50 shadow-sm focus:outline-none transition">
                        Manage Schools &amp; Colleges
                    </button>
                @endif
                @if($role === 'QA Admin' || $role === 'Dean' || $role === 'Principal')
                    <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon hover:bg-hau-maroon-light border border-transparent text-sm font-semibold rounded-xl text-white shadow-sm focus:outline-none transition">
                        + Add Academic Program
                    </button>
                @endif
            </div>
        </div>

        <!-- College Logo Filter Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4" id="college-filter-cards">
            @foreach($colleges as $col)
            @php $count = $collegeCounts[$col->name] ?? 0; @endphp
            <button type="button"
                onclick="filterByCollegeCard('{{ e($col->name) }}', this)"
                data-college-filter="{{ e($col->name) }}"
                class="college-filter-card group relative bg-white rounded-2xl shadow-xs border border-gray-200 p-4 hover:shadow-md hover:border-hau-maroon/30 transition-all duration-200 text-left focus:outline-none">
                <!-- Logo / Initial Avatar -->
                <div class="flex items-center gap-3 mb-3">
                    @if($col->logo)
                        <img src="{{ asset('storage/' . $col->logo) }}" alt="{{ $col->name }} logo"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-100 bg-gray-50 p-0.5 flex-shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-hau-maroon/8 border border-hau-maroon/15 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-black text-hau-maroon">{{ strtoupper(substr($col->code ?? $col->name, 0, 3)) }}</span>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-hau-maroon font-mono">{{ $col->code ?? '—' }}</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">School</p>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide leading-tight line-clamp-2 mb-1" title="{{ $col->name }}">{{ $col->name }}</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-gray-900">{{ $count }}</span>
                    <span class="text-[10px] text-gray-400 font-semibold">Programs</span>
                </div>
                <!-- Active indicator -->
                <div class="absolute top-2 right-2 w-2 h-2 rounded-full bg-hau-maroon scale-0 transition-transform duration-200 college-active-dot"></div>
            </button>
            @endforeach


            @if($role === 'QA Admin')
            <!-- Aggregate Total / Clear Filter Card -->
            <div class="bg-white rounded-2xl shadow-xs border-2 border-dashed border-hau-gold/40 p-4">
                <div class="mb-2">
                    <p class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider">Aggregate Total</p>
                </div>
                <div class="flex items-baseline gap-1.5 mb-3">
                    <span class="text-2xl font-black text-hau-maroon">{{ $totalCount }}</span>
                    <span class="text-[10px] text-gray-600 font-semibold">HAU Programs</span>
                </div>
                <button type="button" id="clear-college-filter-btn" onclick="clearCollegeFilter()"
                    class="w-full text-[10px] font-bold text-gray-500 hover:text-hau-maroon border border-gray-200 hover:border-hau-maroon/30 rounded-lg py-1.5 px-2 transition hidden">
                    ✕ Clear Filter
                </button>
            </div>
            @endif
        </div>


        <!-- Search & Toolbar -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Search -->
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="prog-search" oninput="filterPrograms()" placeholder="Search program code, name..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>

            <!-- School / College Filter (QA Admin only — Dean/Principal already scoped) -->
            @if($role === 'QA Admin')
            <div class="w-full sm:w-48">
                <select id="prog-college" onchange="filterPrograms()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Schools/Colleges</option>
                    @foreach($colleges as $col)
                        <option value="{{ $col->name }}">{{ $col->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Level Filter -->
            <div>
                <select id="prog-level" onchange="filterPrograms()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Levels</option>
                    <option value="Undergraduate">Undergraduate</option>
                    <option value="Graduate">Graduate</option>
                    <option value="Master's">Master's</option>
                    <option value="Doctoral">Doctoral</option>
                    <option value="K-12">K-12</option>
                </select>
            </div>

            <!-- Accreditable Filter -->
            <div>
                <select id="prog-accreditable" onchange="filterPrograms()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Programs</option>
                    <option value="1">Accreditable</option>
                    <option value="0">Not Accreditable</option>
                </select>
            </div>

            <!-- Record count -->
            <div class="text-xs text-gray-400 font-medium sm:ml-auto whitespace-nowrap">
                Showing <span id="visible-count" class="font-bold text-gray-600">{{ $totalCount }}</span> of {{ $totalCount }} records
            </div>
        </div>

        <!-- Table Directory -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-3 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program Title</th>
                            <th class="px-3 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">School / Dept.</th>
                            <th class="px-3 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Level</th>
                            <th class="px-3 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Accreditable</th>
                            <th class="px-3 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Accreds.</th>
                            <th class="px-3 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Compliance</th>
                            <th class="px-3 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Risks</th>
                            <th class="px-3 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="program-rows" class="divide-y divide-gray-200 bg-white">
                        @forelse ($programs as $p)
                            <tr class="hover:bg-gray-50/50 transition duration-100"
                                data-id="{{ $p->id }}"
                                data-code="{{ $p->program_code }}"
                                data-name="{{ e($p->program_name) }}"
                                data-college="{{ e($p->college->name ?? '') }}"
                                data-college-id="{{ $p->college_id }}"
                                data-department="{{ e($p->department) }}"
                                data-level="{{ $p->program_level }}"
                                data-accreditable="{{ $p->is_accreditable ? 1 : 0 }}">

                                <td class="px-3 py-3 font-bold text-hau-maroon text-sm hover:underline whitespace-nowrap">
                                    <a href="{{ route('programs.show', $p->id) }}">{{ $p->program_code }}</a>
                                </td>
                                <td class="px-3 py-3 text-sm font-semibold text-gray-900 hover:text-hau-maroon hover:underline">
                                    <a href="{{ route('programs.show', $p->id) }}">{{ $p->program_name }}</a>
                                    @if($p->former_name)
                                        <div class="text-[9px] text-gray-400 italic leading-tight font-normal">formerly: {{ $p->former_name }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500 hidden lg:table-cell">
                                    <div class="font-semibold text-gray-800 text-xs leading-snug">
                                        {{ $p->college->name ?? 'Unassigned' }}
                                    </div>
                                    @if($p->former_department)
                                        <div class="text-[9px] text-gray-400 italic leading-tight">formerly: {{ $p->former_department }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-xs text-gray-500 font-semibold hidden md:table-cell whitespace-nowrap">{{ $p->program_level }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if($p->is_accreditable)
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-wide">Accreditable</span>
                                    @else
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-black bg-gray-100 text-gray-500 border border-gray-200 uppercase tracking-wide" title="Excluded from accreditation statistics">Non-Accreditable</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">{{ $p->accreditations_count }}</span>
                                </td>
                                <td class="px-3 py-3 text-center hidden xl:table-cell">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-emerald-50 text-emerald-700">{{ $p->compliance_records_count }} Tasks</span>
                                </td>
                                <td class="px-3 py-3 text-center hidden xl:table-cell">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono {{ $p->risk_items_count > 0 ? 'bg-rose-50 text-rose-700' : 'bg-gray-50 text-gray-400' }}">{{ $p->risk_items_count }} Active</span>
                                </td>
                                <td class="px-3 py-3 text-right text-sm">
                                    @if($role === 'QA Admin' || (($role === 'Dean' || $role === 'Principal') && $p->college_id === auth()->user()->college_id))
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button onclick="openEditModal(this.closest('tr'))" class="p-1.5 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('programs.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Deleting this program will delete all associated accreditations, compliance tasks, and risks! Are you sure you want to proceed?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-gray-100 rounded-lg transition" title="Delete">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="9" class="text-center text-gray-400 py-12 text-sm">No academic programs registered. Add one to begin.</td>
                            </tr>
                        @endforelse

                        <tr id="no-matches-row" class="hidden">
                            <td colspan="9" class="text-center text-gray-400 py-12 text-sm">No programs match the current filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($role === 'QA Admin' || $role === 'Head of Unit')
    <!-- ================= OFFICES & UNITS DIRECTORY SECTION ================= -->
    <div id="offices-units-section" class="space-y-4 hidden">
        <!-- Offices & Units Directory Inner Header -->
        <div id="offices-units-header" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-150 pb-3">
            <div class="space-y-1">
                <h3 class="text-xl font-bold text-gray-900 font-sans">Offices &amp; Units Directory</h3>
                <p class="text-xs text-gray-505">Manage non-academic departments and offices responsible for compliance items.</p>
            </div>
            @if($role === 'QA Admin' || $role === 'Head of Unit')
                <div class="flex items-center gap-2">
                    <button onclick="openModal('manage-colleges-modal'); switchManageTab('units');" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 hover:bg-gray-50 shadow-sm focus:outline-none transition">
                        Manage Offices &amp; Units
                    </button>
                    <button onclick="openModal('add-unit-modal')" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon hover:bg-hau-maroon-light border border-transparent text-sm font-semibold rounded-xl text-white shadow-sm focus:outline-none transition">
                        + Add Office / Unit
                    </button>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Office/Unit Name</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($units as $u)
                            <tr class="hover:bg-gray-50/50 transition duration-100"
                                data-unit-id="{{ $u->id }}"
                                data-unit-name="{{ e($u->name) }}"
                                data-unit-code="{{ e($u->code) }}">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    <div class="flex items-center gap-3">
                                        @if($u->logo)
                                            <img src="{{ asset('storage/' . $u->logo) }}" alt="Logo" class="w-8 h-8 rounded object-contain border border-gray-200 p-0.5 bg-gray-50 flex-shrink-0" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-hau-maroon/5 border border-hau-maroon/10 flex items-center justify-center flex-shrink-0 font-bold text-[10px] text-hau-maroon font-mono">
                                                {{ strtoupper(substr($u->code ?? $u->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <span>{{ $u->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono font-bold text-hau-maroon">{{ $u->code ?? '—' }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    @if($role === 'QA Admin' || $role === 'Head of Unit')
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick="openEditUnitModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('units.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Office/Unit?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-555 hover:text-rose-600 hover:bg-gray-100 rounded-lg transition" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 py-12 text-sm">No offices or units registered. Add one to begin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- ================= MODAL WINDOWS ================= -->

<!-- Add Program Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Add Academic Program</h3>
            <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('programs.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="add-code" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Code</label>
                    <input type="text" name="program_code" id="add-code" required placeholder="e.g. BSCS" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Title / Degree Name</label>
                    <input type="text" name="program_name" id="add-name" required placeholder="e.g. Bachelor of Science in Computer Science" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School / College</label>
                    @if($role === 'Dean' || $role === 'Principal')
                        <select id="add-college" disabled class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-500 focus:outline-none">
                            @foreach($colleges as $col)
                                @if($col->id === auth()->user()->college_id)
                                    <option value="{{ $col->id }}" selected>{{ $col->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" name="college_id" value="{{ auth()->user()->college_id }}">
                    @else
                        <select name="college_id" id="add-college" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="" disabled selected>Select School / College</option>
                            @foreach($colleges as $col)
                                <option value="{{ $col->id }}">{{ $col->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label for="add-department" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Department / Committee</label>
                    <input type="text" name="department" id="add-department" placeholder="e.g. Computer Science Department" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-level" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Level</label>
                    <select name="program_level" id="add-level" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Undergraduate" selected>Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Master's">Master's</option>
                        <option value="Doctoral">Doctoral</option>
                        <option value="K-12">K-12</option>
                    </select>
                </div>
                <div>
                    <label for="add-accreditable" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Accreditable Status</label>
                    <select name="is_accreditable" id="add-accreditable" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="1" selected>Accreditable (Include in stats)</option>
                        <option value="0">Non-Accreditable (Exclude from stats)</option>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Save Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Program Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Edit Academic Program</h3>
            <button onclick="closeModal('edit-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form id="edit-form" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label for="edit-code" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Code</label>
                    <input type="text" name="program_code" id="edit-code" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Title / Degree Name</label>
                    <input type="text" name="program_name" id="edit-name" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School / College</label>
                    @if($role === 'Dean' || $role === 'Principal')
                        <select id="edit-college" disabled class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-500 focus:outline-none">
                            @foreach($colleges as $col)
                                @if($col->id === auth()->user()->college_id)
                                    <option value="{{ $col->id }}" selected>{{ $col->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" name="college_id" value="{{ auth()->user()->college_id }}">
                    @else
                        <select name="college_id" id="edit-college" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            @foreach($colleges as $col)
                                <option value="{{ $col->id }}">{{ $col->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label for="edit-department" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Department / Committee</label>
                    <input type="text" name="department" id="edit-department" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-level" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Level</label>
                    <select name="program_level" id="edit-level" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Undergraduate">Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Master's">Master's</option>
                        <option value="Doctoral">Doctoral</option>
                        <option value="K-12">K-12</option>
                    </select>
                </div>
                <div>
                    <label for="edit-accreditable" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Accreditable Status</label>
                    <select name="is_accreditable" id="edit-accreditable" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="1">Accreditable (Include in stats)</option>
                        <option value="0">Non-Accreditable (Exclude from stats)</option>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Update Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Office/Unit Modal -->
<div id="add-unit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Add Office / Unit</h3>
            <button onclick="closeModal('add-unit-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('units.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="add-unit-name-field" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Office/Unit Name</label>
                    <input type="text" name="name" id="add-unit-name-field" required placeholder="e.g. Quality Assurance Office" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-unit-code-field" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Code</label>
                    <input type="text" name="code" id="add-unit-code-field" placeholder="e.g. QAO" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Office Logo <span class="text-gray-400 normal-case font-normal">(optional, max 10MB)</span></label>
                    <div class="mt-1">
                        <label for="logo-add-unit" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 hover:border-hau-maroon rounded-xl cursor-pointer bg-gray-50 hover:bg-hau-maroon/5 transition duration-150 text-xs font-bold text-gray-600 hover:text-hau-maroon group">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-hau-maroon transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span class="file-label-text truncate">Choose Image File...</span>
                        </label>
                        <input type="file" id="logo-add-unit" name="logo" accept="image/*" class="hidden" onchange="updateFileLabel(this)" />
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('add-unit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Save Office/Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Office/Unit Modal -->
<div id="edit-unit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Edit Office / Unit</h3>
            <button onclick="closeModal('edit-unit-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form id="edit-unit-form" action="" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label for="edit-unit-name-field" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Office/Unit Name</label>
                    <input type="text" name="name" id="edit-unit-name-field" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-unit-code-field" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Code</label>
                    <input type="text" name="code" id="edit-unit-code-field" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Office Logo <span class="text-gray-400 normal-case font-normal">(upload new to replace, max 10MB)</span></label>
                    <div class="mt-1">
                        <label for="logo-edit-unit" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 hover:border-hau-maroon rounded-xl cursor-pointer bg-gray-50 hover:bg-hau-maroon/5 transition duration-150 text-xs font-bold text-gray-600 hover:text-hau-maroon group">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-hau-maroon transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span class="file-label-text truncate">Choose Image File...</span>
                        </label>
                        <input type="file" id="logo-edit-unit" name="logo" accept="image/*" class="hidden" onchange="updateFileLabel(this)" />
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('edit-unit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Update Office/Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Schools & Colleges Modal -->
<div id="manage-colleges-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all flex flex-col" style="max-height: 85vh;">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold shrink-0">
            <h3 class="text-lg font-bold">Manage Colleges &amp; Units</h3>
            <button onclick="closeModal('manage-colleges-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>

        <!-- Tab Switcher -->
        <div class="flex border-b border-gray-200 shrink-0">
            <button type="button" onclick="switchManageTab('colleges')" id="tab-btn-colleges" class="flex-1 py-3 text-center text-sm font-bold border-b-2 border-hau-maroon text-hau-maroon focus:outline-none">Schools &amp; Colleges</button>
            <button type="button" onclick="switchManageTab('units')" id="tab-btn-units" class="flex-1 py-3 text-center text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none">Offices &amp; Units</button>
        </div>

        <!-- Colleges Tab Content -->
        <div id="manage-tab-colleges" class="flex flex-col flex-1 min-h-0">
            <div class="px-6 py-3 border-b border-gray-150 flex justify-between items-center bg-gray-50/50 shrink-0">
                <span class="text-xs font-bold text-gray-500">Active Schools/Colleges</span>
                <button type="button" onclick="openModal('add-school-modal')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add School
                </button>
            </div>
            <div class="p-6 space-y-4 overflow-y-auto flex-1 bg-gray-50/20">
                @forelse($colleges as $col)
                    <div class="bg-white border border-gray-200 rounded-xl p-4 shrink-0 shadow-xs space-y-3">
                        <form action="{{ route('colleges.update', $col->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="space-y-3.5">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">School / College Name</label>
                                    <input type="text" name="name" value="{{ $col->name }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon font-semibold text-gray-700" />
                                </div>
                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-4">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Acronym / Code</label>
                                        <input type="text" name="code" value="{{ $col->code }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon font-semibold text-gray-700" />
                                    </div>
                                    <div class="col-span-8">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Former Name (Optional)</label>
                                        <input type="text" name="former_name" value="{{ $col->former_name }}" placeholder="e.g. College of Nursing (CON)" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Assigned Dean / Principal</label>
                                    <select name="dean_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold bg-white">
                                        <option value="">No Head Assigned</option>
                                        @foreach($deans as $dean)
                                            <option value="{{ $dean->id }}" {{ $dean->college_id === $col->id ? 'selected' : '' }}>
                                                {{ $dean->name }} ({{ $dean->username }}) – {{ $dean->usertype }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pt-2 border-t border-gray-100">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1 font-sans">School Logo <span class="text-gray-455 normal-case font-normal">(upload new to replace, max 10MB)</span></label>
                                    <div class="mt-1 flex items-center gap-3">
                                        @if($col->logo)
                                            <img src="{{ asset('storage/' . $col->logo) }}" alt="Logo" class="w-10 h-10 rounded object-contain border border-gray-200 p-0.5 bg-gray-50 flex-shrink-0" />
                                        @endif
                                        <label for="logo-col-{{ $col->id }}" class="flex-grow flex items-center justify-center gap-2 px-3 py-2.5 border-2 border-dashed border-gray-300 hover:border-hau-maroon rounded-xl cursor-pointer bg-gray-50 hover:bg-hau-maroon/5 transition duration-150 text-xs font-bold text-gray-655 hover:text-hau-maroon group">
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-hau-maroon transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                            </svg>
                                            <span class="file-label-text truncate">Choose Image...</span>
                                        </label>
                                        <input type="file" id="logo-col-{{ $col->id }}" name="logo" accept="image/*" class="hidden" onchange="updateFileLabel(this)" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2 border-t border-gray-100">
                                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">Save Changes</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">No schools/colleges yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Units Tab Content -->
        <div id="manage-tab-units" class="flex flex-col flex-1 min-h-0 hidden">
            <div class="px-6 py-3 border-b border-gray-150 flex justify-between items-center bg-gray-50/50 shrink-0">
                <span class="text-xs font-bold text-gray-500">Active Offices/Units</span>
                <button type="button" onclick="openModal('add-unit-modal')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Unit
                </button>
            </div>
            <div class="p-6 space-y-4 overflow-y-auto flex-1 bg-gray-50/20">
                @forelse($units as $u)
                    <div class="bg-white border border-gray-200 rounded-xl p-4 shrink-0 shadow-xs space-y-3">
                        <form action="{{ route('units.update', $u->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="space-y-3.5">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Office / Unit Name</label>
                                    <input type="text" name="name" value="{{ $u->name }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon font-semibold text-gray-700" />
                                </div>
                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-4">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Code</label>
                                        <input type="text" name="code" value="{{ $u->code }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon font-semibold text-gray-700" />
                                    </div>
                                    <div class="col-span-8">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Assigned Unit Head</label>
                                        <select name="head_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold bg-white">
                                            <option value="">No Head Assigned</option>
                                            @foreach($unitHeads as $head)
                                                <option value="{{ $head->id }}" {{ $head->unit_id === $u->id ? 'selected' : '' }}>
                                                    {{ $head->name }} ({{ $head->username }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-3 font-sans">
                                    <div class="flex-grow">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Office Logo <span class="text-gray-455 normal-case font-normal">(upload new to replace, max 10MB)</span></label>
                                        <div class="mt-1 flex items-center gap-3">
                                            @if($u->logo)
                                                <img src="{{ asset('storage/' . $u->logo) }}" alt="Logo" class="w-10 h-10 rounded object-contain border border-gray-200 p-0.5 bg-gray-50 flex-shrink-0" />
                                            @endif
                                            <label for="logo-unit-{{ $u->id }}" class="flex-grow flex items-center justify-center gap-2 px-3 py-2.5 border-2 border-dashed border-gray-300 hover:border-hau-maroon rounded-xl cursor-pointer bg-gray-50 hover:bg-hau-maroon/5 transition duration-150 text-xs font-bold text-gray-655 hover:text-hau-maroon group">
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-hau-maroon transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                                <span class="file-label-text truncate">Choose Image...</span>
                                            </label>
                                            <input type="file" id="logo-unit-{{ $u->id }}" name="logo" accept="image/*" class="hidden" onchange="updateFileLabel(this)" />
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 self-end pb-1">
                                        <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">Save</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">No offices or units yet.</p>
                @endforelse
            </div>
        </div>

        <div class="px-6 py-3 flex justify-end border-t border-gray-250 shrink-0 bg-gray-50">
            <button type="button" onclick="closeModal('manage-colleges-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Close</button>
        </div>
    </div>
</div>

<!-- Add School Modal -->
<div id="add-school-modal" class="fixed inset-0 z-55 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all flex flex-col">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold shrink-0">
            <h3 class="text-sm font-black uppercase tracking-wider">Add New School / College</h3>
            <button onclick="closeModal('add-school-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('colleges.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="add-college-name" class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">School / College Name</label>
                    <input type="text" name="name" id="add-college-name" required placeholder="e.g. School of Fine Arts" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold" />
                </div>
                <div>
                    <label for="add-college-code" class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Acronym / Code</label>
                    <input type="text" name="code" id="add-college-code" placeholder="e.g. SFA" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold" />
                </div>
                <div>
                    <label for="add-college-former" class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Former Name (Optional)</label>
                    <input type="text" name="former_name" id="add-college-former" placeholder="e.g. College of Fine Arts (CFA)" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon text-gray-700 font-semibold" />
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">School Logo <span class="text-gray-455 normal-case font-normal">(optional, max 10MB)</span></label>
                    <div class="mt-1">
                        <label for="logo-add-school" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 hover:border-hau-maroon rounded-xl cursor-pointer bg-gray-50 hover:bg-hau-maroon/5 transition duration-150 text-xs font-bold text-gray-650 hover:text-hau-maroon group">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-hau-maroon transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span class="file-label-text truncate">Choose Image File...</span>
                        </label>
                        <input type="file" id="logo-add-school" name="logo" accept="image/*" class="hidden" onchange="updateFileLabel(this)" />
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2 border-t border-gray-250 shrink-0">
                <button type="button" onclick="closeModal('add-school-modal')" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-xs font-bold rounded-lg shadow-sm transition">Add School</button>
            </div>
        </form>
    </div>
</div>



<!-- ================= JAVASCRIPT ================= -->
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        // Re-parent to <body> in case an ancestor has a transform/filter/perspective,
        // which would turn `position: fixed` into "fixed relative to that ancestor"
        // instead of the viewport, cropping the modal.
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
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

    function switchManageTab(tab) {
        if (tab === 'colleges') {
            document.getElementById('manage-tab-colleges').classList.remove('hidden');
            document.getElementById('manage-tab-units').classList.add('hidden');
            document.getElementById('tab-btn-colleges').className = 'flex-1 py-3 text-center text-sm font-bold border-b-2 border-hau-maroon text-hau-maroon focus:outline-none';
            document.getElementById('tab-btn-units').className = 'flex-1 py-3 text-center text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none';
        } else {
            document.getElementById('manage-tab-colleges').classList.add('hidden');
            document.getElementById('manage-tab-units').classList.remove('hidden');
            document.getElementById('tab-btn-colleges').className = 'flex-1 py-3 text-center text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none';
            document.getElementById('tab-btn-units').className = 'flex-1 py-3 text-center text-sm font-bold border-b-2 border-hau-maroon text-hau-maroon focus:outline-none';
        }
    }

    function openAddModal() {
        openModal('add-modal');
    }

    function openEditModal(row) {
        const id           = row.getAttribute('data-id');
        const code         = row.getAttribute('data-code');
        const name         = row.getAttribute('data-name');
        const collegeId    = row.getAttribute('data-college-id') || '';
        const department   = row.getAttribute('data-department') || '';
        const level        = row.getAttribute('data-level');
        const accreditable = row.getAttribute('data-accreditable');

        document.getElementById('edit-code').value         = code;
        document.getElementById('edit-name').value         = name;
        document.getElementById('edit-college').value      = collegeId;
        document.getElementById('edit-department').value   = department;
        document.getElementById('edit-level').value        = level;
        document.getElementById('edit-accreditable').value = accreditable;

        document.getElementById('edit-form').action = `/programs/${id}`;
        openModal('edit-modal');
    }

    function openEditUnitModal(row) {
        const id   = row.getAttribute('data-unit-id');
        const name = row.getAttribute('data-unit-name');
        const code = row.getAttribute('data-unit-code');

        document.getElementById('edit-unit-name-field').value = name;
        document.getElementById('edit-unit-code-field').value = code;

        document.getElementById('edit-unit-form').action = `/units/${id}`;
        openModal('edit-unit-modal');
    }

    let activeCollegeCardFilter = '';

    function filterByCollegeCard(collegeName, cardElement) {
        const allCards = document.querySelectorAll('.college-filter-card');
        const clearBtn = document.getElementById('clear-college-filter-btn');

        if (activeCollegeCardFilter === collegeName) {
            clearCollegeFilter();
            return;
        }

        activeCollegeCardFilter = collegeName;

        allCards.forEach(card => {
            if (card === cardElement) {
                card.classList.add('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
                const dot = card.querySelector('.college-active-dot');
                if (dot) dot.classList.remove('scale-0');
            } else {
                card.classList.remove('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
                const dot = card.querySelector('.college-active-dot');
                if (dot) dot.classList.add('scale-0');
            }
        });

        document.getElementById('prog-college').value = collegeName;
        if (clearBtn) clearBtn.classList.remove('hidden');

        filterPrograms();
    }

    function clearCollegeFilter() {
        activeCollegeCardFilter = '';
        const allCards = document.querySelectorAll('.college-filter-card');
        const clearBtn = document.getElementById('clear-college-filter-btn');

        allCards.forEach(card => {
            card.classList.remove('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
            const dot = card.querySelector('.college-active-dot');
            if (dot) dot.classList.add('scale-0');
        });

        document.getElementById('prog-college').value = '';
        if (clearBtn) clearBtn.classList.add('hidden');

        filterPrograms();
    }

    function filterPrograms() {
        const query               = document.getElementById('prog-search').value.toLowerCase();
        const collegeSelect       = document.getElementById('prog-college');
        const collegeFilter       = collegeSelect ? collegeSelect.value.toLowerCase() : '';
        const levelFilter         = document.getElementById('prog-level').value;
        const accreditableSelect  = document.getElementById('prog-accreditable');
        const accreditableFilter  = accreditableSelect ? accreditableSelect.value : '';

        // Sync card active states with select value
        const allCards = document.querySelectorAll('.college-filter-card');
        const clearBtn = document.getElementById('clear-college-filter-btn');
        
        if (collegeSelect && collegeSelect.value) {
            if (clearBtn) clearBtn.classList.remove('hidden');
            allCards.forEach(card => {
                const cardCollege = card.getAttribute('data-college-filter');
                if (cardCollege && cardCollege.toLowerCase() === collegeFilter) {
                    card.classList.add('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
                    const dot = card.querySelector('.college-active-dot');
                    if (dot) dot.classList.remove('scale-0');
                    activeCollegeCardFilter = cardCollege;
                } else {
                    card.classList.remove('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
                    const dot = card.querySelector('.college-active-dot');
                    if (dot) dot.classList.add('scale-0');
                }
            });
        } else {
            if (clearBtn) clearBtn.classList.add('hidden');
            allCards.forEach(card => {
                card.classList.remove('border-hau-maroon', 'bg-hau-maroon/5', 'ring-2', 'ring-hau-maroon/20');
                const dot = card.querySelector('.college-active-dot');
                if (dot) dot.classList.add('scale-0');
            });
            activeCollegeCardFilter = '';
        }

        const rows = document.querySelectorAll('#program-rows tr[data-id]');
        let count = 0;

        rows.forEach(row => {
            const code         = row.getAttribute('data-code').toLowerCase();
            const name         = row.getAttribute('data-name').toLowerCase();
            const college      = row.getAttribute('data-college').toLowerCase();
            const department   = (row.getAttribute('data-department') || '').toLowerCase();
            const level        = row.getAttribute('data-level');
            const accreditable = row.getAttribute('data-accreditable');

            const matchesSearch      = !query              || code.includes(query) || name.includes(query) || college.includes(query) || department.includes(query);
            const matchesCollege     = !collegeFilter      || college === collegeFilter;
            const matchesLevel       = !levelFilter        || level === levelFilter;
            const matchesAccreditable = accreditableFilter === '' || accreditable === accreditableFilter;

            if (matchesSearch && matchesCollege && matchesLevel && matchesAccreditable) {
                row.classList.remove('hidden');
                count++;
            } else {
                row.classList.add('hidden');
            }
        });

        document.getElementById('visible-count').innerText = count;

        const emptyRow    = document.getElementById('empty-row');
        const noMatchRow  = document.getElementById('no-matches-row');

        if (count === 0 && rows.length > 0) {
            noMatchRow.classList.remove('hidden');
            if (emptyRow) emptyRow.classList.add('hidden');
        } else {
            noMatchRow.classList.add('hidden');
            if (emptyRow && rows.length === 0) emptyRow.classList.remove('hidden');
        }
    }
    function toggleDirectoryView(val) {
        const sectionUnits    = document.getElementById('offices-units-section');
        const sectionPrograms = document.getElementById('academic-programs-section');
        
        if (val === 'units') {
            sectionUnits.classList.remove('hidden');
            sectionUnits.classList.remove('border-t', 'pt-6');
            sectionPrograms.classList.add('hidden');
        } else if (val === 'programs') {
            sectionUnits.classList.add('hidden');
            sectionPrograms.classList.remove('hidden');
            sectionPrograms.classList.remove('border-t', 'pt-6');
        } else if (val === 'all') {
            sectionPrograms.classList.remove('hidden');
            sectionPrograms.classList.remove('border-t', 'pt-6');
            sectionUnits.classList.remove('hidden');
            sectionUnits.classList.add('border-t', 'pt-6');
        }
    }

    function updateFileLabel(input) {
        let label = input.closest('label');
        if (!label && input.id) {
            label = document.querySelector(`label[for="${input.id}"]`);
        }
        if (!label) return;

        const textSpan = label.querySelector('.file-label-text');
        if (!textSpan) return;

        if (input.files && input.files[0]) {
            textSpan.textContent = input.files[0].name;
            label.classList.remove('border-gray-300', 'text-gray-650', 'text-gray-600', 'bg-gray-50');
            label.classList.add('border-hau-maroon', 'text-hau-maroon', 'bg-hau-maroon/5');
        } else {
            textSpan.textContent = 'Choose Image File...';
            label.classList.remove('border-hau-maroon', 'text-hau-maroon', 'bg-hau-maroon/5');
            label.classList.add('border-gray-300', 'text-gray-600', 'bg-gray-50');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('directory-view-select');
        if (select) {
            toggleDirectoryView(select.value);
        }
    });
</script>
@endsection