<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter Verification Code - HAU QA Portal</title>
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
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Enter Verification Code</h1>
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Quality Assurance Portal</p>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3.5 text-xs font-semibold flex items-start gap-2">
                <svg class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

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

        <p class="text-xs text-gray-500 text-center leading-relaxed font-medium">
            We've sent a <strong class="text-gray-700">6-digit verification code</strong> to your email address.
            Enter it below to continue. The code expires in <strong class="text-red-600">10 minutes</strong>.
        </p>

        <form action="{{ route('password.verify-otp.post') }}" method="POST" class="space-y-5" id="otp-form">
            @csrf

            {{-- 6-digit OTP input boxes --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3 text-center">Verification Code</label>
                <div class="flex justify-center gap-2" id="otp-boxes">
                    @for ($i = 0; $i < 6; $i++)
                        <input
                            type="text"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]"
                            class="otp-digit w-12 h-14 text-center text-2xl font-black border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-hau-maroon/30 focus:border-hau-maroon transition bg-white text-gray-900"
                            autocomplete="off"
                        >
                    @endfor
                </div>
                {{-- Hidden real input that gets concatenated value --}}
                <input type="hidden" name="otp" id="otp-value">
            </div>

            {{-- Countdown Timer --}}
            <div class="text-center">
                <p class="text-xs text-gray-500">Code expires in: <span id="countdown" class="font-bold text-hau-maroon">10:00</span></p>
            </div>

            <button type="submit" id="verify-btn"
                class="w-full py-3 px-4 bg-hau-maroon hover:bg-hau-maroon-dark text-white font-bold text-sm rounded-xl transition duration-150 shadow-md hover:shadow-lg focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                Verify Code
            </button>
        </form>

        <div class="text-center space-y-2 pt-2">
            <p class="text-xs text-gray-500">Didn't receive the code?
                <a href="{{ route('password.request') }}" class="font-bold text-hau-maroon hover:underline">Request a new one</a>
            </p>
            <a href="{{ route('login') }}" class="block text-xs font-bold text-gray-400 hover:text-gray-600 hover:underline">
                ← Back to Sign In
            </a>
        </div>
    </div>

    <script>
        // ── Auto-advance through the 6 digit boxes ──────────────────
        const digits   = document.querySelectorAll('.otp-digit');
        const hidden   = document.getElementById('otp-value');
        const form     = document.getElementById('otp-form');

        digits.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val;
                if (val && idx < digits.length - 1) {
                    digits[idx + 1].focus();
                }
                syncHidden();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    syncHidden();
                }
            });

            // Handle paste of full code
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((ch, i) => {
                    if (digits[i]) digits[i].value = ch;
                });
                if (digits[pasted.length - 1]) digits[pasted.length - 1].focus();
                syncHidden();
            });
        });

        function syncHidden() {
            hidden.value = [...digits].map(d => d.value).join('');
        }

        // Auto-submit when all 6 digits filled
        function checkAutoSubmit() {
            const code = [...digits].map(d => d.value).join('');
            if (code.length === 6 && /^\d{6}$/.test(code)) {
                hidden.value = code;
                setTimeout(() => form.submit(), 300);
            }
        }
        digits.forEach(d => d.addEventListener('input', checkAutoSubmit));

        // ── Countdown timer ──────────────────────────────────────────
        let seconds = 10 * 60;
        const countdownEl = document.getElementById('countdown');
        const verifyBtn   = document.getElementById('verify-btn');

        const timer = setInterval(() => {
            seconds--;
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            countdownEl.textContent = `${m}:${s}`;

            if (seconds <= 60) countdownEl.classList.add('text-red-600');

            if (seconds <= 0) {
                clearInterval(timer);
                countdownEl.textContent = 'Expired';
                verifyBtn.disabled = true;
                digits.forEach(d => { d.disabled = true; d.classList.add('bg-gray-100'); });
            }
        }, 1000);

        // Focus first box on load
        digits[0]?.focus();
    </script>
</body>
</html>
