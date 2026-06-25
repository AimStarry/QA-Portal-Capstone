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
        $role = session('active_role', 'QA Admin');
    @endphp

    <div class="h-full flex overflow-hidden bg-gray-100">
        
        <!-- Sidebar Navigation for Large Screens -->
        <aside class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64 border-r border-hau-gold/30 bg-hau-maroon text-white">
                
                <!-- HAU Header Banner -->
                <div class="flex items-center h-20 px-6 bg-hau-maroon-dark border-b border-hau-gold/20 gap-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center border-2 border-hau-gold overflow-hidden flex-shrink-0">
                        <span class="text-hau-maroon font-bold text-base">HAU</span>
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

                        @if($role === 'QA Admin')
                            <!-- 2. Programs (Admin Only) -->
                            <a href="{{ route('programs.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('programs.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('programs.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                Academic Programs
                            </a>

                            <!-- 3. Accreditations (Admin Only) -->
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

                        <!-- 5. Graduates (Shared) -->
                        <a href="{{ route('graduates.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('graduates.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                            <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('graduates.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7"></path>
                            </svg>
                            Graduates Tracker
                        </a>

                        @if($role === 'QA Admin')
                            <!-- 6. Risk Monitor (Admin Only) -->
                            <a href="{{ route('risk.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition duration-150 group {{ request()->routeIs('risk.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10 hover:text-hau-gold' }}">
                                <svg class="mr-3 h-5 w-5 flex-shrink-0 {{ request()->routeIs('risk.*') ? 'text-hau-maroon' : 'text-hau-gold-light group-hover:text-hau-gold' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Risk Monitor
                            </a>
                        @endif
                    </nav>

                    <!-- Sidebar Footer Info -->
                    <div class="mt-auto border-t border-white/10 pt-4 text-center">
                        <span class="text-[10px] text-hau-gold-light font-medium tracking-wide">Office of Academic Quality</span>
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

                    <!-- Role Switcher Form -->
                    <div class="flex items-center gap-4">
                        <form action="{{ route('switch-role') }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            <label for="role-select" class="text-xs font-bold text-gray-500 uppercase tracking-wide">Active Role:</label>
                            <select name="role" id="role-select" onchange="this.form.submit()" class="text-xs font-bold text-hau-maroon bg-hau-gold/20 border border-hau-gold/40 rounded-lg px-2.5 py-1 focus:outline-none cursor-pointer hover:bg-hau-gold/30 transition">
                                <option value="QA Admin" {{ $role === 'QA Admin' ? 'selected' : '' }}>QA Admin</option>
                                <option value="Responsible Unit" {{ $role === 'Responsible Unit' ? 'selected' : '' }}>Responsible Unit</option>
                            </select>
                        </form>
                        
                        <div class="w-8 h-8 rounded-full bg-hau-maroon text-white flex items-center justify-center font-bold text-xs shadow-xs border border-hau-gold/30">
                            {{ $role === 'QA Admin' ? 'QA' : 'RU' }}
                        </div>
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
                            <span class="text-hau-maroon font-bold text-sm">HAU</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-xs text-white leading-tight">Holy Angel University</h2>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col overflow-y-auto px-4 py-4 space-y-2">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('dashboard') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Dashboard</a>
                        @if($role === 'QA Admin')
                            <a href="{{ route('programs.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('programs.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Academic Programs</a>
                            <a href="{{ route('accreditations.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('accreditations.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Accreditations</a>
                        @endif
                        <a href="{{ route('compliance.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('compliance.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Compliance Tracker</a>
                        <a href="{{ route('graduates.index') }}" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl {{ request()->routeIs('graduates.*') ? 'bg-hau-gold text-hau-maroon' : 'text-white hover:bg-white/10' }}">Graduates Tracker</a>
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
    </script>
</body>
</html>
