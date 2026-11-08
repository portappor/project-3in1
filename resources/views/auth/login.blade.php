<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - 3in1 Ruang Kelas Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo & Title Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 items-center justify-center text-white shadow-xl shadow-blue-500/20 mb-4 ring-4 ring-indigo-500/20">
                <i class="fa-solid fa-chalkboard-user text-3xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">3in1 Virtual Class</h1>
            <p class="text-sm text-slate-400 mt-2">Platform Ruang Kelas Virtual & Interaksi Pembelajaran</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 p-8">

            <!-- Alert Session / Error -->
            @if(session('success'))
                <div class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <div class="flex items-center space-x-2 font-semibold mb-1">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span>Autentikasi Gagal:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Username / NIM / NIDN -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        NIM / NIDN (Username)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                            placeholder="Ketik NIDN Dosen atau NIM Mahasiswa..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm text-slate-900 placeholder-slate-400 transition">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                        Dosen &amp; Mahasiswa dapat login langsung menggunakan <strong>NIDN / NIM</strong> masing-masing (NIDN/NIM juga berfungsi sebagai kata sandi default).
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            placeholder="Masukkan kata sandi Anda"
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm text-slate-900 placeholder-slate-400 transition">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Ingat Saya di Perangkat Ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm shadow-lg shadow-blue-500/25 transition duration-150 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Masuk ke Kelas Virtual</span>
                </button>
            </form>

            <!-- Quick Demo Testing Buttons -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs text-center font-medium text-slate-500 mb-3">Akun Uji Coba Cepat (Klik untuk Mengisi):</p>
                <div class="flex justify-center">
                    <button type="button" onclick="fillDemo('admin', 'password')"
                        class="w-full max-w-[220px] py-2 px-4 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-800 text-xs font-semibold text-center transition flex items-center justify-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-shield-halved text-blue-600"></i>
                        <span>Admin</span>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            Proyek 3in1 &bull; Ruang Kelas Virtual Interaktif
        </p>
    </div>

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

        function fillDemo(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>

