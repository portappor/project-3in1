<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassroomAttendance;
use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\VirtualClassroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DosenLectureSessionController extends Controller
{
    /**
     * Halaman pengaturan sesi perkuliahan (Online / Offline).
     */
    public function setup(CourseClass $class): View
    {
        $this->authorizeClassAccess($class);

        $class->load(['students', 'lectureSessions.attendances']);
        $nextMeeting = $class->nextMeetingNumber();
        $historySessions = $class->lectureSessions()->latest('pertemuan_ke')->get();

        return view('dosen.classes.session_setup', compact('class', 'nextMeeting', 'historySessions'));
    }

    /**
     * Simpan sesi perkuliahan baru dan arahkan berdasarkan mode (Online/Offline).
     */
    public function store(Request $request, CourseClass $class): RedirectResponse
    {
        $this->authorizeClassAccess($class);

        $validated = $request->validate([
            'pertemuan_ke' => 'required|integer|min:1|max:16',
            'tanggal' => 'required|date',
            'mode' => 'required|in:online,offline',
            'topik' => 'nullable|string|max:255',
        ]);

        $mode = $validated['mode'];
        $virtualClassroomId = null;

        if ($mode === 'online') {
            // Cek jika kelas sudah memiliki sesi live yang aktif
            $existingLiveRoom = $class->activeSession();
            if ($existingLiveRoom) {
                $virtualClassroomId = $existingLiveRoom->id;
            } else {
                do {
                    $roomCode = '3IN1-'.Str::upper(Str::random(5));
                } while (VirtualClassroom::where('room_code', $roomCode)->exists());

                $newRoom = VirtualClassroom::create([
                    'room_code' => $roomCode,
                    'title' => "{$class->nama_mk} ({$class->nama_kelas}) - Pertemuan {$validated['pertemuan_ke']}",
                    'dosen_id' => Auth::id(),
                    'course_class_id' => $class->id,
                    'status' => 'live',
                    'current_slide' => 1,
                    'total_slides' => 1,
                ]);

                ClassroomAttendance::create([
                    'virtual_classroom_id' => $newRoom->id,
                    'user_id' => Auth::id(),
                    'role' => 'host',
                    'joined_at' => now(),
                    'last_seen_at' => now(),
                    'status' => 'hadir',
                ]);

                $virtualClassroomId = $newRoom->id;
            }
        }

        // Buat CourseLectureSession
        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => Auth::id(),
            'pertemuan_ke' => $validated['pertemuan_ke'],
            'tanggal' => $validated['tanggal'],
            'mode' => $mode,
            'topik' => $validated['topik'] ?? null,
            'ruang' => $mode === 'offline' ? ($class->ruang ?? 'Ruang Kelas Fisik') : 'Ruang Virtual 3in1',
            'virtual_classroom_id' => $virtualClassroomId,
            'status' => 'aktif',
        ]);

        // Jika mode offline, inisialisasi daftar kehadiran mahasiswa terploting
        if ($mode === 'offline') {
            $studentIds = $class->students()->pluck('users.id');
            foreach ($studentIds as $studentId) {
                CourseLectureAttendance::firstOrCreate([
                    'lecture_session_id' => $session->id,
                    'student_id' => $studentId,
                ], [
                    'status' => 'hadir',
                    'waktu_presensi' => now(),
                ]);
            }

            return redirect()->route('dosen.lectures.attendance', $session->id)
                ->with('success', "Sesi perkuliahan Tatap Muka (Pertemuan {$session->pertemuan_ke}) berhasil dibuka. Silakan catat presensi mahasiswa.");
        }

        // Jika mode online, arahkan ke ruang kelas virtual
        $room = VirtualClassroom::find($virtualClassroomId);

        return redirect()->route('classroom.room', $room->room_code)
            ->with('success', "Ruang kelas virtual (Pertemuan {$session->pertemuan_ke}) aktif! Kode: {$room->room_code}");
    }

    /**
     * Tampilkan lembar presensi perkuliahan offline.
     */
    public function showAttendance(CourseLectureSession $session): View
    {
        $this->authorizeSessionAccess($session);

        $session->load(['courseClass.students', 'attendances.student']);

        // Pastikan semua mahasiswa terdaftar memiliki baris presensi
        $existingStudentIds = $session->attendances->pluck('student_id')->toArray();
        $classStudents = $session->courseClass->students;

        foreach ($classStudents as $student) {
            if (! in_array($student->id, $existingStudentIds)) {
                CourseLectureAttendance::create([
                    'lecture_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => 'hadir',
                    'waktu_presensi' => now(),
                ]);
            }
        }

        $session->load('attendances.student');
        $attendances = $session->attendances->sortBy(fn ($att) => $att->student->name ?? '');

        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpa' => $attendances->where('status', 'alpa')->count(),
        ];

        return view('dosen.lectures.attendance', compact('session', 'attendances', 'stats'));
    }

    /**
     * Simpan status presensi mahasiswa.
     */
    public function saveAttendance(Request $request, CourseLectureSession $session): RedirectResponse
    {
        $this->authorizeSessionAccess($session);

        $data = $request->input('attendances', []);

        foreach ($data as $studentId => $row) {
            $status = in_array($row['status'] ?? '', ['hadir', 'izin', 'sakit', 'alpa']) ? $row['status'] : 'hadir';
            $catatan = isset($row['catatan']) ? substr(trim($row['catatan']), 0, 255) : null;

            CourseLectureAttendance::updateOrCreate([
                'lecture_session_id' => $session->id,
                'student_id' => $studentId,
            ], [
                'status' => $status,
                'catatan' => $catatan,
                'waktu_presensi' => now(),
            ]);
        }

        return redirect()->route('dosen.lectures.attendance', $session->id)
            ->with('success', 'Data presensi perkuliahan berhasil diperbarui!');
    }

    /**
     * Ekspor lembar presensi offline ke file CSV.
     */
    public function exportAttendanceCsv(CourseLectureSession $session)
    {
        $this->authorizeSessionAccess($session);

        $session->load(['courseClass', 'attendances.student']);
        $filename = 'Presensi-'.$session->courseClass->kode_mk.'-Pertemuan-'.$session->pertemuan_ke.'-'.$session->tanggal->format('Ymd').'.csv';

        $callback = function () use ($session) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Mata Kuliah', $session->courseClass->nama_mk]);
            fputcsv($handle, ['Kode MK', $session->courseClass->kode_mk]);
            fputcsv($handle, ['Kelas', $session->courseClass->nama_kelas]);
            fputcsv($handle, ['Pertemuan Ke', $session->pertemuan_ke]);
            fputcsv($handle, ['Tanggal', $session->tanggal->format('d/m/Y')]);
            fputcsv($handle, ['Mode Perkuliahan', strtoupper($session->mode)]);
            fputcsv($handle, ['Topik', $session->topik ?? '-']);
            fputcsv($handle, []);
            fputcsv($handle, ['No', 'NIM/Username', 'Nama Mahasiswa', 'Status Kehadiran', 'Catatan', 'Waktu Presensi']);

            $no = 1;
            foreach ($session->attendances as $att) {
                fputcsv($handle, [
                    $no++,
                    $att->student->username ?? '-',
                    $att->student->name ?? '-',
                    strtoupper($att->status),
                    $att->catatan ?? '-',
                    $att->waktu_presensi ? $att->waktu_presensi->format('d/m/Y H:i') : '-',
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeClassAccess(CourseClass $class): void
    {
        if ($class->dosen_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola perkuliahan kelas ini.');
        }
    }

    private function authorizeSessionAccess(CourseLectureSession $session): void
    {
        if ($session->dosen_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk sesi perkuliahan ini.');
        }
    }
}
