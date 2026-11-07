@extends('layouts.app')

@section('title', 'Dashboard Administrator - 3in1')

@section('content')
<div class="space-y-8">

    <!-- Welcome Hero Section -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-blue-200 backdrop-blur-md mb-3 border border-white/10">
                <i class="fa-solid fa-shield-halved mr-1.5 text-blue-300"></i> Hak Akses: Administrator Utama
            </span>
            <h1 class="text-3xl font-extrabold tracking-tight">Panel Kontrol Administrator</h1>
            <p class="text-blue-200 text-sm mt-2 leading-relaxed">
                Anda memiliki hak akses penuh untuk mengelola pengguna (RBAC), mengatur peran Dosen & Mahasiswa, serta mengakses dan menyinkronkan seluruh data API Kampus.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-gear text-9xl"></i>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengguna</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $counts['total'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3"><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Terdaftar di database lokal</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Dosen</span>
                    <h3 class="text-2xl font-black text-purple-700 mt-1">{{ $counts['dosen'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3">Peran: Host Ruang Kelas</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Mahasiswa</span>
                    <h3 class="text-2xl font-black text-emerald-700 mt-1">{{ $counts['mahasiswa'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3">Peran: Peserta Kelas</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status API Kampus</span>
                    <div class="mt-1">
                        @if($connectionTest['success'])
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Live Terhubung
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                Kredensial Sensor
                            </span>
                        @endif
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-700 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3">Portal Data UNUJA</p>
        </div>
    </div>

    <!-- Admin Master Actions Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">Akses Data API & Sinkronisasi Master</h3>
            <p class="text-xs text-slate-500">Khusus Administrator: Akses seluruh 7 data referensi kampus & sinkronisasi otomatis ke users.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.campus_data') }}"
                class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition flex items-center space-x-1.5">
                <i class="fa-solid fa-database"></i>
                <span>Kelola Data API Kampus</span>
            </a>
            <form action="{{ route('admin.campus_data.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Mulai sinkronisasi data dari API Kampus ke tabel users lokal?')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-900 text-white shadow-sm transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Tarik & Sinkronkan Data</span>
                </button>
            </form>
        </div>
    </div>

    <!-- User Management Table (Pengaturan Akses Pengguna) -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Pengaturan Akses Pengguna (RBAC)</h2>
                <p class="text-xs text-slate-500">Ubah peran Dosen, Mahasiswa, atau Administrator serta kelola status keaktifan akun.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                        <th class="py-3 px-4">Nama Pengguna</th>
                        <th class="py-3 px-4">Username (NIM / NIDN)</th>
                        <th class="py-3 px-4">Program Studi</th>
                        <th class="py-3 px-4">Peran Saat Ini</th>
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4 text-center">Aksi / Atur Hak Akses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $u->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $u->email ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ $u->username }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $u->prodi ?? 'Teknik Informatika' }}</td>
                            <td class="py-3 px-4">
                                @if($u->isAdmin())
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Admin
                                    </span>
                                @elseif($u->isDosen())
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        Dosen
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Mahasiswa
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($u->status_aktif)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form action="{{ route('admin.users.update_role', $u->id) }}" method="POST" class="inline-flex items-center space-x-1.5">
                                    @csrf
                                    <select name="role" class="px-2 py-1 rounded-lg border border-slate-200 text-xs font-semibold bg-white">
                                        <option value="dosen" {{ $u->role === 'dosen' ? 'selected' : '' }}>Dosen</option>
                                        <option value="mahasiswa" {{ $u->role === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <select name="status_aktif" class="px-2 py-1 rounded-lg border border-slate-200 text-xs font-semibold bg-white">
                                        <option value="1" {{ $u->status_aktif ? 'selected' : '' }}>Aktif</option>
                                        <option value="0" {{ ! $u->status_aktif ? 'selected' : '' }}>Nonaktif</option>
                                    </select>
                                    <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition" title="Simpan Perubahan Role">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8 text-slate-400">Tidak ada pengguna ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

