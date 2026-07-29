<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - HAU QA Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900 bg-gray-100 flex items-center justify-center min-h-screen p-0 sm:p-6 lg:p-10">

    <!-- Split Screen Login Container -->
    <div class="w-full max-w-5xl bg-white sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[580px] border border-gray-200/80">

        <!-- ================= LEFT BRANDED BANNER ================= -->
        <div class="md:w-1/2 bg-gradient-to-br from-hau-maroon-dark via-hau-maroon to-[#600000] text-white p-8 lg:p-12 flex flex-col justify-between relative overflow-hidden">
            
            <!-- Subtle Decorative Geometric Curves Overlay -->
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <svg class="w-full h-full" viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M-100 100 C 150 200, 300 50, 600 300" stroke="#D4AF37" stroke-width="3" fill="none"/>
                    <path d="M-100 200 C 200 350, 350 150, 600 450" stroke="#FFFFFF" stroke-width="2" fill="none"/>
                    <path d="M-100 300 C 250 450, 400 250, 600 550" stroke="#D4AF37" stroke-width="2.5" fill="none"/>
                    <path d="M-100 400 C 300 550, 450 350, 600 650" stroke="#FFFFFF" stroke-width="1.5" fill="none"/>
                </svg>
            </div>

            <!-- Large Background Watermark HAU Emblem -->
            <div class="absolute -right-16 -bottom-16 w-96 h-96 opacity-15 pointer-events-none transform rotate-12">
                <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Watermark" class="w-full h-full object-contain filter drop-shadow-2xl">
            </div>

            <!-- Prominent HAU Brand Badge -->
            <div class="relative z-10 space-y-3">
                <div class="inline-flex items-center gap-3.5 bg-white/12 backdrop-blur-md px-5 py-2.5 rounded-2xl border border-white/20 shadow-lg">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center border-2 border-hau-gold overflow-hidden flex-shrink-0 shadow-sm">
                        <img src="{{ asset('images/hau_logo.png') }}" alt="HAU Logo" class="w-full h-full object-contain transform scale-110">
                    </div>
                    <span class="text-sm font-extrabold tracking-wider text-hau-gold uppercase">Holy Angel University</span>
                </div>
            </div>

            <!-- Middle Title Heading -->
            <div class="relative z-10 my-auto py-12 space-y-4">
                <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-tight">
                    Welcome to <br/>
                    <span class="text-hau-gold text-4xl lg:text-5xl">HAU QA Portal!</span>
                </h1>
            </div>

            <!-- Bottom Copyright Footer -->
            <div class="relative z-10 pt-6 border-t border-white/15">
                <p class="text-[11px] text-hau-gold-light/80 font-medium tracking-wide">
                    &copy; {{ date('Y') }} Holy Angel University. All rights reserved.
                </p>
            </div>
        </div>

        <!-- ================= RIGHT FORM AREA ================= -->
        <div class="md:w-1/2 bg-white p-8 lg:p-12 flex flex-col justify-between">
            
            <div class="space-y-6 my-auto">
                <!-- Top Brand Heading -->
                <div class="space-y-2">
                    <div>
                        <span class="text-xs font-black tracking-widest text-hau-maroon uppercase bg-hau-maroon/10 px-3 py-1 rounded-lg">QA Portal</span>
                    </div>
                    <h2 class="text-2xl lg:text-3xl font-extrabold text-gray-900 tracking-tight">Welcome Back!</h2>
                </div>

                <!-- Session Messages & Errors -->
                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3.5 text-xs font-semibold flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-3.5 text-xs font-semibold flex items-start gap-2.5">
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

                <!-- Login Form -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                    @csrf
                    
                    <!-- Username Field -->
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input type="text" name="username" id="username" required value="{{ old('username') }}" autocomplete="username" autofocus
                                class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400 bg-gray-50/50 focus:bg-white"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center">
                            <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Password</label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required autocomplete="current-password"
                                class="block w-full pl-10 pr-11 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-hau-maroon/20 focus:border-hau-maroon transition placeholder-gray-400 bg-gray-50/50 focus:bg-white"
                                placeholder="••••••••">
                            
                            <!-- Toggle Password Eye Button -->
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Forgot Password Links -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-gray-600 hover:text-gray-900 transition">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-hau-maroon focus:ring-hau-maroon border-gray-300 cursor-pointer">
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="text-hau-maroon font-bold hover:text-hau-maroon-dark hover:underline transition">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-hau-maroon to-hau-maroon-dark hover:from-hau-maroon-dark hover:to-[#4a0000] text-white font-bold text-sm rounded-xl transition duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-hau-maroon/50 focus:ring-offset-2 flex items-center justify-center gap-2 group cursor-pointer">
                        <span>Sign In to Portal</span>
                        <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Footer Badge -->
            <div class="pt-6 border-t border-gray-100 text-center">
                <p class="text-[10px] text-gray-400 font-bold tracking-widest uppercase">Quality Assurance Office &bull; Holy Angel University</p>
            </div>
        </div>

    </div>

    <!-- Toggle Password Visibility JS -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.959 8.959 0 013.682-.793c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-2.12 2.122L3 3l18 18"></path>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
    </script>

</body>
</html>
