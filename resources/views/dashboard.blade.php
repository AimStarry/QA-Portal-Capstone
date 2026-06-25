@extends('layouts.app')

@section('content')
<div class="space-y-8 font-sans">

    <!-- Portal Welcome Header -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">HAU QA Portal Dashboard</h2>
            <p class="text-xs sm:text-sm text-gray-500">Active Viewport: <strong class="text-hau-maroon font-semibold">{{ $role }}</strong>. Tracking accreditation metrics and documentation checklists.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold font-mono px-3 py-1.5 rounded-lg bg-hau-gold/10 text-hau-maroon border border-hau-gold/30">
            Last Updated: {{ date('F d, Y') }}
        </div>
    </div>

    @if ($role === 'QA Admin')
        <!-- ================= QA ADMIN VIEWPORT ================= -->

        <!-- Core Counts -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Degree Programs</p>
                    <p class="text-3xl font-black text-gray-900">{{ $totalPrograms }}</p>
                </div>
                <div class="p-3 bg-hau-maroon/10 rounded-xl text-hau-maroon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Accrediting Bodies</p>
                    <p class="text-3xl font-black text-gray-900">{{ $totalAccreditations }}</p>
                    <p class="text-[10px] text-emerald-600 font-semibold">{{ $activeAccreditations }} Active Audits</p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Monitored QA Risks</p>
                    <p class="text-3xl font-black text-gray-900">{{ $totalRisks }}</p>
                </div>
                <div class="p-3 bg-rose-50 rounded-xl text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- FIX 1: This grid previously had two children but the third card (Pending Approvals)
             was placed OUTSIDE the closing </div>, breaking the 3-col layout.
             All three cards now sit correctly inside one grid. --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Compliance Rate per Accrediting Body -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 lg:col-span-2">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Compliance Rates by Accrediting Body</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @forelse($bodyComplianceRates as $body => $rate)
                        <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-150">
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
                                <p class="text-xs text-gray-500 font-semibold mt-0.5">Compliance index</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-4 col-span-full">No accrediting body accreditations found to compute rates.</p>
                    @endforelse
                </div>
            </div>

            <!-- Accreditation Type Breakdown -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Accreditation Type Breakdown</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>Local Accreditations</span>
                            <span class="font-mono text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded font-bold">{{ $localPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-hau-maroon h-full rounded-full transition-all duration-500" style="width: {{ $localPercentage }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>International Accreditations</span>
                            <span class="font-mono text-hau-gold-dark bg-hau-gold/10 px-2 py-0.5 rounded font-bold">{{ $intlPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-hau-gold h-full rounded-full transition-all duration-500" style="width: {{ $intlPercentage }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold text-gray-750 uppercase tracking-wider mb-1.5">
                            <span>Regulatory Certifications</span>
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
        <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
            <div class="bg-hau-maroon-dark px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
                <h3 class="font-bold text-sm text-white flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-hau-gold opacity-75"></span>
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
                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-gray-50/50 transition duration-150">
                        <div class="space-y-2 max-w-xl">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold font-mono bg-hau-maroon/5 text-hau-maroon">
                                    {{ $pending->program->program_code }}
                                </span>
                                <span class="text-xs font-bold text-gray-400">Submitted by: {{ $pending->responsible_unit }}</span>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm leading-snug">{{ $pending->title }}</h4>
                            <p class="text-xs text-gray-500 leading-relaxed">{{ $pending->description }}</p>

                            <div class="bg-gray-50 rounded-lg p-3 text-xs border border-gray-150 space-y-1.5">
                                <div>
                                    <span class="text-gray-400 font-semibold">Active Status:</span>
                                    <span class="font-bold text-gray-600 ml-1">{{ $pending->status }}</span>
                                    <span class="text-gray-400 mx-2">&rarr;</span>
                                    <span class="text-gray-400 font-semibold">Proposed:</span>
                                    <span class="font-bold text-emerald-600 ml-1 bg-emerald-50 px-1.5 py-0.25 rounded border border-emerald-100">{{ $pending->pending_status }}</span>
                                </div>
                                @if ($pending->pending_document_link)
                                    <div>
                                        <span class="text-gray-400 font-semibold">Attached Link:</span>
                                        <a href="{{ $pending->pending_document_link }}" target="_blank" class="text-blue-600 hover:underline font-mono font-medium ml-1 truncate block sm:inline-block max-w-[300px]" title="{{ $pending->pending_document_link }}">
                                            {{ $pending->pending_document_link }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-row md:flex-col lg:flex-row gap-2.5 justify-end">
                            <form action="{{ route('compliance.approve', $pending->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    Approve Update
                                </button>
                            </form>
                            <button onclick="openRejectModal('{{ $pending->id }}', '{{ $pending->title }}')" class="inline-flex items-center px-4 py-2 border border-rose-200 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-lg transition">
                                Reject Update
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

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-5">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Checklists Assigned</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono">{{ $unitTotalTasks }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-5 border-l-4 border-l-hau-gold">
                <p class="text-xs font-bold text-hau-maroon uppercase tracking-wider">Awaiting HAU Approval</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono text-hau-maroon-dark">{{ $unitPendingApprovalsCount }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-5 border-l-4 border-l-rose-500">
                <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Rejected by Admin</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono text-rose-600">{{ $unitRejectedCount }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-5 border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Completed / Compliant</p>
                <p class="text-2xl font-black text-gray-900 mt-1 font-mono text-emerald-600">{{ $unitCompliantCount }}</p>
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
                                <a href="{{ route('compliance.index') }}?search={{ $task->program->program_code }}" class="inline-flex px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg transition shadow-xs">
                                    Revise & Resubmit
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Awaiting Approvals tracker -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Awaiting QA Admin Approvals</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Program</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Task Title</th>
                            {{-- FIX 2: Removed stray "class" text fragment from inside this th attribute --}}
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Proposed Status</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Proposed Link</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Submission Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($awaitingTasks as $at)
                            <tr>
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

    <!-- ================= PAASCU PROGRAMS DIRECTORY ================= -->
    {{-- FIX 3: This block was previously swallowed inside the @else branch because the
         @endif above it was missing. It now correctly renders for both roles. --}}
    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                <span class="bg-hau-maroon text-hau-gold font-bold text-[10px] px-2 py-0.5 rounded border border-hau-gold/30">HAU</span>
                PAASCU Accredited Programs Directory
            </h3>
            <span class="text-xs text-gray-500 font-semibold">{{ $paascuPrograms->count() }} Program(s) Total</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($paascuPrograms as $prog)
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex flex-col justify-between hover:border-hau-gold transition duration-150 relative">
                    <div class="absolute top-4 right-4 bg-white border border-gray-200 shadow-xs px-2 py-0.5 rounded text-[10px] font-bold text-hau-gold-dark">
                        PAASCU
                    </div>
                    <div class="space-y-2">
                        <span class="text-xs font-black text-hau-maroon block font-mono">{{ $prog->program_code }}</span>
                        <h4 class="text-sm font-bold text-gray-900 leading-snug line-clamp-2" title="{{ $prog->program_name }}">{{ $prog->program_name }}</h4>
                        <span class="text-[10px] text-gray-500 font-medium block">{{ $prog->college }}</span>
                    </div>
                    @php
                        $audit = $prog->accreditations->first();
                    @endphp
                    @if($audit)
                        <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between text-xs">
                            <div>
                                <span class="block text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Level / Tier</span>
                                <span class="font-bold text-gray-700">{{ $audit->level_or_tier ?? 'Awaiting visit' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Expiry</span>
                                <span class="font-mono font-bold text-gray-700">{{ $audit->expiry_date ? $audit->expiry_date->format('M d, Y') : '—' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-xs text-gray-400 text-center py-6 col-span-full">No PAASCU accredited programs logged in directory.</p>
            @endforelse
        </div>
    </div>

</div>

<!-- ================= REJECTION REASON MODAL ================= -->
<div id="reject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-rose-700 px-6 py-4 text-white flex items-center justify-between">
            <h3 class="text-md font-bold">Reject Compliance Update</h3>
            <button onclick="closeRejectModal()" class="text-white hover:text-rose-200 text-2xl leading-none">&times;</button>
        </div>
        <form id="reject-form" action="" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <p class="text-xs text-gray-500">Provide constructive feedback or a reason for rejecting the proposed compliance changes. The Responsible Unit will see this feedback in order to submit revisions.</p>
                <div>
                    <label for="reject-reason" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Rejection Reason</label>
                    <textarea name="rejection_reason" id="reject-reason" required rows="3" placeholder="e.g. Please upload the document with authorized signatures." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"></textarea>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow transition">Reject Update</button>
            </div>
        </form>
    </div>
</div>

<script>
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
</script>
@endsection