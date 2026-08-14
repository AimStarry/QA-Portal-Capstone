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
<div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">{{ session('error') }}</div>
@endif
<div class="max-w-3xl">
<div class="bg-white shadow rounded-xl overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
<h2 class="text-base font-semibold text-gray-800">Responsible Units</h2>
<button onclick="document.getElementById('addUnitModal').classList.remove('hidden')" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">+ Add Unit</button>
</div>
<ul class="divide-y divide-gray-100">
@forelse ($responsibleUnits as $unit)
<li class="px-6 py-4 flex items-start justify-between gap-4">
<div>
<p class="font-medium text-gray-900">{{ $unit->name }}</p>
<p class="text-xs text-gray-400 mt-0.5">Code: {{ $unit->code ?? chr(8212) }}@if ($unit->college) &bull; {{ $unit->college->name }} @endif</p>
</div>
<form method="POST" action="{{ route('admin.categories.destroy-unit', $unit->responsible_unit_id) }}" onsubmit="return confirm('Delete this unit?')">
@csrf @method('DELETE')
<button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
</form>
</li>
@empty
<li class="px-6 py-8 text-center text-sm text-gray-400">No responsible units yet.</li>
@endforelse
</ul>
</div>
</div>

<div id="addUnitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
<div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
<h3 class="text-lg font-semibold text-gray-900 mb-4">Add Responsible Unit</h3>
<form method="POST" action="{{ route('admin.categories.store-unit') }}">
@csrf
<div class="space-y-4">
<div><label class="block text-sm font-medium text-gray-700 mb-1">Name *</label><input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Code</label><input type="text" name="code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">School / College</label>
<select name="college_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">None</option>@foreach ($colleges as $c)<option value="{{ $c->college_id }}">{{ $c->name }}</option>@endforeach</select></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Parent Unit</label>
<select name="parent_unit_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Top-level</option>@foreach ($responsibleUnits as $ru)<option value="{{ $ru->responsible_unit_id }}">{{ $ru->name }}</option>@endforeach</select></div>
</div>
<div class="mt-6 flex justify-end gap-3">
<button type="button" onclick="document.getElementById('addUnitModal').classList.add('hidden')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancel</button>
<button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create</button>
</div>
</form>
</div>
</div>
</div>
@endsection