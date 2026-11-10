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
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-rendering: optimizeLegibility;
        }

        /* Smooth Cinematic Ken-Burns Effect on Hero Image */
        @keyframes smoothKenBurns {
            0% { transform: scale(1.02) translate(0%, 0%); }
            50% { transform: scale(1.06) translate(-0.8%, -0.6%); }
            100% { transform: scale(1.02) translate(0%, 0%); }
        }
        .animate-ken-burns {
            animation: smoothKenBurns 28s cubic-bezier(0.4, 0, 0.2, 1) infinite alternate;
        }

        /* Smooth Ambient Floating Orbs */
        @keyframes floatAmbient1 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(20px, -15px) scale(1.06); }
        }
        @keyframes floatAmbient2 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(-15px, 20px) scale(1.05); }
        }
        .animate-float-1 {
            animation: floatAmbient1 16s ease-in-out infinite;
        }
        .animate-float-2 {
            animation: floatAmbient2 20s ease-in-out infinite;
        }

        /* Smooth Fade In Entrance */
        @keyframes smoothFadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: smoothFadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shimmer Fluid Button */
        .btn-gradient-fluid {
            background-size: 200% auto;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-gradient-fluid:hover {
            background-position: right center;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen lg:h-screen lg:max-h-screen lg:overflow-hidden selection:bg-blue-600 selection:text-white flex flex-col justify-between">

    <div class="h-full min-h-screen lg:min-h-0 lg:h-screen flex flex-col lg:flex-row w-full relative overflow-hidden">

        <!-- ============================================== -->
        <!-- PANEL KIRI: HERO FOTO KAMPUS & IDENTITAS FT   -->
        <!-- ============================================== -->
        <div class="relative hidden lg:flex lg:w-7/12 xl:w-2/3 h-full flex-col justify-between p-8 xl:p-10 overflow-hidden bg-slate-950 border-r border-slate-800/60">

            <!-- Background Image with Ultra-Smooth Ken-Burns Animation -->
            <div class="absolute inset-0 overflow-hidden">
                <img src="{{ asset('images/fakultas-teknik-unuja.jpg') }}"
                     alt="Gedung Fakultas Teknik Universitas Nurul Jadid"
                     class="w-full h-full object-cover object-center animate-ken-burns will-change-transform opacity-95">
            </div>

            <!-- Layered Soft Gradient Overlays (Preserves photo richness while keeping text readable) -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-blue-950/35"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent"></div>

            <!-- Decorative Ambient Glow in Hero -->
            <div class="absolute top-1/4 left-1/4 w-80 h-80 rounded-full bg-blue-600/15 blur-[120px] pointer-events-none animate-float-1"></div>

            <!-- Content Container -->
            <div class="relative z-10 flex flex-col h-full justify-between animate-fade-in">

                <!-- Top Header: Institutional Badge -->
                <div class="flex items-center justify-between">
                    <div class="inline-flex items-center gap-3 px-3.5 py-2 rounded-xl bg-slate-900/65 backdrop-blur-xl border border-white/15 text-white shadow-xl shadow-black/30 hover:border-white/30 transition-all duration-300">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-amber-300 font-bold shadow-sm">
                            <i class="fa-solid fa-graduation-cap text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold tracking-widest uppercase text-blue-300 leading-none">Universitas Nurul Jadid</p>
                            <p class="text-xs font-extrabold text-white tracking-wide mt-0.5 leading-none">FAKULTAS TEKNIK</p>
                        </div>
                    </div>

                    <div class="hidden xl:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 backdrop-blur-xl border border-emerald-400/20 text-emerald-300 text-[11px] font-medium shadow-inner">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shadow-sm shadow-emerald-400"></span>
                        <span>Sistem Aktif &bull; Semester Berjalan</span>
                    </div>
                </div>

                <!-- Middle Headline & 3-in-1 Showcase -->
                <div class="my-auto py-6 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/15 border border-blue-400/30 text-blue-300 text-[11px] font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                        <i class="fa-solid fa-atom text-blue-400 animate-spin" style="animation-duration: 12s;"></i>
                        <span>3in1 Virtual Class &bull; Single-Window</span>
                    </div>

                    <h1 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight tracking-tight drop-shadow-sm">
                        Platform Pembelajaran Daring <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-300 via-blue-200 to-indigo-200">Terpadu Satu Jendela</span>
                    </h1>

                    <p class="text-slate-300 text-xs xl:text-sm mt-3 leading-relaxed font-light drop-shadow-sm max-w-xl">
                        Mengintegrasikan ruang tatap muka video conference, penampil dokumen slide interaktif tersinkronisasi, dan papan diskusi permanen dalam satu antarmuka terpadu.
                    </p>

                    <!-- 3-in-1 Feature Compact Cards -->
                    <div class="grid grid-cols-3 gap-3 mt-6">
                        <!-- Card 1 -->
                        <div class="group p-3 rounded-xl bg-slate-900/50 backdrop-blur-xl border border-white/10 hover:border-blue-400/30 hover:bg-slate-900/70 transition-all duration-300 shadow-lg shadow-black/20">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-300 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition-transform duration-300">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <h2 class="text-[11px] font-bold text-white group-hover:text-blue-200 transition-colors">Video Conference API</h2>
                            <p class="text-[10px] text-slate-300 mt-0.5 leading-snug">Jitsi Meet SDK tertanam langsung tanpa aplikasi pihak ketiga.</p>
                        </div>

                        <!-- Card 2 -->
                        <div class="group p-3 rounded-xl bg-slate-900/50 backdrop-blur-xl border border-white/10 hover:border-indigo-400/30 hover:bg-slate-900/70 transition-all duration-300 shadow-lg shadow-black/20">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition-transform duration-300">
                                <i class="fa-solid fa-file-powerpoint"></i>
                            </div>
                            <h2 class="text-[11px] font-bold text-white group-hover:text-indigo-200 transition-colors">Slide Sync Real-Time</h2>
                            <p class="text-[10px] text-slate-300 mt-0.5 leading-snug">PDF.js viewer terintegrasi dengan tombol kendali Sync-Lock.</p>
                        </div>

                        <!-- Card 3 -->
                        <div class="group p-3 rounded-xl bg-slate-900/50 backdrop-blur-xl border border-white/10 hover:border-sky-400/30 hover:bg-slate-900/70 transition-all duration-300 shadow-lg shadow-black/20">
                            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition-transform duration-300">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <h2 class="text-[11px] font-bold text-white group-hover:text-sky-200 transition-colors">Diskusi &amp; Presensi</h2>
                            <p class="text-[10px] text-slate-300 mt-0.5 leading-snug">Rekam jejak keaktifan akademik dan ekspor rekapitulasi CSV.</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Footer Info -->
                <div class="pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300">
                    <div class="flex items-center gap-1.5 text-slate-300">
                        <i class="fa-solid fa-location-dot text-rose-400 text-xs"></i>
                        <span>Paiton, Probolinggo &bull; Jawa Timur</span>
                    </div>
                    <div class="text-slate-400 font-mono text-[10px]">
                        Fakultas Teknik UNUJA &copy; {{ date('Y') }}
                    </div>
                </div>

            </div>
        </div>

        <!-- ============================================== -->
        <!-- PANEL KANAN: FORM LOGIN & DEMO TESTING         -->
        <!-- ============================================== -->
        <div class="w-full lg:w-5/12 xl:w-1/3 h-full flex flex-col justify-center items-center p-5 sm:p-7 lg:p-8 bg-slate-950 relative overflow-hidden">

            <!-- Ambient Glowing Spheres Behind Form (Apple/Stripe Style Depth) -->
            <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-blue-600/15 blur-[90px] pointer-events-none animate-float-1"></div>
            <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full bg-indigo-600/15 blur-[90px] pointer-events-none animate-float-2"></div>

            <!-- Subtle Grid Background -->
            <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:20px_20px] opacity-30 pointer-events-none"></div>

            <div class="w-full max-w-sm sm:max-w-md mx-auto my-auto relative z-10 animate-fade-in">

                <!-- Mobile Header Image Banner (Visible only on small screens) -->
                <div class="lg:hidden rounded-xl overflow-hidden relative shadow-xl border border-white/15 mb-4 group">
                    <img src="{{ asset('images/fakultas-teknik-unuja.jpg') }}" alt="Fakultas Teknik UNUJA" class="w-full h-28 object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent flex items-end p-3">
                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-400/30">Fakultas Teknik</span>
                            <h2 class="text-xs font-extrabold text-white mt-0.5">Universitas Nurul Jadid</h2>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="mb-3 p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center space-x-2 shadow-sm animate-fade-in">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-sm shrink-0"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-3 p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs shadow-sm animate-fade-in">
                        <div class="flex items-center space-x-1.5 font-bold mb-0.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-400 shrink-0"></i>
                            <span>Autentikasi Gagal</span>
                        </div>
                        <ul class="list-disc pl-4 space-y-0.5 text-[10px] text-rose-200">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Compact Glassmorphic Form Card -->
                <div class="bg-slate-900/70 backdrop-blur-2xl rounded-2xl p-5 sm:p-6 border border-white/10 shadow-[0_15px_40px_rgba(0,0,0,0.6)]">

                    <!-- Compact Header Brand Row -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 ring-2 ring-blue-500/20 shrink-0">
                            <i class="fa-solid fa-chalkboard-user text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-extrabold text-white tracking-tight leading-tight">3in1 Virtual Class</h2>
                            <p class="text-[11px] text-slate-400 leading-none mt-0.5">Portal Masuk Fakultas Teknik</p>
                        </div>
                    </div>

                    <form action="{{ route('login.post') }}" method="POST" class="space-y-3">
                        @csrf

                        <!-- Username / NIM / NIDN -->
                        <div>
                            <label for="username" class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                                NIM / NIDN (Username)
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-400 transition-colors duration-200">
                                    <i class="fa-solid fa-id-badge text-xs"></i>
                                </div>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                                    placeholder="NIDN Dosen atau NIM Mahasiswa..."
                                    class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-700/80 bg-slate-950/60 text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 focus:bg-slate-900/90 transition-all duration-200 outline-none shadow-inner">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1.5 leading-none">
                                <i class="fa-solid fa-circle-info text-blue-400 shrink-0 text-[10px]"></i>
                                <span>Gunakan <strong>NIDN</strong> (Dosen), <strong>NIM</strong> (Mahasiswa), atau <strong>admin</strong>.</span>
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label for="password" class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider">
                                    Kata Sandi
                                </label>
                            </div>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-400 transition-colors duration-200">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    placeholder="Masukkan kata sandi..."
                                    class="w-full pl-9 pr-9 py-2 rounded-xl border border-slate-700/80 bg-slate-950/60 text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 focus:bg-slate-900/90 transition-all duration-200 outline-none shadow-inner">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition-colors duration-200"
                                    title="Tampilkan / Sembunyikan Kata Sandi">
                                    <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between text-[11px] text-slate-300 pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none group">
                                <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-700 bg-slate-900 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900 transition-colors cursor-pointer">
                                <span class="group-hover:text-slate-200 transition-colors">Ingat Saya di Perangkat Ini</span>
                            </label>
                        </div>

                        <!-- Submit Button with Fluid Shimmer -->
                        <button type="submit"
                            class="btn-gradient-fluid w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 active:scale-[0.98] text-white font-bold text-xs sm:text-sm shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 flex items-center justify-center gap-2 group mt-1 cursor-pointer">
                            <span>Masuk</span>
                            <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
                        </button>
                    </form>

                    <!-- Quick Demo Testing Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-800/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                <i class="fa-solid fa-bolt text-amber-400"></i>
                                <span>Akun Uji Coba:</span>
                            </span>
                            <span class="text-[9px] text-slate-500">Klik 1-kali untuk mengisi</span>
                        </div>
                        <div class="flex justify-center">
                            <!-- Button Admin -->
                            <button type="button" onclick="fillDemo('admin', 'password', 'Admin')"
                                class="w-full max-w-[220px] py-2 px-4 rounded-xl border border-blue-500/30 bg-blue-500/15 hover:bg-blue-500/25 hover:border-blue-400/50 text-blue-300 text-xs font-semibold transition-all duration-200 hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                                <i class="fa-solid fa-shield-halved text-blue-400 text-sm"></i>
                                <span>Admin</span>
                            </button>
                        </div>

                        <!-- Feedback Toast Mini with Smooth Transition -->
                        <div id="demo-badge" class="hidden mt-2 py-1 px-2.5 rounded-lg bg-blue-500/15 border border-blue-400/30 text-blue-200 text-[10px] text-center transition-all duration-200">
                            <i class="fa-solid fa-circle-check mr-1 text-blue-400"></i>
                            <span id="demo-badge-text" class="font-medium"></span>
                        </div>
                    </div>

                </div>

                <!-- Bottom Footer Note -->
                <p class="text-center text-[10px] text-slate-500 tracking-wide mt-2.5">
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

            // Smooth highlight pulse animation
            usernameInput.classList.add('ring-2', 'ring-blue-500/40', 'border-blue-400');
            passwordInput.classList.add('ring-2', 'ring-blue-500/40', 'border-blue-400');
            setTimeout(() => {
                usernameInput.classList.remove('ring-2', 'ring-blue-500/40', 'border-blue-400');
                passwordInput.classList.remove('ring-2', 'ring-blue-500/40', 'border-blue-400');
            }, 600);

            if (demoBadge && demoBadgeText) {
                demoBadgeText.textContent = `Akun ${roleName} dipilih (${username})`;
                demoBadge.classList.remove('hidden');
                demoBadge.style.opacity = '0';
                demoBadge.style.transform = 'translateY(-4px)';

                requestAnimationFrame(() => {
                    demoBadge.style.transition = 'all 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
                    demoBadge.style.opacity = '1';
                    demoBadge.style.transform = 'translateY(0)';
                });

                setTimeout(() => {
                    demoBadge.style.opacity = '0';
                    demoBadge.style.transform = 'translateY(-4px)';
                    setTimeout(() => {
                        demoBadge.classList.add('hidden');
                    }, 250);
                }, 2500);
            }
        }
    </script>
</body>
</html>
