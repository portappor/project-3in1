@extends('layouts.app')

@section('title', 'Dashboard Dosen (Host) - 3in1 Virtual Class')

@section('content')
<div class="space-y-8">

    <!-- Welcome Hero Section -->
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-blue-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-purple-200 backdrop-blur-md mb-3 border border-white/10">
                <i class="fa-solid fa-crown mr-1.5 text-yellow-300"></i> Hak Akses: Host Ruang Kelas
            </span>
            <h1 class="text-3xl font-extrabold tracking-tight">Selamat Datang, {{ $user->name }}</h1>
            <p class="text-purple-200 text-sm mt-2 leading-relaxed">
                Anda memiliki hak penuh untuk membuat sesi kelas virtual, menyinkronkan presentasi dokumen, mengontrol mikrofon peserta, serta mengekspor data absensi dan log diskusi.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-chalkboard-user text-9xl"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Form: Buat Sesi Kelas Virtual Baru -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center space-x-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-plus text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Buka Sesi Kelas Baru</h2>
                    <p class="text-xs text-slate-500">Mulai ruang interaktif virtual</p>
                </div>
            </div>

            <form action="{{ route('dosen.classroom.create') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Pilih Matakuliah (Dari API Kampus)
                    </label>
                    <input type="text" name="title" list="mk-list" required
                        placeholder="Ketik atau pilih matakuliah..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-purple-600 focus:border-purple-600">
                    <datalist id="mk-list">
                        @foreach($matakuliah as $mk)
                            <option value="{{ $mk['nama'] ?? $mk['nama_mk'] }} ({{ $mk['kode'] ?? $mk['kode_mk'] }})">
                        @endforeach
                    </datalist>
                </div>

                <div class="p-3 bg-purple-50 rounded-xl border border-purple-100 text-xs text-purple-900 space-y-1">
                    <div class="font-semibold flex items-center space-x-1.5">
                        <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i>
                        <span>Pengaturan Otomatis:</span>
                    </div>
                    <p class="text-purple-700">Kode akses unik akan di-generate otomatis untuk dibagikan ke mahasiswa.</p>
                </div>

                <button type="submit"
                    class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-sm font-semibold shadow-md shadow-purple-600/20 transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-play"></i>
                    <span>Mulai Kelas Sekarang</span>
                </button>
            </form>
        </div>

        <!-- Daftar Sesi Kelas & Akses Room -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Sesi Kelas Virtual Anda</h2>
                    <p class="text-xs text-slate-500">Kelola dan masuki sesi yang sedang berlangsung</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($recentRooms as $room)
                    @php
                        $code = is_object($room) ? $room->room_code : $room['room_code'];
                        $title = is_object($room) ? $room->title : $room['title'];
                        $createdAt = is_object($room) ? $room->created_at->format('d M Y, H:i') : $room['created_at'];
                        $participantsCount = is_object($room) ? ($room->attendances_count ?? 1) : ($room['participants_count'] ?? 1);
                    @endphp
                    <div class="p-4 rounded-xl border border-slate-200 hover:border-purple-300 bg-slate-50/50 hover:bg-purple-50/20 transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span> LIVE
                                </span>
                                <span class="text-xs font-mono font-bold px-2 py-0.5 bg-slate-200 rounded text-slate-800 tracking-wider">
                                    {{ $code }}
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $title }}</h3>
                            <p class="text-xs text-slate-500">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $createdAt }} &bull;
                                <i class="fa-solid fa-users mr-1"></i> {{ $participantsCount }} Peserta Terhubung
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <a href="{{ route('classroom.room', $code) }}"
                                class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-sm transition inline-flex items-center justify-center space-x-1.5">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                <span>Masuk Kelas</span>
                            </a>
                            <a href="{{ route('dosen.classroom.export_attendance', $code) }}" title="Unduh CSV Presensi"
                                class="px-2.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition inline-flex items-center justify-center space-x-1">
                                <i class="fa-solid fa-file-csv text-blue-600"></i>
                                <span>Presensi</span>
                            </a>
                            <a href="{{ route('dosen.classroom.export_chat', $code) }}" title="Unduh CSV Log Chat"
                                class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold transition inline-flex items-center justify-center space-x-1">
                                <i class="fa-solid fa-comments text-slate-600"></i>
                                <span>Chat</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400">
                        <i class="fa-solid fa-chalkboard text-4xl mb-2 text-slate-300"></i>
                        <p class="text-sm">Belum ada sesi kelas yang aktif.</p>
                    </div>
                @endforelse
            </div>

            <!-- Panel Fitur Host Dosen -->
            <div class="mt-8 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-file-pdf text-rose-500"></i>
                        <span>Sinkronisasi Slide</span>
                    </div>
                    <p class="text-slate-500">Dosen mengunggah materi presentasi & mengendalikan slide peserta secara real-time.</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-microphone-slash text-amber-500"></i>
                        <span>Mute Mikrofon Semua</span>
                    </div>
                    <p class="text-slate-500">Membisukan semua mikrofon mahasiswa sekaligus saat pemaparan kuliah.</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div class="font-bold text-slate-800 flex items-center space-x-1.5 mb-1">
                        <i class="fa-solid fa-download text-blue-500"></i>
                        <span>Unduh Presensi & Chat</span>
                    </div>
                    <p class="text-slate-500">Mengunduh rekapan daftar mahasiswa yang hadir dan riwayat diskusi di ruang kelas.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Bagian Matakuliah yang Diampu & Jadwal Kuliah Dosen -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- 1. Jadwal Mengajar Anda -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center space-x-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-calendar-check text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Jadwal Mengajar Anda</h2>
                    <p class="text-xs text-slate-500">Jadwal perkuliahan yang ditugaskan kepada Anda</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($jadwalMengajar as $j)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-purple-300 transition flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-800">
                                    {{ $j['hari'] ?? 'Senin' }}
                                </span>
                                <span class="text-xs font-mono text-slate-500 font-semibold">
                                    {{ $j['jam'] ?? '08:00 - 10:30' }}
                                </span>
                                <span class="text-xs font-bold px-1.5 py-0.5 bg-slate-200 rounded text-slate-700">
                                    {{ $j['kelas'] ?? 'TI-4A' }}
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 mt-1">{{ $j['nama_mk'] ?? $j['matakuliah'] }}</h3>
                            <p class="text-xs text-slate-500">{{ $j['ruang'] ?? 'Ruang Kelas Virtual' }}</p>
                        </div>
                        <form action="{{ route('dosen.classroom.create') }}" method="POST">
                            @csrf
                            <input type="hidden" name="title" value="{{ $j['nama_mk'] ?? $j['matakuliah'] }} ({{ $j['kelas'] ?? 'Kelas' }})">
                            <button type="submit" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                                <i class="fa-solid fa-play text-[10px]"></i>
                                <span>Mulai Sesi Ini</span>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada jadwal mengajar.</p>
                @endforelse
            </div>
        </div>

        <!-- 2. Matakuliah yang Diampu -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center space-x-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-book-bookmark text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Matakuliah yang Diampu</h2>
                    <p class="text-xs text-slate-500">Daftar beban matakuliah Anda semester ini</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($matakuliah as $mk)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-purple-700 font-mono font-black text-xs flex items-center justify-center shadow-xs">
                                {{ substr($mk['kode'] ?? $mk['kode_mk'] ?? 'MK', 0, 3) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $mk['nama'] ?? $mk['nama_mk'] }}</h3>
                                <p class="text-xs text-slate-500">
                                    Kode: <span class="font-mono font-bold">{{ $mk['kode'] ?? $mk['kode_mk'] }}</span> &bull; 
                                    Bobot: <span class="font-semibold text-slate-700">{{ $mk['sks'] ?? 3 }} SKS</span>
                                </p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                            Semester {{ $mk['semester'] ?? 4 }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada matakuliah yang terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

