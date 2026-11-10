<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenLectureSessionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_can_view_session_setup_for_assigned_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
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

        $response = $this->actingAs($dosen)->get(route('dosen.classes.session_setup', $class->id));

        $response->assertStatus(200);
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('Kelas A');
        $response->assertSee('Pertemuan ke-');
    }

    public function test_dosen_cannot_access_session_setup_for_other_dosen_class(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosenA->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $response = $this->actingAs($dosenB)->get(route('dosen.classes.session_setup', $class->id));
        $response->assertStatus(403);
    }

    public function test_dosen_can_create_offline_session_and_is_redirected_to_attendance(): void
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

        $response = $this->actingAs($dosen)->post(route('dosen.classes.sessions.store', $class->id), [
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'topik' => 'Kontrak Kuliah & Pengenalan',
        ]);

        $this->assertDatabaseHas('course_lecture_sessions', [
            'course_class_id' => $class->id,
            'pertemuan_ke' => 1,
            'mode' => 'offline',
        ]);

        $session = CourseLectureSession::where('course_class_id', $class->id)->first();
        $this->assertNotNull($session);

        // Check both students auto-populated in attendances table
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);

        $response->assertRedirect(route('dosen.lectures.attendance', $session->id));
    }

    public function test_dosen_can_create_online_session_and_is_redirected_to_room(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
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

        $response = $this->actingAs($dosen)->post(route('dosen.classes.sessions.store', $class->id), [
            'pertemuan_ke' => 2,
            'tanggal' => '2026-11-17',
            'mode' => 'online',
            'topik' => 'Sinkronisasi Realtime',
        ]);

        $session = CourseLectureSession::where('course_class_id', $class->id)->where('pertemuan_ke', 2)->first();
        $this->assertNotNull($session);
        $this->assertNotNull($session->virtual_classroom_id);

        $response->assertRedirect(route('classroom.room', $session->virtualClassroom->room_code));
    }
}
