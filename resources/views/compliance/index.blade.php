@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Compliance Checklist Tracker</h2>
            <p class="text-xs sm:text-sm text-gray-500">Track documentation audits and compliance tasks. Document links are direct links to evidence files.</p>
        </div>
        @if ($role === 'QA Admin')
            <div>
                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                    + Log Compliance Task
                </button>
            </div>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Checklists</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 font-mono">{{ $totalCompliance }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-emerald-500">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Compliant</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1 font-mono">{{ $compliantCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-rose-500">
            <p class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Non-Compliant</p>
            <p class="text-2xl font-bold text-rose-600 mt-1 font-mono">{{ $nonCompliantCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4 border-l-4 border-l-gray-400">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Pending Audit</p>
            <p class="text-2xl font-bold text-gray-500 mt-1 font-mono">{{ $pendingCount }}</p>
        </div>
    </div>

    <!-- Filter toolbar -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <!-- Search -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="comp-search" oninput="applyFilters()" placeholder="Search title, description, responsible..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>

            <!-- Accrediting Body Dropdown Filter (Requirement!) -->
            <div>
                <select id="comp-body" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Accrediting Bodies</option>
                    @foreach($bodies as $body)
                        <option value="{{ $body }}">{{ $body }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <select id="comp-status" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Statuses</option>
                    <option value="Compliant">Compliant</option>
                    <option value="Non-Compliant">Non-Compliant</option>
                    <option value="Pending">Pending</option>
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
            <!-- Program details lookup for filtering -->
            @php
                $programBodies = $c->program->accreditations->pluck('accrediting_body')->map(function($val) {
                    return strtolower($val);
                })->implode(',');
            @endphp
            <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden flex flex-col justify-between hover:shadow-sm transition"
                 data-id="{{ $c->id }}"
                 data-program-id="{{ $c->program_id }}"
                 data-program-code="{{ $c->program->program_code }}"
                 data-program-bodies="{{ $programBodies }}"
                 data-title="{{ $c->title }}"
                 data-desc="{{ $c->description }}"
                 data-status="{{ $c->status }}"
                 data-due="{{ $c->due_date ? $c->due_date->format('Y-m-d') : '' }}"
                 data-resp="{{ $c->responsible_unit }}"
                 data-link="{{ $c->document_link }}"
                 data-pending-status="{{ $c->pending_status }}"
                 data-pending-link="{{ $c->pending_document_link }}"
                 data-approval-state="{{ $c->approval_state }}"
                 data-rejection-reason="{{ $c->rejection_reason }}">
                 
                 <!-- Top Body -->
                 <div class="p-5 space-y-4">
                     <div class="flex items-start justify-between gap-2">
                         <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                             {{ $c->program->program_code }}
                         </span>
                         
                         <!-- Workflow Status Badge -->
                         <div class="flex flex-col items-end gap-1">
                             <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold
                                 @if ($c->status == 'Compliant') bg-emerald-50 text-emerald-700 border border-emerald-100
                                 @elseif ($c->status == 'In Progress') bg-blue-50 text-blue-700 border border-blue-100
                                 @elseif ($c->status == 'Non-Compliant') bg-rose-50 text-rose-700 border border-rose-100
                                 @else bg-gray-50 text-gray-600 border border-gray-150
                                 @endif">
                                 {{ $c->status }}
                             </span>
                             
                             <!-- Approval State badges -->
                             @if ($c->approval_state === 'Pending Approval')
                                 <span class="inline-flex items-center px-1.5 py-0.25 rounded text-[9px] font-black bg-hau-gold/15 text-hau-maroon border border-hau-gold/30">Awaiting Approval</span>
                             @elseif ($c->approval_state === 'Rejected')
                                 <button onclick="alert('Rejection Feedback:\n\n&ldquo;{{ $c->rejection_reason }}&rdquo;')" class="inline-flex items-center px-1.5 py-0.25 rounded text-[9px] font-black bg-rose-50 text-rose-700 border border-rose-100 hover:bg-rose-100/60 transition">
                                     Rejected (Details)
                                 </button>
                             @endif
                         </div>
                     </div>

                     <!-- Info -->
                     <div class="space-y-1">
                         <h4 class="font-bold text-gray-900 text-sm leading-snug truncate" title="{{ $c->title }}">{{ $c->title }}</h4>
                         <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed" title="{{ $c->description }}">{{ $c->description ?? 'No description provided.' }}</p>
                     </div>

                     <!-- Document Link -->
                     <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                         <span class="text-gray-400 font-semibold">Evidence Link:</span>
                         @if ($c->document_link)
                             <a href="{{ $c->document_link }}" target="_blank" class="text-blue-600 hover:underline font-mono font-medium truncate block max-w-[200px]" title="{{ $c->document_link }}">
                                 Open Link &rarr;
                             </a>
                         @else
                             <span class="text-gray-400 font-medium italic">No document attached</span>
                         @endif
                     </div>

                     <div class="flex items-center justify-between text-xs pt-1">
                         <span class="text-gray-400 font-semibold">Responsible Unit:</span>
                         <span class="font-bold text-gray-700">{{ $c->responsible_unit ?? 'Unassigned' }}</span>
                     </div>
                 </div>

                 <!-- Footer Controls -->
                 <div class="bg-gray-50/70 px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                     <div class="flex items-center gap-1.5 text-xs text-gray-500 font-semibold">
                         <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                         </svg>
                         @if ($c->due_date)
                             <span class="font-mono">Due: {{ $c->due_date->format('M d, Y') }}</span>
                         @else
                             <span>No deadline</span>
                         @endif
                     </div>
                     
                     <div class="flex items-center gap-2">
                         @if ($role === 'QA Admin')
                             <!-- Admin Full CRUD -->
                             <button onclick="openEditModal(this.closest('[data-id]'))" class="p-1 text-gray-400 hover:text-hau-maroon hover:bg-gray-200/50 rounded transition" title="Edit Task">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                 </svg>
                             </button>
                             <form action="{{ route('compliance.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this compliance record?')" class="inline">
                                 @csrf
                                 @method('DELETE')
                                 <button type="submit" class="p-1 text-gray-400 hover:text-rose-600 hover:bg-gray-200/50 rounded transition" title="Delete Task">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                     </svg>
                                 </button>
                             </form>
                         @else
                             <!-- Responsible Unit Draft Propose Update Button (Requirement!) -->
                             <button onclick="openProposeModal(this.closest('[data-id]'))" 
                                     {{ $c->approval_state === 'Pending Approval' ? 'disabled' : '' }}
                                     class="inline-flex px-3 py-1 border border-hau-maroon hover:bg-hau-maroon/5 text-hau-maroon font-bold text-xs rounded-lg transition disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-200 disabled:cursor-not-allowed shadow-2xs">
                                 Propose Update
                             </button>
                         @endif
                     </div>
                 </div>
            </div>
        @empty
            <div id="empty-row" class="col-span-full text-center text-gray-400 py-12 text-sm bg-white rounded-xl border border-gray-200">No compliance items logged yet.</div>
        @endforelse
        
        <div id="no-matches-row" class="col-span-full text-center text-gray-400 py-12 text-sm bg-white rounded-xl border border-gray-200 hidden">No compliance items matches your filters.</div>
    </div>

</div>

<!-- ================= MODAL WINDOWS ================= -->

@if ($role === 'QA Admin')
    <!-- 1. Add Compliance Modal (Admin only) -->
    <div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-lg font-bold">Log Compliance Item</h3>
                <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('compliance.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label for="add-program_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Academic Program</label>
                        <select name="program_id" id="add-program_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="">Select a Program</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="add-title" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Task Title</label>
                        <input type="text" name="title" id="add-title" required placeholder="e.g. Submit Alumni Board Minutes" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>

                    <div>
                        <label for="add-desc" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Task Description</label>
                        <textarea name="description" id="add-desc" rows="3" placeholder="Describe compliance details..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>

                    <!-- Link Input (Requirement!) -->
                    <div>
                        <label for="add-link" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Document Link (Evidence URL)</label>
                        <input type="url" name="document_link" id="add-link" placeholder="e.g. https://drive.google.com/..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="add-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" id="add-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Pending" selected>Pending Audit</option>
                                <option value="Compliant">Compliant</option>
                                <option value="Non-Compliant">Non-Compliant</option>
                            </select>
                        </div>

                        <div>
                            <label for="add-due" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Due Date</label>
                            <input type="date" name="due_date" id="add-due" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>

                    <div>
                        <label for="add-resp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Responsible Unit</label>
                        <input type="text" name="responsible_unit" id="add-resp" placeholder="e.g. School of Computing (SOC)" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit Compliance Modal (Admin only) -->
    <div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-lg font-bold">Edit Compliance Item</h3>
                <button onclick="closeModal('edit-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>
            <form id="edit-form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6 space-y-4">
                    <div>
                        <label for="edit-program_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Academic Program</label>
                        <select name="program_id" id="edit-program_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->program_code }} &mdash; {{ $p->program_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="edit-title" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Task Title</label>
                        <input type="text" name="title" id="edit-title" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>

                    <div>
                        <label for="edit-desc" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Task Description</label>
                        <textarea name="description" id="edit-desc" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                    </div>

                    <!-- Link Input (Requirement!) -->
                    <div>
                        <label for="edit-link" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Document Link (Evidence URL)</label>
                        <input type="url" name="document_link" id="edit-link" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" id="edit-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                                <option value="Pending">Pending Audit</option>
                                <option value="Compliant">Compliant</option>
                                <option value="Non-Compliant">Non-Compliant</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit-due" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Due Date</label>
                            <input type="date" name="due_date" id="edit-due" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        </div>
                    </div>

                    <div>
                        <label for="edit-resp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Responsible Unit</label>
                        <input type="text" name="responsible_unit" id="edit-resp" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                    <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Update Task</button>
                </div>
            </form>
        </div>
    </div>

@else
    <!-- 3. Propose Update Modal (Responsible Unit only, Requirement!) -->
    <div id="propose-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
            <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="text-md font-bold">Propose Compliance Update</h3>
                <button onclick="closeModal('propose-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
            </div>
            <form id="propose-form" action="" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <div class="bg-hau-maroon/5 border border-hau-maroon/10 p-3 rounded-lg text-xs space-y-1">
                        <span class="text-gray-400 font-bold">Task:</span>
                        <h5 id="propose-task-title" class="font-bold text-gray-800"></h5>
                    </div>
                    
                    <div>
                        <label for="propose-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Proposed Status</label>
                        <select name="pending_status" id="propose-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Pending" selected>Pending Audit</option>
                            <option value="Compliant">Compliant</option>
                            <option value="Non-Compliant">Non-Compliant</option>
                        </select>
                    </div>

                    <div>
                        <label for="propose-link" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Document Link (Evidence URL)</label>
                        <input type="url" name="pending_document_link" id="propose-link" required placeholder="e.g., https://drive.google.com/..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
                        <span class="text-[10px] text-gray-400 mt-1 block">A valid document URL must be provided to submit changes for approval.</span>
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

    function openEditModal(card) {
        const id = card.getAttribute('data-id');
        const programId = card.getAttribute('data-program-id');
        const title = card.getAttribute('data-title');
        const desc = card.getAttribute('data-desc');
        const status = card.getAttribute('data-status');
        const due = card.getAttribute('data-due');
        const resp = card.getAttribute('data-resp');
        const link = card.getAttribute('data-link');

        document.getElementById('edit-program_id').value = programId;
        document.getElementById('edit-title').value = title;
        document.getElementById('edit-desc').value = desc;
        document.getElementById('edit-status').value = status;
        document.getElementById('edit-due').value = due;
        document.getElementById('edit-resp').value = resp;
        document.getElementById('edit-link').value = link;

        document.getElementById('edit-form').action = `/compliance/${id}`;

        openModal('edit-modal');
    }

    function openProposeModal(card) {
        const id = card.getAttribute('data-id');
        const title = card.getAttribute('data-title');
        const pendingStatus = card.getAttribute('data-pending-status') || card.getAttribute('data-status');
        const pendingLink = card.getAttribute('data-pending-link') || card.getAttribute('data-link');

        document.getElementById('propose-task-title').innerText = title;
        document.getElementById('propose-status').value = pendingStatus;
        document.getElementById('propose-link').value = pendingLink;

        document.getElementById('propose-form').action = `/compliance/${id}/submit-update`;

        openModal('propose-modal');
    }

    function applyFilters() {
        const search = document.getElementById('comp-search').value.toLowerCase();
        const body = document.getElementById('comp-body').value.toLowerCase();
        const status = document.getElementById('comp-status').value;

        const cards = document.querySelectorAll('#compliance-grid > div[data-id]');
        let count = 0;

        cards.forEach(card => {
            const code = card.getAttribute('data-program-code').toLowerCase();
            const bodiesList = card.getAttribute('data-program-bodies').toLowerCase();
            const title = card.getAttribute('data-title').toLowerCase();
            const desc = card.getAttribute('data-desc').toLowerCase();
            const cardStatus = card.getAttribute('data-status');
            const resp = card.getAttribute('data-resp').toLowerCase();

            const matchesSearch = !search || 
                                  code.includes(search) || 
                                  title.includes(search) || 
                                  desc.includes(search) || 
                                  resp.includes(search);
            
            const matchesBody = !body || bodiesList.includes(body);
            const matchesStatus = !status || cardStatus === status;

            if (matchesSearch && matchesBody && matchesStatus) {
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
</script>
@endsection
