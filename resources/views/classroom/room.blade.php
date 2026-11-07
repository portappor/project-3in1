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

    <!-- Main Virtual Classroom Stage (Split Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        <!-- Stage Area: Presentation & Slide Synchronizer (Col 3) -->
        <div class="lg:col-span-3 bg-slate-900 rounded-3xl overflow-hidden shadow-xl border border-slate-800 flex flex-col h-[600px]">

            <!-- Slide Stage Header -->
            <div class="bg-slate-800/90 px-4 py-2.5 border-b border-slate-700 flex justify-between items-center text-xs text-slate-300">
                <div class="flex items-center space-x-2 truncate max-w-md">
                    <i class="fa-solid fa-file-pdf text-rose-400"></i>
                    <span id="presentation-name" class="font-medium truncate">
                        {{ $classroom->presentation_name ?? 'Materi_Presentasi_Default.pdf' }}
                    </span>
                    @if($classroom->presentation_path)
                        <span class="text-[10px] bg-emerald-900/60 text-emerald-300 px-1.5 py-0.5 rounded border border-emerald-700">PDF Aktif</span>
                    @endif
                </div>
                <div class="flex items-center space-x-2 font-mono">
                    <span class="text-slate-400">Slide:</span>
                    <span id="current-slide" class="font-bold text-white px-2 py-0.5 bg-slate-700 rounded">{{ $classroom->current_slide ?? 1 }}</span>
                    <span class="text-slate-500">/ <span id="total-slides">{{ $classroom->total_slides ?? 1 }}</span></span>
                </div>
            </div>

            <!-- Slide Canvas Viewer Stage -->
            <div class="flex-1 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-4 flex flex-col items-center justify-center text-center relative overflow-hidden">

                <!-- PDF.js Canvas Container -->
                <div id="pdf-container" class="{{ $classroom->presentation_path ? '' : 'hidden' }} w-full h-full flex items-center justify-center relative">
                    <canvas id="pdf-render-canvas" class="max-h-[480px] max-w-full rounded-xl shadow-2xl object-contain border border-slate-700"></canvas>
                    <div id="pdf-loader" class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex items-center justify-center text-white space-x-2">
                        <i class="fa-solid fa-spinner fa-spin text-xl text-blue-400"></i>
                        <span class="text-xs">Memuat Slide PDF...</span>
                    </div>
                </div>

                <!-- Fallback Interactive Slide when no PDF is uploaded yet -->
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
                                    <span>Unggah Dokumen Presentasi PDF</span>
                                    <input type="file" accept="application/pdf" class="hidden" onchange="uploadPresentationFile(this)">
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

                <!-- Raise Hand Alert Banner for Host -->
                <div id="raise-hand-notification" class="hidden absolute top-4 right-4 bg-amber-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-xl flex items-center space-x-2 animate-bounce">
                    <i class="fa-solid fa-hand text-base"></i>
                    <span id="raise-hand-notif-text">Ada Mahasiswa yang Mengangkat Tangan!</span>
                </div>

                <!-- Host Mic Mute Banner for Participant -->
                <div id="mic-locked-banner" class="{{ ($classroom->is_all_mic_muted ?? false) ? '' : 'hidden' }} absolute bottom-3 left-4 right-4 bg-rose-900/90 text-rose-200 text-xs px-3 py-1.5 rounded-xl border border-rose-700 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-microphone-slash"></i>
                    <span>Host mengaktifkan MUTE ALL. Mikrofon seluruh peserta dinonaktifkan sementara.</span>
                </div>
            </div>

            <!-- Bottom Control Bar -->
            <div class="bg-slate-800 px-6 py-3 border-t border-slate-700 flex flex-wrap justify-between items-center gap-3">

                <!-- Media Toggles (Mic / Cam) -->
                <div class="flex items-center space-x-2">
                    <button type="button" id="mic-btn" onclick="toggleMyMic()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2">
                        <i id="mic-icon" class="fa-solid fa-microphone"></i>
                        <span id="mic-text">Mic Aktif</span>
                    </button>
                    <button type="button" id="cam-btn" onclick="toggleCam()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2">
                        <i id="cam-icon" class="fa-solid fa-video"></i>
                        <span id="cam-text">Kamera</span>
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
                        <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-semibold transition inline-flex items-center space-x-1.5 border border-slate-600">
                            <i class="fa-solid fa-upload"></i>
                            <span>Upload PDF</span>
                            <input type="file" accept="application/pdf" class="hidden" onchange="uploadPresentationFile(this)">
                        </label>
                    </div>
                @else
                    <!-- Participant Interactive Button (Raise Hand) -->
                    <div>
                        <button type="button" id="raise-hand-btn" onclick="toggleMyRaiseHand()"
                            class="px-4 py-2 rounded-xl {{ ($attendance->is_hand_raised ?? false) ? 'bg-amber-600 ring-2 ring-white' : 'bg-amber-500 hover:bg-amber-600' }} text-white text-xs font-bold transition shadow-lg shadow-amber-500/25 flex items-center space-x-2">
                            <i class="fa-solid fa-hand"></i>
                            <span id="raise-hand-text">{{ ($attendance->is_hand_raised ?? false) ? 'Turunkan Tangan' : 'Raise Hand (Angkat Tangan)' }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Sidebar Area: Peserta & Chat (Col 1) -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[600px] overflow-hidden">

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
<!-- PDF.js CDN library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    // State Variables
    const ROOM_CODE = "{{ $classroom->room_code ?? $room_code }}";
    const CURRENT_USER_ID = {{ $user->id }};
    const IS_HOST = {{ $isHost ? 'true' : 'false' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";

    let currentPdfUrl = "{{ $classroom->presentation_url ?? '' }}";
    let currentSlide = {{ (int) ($classroom->current_slide ?? 1) }};
    let totalSlides = {{ (int) ($classroom->total_slides ?? 1) }};
    let isAllMicMuted = {{ ($classroom->is_all_mic_muted ?? false) ? 'true' : 'false' }};
    let isMyMicActive = true;
    let isMyHandRaised = {{ ($attendance->is_hand_raised ?? false) ? 'true' : 'false' }};

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

    // Initialize PDF or Fallback view
    document.addEventListener('DOMContentLoaded', () => {
        if (currentPdfUrl) {
            loadPdfDocument(currentPdfUrl, currentSlide);
        } else {
            renderFallbackSlide(currentSlide);
        }

        // Start Periodic Synchronization Loop
        setInterval(pollRoomState, 2500);
        pollRoomState();
    });

    // Load PDF via PDF.js
    function loadPdfDocument(url, initialPage = 1) {
        showPdfLoader(true);
        pdfjsLib.getDocument(url).promise.then((pdf) => {
            pdfDoc = pdf;
            totalSlides = pdfDoc.numPages;
            document.getElementById('total-slides').innerText = totalSlides;

            document.getElementById('pdf-container').classList.remove('hidden');
            document.getElementById('fallback-slide').classList.add('hidden');

            renderPdfPage(initialPage);
            showPdfLoader(false);
        }).catch((err) => {
            console.error('Error loading PDF:', err);
            showPdfLoader(false);
            document.getElementById('pdf-container').classList.add('hidden');
            document.getElementById('fallback-slide').classList.remove('hidden');
        });
    }

    function renderPdfPage(num) {
        if (!pdfDoc) return;
        pageRendering = true;
        showPdfLoader(true);

        pdfDoc.getPage(num).then((page) => {
            const containerWidth = document.getElementById('pdf-container').clientWidth || 700;
            const unscaledViewport = page.getViewport({ scale: 1 });
            const scale = Math.min((containerWidth - 30) / unscaledViewport.width, 480 / unscaledViewport.height);
            const viewport = page.getViewport({ scale: scale > 0.4 ? scale : 0.8 });

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
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
        const max = pdfDoc ? pdfDoc.numPages : 4;
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
            const max = pdfDoc ? pdfDoc.numPages : 4;
            syncSlideToServer(currentSlide, max);
        }
    }

    function syncSlideToServer(slideNum, totalNum) {
        if (pdfDoc) {
            queueRenderPage(slideNum);
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

    // Host: Upload PDF presentation
    function uploadPresentationFile(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        if (file.type !== 'application/pdf') {
            alert('File yang diunggah harus bertipe PDF.');
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
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentPdfUrl = data.presentation_url;
                document.getElementById('presentation-name').innerText = data.presentation_name;
                loadPdfDocument(data.presentation_url, 1);
                showToast('Materi presentasi PDF berhasil diunggah!');
            } else {
                alert(data.message || 'Gagal mengunggah presentasi.');
                showPdfLoader(false);
            }
        })
        .catch(err => {
            console.error('Upload error:', err);
            showPdfLoader(false);
            alert('Terjadi kesalahan saat mengunggah file PDF.');
        });
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

    // Toggle Mic
    function toggleMyMic() {
        if (isAllMicMuted && !IS_HOST) {
            showToast('Mikrofon dikunci oleh Host Dosen.', 'rose');
            return;
        }

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
            if (data.success) {
                isMyMicActive = !data.is_mic_muted;
                const micIcon = document.getElementById('mic-icon');
                const micText = document.getElementById('mic-text');
                const micBtn = document.getElementById('mic-btn');

                if (isMyMicActive) {
                    micIcon.className = 'fa-solid fa-microphone';
                    micText.innerText = 'Mic Aktif';
                    micBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2';
                } else {
                    micIcon.className = 'fa-solid fa-microphone-slash text-rose-400';
                    micText.innerText = 'Mic Dimatikan';
                    micBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-rose-900/60 border border-rose-600 text-white transition flex items-center space-x-2';
                }
            } else {
                showToast(data.message, 'rose');
            }
        });
    }

    let isCamActive = true;
    function toggleCam() {
        isCamActive = !isCamActive;
        const camBtn = document.getElementById('cam-btn');
        const camIcon = document.getElementById('cam-icon');
        const camText = document.getElementById('cam-text');
        if (isCamActive) {
            camBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-white transition flex items-center space-x-2';
            camIcon.className = 'fa-solid fa-video';
            camText.innerText = 'Kamera';
        } else {
            camBtn.className = 'px-3.5 py-2 rounded-xl text-xs font-semibold bg-rose-900/60 border border-rose-600 text-white transition flex items-center space-x-2';
            camIcon.className = 'fa-solid fa-video-slash text-rose-400';
            camText.innerText = 'Kamera Off';
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

            // 1. Sync PDF URL if changed
            if (data.presentation_url && data.presentation_url !== currentPdfUrl) {
                currentPdfUrl = data.presentation_url;
                document.getElementById('presentation-name').innerText = data.presentation_name || 'Materi_Presentasi.pdf';
                loadPdfDocument(currentPdfUrl, data.current_slide);
            }

            // 2. Sync Slide page for Participant (Host controls it)
            if (!IS_HOST) {
                if (data.current_slide !== currentSlide) {
                    currentSlide = data.current_slide;
                    if (pdfDoc) {
                        queueRenderPage(currentSlide);
                    } else {
                        renderFallbackSlide(currentSlide);
                    }
                }
            }

            document.getElementById('current-slide').innerText = data.current_slide;
            document.getElementById('total-slides').innerText = data.total_slides;

            // 3. Mute All State banner & button
            isAllMicMuted = data.is_all_mic_muted;
            const micBanner = document.getElementById('mic-locked-banner');
            if (micBanner) {
                if (isAllMicMuted && !IS_HOST) micBanner.classList.remove('hidden');
                else micBanner.classList.add('hidden');
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
</script>
@endpush
