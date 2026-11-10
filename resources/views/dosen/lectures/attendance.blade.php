@extends('layouts.app')

@section('title', 'Presensi Tatap Muka - ' . $session->courseClass->nama_mk)

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('dosen.classes.session_setup', $session->course_class_id) }}"
            class="inline-flex items-center text-xs font-semibold text-emerald-700 hover:text-emerald-900 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Pengaturan Sesi Kelas
        </a>
        <a href="{{ route('dosen.dashboard') }}"
            class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <i class="fa-solid fa-house mr-1"></i> Dashboard Dosen
        </a>
    </div>

    <!-- Header Banner Presensi Tatap Muka -->
    <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
        <div class="relative z-10 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-white/20 text-emerald-200 border border-white/10">
                    {{ $session->courseClass->kode_mk }}
                </span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-500/30 text-emerald-200 border border-emerald-400/20">
                    Pertemuan {{ $session->pertemuan_ke }}
                </span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-900">
                    <i class="fa-solid fa-chalkboard mr-1"></i> Tatap Muka (Offline)
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Lembar Presensi Tatap Muka: {{ $session->courseClass->nama_mk }}
            </h1>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-emerald-200 pt-2">
                <span>
                    <i class="fa-regular fa-calendar mr-1 text-emerald-300"></i> {{ $session->tanggal ? $session->tanggal->format('l, d F Y') : '-' }}
                </span>
                <span>
                    <i class="fa-solid fa-location-dot mr-1 text-emerald-300"></i> Ruang: {{ $session->ruang ?? $session->courseClass->ruang ?? 'Ruang Kelas' }}
                </span>
                <span>
                    <i class="fa-solid fa-graduation-cap mr-1 text-emerald-300"></i> Kelas: {{ $session->courseClass->nama_kelas }} ({{ $session->courseClass->sks }} SKS)
                </span>
                @if($session->topik)
                    <span class="w-full text-emerald-100 font-medium">
                        Topik: {{ $session->topik }}
                    </span>
                @endif
            </div>
        </div>
        <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-clipboard-user text-8xl"></i>
        </div>
    </div>

    <!-- Statistik Ringkasan Kehadiran -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs text-center">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Mahasiswa</p>
            <p class="text-2xl font-black text-slate-800 mt-1" id="stat-total">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-emerald-50/60 rounded-2xl border border-emerald-200 p-4 shadow-xs text-center">
            <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Hadir</p>
            <p class="text-2xl font-black text-emerald-700 mt-1" id="stat-hadir">{{ $stats['hadir'] ?? 0 }}</p>
        </div>
        <div class="bg-blue-50/60 rounded-2xl border border-blue-200 p-4 shadow-xs text-center">
            <p class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Izin</p>
            <p class="text-2xl font-black text-blue-700 mt-1" id="stat-izin">{{ $stats['izin'] ?? 0 }}</p>
        </div>
        <div class="bg-amber-50/60 rounded-2xl border border-amber-200 p-4 shadow-xs text-center">
            <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Sakit</p>
            <p class="text-2xl font-black text-amber-700 mt-1" id="stat-sakit">{{ $stats['sakit'] ?? 0 }}</p>
        </div>
        <div class="bg-rose-50/60 rounded-2xl border border-rose-200 p-4 shadow-xs text-center col-span-2 sm:col-span-1">
            <p class="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Alpa</p>
            <p class="text-2xl font-black text-rose-700 mt-1" id="stat-alpa">{{ $stats['alpa'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Lembar Presensi Mahasiswa -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        
        <form action="{{ route('dosen.lectures.attendance.save', $session->id) }}" method="POST">
            @csrf

            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Daftar Kehadiran Mahasiswa</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah status presensi per mahasiswa, lalu klik Simpan Presensi.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="markAllPresent()"
                        class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                        <i class="fa-solid fa-check-double text-emerald-600"></i>
                        <span>Tandai Semua Hadir</span>
                    </button>
                    <a href="{{ route('dosen.lectures.attendance.export', $session->id) }}"
                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                        <i class="fa-solid fa-file-csv text-blue-600"></i>
                        <span>Unduh CSV</span>
                    </a>
                </div>
            </div>

            @if(!isset($attendances) || $attendances->isEmpty())
                <div class="text-center py-10 text-slate-400">
                    <i class="fa-solid fa-users-slash text-4xl mb-2 text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-600">Belum ada mahasiswa yang ter-ploting di kelas ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3 px-3 w-12 text-center">No</th>
                                <th class="py-3 px-3 w-32">NIM</th>
                                <th class="py-3 px-3">Nama Mahasiswa</th>
                                <th class="py-3 px-3 w-80 text-center">Status Kehadiran</th>
                                <th class="py-3 px-3 w-56">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($attendances as $index => $att)
                                @php
                                    $student = $att->student;
                                    $currentStatus = old("attendances.{$student->id}.status", $att->status);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-3.5 px-3 font-mono font-bold text-slate-700">
                                        {{ $student->username }}
                                    </td>
                                    <td class="py-3.5 px-3 font-semibold text-slate-900">
                                        {{ $student->name }}
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 gap-1 status-group">
                                            
                                            <!-- Hadir -->
                                            <label class="cursor-pointer">
                                                <input type="radio" name="attendances[{{ $student->id }}][status]" value="hadir"
                                                    class="sr-only peer status-radio" {{ $currentStatus === 'hadir' ? 'checked' : '' }} onchange="updateCounters()">
                                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 block peer-checked:bg-emerald-600 peer-checked:text-white transition shadow-xs">
                                                    Hadir
                                                </span>
                                            </label>

                                            <!-- Izin -->
                                            <label class="cursor-pointer">
                                                <input type="radio" name="attendances[{{ $student->id }}][status]" value="izin"
                                                    class="sr-only peer status-radio" {{ $currentStatus === 'izin' ? 'checked' : '' }} onchange="updateCounters()">
                                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 block peer-checked:bg-blue-600 peer-checked:text-white transition shadow-xs">
                                                    Izin
                                                </span>
                                            </label>

                                            <!-- Sakit -->
                                            <label class="cursor-pointer">
                                                <input type="radio" name="attendances[{{ $student->id }}][status]" value="sakit"
                                                    class="sr-only peer status-radio" {{ $currentStatus === 'sakit' ? 'checked' : '' }} onchange="updateCounters()">
                                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 block peer-checked:bg-amber-500 peer-checked:text-white transition shadow-xs">
                                                    Sakit
                                                </span>
                                            </label>

                                            <!-- Alpa -->
                                            <label class="cursor-pointer">
                                                <input type="radio" name="attendances[{{ $student->id }}][status]" value="alpa"
                                                    class="sr-only peer status-radio" {{ $currentStatus === 'alpa' ? 'checked' : '' }} onchange="updateCounters()">
                                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 block peer-checked:bg-rose-600 peer-checked:text-white transition shadow-xs">
                                                    Alpa
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <input type="text" name="attendances[{{ $student->id }}][catatan]"
                                            value="{{ old("attendances.{$student->id}.catatan", $att->catatan) }}"
                                            placeholder="Keterangan..."
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none bg-white">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tombol Simpan Bawah -->
                <div class="mt-8 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500">
                        Pastikan seluruh data presensi sudah sesuai sebelum meninggalkan halaman ini.
                    </p>
                    <div class="flex items-center space-x-3 w-full sm:w-auto">
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Presensi Perkuliahan</span>
                        </button>
                    </div>
                </div>
            @endif

        </form>
    </div>

</div>

<script>
function markAllPresent() {
    const presentRadios = document.querySelectorAll('input.status-radio[value="hadir"]');
    presentRadios.forEach(radio => {
        radio.checked = true;
    });
    updateCounters();
}

function updateCounters() {
    let hadir = 0, izin = 0, sakit = 0, alpa = 0;
    document.querySelectorAll('input.status-radio:checked').forEach(radio => {
        if (radio.value === 'hadir') hadir++;
        else if (radio.value === 'izin') izin++;
        else if (radio.value === 'sakit') sakit++;
        else if (radio.value === 'alpa') alpa++;
    });

    const elHadir = document.getElementById('stat-hadir');
    const elIzin = document.getElementById('stat-izin');
    const elSakit = document.getElementById('stat-sakit');
    const elAlpa = document.getElementById('stat-alpa');

    if (elHadir) elHadir.innerText = hadir;
    if (elIzin) elIzin.innerText = izin;
    if (elSakit) elSakit.innerText = sakit;
    if (elAlpa) elAlpa.innerText = alpa;
}
</script>
@endsection
