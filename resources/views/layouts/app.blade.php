<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50/50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'HAU QA Portal') }} - Quality Assurance & Accreditation</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Style and JS Compilation -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900">

    @php
        // Enforce role based on usertype
        $user = auth()->user();
        if ($user && $user->usertype !== 'QA Admin') {
            $role = 'Unit or Department';
            session(['active_role' => 'Unit or Department']);
        } else {
            $role = 'QA Admin';
            session(['active_role' => 'QA Admin']);
        }
        $formerSchoolNames = [
            'School of Computing' => 'formerly College of Information and Communications Technology (CICT)',
            'School of Nursing and Allied Medical Sciences' => 'formerly College of Nursing (CON)'
        ];
        $notifCount = 0;
        $notifItems = [];

        if ($role === 'QA Admin') {
            // 1. Pending Approvals
            $pendingApprovalsCount = \App\Models\ComplianceRecord::where('approval_state', 'Pending Approval')->count();
            if ($pendingApprovalsCount > 0) {
                $notifCount += $pendingApprovalsCount;
                $notifItems[] = [
                    'type' => 'pending_approvals',
                    'title' => 'Pending Compliance Approvals',
                    'message' => "You have {$pendingApprovalsCount} compliance updates awaiting approval.",
                    'link' => route('dashboard'),
                    'icon_bg' => 'bg-hau-gold/15 text-hau-maroon-dark',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>'
                ];
            }

            // 2. Expired Accreditations
            $expiredAccredsCount = \App\Models\Accreditation::whereIn('status', ['Expired', 'Expiring Soon'])->count();
            if ($expiredAccredsCount > 0) {
                $notifCount += $expiredAccredsCount;
                $notifItems[] = [
                    'type' => 'expired_accreditations',
                    'title' => 'Accreditation Warning',
                    'message' => "{$expiredAccredsCount} program accreditations are Expired or Expiring Soon.",
                    'link' => route('dashboard'),
                    'icon_bg' => 'bg-rose-50 text-rose-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
                ];
            }

            // 3. Overdue Tasks
            $overdueTasksCount = \App\Models\ComplianceRecord::whereIn('status', ['Non-Compliant', 'Pending'])
                ->where('approval_state', '!=', 'Pending Approval')
                ->where('due_date', '<', now()->toDateString())
                ->count();
            if ($overdueTasksCount > 0) {
                $notifCount += 1;
                $notifItems[] = [
                    'type' => 'overdue_tasks',
                    'title' => 'Overdue Compliance Tasks',
                    'message' => "{$overdueTasksCount} compliance tasks are past their due dates.",
                    'link' => route('compliance.index') . '?status=Pending',
                    'icon_bg' => 'bg-rose-50 text-rose-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                ];
            }
            // 4. Database Notifications (Unit check-off recommendations)
            $dbNotifications = \App\Models\Notification::where('is_read', false)->orderBy('created_at', 'desc')->get();
            foreach ($dbNotifications as $dbNotif) {
                $notifCount += 1;
                $notifItems[] = [
                    'id' => $dbNotif->id,
                    'type' => 'db_notification',
                    'title' => 'Checklist Item Checked Off',
                    'message' => $dbNotif->message,
                    'link' => $dbNotif->link ?? route('compliance.index'),
                    'icon_bg' => 'bg-emerald-50 text-emerald-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>',
                    'is_db' => true,
                ];
            }
        } else {
            // Unit or Department
            // 1. Rejected updates
            $rejectedCount = \App\Models\ComplianceRecord::where('approval_state', 'Rejected')->count();
            if ($rejectedCount > 0) {
                $notifCount += $rejectedCount;
                $notifItems[] = [
                    'type' => 'rejected_tasks',
                    'title' => 'Rejected Proposals',
                    'message' => "You have {$rejectedCount} updates rejected by Admin requiring action.",
                    'link' => route('dashboard'),
                    'icon_bg' => 'bg-rose-50 text-rose-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
                ];
            }

            // 2. Overdue Tasks
            $overdueTasksCount = \App\Models\ComplianceRecord::whereIn('status', ['Non-Compliant', 'Pending'])
                ->where('approval_state', '!=', 'Pending Approval')
                ->where('due_date', '<', now()->toDateString())
                ->count();
            if ($overdueTasksCount > 0) {
                $notifCount += 1;
                $notifItems[] = [
                    'type' => 'overdue_tasks',
                    'title' => 'Overdue Tasks',
                    'message' => "You have {$overdueTasksCount} assigned compliance tasks past due.",
                    'link' => route('compliance.index'),
                    'icon_bg' => 'bg-rose-50 text-rose-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                ];
            }
        }
    @endphp

    <div class="h-full flex overflow-hidden bg-gray-100">
        
        <!-- Sidebar Navigation for Large Screens -->
        <aside class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64 border-r border-hau-gold/30 bg-hau-maroon text-white">
                
                <!-- HAU Header Banner -->
                <div class="flex items-center h-20 px-6 bg-hau-maroon-dark border-b border-hau-gold/20 gap-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center border-2 border-hau-gold overflow-hidden flex-shrink-0">
                        <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" class="w-full h-full object-cover transform scale-[1.8]">
                    </div>
                    <div>
                        <h2 class="font-bold text-sm leading-tight tracking-wide text-white">Holy Angel University</h2>
                        <span class="text-[10px] text-hau-gold font-semibold uppercase tracking-wider">QA Portal</span>
                    </div>
                </div>

                <!-- Navigation Links based on Active Role -->
                <div class="flex-1 flex flex-col justify-between overflow-y-auto px-4 py-6">
                    <nav class="space-y-2">
                        <!-- 1. Dashboard (Shared) -->
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('dashboard') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                            <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path>
                            </svg>
                            Dashboard
                        </a>

                        @if(in_array(auth()->user()->usertype, ['Dean', 'Principal']) || (auth()->user()->usertype === 'QA Admin' && $role === 'QA Admin'))
                            <!-- 2. Programs -->
                            <a href="{{ route('programs.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('programs.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('programs.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                Academic Programs
                            </a>
                        @endif

                        @if(auth()->user()->usertype === 'QA Admin' && $role === 'QA Admin')
                            <!-- 3. Accreditations -->
                            <a href="{{ route('accreditations.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('accreditations.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('accreditations.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Accreditations
                            </a>
                        @endif

                        <!-- 4. Compliance (Shared) -->
                        <a href="{{ route('compliance.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('compliance.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                            <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('compliance.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            Compliance Tracker
                        </a>

                        @if(in_array(auth()->user()->usertype, ['Dean', 'Principal']) || (auth()->user()->usertype === 'QA Admin' && $role === 'QA Admin'))
                            <!-- 5. Graduates -->
                            <a href="{{ route('graduates.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('graduates.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('graduates.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7"></path>
                                </svg>
                                Graduates Tracker
                            </a>
                        @endif

                        @if(auth()->user()->usertype === 'QA Admin' && $role === 'QA Admin')
                            <!-- 6. Risk Monitor -->
                            <a href="{{ route('risk.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('risk.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('risk.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Risk Monitor
                            </a>

                            <!-- 7. User Accounts -->
                            <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('users.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('users.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                User Accounts
                            </a>


                        @endif
                    </nav>

                    <!-- Sidebar Footer: User Info + Logout -->
                    <div class="mt-auto pt-4 border-t border-white/10 space-y-3">
                        <!-- Signed-in user info -->
                        <div class="flex items-center gap-3 px-2">
                            <div class="w-8 h-8 rounded-full bg-hau-gold flex items-center justify-center text-hau-maroon font-bold text-xs flex-shrink-0">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name ?? 'User' }}</p>
                                <p class="text-hau-gold-light text-[10px] truncate">{{ ucwords(str_replace('_', ' ', auth()->user()->usertype ?? '')) }}</p>
                            </div>
                        </div>

                        <!-- Logout Button -->
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-all duration-200 group hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/30"
                                style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 40%, #b91c1c 100%); border: 1px solid rgba(255,255,255,0.15);">
                                <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Sign Out
                            </button>
                        </form>

                        <p class="text-center text-[10px] text-hau-gold-light/60 font-medium tracking-wide pb-1">Office of Academic Quality</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Portal Workspace Area -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            
            <!-- Top Mobile Bar & Portal Title & Role Switcher -->
            <header class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow border-b border-gray-200">
                <!-- Mobile Navigation Toggle -->
                <div class="lg:hidden pl-4 flex items-center">
                    <button type="button" onclick="toggleMobileMenu()" class="text-gray-500 hover:text-hau-maroon focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div>
                        <span class="text-sm font-semibold text-gray-500 hidden sm:inline">HAU QA Portal &middot; </span>
                        <span class="text-xs font-semibold text-hau-maroon bg-hau-maroon/5 border border-hau-maroon/10 rounded-full px-2.5 py-0.5">Academic Year 2026-2027</span>
                    </div>

                    <!-- Role Switcher Form & Notifications -->
                    <div class="flex items-center gap-4">
                        
                        <!-- Notification Center (Bell) -->
                        <div class="relative">
                            <button id="notif-bell-btn" onclick="toggleNotifications()" class="relative p-1.5 text-gray-400 hover:text-hau-maroon hover:bg-gray-100 rounded-xl transition focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if ($notifCount > 0)
                                    <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[9px] font-black text-white ring-2 ring-white">
                                        {{ $notifCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- Notification Dropdown Panel -->
                            <div id="notif-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 py-2 z-50 hidden">
                                <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-800">QA Alerts Center</span>
                                    <span class="text-[10px] font-bold text-hau-maroon bg-hau-maroon/5 px-2 py-0.5 rounded-full">{{ $notifCount }} Alerts</span>
                                </div>
                                <div class="max-h-64 overflow-y-auto divide-y divide-gray-100">
                                    @forelse($notifItems as $item)
                                        <div class="flex items-start p-4 hover:bg-gray-50/80 transition gap-3 relative group">
                                            <a href="{{ $item['link'] }}" class="flex items-start gap-3 flex-1">
                                                <div class="p-2 rounded-lg {{ $item['icon_bg'] }} flex-shrink-0">
                                                    {!! $item['icon_svg'] !!}
                                                </div>
                                                <div class="space-y-0.5 pr-4">
                                                    <h5 class="text-xs font-bold text-gray-900 leading-snug">{{ $item['title'] }}</h5>
                                                    <p class="text-[11px] text-gray-550 leading-normal">{{ $item['message'] }}</p>
                                                </div>
                                            </a>
                                            @if(!empty($item['is_db']))
                                                <button onclick="markAsRead(event, {{ $item['id'] }}, this)" class="absolute right-3 top-3 p-1 text-gray-400 hover:text-rose-600 rounded transition opacity-0 group-hover:opacity-100" title="Dismiss">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="px-4 py-6 text-center text-gray-400 text-xs italic">
                                            All clear! No urgent QA alerts at this time.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>


                        <div class="w-8 h-8 rounded-full bg-hau-maroon text-white flex items-center justify-center font-bold text-xs shadow-xs border border-hau-gold/30" title="{{ auth()->user()->name }} ({{ auth()->user()->usertype }})">
                            @if(auth()->user()->usertype === 'QA Admin')
                                QA
                            @elseif(auth()->user()->usertype === 'Dean')
                                DN
                            @elseif(auth()->user()->usertype === 'Principal')
                                PC
                            @else
                                HU
                            @endif
                        </div>

                        <form action="{{ route('logout') }}" method="POST" class="inline flex items-center">
                            @csrf
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition focus:outline-none" title="Sign Out">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Mobile Sidebar Overlay Menu -->
            <div id="mobile-menu" class="fixed inset-0 z-40 lg:hidden hidden" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="toggleMobileMenu()"></div>
                <div class="relative flex-1 flex flex-col max-w-xs w-full bg-hau-maroon text-white h-full z-50 shadow-xl">
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button type="button" onclick="toggleMobileMenu()" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center h-20 px-6 bg-hau-maroon-dark border-b border-hau-gold/20 gap-3">
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center border border-hau-gold overflow-hidden">
                            <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" class="w-full h-full object-cover transform scale-[1.8]">
                        </div>
                        <div>
                            <h2 class="font-bold text-xs text-white leading-tight">Holy Angel University</h2>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col overflow-y-auto px-4 py-4 space-y-2">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('dashboard') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Dashboard</a>
                        @if(in_array(auth()->user()->usertype, ['Dean', 'Principal']) || $role === 'QA Admin')
                            <a href="{{ route('programs.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('programs.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Academic Programs</a>
                        @endif
                        @if($role === 'QA Admin')
                            <a href="{{ route('accreditations.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('accreditations.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Accreditations</a>
                        @endif
                        <a href="{{ route('compliance.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('compliance.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Compliance Tracker</a>
                        @if(in_array(auth()->user()->usertype, ['Dean', 'Principal']) || $role === 'QA Admin')
                            <a href="{{ route('graduates.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('graduates.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Graduates Tracker</a>
                        @endif

                        @if($role === 'QA Admin')
                            <a href="{{ route('risk.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('risk.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Risk Monitor</a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Page Specific Main Content -->
            <main class="flex-1 relative overflow-y-auto focus:outline-none bg-gray-50/50 p-6 lg:p-8">
                
                @if (session('success'))
                    <div id="toast-success" class="mb-6 flex items-center p-4 text-emerald-800 border-l-4 border-emerald-500 bg-emerald-50 rounded-xl shadow-xs" role="alert">
                        <svg class="flex-shrink-0 w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                        </svg>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                        <button type="button" onclick="document.getElementById('toast-success').remove()" class="ml-auto -mx-1.5 -my-1.5 bg-emerald-50 text-emerald-500 rounded-lg focus:ring-2 p-1.5 hover:bg-emerald-100 inline-flex items-center justify-center h-8 w-8">
                            &times;
                        </button>
                    </div>
                @endif

                @if (session('warning'))
                    <div id="toast-warning" class="mb-6 flex items-center p-4 text-amber-800 border-l-4 border-amber-500 bg-amber-50 rounded-xl shadow-xs" role="alert">
                        <svg class="flex-shrink-0 w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 1 1 2 0v5Z"/>
                        </svg>
                        <div class="text-sm font-medium">{{ session('warning') }}</div>
                        <button type="button" onclick="document.getElementById('toast-warning').remove()" class="ml-auto -mx-1.5 -my-1.5 bg-amber-50 text-amber-500 rounded-lg focus:ring-2 p-1.5 hover:bg-amber-100 inline-flex items-center justify-center h-8 w-8">
                            &times;
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div id="toast-error" class="mb-6 flex flex-col p-4 text-rose-800 border-l-4 border-rose-500 bg-rose-50 rounded-xl shadow-xs" role="alert">
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
                            </svg>
                            <span class="text-sm font-bold">Please correct the following:</span>
                            <button type="button" onclick="document.getElementById('toast-error').remove()" class="ml-auto -mx-1.5 -my-1.5 bg-rose-50 text-rose-500 rounded-lg focus:ring-2 p-1.5 hover:bg-rose-100 inline-flex items-center justify-center h-8 w-8">
                                &times;
                            </button>
                        </div>
                        <ul class="mt-2 ml-6 list-disc list-inside text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Copyright footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-[11px] text-gray-500 flex-shrink-0">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div>
                        <span class="text-hau-maroon font-bold">Holy Angel University</span> &copy; {{ date('Y') }}. All rights reserved.
                    </div>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-hau-maroon transition">Privacy Statement</a>
                        <span>&middot;</span>
                        <a href="https://paascu.org.ph" target="_blank" class="hover:text-hau-maroon transition">PAASCU Website</a>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <!-- Custom Confirm Modal -->
    <div id="custom-confirm-modal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs hidden opacity-0 transition-opacity duration-200">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-150 w-full max-w-md overflow-hidden transform scale-95 transition-all duration-200">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <!-- Warning Icon -->
                    <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center flex-shrink-0 text-rose-600 border border-rose-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <!-- Content -->
                    <div class="space-y-1.5 flex-1">
                        <h4 class="text-base font-bold text-gray-900 font-sans">Are you sure?</h4>
                        <p id="custom-confirm-message" class="text-xs text-gray-500 font-medium leading-relaxed"></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-150">
                <button id="custom-confirm-cancel" type="button" class="px-4 py-2 border border-gray-300 text-xs font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button id="custom-confirm-btn" type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Layout JS -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }

        function toggleNotifications() {
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }

        function markAsRead(event, id, btn) {
            event.stopPropagation();
            event.preventDefault();
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const item = btn.closest('.flex.items-start.p-4');
                    item.remove();
                    
                    const badge = document.querySelector('#notif-bell-btn span');
                    if (badge) {
                        let val = parseInt(badge.innerText) - 1;
                        if (val > 0) {
                            badge.innerText = val;
                        } else {
                            badge.remove();
                        }
                    }
                    
                    const centers = document.querySelectorAll('#notif-dropdown span.text-hau-maroon');
                    centers.forEach(c => {
                        let val = parseInt(c.innerText) - 1;
                        c.innerText = val + ' Alerts';
                    });
                }
            })
            .catch(err => console.error('Error marking notification as read:', err));
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('notif-dropdown');
            const btn = document.getElementById('notif-bell-btn');
            if (dropdown && btn && !btn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // ================= CUSTOM CONFIRM MODAL LOGIC =================
        let confirmCallback = null;

        function showCustomConfirmModal(message, callback) {
            document.getElementById('custom-confirm-message').innerText = message;
            confirmCallback = callback;
            
            const modal = document.getElementById('custom-confirm-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modal.firstElementChild.classList.remove('scale-95');
                modal.firstElementChild.classList.add('scale-100');
            }, 10);
        }

        function closeCustomConfirmModal() {
            const modal = document.getElementById('custom-confirm-modal');
            modal.firstElementChild.classList.remove('scale-100');
            modal.firstElementChild.classList.add('scale-95');
            modal.classList.remove('opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                confirmCallback = null;
            }, 200);
        }

        document.getElementById('custom-confirm-cancel').addEventListener('click', closeCustomConfirmModal);
        document.getElementById('custom-confirm-btn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeCustomConfirmModal();
        });

        document.getElementById('custom-confirm-modal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeCustomConfirmModal();
            }
        });

        // Hijack default confirm on form submits
        function hijackConfirmForms() {
            document.querySelectorAll('form').forEach(form => {
                // If it's already hijacked, ignore
                if (form.dataset.hijacked === 'true') {
                    return;
                }

                const onsubmitAttr = form.getAttribute('onsubmit');
                if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                    // Extract message
                    let message = "Are you sure you want to proceed?";
                    const match = onsubmitAttr.match(/confirm\(['"](.*)['"]\)/);
                    if (match && match[1]) {
                        message = match[1];
                    }

                    // Remove standard inline onsubmit
                    form.removeAttribute('onsubmit');
                    form.onsubmit = null;

                    // Mark as hijacked
                    form.dataset.hijacked = 'true';

                    // Attach custom submit listener
                    form.addEventListener('submit', function(event) {
                        if (form.dataset.customConfirmed === 'true') {
                            return;
                        }
                        event.preventDefault();
                        showCustomConfirmModal(message, function() {
                            form.dataset.customConfirmed = 'true';
                            form.submit();
                        });
                    });
                }
            });
        }

        // Run on load and dynamic updates
        document.addEventListener('DOMContentLoaded', hijackConfirmForms);
        setInterval(hijackConfirmForms, 1000);
    </script>
</body>
</html>
