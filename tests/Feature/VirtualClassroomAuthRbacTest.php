<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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

        $mhsRoom = $this->actingAs($this->mahasiswa)->get('/room/3IN1-TEST');
        $mhsRoom->assertStatus(200);
        $mhsRoom->assertSee('Peserta (Mahasiswa)');
        $mhsRoom->assertSee('Raise Hand (Angkat Tangan)');
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs($this->mahasiswa);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}

