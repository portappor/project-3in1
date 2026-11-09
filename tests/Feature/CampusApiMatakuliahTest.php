<?php

namespace Tests\Feature;

use App\Services\CampusApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampusApiMatakuliahTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Helper to mock Campus API endpoints deterministically.
     */
    protected function fakeCampusApi(bool $withError = false, ?string $failingProdiId = null): void
    {
        Http::fake([
            '*/authorize' => function (Request $request) use ($withError) {
                if ($withError) {
                    return Http::response(['message' => 'Authorize connection timeout'], 500);
                }

                return Http::response([
                    'success' => true,
                    'token' => 'mocked-campus-access-token-1234567890abcdef',
                    'data' => [
                        'token' => 'mocked-campus-access-token-1234567890abcdef',
                        'info' => ['scope' => 'all'],
                    ],
                ], 200);
            },
            '*/data/*' => function (Request $request) use ($withError, $failingProdiId) {
                if ($withError) {
                    return Http::response(['message' => 'Service Unavailable'], 503);
                }

                $body = json_decode($request->body(), true) ?? [];
                $idSms = $body['id_sms'] ?? '';

                if ($failingProdiId && $idSms === $failingProdiId) {
                    return Http::response(['message' => 'Prodi database timeout'], 500);
                }

                $prodiMap = [
                    '0f6bb17f-efdd-493c-8939-30c215bd9fb1' => ['name' => 'Teknik Elektro', 'prefix' => 'TEL'],
                    '1319bfef-2188-4240-b7a3-7a507bec2e64' => ['name' => 'Teknik Informatika', 'prefix' => 'TIF'],
                    '6bb7c485-08a3-465c-b549-e5223c2affe2' => ['name' => 'Teknologi Informasi', 'prefix' => 'TIN'],
                    '6dc3cb7a-fc03-4c1a-a66f-faa4de36d6e8' => ['name' => 'Teknik Industri Pertanian', 'prefix' => 'TIP'],
                ];

                $prodiInfo = $prodiMap[$idSms] ?? ['name' => 'Teknik Informatika', 'prefix' => 'TIF'];
                $prodiName = $prodiInfo['name'];
                $prefix = $prodiInfo['prefix'];

                $courses = [];
                for ($i = 1; $i <= 8; $i++) {
                    $courses[] = [
                        'kode_matkul' => "{$prefix}10{$i}",
                        'nama_matkul' => "Matakuliah {$prodiName} {$i}",
                        'sks_mk' => 3,
                        'prodi' => $prodiName,
                        'semester' => ($i % 8) + 1,
                        'nama_kurikulum' => 'Kurikulum 2024',
                        'id_matkul' => "mk-{$prefix}-{$i}",
                    ];
                }

                return Http::response([
                    'success' => true,
                    'data' => [
                        'data' => $courses,
                        'total' => count($courses),
                    ],
                ], 200);
            },
        ]);
    }

    public function test_campus_api_service_fetches_matakuliah_for_all_engineering_prodi(): void
    {
        if (! env('CAMPUS_API_TEST_LIVE', false)) {
            $this->fakeCampusApi();
        }

        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['pagination' => 'off']);

        $this->assertIsArray($result);
        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        // Memastikan lebih dari katalog statis 12 matakuliah (32 mock atau 700+ live)
        $this->assertGreaterThan(20, count($records));

        $first = $records[0];
        $this->assertArrayHasKey('kode', $first);
        $this->assertArrayHasKey('nama', $first);
        $this->assertArrayHasKey('prodi', $first);

        // Memastikan tersimpan dalam cache
        $cacheKey = 'campus_api_all_matakuliah_'.md5(json_encode(['pagination' => 'off']));
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_campus_api_service_can_filter_matakuliah_by_prodi(): void
    {
        if (! env('CAMPUS_API_TEST_LIVE', false)) {
            $this->fakeCampusApi();
        }

        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['prodi' => 'Teknik Elektro', 'pagination' => 'off']);

        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        foreach ($records as $item) {
            $this->assertStringContainsStringIgnoringCase('Elektro', $item['prodi']);
        }
    }

    public function test_campus_api_service_prevents_cache_poisoning_on_api_failure(): void
    {
        // Simulasi kegagalan total server API kampus
        $this->fakeCampusApi(withError: true);

        $api = app(CampusApiService::class);
        $options = [];
        $result = $api->getMatakuliah($options);

        // Harus fallback ke katalog akademik statis
        $this->assertIsArray($result);
        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertCount(12, $records);

        // KUNCI: Jangan pernah meng-cache data fallback jika API live gagal
        $cacheKey = 'campus_api_all_matakuliah_'.md5(json_encode($options));
        $this->assertFalse(Cache::has($cacheKey), 'Fallback catalog should not poison cache for 24h when API fails.');
    }

    public function test_campus_api_service_per_prodi_resilience_when_one_prodi_fails(): void
    {
        // Simulasi prodi Teknik Elektro mengalami kegagalan/timeout di server kampus
        $failingProdiId = CampusApiService::TEKNIK_PRODI_SMS['Teknik Elektro'];
        $this->fakeCampusApi(withError: false, failingProdiId: $failingProdiId);

        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['pagination' => 'off']);

        $this->assertTrue($result['success']);
        $this->assertSame('campus_api_live', $result['source']);

        $records = $result['data'];
        $this->assertNotEmpty($records);

        // Prodi lain tetap berhasil dimuat (3 prodi x 8 courses = 24 courses)
        $this->assertCount(24, $records);

        $prodis = array_unique(array_column($records, 'prodi'));
        $this->assertContains('Teknik Informatika', $prodis);
        $this->assertNotContains('Teknik Elektro', $prodis);

        // Data yang berhasil tetap tersimpan di cache
        $cacheKey = 'campus_api_all_matakuliah_'.md5(json_encode(['pagination' => 'off']));
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_campus_api_service_serves_cached_data_on_subsequent_calls(): void
    {
        $this->fakeCampusApi();

        $api = app(CampusApiService::class);
        $options = ['pagination' => 'off'];

        $firstCall = $api->getMatakuliah($options);
        $this->assertSame('campus_api_live', $firstCall['source']);

        // Matikan semua koneksi HTTP untuk membuktikan data diambil dari cache
        Http::fake(['*' => Http::response('Server unreachable', 500)]);

        $secondCall = $api->getMatakuliah($options);
        $this->assertSame($firstCall, $secondCall);
    }
}
