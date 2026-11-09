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
                Pilih kelas perkuliahan aktif Anda di bawah atau masukkan kode ruangan untuk langsung bergabung ke sesi interaktif.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-graduation-cap text-9xl"></i>
        </div>
    </div>

    <!-- Section: Matakuliah & Kelas Terdaftar Anda -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-100 gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl shadow-inner">
                    <i class="fa-solid fa-book-bookmark"></i>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Matakuliah &amp; Kelas Terdaftar Anda</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar kelas perkuliahan hasil ploting KRS Anda. Masuk langsung ke ruang kelas saat Dosen memulai sesi.</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200/60 inline-flex items-center">
                    <i class="fa-solid fa-graduation-cap mr-1.5"></i> {{ isset($enrolledClasses) ? $enrolledClasses->count() : 0 }} Kelas Terdaftar
                </span>
            </div>
        </div>

        @if(!isset($enrolledClasses) || $enrolledClasses->isEmpty())
            <div class="text-center py-12 px-4 rounded-2xl border-2 border-dashed border-slate-200 mt-6 bg-slate-50/50">
                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum Ada Kelas yang Diploting</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                    Anda belum terdaftar dalam kelas matakuliah manapun. Silakan hubungi Administrator untuk ploting kelas atau gunakan formulir kode ruangan di bawah untuk bergabung ke sesi ad-hoc.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-6">
                @foreach($enrolledClasses as $class)
                    @php
                        $activeSession = $class->activeSession();
                    @endphp
                    <div class="bg-gradient-to-b from-white to-slate-50/50 rounded-2xl border {{ $activeSession ? 'border-red-300 ring-2 ring-red-500/20 shadow-md' : 'border-slate-200 hover:border-emerald-300 hover:shadow-md' }} p-5 transition flex flex-col justify-between">
                        <div>
                            <!-- Header Kartu: Badge Status, Kode MK & SKS -->
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $class->kode_mk }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $class->sks }} SKS
                                    </span>
                                </div>

                                @if($activeSession)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-200 shadow-xs animate-pulse">
                                        🔴 KELAS SEDANG LIVE
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Belum Ada Sesi Aktif
                                    </span>
                                @endif
                            </div>

                            <!-- Nama MK & Kelas -->
                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                {{ $class->nama_mk }}
                            </h3>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                                    {{ $class->nama_kelas }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ $class->prodi }} &bull; Sem. {{ $class->semester }}
                                </span>
                            </div>

                            <!-- Dosen Pengampu & Detail Jadwal -->
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-chalkboard-user w-4 text-emerald-600"></i>
                                    <span class="font-medium text-slate-700">{{ $class->dosen->name ?? 'Dosen Pengampu' }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fa-regular fa-clock w-4 text-purple-600"></i>
                                    <span>{{ $class->hari }}, {{ $class->jam_mulai }} - {{ $class->jam_selesai }}</span>
                                </div>
                                @if($class->ruang)
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid fa-location-dot w-4 text-slate-400"></i>
                                        <span>{{ $class->ruang }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Button: Masuk ke Kelas (Live) / Menunggu Dosen -->
                        <div class="mt-5 pt-4 border-t border-slate-100">
                            @if($activeSession)
                                <a href="{{ route('classroom.room', $activeSession->room_code) }}"
                                    class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-red-600/20 transition flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-right-to-bracket animate-bounce"></i>
                                    <span>Masuk ke Kelas</span>
                                </a>
                            @else
                                <div class="w-full py-2.5 px-4 bg-slate-100 text-slate-500 rounded-xl text-xs font-semibold text-center flex items-center justify-center space-x-2 cursor-not-allowed border border-slate-200/60">
                                    <i class="fa-regular fa-hourglass-half text-slate-400"></i>
                                    <span>Menunggu Dosen Memulai Sesi</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="max-w-2xl mx-auto">
        <!-- Card Gabung Ruang Kelas (Manual Room Code) -->
        <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold mx-auto mb-4 text-2xl shadow-inner">
                <i class="fa-solid fa-door-open"></i>
            </div>

            <h2 class="text-xl font-bold text-slate-900">Gabung ke Ruang Kelas Virtual</h2>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Silakan ketikkan kode ruangan (room code) untuk bergabung ke ruang kelas virtual atau sesi ad-hoc.
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
                        <i class="fa-solid fa-calendar-check text-emerald-600 mr-1"></i> Jadwal Kuliah Prodi Anda (API Kampus):
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
