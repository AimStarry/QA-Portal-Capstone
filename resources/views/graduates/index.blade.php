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

    <!-- Table Container -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
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
        <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-lg font-bold">Log Graduates Count</h3>
                <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold transition">&times;</button>
            </div>
            <form action="{{ route('graduates.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <!-- Program Selection -->
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
                        <!-- School Year -->
                        <div>
                            <label for="add-school_year" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School Year</label>
                            <input type="text" name="school_year" id="add-school_year" required placeholder="e.g. 2025-2026" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>

                        <!-- Term -->
                        <div>
                            <label for="add-term" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Term</label>
                            <select name="term" id="add-term" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
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

                    <!-- Graduates Count -->
                    <div>
                        <label for="add-count" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Number of Graduates</label>
                        <input type="number" name="graduates_count" id="add-count" required min="0" placeholder="e.g. 50" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow focus:outline-none transition">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit Graduates Count Modal -->
    <div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-lg font-bold">Edit Graduates Count Record</h3>
                <button onclick="closeModal('edit-modal')" class="text-white hover:text-hau-gold transition">&times;</button>
            </div>
            <form id="edit-form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6 space-y-4">
                    <!-- Program Selection -->
                    <div>
                        <label for="edit-program_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Academic Program</label>
                        <select name="program_id" id="edit-program_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- School Year -->
                        <div>
                            <label for="edit-school_year" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School Year</label>
                            <input type="text" name="school_year" id="edit-school_year" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>

                        <!-- Term -->
                        <div>
                            <label for="edit-term" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Term</label>
                            <select name="term" id="edit-term" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
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

                    <!-- Graduates Count -->
                    <div>
                        <label for="edit-count" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Number of Graduates</label>
                        <input type="number" name="graduates_count" id="edit-count" required min="0" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow focus:outline-none transition">Update Record</button>
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
    }
</script>
@endsection
