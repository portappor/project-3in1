<?php

namespace Tests\Feature;

use App\Services\CampusApiService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CampusApiMatakuliahTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_campus_api_service_fetches_matakuliah_for_all_engineering_prodi(): void
    {
        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['pagination' => 'off']);

        $this->assertIsArray($result);
        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        // Memastikan lebih dari katalog statis 12 matakuliah (saat live server mencapai 700+)
        $this->assertGreaterThan(20, count($records));

        $first = $records[0];
        $this->assertArrayHasKey('kode', $first);
        $this->assertArrayHasKey('nama', $first);
        $this->assertArrayHasKey('prodi', $first);
    }

    public function test_campus_api_service_can_filter_matakuliah_by_prodi(): void
    {
        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['prodi' => 'Teknik Elektro', 'pagination' => 'off']);

        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        foreach ($records as $item) {
            $this->assertStringContainsStringIgnoringCase('Elektro', $item['prodi']);
        }
    }
}
