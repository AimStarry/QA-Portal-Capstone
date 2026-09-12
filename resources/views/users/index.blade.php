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
<div id="add-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl overflow-hidden transform scale-95 transition-all duration-200 flex flex-col max-h-[92vh]">

        <!-- Rich Dark Maroon Header -->
        <div class="modal-dark-header flex items-center justify-between text-white border-b border-black/20 shrink-0 shadow-md"
             style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Create User Account</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Set up credentials and assign institutional department access</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('add-user-modal')" 
                class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer focus:outline-none shrink-0" 
                style="color: #ffffff;"
                title="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-6 overflow-y-auto flex-1">
            @csrf
            
            <!-- Section 1: User Identity & Contact -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Personal & Login Information
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-first_name" class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="first_name" id="add-first_name" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                            placeholder="e.g. Juan">
                    </div>
                    <div>
                        <label for="add-last_name" class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="last_name" id="add-last_name" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                            placeholder="e.g. Dela Cruz">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-username" class="block text-xs font-semibold text-gray-700 mb-1.5">Username <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" id="add-username" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                            placeholder="e.g. jdelacruz">
                    </div>
                    <div>
                        <label for="add-email" class="block text-xs font-semibold text-gray-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="add-email" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" 
                            placeholder="e.g. jdelacruz@hau.edu.ph">
                    </div>
                </div>
            </div>

            <!-- Section 2: Role & Department Scope -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Role & Institutional Scope
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-usertype" class="block text-xs font-semibold text-gray-700 mb-1.5">User Role <span class="text-rose-500">*</span></label>
                        <select name="usertype" id="add-usertype" required onchange="toggleScopeInputs('add')" 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                            <option value="Dean" selected>Dean</option>
                            <option value="Principal">Principal</option>
                            <option value="Head of Unit">Head of Unit</option>
                            @if(auth()->user()->username === 'admin')
                                <option value="QA Admin">QA Admin</option>
                            @endif
                        </select>
                    </div>

                    <!-- Dynamic Scoping field -->
                    <div>
                        <div id="add-college-group">
                            <label for="add-college" class="block text-xs font-semibold text-gray-700 mb-1.5">Assigned School / College <span class="text-rose-500">*</span></label>
                            <select name="college_id" id="add-college" 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="">-- Choose School --</option>
                                @foreach($colleges as $col)
                                    <option value="{{ $col->college_id }}">{{ $col->name }} ({{ $col->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="add-unit-group" class="hidden">
                            <label for="add-unit" class="block text-xs font-semibold text-gray-700 mb-1.5">Assigned Support Office / Unit <span class="text-rose-500">*</span></label>
                            <select name="unit_id" id="add-unit" 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="">-- Choose Unit --</option>
                                @foreach($units as $un)
                                    <option value="{{ $un->unit_id }}">{{ $un->name }} ({{ $un->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="add-admin-group" class="hidden">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Access Scope</label>
                            <div class="px-3.5 py-2.5 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 font-medium flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                University-Wide Access (All Colleges & Units)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Password / Credentials -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Security Credentials
                    </span>
                </div>

                <div>
                    <label for="add-password" class="block text-xs font-semibold text-gray-700 mb-1.5">Initial Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="add-password" required 
                            class="block w-full px-3.5 pr-11 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" 
                            placeholder="Minimum 8 characters">
                        
                        <!-- Eye Toggle Button -->
                        <button type="button" onclick="togglePasswordVisibility('add-password', 'add-eye-icon')" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-700 transition cursor-pointer focus:outline-none" 
                            title="Show / Hide Password">
                            <svg id="add-eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Users will use this temporary password to log in and can change it anytime.</p>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-200 mt-6 shrink-0">
                <button type="button" onclick="closeModal('add-user-modal')" 
                    class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-100 transition cursor-pointer shadow-xs">
                    Cancel
                </button>
                <button type="submit" 
                    class="inline-flex items-center gap-2 px-5 py-2 bg-hau-maroon text-white text-sm font-bold rounded-xl hover:bg-hau-maroon-dark transition cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT USER MODAL ================= -->
<div id="edit-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden transition duration-150">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl overflow-hidden transform scale-95 transition-all duration-200 flex flex-col max-h-[92vh]">

        <!-- Rich Dark Maroon Header -->
        <div class="modal-dark-header flex items-center justify-between text-white border-b border-black/20 shrink-0 shadow-md"
             style="background: linear-gradient(135deg, #5c0000 0%, #2f0000 55%, #150000 100%); padding: 1.25rem 1.5rem; color: #ffffff;">
            <div class="flex items-center gap-3.5">
                <div class="modal-icon-badge w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-inner shrink-0" style="color: #ffffff;">
                    <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-wide" style="color: #ffffff;">Edit User Account</h3>
                    <p class="text-xs text-white/80 font-normal mt-0.5" style="color: rgba(255, 255, 255, 0.85);">Update user credentials, role permissions, and assigned department</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-user-modal')" 
                class="modal-close-btn p-2 text-white hover:bg-white/20 rounded-xl transition cursor-pointer focus:outline-none shrink-0" 
                style="color: #ffffff;"
                title="Close modal">
                <svg class="w-5 h-5 text-white" style="color: #ffffff; stroke: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="edit-user-form" action="" method="POST" class="p-6 space-y-6 overflow-y-auto flex-1">
            @csrf
            @method('PUT')
            
            <!-- Section 1: User Identity & Contact -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Personal & Login Information
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-first_name" class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="first_name" id="edit-first_name" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400">
                    </div>
                    <div>
                        <label for="edit-last_name" class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="last_name" id="edit-last_name" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-username" class="block text-xs font-semibold text-gray-700 mb-1.5">Username <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" id="edit-username" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400">
                    </div>
                    <div>
                        <label for="edit-email" class="block text-xs font-semibold text-gray-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="edit-email" required 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400">
                    </div>
                </div>
            </div>

            <!-- Section 2: Role & Department Scope -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Role & Institutional Scope
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-usertype" class="block text-xs font-semibold text-gray-700 mb-1.5">User Role <span class="text-rose-500">*</span></label>
                        <select name="usertype" id="edit-usertype" required onchange="toggleScopeInputs('edit')" 
                            class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                            <option value="Dean">Dean</option>
                            <option value="Principal">Principal</option>
                            <option value="Head of Unit">Head of Unit</option>
                            @if(auth()->user()->username === 'admin')
                                <option value="QA Admin">QA Admin</option>
                            @endif
                        </select>
                    </div>

                    <!-- Dynamic Scoping field -->
                    <div>
                        <div id="edit-college-group">
                            <label for="edit-college" class="block text-xs font-semibold text-gray-700 mb-1.5">Assigned School / College <span class="text-rose-500">*</span></label>
                            <select name="college_id" id="edit-college" 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="">-- Choose School --</option>
                                @foreach($colleges as $col)
                                    <option value="{{ $col->college_id }}">{{ $col->name }} ({{ $col->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="edit-unit-group" class="hidden">
                            <label for="edit-unit" class="block text-xs font-semibold text-gray-700 mb-1.5">Assigned Support Office / Unit <span class="text-rose-500">*</span></label>
                            <select name="unit_id" id="edit-unit" 
                                class="block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition cursor-pointer">
                                <option value="">-- Choose Unit --</option>
                                @foreach($units as $un)
                                    <option value="{{ $un->unit_id }}">{{ $un->name }} ({{ $un->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="edit-admin-group" class="hidden">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Access Scope</label>
                            <div class="px-3.5 py-2.5 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 font-medium flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                University-Wide Access (All Colleges & Units)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Password / Security -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-1.5 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-hau-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Security & Password Reset
                    </span>
                    <span class="text-[11px] font-medium text-gray-400 italic">Optional</span>
                </div>

                <div>
                    <label for="edit-password" class="block text-xs font-semibold text-gray-700 mb-1.5">
                        New Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="edit-password" 
                            class="block w-full px-3.5 pr-11 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400" 
                            placeholder="Leave blank to keep current password">
                        
                        <!-- Eye Toggle Button -->
                        <button type="button" onclick="togglePasswordVisibility('edit-password', 'edit-eye-icon')" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-700 transition cursor-pointer focus:outline-none" 
                            title="Show / Hide Password">
                            <svg id="edit-eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Only fill this field if you wish to reset or change this user's password.</p>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-200 mt-6 shrink-0">
                <button type="button" onclick="closeModal('edit-user-modal')" 
                    class="px-4 py-2 border border-gray-300 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-100 transition cursor-pointer shadow-xs">
                    Cancel
                </button>
                <button type="submit" 
                    class="inline-flex items-center gap-2 px-5 py-2 bg-hau-maroon text-white text-sm font-bold rounded-xl hover:bg-hau-maroon-dark transition cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Update Account
                </button>
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
        
        // Reset password visibility to hidden
        const pwdInput = document.getElementById('add-password');
        if (pwdInput) pwdInput.type = 'password';
        const eyeIcon = document.getElementById('add-eye-icon');
        if (eyeIcon) {
            eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
        }

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

        // Reset password visibility to hidden
        const pwdInput = document.getElementById('edit-password');
        if (pwdInput) pwdInput.type = 'password';
        const eyeIcon = document.getElementById('edit-eye-icon');
        if (eyeIcon) {
            eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
        }

        toggleScopeInputs('edit');
        document.getElementById('edit-user-form').action = `/users/${id}`;
        openModal('edit-user-modal');
    }

    function toggleScopeInputs(prefix) {
        const role = document.getElementById(prefix + '-usertype').value;
        const collegeGroup = document.getElementById(prefix + '-college-group');
        const unitGroup = document.getElementById(prefix + '-unit-group');
        const adminGroup = document.getElementById(prefix + '-admin-group');

        if (role === 'Dean' || role === 'Principal') {
            if (collegeGroup) collegeGroup.classList.remove('hidden');
            if (unitGroup) unitGroup.classList.add('hidden');
            if (adminGroup) adminGroup.classList.add('hidden');
            const unitEl = document.getElementById(prefix + '-unit');
            if (unitEl) unitEl.value = '';
        } else if (role === 'Head of Unit') {
            if (collegeGroup) collegeGroup.classList.add('hidden');
            if (unitGroup) unitGroup.classList.remove('hidden');
            if (adminGroup) adminGroup.classList.add('hidden');
            const collegeEl = document.getElementById(prefix + '-college');
            if (collegeEl) collegeEl.value = '';
        } else {
            if (collegeGroup) collegeGroup.classList.add('hidden');
            if (unitGroup) unitGroup.classList.add('hidden');
            if (adminGroup) adminGroup.classList.remove('hidden');
            const collegeEl = document.getElementById(prefix + '-college');
            if (collegeEl) collegeEl.value = '';
            const unitEl = document.getElementById(prefix + '-unit');
            if (unitEl) unitEl.value = '';
        }
    }

    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>`;
        } else {
            input.type = 'password';
            icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
        }
    }
</script>
@endsection
