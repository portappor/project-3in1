<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '3in1 Ruang Kelas Virtual')</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#0f766e',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans antialiased">

    <!-- Top Navigation Bar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-6">
                    <a href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isDosen() ? route('dosen.dashboard') : route('mahasiswa.dashboard'))) : route('login') }}"
                        class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 flex items-center justify-center text-white shadow-md">
                            <i class="fa-solid fa-chalkboard-user text-lg"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold tracking-tight text-slate-900">3in1</span>
                            <span class="text-xs uppercase tracking-wider font-semibold text-blue-600 block -mt-1">Ruang Kelas Virtual</span>
                        </div>
                    </a>

                    @auth
                        <div class="hidden md:flex items-center space-x-2 pl-4 border-l border-slate-200 text-xs font-semibold">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                    class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-shield-halved mr-1.5 text-blue-600"></i> Panel Kontrol Admin
                                </a>
                                <a href="{{ route('admin.campus_data') }}"
                                    class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.campus_data') || request()->routeIs('campus.data') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-database mr-1.5 text-blue-600"></i> Data Kampus (API)
                                </a>
                            @else
                                <a href="{{ auth()->user()->isDosen() ? route('dosen.dashboard') : route('mahasiswa.dashboard') }}"
                                    class="px-3 py-1.5 rounded-lg {{ request()->routeIs('dosen.*') || request()->routeIs('mahasiswa.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-chalkboard mr-1.5"></i> Ruang Kelas Virtual
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>

                <div class="flex items-center space-x-4">
                    @auth
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                            <span class="text-xs text-slate-500">{{ auth()->user()->username }} &bull; {{ auth()->user()->prodi ?? 'Teknik' }}</span>
                        </div>

                        <!-- Role Badge -->
                        @if(auth()->user()->isAdmin())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                <i class="fa-solid fa-shield-halved mr-1.5 text-blue-600"></i> Administrator
                            </span>
                        @elseif(auth()->user()->isDosen())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                <i class="fa-solid fa-crown mr-1.5 text-purple-600"></i> Host (Dosen)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <i class="fa-solid fa-user-graduate mr-1.5 text-emerald-600"></i> Peserta (Mahasiswa)
                            </span>
                        @endif

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket mr-1.5"></i> Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                            Masuk
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Aplikasi 3in1 - Ruang Kelas Virtual Terpadu. All rights reserved.
    </footer>

    @stack('scripts')
</body>
</html>

