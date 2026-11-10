<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaClassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_sees_classes_and_can_join_live_session(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ]);
        $class->students()->attach($student->id);

        // Dosen starts a live session
        $room = VirtualClassroom::create([
            'room_code' => '3IN1-WEB1',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Matakuliah & Kelas Terdaftar Anda');
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('SEDANG LIVE');
        $response->assertSee(route('classroom.room', $room->room_code));
    }

    public function test_enrolled_student_sees_classes_without_active_session(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF302',
            'nama_mk' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
            'nama_kelas' => 'Kelas B',
            'prodi' => 'Teknik Informatika',
            'semester' => 5,
            'dosen_id' => $dosen->id,
            'hari' => 'Rabu',
            'jam_mulai' => '10:30',
            'jam_selesai' => '13:00',
            'ruang' => 'Lab Komputer 2',
        ]);
        $class->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Rekayasa Perangkat Lunak');
        $response->assertSee('Belum Ada Sesi Aktif');
        $response->assertSee('Menunggu Dosen Memulai Sesi');
    }

    public function test_non_enrolled_student_is_blocked_from_protected_class_room(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $enrolledStudent = User::factory()->create(['role' => 'mahasiswa']);
        $outsiderStudent = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);
        $class->students()->attach($enrolledStudent->id);

        $room = VirtualClassroom::create([
            'room_code' => '3IN1-WEB2',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        // Outsider student attempts to enter via direct URL
        $response = $this->actingAs($outsiderStudent)->get(route('classroom.room', $room->room_code));
        $response->assertRedirect(route('mahasiswa.dashboard'));
        $response->assertSessionHas('error', 'Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas.');

        // Outsider student attempts to enter via form POST
        $postResponse = $this->actingAs($outsiderStudent)->post(route('mahasiswa.classroom.join'), [
            'room_code' => $room->room_code,
        ]);
        $postResponse->assertRedirect(route('mahasiswa.dashboard'));
        $postResponse->assertSessionHas('error', 'Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas.');
    }

    public function test_enrolled_student_can_enter_protected_class_room(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $enrolledStudent = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);
        $class->students()->attach($enrolledStudent->id);

        $room = VirtualClassroom::create([
            'room_code' => '3IN1-WEB3',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        $response = $this->actingAs($enrolledStudent)->get(route('classroom.room', $room->room_code));
        $response->assertStatus(200);
        $response->assertSee($room->room_code);
    }

    public function test_student_can_join_unassigned_adhoc_room_via_code(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $adhocRoom = VirtualClassroom::create([
            'room_code' => '3IN1-ADHOC',
            'title' => 'Diskusi Bebas Terbuka',
            'dosen_id' => $dosen->id,
            'course_class_id' => null,
            'status' => 'live',
        ]);

        $response = $this->actingAs($student)->get(route('classroom.room', $adhocRoom->room_code));
        $response->assertStatus(200);
        $response->assertSee($adhocRoom->room_code);
    }

    public function test_student_sees_offline_session_and_attendance_status_configured_by_dosen(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen', 'name' => 'Dr. Budi Santoso']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ]);
        $class->students()->attach($student->id);

        // Dosen mengonfigurasi sesi perkuliahan OFFLINE (Pertemuan 3)
        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 3,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'topik' => 'Arsitektur MVC dan Routing',
            'ruang' => 'Lab Komputer 1',
            'status' => 'aktif',
        ]);

        // Dosen mencatat kehadiran mahasiswa sebagai HADIR
        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'catatan' => 'Hadir di lab',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('KULIAH TATAP MUKA');
        $response->assertSee('Pertemuan 3');
        $response->assertSee('Arsitektur MVC dan Routing');
        $response->assertSee('Lab Komputer 1');
        $response->assertSee('Hadir');
    }

    public function test_student_sees_online_session_meeting_number_and_topic_configured_by_dosen(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen', 'name' => 'Dr. Budi Santoso']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Ruang Virtual',
        ]);
        $class->students()->attach($student->id);

        $room = VirtualClassroom::create([
            'room_code' => '3IN1-ONLINE',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        // Sesi online pertemuan 4
        CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 4,
            'tanggal' => '2026-11-17',
            'mode' => 'online',
            'topik' => 'API RESTful & Sanctum',
            'virtual_classroom_id' => $room->id,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Pertemuan 4');
        $response->assertSee('API RESTful & Sanctum');
        $response->assertSee('SEDANG LIVE');
        $response->assertSee(route('classroom.room', $room->room_code));
    }
}
