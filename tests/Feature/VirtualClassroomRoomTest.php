<?php

namespace Tests\Feature;

use App\Models\ClassroomAttendance;
use App\Models\ClassroomChat;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VirtualClassroomRoomTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::factory()->create([
            'username' => '198001012010121001',
            'name' => 'Dr. Ir. Ahmad Syahid, M.Kom',
            'role' => 'dosen',
            'prodi' => 'Teknik Informatika',
            'fakultas' => 'Fakultas Teknik',
        ]);

        $this->mahasiswa = User::factory()->create([
            'username' => '2023001',
            'name' => 'Muhammad Rifqi',
            'role' => 'mahasiswa',
            'prodi' => 'Teknik Informatika',
            'fakultas' => 'Fakultas Teknik',
        ]);
    }

    public function test_dosen_can_create_room(): void
    {
        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.classroom.create'), [
                'title' => 'Pemrograman Web Lanjut TI-4A',
            ]);

        $this->assertDatabaseHas('virtual_classrooms', [
            'title' => 'Pemrograman Web Lanjut TI-4A',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $classroom = VirtualClassroom::first();
        $response->assertRedirect(route('classroom.room', ['room_code' => $classroom->room_code]));
    }

    public function test_user_enters_room_and_records_attendance(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-TEST1',
            'title' => 'Sesi Pengujian Kelas',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        // Mahasiswa enters
        $response = $this->actingAs($this->mahasiswa)
            ->get(route('classroom.room', ['room_code' => '3IN1-TEST1']));

        $response->assertOk();
        $response->assertSee('Sesi Pengujian Kelas');
        $response->assertSee('3IN1-TEST1');

        $this->assertDatabaseHas('classroom_attendances', [
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $this->mahasiswa->id,
            'role' => 'participant',
            'status' => 'hadir',
        ]);
    }

    public function test_dosen_can_upload_pdf_presentation(): void
    {
        Storage::fake('public');

        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-TEST2',
            'title' => 'Materi Kuliah Interaktif',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $fakePdf = UploadedFile::fake()->create('slide_pertemuan_1.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->dosen)
            ->postJson(route('dosen.classroom.presentation', ['room_code' => '3IN1-TEST2']), [
                'presentation' => $fakePdf,
                'total_slides' => 15,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'presentation_name' => 'slide_pertemuan_1.pdf',
            'current_slide' => 1,
            'total_slides' => 15,
        ]);

        $classroom->refresh();
        $this->assertNotNull($classroom->presentation_path);
        Storage::disk('public')->assertExists($classroom->presentation_path);
    }

    public function test_mahasiswa_cannot_upload_presentation(): void
    {
        $fakePdf = UploadedFile::fake()->create('hacked.pdf', 100, 'application/pdf');

        // Web request: blocked and redirected to mahasiswa dashboard with error message
        $response = $this->actingAs($this->mahasiswa)
            ->post(route('dosen.classroom.presentation', ['room_code' => '3IN1-TEST2']), [
                'presentation' => $fakePdf,
            ]);

        $response->assertRedirect(route('mahasiswa.dashboard'));
        $response->assertSessionHas('error');

        // JSON API request: returns HTTP 403 Forbidden
        $responseJson = $this->actingAs($this->mahasiswa)
            ->postJson(route('dosen.classroom.presentation', ['room_code' => '3IN1-TEST2']), [
                'presentation' => $fakePdf,
            ]);

        $responseJson->assertStatus(403);
    }

    public function test_dosen_can_update_slide_synchronously(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-SLIDE',
            'title' => 'Presentasi Kuliah',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
            'current_slide' => 1,
            'total_slides' => 10,
        ]);

        $response = $this->actingAs($this->dosen)
            ->postJson(route('dosen.classroom.slide', ['room_code' => '3IN1-SLIDE']), [
                'current_slide' => 5,
                'total_slides' => 10,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'current_slide' => 5,
            'total_slides' => 10,
        ]);

        $this->assertEquals(5, $classroom->fresh()->current_slide);
    }

    public function test_dosen_can_toggle_mute_all_participants(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-MUTE',
            'title' => 'Sesi Kuliah Fokus',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
            'is_all_mic_muted' => false,
        ]);

        ClassroomAttendance::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $this->mahasiswa->id,
            'role' => 'participant',
            'joined_at' => now(),
            'is_mic_muted' => false,
        ]);

        $response = $this->actingAs($this->dosen)
            ->postJson(route('dosen.classroom.mute_all', ['room_code' => '3IN1-MUTE']));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_all_mic_muted' => true,
        ]);

        $this->assertTrue($classroom->fresh()->is_all_mic_muted);
        $this->assertTrue(ClassroomAttendance::where('user_id', $this->mahasiswa->id)->first()->is_mic_muted);
    }

    public function test_participant_can_raise_hand(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-HAND',
            'title' => 'Sesi Tanya Jawab',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        ClassroomAttendance::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $this->mahasiswa->id,
            'role' => 'participant',
            'joined_at' => now(),
            'is_hand_raised' => false,
        ]);

        $response = $this->actingAs($this->mahasiswa)
            ->postJson(route('classroom.raise_hand', ['room_code' => '3IN1-HAND']), [
                'raise_hand' => true,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_hand_raised' => true,
        ]);

        $this->assertTrue(ClassroomAttendance::where('user_id', $this->mahasiswa->id)->first()->is_hand_raised);
    }

    public function test_users_can_post_chat_and_receive_in_state(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-CHAT',
            'title' => 'Ruang Diskusi',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $chatResponse = $this->actingAs($this->mahasiswa)
            ->postJson(route('classroom.chat', ['room_code' => '3IN1-CHAT']), [
                'message' => 'Izin bertanya terkait materi pertemuan hari ini, Pak.',
            ]);

        $chatResponse->assertOk();
        $chatResponse->assertJson([
            'success' => true,
            'chat' => [
                'user_name' => 'Muhammad Rifqi',
                'message' => 'Izin bertanya terkait materi pertemuan hari ini, Pak.',
            ],
        ]);

        // State polling verification
        $stateResponse = $this->actingAs($this->dosen)
            ->getJson(route('classroom.state', ['room_code' => '3IN1-CHAT']));

        $stateResponse->assertOk();
        $stateResponse->assertJsonStructure([
            'success',
            'title',
            'current_slide',
            'total_slides',
            'is_all_mic_muted',
            'participants',
            'chats',
        ]);
    }

    public function test_dosen_can_export_attendance_and_chat_logs(): void
    {
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-EXPORT',
            'title' => 'Sesi Ekspor Rekap',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        ClassroomAttendance::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $this->mahasiswa->id,
            'role' => 'participant',
            'joined_at' => now(),
            'status' => 'hadir',
        ]);

        ClassroomChat::create([
            'virtual_classroom_id' => $classroom->id,
            'user_id' => $this->mahasiswa->id,
            'message' => 'Pesan diskusi untuk log.',
        ]);

        // Export attendance CSV
        $attResponse = $this->actingAs($this->dosen)
            ->get(route('dosen.classroom.export_attendance', ['room_code' => '3IN1-EXPORT']));

        $attResponse->assertOk();
        $this->assertStringContainsString('text/csv', $attResponse->headers->get('content-type'));

        // Export chat logs CSV
        $chatResponse = $this->actingAs($this->dosen)
            ->get(route('dosen.classroom.export_chat', ['room_code' => '3IN1-EXPORT']));

        $chatResponse->assertOk();
        $this->assertStringContainsString('text/csv', $chatResponse->headers->get('content-type'));

        // Mahasiswa cannot export: redirected with access error
        $blockedResponse = $this->actingAs($this->mahasiswa)
            ->get(route('dosen.classroom.export_attendance', ['room_code' => '3IN1-EXPORT']));

        $blockedResponse->assertRedirect(route('mahasiswa.dashboard'));
        $blockedResponse->assertSessionHas('error');
    }
}
