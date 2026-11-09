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
}
