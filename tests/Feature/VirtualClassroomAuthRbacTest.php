<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VirtualClassroom;
use App\Services\CampusApiService;
use App\Services\PresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class VirtualClassroomAuthRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::create([
            'username' => '198001012010121001',
            'name' => 'Dr. Ir. Ahmad Syahid, M.Kom',
            'email' => 'ahmad@kampus.ac.id',
            'role' => 'dosen',
            'prodi' => 'Teknik Informatika',
            'status_aktif' => true,
            'password' => Hash::make('password'),
        ]);

        $this->mahasiswa = User::create([
            'username' => '2023001',
            'name' => 'Muhammad Rifqi',
            'email' => 'rifqi@kampus.ac.id',
            'role' => 'mahasiswa',
            'prodi' => 'Teknik Informatika',
            'status_aktif' => true,
            'password' => Hash::make('password'),
        ]);

        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator 3in1',
            'email' => 'admin@kampus.ac.id',
            'role' => 'admin',
            'status_aktif' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_login_and_access_admin_dashboard_and_campus_data(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->admin);
        $response->assertRedirect(route('admin.dashboard'));

        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashboard->assertStatus(200);
        $dashboard->assertSee('Panel Kontrol Administrator');

        $campusData = $this->actingAs($this->admin)->get(route('admin.campus_data'));
        $campusData->assertStatus(200);
        $campusData->assertSee('Data Referensi Akademik Terpadu');
    }

    public function test_admin_can_update_user_role(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.update_role', $this->mahasiswa->id), [
            'role' => 'dosen',
            'status_aktif' => 1,
        ]);

        $response->assertSessionHas('success');
        $this->mahasiswa->refresh();
        $this->assertSame('dosen', $this->mahasiswa->role);
    }

    public function test_dosen_cannot_access_campus_data_or_admin_dashboard(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('campus.data'));
        $response->assertRedirect(route('dosen.dashboard'));
        $response->assertSessionHas('error');

        $adminDash = $this->actingAs($this->dosen)->get(route('admin.dashboard'));
        $adminDash->assertRedirect(route('dosen.dashboard'));
        $adminDash->assertSessionHas('error');
    }

    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('3in1 Virtual Class');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => '2023001',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_dosen_can_login_and_is_redirected_to_dosen_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => '198001012010121001',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->dosen);
        $response->assertRedirect(route('dosen.dashboard'));

        $dashboardResponse = $this->actingAs($this->dosen)->get(route('dosen.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Host (Dosen)');
        $dashboardResponse->assertSee('Buka Sesi Kelas Baru');
    }

    public function test_mahasiswa_can_login_and_is_redirected_to_mahasiswa_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => '2023001',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->mahasiswa);
        $response->assertRedirect(route('mahasiswa.dashboard'));

        $dashboardResponse = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Peserta (Mahasiswa)');
        $dashboardResponse->assertSee('Gabung ke Ruang Kelas Virtual');
    }

    public function test_mahasiswa_can_login_with_nim_as_password(): void
    {
        $mhs = User::create([
            'username' => '1921300014',
            'name' => 'MOH NURUL YAQIN',
            'role' => 'mahasiswa',
            'prodi' => 'Teknik Elektro',
            'password' => Hash::make('1921300014'),
        ]);

        // Login using NIM as both username and password
        $response = $this->post('/login', [
            'username' => '1921300014',
            'password' => '1921300014',
        ]);

        $this->assertAuthenticatedAs($mhs);
        $response->assertRedirect(route('mahasiswa.dashboard'));
    }

    public function test_mahasiswa_can_login_with_default_password_even_if_hash_is_nim(): void
    {
        $mhs = User::create([
            'username' => '1921300015',
            'name' => 'AHMAD TAUFIK',
            'role' => 'mahasiswa',
            'prodi' => 'Teknik Elektro',
            'password' => Hash::make('1921300015'),
        ]);

        // Login using 'password'
        $response = $this->post('/login', [
            'username' => '1921300015',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($mhs);
        $response->assertRedirect(route('mahasiswa.dashboard'));
    }

    public function test_rbac_prevents_mahasiswa_from_accessing_dosen_dashboard(): void
    {
        // Mahasiswa attempts to access Dosen dashboard
        $response = $this->actingAs($this->mahasiswa)->get(route('dosen.dashboard'));

        // Should be blocked and redirected to Mahasiswa dashboard with flash error
        $response->assertRedirect(route('mahasiswa.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_rbac_prevents_dosen_from_accessing_mahasiswa_dashboard(): void
    {
        // Dosen attempts to access Mahasiswa dashboard
        $response = $this->actingAs($this->dosen)->get(route('mahasiswa.dashboard'));

        // Should be blocked and redirected to Dosen dashboard with flash error
        $response->assertRedirect(route('dosen.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_both_dosen_and_mahasiswa_can_access_virtual_classroom_room(): void
    {
        $dosenRoom = $this->actingAs($this->dosen)->get('/room/3IN1-TEST');
        $dosenRoom->assertStatus(200);
        $dosenRoom->assertSee('Host Utama (Dosen)');
        $dosenRoom->assertSee('Mute All Peserta');
        $dosenRoom->assertSee('Share Screen');
        $dosenRoom->assertSee('drag-splitter');
        $dosenRoom->assertSee('classroom-split-container');
        $dosenRoom->assertSee('external_api.js');
        $dosenRoom->assertSee('jitsi-meet-container');
        $dosenRoom->assertSee('Tatap Muka Jitsi Meet');
        $dosenRoom->assertSee('permintaan moderator');
        $dosenRoom->assertDontSee('btn-engine-webrtc');
        $dosenRoom->assertDontSee('btn-engine-jitsi');
        $dosenRoom->assertSee('btn-mode-presentation');
        $dosenRoom->assertSee('btn-mode-split');
        $dosenRoom->assertSee('btn-mode-video');
        $dosenRoom->assertSee('id="mic-btn"', false);
        $dosenRoom->assertSee('id="cam-btn"', false);
        $dosenRoom->assertSee('id="share-screen-btn"', false);

        $mhsRoom = $this->actingAs($this->mahasiswa)->get('/room/3IN1-TEST');
        $mhsRoom->assertStatus(200);
        $mhsRoom->assertSee('Peserta (Mahasiswa)');
        $mhsRoom->assertSee('Raise Hand (Angkat Tangan)');
        $mhsRoom->assertSee('Share Screen');
        $mhsRoom->assertSee('Sync Lock');
        $mhsRoom->assertSee('drag-splitter');
        $mhsRoom->assertSee('external_api.js');
        $mhsRoom->assertSee('jitsi-meet-container');
        $mhsRoom->assertSee('Tatap Muka Jitsi Meet');
        $mhsRoom->assertSee('Otomatis bergabung begitu Host membuka ruangan');
        $mhsRoom->assertDontSee('btn-engine-webrtc');
        $mhsRoom->assertDontSee('btn-engine-jitsi');
        $mhsRoom->assertSee('id="mic-btn"', false);
        $mhsRoom->assertSee('id="cam-btn"', false);
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs($this->mahasiswa);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_dosen_can_login_with_nidn_as_password(): void
    {
        $dosen = User::create([
            'username' => '0703109403',
            'name' => 'AHMAD MUHTADI',
            'role' => 'dosen',
            'prodi' => 'Teknik Elektro',
            'status_aktif' => true,
            'password' => Hash::make('0703109403'),
        ]);

        $response = $this->post('/login', [
            'username' => '0703109403',
            'password' => '0703109403',
        ]);

        $this->assertAuthenticatedAs($dosen);
        $response->assertRedirect(route('dosen.dashboard'));
    }

    public function test_dosen_can_login_with_default_password_even_if_hash_is_nidn(): void
    {
        $dosen = User::create([
            'username' => '0706119303',
            'name' => 'FUAD HASAN, S.T.',
            'role' => 'dosen',
            'prodi' => 'Teknik Elektro',
            'status_aktif' => true,
            'password' => Hash::make('0706119303'),
        ]);

        $response = $this->post('/login', [
            'username' => '0706119303',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($dosen);
        $response->assertRedirect(route('dosen.dashboard'));
    }

    public function test_dosen_on_demand_provisioning_from_campus_api(): void
    {
        $this->mock(CampusApiService::class, function ($mock) {
            $mock->shouldReceive('getDosen')
                ->with(['search' => '0719117002'])
                ->once()
                ->andReturn([
                    'data' => [
                        'data' => [
                            [
                                'nidn' => '0719117002',
                                'nama_penduduk' => 'SULISTIYANTO',
                                'gelar_belakang' => 'M.T.',
                                'prodi' => 'Teknik Elektro',
                                'fakultas' => 'Teknik',
                                'email' => 'sulis@kampus.ac.id',
                            ],
                        ],
                    ],
                ]);
        });

        $this->assertDatabaseMissing('users', ['username' => '0719117002']);

        $response = $this->post('/login', [
            'username' => '0719117002',
            'password' => '0719117002',
        ]);

        $response->assertRedirect(route('dosen.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('username', '0719117002')->first();
        $this->assertNotNull($user);
        $this->assertSame('dosen', $user->role);
        $this->assertSame('SULISTIYANTO, M.T.', $user->name);
        $this->assertSame('Teknik Elektro', $user->prodi);
    }

    public function test_multiple_dosen_can_access_dashboard_without_room_code_collision(): void
    {
        $dosen1 = User::create([
            'username' => '0703109401',
            'name' => 'Dosen Satu',
            'role' => 'dosen',
            'prodi' => 'Teknik Elektro',
            'status_aktif' => true,
            'password' => Hash::make('password'),
        ]);

        $dosen2 = User::create([
            'username' => '0703109402',
            'name' => 'Dosen Dua',
            'role' => 'dosen',
            'prodi' => 'Teknik Informatika',
            'status_aktif' => true,
            'password' => Hash::make('password'),
        ]);

        $res1 = $this->actingAs($dosen1)->get(route('dosen.dashboard'));
        $res1->assertStatus(200);

        $res2 = $this->actingAs($dosen2)->get(route('dosen.dashboard'));
        $res2->assertStatus(200);

        $rooms = VirtualClassroom::whereIn('dosen_id', [$dosen1->id, $dosen2->id])->get();
        $this->assertCount(2, $rooms);
        $this->assertNotSame($rooms[0]->room_code, $rooms[1]->room_code);
    }

    public function test_dosen_can_upload_pdf_presentation(): void
    {
        Storage::fake('public');
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-TEST',
            'title' => 'Uji Kelas Virtual',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $file = UploadedFile::fake()->create('slide_kuliah.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->dosen)->postJson('/dosen/classroom/3IN1-TEST/presentation', [
            'presentation' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'presentation_name' => 'slide_kuliah.pdf',
            'presentation_type' => 'pdf',
        ]);

        $classroom->refresh();
        $this->assertSame('slide_kuliah.pdf', $classroom->presentation_name);
        $this->assertSame('pdf', $classroom->presentation_type);
        Storage::disk('public')->assertExists($classroom->presentation_path);
    }

    public function test_dosen_can_upload_pptx_presentation(): void
    {
        Storage::fake('public');
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-TEST',
            'title' => 'Uji Kelas Virtual',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $file = UploadedFile::fake()->create('materi_kuliah.pptx', 500, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->actingAs($this->dosen)->postJson('/dosen/classroom/3IN1-TEST/presentation', [
            'presentation' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'presentation_name' => 'materi_kuliah.pptx',
            'presentation_type' => 'pptx',
        ]);

        $classroom->refresh();
        $this->assertSame('materi_kuliah.pptx', $classroom->presentation_name);
        $this->assertSame('pptx', $classroom->presentation_type);
        Storage::disk('public')->assertExists($classroom->presentation_path);
    }

    public function test_presentation_file_can_be_streamed_via_route(): void
    {
        Storage::fake('public');
        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-TEST',
            'title' => 'Uji Kelas Virtual',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
        ]);

        $file = UploadedFile::fake()->create('materi.pdf', 300, 'application/pdf');

        $this->actingAs($this->dosen)->postJson('/dosen/classroom/3IN1-TEST/presentation', [
            'presentation' => $file,
        ]);

        $response = $this->actingAs($this->mahasiswa)->get(route('classroom.presentation.file', '3IN1-TEST'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'inline; filename="materi.pdf"');
    }

    public function test_presentation_service_extracts_pptx_slide_text_and_paragraphs(): void
    {
        Storage::fake('public');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_pptx_').'.pptx';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $slideXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
               xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
               xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
            <p:cSld>
                <p:spTree>
                    <p:sp>
                        <p:nvSpPr>
                            <p:cNvPr id="1" name="Title 1"/>
                            <p:cNvSpPr/>
                            <p:nvPr>
                                <p:ph type="title"/>
                            </p:nvPr>
                        </p:nvSpPr>
                        <p:txBody>
                            <a:bodyPr/>
                            <a:p>
                                <a:r>
                                    <a:t>PENYUSUNAN PROPOSAL TUGAS AKHIR</a:t>
                                </a:r>
                            </a:p>
                        </p:txBody>
                    </p:sp>
                    <p:sp>
                        <p:nvSpPr>
                            <p:cNvPr id="2" name="Content 2"/>
                            <p:cNvSpPr/>
                            <p:nvPr/>
                        </p:nvSpPr>
                        <p:txBody>
                            <a:bodyPr/>
                            <a:p>
                                <a:r>
                                    <a:t>PRODI TEKNOLOGI INFORMASI</a:t>
                                </a:r>
                            </a:p>
                            <a:p>
                                <a:r>
                                    <a:t>Maulidiansyah, M.Kom</a:t>
                                </a:r>
                            </a:p>
                        </p:txBody>
                    </p:sp>
                </p:spTree>
            </p:cSld>
        </p:sld>';

        $zip->addFromString('ppt/slides/slide1.xml', $slideXml);
        $zip->close();

        $service = new PresentationService;
        $parsed = $service->parsePptx($tempPath, 100);

        @unlink($tempPath);

        $this->assertSame(1, $parsed['total_slides']);
        $this->assertSame('PENYUSUNAN PROPOSAL TUGAS AKHIR', $parsed['slides'][0]['title']);
        $this->assertCount(2, $parsed['slides'][0]['paragraphs']);
        $this->assertContains('PRODI TEKNOLOGI INFORMASI', $parsed['slides'][0]['paragraphs']);
        $this->assertContains('Maulidiansyah, M.Kom', $parsed['slides'][0]['paragraphs']);
    }

    public function test_presentation_streaming_serves_converted_pdf_and_allows_original_download(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('presentations/materi.pptx', "PK\x03\x04test_content");
        Storage::disk('public')->put('presentations/materi.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n");

        $classroom = VirtualClassroom::create([
            'room_code' => '3IN1-PPTX',
            'title' => 'Uji Kelas PPTX',
            'dosen_id' => $this->dosen->id,
            'status' => 'live',
            'presentation_path' => 'presentations/materi.pptx',
            'presentation_name' => 'materi.pptx',
            'presentation_type' => 'pptx',
        ]);

        // 1. Permintaan view biasa untuk browser: menyajikan PDF hasil konversi
        $viewResponse = $this->actingAs($this->mahasiswa)->get(route('classroom.presentation.file', '3IN1-PPTX'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertHeader('Content-Type', 'application/pdf');
        $viewResponse->assertHeader('Content-Disposition', 'inline; filename="materi.pdf"');

        // 2. Permintaan download asli (?download=1): menyajikan file PPTX asli
        $downloadResponse = $this->actingAs($this->mahasiswa)->get(route('classroom.presentation.file', '3IN1-PPTX').'?download=1');
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename="materi.pptx"');
    }
}
