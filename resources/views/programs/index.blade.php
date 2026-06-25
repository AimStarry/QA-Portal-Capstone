@extends('layouts.app')

@section('content')
@php $totalCount = $programs->count(); @endphp
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-gray-900 font-sans">Academic Programs Directory</h2>
            <p class="text-sm text-gray-500">Manage HAU degree programs, department assignments, and track high-level QA stats.</p>
        </div>
        <div>
            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                + Add Academic Program
            </button>
        </div>
    </div>

    <!-- College Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($collegeCounts as $college => $count)
            <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 hover:shadow-sm transition">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider truncate" title="{{ $college }}">{{ $college }}</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-gray-900">{{ $count }}</span>
                    <span class="text-xs text-gray-500 font-medium">Programs Registered</span>
                </div>
            </div>
        @endforeach

        <!-- Aggregate Total -->
        <div class="bg-white rounded-xl shadow-xs border-2 border-dashed border-hau-gold/40 p-5">
            <p class="text-[10px] font-bold text-hau-maroon uppercase tracking-wider">Aggregate Total</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-hau-maroon">{{ $totalCount }}</span>
                <span class="text-xs text-gray-600 font-semibold">HAU Degree Programs</span>
            </div>
        </div>
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

        <!-- Department / College Filter (NEW) -->
        @php
            $colleges = $programs->pluck('college')->unique()->sort()->values();
        @endphp
        <div class="w-full sm:w-64">
            <select id="prog-college" onchange="filterPrograms()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                <option value="">All Departments</option>
                @foreach($colleges as $col)
                    <option value="{{ $col }}">{{ $col }}</option>
                @endforeach
            </select>
        </div>

        <!-- Level Filter (NEW) -->
        <div>
            <select id="prog-level" onchange="filterPrograms()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                <option value="">All Levels</option>
                <option value="Undergraduate">Undergraduate</option>
                <option value="Graduate">Graduate</option>
                <option value="Master's">Master's</option>
                <option value="Doctoral">Doctoral</option>
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
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program Title</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">School / Department</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Accreditations</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Compliance Checklist</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Risks</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody id="program-rows" class="divide-y divide-gray-200 bg-white">
                    @forelse ($programs as $p)
                        <tr class="hover:bg-gray-50/50 transition duration-100"
                            data-id="{{ $p->id }}"
                            data-code="{{ $p->program_code }}"
                            {{-- FIX 3: program_name and college can contain single/double quotes
                                 which break HTML attribute parsing. e() encodes them safely. --}}
                            data-name="{{ e($p->program_name) }}"
                            data-college="{{ e($p->college) }}"
                            data-level="{{ $p->program_level }}">

                            <td class="px-6 py-4 font-bold text-hau-maroon text-sm">{{ $p->program_code }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $p->program_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $p->college }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-semibold">{{ $p->program_level }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">{{ $p->accreditations_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-emerald-50 text-emerald-700">{{ $p->compliance_records_count }} Tasks</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono {{ $p->risk_items_count > 0 ? 'bg-rose-50 text-rose-700' : 'bg-gray-50 text-gray-400' }}">{{ $p->risk_items_count }} Active</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="openEditModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('programs.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Deleting this program will delete all associated accreditations, compliance tasks, and risks! Are you sure you want to proceed?')" class="inline">
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
                        {{-- FIX 4: Both placeholder rows had colspan="7" but the table has 8 columns. --}}
                        <tr id="empty-row">
                            <td colspan="8" class="text-center text-gray-400 py-12 text-sm">No academic programs registered. Add one to begin.</td>
                        </tr>
                    @endforelse

                    <tr id="no-matches-row" class="hidden">
                        <td colspan="8" class="text-center text-gray-400 py-12 text-sm">No programs match the current filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

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
                    <label for="add-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School / Department</label>
                    <input type="text" name="college" id="add-college" required placeholder="e.g. School of Computing (SOC)" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="add-level" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Level</label>
                    <select name="program_level" id="add-level" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Undergraduate" selected>Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Master's">Master's</option>
                        <option value="Doctoral">Doctoral</option>
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
                    <label for="edit-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">School / Department</label>
                    <input type="text" name="college" id="edit-college" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                </div>
                <div>
                    <label for="edit-level" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Program Level</label>
                    <select name="program_level" id="edit-level" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Undergraduate">Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Master's">Master's</option>
                        <option value="Doctoral">Doctoral</option>
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
        const id      = row.getAttribute('data-id');
        const code    = row.getAttribute('data-code');
        const name    = row.getAttribute('data-name');
        const college = row.getAttribute('data-college');
        const level   = row.getAttribute('data-level');

        document.getElementById('edit-code').value    = code;
        document.getElementById('edit-name').value    = name;
        document.getElementById('edit-college').value = college;
        document.getElementById('edit-level').value   = level;

        document.getElementById('edit-form').action = `/programs/${id}`;
        openModal('edit-modal');
    }

    function filterPrograms() {
        const query         = document.getElementById('prog-search').value.toLowerCase();
        const collegeFilter = document.getElementById('prog-college').value.toLowerCase();
        const levelFilter   = document.getElementById('prog-level').value;

        const rows = document.querySelectorAll('#program-rows tr[data-id]');
        let count = 0;

        rows.forEach(row => {
            const code    = row.getAttribute('data-code').toLowerCase();
            const name    = row.getAttribute('data-name').toLowerCase();
            const college = row.getAttribute('data-college').toLowerCase();
            const level   = row.getAttribute('data-level');

            const matchesSearch  = !query         || code.includes(query) || name.includes(query) || college.includes(query);
            const matchesCollege = !collegeFilter || college === collegeFilter;
            const matchesLevel   = !levelFilter   || level === levelFilter;

            if (matchesSearch && matchesCollege && matchesLevel) {
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
</script>
@endsection