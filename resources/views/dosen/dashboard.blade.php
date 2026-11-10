@extends('layouts.app')

@section('title', 'Dashboard Dosen - 3in1 Virtual Class')

@section('content')
<div class="space-y-6">

    <!-- Welcome Hero Section (Ringkas & Elegan) -->
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-blue-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-purple-200 backdrop-blur-md mb-3 border border-white/10">
                <i class="fa-solid fa-crown mr-1.5 text-yellow-300"></i> Host (Dosen) &bull; {{ $user->prodi ?? 'Program Studi' }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, {{ $user->name }}</h1>
            <p class="text-purple-200 text-xs sm:text-sm mt-2 leading-relaxed">
                Kelola ruang perkuliahan virtual, presentasi materi interaktif, dan presensi mahasiswa untuk kelas yang Anda ampu.
            </p>
        </div>
        <div class="absolute -right-8 -bottom-8 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-chalkboard-user text-8xl"></i>
        </div>
    </div>

    <!-- Section: Daftar Kelas Perkuliahan Anda (Simple & Terpadu) -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-100 gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Daftar Kelas Perkuliahan Anda</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Kelas hasil ploting Admin. Pilih kelas untuk <strong>Buka Sesi Kelas Baru</strong> atau bergabung ke sesi yang sedang aktif.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Dropdown Filter Semester -->
                <form method="GET" action="{{ route('dosen.dashboard') }}" class="inline-flex items-center space-x-1.5">
                    <label for="tahun_ajaran" class="text-xs font-semibold text-slate-500 hidden sm:inline">Semester:</label>
                    <select name="tahun_ajaran" id="tahun_ajaran" onchange="this.form.submit()"
                        class="text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-purple-600 focus:outline-none shadow-xs">
                        @foreach($availableSemesters as $sem)
                            <option value="{{ $sem }}" {{ ($selectedTahunAjaran ?? '') === $sem ? 'selected' : '' }}>
                                {{ $sem }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 text-xs font-bold border border-purple-200/60 inline-flex items-center">
                    <i class="fa-solid fa-chalkboard mr-1.5"></i> {{ isset($teachingClasses) ? $teachingClasses->count() : 0 }} Kelas Ditugaskan
                </span>
            </div>
        </div>

        @if(!isset($teachingClasses) || $teachingClasses->isEmpty())
            <div class="text-center py-12 px-4 rounded-2xl border-2 border-dashed border-slate-200 mt-6 bg-slate-50/50">
                <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum Ada Kelas yang Ditugaskan</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                    Admin Akademik belum mem-ploting kelas perkuliahan ke akun Anda untuk semester {{ $selectedTahunAjaran ?? 'aktif' }}.
                </p>
            </div>
        @else
            <div class="space-y-4 mt-6">
                @foreach($teachingClasses as $class)
                    @php
                        $activeSession = $class->activeSession();
                        $studentCount = $class->students ? $class->students->count() : 0;
                    @endphp
                    <div class="p-5 rounded-2xl border {{ $activeSession ? 'border-purple-300 bg-purple-50/20 ring-1 ring-purple-400/30' : 'border-slate-200 bg-white' }} hover:border-purple-300 transition shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        
                        <!-- Info Utama Kelas -->
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $class->kode_mk }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-purple-100 text-purple-700">
                                    {{ $class->nama_kelas }}
                                </span>
                                <span class="text-xs text-slate-500 font-medium">
                                    {{ $class->sks }} SKS &bull; Semester {{ $class->semester }}
                                </span>

                                @if($activeSession)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span> LIVE
                                    </span>
                                    <span class="text-xs font-mono font-bold px-2 py-0.5 bg-slate-100 rounded text-slate-700">
                                        {{ $activeSession->room_code }}
                                    </span>
                                @endif
                            </div>

                            <!-- Judul Mata Kuliah -->
                            <h3 class="text-base font-bold text-slate-900 truncate">
                                {{ $class->kode_mk }} - {{ $class->nama_mk }}
                            </h3>

                            <!-- Metadata: Jadwal, Ruang, Mahasiswa -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span>
                                    <i class="fa-regular fa-clock mr-1 text-purple-600"></i>
                                    {{ $class->hari }}, {{ $class->jam_mulai }} - {{ $class->jam_selesai }}
                                </span>
                                @if($class->ruang)
                                    <span>
                                        <i class="fa-solid fa-location-dot mr-1 text-slate-400"></i>
                                        {{ $class->ruang }}
                                    </span>
                                @endif
                                <span>
                                    <i class="fa-solid fa-user-graduate mr-1 text-emerald-600"></i>
                                    {{ $studentCount }} Mahasiswa
                                </span>
                                @if($activeSession)
                                    <span class="text-purple-700 font-semibold">
                                        <i class="fa-solid fa-users mr-1"></i>
                                        {{ $activeSession->attendances_count ?? 1 }} Peserta Terhubung
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Aksi Tombol: Sederhana & Langsung -->
                        <div class="flex items-center gap-2 sm:shrink-0">
                            @if($activeSession)
                                <span class="sr-only">SESI VIRTUAL SEDANG BERLANGSUNG</span>
                                <a href="{{ route('classroom.room', $activeSession->room_code) }}"
                                    class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-sm transition inline-flex items-center space-x-1.5">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    <span>Masuk Kembali ke Ruang Kelas</span>
                                </a>
                                <a href="{{ route('dosen.classroom.export_attendance', $activeSession->room_code) }}"
                                    title="Unduh Presensi (CSV)"
                                    class="px-3 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-semibold transition inline-flex items-center space-x-1">
                                    <i class="fa-solid fa-file-csv text-blue-600"></i>
                                    <span>Presensi</span>
                                </a>
                            @else
                                <form action="{{ route('dosen.classroom.create') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="course_class_id" value="{{ $class->id }}">
                                    <input type="hidden" name="title" value="{{ $class->nama_mk }} ({{ $class->nama_kelas }})">
                                    <button type="submit"
                                        class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-sm transition inline-flex items-center space-x-1.5">
                                        <i class="fa-solid fa-video text-xs"></i>
                                        <span>Mulai Sesi Virtual</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
