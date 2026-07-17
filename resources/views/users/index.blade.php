@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">User Account Management</h1>
            <p class="text-xs text-gray-500 font-semibold mt-1 uppercase tracking-wider">Configure account roles and school/unit viewport scoping</p>
        </div>
        <button onclick="openAddUserModal()" class="inline-flex items-center justify-center px-4 py-2.5 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition gap-2 uppercase tracking-wider">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            Create User Account
        </button>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 text-xs font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Users Table Directory -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Assigned Scope (School/Unit)</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50/50 transition duration-100"
                            data-id="{{ $user->id }}"
                            data-username="{{ $user->username }}"
                            data-name="{{ $user->name }}"
                            data-first-name="{{ $user->first_name }}"
                            data-last-name="{{ $user->last_name }}"
                            data-email="{{ $user->email }}"
                            data-usertype="{{ $user->usertype }}"
                            data-school-id="{{ $user->school_id }}"
                            data-college-id="{{ $user->college_id }}"
                            data-unit-id="{{ $user->unit_id }}">
                            
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-gray-600 font-mono">{{ $user->username }}</td>
                            <td class="px-6 py-4">
                                @if($user->usertype === 'QA Admin')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black bg-hau-maroon/5 text-hau-maroon border border-hau-maroon/10 uppercase tracking-wide">QA Admin</span>
                                @elseif($user->usertype === 'Dean')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black bg-indigo-50 text-indigo-750 border border-indigo-100 uppercase tracking-wide">Dean</span>
                                @elseif($user->usertype === 'Principal')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-wide">Principal</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-100 uppercase tracking-wide">Head of Unit</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-semibold">
                                @if($user->usertype === 'QA Admin')
                                    <span class="text-gray-400 italic">Entire University</span>
                                @elseif($user->usertype === 'Dean' || $user->usertype === 'Principal')
                                    {{ $user->college->name ?? 'Unassigned College' }}
                                @else
                                    {{ $user->unit->name ?? 'Unassigned Unit' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @php
                                    $isSelf       = $user->id === auth()->id();
                                    $isAuthAdmin  = auth()->user()->username === 'admin';
                                    $isTargetAdmin = $user->usertype === 'QA Admin';

                                    // Edit: admin can always edit themselves; anyone can edit non-admins;
                                    // only primary admin can edit other QA Admins.
                                    $canEdit = ($isAuthAdmin && $isSelf)
                                        || (!$isSelf && !$isTargetAdmin)
                                        || (!$isSelf && $isAuthAdmin);

                                    // Delete: never allow self-deletion; same hierarchy as edit otherwise.
                                    $canDelete = !$isSelf && $canEdit;
                                @endphp
                                @if($canEdit)
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="openEditUserModal(this.closest('tr'))" class="p-1 text-gray-500 hover:text-hau-maroon hover:bg-gray-100 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        @if($canDelete)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-500 hover:text-rose-600 hover:bg-gray-100 rounded-lg transition" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic font-medium">Protected</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= CREATE USER MODAL ================= -->
<div id="add-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition duration-150">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-black text-base text-gray-900 uppercase tracking-wider">Create User Account</h3>
            <button onclick="closeModal('add-user-modal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label for="add-username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" id="add-username" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="add-first_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">First Name</label>
                    <input type="text" name="first_name" id="add-first_name" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                </div>
                <div>
                    <label for="add-last_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Last Name</label>
                    <input type="text" name="last_name" id="add-last_name" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="add-password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" id="add-password" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" placeholder="Min. 8 chars (letters, mixed case, numbers, symbols)">
                </div>
                <div>
                    <label for="add-email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" id="add-email" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" placeholder="e.g. user@hau.edu.ph">
                </div>
            </div>

            <div>
                <label for="add-usertype" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">User Role</label>
                <select name="usertype" id="add-usertype" required onchange="toggleScopeInputs('add')" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="Dean" selected>Dean</option>
                    <option value="Principal">Principal</option>
                    <option value="Head of Unit">Head of Unit</option>
                    @if(auth()->user()->username === 'admin')
                        <option value="QA Admin">QA Admin</option>
                    @endif
                </select>
            </div>

            <!-- Scoping fields -->
            <div id="add-college-group">
                <label for="add-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Assigned School / College</label>
                <select name="college_id" id="add-college" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">-- Choose School --</option>
                    @foreach($colleges as $col)
                        <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->code }})</option>
                    @endforeach
                </select>
            </div>

            <div id="add-unit-group" class="hidden">
                <label for="add-unit" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Assigned Support Office / Unit</label>
                <select name="unit_id" id="add-unit" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">-- Choose Unit --</option>
                    @foreach($units as $un)
                        <option value="{{ $un->id }}">{{ $un->name }} ({{ $un->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('add-user-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon text-white text-sm font-bold rounded-lg hover:bg-hau-maroon-dark transition">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT USER MODAL ================= -->
<div id="edit-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-200 w-full max-w-lg overflow-hidden transform scale-95 transition duration-150">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-black text-base text-gray-900 uppercase tracking-wider">Edit User Account</h3>
            <button onclick="closeModal('edit-user-modal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <form id="edit-user-form" action="" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="edit-username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" id="edit-username" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-first_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">First Name</label>
                    <input type="text" name="first_name" id="edit-first_name" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                </div>
                <div>
                    <label for="edit-last_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Last Name</label>
                    <input type="text" name="last_name" id="edit-last_name" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password <span class="text-[9px] text-gray-450 lowercase normal-case">(leave blank to keep)</span></label>
                    <input type="password" name="password" id="edit-password" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" placeholder="Min. 8 chars (letters, mixed case, numbers, symbols)">
                </div>
                <div>
                    <label for="edit-email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" id="edit-email" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon" placeholder="e.g. user@hau.edu.ph">
                </div>
            </div>

            <div>
                <label for="edit-usertype" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">User Role</label>
                <select name="usertype" id="edit-usertype" required onchange="toggleScopeInputs('edit')" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="Dean">Dean</option>
                    <option value="Principal">Principal</option>
                    <option value="Head of Unit">Head of Unit</option>
                    @if(auth()->user()->username === 'admin')
                        <option value="QA Admin">QA Admin</option>
                    @endif
                </select>
            </div>

            <!-- Scoping fields -->
            <div id="edit-college-group">
                <label for="edit-college" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Assigned School / College</label>
                <select name="college_id" id="edit-college" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">-- Choose School --</option>
                    @foreach($colleges as $col)
                        <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->code }})</option>
                    @endforeach
                </select>
            </div>

            <div id="edit-unit-group" class="hidden">
                <label for="edit-unit" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Assigned Support Office / Unit</label>
                <select name="unit_id" id="edit-unit" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon">
                    <option value="">-- Choose Unit --</option>
                    @foreach($units as $un)
                        <option value="{{ $un->id }}">{{ $un->name }} ({{ $un->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('edit-user-modal')" class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-hau-maroon text-white text-sm font-bold rounded-lg hover:bg-hau-maroon-dark transition">Update Account</button>
            </div>
        </form>
    </div>
</div>

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
        }, 150);
    }

    function openAddUserModal() {
        document.getElementById('add-username').value = '';
        document.getElementById('add-first_name').value = '';
        document.getElementById('add-last_name').value = '';
        document.getElementById('add-email').value = '';
        document.getElementById('add-password').value = '';
        document.getElementById('add-usertype').value = 'Dean';
        document.getElementById('add-college').value = '';
        document.getElementById('add-unit').value = '';
        toggleScopeInputs('add');
        openModal('add-user-modal');
    }

    function openEditUserModal(row) {
        const id = row.getAttribute('data-id');
        const username = row.getAttribute('data-username');
        const firstName = row.getAttribute('data-first-name') || '';
        const lastName = row.getAttribute('data-last-name') || '';
        const email = row.getAttribute('data-email') || '';
        const usertype = row.getAttribute('data-usertype');
        const collegeId = row.getAttribute('data-college-id') || '';
        const unitId = row.getAttribute('data-unit-id') || '';

        document.getElementById('edit-username').value = username;
        document.getElementById('edit-first_name').value = firstName;
        document.getElementById('edit-last_name').value = lastName;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-usertype').value = usertype;
        document.getElementById('edit-college').value = collegeId;
        document.getElementById('edit-unit').value = unitId;

        toggleScopeInputs('edit');
        document.getElementById('edit-user-form').action = `/users/${id}`;
        openModal('edit-user-modal');
    }

    function toggleScopeInputs(prefix) {
        const role = document.getElementById(prefix + '-usertype').value;
        const collegeGroup = document.getElementById(prefix + '-college-group');
        const unitGroup = document.getElementById(prefix + '-unit-group');

        if (role === 'Dean' || role === 'Principal') {
            collegeGroup.classList.remove('hidden');
            unitGroup.classList.add('hidden');
            document.getElementById(prefix + '-unit').value = '';
        } else if (role === 'Head of Unit') {
            collegeGroup.classList.add('hidden');
            unitGroup.classList.remove('hidden');
            document.getElementById(prefix + '-college').value = '';
        } else {
            collegeGroup.classList.add('hidden');
            unitGroup.classList.add('hidden');
            document.getElementById(prefix + '-college').value = '';
            document.getElementById(prefix + '-unit').value = '';
        }
    }
</script>
@endsection
