<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenLectureAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_can_view_offline_attendance_sheet(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Budi Santoso', 'username' => '2023001']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $class->students()->attach($student->id);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.lectures.attendance', $session->id));

        $response->assertStatus(200);
        $response->assertSee('Lembar Presensi Tatap Muka');
        $response->assertSee('Budi Santoso');
        $response->assertSee('2023001');
    }

    public function test_dosen_can_save_updated_attendance_statuses(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $class->students()->attach([$student1->id, $student2->id]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
        ]);
        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($dosen)->post(route('dosen.lectures.attendance.save', $session->id), [
            'attendances' => [
                $student1->id => [
                    'status' => 'sakit',
                    'catatan' => 'Surat dokter terlampir',
                ],
                $student2->id => [
                    'status' => 'hadir',
                    'catatan' => '',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'sakit',
            'catatan' => 'Surat dokter terlampir',
        ]);
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);
    }

    public function test_dosen_can_export_offline_attendance_to_csv(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Siti Nurhaliza', 'username' => '2023002']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'catatan' => 'Hadir tepat waktu',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.lectures.attendance.export', $session->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('NIM/Username', $content);
        $this->assertStringContainsString('Siti Nurhaliza', $content);
        $this->assertStringContainsString('HADIR', $content);
    }
}
