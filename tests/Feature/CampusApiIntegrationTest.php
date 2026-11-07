<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CampusApiService;
use App\Services\CampusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampusApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.campus_api.base_url' => 'https://api.kampus.test',
            'services.campus_api.app_id' => 'test-app-id',
            'services.campus_api.dev_id' => 'test-dev-id',
            'services.campus_api.x_token' => 'test-x-token-secret',
            'services.campus_api.user_agent' => 'Project-3in1-Test/1.0',
            'services.campus_api.default_password' => null,
        ]);

        Cache::flush();
    }

    public function test_api_service_can_authorize_and_caches_token(): void
    {
        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
                'status' => 'success',
            ], 200),
        ]);

        $service = app(CampusApiService::class);

        $token1 = $service->getAccessToken();
        $this->assertSame('abcdef1234567890abcdef1234567890', $token1);

        // Call again - should retrieve from cache, not making another HTTP request
        $token2 = $service->getAccessToken();
        $this->assertSame($token1, $token2);

        Http::assertSentCount(1);
    }

    public function test_api_service_sends_required_headers_and_body_when_fetching_data(): void
    {
        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    ['nidn' => '0712345678', 'nama' => 'Dr. Budi Santoso, M.Kom', 'nama_prodi' => 'Teknik Informatika'],
                ],
            ], 200),
        ]);

        $service = app(CampusApiService::class);
        $result = $service->getData('dosen', ['search' => 'Budi']);

        $this->assertCount(1, $result['data']);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/data/')) {
                return $request->hasHeader('X-Token', 'test-x-token-secret')
                    && $request->hasHeader('User-Agent', 'Project-3in1-Test/1.0')
                    && $request['filter'] === 'dosen'
                    && $request['search'] === 'Budi';
            }
            return true;
        });
    }

    public function test_sync_service_syncs_dosen_to_users_table(): void
    {
        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    [
                        'nidn' => '0712345678',
                        'nama' => 'Dr. Budi Santoso, M.Kom',
                        'nama_prodi' => 'Teknik Informatika',
                        'nama_fakultas' => 'Fakultas Teknik',
                        'email' => 'budi@kampus.ac.id',
                    ],
                ],
            ], 200),
        ]);

        $syncService = app(CampusSyncService::class);
        $stats = $syncService->syncDosen();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['created']);

        $user = User::where('username', '0712345678')->first();
        $this->assertNotNull($user);
        $this->assertSame('Dr. Budi Santoso, M.Kom', $user->name);
        $this->assertSame('dosen', $user->role);
        $this->assertSame('Teknik Informatika', $user->prodi);
        $this->assertSame('Fakultas Teknik', $user->fakultas);
        $this->assertTrue(Hash::check('0712345678', $user->password));
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->isDosen());
    }

    public function test_sync_service_syncs_mahasiswa_with_pagination(): void
    {
        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    [
                        'nim' => '2023001',
                        'nama' => 'Ahmad Fauzi',
                        'prodi' => 'Sistem Informasi',
                    ],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                ],
            ], 200),
        ]);

        $syncService = app(CampusSyncService::class);
        $stats = $syncService->syncMahasiswa();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['created']);

        $user = User::where('username', '2023001')->first();
        $this->assertNotNull($user);
        $this->assertSame('Ahmad Fauzi', $user->name);
        $this->assertSame('mahasiswa', $user->role);
        $this->assertTrue(Hash::check('2023001', $user->password));
        $this->assertTrue($user->isMahasiswa());
    }

    public function test_sync_service_preserves_password_when_user_has_changed_password(): void
    {
        // Existing user who already changed their password
        $existing = User::create([
            'username' => '0712345678',
            'name' => 'Budi Old Name',
            'role' => 'dosen',
            'password' => Hash::make('MyCustomSecretPassword!'),
            'password_changed_at' => now(),
        ]);

        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    [
                        'nidn' => '0712345678',
                        'nama' => 'Prof. Dr. Budi Santoso, M.Kom',
                    ],
                ],
            ], 200),
        ]);

        $syncService = app(CampusSyncService::class);
        $stats = $syncService->syncDosen();

        $this->assertSame(1, $stats['updated']);

        $existing->refresh();
        $this->assertSame('Prof. Dr. Budi Santoso, M.Kom', $existing->name);
        // Custom password must NOT be overwritten with default NIDN
        $this->assertTrue(Hash::check('MyCustomSecretPassword!', $existing->password));
        $this->assertFalse(Hash::check('0712345678', $existing->password));
    }

    public function test_artisan_command_sync_campus_users(): void
    {
        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    ['nidn' => '11223344', 'nama' => 'Dosen Test'],
                ],
            ], 200),
        ]);

        $this->artisan('campus:sync-users', ['--type' => 'dosen'])
            ->expectsOutputToContain('Menyinkronkan data Dosen')
            ->expectsOutputToContain('Sinkronisasi selesai!')
            ->assertExitCode(0);
    }

    public function test_campus_data_page_renders_successfully(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator 3in1',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    ['nidn' => '0712038001', 'nama' => 'Dr. Ir. Ahmad Syahid', 'prodi' => 'Teknik Informatika'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('campus.data', ['tab' => 'dosen']));
        $response->assertStatus(200);
        $response->assertSee('Data Referensi Akademik Terpadu');
    }

    public function test_campus_data_prodi_fakultas_tab_renders_without_error(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_fak'],
            [
                'name' => 'Admin Test Fak',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    ['id_fakultas' => 2, 'fakultas' => 'Teknik', 'singkatan' => 'FT'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('campus.data', ['tab' => 'prodi_fakultas']));
        $response->assertStatus(200);
        $response->assertSee('Daftar Fakultas');
        $response->assertSee('Daftar Program Studi');
    }

    public function test_campus_data_matakuliah_and_jadwal_tabs_render_cleanly(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_mk'],
            [
                'name' => 'Admin Test MK',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        $mkResponse = $this->actingAs($admin)->get(route('campus.data', ['tab' => 'matakuliah']));
        $mkResponse->assertStatus(200);
        $mkResponse->assertSee('Pemrograman Web Lanjut');
        $mkResponse->assertSee('TIF201');

        $jadwalResponse = $this->actingAs($admin)->get(route('campus.data', ['tab' => 'jadwal']));
        $jadwalResponse->assertStatus(200);
        $jadwalResponse->assertSee('Pemrograman Web Lanjut');
        $jadwalResponse->assertSee('TI-4A');
    }

    public function test_campus_data_can_filter_matakuliah_and_jadwal_by_prodi(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_prodi_filter'],
            [
                'name' => 'Admin Test Prodi Filter',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        // Filter Matakuliah for Teknik Elektro
        $mkTe = $this->actingAs($admin)->get(route('campus.data', [
            'tab' => 'matakuliah',
            'prodi' => 'Teknik Elektro',
        ]));
        $mkTe->assertStatus(200);
        $mkTe->assertSee('Rangkaian Listrik &amp; Elektronika', false);
        $mkTe->assertSee('TEL101');
        $mkTe->assertDontSee('TIF201');

        // Filter Jadwal for Teknik Elektro
        $jadwalTe = $this->actingAs($admin)->get(route('campus.data', [
            'tab' => 'jadwal',
            'prodi' => 'Teknik Elektro',
        ]));
        $jadwalTe->assertStatus(200);
        $jadwalTe->assertSee('Rangkaian Listrik &amp; Elektronika', false);
        $jadwalTe->assertSee('TE-2A');
        $jadwalTe->assertDontSee('TI-4A');
    }

    public function test_campus_data_can_filter_dosen_and_mahasiswa_by_prodi(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_dosen_mhs_filter'],
            [
                'name' => 'Admin Test Dosen Mhs Filter',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        Http::fake([
            'https://api.kampus.test/authorize' => Http::response([
                'token' => 'abcdef1234567890abcdef1234567890',
            ], 200),
            'https://api.kampus.test/data/abcdef1234567890abcdef1234567890' => Http::response([
                'data' => [
                    ['nidn' => '0711111111', 'nama' => 'Dosen Elektro Satu', 'prodi' => 'Teknik Elektro', 'fakultas' => 'Teknik'],
                    ['nidn' => '0722222222', 'nama' => 'Dosen Informatika Dua', 'prodi' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
                ],
            ], 200),
        ]);

        // Filter Dosen for Teknik Elektro
        $dosenTe = $this->actingAs($admin)->get(route('campus.data', [
            'tab' => 'dosen',
            'prodi' => 'Teknik Elektro',
        ]));
        $dosenTe->assertStatus(200);
        $dosenTe->assertSee('Dosen Elektro Satu');
        $dosenTe->assertDontSee('Dosen Informatika Dua');
    }
}

