<?php

namespace Tests\Unit;

use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseClassModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_class_has_dosen_and_students_relationships(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $courseClass = CourseClass::create([
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

        $this->assertEquals($dosen->id, $courseClass->dosen->id);
        $this->assertCount(1, $dosen->teachingClasses);

        // Attach students
        $courseClass->students()->attach([$student1->id, $student2->id]);
        $this->assertCount(2, $courseClass->students);
        $this->assertCount(1, $student1->enrolledClasses);
    }

    public function test_course_class_virtual_classrooms_and_active_session_relationships(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $courseClass = CourseClass::create([
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

        $classroom = VirtualClassroom::create([
            'room_code' => 'TEST1234',
            'title' => 'Pertemuan 1',
            'dosen_id' => $dosen->id,
            'course_class_id' => $courseClass->id,
            'status' => 'live',
        ]);

        $this->assertCount(1, $courseClass->virtualClassrooms);
        $this->assertEquals($courseClass->id, $classroom->courseClass->id);
        $this->assertNotNull($courseClass->activeSession());
        $this->assertEquals($classroom->id, $courseClass->activeSession()->id);
    }
}
