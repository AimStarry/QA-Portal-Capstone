@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Departments &amp; Responsible Units</h1>
            <p class="mt-1 text-sm text-gray-500">Manage organizational responsible units and department hierarchies.</p>
        </div>
    </div>
    @if (session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    <div class="max-w-3xl">
        <div class="bg-white shadow-sm border border-gray-200/80 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 class="text-base font-bold text-gray-900">Responsible Units</h2>
                <button onclick="document.getElementById('addUnitModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 rounded-xl bg-hau-maroon px-4 py-2 text-xs font-bold text-white hover:bg-hau-maroon-dark transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Unit
                </button>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($responsibleUnits as $unit)
                    <li class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $unit->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Code: <span class="font-mono font-semibold">{{ $unit->code ?? '—' }}</span>@if ($unit->college) &bull; {{ $unit->college->name }} @endif</p>
                        </div>
                        <form method="POST" action="{{ route('admin.categories.destroy-unit', $unit->responsible_unit_id) }}" onsubmit="return confirm('Delete this unit?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Unit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-xs text-gray-400 italic">No responsible units yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Add Unit Modal -->
    <div id="addUnitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-md overflow-hidden transform scale-100 transition-all duration-200">
            <!-- Header with dark maroon gradient -->
            <div class="modal-dark-header flex items-center justify-between border-b border-white/10" style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
                <div class="flex items-center gap-3.5">
                    <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                        <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Add Responsible Unit</h3>
                        <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Define functional department or unit entity</p>
                    </div>
                </div>
                <button onclick="document.getElementById('addUnitModal').classList.add('hidden')" class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer" style="color: #ffffff;" aria-label="Close modal">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.categories.store-unit') }}">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Planning and Development Office" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Code / Acronym</label>
                        <input type="text" name="code" placeholder="e.g. PDO" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">School / College</label>
                        <select name="college_id" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition">
                            <option value="">None (University-wide)</option>
                            @foreach ($colleges as $c)
                                <option value="{{ $c->college_id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Parent Unit</label>
                        <select name="parent_unit_id" class="block w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon focus:bg-white transition">
                            <option value="">Top-level Unit</option>
                            @foreach ($responsibleUnits as $ru)
                                <option value="{{ $ru->responsible_unit_id }}">{{ $ru->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('addUnitModal').classList.add('hidden')" class="px-4 py-2.5 border border-gray-200 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Create Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection