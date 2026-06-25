@extends('layouts.app')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-gray-900 font-sans">Institutional Risk Monitor</h2>
            <p class="text-sm text-gray-500">Log, analyze, and mitigate potential quality assurance threats that could impact program accreditations.</p>
        </div>
        <div>
            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2.5 bg-hau-maroon border border-transparent text-sm font-semibold rounded-xl text-white hover:bg-hau-maroon-light shadow-sm focus:outline-none transition">
                + Log Risk Profile
            </button>
        </div>
    </div>

    <!-- Visual Heat Matrix and Quick Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Risk Status Stats -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 flex flex-col justify-between space-y-4 lg:col-span-1">
            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Risk Status Overview</h4>
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Identified
                        </span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ $identifiedCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Monitoring
                        </span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ $monitoringCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Mitigated
                        </span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ $mitigatedCount }}</span>
                    </div>
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100">
                <span class="text-[10px] text-gray-400 block">Total Risk Logs:</span>
                <span class="text-2xl font-black text-gray-900 font-mono">{{ $totalRisks }}</span>
            </div>
        </div>

        <!-- 3x3 Risk Matrix Heat Map (Visual Upgrade!) -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">3x3 Risk Assessment Matrix</h4>
                <span class="text-[10px] text-gray-400 font-medium font-sans">Click on any cell to filter the threat log below</span>
            </div>
            
            <div class="flex">
                <!-- Y-Axis Label -->
                <div class="w-8 flex items-center justify-center">
                    <div class="transform -rotate-90 text-[10px] font-bold text-gray-400 uppercase tracking-widest whitespace-nowrap">
                        Likelihood
                    </div>
                </div>
                
                <!-- Matrix Grid -->
                <div class="flex-grow">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        
                        <!-- Header Labels -->
                        <div class="col-span-3 grid grid-cols-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                            <div>Low Impact</div>
                            <div>Medium Impact</div>
                            <div>High Impact</div>
                        </div>

                        <!-- Row 1: High Likelihood -->
                        <!-- High-Low (Orange) -->
                        <div onclick="filterByMatrix('High', 'Low')" class="p-4 rounded-xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-amber-700 font-mono">{{ $matrix['High']['Low'] }}</span>
                            <span class="text-[9px] font-bold text-amber-600 uppercase">High/Low</span>
                        </div>
                        <!-- High-Medium (Red) -->
                        <div onclick="filterByMatrix('High', 'Medium')" class="p-4 rounded-xl bg-rose-50 hover:bg-rose-100/80 border border-rose-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-rose-700 font-mono">{{ $matrix['High']['Medium'] }}</span>
                            <span class="text-[9px] font-bold text-rose-600 uppercase">High/Med</span>
                        </div>
                        <!-- High-High (Red) -->
                        <div onclick="filterByMatrix('High', 'High')" class="p-4 rounded-xl bg-rose-100 hover:bg-rose-200/80 border-2 border-rose-300 cursor-pointer transition scale-102 shadow-xs select-none">
                            <span class="block text-lg font-black text-rose-800 font-mono">{{ $matrix['High']['High'] }}</span>
                            <span class="text-[9px] font-black text-rose-700 uppercase">Critical</span>
                        </div>

                        <!-- Row 2: Medium Likelihood -->
                        <!-- Med-Low (Green) -->
                        <div onclick="filterByMatrix('Medium', 'Low')" class="p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-emerald-700 font-mono">{{ $matrix['Medium']['Low'] }}</span>
                            <span class="text-[9px] font-bold text-emerald-600 uppercase">Med/Low</span>
                        </div>
                        <!-- Med-Med (Orange) -->
                        <div onclick="filterByMatrix('Medium', 'Medium')" class="p-4 rounded-xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-amber-700 font-mono">{{ $matrix['Medium']['Medium'] }}</span>
                            <span class="text-[9px] font-bold text-amber-600 uppercase">Med/Med</span>
                        </div>
                        <!-- Med-High (Red) -->
                        <div onclick="filterByMatrix('Medium', 'High')" class="p-4 rounded-xl bg-rose-50 hover:bg-rose-100/80 border border-rose-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-rose-700 font-mono">{{ $matrix['Medium']['High'] }}</span>
                            <span class="text-[9px] font-bold text-rose-600 uppercase">Med/High</span>
                        </div>

                        <!-- Row 3: Low Likelihood -->
                        <!-- Low-Low (Green) -->
                        <div onclick="filterByMatrix('Low', 'Low')" class="p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-emerald-700 font-mono">{{ $matrix['Low']['Low'] }}</span>
                            <span class="text-[9px] font-bold text-emerald-600 uppercase">Low/Low</span>
                        </div>
                        <!-- Low-Med (Green) -->
                        <div onclick="filterByMatrix('Low', 'Medium')" class="p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-emerald-700 font-mono">{{ $matrix['Low']['Medium'] }}</span>
                            <span class="text-[9px] font-bold text-emerald-600 uppercase">Low/Med</span>
                        </div>
                        <!-- Low-High (Orange) -->
                        <div onclick="filterByMatrix('Low', 'High')" class="p-4 rounded-xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200 cursor-pointer transition select-none">
                            <span class="block text-lg font-black text-amber-700 font-mono">{{ $matrix['Low']['High'] }}</span>
                            <span class="text-[9px] font-bold text-amber-600 uppercase">Low/High</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Toolbar -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="risk-search" oninput="applyFilters()" placeholder="Search threat description, mitigation..." class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" />
            </div>
            <div>
                <select id="risk-status" onchange="applyFilters()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">All Statuses</option>
                    <option value="Identified">Identified</option>
                    <option value="Monitoring">Monitoring</option>
                    <option value="Mitigated">Mitigated</option>
                </select>
            </div>
            <!-- Matrix Reset Button -->
            <button id="matrix-reset-btn" onclick="clearMatrixFilter()" class="hidden text-xs font-semibold text-hau-maroon hover:text-hau-maroon-light border border-hau-maroon/20 hover:border-hau-maroon bg-white px-3 py-2 rounded-lg transition">
                Clear Matrix Filter (&times;)
            </button>
        </div>
        <div class="text-xs text-gray-400 font-medium">
            Risk Registry Results: <span id="visible-count" class="font-bold text-gray-600">{{ $riskItems->count() }}</span> rows
        </div>
    </div>

    <!-- Risk Register Table -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Threat / Risk Description</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Likelihood</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Impact</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Mitigation Plan</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody id="risk-rows" class="divide-y divide-gray-200 bg-white">
                    @forelse ($riskItems as $r)
                        <tr class="hover:bg-gray-50/50 transition duration-100"
                            data-id="{{ $r->id }}"
                            data-program-id="{{ $r->program_id }}"
                            data-program-code="{{ $r->program->program_code }}"
                            data-desc="{{ $r->description }}"
                            data-likelihood="{{ $r->likelihood }}"
                            data-impact="{{ $r->impact }}"
                            data-mitigation="{{ $r->mitigation_plan }}"
                            data-status="{{ $r->status }}">
                            
                            <td class="px-6 py-4 font-bold text-hau-maroon text-sm">{{ $r->program->program_code }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 line-clamp-2 leading-relaxed max-w-[300px]" title="{{ $r->description }}">
                                    {{ $r->description }}
                                </div>
                            </td>
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
                            <td class="px-6 py-4 text-xs text-gray-600 hidden md:table-cell">
                                <div class="line-clamp-2 leading-relaxed max-w-[250px]" title="{{ $r->mitigation_plan }}">
                                    {{ $r->mitigation_plan ?? 'None formulated.' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-xs font-semibold">
                                @if ($r->status == 'Identified')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-100">Identified</span>
                                @elseif ($r->status == 'Monitoring')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">Monitoring</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">Mitigated</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="openEditModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('risk.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this risk profile?')" class="inline">
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
                        <tr><td colspan="7" class="text-center text-gray-400 py-12 text-sm">No quality risks logged. The portal is clear.</td></tr>
                    @endforelse
                    
                    <tr id="no-matches-row" class="hidden"><td colspan="7" class="text-center text-gray-400 py-12 text-sm">No risks match the current filters.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= MODAL WINDOWS ================= -->

<!-- Add Risk Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Log QA Risk Profile</h3>
            <button onclick="closeModal('add-modal')" class="text-white hover:text-hau-gold text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('risk.store') }}" method="POST">
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
                    <label for="add-desc" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Threat / Risk Description</label>
                    <textarea name="description" id="add-desc" required rows="3" placeholder="Describe the potential threat to quality or accreditation..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add-like" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Likelihood</label>
                        <select name="likelihood" id="add-like" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div>
                        <label for="add-imp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Impact</label>
                        <select name="impact" id="add-imp" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High" selected>High</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="add-mitigation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mitigation Plan</label>
                    <textarea name="mitigation_plan" id="add-mitigation" rows="3" placeholder="What plans are formulated to mitigate or monitor this threat..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                </div>

                <div>
                    <label for="add-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Monitoring Status</label>
                    <select name="status" id="add-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Identified" selected>Identified</option>
                        <option value="Monitoring">Monitoring</option>
                        <option value="Mitigated">Mitigated</option>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('add-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Save Risk</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Risk Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-md overflow-hidden transform scale-95 transition-all">
        <div class="bg-hau-maroon px-6 py-4 text-white flex items-center justify-between border-b-2 border-hau-gold">
            <h3 class="text-lg font-bold">Edit QA Risk Profile</h3>
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
                    <label for="edit-desc" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Threat / Risk Description</label>
                    <textarea name="description" id="edit-desc" required rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-like" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Likelihood</label>
                        <select name="likelihood" id="edit-like" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div>
                        <label for="edit-imp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Impact</label>
                        <select name="impact" id="edit-imp" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="edit-mitigation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mitigation Plan</label>
                    <textarea name="mitigation_plan" id="edit-mitigation" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon"></textarea>
                </div>

                <div>
                    <label for="edit-status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Monitoring Status</label>
                    <select name="status" id="edit-status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                        <option value="Identified">Identified</option>
                        <option value="Monitoring">Monitoring</option>
                        <option value="Mitigated">Mitigated</option>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('edit-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon hover:bg-hau-maroon-light text-white text-sm font-semibold rounded-lg shadow transition">Update Risk</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
    let matrixFilter = { likelihood: '', impact: '' };

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
        const id = row.getAttribute('data-id');
        const programId = row.getAttribute('data-program-id');
        const desc = row.getAttribute('data-desc');
        const likelihood = row.getAttribute('data-likelihood');
        const impact = row.getAttribute('data-impact');
        const mitigation = row.getAttribute('data-mitigation');
        const status = row.getAttribute('data-status');

        document.getElementById('edit-program_id').value = programId;
        document.getElementById('edit-desc').value = desc;
        document.getElementById('edit-like').value = likelihood;
        document.getElementById('edit-imp').value = impact;
        document.getElementById('edit-mitigation').value = mitigation;
        document.getElementById('edit-status').value = status;

        document.getElementById('edit-form').action = `/risk/${id}`;

        openModal('edit-modal');
    }

    function filterByMatrix(likelihood, impact) {
        matrixFilter.likelihood = likelihood;
        matrixFilter.impact = impact;

        // Show reset button
        document.getElementById('matrix-reset-btn').classList.remove('hidden');
        applyFilters();
    }

    function clearMatrixFilter() {
        matrixFilter.likelihood = '';
        matrixFilter.impact = '';
        
        // Hide reset button
        document.getElementById('matrix-reset-btn').classList.add('hidden');
        applyFilters();
    }

    function applyFilters() {
        const search = document.getElementById('risk-search').value.toLowerCase();
        const status = document.getElementById('risk-status').value;

        const rows = document.querySelectorAll('#risk-rows tr[data-id]');
        let count = 0;

        rows.forEach(row => {
            const code = row.getAttribute('data-program-code').toLowerCase();
            const desc = row.getAttribute('data-desc').toLowerCase();
            const likelihood = row.getAttribute('data-likelihood');
            const impact = row.getAttribute('data-impact');
            const mitigation = row.getAttribute('data-mitigation').toLowerCase();
            const cardStatus = row.getAttribute('data-status');

            // Search matches
            const matchesSearch = !search || 
                                  code.includes(search) || 
                                  desc.includes(search) || 
                                  mitigation.includes(search);
            
            // Status matches
            const matchesStatus = !status || cardStatus === status;

            // Matrix heat map matches
            const matchesMatrix = (!matrixFilter.likelihood || likelihood === matrixFilter.likelihood) &&
                                  (!matrixFilter.impact || impact === matrixFilter.impact);

            if (matchesSearch && matchesStatus && matchesMatrix) {
                row.classList.remove('hidden');
                count++;
            } else {
                row.classList.add('hidden');
            }
        });

        document.getElementById('visible-count').innerText = count;

        const noMatches = document.getElementById('no-matches-row');
        if (count === 0 && rows.length > 0) {
            noMatches.classList.remove('hidden');
        } else {
            noMatches.classList.add('hidden');
        }
    }
</script>
@endsection
