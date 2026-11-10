@extends('layouts.app')

@section('title', 'Ruang Kelas Virtual: ' . ($classroom->room_code ?? $room_code) . ' - 3in1')

@section('content')
<div class="space-y-4">

    <!-- Top Room Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-3">
            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg font-bold text-slate-900">{{ $classroom->title ?? 'Ruang Kelas Virtual' }}</h1>
                    <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-purple-100 text-purple-800 border border-purple-200">
                        {{ $classroom->room_code ?? $room_code }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">
                    Peran Anda:
                    @if($isHost)
                        <span class="font-bold text-purple-700"><i class="fa-solid fa-crown mr-1 text-yellow-500"></i> Host Utama (Dosen)</span>
                    @else
                        <span class="font-bold text-emerald-700"><i class="fa-solid fa-user-graduate mr-1"></i> Peserta (Mahasiswa)</span>
                    @endif
                    &bull; Pengguna: <span class="font-semibold text-slate-700">{{ $user->name }} ({{ $user->username }})</span>
                </p>
            </div>
        </div>

        <!-- Host Quick Controls & Exit Button -->
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            @if($isHost)
                <button type="button" id="mute-all-btn" onclick="toggleMuteAll()"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition flex items-center space-x-1.5 shadow-xs">
                    <i id="mute-all-icon" class="fa-solid fa-microphone-slash text-amber-600"></i>
                    <span id="mute-all-text">{{ ($classroom->is_all_mic_muted ?? false) ? 'Unmute Semua' : 'Mute All Peserta' }}</span>
                </button>
                <a href="{{ route('dosen.classroom.export_attendance', $classroom->room_code ?? $room_code) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100 transition flex items-center space-x-1.5 shadow-xs"
                    title="Unduh file laporan presensi CSV">
                    <i class="fa-solid fa-file-arrow-down text-blue-600"></i>
                    <span>Unduh Presensi</span>
                </a>
                <a href="{{ route('dosen.classroom.export_chat', $classroom->room_code ?? $room_code) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200 hover:bg-slate-200 transition flex items-center space-x-1.5 shadow-xs"
                    title="Unduh log diskusi chat CSV">
                    <i class="fa-solid fa-download text-slate-600"></i>
                    <span>Unduh Chat</span>
                </a>
            @endif

            <a href="{{ $isHost ? route('dosen.dashboard') : route('mahasiswa.dashboard') }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition flex items-center space-x-1.5">
                <i class="fa-solid fa-door-open"></i>
                <span>Tinggalkan Kelas</span>
            </a>
        </div>
    </div>

    <!-- Alert / Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2 pointer-events-none"></div>

    <!-- Main Virtual Classroom Stage (Resizable Flex Container) -->
    <div id="classroom-split-container" class="flex flex-col lg:flex-row gap-0 lg:gap-2 items-stretch relative">

        <!-- Stage Area: Presentation & Slide Synchronizer -->
        <div id="stage-panel" class="w-full lg:w-[73%] bg-slate-900 rounded-3xl overflow-hidden shadow-xl border border-slate-800 flex flex-col h-[600px] relative transition-none">

            <!-- Slide Stage Header -->
            <div class="bg-slate-800/90 px-4 py-2 border-b border-slate-700 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-300">
                <div class="flex items-center space-x-2 truncate max-w-xs sm:max-w-md">
                    <i class="fa-solid fa-chalkboard-user text-purple-400"></i>
                    <span id="presentation-name" class="font-medium truncate">
                        {{ $classroom->presentation_name ?? 'Materi_Presentasi_Default.pdf' }}
                    </span>
                    <span id="presentation-badge" class="{{ $classroom->presentation_path ? '' : 'hidden' }} text-[10px] {{ in_array($classroom->presentation_type ?? 'pdf', ['pptx', 'ppt'], true) ? 'bg-amber-900/60 text-amber-300 border-amber-700' : 'bg-emerald-900/60 text-emerald-300 border-emerald-700' }} px-1.5 py-0.5 rounded border">
                        {{ Str::upper($classroom->presentation_type ?? 'PDF') }} Aktif
                    </span>
                    @if($classroom->presentation_path)
                        <a id="presentation-download-btn" href="{{ route('classroom.presentation.file', $classroom->room_code) }}?download=1" target="_blank" download class="text-[10px] text-slate-400 hover:text-white px-1.5 py-0.5 rounded bg-slate-700/60 hover:bg-slate-700 border border-slate-600 transition flex items-center space-x-1" title="Unduh File Materi Asli">
                            <i class="fa-solid fa-download"></i>
                            <span class="hidden md:inline">Unduh</span>
                        </a>
                    @endif
                </div>

                <!-- Stage View Mode Switcher (Presentation vs Jitsi Video vs Split) -->
                <div class="flex items-center bg-slate-900/90 p-0.5 rounded-xl border border-slate-700/80">
                    <button type="button" onclick="setStageMode('presentation')" id="btn-mode-presentation"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center space-x-1"
                        title="Tampilan Penuh Dokumen Presentasi">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span class="hidden sm:inline">Presentasi</span>
                    </button>
                    <button type="button" onclick="setStageMode('split')" id="btn-mode-split"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white transition flex items-center space-x-1 shadow-xs"
                        title="Tampilan Bersisian: Slide & Tatap Muka Video">
                        <i class="fa-solid fa-table-columns"></i>
                        <span class="hidden sm:inline">Split 50:50</span>
                    </button>
                    <button type="button" onclick="setStageMode('video')" id="btn-mode-video"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center space-x-1"
                        title="Tampilan Penuh Tatap Muka Video Conference (Jitsi)">
                        <i class="fa-solid fa-video text-emerald-400"></i>
                        <span class="hidden sm:inline">Tatap Muka</span>
                    </button>
                </div>

                <div class="flex items-center space-x-3">
                    @if(!$isHost)
                        <!-- Student Sync Lock Button & Re-sync -->
                        <div class="flex items-center space-x-2">
                            <button type="button" id="sync-lock-btn" onclick="toggleSyncLock()"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-900/60 text-emerald-300 border border-emerald-600 hover:bg-emerald-800/60 transition flex items-center space-x-1.5 shadow-xs"
                                title="Kunci slide ke presentasi Dosen">
                                <i id="sync-lock-icon" class="fa-solid fa-lock text-emerald-400"></i>
                                <span id="sync-lock-text">Sync Lock: Aktif</span>
                            </button>
                            <button type="button" id="resync-btn" onclick="resyncToHost()"
                                class="hidden px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 hover:bg-purple-700 text-white transition flex items-center space-x-1 shadow-xs animate-pulse"
                                title="Lompat kembali ke slide yang sedang diterangkan Dosen">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>Ikuti Dosen (Slide <span id="host-slide-num">1</span>)</span>
                            </button>
                        </div>
                    @endif

                    <div class="flex items-center space-x-2 font-mono">
                        <span class="text-slate-400">Slide:</span>
                        <span id="current-slide" class="font-bold text-white px-2 py-0.5 bg-slate-700 rounded">{{ $classroom->current_slide ?? 1 }}</span>
                        <span class="text-slate-500">/ <span id="total-slides">{{ $classroom->total_slides ?? 1 }}</span></span>
                    </div>
                </div>
            </div>

            <!-- Slide & Video Stage Viewport Container -->
            <div id="stage-viewport" class="flex-1 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-2 sm:p-4 flex flex-col lg:flex-row items-center justify-center text-center relative overflow-hidden gap-3">

                <!-- Floating Mode Notice when in Tatap Muka mode -->
                <div id="video-mode-notice" class="hidden absolute top-3 left-4 right-4 z-20 bg-slate-900/90 backdrop-blur border border-purple-500/40 text-white px-3.5 py-2 rounded-xl flex items-center justify-between text-xs shadow-2xl">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-semibold text-slate-200">Mode Tatap Muka Aktif (Fokus Layar Penuh Video)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="setStageMode('split')" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-[11px] font-bold rounded-lg transition shadow flex items-center space-x-1">
                            <i class="fa-solid fa-table-columns"></i>
                            <span>Buka Slide (Split 50:50)</span>
                        </button>
                    </div>
                </div>

                <!-- Presentation Section -->
                <div id="presentation-stage-section" class="w-full lg:w-1/2 h-full flex flex-col items-center justify-center relative transition-all duration-300">
                    <!-- PDF.js Canvas Container (Visual Presentation Slides) -->
                    <div id="pdf-container" class="{{ $classroom->presentation_path ? '' : 'hidden' }} w-full h-full flex items-center justify-center relative">
                        <canvas id="pdf-render-canvas" class="max-h-[480px] max-w-full rounded-xl shadow-2xl object-contain border border-slate-700"></canvas>
                        <div id="pdf-loader" class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex items-center justify-center text-white space-x-2">
                            <i class="fa-solid fa-spinner fa-spin text-xl text-blue-400"></i>
                            <span class="text-xs">Memuat Slide Materi...</span>
                        </div>
                    </div>

                    <!-- PPTX / PPT Fallback Slide View Container -->
                    <div id="pptx-container" class="hidden w-full h-full flex flex-col justify-between bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl shadow-2xl p-6 border-2 border-slate-700 relative overflow-y-auto">
                        <div class="flex justify-between items-center border-b border-slate-700/80 pb-2.5">
                            <span class="text-xs font-bold text-amber-400 flex items-center space-x-1.5 tracking-wider uppercase">
                                <i class="fa-solid fa-file-powerpoint text-amber-500"></i>
                                <span>PowerPoint Slide Deck</span>
                            </span>
                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700" id="pptx-slide-badge">SLIDE {{ $classroom->current_slide ?? 1 }}</span>
                        </div>

                        <div class="py-4 my-auto space-y-4 text-left px-2">
                            <h2 id="pptx-slide-title" class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                                {{ $classroom->presentation_name ?? 'Presentasi Slide Kuliah' }}
                            </h2>
                            
                            <div id="pptx-slide-body" class="space-y-2 text-xs sm:text-sm text-slate-200">
                                <!-- Populated dynamically by renderPptxSlide() -->
                            </div>

                            <div id="pptx-slide-images" class="flex flex-wrap gap-2 pt-2">
                                <!-- Populated dynamically if images exist -->
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-[11px] text-slate-400 pt-2.5 border-t border-slate-800">
                            <span>Host: {{ $classroom->dosen->name ?? 'Dosen Pengampu' }}</span>
                            <span class="text-amber-400 font-semibold flex items-center space-x-1">
                                <i class="fa-solid fa-bolt text-xs"></i>
                                <span>Sinkronisasi Slide PPT Otomatis</span>
                            </span>
                        </div>
                    </div>

                    <!-- Fallback Interactive Slide when no file is uploaded yet -->
                    <div id="fallback-slide" class="{{ $classroom->presentation_path ? 'hidden' : '' }} w-full max-w-2xl bg-white text-slate-800 rounded-2xl shadow-2xl p-8 aspect-video flex flex-col justify-between border-4 border-slate-800 relative">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                            <span class="text-xs font-bold text-blue-700 tracking-wider uppercase">3in1 Ruang Kelas Virtual</span>
                            <span class="text-xs font-mono text-slate-400" id="slide-num-preview">SLIDE {{ $classroom->current_slide ?? 1 }}</span>
                        </div>

                        <div class="py-6 space-y-3">
                            <h2 id="slide-title" class="text-2xl font-black text-slate-900">
                                {{ $classroom->title ?? 'Sesi Perkuliahan Interaktif' }}
                            </h2>
                            <p id="slide-subtitle" class="text-sm text-slate-600 max-w-lg mx-auto">
                                Menghubungkan Dosen sebagai Host dan Mahasiswa sebagai Participant dalam ruang pembelajaran sinkron.
                            </p>
                            @if($isHost)
                                <div class="pt-2">
                                    <label class="cursor-pointer inline-flex items-center space-x-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-md transition">
                                        <i class="fa-solid fa-file-arrow-up"></i>
                                        <span>Unggah Dokumen Materi (PDF / PPT / PPTX)</span>
                                        <input type="file" accept=".pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation" class="hidden" onchange="uploadPresentationFile(this)">
                                    </label>
                                </div>
                            @else
                                <p class="text-xs text-slate-400 italic">Menunggu Dosen menampilkan materi slide kuliah...</p>
                            @endif
                        </div>

                        <div class="flex justify-between items-center text-xs text-slate-400 pt-3 border-t border-slate-100">
                            <span>Host: {{ $classroom->dosen->name ?? 'Dosen Pengampu' }}</span>
                            <span class="flex items-center text-emerald-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Sinkronisasi Real-Time
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Video Conference Section (Single Pure Jitsi Meet Engine) -->
                <div id="jitsi-video-section" class="w-full lg:w-1/2 h-full flex flex-col relative rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 transition-all duration-300">
                    
                    <!-- Video Stage Header Bar -->
                    <div class="z-20 bg-slate-900/95 backdrop-blur px-3 py-1.5 border-b border-slate-800 flex flex-wrap items-center justify-between text-xs gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span id="video-engine-status" class="font-bold text-slate-100 text-[11px] tracking-wide flex items-center space-x-1.5">
                                <i class="fa-solid fa-video text-purple-400"></i>
                                <span>Tatap Muka Jitsi Meet</span>
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">meet.jit.si</span>
                        </div>
                        
                        <!-- Role-based Indicator Pill -->
                        <div class="flex items-center space-x-1.5 text-[10px]">
                            @if($isHost)
                                <span class="px-2 py-0.5 rounded-md bg-purple-950/80 text-purple-300 border border-purple-700/80 font-medium flex items-center space-x-1" title="Anda bertindak sebagai Host Ruang Kelas">
                                    <i class="fa-solid fa-crown text-amber-400 text-[9px]"></i>
                                    <span>Host Dosen</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md bg-emerald-950/80 text-emerald-300 border border-emerald-700/80 font-medium flex items-center space-x-1">
                                    <i class="fa-solid fa-user-graduate text-emerald-400 text-[9px]"></i>
                                    <span>Peserta Mahasiswa</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Role-based Operational Notice Banner -->
                    @if($isHost)
                        <div id="jitsi-host-notice" class="z-15 bg-indigo-950/90 border-b border-indigo-800/80 px-3 py-1 text-indigo-200 text-[10px] flex items-center justify-between">
                            <span class="flex items-center space-x-1.5">
                                <i class="fa-solid fa-lightbulb text-amber-300"></i>
                                <span>Host: Jika video meminta izin, klik tombol <strong>"Masuk"</strong> untuk membuka ruangan bagi seluruh mahasiswa (permintaan moderator).</span>
                            </span>
                            <span class="text-[9px] text-indigo-300 font-mono">1x Login Google</span>
                        </div>
                    @else
                        <div id="jitsi-student-notice" class="z-15 bg-slate-900/90 border-b border-slate-800 px-3 py-1 text-slate-300 text-[10px] flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-check text-emerald-400"></i>
                            <span>Otomatis bergabung begitu Host membuka ruangan (tanpa perlu login).</span>
                        </div>
                    @endif

                    <!-- Sole Video Container: Jitsi Meet Iframe Mount Point -->
                    <div id="jitsi-stage" class="w-full flex-1 relative flex flex-col bg-slate-950 overflow-hidden">
                        <div id="jitsi-meet-container" class="w-full flex-1 h-full"></div>

                        <!-- Jitsi Loading Spinner -->
                        <div id="jitsi-loader" class="pointer-events-none absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white space-y-3 z-10 transition-opacity duration-500">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center animate-pulse shadow-lg">
                                <i class="fa-solid fa-video text-xl"></i>
                            </div>
                            <p class="text-xs font-semibold tracking-wide">Menghubungkan ke Video Conference Jitsi Meet...</p>
                            <span class="text-[11px] text-slate-400 font-mono">{{ config('services.jitsi.domain', 'meet.jit.si') }}</span>
                        </div>
                    </div>

                </div>

                <!-- Raise Hand Alert Banner for Host -->
                <div id="raise-hand-notification" class="hidden absolute top-4 right-4 bg-amber-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-xl flex items-center space-x-2 animate-bounce z-20">
                    <i class="fa-solid fa-hand text-base"></i>
                    <span id="raise-hand-notif-text">Ada Mahasiswa yang Mengangkat Tangan!</span>
                </div>

                <!-- Host Mic Mute Banner for Participant -->
                <div id="mic-locked-banner" class="{{ ($classroom->is_all_mic_muted ?? false) ? '' : 'hidden' }} absolute bottom-3 left-4 right-4 bg-rose-900/90 text-rose-200 text-xs px-3 py-1.5 rounded-xl border border-rose-700 flex items-center justify-center space-x-2 z-20">
                    <i class="fa-solid fa-microphone-slash"></i>
                    <span>Host mengaktifkan MUTE ALL. Mikrofon seluruh peserta dinonaktifkan sementara.</span>
                </div>
            </div>

            <!-- Bottom Control Bar -->
            <div class="bg-slate-800 px-6 py-3 border-t border-slate-700 flex flex-wrap justify-between items-center gap-3">

                <!-- Media Toggles (Mic / Cam / Share Screen) -->
                <div class="flex items-center space-x-2">
                    <button type="button" id="mic-btn" onclick="toggleMyMic()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2">
                        <i id="mic-icon" class="fa-solid fa-microphone"></i>
                        <span id="mic-text">Mic Aktif</span>
                    </button>
                    <button type="button" id="cam-btn" onclick="toggleCam()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2">
                        <i id="cam-icon" class="fa-solid fa-video"></i>
                        <span id="cam-text">Kamera</span>
                    </button>
                    <button type="button" id="share-screen-btn" onclick="toggleShareScreen()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2" title="Bagikan Layar Anda (Share Screen)">
                        <i id="share-screen-icon" class="fa-solid fa-desktop"></i>
                        <span id="share-screen-text">Share Screen</span>
                    </button>
                </div>

                <!-- Host Presentation Controls (Next/Prev slide / Upload) -->
                @if($isHost)
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="prevSlide()" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold transition">
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                        </button>
                        <button type="button" onclick="nextSlide()" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition shadow-md shadow-purple-900/50">
                            Next <i class="fa-solid fa-chevron-right ml-1"></i>
                        </button>
                        <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-semibold transition inline-flex items-center space-x-1.5 border border-slate-600" title="Unggah Dokumen PDF atau Presentasi PowerPoint (PPT / PPTX)">
                            <i class="fa-solid fa-file-arrow-up text-amber-400"></i>
                            <span>Upload Materi (PDF/PPT)</span>
                            <input type="file" accept=".pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation" class="hidden" onchange="uploadPresentationFile(this)">
                        </label>
                    </div>
                @else
                    <!-- Participant Interactive Button (Sync Navigation & Raise Hand) -->
                    <div class="flex items-center space-x-2">
                        <div id="student-nav-group" class="hidden items-center space-x-1.5 mr-2">
                            <button type="button" onclick="studentPrevSlide()" class="px-2.5 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold transition flex items-center space-x-1" title="Slide Sebelumnya (Mode Bebas)">
                                <i class="fa-solid fa-chevron-left"></i>
                                <span>Prev</span>
                            </button>
                            <span class="text-[10px] text-amber-300 font-semibold px-2 py-1 bg-amber-950/70 rounded border border-amber-800">
                                <i class="fa-solid fa-compass mr-1"></i> Navigasi Bebas
                            </span>
                            <button type="button" onclick="studentNextSlide()" class="px-2.5 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold transition flex items-center space-x-1" title="Slide Selanjutnya (Mode Bebas)">
                                <span>Next</span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                        <button type="button" id="raise-hand-btn" onclick="toggleMyRaiseHand()"
                            class="px-4 py-2 rounded-xl {{ ($attendance->is_hand_raised ?? false) ? 'bg-amber-600 ring-2 ring-white' : 'bg-amber-500 hover:bg-amber-600' }} text-white text-xs font-bold transition shadow-lg shadow-amber-500/25 flex items-center space-x-2">
                            <i class="fa-solid fa-hand"></i>
                            <span id="raise-hand-text">{{ ($attendance->is_hand_raised ?? false) ? 'Turunkan Tangan' : 'Raise Hand (Angkat Tangan)' }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Draggable Resizer Bar (Splitter Divider) -->
        <div id="drag-splitter"
             class="hidden lg:flex w-3.5 items-center justify-center cursor-col-resize group hover:bg-purple-100/40 active:bg-purple-200/60 rounded-xl transition-colors py-2 touch-none relative select-none shrink-0"
             title="Geser ke kiri / kanan untuk menyesuaikan ukuran layar (Klik 2x untuk reset)">
            <div class="w-1.5 h-16 bg-slate-300 group-hover:bg-purple-600 group-active:bg-purple-700 rounded-full transition-all flex items-center justify-center shadow-xs">
                <i class="fa-solid fa-ellipsis-vertical text-[9px] text-slate-500 group-hover:text-white transition-colors"></i>
            </div>
            <!-- Tooltip visual hint -->
            <div class="absolute -top-7 left-1/2 -translate-x-1/2 hidden group-hover:block bg-slate-800 text-white text-[10px] px-2 py-0.5 rounded-md shadow-lg pointer-events-none whitespace-nowrap z-30">
                <i class="fa-solid fa-arrows-left-right mr-1 text-purple-400"></i> Geser Ukuran
            </div>
        </div>

        <!-- Right Sidebar Area: Peserta & Chat -->
        <div id="sidebar-panel" class="w-full lg:flex-1 bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[600px] overflow-hidden mt-4 lg:mt-0 transition-none">

            <!-- Sidebar Tab Headers -->
            <div class="flex border-b border-slate-200 bg-slate-50">
                <button type="button" onclick="switchTab('peserta')" id="tab-peserta" class="flex-1 py-3 text-center text-xs font-bold text-blue-600 border-b-2 border-blue-600">
                    <i class="fa-solid fa-users mr-1"></i> Peserta (<span id="participants-count">1</span>)
                </button>
                <button type="button" onclick="switchTab('chat')" id="tab-chat" class="flex-1 py-3 text-center text-xs font-semibold text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-comments mr-1"></i> Diskusi
                </button>
            </div>

            <!-- Tab Content: Peserta -->
            <div id="content-peserta" class="flex-1 p-4 overflow-y-auto space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Host Utama:</div>
                <div id="host-participant-card" class="p-2.5 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs">
                            DS
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900" id="host-name">{{ $classroom->dosen->name ?? 'Dosen Host' }}</p>
                            <span class="text-[10px] text-purple-700 font-semibold">Host Dosen</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-microphone text-emerald-500 text-xs"></i>
                </div>

                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-4 mb-2">
                    Daftar Mahasiswa (<span id="students-count">0</span>):
                </div>
                <div id="students-list" class="space-y-2">
                    <!-- Populated via real-time polling -->
                    <div class="text-center py-4 text-xs text-slate-400">
                        Memuat daftar peserta...
                    </div>
                </div>
            </div>

            <!-- Tab Content: Chat Board -->
            <div id="content-chat" class="hidden flex-1 flex flex-col h-full">
                <!-- Chat Message List -->
                <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-3 text-xs bg-slate-50/50">
                    @forelse($classroom->chats ?? [] as $chat)
                        <div class="{{ $chat->user_id === $user->id ? 'bg-blue-50 ml-auto border border-blue-100' : 'bg-white border border-slate-200' }} p-2.5 rounded-xl max-w-[85%] space-y-1 shadow-2xs">
                            <div class="flex justify-between items-center text-[10px] font-bold {{ $chat->user_id === $user->id ? 'text-blue-700' : ($chat->user->isDosen() ? 'text-purple-700' : 'text-slate-700') }}">
                                <span>{{ $chat->user->name ?? 'Pengguna' }} {{ $chat->user_id === $user->id ? '(Anda)' : '' }}</span>
                                <span class="text-slate-400 font-normal ml-2">{{ $chat->created_at->format('H:i') }}</span>
                            </div>
                            <p class="text-slate-800 break-words">{{ $chat->message }}</p>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">
                            Belum ada pesan obrolan. Mulai diskusi kelas di sini!
                        </div>
                    @endforelse
                </div>

                <!-- Chat Input Box -->
                <form onsubmit="handleSendChat(event)" class="p-3 border-t border-slate-200 bg-white flex items-center space-x-2">
                    <input type="text" id="chat-input" placeholder="Ketik pesan diskusi..." autocomplete="off"
                        class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-1 focus:ring-blue-600 focus:outline-none">
                    <button type="submit" id="chat-send-btn" class="p-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 text-xs transition shadow-sm">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Jitsi Meet External API SDK -->
<script src="https://{{ config('services.jitsi.domain', 'meet.jit.si') }}/external_api.js"></script>
<!-- PDF.js CDN library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    // State Variables
    const ROOM_CODE = "{{ $classroom->room_code ?? $room_code }}";
    const CURRENT_USER_ID = {{ $user->id }};
    const IS_HOST = {{ $isHost ? 'true' : 'false' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";

    let currentVideoEngine = 'jitsi';

    let currentPdfUrl = "{{ $classroom->presentation_url ?? '' }}";
    let presentationType = "{{ $classroom->presentation_type ?? 'pdf' }}";
    let presentationData = @json($classroom->presentation_data ?? null);
    let currentSlide = {{ (int) ($classroom->current_slide ?? 1) }};
    let totalSlides = {{ (int) ($classroom->total_slides ?? 1) }};
    let isAllMicMuted = {{ ($classroom->is_all_mic_muted ?? false) ? 'true' : 'false' }};
    let isMyMicActive = true;
    let isCamActive = true;
    let stageMode = 'split';
    let jitsiApi = null;
    let isJitsiInitialized = false;
    let isMyHandRaised = {{ ($attendance->is_hand_raised ?? false) ? 'true' : 'false' }};
    let isSyncLocked = true;
    let hostCurrentSlide = currentSlide;
    let isScreenSharing = false;

    let pdfDoc = null;
    let pageRendering = false;
    let pageNumPending = null;
    const canvas = document.getElementById('pdf-render-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;

    // Slide Titles fallback
    const fallbackTitles = [
        "Arsitektur RBAC & Autentikasi Pengguna",
        "Peran Dosen: Manajemen Presentasi & Mic Peserta",
        "Peran Mahasiswa: Interaktivitas & Raise Hand",
        "Implementasi WebSocket & Sinkronisasi State",
    ];

    // Initialize Presentation & Jitsi Video Engine
    document.addEventListener('DOMContentLoaded', () => {
        renderPresentationView();

        // Initialize Pure Jitsi Meet Video Conference
        initJitsiMeet();

        // Start Periodic Synchronization Loop
        setInterval(pollRoomState, 2500);
        pollRoomState();
    });

    function updatePresentationBadge(type) {
        const badge = document.getElementById('presentation-badge');
        if (!badge) return;
        badge.classList.remove('hidden');
        if (type === 'pptx' || type === 'ppt') {
            badge.className = 'text-[10px] bg-amber-900/60 text-amber-300 border border-amber-700 px-1.5 py-0.5 rounded';
            badge.innerText = type.toUpperCase() + ' Aktif';
        } else {
            badge.className = 'text-[10px] bg-emerald-900/60 text-emerald-300 border border-emerald-700 px-1.5 py-0.5 rounded';
            badge.innerText = 'PDF Aktif';
        }
    }

    function renderPresentationView() {
        const pdfContainer = document.getElementById('pdf-container');
        const pptxContainer = document.getElementById('pptx-container');
        const fallbackSlide = document.getElementById('fallback-slide');

        if (!currentPdfUrl) {
            if (pdfContainer) pdfContainer.classList.add('hidden');
            if (pptxContainer) pptxContainer.classList.add('hidden');
            if (fallbackSlide) fallbackSlide.classList.remove('hidden');
            renderFallbackSlide(currentSlide);
            return;
        }

        // Tampilkan dokumen visual asli via PDF.js canvas
        if (pptxContainer) pptxContainer.classList.add('hidden');
        if (fallbackSlide) fallbackSlide.classList.add('hidden');
        if (pdfContainer) pdfContainer.classList.remove('hidden');
        loadPdfDocument(currentPdfUrl, currentSlide);
    }

    function renderPptxSlide(num) {
        currentSlide = num;
        document.getElementById('current-slide').innerText = num;
        const total = totalSlides || (presentationData ? presentationData.length : 1);
        document.getElementById('total-slides').innerText = total;

        const badge = document.getElementById('pptx-slide-badge');
        if (badge) badge.innerText = `SLIDE ${num} / ${total}`;

        const slideItem = presentationData && presentationData[num - 1] ? presentationData[num - 1] : null;

        const titleEl = document.getElementById('pptx-slide-title');
        const bodyEl = document.getElementById('pptx-slide-body');
        const imagesEl = document.getElementById('pptx-slide-images');

        if (slideItem) {
            if (titleEl) titleEl.innerText = slideItem.title || `Slide ${num}`;
            if (bodyEl) {
                if (slideItem.paragraphs && slideItem.paragraphs.length > 0) {
                    bodyEl.innerHTML = slideItem.paragraphs.map(p => `
                        <div class="flex items-start space-x-2.5 py-1">
                            <span class="w-2 h-2 rounded-full bg-amber-400 mt-1.5 shrink-0"></span>
                            <span class="text-slate-200 text-xs sm:text-sm leading-relaxed">${escapeHtml(p)}</span>
                        </div>
                    `).join('');
                } else {
                    bodyEl.innerHTML = '<p class="text-slate-400 text-xs italic">Slide visual tanpa teks.</p>';
                }
            }
            if (imagesEl) {
                if (slideItem.images && slideItem.images.length > 0) {
                    imagesEl.innerHTML = slideItem.images.map(img => `
                        <img src="${img}" alt="Slide Media" class="max-h-48 rounded-xl border border-slate-700 shadow-md object-contain bg-slate-950/60 p-1">
                    `).join('');
                } else {
                    imagesEl.innerHTML = '';
                }
            }
        } else {
            if (titleEl) titleEl.innerText = `Slide ${num}`;
            if (bodyEl) bodyEl.innerHTML = `<p class="text-slate-300 text-xs">Materi presentasi PowerPoint slide ${num}.</p>`;
            if (imagesEl) imagesEl.innerHTML = '';
        }
    }

    // Load PDF via PDF.js with same-origin URL enforcement & credentials
    function loadPdfDocument(url, initialPage = 1) {
        showPdfLoader(true);

        // Enforce same-origin relative URL to prevent cross-origin CORS & auth cookie drops
        let fetchUrl = url;
        try {
            if (fetchUrl && (fetchUrl.startsWith('http://') || fetchUrl.startsWith('https://'))) {
                const parsed = new URL(fetchUrl);
                fetchUrl = parsed.pathname + parsed.search;
            }
        } catch (e) {
            console.warn('URL parse fallback:', e);
        }

        const loadingTask = pdfjsLib.getDocument({
            url: fetchUrl,
            withCredentials: true
        });

        loadingTask.promise.then((pdf) => {
            pdfDoc = pdf;
            totalSlides = pdfDoc.numPages;
            document.getElementById('total-slides').innerText = totalSlides;

            document.getElementById('pdf-container').classList.remove('hidden');
            document.getElementById('pptx-container').classList.add('hidden');
            document.getElementById('fallback-slide').classList.add('hidden');

            renderPdfPage(initialPage);
            showPdfLoader(false);
        }).catch((err) => {
            console.warn('Gagal memuat visual PDF, mencoba fallback slide deck:', err);
            showPdfLoader(false);
            if (presentationType === 'pptx' || presentationType === 'ppt') {
                document.getElementById('pdf-container').classList.add('hidden');
                document.getElementById('pptx-container').classList.remove('hidden');
                document.getElementById('fallback-slide').classList.add('hidden');
                renderPptxSlide(currentSlide);
            } else {
                document.getElementById('pdf-container').classList.add('hidden');
                document.getElementById('pptx-container').classList.add('hidden');
                document.getElementById('fallback-slide').classList.remove('hidden');
            }
        });
    }

    function renderPdfPage(num) {
        if (!pdfDoc) return;
        pageRendering = true;
        showPdfLoader(true);

        pdfDoc.getPage(num).then((page) => {
            const container = document.getElementById('pdf-container');
            const containerWidth = (container ? container.clientWidth : 0) || 700;
            const containerHeight = (container ? container.clientHeight : 0) || 480;
            const unscaledViewport = page.getViewport({ scale: 1 });
            const scale = Math.min((containerWidth - 24) / unscaledViewport.width, (containerHeight - 24) / unscaledViewport.height);
            const actualScale = scale > 0.3 ? scale : 0.8;
            const viewport = page.getViewport({ scale: actualScale });

            const dpr = window.devicePixelRatio || 1;
            canvas.width = Math.floor(viewport.width * dpr);
            canvas.height = Math.floor(viewport.height * dpr);
            canvas.style.width = Math.floor(viewport.width) + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';

            const renderContext = {
                canvasContext: ctx,
                transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null,
                viewport: viewport
            };

            const renderTask = page.render(renderContext);
            renderTask.promise.then(() => {
                pageRendering = false;
                showPdfLoader(false);
                if (pageNumPending !== null) {
                    renderPdfPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        });

        currentSlide = num;
        document.getElementById('current-slide').innerText = num;
    }

    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPdfPage(num);
        }
    }

    function showPdfLoader(show) {
        const loader = document.getElementById('pdf-loader');
        if (loader) {
            if (show) loader.classList.remove('hidden');
            else loader.classList.add('hidden');
        }
    }

    function renderFallbackSlide(num) {
        document.getElementById('current-slide').innerText = num;
        document.getElementById('total-slides').innerText = '4';
        const numPreview = document.getElementById('slide-num-preview');
        const titleEl = document.getElementById('slide-title');
        if (numPreview) numPreview.innerText = 'SLIDE ' + num;
        if (titleEl && fallbackTitles[num - 1]) titleEl.innerText = fallbackTitles[num - 1];
    }

    // Host: Next Slide
    function nextSlide() {
        if (!IS_HOST) return;
        const max = pdfDoc ? pdfDoc.numPages : (totalSlides || 1);
        if (currentSlide < max) {
            currentSlide++;
            syncSlideToServer(currentSlide, max);
        }
    }

    // Host: Prev Slide
    function prevSlide() {
        if (!IS_HOST) return;
        if (currentSlide > 1) {
            currentSlide--;
            const max = pdfDoc ? pdfDoc.numPages : (totalSlides || 1);
            syncSlideToServer(currentSlide, max);
        }
    }

    function syncSlideToServer(slideNum, totalNum) {
        if (pdfDoc) {
            queueRenderPage(slideNum);
        } else if (presentationType === 'pptx' || presentationType === 'ppt') {
            renderPptxSlide(slideNum);
        } else {
            renderFallbackSlide(slideNum);
        }

        fetch(`/dosen/classroom/${ROOM_CODE}/slide`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                current_slide: slideNum,
                total_slides: totalNum
            })
        }).catch(err => console.error('Slide update error:', err));
    }

    // Host: Upload presentation (PDF, PPT, PPTX)
    function uploadPresentationFile(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'ppt', 'pptx'].includes(ext)) {
            alert('File yang diunggah harus bertipe PDF atau PowerPoint (.ppt, .pptx).');
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('presentation', file);
        formData.append('_token', CSRF_TOKEN);

        showPdfLoader(true);
        fetch(`/dosen/classroom/${ROOM_CODE}/presentation`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: formData
        })
        .then(async (res) => {
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                const errorMsg = (data && data.message) ? data.message : `Gagal mengunggah file (HTTP ${res.status})`;
                throw new Error(errorMsg);
            }
            return data;
        })
        .then(data => {
            showPdfLoader(false);
            input.value = '';
            if (data && data.success) {
                currentPdfUrl = data.presentation_url;
                presentationType = data.presentation_type || ext;
                presentationData = data.presentation_data || null;
                totalSlides = data.total_slides || 1;
                document.getElementById('presentation-name').innerText = data.presentation_name;
                updatePresentationBadge(presentationType);
                renderPresentationView();
                showToast(`Materi presentasi ${presentationType.toUpperCase()} berhasil diunggah!`);
            } else {
                alert((data && data.message) || 'Gagal mengunggah presentasi.');
            }
        })
        .catch(err => {
            console.error('Upload error:', err);
            showPdfLoader(false);
            input.value = '';
            alert(err.message || 'Terjadi kesalahan saat mengunggah file materi.');
        });
    }

    // ===============================================================
    // SINGLE PURE JITSI MEET VIDEO CONFERENCING ENGINE
    // ===============================================================

    function initJitsiMeet() {
        const container = document.getElementById('jitsi-meet-container');
        if (!container || isJitsiInitialized) return;

        if (typeof JitsiMeetExternalAPI === 'undefined') {
            if (!window._jitsiRetryCount) window._jitsiRetryCount = 0;
            window._jitsiRetryCount++;
            if (window._jitsiRetryCount < 8) {
                setTimeout(initJitsiMeet, 800);
                return;
            }

            const loader = document.getElementById('jitsi-loader');
            if (loader) {
                loader.innerHTML = `
                    <div class="text-center p-6 text-white space-y-2">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-800 flex items-center justify-center text-amber-400 text-xl border border-amber-500/30">
                            <i class="fa-solid fa-video-slash"></i>
                        </div>
                        <p class="text-xs font-bold">API Jitsi Meet Tidak Terjangkau</p>
                        <p class="text-[11px] text-slate-400 max-w-xs mx-auto">Pastikan koneksi internet aktif untuk memuat server konferensi meet.jit.si.</p>
                        <div class="pt-2">
                            <button onclick="window._jitsiRetryCount=0; initJitsiMeet();" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition">
                                <i class="fa-solid fa-arrows-rotate mr-1"></i> Coba Hubungkan Lagi
                            </button>
                        </div>
                    </div>
                `;
            }
            return;
        }

        isJitsiInitialized = true;
        const domain = "{{ config('services.jitsi.domain', 'meet.jit.si') }}";
        const cleanRoom = 'unuja-3in1-' + "{{ Str::slug($classroom->room_code ?? $room_code) }}";

        const options = {
            roomName: cleanRoom,
            width: '100%',
            height: '100%',
            parentNode: container,
            userInfo: {
                displayName: "{{ $user->name }} ({{ $isHost ? 'Dosen' : 'Mahasiswa' }})"
            },
            configOverwrite: {
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                prejoinPageEnabled: false,
                prejoinConfig: {
                    enabled: false
                },
                disableDeepLinking: true,
                enableWelcomePage: false,
                enableClosePage: false,
                toolbarButtons: [
                    'microphone', 'camera', 'desktop', 'fullscreen',
                    'chat', 'raisehand', 'videoquality', 'tileview',
                    'settings', 'mute-everyone'
                ]
            },
            interfaceConfigOverwrite: {
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                DEFAULT_BACKGROUND: '#020617'
            }
        };

        try {
            jitsiApi = new JitsiMeetExternalAPI(domain, options);

            setTimeout(() => {
                const loader = document.getElementById('jitsi-loader');
                if (loader) loader.classList.add('hidden');
            }, 2500);

            jitsiApi.addEventListener('videoConferenceJoined', () => {
                const loader = document.getElementById('jitsi-loader');
                if (loader) loader.classList.add('hidden');
                showToast('Terkoneksi ke Video Conference Jitsi Meet.');
            });

            jitsiApi.addEventListener('audioMuteStatusChanged', (e) => {
                isMyMicActive = !e.muted;
                updateMicButtonUI(!e.muted);
            });

            jitsiApi.addEventListener('videoMuteStatusChanged', (e) => {
                isCamActive = !e.muted;
                updateCamButtonUI(!e.muted);
            });

            jitsiApi.addEventListener('screenSharingStatusChanged', (e) => {
                isScreenSharing = e.on;
                updateShareScreenUI(e.on);
            });

        } catch (err) {
            console.error('Error initializing Jitsi Meet:', err);
            isJitsiInitialized = false;
        }
    }

    // Switch between Presentation, Split 50:50, and Full Video (Tatap Muka)
    function setStageMode(mode) {
        stageMode = mode;
        const presSection = document.getElementById('presentation-stage-section');
        const jitsiSection = document.getElementById('jitsi-video-section');
        const btnPres = document.getElementById('btn-mode-presentation');
        const btnSplit = document.getElementById('btn-mode-split');
        const btnVideo = document.getElementById('btn-mode-video');

        if (!presSection || !jitsiSection) return;

        // Reset button active styles
        [btnPres, btnSplit, btnVideo].forEach(btn => {
            if (!btn) return;
            btn.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center space-x-1';
        });

        if (mode === 'presentation') {
            if (btnPres) btnPres.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white transition flex items-center space-x-1 shadow-xs';
            presSection.className = 'w-full h-full flex flex-col items-center justify-center relative transition-all duration-300';
            presSection.classList.remove('hidden');
            // Keep Jitsi section alive off-screen so meeting audio/video is not disconnected
            jitsiSection.className = 'absolute -left-[9999px] w-1 h-1 overflow-hidden opacity-0 pointer-events-none';
        } else if (mode === 'split') {
            if (btnSplit) btnSplit.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white transition flex items-center space-x-1 shadow-xs';
            presSection.className = 'w-full lg:w-1/2 h-full flex flex-col items-center justify-center relative transition-all duration-300';
            presSection.classList.remove('hidden');
            jitsiSection.className = 'w-full lg:w-1/2 h-full flex flex-col relative rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 transition-all duration-300';
        } else if (mode === 'video') {
            if (btnVideo) btnVideo.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white transition flex items-center space-x-1 shadow-xs';
            presSection.classList.add('hidden');
            jitsiSection.className = 'w-full h-full flex flex-col relative rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 transition-all duration-300';
        }

        if (mode === 'presentation' || mode === 'split') {
            if (pdfDoc) {
                setTimeout(() => queueRenderPage(currentSlide), 200);
            } else if (presentationType === 'pptx' || presentationType === 'ppt') {
                renderPptxSlide(currentSlide);
            }
        }
    }

    // Host: Toggle Mute All
    function toggleMuteAll() {
        if (!IS_HOST) return;
        fetch(`/dosen/classroom/${ROOM_CODE}/mute-all`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                isAllMicMuted = data.is_all_mic_muted;
                updateMuteAllButton(data.is_all_mic_muted);
                if (jitsiApi && data.is_all_mic_muted) {
                    try {
                        jitsiApi.executeCommand('muteEveryone');
                    } catch (e) {
                        console.warn('Jitsi muteEveryone command:', e);
                    }
                }
                showToast(data.message);
            }
        });
    }

    function updateMuteAllButton(muted) {
        const btn = document.getElementById('mute-all-btn');
        const text = document.getElementById('mute-all-text');
        const icon = document.getElementById('mute-all-icon');
        if (!btn) return;

        if (muted) {
            text.innerText = 'Unmute Semua';
            btn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100 transition flex items-center space-x-1.5 shadow-xs';
            icon.className = 'fa-solid fa-microphone text-rose-600';
        } else {
            text.innerText = 'Mute All Peserta';
            btn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition flex items-center space-x-1.5 shadow-xs';
            icon.className = 'fa-solid fa-microphone-slash text-amber-600';
        }
    }

    // Participant: Toggle Raise Hand
    function toggleMyRaiseHand() {
        if (jitsiApi) {
            try {
                jitsiApi.executeCommand('toggleRaiseHand');
            } catch (e) {}
        }

        fetch(`/classroom/${ROOM_CODE}/raise-hand`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                isMyHandRaised = data.is_hand_raised;
                const handBtn = document.getElementById('raise-hand-btn');
                const handText = document.getElementById('raise-hand-text');
                if (isMyHandRaised) {
                    handText.innerText = 'Turunkan Tangan';
                    handBtn.className = 'px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold shadow-lg ring-2 ring-white flex items-center space-x-2';
                    showToast('Anda telah mengangkat tangan.');
                } else {
                    handText.innerText = 'Raise Hand (Angkat Tangan)';
                    handBtn.className = 'px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition shadow-lg flex items-center space-x-2';
                }
            }
        });
    }

    // Central Toolbar: Toggle Microphone (Jitsi Meet Integrated)
    function toggleMyMic() {
        if (isAllMicMuted && !IS_HOST) {
            showToast('Mikrofon dikunci oleh Host Dosen.', 'rose');
            return;
        }

        if (jitsiApi) {
            try {
                jitsiApi.executeCommand('toggleAudio');
            } catch (e) {
                console.warn('Jitsi audio toggle command error:', e);
            }
        } else {
            isMyMicActive = !isMyMicActive;
            updateMicButtonUI(isMyMicActive);
        }

        // Sync state to backend
        fetch(`/classroom/${ROOM_CODE}/mic`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message, 'rose');
            }
        })
        .catch(err => {
            console.error('Mic toggle backend sync error:', err);
        });
    }

    function updateMicButtonUI(active) {
        isMyMicActive = active;
        const micIcon = document.getElementById('mic-icon');
        const micText = document.getElementById('mic-text');
        const micBtn = document.getElementById('mic-btn');
        if (!micBtn) return;

        if (active) {
            micBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2';
            if (micIcon) micIcon.className = 'fa-solid fa-microphone text-emerald-400';
            if (micText) micText.innerText = 'Mic Aktif';
        } else {
            micBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-rose-900/60 border border-rose-600 text-white transition flex items-center space-x-2';
            if (micIcon) micIcon.className = 'fa-solid fa-microphone-slash text-rose-400';
            if (micText) micText.innerText = 'Mic Dimatikan';
        }
    }

    // Central Toolbar: Toggle Camera (Jitsi Meet Integrated)
    function toggleCam() {
        if (jitsiApi) {
            try {
                jitsiApi.executeCommand('toggleVideo');
            } catch (e) {
                console.warn('Jitsi video toggle command error:', e);
            }
        } else {
            isCamActive = !isCamActive;
            updateCamButtonUI(isCamActive);
        }
    }

    function updateCamButtonUI(active) {
        isCamActive = active;
        const camBtn = document.getElementById('cam-btn');
        const camIcon = document.getElementById('cam-icon');
        const camText = document.getElementById('cam-text');
        if (!camBtn) return;

        if (active) {
            camBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2';
            if (camIcon) camIcon.className = 'fa-solid fa-video text-emerald-400';
            if (camText) camText.innerText = 'Kamera Aktif';
        } else {
            camBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-rose-900/60 border border-rose-600 text-white transition flex items-center space-x-2';
            if (camIcon) camIcon.className = 'fa-solid fa-video-slash text-rose-400';
            if (camText) camText.innerText = 'Kamera Off';
        }
    }

    // Central Toolbar: Toggle Share Screen (Jitsi Meet Integrated)
    function toggleShareScreen() {
        if (jitsiApi) {
            try {
                jitsiApi.executeCommand('toggleShareScreen');
            } catch (e) {
                console.warn('Jitsi share screen command error:', e);
            }
        }
    }

    function updateShareScreenUI(sharing) {
        isScreenSharing = sharing;
        const btn = document.getElementById('share-screen-btn');
        const icon = document.getElementById('share-screen-icon');
        const text = document.getElementById('share-screen-text');
        if (!btn) return;

        if (sharing) {
            btn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center space-x-2 ring-2 ring-blue-400 shadow-md';
            if (icon) icon.className = 'fa-solid fa-desktop text-white animate-pulse';
            if (text) text.innerText = 'Sharing Aktif';
        } else {
            btn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2';
            if (icon) icon.className = 'fa-solid fa-desktop';
            if (text) text.innerText = 'Share Screen';
        }
    }

    // Participant: Toggle Sync Lock
    function toggleSyncLock() {
        isSyncLocked = !isSyncLocked;
        const btn = document.getElementById('sync-lock-btn');
        const icon = document.getElementById('sync-lock-icon');
        const text = document.getElementById('sync-lock-text');
        const navGroup = document.getElementById('student-nav-group');
        const resyncBtn = document.getElementById('resync-btn');

        if (isSyncLocked) {
            btn.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-900/60 text-emerald-300 border border-emerald-600 hover:bg-emerald-800/60 transition flex items-center space-x-1.5 shadow-xs';
            icon.className = 'fa-solid fa-lock text-emerald-400';
            text.innerText = 'Sync Lock: Aktif';
            if (navGroup) navGroup.classList.add('hidden');
            if (resyncBtn) resyncBtn.classList.add('hidden');
            resyncToHost();
            showToast('Sync Lock Aktif: Slide terkunci mengikuti presentasi Dosen.');
        } else {
            btn.className = 'px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-900/60 text-amber-300 border border-amber-600 hover:bg-amber-800/60 transition flex items-center space-x-1.5 shadow-xs';
            icon.className = 'fa-solid fa-lock-open text-amber-400';
            text.innerText = 'Sync Lock: Bebas';
            if (navGroup) navGroup.classList.remove('hidden');
            if (resyncBtn && currentSlide !== hostCurrentSlide) resyncBtn.classList.remove('hidden');
            showToast('Sync Lock Nonaktif: Anda bebas menjelajahi slide secara mandiri.', 'amber');
        }
    }

    function resyncToHost() {
        currentSlide = hostCurrentSlide;
        if (pdfDoc) {
            queueRenderPage(currentSlide);
        } else if (presentationType === 'pptx' || presentationType === 'ppt') {
            renderPptxSlide(currentSlide);
        } else {
            renderFallbackSlide(currentSlide);
        }
        const resyncBtn = document.getElementById('resync-btn');
        if (resyncBtn) resyncBtn.classList.add('hidden');
        showToast('Tampilan berhasil disinkronkan kembali ke slide Dosen.');
    }

    function studentNextSlide() {
        if (isSyncLocked) return;
        const max = pdfDoc ? pdfDoc.numPages : (totalSlides || 1);
        if (currentSlide < max) {
            currentSlide++;
            if (pdfDoc) {
                queueRenderPage(currentSlide);
            } else if (presentationType === 'pptx' || presentationType === 'ppt') {
                renderPptxSlide(currentSlide);
            } else {
                renderFallbackSlide(currentSlide);
            }
            const resyncBtn = document.getElementById('resync-btn');
            if (resyncBtn && currentSlide !== hostCurrentSlide) resyncBtn.classList.remove('hidden');
        }
    }

    function studentPrevSlide() {
        if (isSyncLocked) return;
        if (currentSlide > 1) {
            currentSlide--;
            if (pdfDoc) {
                queueRenderPage(currentSlide);
            } else if (presentationType === 'pptx' || presentationType === 'ppt') {
                renderPptxSlide(currentSlide);
            } else {
                renderFallbackSlide(currentSlide);
            }
            const resyncBtn = document.getElementById('resync-btn');
            if (resyncBtn && currentSlide !== hostCurrentSlide) resyncBtn.classList.remove('hidden');
        }
    }

    // Real-Time Polling Loop for State Sync
    function pollRoomState() {
        fetch(`/classroom/${ROOM_CODE}/state`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // 1. Sync Presentation if changed
            if (data.presentation_url && (data.presentation_url !== currentPdfUrl || (data.presentation_type && data.presentation_type !== presentationType))) {
                currentPdfUrl = data.presentation_url;
                presentationType = data.presentation_type || 'pdf';
                presentationData = data.presentation_data || null;
                document.getElementById('presentation-name').innerText = data.presentation_name || 'Materi_Presentasi';
                updatePresentationBadge(presentationType);
                renderPresentationView();
            }

            // 2. Sync Slide page for Participant (Host controls it)
            if (!IS_HOST) {
                hostCurrentSlide = data.current_slide;
                const hostSlideNum = document.getElementById('host-slide-num');
                if (hostSlideNum) hostSlideNum.innerText = hostCurrentSlide;

                if (isSyncLocked) {
                    if (data.current_slide !== currentSlide) {
                        currentSlide = data.current_slide;
                        if (pdfDoc) {
                            queueRenderPage(currentSlide);
                        } else if (presentationType === 'pptx' || presentationType === 'ppt') {
                            renderPptxSlide(currentSlide);
                        } else {
                            renderFallbackSlide(currentSlide);
                        }
                    }
                } else {
                    const resyncBtn = document.getElementById('resync-btn');
                    if (resyncBtn) {
                        if (currentSlide !== hostCurrentSlide) {
                            resyncBtn.classList.remove('hidden');
                        } else {
                            resyncBtn.classList.add('hidden');
                        }
                    }
                }
            }

            document.getElementById('current-slide').innerText = data.current_slide;
            document.getElementById('total-slides').innerText = data.total_slides;

            // 3. Mute All State banner & button
            isAllMicMuted = data.is_all_mic_muted;
            const micBanner = document.getElementById('mic-locked-banner');
            if (micBanner) {
                if (isAllMicMuted && !IS_HOST) {
                    micBanner.classList.remove('hidden');
                    if (isMyMicActive) {
                        isMyMicActive = false;
                        if (localStream) {
                            localStream.getAudioTracks().forEach(track => { track.enabled = false; });
                        }
                        const streamMicIcon = document.getElementById('webrtc-stream-mic-icon');
                        if (streamMicIcon) streamMicIcon.className = 'fa-solid fa-microphone-slash text-rose-400';
                        updateMicButtonUI(false);
                        if (jitsiApi && currentVideoEngine === 'jitsi') {
                            try {
                                jitsiApi.isAudioMuted().then(muted => {
                                    if (!muted) jitsiApi.executeCommand('toggleAudio');
                                });
                            } catch (e) {}
                        }
                    }
                } else {
                    micBanner.classList.add('hidden');
                }
            }
            if (IS_HOST) {
                updateMuteAllButton(isAllMicMuted);
            }

            // 4. Raise Hand notification banner for Host
            const raiseHandBanner = document.getElementById('raise-hand-notification');
            if (raiseHandBanner) {
                if (data.has_hand_raised) {
                    raiseHandBanner.classList.remove('hidden');
                } else {
                    raiseHandBanner.classList.add('hidden');
                }
            }

            // 5. Update Participants list
            renderParticipants(data.participants);

            // 6. Update Chats
            renderChats(data.chats);
        })
        .catch(err => console.error('Poll error:', err));
    }

    function renderParticipants(participants) {
        if (!participants) return;
        document.getElementById('participants-count').innerText = participants.length;
        const webrtcBadge = document.getElementById('webrtc-participant-badge');
        if (webrtcBadge) {
            webrtcBadge.innerText = participants.length + ' Di Ruang Tatap Muka';
        }

        const host = participants.find(p => p.role === 'host' || p.role === 'dosen');
        if (host) {
            const hostNameEl = document.getElementById('host-name');
            if (hostNameEl) hostNameEl.innerText = host.name;
        }

        const students = participants.filter(p => p.role !== 'host' && p.role !== 'dosen');
        document.getElementById('students-count').innerText = students.length;

        const listContainer = document.getElementById('students-list');
        if (!listContainer) return;

        if (students.length === 0) {
            listContainer.innerHTML = '<p class="text-xs text-slate-400 text-center py-3 italic">Belum ada mahasiswa bergabung.</p>';
            return;
        }

        listContainer.innerHTML = students.map(s => {
            const isMe = s.user_id === CURRENT_USER_ID;
            const initials = s.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            return `
                <div class="p-2.5 rounded-xl ${isMe ? 'bg-blue-50/70 border border-blue-200' : 'bg-slate-50 border border-slate-100'} flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                            ${initials}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">${s.name} ${isMe ? '(Anda)' : ''}</p>
                            <span class="text-[10px] text-slate-500">NIM: ${s.username} &bull; ${s.prodi}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        ${s.is_hand_raised ? '<span class="text-amber-500 text-xs animate-bounce" title="Mengangkat Tangan"><i class="fa-solid fa-hand"></i></span>' : ''}
                        ${s.is_mic_muted
                            ? '<i class="fa-solid fa-microphone-slash text-rose-400 text-xs" title="Mic Muted"></i>'
                            : '<i class="fa-solid fa-microphone text-emerald-500 text-xs" title="Mic Aktif"></i>'}
                    </div>
                </div>
            `;
        }).join('');
    }

    let lastChatCount = 0;
    function renderChats(chats) {
        if (!chats) return;
        const chatBox = document.getElementById('chat-messages');
        if (!chatBox) return;

        if (chats.length !== lastChatCount) {
            lastChatCount = chats.length;
            chatBox.innerHTML = chats.map(c => {
                const isMe = c.user_id === CURRENT_USER_ID;
                const isDosenRole = c.role === 'dosen' || c.role === 'host';
                return `
                    <div class="${isMe ? 'bg-blue-50 ml-auto border border-blue-100' : 'bg-white border border-slate-200'} p-2.5 rounded-xl max-w-[85%] space-y-1 shadow-2xs">
                        <div class="flex justify-between items-center text-[10px] font-bold ${isMe ? 'text-blue-700' : (isDosenRole ? 'text-purple-700' : 'text-slate-700')}">
                            <span>${c.user_name} ${isMe ? '(Anda)' : ''}</span>
                            <span class="text-slate-400 font-normal ml-2">${c.created_at}</span>
                        </div>
                        <p class="text-slate-800 break-words">${escapeHtml(c.message)}</p>
                    </div>
                `;
            }).join('');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    function handleSendChat(event) {
        event.preventDefault();
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        fetch(`/classroom/${ROOM_CODE}/chat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                pollRoomState();
            }
        });
    }

    function switchTab(tab) {
        if (tab === 'peserta') {
            document.getElementById('content-peserta').classList.remove('hidden');
            document.getElementById('content-chat').classList.add('hidden');
            document.getElementById('tab-peserta').className = 'flex-1 py-3 text-center text-xs font-bold text-blue-600 border-b-2 border-blue-600 bg-white';
            document.getElementById('tab-chat').className = 'flex-1 py-3 text-center text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-50';
        } else {
            document.getElementById('content-peserta').classList.add('hidden');
            document.getElementById('content-chat').classList.remove('hidden');
            document.getElementById('tab-chat').className = 'flex-1 py-3 text-center text-xs font-bold text-blue-600 border-b-2 border-blue-600 bg-white';
            document.getElementById('tab-peserta').className = 'flex-1 py-3 text-center text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-50';
        }
    }

    function showToast(message, color = 'emerald') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `p-3 rounded-xl shadow-lg text-xs font-semibold text-white ${color === 'rose' ? 'bg-rose-600' : 'bg-emerald-600'} transition-all transform duration-300`;
        toast.innerText = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 3500);
    }

    function escapeHtml(str) {
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag));
    }

    // Resizable Panel Logic (Drag Splitter)
    (function initResizablePanels() {
        const container = document.getElementById('classroom-split-container');
        const stagePanel = document.getElementById('stage-panel');
        const sidebarPanel = document.getElementById('sidebar-panel');
        const splitter = document.getElementById('drag-splitter');

        if (!container || !stagePanel || !sidebarPanel || !splitter) return;

        const DEFAULT_WIDTH = 73; // 73%
        const MIN_WIDTH = 45; // 45%
        const MAX_WIDTH = 85; // 85%

        // Restore saved preference if exists
        const savedWidth = localStorage.getItem('3in1_panel_width');
        if (savedWidth && window.innerWidth >= 1024) {
            applyWidth(parseFloat(savedWidth));
        }

        let isDragging = false;

        splitter.addEventListener('mousedown', startDragging);
        splitter.addEventListener('touchstart', startDragging, { passive: false });

        function startDragging(e) {
            if (window.innerWidth < 1024) return;
            isDragging = true;
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'col-resize';
            splitter.classList.add('bg-purple-200/50');

            window.addEventListener('mousemove', onDragging);
            window.addEventListener('touchmove', onDragging, { passive: false });
            window.addEventListener('mouseup', stopDragging);
            window.addEventListener('touchend', stopDragging);
        }

        function onDragging(e) {
            if (!isDragging) return;
            e.preventDefault();

            const containerRect = container.getBoundingClientRect();
            const clientX = e.clientX || (e.touches && e.touches[0].clientX);
            if (!clientX) return;

            const pointerPos = clientX - containerRect.left;
            let percent = (pointerPos / containerRect.width) * 100;
            percent = Math.max(MIN_WIDTH, Math.min(MAX_WIDTH, percent));

            applyWidth(percent);
        }

        function stopDragging() {
            if (!isDragging) return;
            isDragging = false;
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            splitter.classList.remove('bg-purple-200/50');

            window.removeEventListener('mousemove', onDragging);
            window.removeEventListener('touchmove', onDragging);
            window.removeEventListener('mouseup', stopDragging);
            window.removeEventListener('touchend', stopDragging);

            const currentWidthPercent = parseFloat(stagePanel.style.width);
            if (currentWidthPercent) {
                localStorage.setItem('3in1_panel_width', currentWidthPercent);
            }

            // Re-render PDF canvas if needed to match new container dimensions
            if (typeof pdfDoc !== 'undefined' && pdfDoc) {
                queueRenderPage(currentSlide);
            }
        }

        function applyWidth(percent) {
            stagePanel.style.width = percent + '%';
            sidebarPanel.style.width = (100 - percent) + '%';
            sidebarPanel.style.flex = 'none';
        }

        // Double click splitter to reset default
        splitter.addEventListener('dblclick', () => {
            applyWidth(DEFAULT_WIDTH);
            localStorage.removeItem('3in1_panel_width');
            if (typeof pdfDoc !== 'undefined' && pdfDoc) {
                queueRenderPage(currentSlide);
            }
            if (typeof showToast === 'function') {
                showToast('Ukuran panel di-reset ke default (73% / 27%).');
            }
        });
    })();
</script>
@endpush
