<?php

namespace App\Http\Controllers;

use App\Models\ClassroomAttendance;
use App\Models\ClassroomChat;
use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use App\Services\CampusApiService;
use App\Services\PresentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualClassroomController extends Controller
{
    /**
     * Dashboard untuk peran Dosen (Host).
     */
    public function dosenDashboard(CampusApiService $campusApi)
    {
        $user = Auth::user();

        $teachingClasses = $user->teachingClasses()
            ->with(['students', 'virtualClassrooms'])
            ->latest()
            ->get();

        $recentRooms = VirtualClassroom::where('dosen_id', $user->id)
            ->with(['courseClass'])
            ->withCount('attendances')
            ->latest()
            ->take(10)
            ->get();

        if ($recentRooms->isEmpty()) {
            do {
                $sampleCode = '3IN1-'.Str::upper(Str::random(5));
            } while (VirtualClassroom::where('room_code', $sampleCode)->exists());

            $sampleRoom = VirtualClassroom::create([
                'room_code' => $sampleCode,
                'title' => 'Sesi Pembelajaran Interaktif '.($user->prodi ?? 'Teknik'),
                'dosen_id' => $user->id,
                'status' => 'live',
                'current_slide' => 1,
                'total_slides' => 1,
            ]);

            ClassroomAttendance::firstOrCreate([
                'virtual_classroom_id' => $sampleRoom->id,
                'user_id' => $user->id,
            ], [
                'role' => 'host',
                'joined_at' => now(),
                'status' => 'hadir',
            ]);

            $recentRooms = VirtualClassroom::where('dosen_id', $user->id)
                ->withCount('attendances')
                ->latest()
                ->get();
        }

        try {
            $matakuliahList = $campusApi->getMatakuliah();
            $matakuliah = $matakuliahList['data']['data'] ?? $matakuliahList['data'] ?? $matakuliahList;
        } catch (\Throwable $e) {
            $matakuliah = [
                ['kode' => 'TIF201', 'nama' => 'Pemrograman Web Lanjut', 'sks' => 3, 'semester' => 4, 'kelas' => 'TI-4A'],
                ['kode' => 'TIF302', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester' => 5, 'kelas' => 'TI-5B'],
                ['kode' => 'TIF105', 'nama' => 'Struktur Data & Algoritma', 'sks' => 3, 'semester' => 2, 'kelas' => 'TI-2A'],
            ];
        }

        try {
            $jadwalList = $campusApi->getJadwal();
            $allJadwal = $jadwalList['data']['data'] ?? $jadwalList['data'] ?? $jadwalList;

            // Prioritaskan jadwal yang diampu Dosen bersangkutan
            $userName = strtolower($user->name);
            $userNidn = strtolower((string) $user->username);
            $userProdi = strtolower((string) ($user->prodi ?? ''));

            $nameParts = array_filter(
                explode(' ', preg_replace('/[^a-z0-9]/', ' ', $userName)),
                fn ($p) => strlen($p) >= 4 && ! in_array($p, ['kom', 'dosen', 'teknik', 'ir', 'prof', 'dra', 'drs'], true)
            );

            $jadwalMengajar = array_values(array_filter($allJadwal, function ($j) use ($nameParts, $userNidn) {
                $dosenNama = strtolower($j['dosen'] ?? $j['nama_dosen'] ?? '');
                $jadwalNidn = strtolower((string) ($j['nidn'] ?? $j['nip'] ?? ''));

                if ($jadwalNidn && $jadwalNidn === $userNidn) {
                    return true;
                }

                foreach ($nameParts as $part) {
                    if (str_contains($dosenNama, $part)) {
                        return true;
                    }
                }

                return false;
            }));

            // Jika belum ada jadwal spesifik atas nama dosen, saring berdasarkan prodi atau tampilkan jadwal akademik
            if (empty($jadwalMengajar)) {
                $prodiJadwal = array_values(array_filter($allJadwal, function ($j) use ($userProdi) {
                    if (empty($userProdi)) {
                        return false;
                    }
                    $jProdi = strtolower($j['prodi'] ?? '');

                    return str_contains($jProdi, $userProdi) || str_contains($userProdi, $jProdi);
                }));

                $jadwalMengajar = ! empty($prodiJadwal) ? $prodiJadwal : $allJadwal;
            }
        } catch (\Throwable $e) {
            $jadwalMengajar = [
                ['hari' => 'Senin', 'jam' => '08:00 - 10:30', 'nama_mk' => 'Pemrograman Web Lanjut', 'kode_mk' => 'TIF201', 'kelas' => 'TI-4A', 'ruang' => 'Ruang Kelas Virtual 3in1 (A)'],
                ['hari' => 'Rabu', 'jam' => '10:30 - 13:00', 'nama_mk' => 'Rekayasa Perangkat Lunak', 'kode_mk' => 'TIF302', 'kelas' => 'TI-5B', 'ruang' => 'Ruang Kelas Virtual 3in1 (B)'],
                ['hari' => 'Kamis', 'jam' => '13:00 - 15:30', 'nama_mk' => 'Struktur Data & Algoritma', 'kode_mk' => 'TIF105', 'kelas' => 'TI-2A', 'ruang' => 'Ruang Kelas Virtual 3in1 (C)'],
            ];
        }

        return view('dosen.dashboard', compact('user', 'recentRooms', 'matakuliah', 'jadwalMengajar', 'teachingClasses'));
    }

    /**
     * Dashboard untuk peran Mahasiswa (Participant).
     */
    public function mahasiswaDashboard(Request $request, CampusApiService $campusApi)
    {
        $user = Auth::user();

        $enrolledClasses = $user->enrolledClasses()
            ->with(['dosen', 'virtualClassrooms'])
            ->latest()
            ->get();

        try {
            $jadwalList = $campusApi->getJadwal();
            $allJadwal = $jadwalList['data']['data'] ?? $jadwalList['data'] ?? $jadwalList;

            // Filter jadwal sesuai program studi mahasiswa
            $userProdi = strtolower($user->prodi ?? '');
            $jadwal = array_values(array_filter($allJadwal, function ($j) use ($userProdi) {
                if (empty($userProdi)) {
                    return true;
                }
                $prodiJadwal = strtolower($j['prodi'] ?? '');

                return str_contains($prodiJadwal, $userProdi) || str_contains($userProdi, $prodiJadwal);
            }));

            if (empty($jadwal)) {
                $jadwal = $allJadwal;
            }
        } catch (\Throwable $e) {
            $jadwal = [
                ['kode_mk' => 'TIF201', 'nama_mk' => 'Pemrograman Web Lanjut', 'hari' => 'Senin', 'jam' => '08:00 - 10:30', 'dosen' => 'Dr. Ir. Ahmad Syahid, M.Kom', 'room_code' => '3IN1-WEB8'],
                ['kode_mk' => 'TIF302', 'nama_mk' => 'Rekayasa Perangkat Lunak', 'hari' => 'Selasa', 'jam' => '10:30 - 13:00', 'dosen' => 'Nurul Hidayati, M.T', 'room_code' => '3IN1-RPL5'],
            ];
        }

        return view('mahasiswa.dashboard', compact('user', 'jadwal', 'enrolledClasses'));
    }

    /**
     * Dosen membuat ruang kelas virtual baru.
     */
    public function createRoom(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required_without:course_class_id|nullable|string|max:150',
            'course_class_id' => 'nullable|exists:course_classes,id',
        ]);

        $courseClassId = $request->input('course_class_id');
        $courseClass = $courseClassId ? CourseClass::find($courseClassId) : null;

        if ($courseClass && $courseClass->dosen_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk memulai sesi perkuliahan kelas ini.');
        }

        // Jika kelas ini sudah memiliki sesi live yang aktif, alihkan langsung tanpa menduplikasi ruang
        if ($courseClass) {
            $existingLiveRoom = $courseClass->activeSession();
            if ($existingLiveRoom) {
                return redirect()->route('classroom.room', ['room_code' => $existingLiveRoom->room_code])
                    ->with('info', "Sesi kelas virtual untuk '{$courseClass->nama_mk}' sedang berlangsung. Anda dialihkan ke ruang kelas.");
            }
        }

        $title = $request->input('title');
        if (empty($title) && $courseClass) {
            $title = "{$courseClass->nama_mk} ({$courseClass->nama_kelas})";
        }
        if (empty($title)) {
            $title = 'Sesi Ruang Kelas Virtual';
        }

        do {
            $roomCode = '3IN1-'.Str::upper(Str::random(5));
        } while (VirtualClassroom::where('room_code', $roomCode)->exists());

        $classroom = VirtualClassroom::create([
            'room_code' => $roomCode,
            'title' => $title,
            'dosen_id' => Auth::id(),
            'course_class_id' => $courseClassId,
            'status' => 'live',
            'current_slide' => 1,
            'total_slides' => 1,
        ]);

        ClassroomAttendance::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => Auth::id(),
            'role' => 'host',
            'joined_at' => now(),
            'last_seen_at' => now(),
            'status' => 'hadir',
        ]);

        return redirect()->route('classroom.room', ['room_code' => $roomCode])
            ->with('success', "Ruang kelas virtual '{$classroom->title}' berhasil dibuat! Kode: {$roomCode}");
    }

    /**
     * Mahasiswa bergabung ke ruang kelas menggunakan kode kelas.
     */
    public function joinRoom(Request $request): RedirectResponse
    {
        $request->validate([
            'room_code' => 'required|string|max:50',
        ], [
            'room_code.required' => 'Kode ruang kelas wajib diisi.',
        ]);

        $code = Str::upper(trim($request->input('room_code')));
        $classroom = VirtualClassroom::where('room_code', $code)->first();
        $user = Auth::user();

        if ($classroom && $classroom->course_class_id && $user && $user->isMahasiswa()) {
            $isEnrolled = $classroom->courseClass && $classroom->courseClass->students->contains($user->id);
            if (! $isEnrolled) {
                return redirect()->route('mahasiswa.dashboard')
                    ->with('error', 'Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas.');
            }
        }

        return redirect()->route('classroom.room', ['room_code' => $code]);
    }

    /**
     * Halaman Ruang Kelas Virtual 3in1 (Interactive Room).
     */
    public function showRoom(Request $request, string $room_code)
    {
        $user = Auth::user();
        $code = Str::upper(trim($room_code));

        $classroom = VirtualClassroom::where('room_code', $code)->first();

        if ($classroom && $classroom->course_class_id && $user && $user->isMahasiswa()) {
            $isEnrolled = $classroom->courseClass && $classroom->courseClass->students->contains($user->id);
            if (! $isEnrolled) {
                return redirect()->route('mahasiswa.dashboard')
                    ->with('error', 'Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas.');
            }
        }

        if (! $classroom) {
            $defaultDosen = $user->isDosen()
                ? $user
                : (User::where('role', 'dosen')->first() ?? $user);

            $classroom = VirtualClassroom::create([
                'room_code' => $code,
                'title' => 'Ruang Kelas Virtual '.$code,
                'dosen_id' => $defaultDosen->id,
                'status' => 'live',
                'current_slide' => 1,
                'total_slides' => 1,
            ]);
        }

        // Catat presensi kehadiran pengguna saat masuk ruang kelas
        $attendance = ClassroomAttendance::updateOrCreate(
            [
                'virtual_classroom_id' => $classroom->id,
                'user_id' => $user->id,
            ],
            [
                'role' => ($user->id === $classroom->dosen_id || $user->isDosen()) ? 'host' : 'participant',
                'joined_at' => now(),
                'last_seen_at' => now(),
                'status' => 'hadir',
            ]
        );

        $isHost = ($user->id === $classroom->dosen_id) || $user->isDosen();

        // Pesan sambutan perdana jika diskusi masih kosong
        if ($classroom->chats()->count() === 0) {
            ClassroomChat::create([
                'virtual_classroom_id' => $classroom->id,
                'user_id' => $classroom->dosen_id,
                'message' => 'Selamat datang di ruang kelas virtual '.$classroom->title.'. Silakan aktifkan mikrofon atau gunakan fitur Raise Hand jika ingin bertanya.',
            ]);
        }

        $classroom->load(['dosen', 'attendances.user', 'chats.user']);

        return view('classroom.room', compact('user', 'classroom', 'room_code', 'isHost', 'attendance'));
    }

    /**
     * Unggah dokumen materi presentasi (PDF, PPTX, atau PPT) khusus Dosen/Host.
     */
    public function uploadPresentation(Request $request, string $room_code, PresentationService $presentationService): JsonResponse|RedirectResponse
    {
        $request->validate([
            'presentation' => 'required|file|max:51200',
        ]);

        $file = $request->file('presentation');
        $ext = Str::lower($file->getClientOriginalExtension());
        if (! in_array($ext, ['pdf', 'ppt', 'pptx'], true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format file tidak didukung. Harap unggah file PDF atau PowerPoint (.ppt, .pptx).',
                ], 422);
            }

            return back()->with('error', 'Format file tidak didukung. Harap unggah file PDF atau PowerPoint (.ppt, .pptx).');
        }

        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $processed = $presentationService->processUpload($file, $classroom);

        $totalSlides = $request->filled('total_slides')
            ? max(1, (int) $request->input('total_slides'))
            : max(1, $processed['total_slides']);

        $classroom->update([
            'presentation_path' => $processed['path'],
            'presentation_name' => $processed['name'],
            'presentation_type' => $processed['type'],
            'presentation_data' => $processed['data'],
            'current_slide' => 1,
            'total_slides' => $totalSlides,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Materi presentasi ('.Str::upper($processed['type']).') berhasil diunggah.',
                'presentation_url' => $classroom->presentation_url,
                'presentation_name' => $classroom->presentation_name,
                'presentation_type' => $classroom->presentation_type,
                'presentation_data' => $classroom->presentation_data,
                'current_slide' => $classroom->current_slide,
                'total_slides' => $classroom->total_slides,
            ]);
        }

        return back()->with('success', 'Materi presentasi berhasil diunggah.');
    }

    /**
     * Sajikan file dokumen presentasi secara langsung (stream/download).
     */
    public function getPresentationFile(string $room_code, PresentationService $presentationService): BinaryFileResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();

        return $presentationService->streamFile($classroom);
    }

    /**
     * Update slide halaman aktif (Next / Prev) oleh Dosen.
     */
    public function updateSlide(Request $request, string $room_code): JsonResponse
    {
        $request->validate([
            'current_slide' => 'required|integer|min:1',
            'total_slides' => 'nullable|integer|min:1',
        ]);

        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();

        $classroom->current_slide = $request->integer('current_slide');
        if ($request->filled('total_slides')) {
            $classroom->total_slides = max($classroom->total_slides, $request->integer('total_slides'));
        }
        $classroom->save();

        return response()->json([
            'success' => true,
            'current_slide' => $classroom->current_slide,
            'total_slides' => $classroom->total_slides,
        ]);
    }

    /**
     * Dosen membisukan seluruh mikrofon peserta sekaligus (Mute All).
     */
    public function toggleMuteAll(Request $request, string $room_code): JsonResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $classroom->is_all_mic_muted = ! $classroom->is_all_mic_muted;
        $classroom->save();

        if ($classroom->is_all_mic_muted) {
            ClassroomAttendance::where('virtual_classroom_id', $classroom->id)
                ->where('user_id', '!=', Auth::id())
                ->update(['is_mic_muted' => true]);
        }

        return response()->json([
            'success' => true,
            'is_all_mic_muted' => $classroom->is_all_mic_muted,
            'message' => $classroom->is_all_mic_muted
                ? 'Semua mikrofon peserta telah dibisukan (Mute All).'
                : 'Mikrofon peserta telah diizinkan kembali.',
        ]);
    }

    /**
     * Mahasiswa menaikkan atau menurunkan tangan (Raise Hand).
     */
    public function toggleRaiseHand(Request $request, string $room_code): JsonResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $attendance = ClassroomAttendance::firstOrCreate(
            ['virtual_classroom_id' => $classroom->id, 'user_id' => Auth::id()],
            ['role' => 'participant', 'joined_at' => now()]
        );

        $newStatus = $request->has('raise_hand')
            ? $request->boolean('raise_hand')
            : ! $attendance->is_hand_raised;

        $attendance->update([
            'is_hand_raised' => $newStatus,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'is_hand_raised' => $newStatus,
        ]);
    }

    /**
     * Pengguna menyalakan atau mematikan mikrofon sendiri.
     */
    public function toggleMic(Request $request, string $room_code): JsonResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $user = Auth::user();

        if ($classroom->is_all_mic_muted && ! $user->isDosen() && $user->id !== $classroom->dosen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Mikrofon terkunci oleh Dosen (Host).',
                'is_mic_muted' => true,
            ], 403);
        }

        $attendance = ClassroomAttendance::firstOrCreate(
            ['virtual_classroom_id' => $classroom->id, 'user_id' => $user->id],
            ['role' => $user->isDosen() ? 'host' : 'participant', 'joined_at' => now()]
        );

        $newMuted = $request->has('is_muted')
            ? $request->boolean('is_muted')
            : ! $attendance->is_mic_muted;

        $attendance->update([
            'is_mic_muted' => $newMuted,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'is_mic_muted' => $newMuted,
        ]);
    }

    /**
     * Mengirim pesan ke papan diskusi kelas virtual.
     */
    public function sendChat(Request $request, string $room_code): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $user = Auth::user();

        $chat = ClassroomChat::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $user->id,
            'message' => trim($request->input('message')),
        ]);

        return response()->json([
            'success' => true,
            'chat' => [
                'id' => $chat->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $user->role,
                'message' => $chat->message,
                'created_at' => $chat->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Mengambil status ruang kelas secara real-time untuk polling sinkronisasi.
     */
    public function getRoomState(string $room_code): JsonResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $user = Auth::user();

        // Perbarui waktu aktif terakhir user
        ClassroomAttendance::where('virtual_classroom_id', $classroom->id)
            ->where('user_id', $user->id)
            ->update(['last_seen_at' => now()]);

        $attendances = $classroom->attendances()->with('user')->get();
        $participants = $attendances->map(function ($att) {
            return [
                'id' => $att->id,
                'user_id' => $att->user_id,
                'name' => $att->user->name ?? 'Peserta',
                'username' => $att->user->username ?? '-',
                'role' => $att->role,
                'is_hand_raised' => (bool) $att->is_hand_raised,
                'is_mic_muted' => (bool) $att->is_mic_muted,
                'status' => $att->status,
            ];
        });

        $chats = $classroom->chats()->with('user')->latest()->take(50)->get()->reverse()->values()->map(function ($chat) {
            return [
                'id' => $chat->id,
                'user_id' => $chat->user_id,
                'user_name' => $chat->user->name ?? 'Pengguna',
                'role' => $chat->user->role ?? 'mahasiswa',
                'message' => $chat->message,
                'created_at' => $chat->created_at->format('H:i'),
            ];
        });

        $hasHandRaised = $attendances->where('role', '!=', 'host')->where('is_hand_raised', true)->isNotEmpty();

        return response()->json([
            'success' => true,
            'title' => $classroom->title,
            'current_slide' => $classroom->current_slide,
            'total_slides' => $classroom->total_slides,
            'presentation_url' => $classroom->presentation_url,
            'presentation_name' => $classroom->presentation_name,
            'presentation_type' => $classroom->presentation_type ?? 'pdf',
            'presentation_data' => $classroom->presentation_data,
            'is_all_mic_muted' => (bool) $classroom->is_all_mic_muted,
            'has_hand_raised' => $hasHandRaised,
            'participants' => $participants,
            'chats' => $chats,
        ]);
    }

    /**
     * Dosen mengunduh laporan presensi peserta kelas (CSV).
     */
    public function exportAttendance(string $room_code): StreamedResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $attendances = $classroom->attendances()->with('user')->get();
        $date = now()->format('Ymd_His');
        $filename = "laporan_presensi_{$room_code}_{$date}.csv";

        return response()->streamDownload(function () use ($attendances, $classroom) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN PRESENSI RUANG KELAS VIRTUAL 3IN1']);
            fputcsv($handle, ['Judul Kelas', $classroom->title]);
            fputcsv($handle, ['Kode Kelas', $classroom->room_code]);
            fputcsv($handle, ['Dosen Pengampu', $classroom->dosen->name ?? '-']);
            fputcsv($handle, ['Waktu Unduh', now()->format('d-m-Y H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, ['No', 'NIM / NIDN', 'Nama Lengkap', 'Peran', 'Program Studi', 'Fakultas', 'Waktu Masuk', 'Status Presensi', 'Status Mic', 'Raise Hand']);

            $no = 1;
            foreach ($attendances as $att) {
                fputcsv($handle, [
                    $no++,
                    $att->user->username ?? '-',
                    $att->user->name ?? '-',
                    ucfirst($att->role),
                    $att->user->prodi ?? 'Teknik Informatika',
                    $att->user->fakultas ?? 'Fakultas Teknik',
                    $att->joined_at ? $att->joined_at->format('d-m-Y H:i:s') : '-',
                    ucfirst($att->status),
                    $att->is_mic_muted ? 'Muted' : 'Aktif',
                    $att->is_hand_raised ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Dosen mengunduh log obrolan / diskusi kelas virtual (CSV).
     */
    public function exportChat(string $room_code): StreamedResponse
    {
        $classroom = VirtualClassroom::where('room_code', Str::upper($room_code))->firstOrFail();
        $chats = $classroom->chats()->with('user')->oldest()->get();
        $date = now()->format('Ymd_His');
        $filename = "log_chat_{$room_code}_{$date}.csv";

        return response()->streamDownload(function () use ($chats, $classroom) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LOG CHAT & DISKUSI RUANG KELAS VIRTUAL 3IN1']);
            fputcsv($handle, ['Judul Kelas', $classroom->title]);
            fputcsv($handle, ['Kode Kelas', $classroom->room_code]);
            fputcsv($handle, ['Dosen Pengampu', $classroom->dosen->name ?? '-']);
            fputcsv($handle, ['Waktu Unduh', now()->format('d-m-Y H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, ['No', 'Waktu', 'NIM / NIDN', 'Nama Pengirim', 'Peran', 'Pesan Diskusi']);

            $no = 1;
            foreach ($chats as $chat) {
                fputcsv($handle, [
                    $no++,
                    $chat->created_at->format('d-m-Y H:i:s'),
                    $chat->user->username ?? '-',
                    $chat->user->name ?? '-',
                    ucfirst($chat->user->role ?? 'mahasiswa'),
                    $chat->message,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
