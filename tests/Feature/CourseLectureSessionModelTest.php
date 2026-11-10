<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLectureSessionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lecture_session_and_link_to_course_class_and_dosen(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
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
            'topik' => 'Pengenalan Algoritma',
            'ruang' => 'Ruang 201',
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('course_lecture_sessions', [
            'id' => $session->id,
            'course_class_id' => $class->id,
            'pertemuan_ke' => 1,
            'mode' => 'offline',
        ]);

        $this->assertEquals($class->id, $session->courseClass->id);
        $this->assertEquals($dosen->id, $session->dosen->id);
        $this->assertTrue($class->lectureSessions->contains($session));
    }

    public function test_can_create_lecture_attendance_for_students(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
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

        $attendance = CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'catatan' => 'Tepat waktu',
        ]);

        $this->assertDatabaseHas('course_lecture_attendances', [
            'id' => $attendance->id,
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $this->assertEquals($session->id, $attendance->lectureSession->id);
        $this->assertEquals($student->id, $attendance->student->id);
    }

    public function test_course_class_calculates_next_meeting_number(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $this->assertEquals(1, $class->nextMeetingNumber());

        CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        $this->assertEquals(2, $class->fresh()->nextMeetingNumber());
    }
}
