<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password - OIE - QA Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/hau_logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/hau_logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900 flex items-center justify-center p-4 bg-gradient-to-br from-hau-maroon via-hau-maroon-dark to-gray-900">
    <div class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6">

        {{-- Logo Header --}}
        <div class="text-center space-y-2">
            <div class="inline-flex w-16 h-16 rounded-full bg-white items-center justify-center border-4 border-hau-gold shadow-md overflow-hidden mx-auto">
                <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Set New Password</h1>
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Quality Assurance Portal</p>
        </div>

        {{-- Success Badge: identity verified --}}
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Identity verified — choose your new password below.</span>
        </div>

        {{-- Error Alerts --}}
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

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4" data-no-draft="true">
            @csrf

            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required autofocus
                        class="block w-full px-4 pr-11 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePasswordVisibility('password', 'eye-icon-1')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-hau-maroon transition cursor-pointer focus:outline-none" title="Show / Hide Password">
                        <svg id="eye-icon-1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 font-medium leading-relaxed">
                    At least 8 characters with uppercase, lowercase, number, and symbol.
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="block w-full px-4 pr-11 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'eye-icon-2')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-hau-maroon transition cursor-pointer focus:outline-none" title="Show / Hide Password">
                        <svg id="eye-icon-2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold text-sm rounded-xl transition duration-150 shadow-md hover:shadow-lg focus:outline-none cursor-pointer">
                Update Password
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-bold text-hau-maroon hover:text-hau-maroon-dark hover:underline">
                Cancel &amp; Return to Sign In
            </a>
        </div>
    </div>

    <script>
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
</body>
</html>
