<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseClassPlottingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_classes_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.classes.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Kelas & Ploting Mahasiswa');
    }

    public function test_admin_can_store_new_course_class_with_api_course_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $data = [
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.classes.store'), $data);

        $this->assertDatabaseHas('course_classes', [
            'nama_kelas' => 'Kelas A',
            'dosen_id' => $dosen->id,
            'kode_mk' => 'TIF201',
        ]);

        $courseClass = CourseClass::where('nama_kelas', 'Kelas A')->first();
        $this->assertNotNull($courseClass);
        $response->assertRedirect(route('admin.classes.plotting', $courseClass));
    }

    public function test_admin_can_update_course_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ]);

        $updateData = [
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas B',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Selasa',
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:30',
            'ruang' => 'Lab Komputer 2',
        ];

        $response = $this->actingAs($admin)->put(route('admin.classes.update', $class), $updateData);
        $response->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('course_classes', [
            'id' => $class->id,
            'nama_kelas' => 'Kelas B',
            'ruang' => 'Lab Komputer 2',
            'hari' => 'Selasa',
        ]);
    }

    public function test_admin_can_plot_students_into_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.classes.plotting.save', $class), [
            'students' => [$student1->id, $student2->id],
        ]);

        $response->assertRedirect(route('admin.classes.index'));
        $this->assertDatabaseHas('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student1->id,
        ]);
        $this->assertDatabaseHas('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student2->id,
        ]);

        // Test unplotting/syncing with subset
        $responseSync = $this->actingAs($admin)->post(route('admin.classes.plotting.save', $class), [
            'students' => [$student2->id],
        ]);
        $responseSync->assertRedirect(route('admin.classes.index'));
        $this->assertDatabaseMissing('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student1->id,
        ]);
        $this->assertDatabaseHas('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student2->id,
        ]);
    }

    public function test_admin_can_delete_course_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.classes.destroy', $class));
        $response->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseMissing('course_classes', [
            'id' => $class->id,
        ]);
    }

    public function test_non_admin_cannot_access_classes_management(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa)->get(route('admin.classes.index'));
        $response->assertRedirect(route('mahasiswa.dashboard'));

        $jsonResponse = $this->actingAs($mahasiswa)->getJson(route('admin.classes.index'));
        $jsonResponse->assertStatus(403);
    }
}
