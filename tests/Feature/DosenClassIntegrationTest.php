<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenClassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dashboard_displays_assigned_teaching_classes(): void
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

        $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Kelas Perkuliahan Anda');
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('Kelas A');
        $response->assertSee('TIF201');
        $response->assertSee('1 Mahasiswa');
        $response->assertSee('Mulai Sesi Virtual');
    }

    public function test_dosen_can_start_session_linked_to_course_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
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

        $response = $this->actingAs($dosen)->post(route('dosen.classroom.create'), [
            'course_class_id' => $class->id,
            'title' => 'Pemrograman Web Lanjut (Kelas A)',
        ]);

        $this->assertDatabaseHas('virtual_classrooms', [
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        $room = VirtualClassroom::where('course_class_id', $class->id)->first();
        $this->assertNotNull($room);
        $response->assertRedirect(route('classroom.room', $room->room_code));
    }

    public function test_dosen_can_rejoin_existing_live_session_for_assigned_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
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

        $liveRoom = VirtualClassroom::create([
            'room_code' => '3IN1-LIVE1',
            'title' => 'Pemrograman Web Lanjut (Kelas A)',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
            'current_slide' => 1,
            'total_slides' => 1,
        ]);

        // Attempt to start room for the same class again
        $response = $this->actingAs($dosen)->post(route('dosen.classroom.create'), [
            'course_class_id' => $class->id,
            'title' => 'Pemrograman Web Lanjut (Kelas A)',
        ]);

        // Should redirect directly to existing room, not creating a duplicate
        $response->assertRedirect(route('classroom.room', $liveRoom->room_code));
        $this->assertEquals(1, VirtualClassroom::where('course_class_id', $class->id)->count());

        // Check dashboard displays live badge and rejoin button
        $dashboardResponse = $this->actingAs($dosen)->get(route('dosen.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('SESI VIRTUAL SEDANG BERLANGSUNG');
        $dashboardResponse->assertSee('Masuk Kembali ke Ruang Kelas');
    }

    public function test_dosen_cannot_start_session_for_another_dosens_class(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosenA->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($dosenB)->post(route('dosen.classroom.create'), [
            'course_class_id' => $class->id,
            'title' => 'Pemrograman Web Lanjut (Kelas A)',
        ]);

        $response->assertStatus(403);
    }

    public function test_dosen_dashboard_filters_classes_by_current_semester(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        // Kelas Semester Aktif
        $activeClass = CourseClass::create([
            'kode_mk' => 'MK-AKTIF',
            'nama_mk' => 'Mata Kuliah Semester Aktif',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 7,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Ruang 101',
        ]);

        // Kelas Semester Lalu
        $pastClass = CourseClass::create([
            'kode_mk' => 'MK-LALU',
            'nama_mk' => 'Mata Kuliah Semester Lalu',
            'sks' => 2,
            'nama_kelas' => 'Kelas B',
            'prodi' => 'Teknologi Informasi',
            'semester' => 6,
            'tahun_ajaran' => '2025/2026 Genap',
            'dosen_id' => $dosen->id,
            'hari' => 'Selasa',
            'jam_mulai' => '10:30',
            'jam_selesai' => '12:00',
            'ruang' => 'Ruang 102',
        ]);

        // 1. Default request (tanpa query param) harus menampilkan semester aktif
        $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('selectedTahunAjaran', '2026/2027 Ganjil');
        $response->assertSee('Mata Kuliah Semester Aktif');
        $response->assertDontSee('Mata Kuliah Semester Lalu');

        // 2. Request dengan filter semester lalu
        $responsePast = $this->actingAs($dosen)->get(route('dosen.dashboard', ['tahun_ajaran' => '2025/2026 Genap']));
        $responsePast->assertStatus(200);
        $responsePast->assertViewHas('selectedTahunAjaran', '2025/2026 Genap');
        $responsePast->assertSee('Mata Kuliah Semester Lalu');
        $responsePast->assertDontSee('Mata Kuliah Semester Aktif');
    }

    public function test_dosen_dashboard_renders_semester_filter_and_assigned_class_select(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF-701',
            'nama_mk' => 'Arsitektur Enterprise',
            'sks' => 3,
            'nama_kelas' => 'TI-7A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 7,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Rabu',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 3',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('name="tahun_ajaran"', false);
        $response->assertSee('name="course_class_id"', false);
        $response->assertSee('TIF-701 - Arsitektur Enterprise');
    }

    public function test_dosen_cannot_see_other_dosen_classes_and_can_create_room_from_assigned_class(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $classA = CourseClass::create([
            'kode_mk' => 'MK-A',
            'nama_mk' => 'Kelas Dosen A',
            'sks' => 3,
            'nama_kelas' => 'A1',
            'prodi' => 'Teknologi Informasi',
            'semester' => 7,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosenA->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $classB = CourseClass::create([
            'kode_mk' => 'MK-B',
            'nama_mk' => 'Kelas Dosen B',
            'sks' => 3,
            'nama_kelas' => 'B1',
            'prodi' => 'Teknologi Informasi',
            'semester' => 7,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosenB->id,
            'hari' => 'Senin',
            'jam_mulai' => '10:00',
            'jam_selesai' => '12:00',
        ]);

        // Dosen A tidak boleh melihat kelas Dosen B
        $response = $this->actingAs($dosenA)->get(route('dosen.dashboard'));
        $response->assertSee('Kelas Dosen A');
        $response->assertDontSee('Kelas Dosen B');

        // Dosen A membuat room dengan course_class_id
        $postResponse = $this->actingAs($dosenA)->post(route('dosen.classroom.create'), [
            'course_class_id' => $classA->id,
        ]);
        $postResponse->assertRedirect();

        $this->assertDatabaseHas('virtual_classrooms', [
            'dosen_id' => $dosenA->id,
            'course_class_id' => $classA->id,
            'title' => 'Kelas Dosen A (A1)',
            'status' => 'live',
        ]);
    }
}
