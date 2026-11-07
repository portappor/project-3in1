@extends('layouts.app')

@section('title', 'Data Referensi Kampus - 3in1')

@section('content')
<div class="space-y-6">

    <!-- Header Section with API Connection Status -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 pb-6 border-b border-slate-100">
            <div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200 mb-2">
                    <i class="fa-solid fa-network-wired mr-1.5 text-blue-600"></i> Integrasi API Portal Data Kampus
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Data Referensi Akademik Terpadu</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Menghubungkan aplikasi 3in1 langsung ke server data kampus UNUJA: Mahasiswa, Dosen, Fakultas, Prodi, Matakuliah, Jadwal Kuliah, dan Semester.
                </p>
            </div>

            <!-- API Status Badge & Sync Button -->
            <div class="flex flex-wrap items-center gap-2">
                @if($connectionTest['success'])
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-2"></span>
                        API Terhubung (Live)
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200" title="{{ $connectionTest['message'] }}">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1.5"></i>
                        Status API: Kredensial Perlu Disesuaikan
                    </span>
                @endif

                <form action="{{ route('campus.sync') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Mulai sinkronisasi data dari API Kampus ke tabel users lokal?')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Sinkronkan ke Users Lokal</span>
                    </button>
                </form>
            </div>
        </div>

        @if(! $connectionTest['success'] && $errorMessage)
            <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-start space-x-2">
                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                <div>
                    <span class="font-semibold text-slate-800">Catatan Koneksi API Kampus:</span>
                    <p class="text-slate-500 mt-0.5">Respons server: <code class="bg-slate-200 px-1 py-0.5 rounded text-slate-700">{{ $errorMessage }}</code>.
                    Sistem saat ini menampilkan pratinjau data representatif agar fitur dapat terus ditinjau.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- 7 Tab Navigation Buttons -->
    <div class="flex overflow-x-auto pb-2 scrollbar-none gap-2">
        @php
            $tabs = [
                'dosen' => ['label' => 'Data Dosen', 'icon' => 'fa-chalkboard-user'],
                'mahasiswa' => ['label' => 'Data Mahasiswa', 'icon' => 'fa-user-graduate'],
                'prodi_fakultas' => ['label' => 'Fakultas & Prodi', 'icon' => 'fa-building-columns'],
                'matakuliah' => ['label' => 'Matakuliah', 'icon' => 'fa-book-open'],
                'jadwal' => ['label' => 'Jadwal Kuliah', 'icon' => 'fa-calendar-days'],
                'semester' => ['label' => 'Semester / Th. Ajaran', 'icon' => 'fa-clock-rotate-left'],
            ];
        @endphp

        @foreach($tabs as $key => $tab)
            <a href="{{ route('campus.data', ['tab' => $key]) }}"
                class="px-4 py-2.5 rounded-2xl text-xs font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $currentTab === $key ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                <i class="fa-solid {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Main Data Container -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">

        <!-- Search & Filter Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-base font-bold text-slate-900 capitalize flex items-center space-x-2">
                <span>{{ $tabs[$currentTab]['label'] ?? 'Data Referensi' }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-mono font-normal bg-slate-100 text-slate-600">
                    Filter API: {{ $currentTab === 'semester' ? 'tahun_ajaran' : $currentTab }}
                </span>
            </h2>

            <form action="{{ route('campus.data') }}" method="GET" class="w-full sm:w-auto flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="{{ $currentTab }}">

                <!-- Filter Program Studi untuk Tab Dosen, Mahasiswa, Matakuliah & Jadwal -->
                @if(in_array($currentTab, ['dosen', 'mahasiswa', 'matakuliah', 'jadwal']))
                    <div class="relative">
                        <select name="prodi" onchange="this.form.submit()"
                            class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white hover:border-purple-300 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition cursor-pointer shadow-2xs">
                            <option value="">-- Semua Program Studi --</option>
                            @foreach($prodiList ?? [] as $pr)
                                <option value="{{ $pr }}" {{ ($selectedProdi ?? '') === $pr ? 'selected' : '' }}>
                                    {{ $pr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="relative flex-1 sm:w-56">
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Cari nama atau kode..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-1 focus:ring-blue-600">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                </div>

                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition">
                    Cari
                </button>
            </form>
        </div>

        <!-- Filter Active Pill -->
        @if(!empty($selectedProdi) && in_array($currentTab, ['dosen', 'mahasiswa', 'matakuliah', 'jadwal']))
            <div class="mb-5 flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs">
                    <i class="fa-solid fa-filter mr-1.5 text-purple-600"></i>
                    Filter Prodi Aktif: <strong class="ml-1 text-purple-900">{{ $selectedProdi }}</strong>
                    <a href="{{ route('campus.data', ['tab' => $currentTab, 'search' => $search]) }}" class="ml-2.5 p-0.5 text-purple-500 hover:text-rose-600" title="Reset Filter Prodi">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </span>
            </div>
        @endif

        <!-- Tab 1: DOSEN -->
        @if($currentTab === 'dosen')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <th class="py-3 px-4">NIDN / NIP</th>
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4">Program Studi</th>
                            <th class="py-3 px-4">Fakultas</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['data']['data'] ?? $data['data'] ?? $data as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $row['nidn'] ?? $row['nip'] ?? $row['kode_dosen'] ?? '-' }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ (!empty($row['gelar_depan']) ? $row['gelar_depan'].' ' : '') . ($row['nama_penduduk'] ?? $row['nama'] ?? $row['nama_dosen'] ?? '-') . (!empty($row['gelar_belakang']) ? ', '.$row['gelar_belakang'] : '') }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">{{ $row['nama_prodi'] ?? $row['prodi'] ?? 'Teknik Informatika' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ $row['nama_fakultas'] ?? $row['fakultas'] ?? 'Fakultas Teknik' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ $row['email'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        {{ $row['status'] ?? 'Aktif' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-slate-400">Tidak ada data dosen ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- Tab 2: MAHASISWA -->
        @elseif($currentTab === 'mahasiswa')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Nama Mahasiswa</th>
                            <th class="py-3 px-4">Program Studi</th>
                            <th class="py-3 px-4">Fakultas</th>
                            <th class="py-3 px-4 text-center">Angkatan</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['data']['data'] ?? $data['data'] ?? $data as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $row['nim'] ?? $row['npm'] ?? '-' }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $row['nama'] ?? $row['nama_mahasiswa'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $row['nama_prodi'] ?? $row['prodi'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ $row['nama_fakultas'] ?? $row['fakultas'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-mono text-slate-600">{{ $row['angkatan'] ?? '2023' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        {{ $row['status'] ?? 'Aktif' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-slate-400">Tidak ada data mahasiswa ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- Tab 3: PRODI & FAKULTAS -->
        @elseif($currentTab === 'prodi_fakultas')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Daftar Fakultas -->
                <div class="border border-slate-200 rounded-2xl p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center space-x-1.5">
                        <i class="fa-solid fa-landmark text-blue-600"></i>
                        <span>Daftar Fakultas</span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($data['fakultas'] ?? [] as $fak)
                            <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center text-xs">
                                <span class="font-bold text-slate-900">{{ $fak['fakultas'] ?? $fak['nama'] ?? $fak['nama_fakultas'] ?? 'Fakultas Teknik' }}</span>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-mono font-bold">{{ $fak['singkatan'] ?? $fak['kode'] ?? 'FT' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Daftar Program Studi -->
                <div class="border border-slate-200 rounded-2xl p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center space-x-1.5">
                        <i class="fa-solid fa-graduation-cap text-purple-600"></i>
                        <span>Daftar Program Studi</span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($data['prodi'] ?? [] as $pr)
                            <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center text-xs">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $pr['prodi'] ?? $pr['nama'] ?? $pr['nama_prodi'] ?? 'Program Studi' }}</p>
                                    <span class="text-[10px] text-slate-500">Kode: {{ $pr['singkatan'] ?? $pr['kode'] ?? 'PRODI' }}</span>
                                </div>
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded font-mono font-bold">{{ $pr['singkatan'] ?? $pr['jenjang'] ?? 'S1' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        <!-- Tab 4: MATAKULIAH -->
        @elseif($currentTab === 'matakuliah')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <th class="py-3 px-4">Kode MK</th>
                            <th class="py-3 px-4">Nama Matakuliah</th>
                            <th class="py-3 px-4 text-center">SKS</th>
                            <th class="py-3 px-4 text-center">Semester</th>
                            <th class="py-3 px-4">Program Studi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['data']['data'] ?? $data['data'] ?? $data as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-purple-700">{{ $row['kode_matkul'] ?? $row['kode'] ?? $row['kode_mk'] ?? '-' }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $row['nama_matkul'] ?? $row['nama'] ?? $row['nama_mk'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700">{{ (int)($row['sks_mk'] ?? $row['sks'] ?? 3) }} SKS</td>
                                <td class="py-3 px-4 text-center text-slate-600">Semester {{ $row['semester'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $row['prodi'] ?? 'Teknik Informatika' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada data matakuliah ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- Tab 5: JADWAL KULIAH -->
        @elseif($currentTab === 'jadwal')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <th class="py-3 px-4">Hari & Jam</th>
                            <th class="py-3 px-4">Matakuliah</th>
                            <th class="py-3 px-4">Dosen Pengampu</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4">Ruang / Platform</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['data']['data'] ?? $data['data'] ?? $data as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block">{{ $row['nama_hari'] ?? $row['hari'] ?? 'Senin' }}</span>
                                    <span class="text-slate-500 font-mono">{{ $row['nama_jam'] ?? $row['jam'] ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-blue-700 block">{{ $row['nama_matkul'] ?? $row['nama_mk'] ?? $row['matakuliah'] ?? '-' }}</span>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $row['kode_matkul'] ?? $row['kode_mk'] ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">{{ $row['nama_dosen'] ?? $row['dosen'] ?? '-' }}</td>
                                <td class="py-3 px-4 font-mono font-bold text-purple-700">{{ $row['nama_kelas'] ?? $row['kelas'] ?? 'A' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-chalkboard-user mr-1.5 text-emerald-600"></i>
                                        {{ $row['nama_ruangan'] ?? $row['ruang'] ?? 'Ruang Kelas Virtual 3in1' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada jadwal kuliah ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        <!-- Tab 6: SEMESTER / TAHUN AJARAN -->
        @elseif($currentTab === 'semester')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <th class="py-3 px-4">ID Semester</th>
                            <th class="py-3 px-4">Tahun Akademik</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['data']['data'] ?? $data['data'] ?? $data as $row)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $row['id_smt'] ?? $row['id'] ?? $row['id_thn_ajaran'] ?? '-' }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $row['nm_smt'] ?? $row['tahun'] ?? $row['nama_tahun_ajaran'] ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if(($row['aktif'] ?? '') === 'y' || str_contains(strtolower($row['status'] ?? ''), 'aktif'))
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                            Aktif Berjalan
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                            {{ $row['status'] ?? 'Selesai' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada data semester / tahun ajaran ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

