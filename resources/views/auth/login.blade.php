<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - HAU QA Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900 flex items-center justify-center p-4 bg-gradient-to-br from-hau-maroon via-hau-maroon-dark to-gray-900">
    <div class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6">
        <!-- Logo Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-16 h-16 rounded-full bg-white flex items-center justify-center border-4 border-hau-gold shadow-md overflow-hidden mx-auto">
                <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" class="w-full h-full object-contain">
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">QA Portal Access</h2>
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Holy Angel University</p>
        </div>

        <!-- Session Message / Success Alert -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3.5 text-xs font-semibold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Alerts -->
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-3.5 text-xs font-semibold flex items-start gap-2">
                <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="space-y-0.5">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" id="username" required value="{{ old('username') }}" autocomplete="username" autofocus
                    class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                    placeholder="Enter your username">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer font-semibold text-gray-600">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded text-hau-maroon focus:ring-hau-maroon border-gray-300 cursor-pointer">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" class="text-hau-maroon font-bold hover:text-hau-maroon-dark hover:underline">
                    Forgot password?
                </a>
            </div>

            <button type="submit" 
                class="w-full py-3 px-4 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold text-sm rounded-xl transition duration-150 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-hau-maroon/50 focus:ring-offset-2">
                Sign In
            </button>
        </form>

        <div class="border-t border-gray-200/60 pt-4 text-center">
            <p class="text-[10px] text-gray-400 font-semibold tracking-wider uppercase">Office of Academic Quality &bull; Holy Angel University</p>
        </div>
    </div>
</body>
</html>
