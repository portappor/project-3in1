<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - 3in1 Ruang Kelas Virtual | Fakultas Teknik Universitas Nurul Jadid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <div class="min-h-screen flex flex-col lg:flex-row w-full">
        <!-- ============================================== -->
        <!-- PANEL KIRI: HERO FOTO KAMPUS & IDENTITAS FT   -->
        <!-- ============================================== -->
        <div class="relative hidden lg:flex lg:w-7/12 xl:w-2/3 flex-col justify-between p-12 lg:p-16 overflow-hidden bg-slate-950">
            <!-- Background Image with Zoom Effect -->
            <img src="{{ asset('images/fakultas-teknik-unuja.jpg') }}" 
                 alt="Gedung Fakultas Teknik Universitas Nurul Jadid" 
                 class="absolute inset-0 w-full h-full object-cover object-center transform scale-100 hover:scale-105 transition-transform duration-1000 ease-out">
            
            <!-- Dark Gradient Overlay for High Contrast -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-blue-950/60"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/40 to-transparent"></div>

            <!-- Content over Image -->
            <div class="relative z-10 flex flex-col h-full justify-between">
                
                <!-- Top Brand Header -->
                <div class="flex items-center justify-between">
                    <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-xl shadow-black/20">
                        <div class="w-8 h-8 rounded-xl bg-blue-600/80 flex items-center justify-center text-amber-300 font-bold">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold tracking-wider uppercase text-blue-200">Universitas Nurul Jadid</p>
                            <p class="text-xs font-extrabold text-white tracking-wide">FAKULTAS TEKNIK</p>
                        </div>
                    </div>

                    <div class="hidden xl:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/20 backdrop-blur-md border border-emerald-400/30 text-emerald-300 text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Sistem Aktif &bull; Semester Berjalan</span>
                    </div>
                </div>

                <!-- Middle Headline & Feature Cards -->
                <div class="my-auto py-12 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-widest mb-4">
                        <i class="fa-solid fa-atom text-blue-400"></i>
                        <span>3in1 Virtual Class &bull; Single-Window</span>
                    </div>
                    
                    <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight tracking-tight">
                        Transformasi Pembelajaran Daring <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-blue-300 to-indigo-300">Single-Window</span>
                    </h1>
                    
                    <p class="text-slate-300 text-sm xl:text-base mt-4 leading-relaxed font-normal">
                        Menyatukan layanan tatap muka video conference, penampil dokumen interaktif real-time, dan papan diskusi akademik dalam satu antarmuka terpadu guna mengurangi beban teknis operasional.
                    </p>

                    <!-- 3-in-1 Feature Glass Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-8">
                        <!-- Card 1 -->
                        <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-white shadow-lg hover:bg-white/15 transition">
                            <div class="w-9 h-9 rounded-xl bg-blue-500/30 text-blue-300 flex items-center justify-center text-base mb-2.5">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <h2 class="text-xs font-bold text-white">Video Conference API</h2>
                            <p class="text-[11px] text-slate-300 mt-1 leading-snug">Jitsi Meet External API tertanam langsung.</p>
                        </div>

                        <!-- Card 2 -->
                        <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-white shadow-lg hover:bg-white/15 transition">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500/30 text-indigo-300 flex items-center justify-center text-base mb-2.5">
                                <i class="fa-solid fa-file-powerpoint"></i>
                            </div>
                            <h2 class="text-xs font-bold text-white">Slide Sync Real-Time</h2>
                            <p class="text-[11px] text-slate-300 mt-1 leading-snug">PDF.js viewer dengan tombol kontrol Sync-Lock.</p>
                        </div>

                        <!-- Card 3 -->
                        <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-white shadow-lg hover:bg-white/15 transition">
                            <div class="w-9 h-9 rounded-xl bg-sky-500/30 text-sky-300 flex items-center justify-center text-base mb-2.5">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <h2 class="text-xs font-bold text-white">Papan Diskusi &amp; Presensi</h2>
                            <p class="text-[11px] text-slate-300 mt-1 leading-snug">Riwayat keaktifan &amp; ekspor CSV terformat.</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Footer Info -->
                <div class="pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-300">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-rose-400"></i>
                        <span>Paiton, Probolinggo &bull; Jawa Timur</span>
                    </div>
                    <div class="text-slate-400 font-mono text-[11px]">
                        Fakultas Teknik UNUJA &copy; {{ date('Y') }}
                    </div>
                </div>

            </div>
        </div>

        <!-- ============================================== -->
        <!-- PANEL KANAN: FORM LOGIN & DEMO TESTING         -->
        <!-- ============================================== -->
        <div class="w-full lg:w-5/12 xl:w-1/3 flex items-center justify-center p-6 sm:p-10 lg:p-12 bg-slate-900 relative">
            
            <!-- Mobile Header Image Banner (Visible only on small screens) -->
            <div class="w-full max-w-md mx-auto space-y-6">
                
                <div class="lg:hidden rounded-2xl overflow-hidden relative shadow-xl border border-white/10 mb-6">
                    <img src="{{ asset('images/fakultas-teknik-unuja.jpg') }}" alt="Fakultas Teknik UNUJA" class="w-full h-36 object-cover object-center">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent flex items-end p-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-400/30">Fakultas Teknik</span>
                            <h2 class="text-sm font-extrabold text-white mt-1">Universitas Nurul Jadid</h2>
                        </div>
                    </div>
                </div>

                <!-- Header Welcome Box -->
                <div>
                    <div class="inline-flex w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 items-center justify-center text-white shadow-lg shadow-blue-500/30 ring-4 ring-blue-500/20 mb-4">
                        <i class="fa-solid fa-chalkboard-user text-xl"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">3in1 Virtual Class</h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Portal Masuk Fakultas Teknik &bull; Pembelajaran Terpadu
                    </p>
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-base shrink-0"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs shadow-sm">
                        <div class="flex items-center space-x-2 font-bold mb-1">
                            <i class="fa-solid fa-circle-exclamation text-rose-400 shrink-0"></i>
                            <span>Autentikasi Gagal</span>
                        </div>
                        <ul class="list-disc pl-5 space-y-0.5 text-[11px] text-rose-200">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl p-6 sm:p-7 border border-slate-700/60 shadow-2xl">
                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Username / NIM / NIDN -->
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                                NIM / NIDN (Username)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-id-badge text-sm"></i>
                                </div>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                                    placeholder="NIDN Dosen atau NIM Mahasiswa..."
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-600 bg-slate-900/80 text-white placeholder-slate-500 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-inner">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5 flex items-start gap-1">
                                <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 shrink-0"></i>
                                <span>Gunakan <strong>NIDN</strong> untuk Dosen, <strong>NIM</strong> untuk Mahasiswa, atau <strong>admin</strong>.</span>
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                    Kata Sandi
                                </label>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-lock text-sm"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    placeholder="Masukkan kata sandi..."
                                    class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-600 bg-slate-900/80 text-white placeholder-slate-500 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-inner">
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition">
                                    <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between text-xs text-slate-300 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-600 bg-slate-900 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                                <span>Ingat Saya di Perangkat Ini</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-500 hover:via-indigo-500 hover:to-blue-600 active:scale-[0.98] text-white font-bold text-sm shadow-xl shadow-blue-600/30 transition duration-150 flex items-center justify-center gap-2 group mt-2">
                            <span>Masuk ke Kelas Virtual</span>
                            <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                        </button>
                    </form>

                    <!-- Quick Demo Testing Buttons -->
                    <div class="mt-6 pt-5 border-t border-slate-700/60">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">⚡ Akun Uji Coba Cepat:</span>
                            <span class="text-[10px] text-slate-500">Klik untuk mengisi</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Button Admin -->
                            <button type="button" onclick="fillDemo('admin', 'password', 'Admin')"
                                class="py-2 px-2 rounded-xl border border-blue-500/30 bg-blue-500/10 hover:bg-blue-500/20 text-blue-300 text-xs font-semibold text-center transition flex flex-col items-center justify-center gap-1 group">
                                <i class="fa-solid fa-shield-halved text-blue-400 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[11px]">Admin</span>
                            </button>

                            <!-- Button Dosen -->
                            <button type="button" onclick="fillDemo('198001012010121001', 'password', 'Dosen')"
                                class="py-2 px-2 rounded-xl border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 text-xs font-semibold text-center transition flex flex-col items-center justify-center gap-1 group">
                                <i class="fa-solid fa-chalkboard-user text-indigo-400 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[11px]">Dosen</span>
                            </button>

                            <!-- Button Mahasiswa -->
                            <button type="button" onclick="fillDemo('2023001', 'password', 'Mahasiswa')"
                                class="py-2 px-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 text-xs font-semibold text-center transition flex flex-col items-center justify-center gap-1 group">
                                <i class="fa-solid fa-user-graduate text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[11px]">Mahasiswa</span>
                            </button>
                        </div>

                        <!-- Feedback Toast Mini -->
                        <div id="demo-badge" class="hidden mt-2 py-1 px-2.5 rounded-lg bg-blue-500/20 border border-blue-400/30 text-blue-200 text-[11px] text-center transition">
                            <i class="fa-solid fa-check-circle mr-1 text-blue-400"></i>
                            <span id="demo-badge-text"></span>
                        </div>
                    </div>

                </div>

                <!-- Bottom Footer Note -->
                <p class="text-center text-[11px] text-slate-500">
                    Sistem Pembelajaran Terintegrasi &bull; Single-Window Interface
                </p>

            </div>

        </div>
    </div>

    <!-- JavaScript Interactivity -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function fillDemo(username, password, roleName) {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const demoBadge = document.getElementById('demo-badge');
            const demoBadgeText = document.getElementById('demo-badge-text');

            usernameInput.value = username;
            passwordInput.value = password;

            // Highlight animation effect
            usernameInput.classList.add('ring-2', 'ring-blue-400');
            passwordInput.classList.add('ring-2', 'ring-blue-400');
            setTimeout(() => {
                usernameInput.classList.remove('ring-2', 'ring-blue-400');
                passwordInput.classList.remove('ring-2', 'ring-blue-400');
            }, 600);

            if (demoBadge && demoBadgeText) {
                demoBadgeText.textContent = `Akun ${roleName} dipilih (${username})`;
                demoBadge.classList.remove('hidden');
                setTimeout(() => {
                    demoBadge.classList.add('hidden');
                }, 3000);
            }
        }
    </script>
</body>
</html>
