@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa (Peserta) - 3in1 Virtual Class')

@section('content')
<div class="space-y-8">

    <!-- Welcome Hero Section -->
    <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-emerald-200 backdrop-blur-md mb-3 border border-white/10">
                <i class="fa-solid fa-user-graduate mr-1.5 text-emerald-300"></i> Hak Akses: Peserta Kelas (Mahasiswa)
            </span>
            <h1 class="text-3xl font-extrabold tracking-tight">Halo, {{ $user->name }}</h1>
            <p class="text-emerald-200 text-sm mt-2 leading-relaxed">
                NIM: <span class="font-mono font-bold">{{ $user->username }}</span> &bull; Program Studi: <span class="font-semibold">{{ $user->prodi ?? 'Teknik' }}</span>.
                Masukkan kode kelas yang dibagikan oleh Dosen untuk langsung bergabung ke sesi interaktif.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-graduation-cap text-9xl"></i>
        </div>
    </div>

    <div class="max-w-2xl mx-auto">
        <!-- Card Gabung Ruang Kelas -->
        <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold mx-auto mb-4 text-2xl shadow-inner">
                <i class="fa-solid fa-door-open"></i>
            </div>

            <h2 class="text-xl font-bold text-slate-900">Gabung ke Ruang Kelas Virtual</h2>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Silakan ketikkan kode ruangan (room code) yang diberikan oleh Dosen pengampu Anda.
            </p>

            <form action="{{ route('mahasiswa.classroom.join') }}" method="POST" class="mt-6 max-w-md mx-auto space-y-4">
                @csrf
                <div>
                    <input type="text" name="room_code" required
                        placeholder="Contoh: 3IN1-WEB8"
                        class="w-full text-center tracking-widest font-mono text-lg uppercase px-4 py-3 rounded-2xl border-2 border-slate-200 focus:border-emerald-600 focus:ring-0 text-slate-800 placeholder:normal-case placeholder:tracking-normal placeholder:text-sm">
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-sm font-semibold shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Masuk ke Kelas</span>
                </button>
            </form>

            @if(!empty($jadwal))
                <!-- Quick Join from Campus API Schedule -->
                <div class="mt-6 pt-6 border-t border-slate-100 text-left">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">
                        <i class="fa-solid fa-calendar-check text-emerald-600 mr-1"></i> Jadwal Kuliah Aktif Anda (API Kampus):
                    </span>
                    <div class="space-y-2">
                        @foreach($jadwal as $jdw)
                            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900">{{ $jdw['nama_mk'] ?? $jdw['matakuliah'] }}</h4>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $jdw['hari'] ?? 'Senin' }}, {{ $jdw['jam'] ?? '08:00 - 10:30' }} &bull; {{ $jdw['dosen'] ?? 'Dosen Pengampu' }}
                                    </p>
                                </div>
                                <a href="{{ route('classroom.room', $jdw['room_code'] ?? '3IN1-WEB8') }}"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition">
                                    Gabung Sesi
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-8 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-eye text-emerald-600"></i>
                        <span>Sinkronisasi Slide</span>
                    </div>
                    <p class="text-slate-500">Materi presentasi berpindah otomatis mengikuti layar Dosen.</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-hand text-amber-500"></i>
                        <span>Fitur Raise Hand</span>
                    </div>
                    <p class="text-slate-500">Angkat tangan saat ingin bertanya atau memberikan tanggapan.</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-comments text-blue-500"></i>
                        <span>Papan Diskusi</span>
                    </div>
                    <p class="text-slate-500">Kirim pesan dan pertanyaan ke Dosen maupun sesama rekan kuliah.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

