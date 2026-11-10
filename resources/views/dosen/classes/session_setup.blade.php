@extends('layouts.app')

@section('title', 'Atur Perkuliahan - ' . $class->nama_mk)

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Breadcrumb & Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('dosen.dashboard') }}"
            class="inline-flex items-center text-xs font-semibold text-purple-700 hover:text-purple-900 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Dashboard Dosen
        </a>
        <span class="text-xs font-medium text-slate-500">
            Tahun Ajaran: <span class="font-bold text-slate-700">{{ $class->tahun_ajaran }}</span>
        </span>
    </div>

    <!-- Header Ringkasan Kelas -->
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
        <div class="relative z-10 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-white/20 text-purple-200 border border-white/10">
                    {{ $class->kode_mk }}
                </span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-purple-500/30 text-purple-200 border border-purple-400/20">
                    {{ $class->nama_kelas }}
                </span>
                <span class="text-xs text-purple-200">
                    {{ $class->sks }} SKS &bull; Semester {{ $class->semester }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                {{ $class->nama_mk }}
            </h1>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-purple-200 pt-2">
                <span>
                    <i class="fa-regular fa-clock mr-1 text-purple-300"></i> {{ $class->hari }}, {{ $class->jam_mulai }} - {{ $class->jam_selesai }}
                </span>
                @if($class->ruang)
                    <span>
                        <i class="fa-solid fa-location-dot mr-1 text-purple-300"></i> Ruang: {{ $class->ruang }}
                    </span>
                @endif
                <span>
                    <i class="fa-solid fa-user-graduate mr-1 text-emerald-400"></i> {{ $class->students->count() }} Mahasiswa Terploting
                </span>
            </div>
        </div>
        <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-chalkboard-user text-8xl"></i>
        </div>
    </div>

    <!-- Form Pengaturan Sesi Perkuliahan -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="border-b border-slate-100 pb-5 mb-6">
            <h2 class="text-lg font-bold text-slate-900">Buka Sesi Perkuliahan Baru</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Tentukan nomor pertemuan, tanggal, dan pilih metode perkuliahan (Online Virtual atau Offline Tatap Muka).
            </p>
        </div>

        <form action="{{ route('dosen.classes.sessions.store', $class->id) }}" method="POST" id="lecture-session-form" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Nomor Pertemuan -->
                <div>
                    <label for="pertemuan_ke" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Pertemuan ke- <span class="text-rose-500">*</span>
                    </label>
                    <select name="pertemuan_ke" id="pertemuan_ke" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:ring-2 focus:ring-purple-600 focus:outline-none bg-white shadow-xs">
                        @for($i = 1; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ (old('pertemuan_ke', $nextMeeting) == $i) ? 'selected' : '' }}>
                                Pertemuan {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Tanggal Kuliah -->
                <div>
                    <label for="tanggal" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Tanggal Pelaksanaan <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" id="tanggal" required value="{{ old('tanggal', date('Y-m-d')) }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:ring-2 focus:ring-purple-600 focus:outline-none bg-white shadow-xs">
                </div>

                <!-- Topik / Pokok Bahasan -->
                <div>
                    <label for="topik" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Topik / Materi (Opsional)
                    </label>
                    <input type="text" name="topik" id="topik" placeholder="Contoh: Pengantar Algoritma..." value="{{ old('topik') }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-600 focus:outline-none bg-white shadow-xs">
                </div>
            </div>

            <!-- Mode Selector: Online vs Offline Cards -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                    Pilih Metode Perkuliahan <span class="text-rose-500">*</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <!-- Kartu Pilihan Mode Online -->
                    <div class="relative flex flex-col justify-between p-6 rounded-2xl border-2 border-purple-200 bg-gradient-to-br from-purple-50/50 to-indigo-50/30 hover:border-purple-500 hover:shadow-md transition">
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-xl shadow-md shadow-purple-600/30 mb-4">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 text-purple-800 mb-2">
                                Daring (Virtual 3-in-1)
                            </span>
                            <h3 class="text-base font-extrabold text-slate-900">Perkuliahan Online</h3>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Membuka ruang kelas virtual 3-in-1 dengan sinkronisasi dokumen materi presentasi (PPTX/PDF), kontrol mikrofon peserta, serta log chat interaktif.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-purple-100">
                            <button type="submit" name="mode" value="online"
                                class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-600/20 transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-play"></i>
                                <span>Mulai Ruang Virtual Online</span>
                            </button>
                        </div>
                    </div>

                    <!-- Kartu Pilihan Mode Offline -->
                    <div class="relative flex flex-col justify-between p-6 rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/50 to-teal-50/30 hover:border-emerald-500 hover:shadow-md transition">
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-600/30 mb-4">
                                <i class="fa-solid fa-clipboard-user"></i>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 mb-2">
                                Tatap Muka Fisik
                            </span>
                            <h3 class="text-base font-extrabold text-slate-900">Perkuliahan Offline</h3>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Perkuliahan tatap muka di ruang kelas fisik. Anda langsung diarahkan ke <strong>Lembar Presensi Offline</strong> untuk menandai kehadiran mahasiswa dan mengekspor rekap.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-emerald-100">
                            <button type="submit" name="mode" value="offline"
                                class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-arrow-right"></i>
                                <span>Buka Lembar Presensi Offline</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Riwayat Sesi Perkuliahan Sebelumnya -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Riwayat Sesi Perkuliahan Kelas Ini</h2>

        @if(!isset($historySessions) || $historySessions->isEmpty())
            <div class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-calendar-xmark text-3xl mb-2 text-slate-300"></i>
                <p class="text-xs">Belum ada riwayat sesi perkuliahan untuk kelas ini.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($historySessions as $hs)
                    <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700">
                                    Pertemuan {{ $hs->pertemuan_ke }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold {{ $hs->isOnline() ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ strtoupper($hs->mode) }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ $hs->tanggal ? $hs->tanggal->format('d M Y') : '-' }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-800">
                                {{ $hs->topik ?? 'Materi Perkuliahan' }}
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            @if($hs->isOffline())
                                <a href="{{ route('dosen.lectures.attendance', $hs->id) }}"
                                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition inline-flex items-center space-x-1">
                                    <i class="fa-solid fa-clipboard-user"></i>
                                    <span>Lihat Presensi</span>
                                </a>
                            @else
                                @if($hs->virtualClassroom)
                                    <a href="{{ route('classroom.room', $hs->virtualClassroom->room_code) }}"
                                        class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-semibold transition inline-flex items-center space-x-1">
                                        <i class="fa-solid fa-video"></i>
                                        <span>Ruang Kelas</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection

