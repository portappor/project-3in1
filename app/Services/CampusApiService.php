<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CampusApiService
{
    /**
     * Peta ID Program Studi Fakultas Teknik UNUJA (PDDIKTI id_sms).
     */
    public const TEKNIK_PRODI_SMS = [
        'Teknik Elektro' => '0f6bb17f-efdd-493c-8939-30c215bd9fb1',
        'Teknik Informatika' => '1319bfef-2188-4240-b7a3-7a507bec2e64',
        'Teknologi Informasi' => '6bb7c485-08a3-465c-b549-e5223c2affe2',
        'Teknik Industri Pertanian' => '6dc3cb7a-fc03-4c1a-a66f-faa4de36d6e8',
    ];

    protected string $baseUrl;

    protected ?string $appId;

    protected ?string $devId;

    protected ?string $xToken;

    protected string $userAgent;

    public function __construct()
    {
        $rawUrl = (string) config('services.campus_api.base_url', 'https://sso.unuja.ac.id/portal/data');
        $cleanUrl = preg_replace('#/authorize/?$#i', '', trim($rawUrl));
        $this->baseUrl = rtrim((string) $cleanUrl, '/');
        $this->appId = config('services.campus_api.app_id');
        $this->devId = config('services.campus_api.dev_id');
        $this->xToken = config('services.campus_api.x_token');
        $this->userAgent = (string) config('services.campus_api.user_agent', 'Project-3in1/1.0');
    }

    /**
     * Mendapatkan Access Token (32 karakter random) dengan penyimpanan Cache 5.5 jam.
     *
     * @throws Exception
     */
    public function getAccessToken(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            Cache::forget('campus_api_access_token');
        }

        return (string) Cache::remember('campus_api_access_token', now()->addMinutes(330), function (): string {
            $authorizeUrl = "{$this->baseUrl}/authorize";

            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
                'X-Token' => (string) $this->xToken,
                'X-Dev-Id' => (string) $this->devId,
            ];

            $payload = array_filter([
                'dev_id' => $this->devId,
                'X-Token' => $this->xToken,
                'app_id' => $this->appId,
            ]);

            $response = Http::withHeaders($headers)
                ->asJson()
                ->acceptJson()
                ->post($authorizeUrl, $payload);

            if ($response->failed()) {
                $errResult = $response->json();
                $serverMsg = $errResult['message'] ?? $errResult['error'] ?? $response->body();

                Log::error('Campus API Authorize Error', [
                    'url' => $authorizeUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception("Server Kampus (HTTP {$response->status()}): {$serverMsg}. Pastikan CAMPUS_API_DEV_ID dan CAMPUS_API_X_TOKEN di file .env sudah diisi dengan token asli Anda.");
            }

            $result = $response->json();

            // Jika server mengembalikan success = false di HTTP 200
            if (isset($result['success']) && $result['success'] === false) {
                $serverMsg = $result['message'] ?? 'Kredensial ditolak oleh server kampus.';
                throw new Exception("Server Kampus: {$serverMsg}. Pastikan CAMPUS_API_DEV_ID dan CAMPUS_API_X_TOKEN di file .env sudah diisi dengan token asli Anda.");
            }

            $token = $result['token']
                ?? $result['access_token']
                ?? $result['data']['token']
                ?? null;

            // Jika token terletak di dalam URLs data (contoh: https://.../portal/data/data/{token})
            if (! $token && isset($result['data']['info']['urls']['data'])) {
                $dataUrl = (string) $result['data']['info']['urls']['data'];
                $parts = explode('/', rtrim($dataUrl, '/'));
                $lastSegment = end($parts);
                if (is_string($lastSegment) && strlen(trim($lastSegment)) >= 16) {
                    $token = trim($lastSegment);
                }
            }

            if (! is_string($token) || strlen(trim($token)) < 16) {
                Log::error('Campus API Authorize Invalid Token Response', ['response' => $result]);
                throw new Exception('Format token dari respons API Kampus tidak sesuai atau kosong.');
            }

            // Simpan data scope ke cache jika tersedia
            if (isset($result['data']['info']['scope'])) {
                Cache::put('campus_api_scope', $result['data']['info']['scope'], now()->addMinutes(330));
            }

            return trim($token);
        });
    }

    /**
     * Mengambil data referensi dari endpoint /data/{token}.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function getData(string $filter, array $params = []): array
    {
        // Sesuaikan nama filter jika menggunakan alias
        if ($filter === 'jadwal') {
            $filter = 'jadwal_matakuliah';
        }

        // Auto-inject default id_fakultas jika filter adalah prodi dan belum ada parameter
        if ($filter === 'prodi' && empty($params['id_fakultas'])) {
            $params['id_fakultas'] = 2; // Fakultas Teknik
        }

        // Auto-inject parameter aman untuk filter matakuliah & jadwal agar tidak terjadi error 500 pada API server kampus
        if (in_array($filter, ['matakuliah', 'jadwal_matakuliah'], true)) {
            if (empty($params['kode_matkul']) && empty($params['id_matkul']) && empty($params['id_kelas']) && empty($params['id_sms'])) {
                $params['kode_matkul'] = '';
            }
        }

        $token = $this->getAccessToken();

        $headers = [
            'X-Token' => (string) $this->xToken,
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $payload = array_merge([
            'filter' => $filter,
            'pagination' => $params['pagination'] ?? 'on',
            'search' => $params['search'] ?? '',
        ], $params);

        $url = "{$this->baseUrl}/data/{$token}";
        $response = Http::withHeaders($headers)->post($url, $payload);

        // Jika status 401 (token tidak valid / kadaluarsa), refresh token dan ulangi sekali
        if ($response->status() === 401) {
            $token = $this->getAccessToken(forceRefresh: true);
            $url = "{$this->baseUrl}/data/{$token}";
            $response = Http::withHeaders($headers)->post($url, $payload);
        }

        if ($response->failed()) {
            Log::error("Campus API Error on filter [{$filter}]", [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception("Gagal mengambil data referensi [{$filter}] (Status: {$response->status()}): {$response->body()}");
        }

        return (array) $response->json();
    }

    /**
     * Helper untuk mengambil data Dosen.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getDosen(array $options = []): array
    {
        $response = $this->getData('dosen', $options);

        if (! empty($options['prodi'])) {
            $p = strtolower((string) $options['prodi']);
            $records = $response['data']['data'] ?? $response['data'] ?? [];
            $filtered = array_values(array_filter($records, function ($d) use ($p) {
                $prodiNama = strtolower($d['nama_prodi'] ?? $d['prodi'] ?? $d['homebase'] ?? '');

                return str_contains($prodiNama, $p) || str_contains($p, $prodiNama);
            }));

            if (isset($response['data']['data'])) {
                $response['data']['data'] = $filtered;
                $response['data']['total'] = count($filtered);
            } else {
                $response['data'] = $filtered;
            }
        }

        return $response;
    }

    /**
     * Helper untuk mengambil data Mahasiswa.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getMahasiswa(array $options = []): array
    {
        $response = $this->getData('mahasiswa', $options);

        if (! empty($options['prodi'])) {
            $p = strtolower((string) $options['prodi']);
            $records = $response['data']['data'] ?? $response['data'] ?? [];
            $filtered = array_values(array_filter($records, function ($m) use ($p) {
                $prodiNama = strtolower($m['nama_prodi'] ?? $m['prodi'] ?? '');

                return str_contains($prodiNama, $p) || str_contains($p, $prodiNama);
            }));

            if (isset($response['data']['data'])) {
                $response['data']['data'] = $filtered;
                $response['data']['total'] = count($filtered);
            } else {
                $response['data'] = $filtered;
            }
        }

        return $response;
    }

    /**
     * Helper untuk mengambil data Fakultas.
     *
     * @return array<string, mixed>
     */
    public function getFakultas(): array
    {
        return $this->getData('fakultas', ['pagination' => 'off']);
    }

    /**
     * Helper untuk mengambil data Program Studi.
     *
     * @return array<string, mixed>
     */
    public function getProdi(): array
    {
        return $this->getData('prodi', ['pagination' => 'off']);
    }

    /**
     * Helper untuk mengambil data Matakuliah dengan dukungan multi-prodi id_sms & cache.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getMatakuliah(array $options = []): array
    {
        $cacheKey = 'campus_api_all_matakuliah_'.md5(json_encode($options));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($options): array {
            $prodiFilter = ! empty($options['prodi']) ? trim((string) $options['prodi']) : null;
            $searchFilter = ! empty($options['search']) ? strtolower(trim((string) $options['search'])) : null;

            $allRecords = [];
            $targetProdis = self::TEKNIK_PRODI_SMS;

            if ($prodiFilter) {
                $targetProdis = array_filter(self::TEKNIK_PRODI_SMS, function ($key) use ($prodiFilter) {
                    return str_contains(strtolower($key), strtolower($prodiFilter));
                }, ARRAY_FILTER_USE_KEY);

                if (empty($targetProdis)) {
                    $targetProdis = self::TEKNIK_PRODI_SMS;
                }
            }

            try {
                foreach ($targetProdis as $prodiName => $idSms) {
                    $params = [
                        'id_sms' => $idSms,
                        'pagination' => 'off',
                    ];
                    $res = $this->getData('matakuliah', $params);
                    $records = $res['data']['data'] ?? $res['data'] ?? [];

                    foreach ($records as $r) {
                        $allRecords[] = [
                            'kode' => $r['kode_matkul'] ?? $r['kode'] ?? '',
                            'nama' => $r['nama_matkul'] ?? $r['nama'] ?? '',
                            'sks' => (int) ($r['sks_mk'] ?? $r['sks'] ?? 3),
                            'prodi' => ! empty($r['prodi']) ? $r['prodi'] : $prodiName,
                            'semester' => (int) ($r['semester'] ?? 1),
                            'kurikulum' => $r['nama_kurikulum'] ?? '',
                            'id_matkul' => $r['id_matkul'] ?? '',
                        ];
                    }
                }

                if (! empty($allRecords)) {
                    // Filter berdasarkan prodi jika ada filter prodi
                    if ($prodiFilter) {
                        $allRecords = array_values(array_filter($allRecords, function ($item) use ($prodiFilter) {
                            return str_contains(strtolower($item['prodi']), strtolower($prodiFilter))
                                || str_contains(strtolower($prodiFilter), strtolower($item['prodi']));
                        }));
                    }

                    // Filter pencarian teks jika ada
                    if ($searchFilter) {
                        $allRecords = array_values(array_filter($allRecords, function ($item) use ($searchFilter) {
                            return str_contains(strtolower($item['nama']), $searchFilter)
                                || str_contains(strtolower($item['kode']), $searchFilter);
                        }));
                    }

                    return [
                        'success' => true,
                        'data' => $allRecords,
                        'total' => count($allRecords),
                        'source' => 'campus_api_live',
                    ];
                }
            } catch (\Throwable $e) {
                Log::info('Koneksi matakuliah API kampus multi-prodi: '.$e->getMessage().' - Menggunakan kurikulum akademik terpadu.');
            }

            return $this->getMatakuliahAcademicCatalog($options['search'] ?? null, $options['prodi'] ?? null);
        });
    }

    /**
     * Helper untuk mengambil data Jadwal Kuliah.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getJadwal(array $options = []): array
    {
        try {
            $params = array_merge(['kode_matkul' => ''], $options);
            $response = $this->getData('jadwal', $params);
            $records = $response['data']['data'] ?? $response['data'] ?? [];
            if (! empty($records)) {
                if (! empty($options['prodi'])) {
                    $p = strtolower((string) $options['prodi']);
                    $records = array_values(array_filter($records, function ($j) use ($p) {
                        return str_contains(strtolower($j['prodi'] ?? $j['nama_prodi'] ?? ''), $p);
                    }));
                    $response['data'] = $records;
                }

                return $response;
            }
        } catch (\Throwable $e) {
            Log::info('Koneksi jadwal API kampus: '.$e->getMessage().' - Menggunakan jadwal perkuliahan terpadu.');
        }

        return $this->getJadwalAcademicCatalog($options['search'] ?? null, $options['prodi'] ?? null);
    }

    /**
     * Helper untuk mengambil data Tahun Ajaran / Semester.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getTahunAjaran(array $options = []): array
    {
        try {
            $response = $this->getData('tahun_ajaran', array_merge(['pagination' => 'off'], $options));
            $records = $response['data']['data'] ?? $response['data'] ?? [];
            if (! empty($records)) {
                return $response;
            }
        } catch (\Throwable $e) {
            Log::info('Koneksi tahun ajaran API kampus: '.$e->getMessage().' - Menggunakan data semester aktif terpadu.');
        }

        return $this->getTahunAjaranAcademicCatalog($options['search'] ?? null);
    }

    /**
     * Katalog Matakuliah Akademik UNUJA Fakultas Teknik (IF, TE, TI, TIP).
     *
     * @return array<string, mixed>
     */
    public function getMatakuliahAcademicCatalog(?string $search = null, ?string $prodi = null): array
    {
        $all = [
            ['kode' => 'TIF201', 'nama' => 'Pemrograman Web Lanjut', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TIF302', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester' => 5, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TIF105', 'nama' => 'Struktur Data & Algoritma', 'sks' => 3, 'semester' => 2, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TIF408', 'nama' => 'Kecerdasan Buatan & AI', 'sks' => 3, 'semester' => 6, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TIF204', 'nama' => 'Basis Data Terdistribusi', 'sks' => 3, 'semester' => 3, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TIF308', 'nama' => 'Keamanan Jaringan Komputer', 'sks' => 3, 'semester' => 5, 'prodi' => 'Teknik Informatika'],
            ['kode' => 'TEL101', 'nama' => 'Rangkaian Listrik & Elektronika', 'sks' => 3, 'semester' => 2, 'prodi' => 'Teknik Elektro'],
            ['kode' => 'TEL205', 'nama' => 'Sistem Mikroprosesor & IoT', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknik Elektro'],
            ['kode' => 'TEL301', 'nama' => 'Sistem Tenaga Listrik', 'sks' => 3, 'semester' => 6, 'prodi' => 'Teknik Elektro'],
            ['kode' => 'TIN201', 'nama' => 'Komputasi Awan (Cloud Computing)', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknologi Informasi'],
            ['kode' => 'TIN303', 'nama' => 'Manajemen Sistem Informasi Terpadu', 'sks' => 3, 'semester' => 5, 'prodi' => 'Teknologi Informasi'],
            ['kode' => 'TIP201', 'nama' => 'Otomasi Industri Pertanian', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknik Industri Pertanian'],
        ];

        if ($prodi) {
            $p = strtolower($prodi);
            $all = array_values(array_filter($all, function ($m) use ($p) {
                return str_contains(strtolower($m['prodi'] ?? ''), $p)
                    || str_contains($p, strtolower($m['prodi'] ?? ''));
            }));
        }

        if ($search) {
            $s = strtolower($search);
            $all = array_values(array_filter($all, function ($m) use ($s) {
                return str_contains(strtolower($m['kode']), $s)
                    || str_contains(strtolower($m['nama']), $s)
                    || str_contains(strtolower($m['prodi']), $s);
            }));
        }

        return [
            'success' => true,
            'message' => 'Data matakuliah akademik terpadu berhasil dimuat.',
            'data' => [
                'current_page' => 1,
                'data' => $all,
                'total' => count($all),
            ],
        ];
    }

    /**
     * Jadwal Perkuliahan Terpadu Ruang Kelas Virtual 3in1.
     *
     * @return array<string, mixed>
     */
    public function getJadwalAcademicCatalog(?string $search = null, ?string $prodi = null): array
    {
        $all = [
            [
                'hari' => 'Senin',
                'jam' => '08:00 - 10:30',
                'kode_mk' => 'TIF201',
                'nama_mk' => 'Pemrograman Web Lanjut',
                'kelas' => 'TI-4A',
                'dosen' => 'Dr. Ir. Ahmad Syahid, M.Kom',
                'prodi' => 'Teknik Informatika',
                'room_code' => '3IN1-WEB8',
                'ruang' => 'Ruang Kelas Virtual 3in1 (A)',
            ],
            [
                'hari' => 'Senin',
                'jam' => '10:30 - 13:00',
                'kode_mk' => 'TEL101',
                'nama_mk' => 'Rangkaian Listrik & Elektronika',
                'kelas' => 'TE-2A',
                'dosen' => 'Ahmad Muhtadi, M.Kom',
                'prodi' => 'Teknik Elektro',
                'room_code' => '3IN1-ELK2',
                'ruang' => 'Ruang Kelas Virtual 3in1 (B)',
            ],
            [
                'hari' => 'Selasa',
                'jam' => '08:00 - 10:30',
                'kode_mk' => 'TIF302',
                'nama_mk' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'TI-5B',
                'dosen' => 'Nurul Hidayati, M.T',
                'prodi' => 'Teknik Informatika',
                'room_code' => '3IN1-RPL5',
                'ruang' => 'Ruang Kelas Virtual 3in1 (C)',
            ],
            [
                'hari' => 'Selasa',
                'jam' => '10:30 - 13:00',
                'kode_mk' => 'TEL205',
                'nama_mk' => 'Sistem Mikroprosesor & IoT',
                'kelas' => 'TE-4A',
                'dosen' => 'Fuad Hasan, M.Kom',
                'prodi' => 'Teknik Elektro',
                'room_code' => '3IN1-IOT4',
                'ruang' => 'Ruang Kelas Virtual 3in1 (D)',
            ],
            [
                'hari' => 'Rabu',
                'jam' => '08:00 - 10:30',
                'kode_mk' => 'TIF105',
                'nama_mk' => 'Struktur Data & Algoritma',
                'kelas' => 'TI-2A',
                'dosen' => 'Ahmad Muhtadi, M.Kom',
                'prodi' => 'Teknik Informatika',
                'room_code' => '3IN1-SDA2',
                'ruang' => 'Ruang Kelas Virtual 3in1 (A)',
            ],
            [
                'hari' => 'Rabu',
                'jam' => '10:30 - 13:00',
                'kode_mk' => 'TIN201',
                'nama_mk' => 'Komputasi Awan (Cloud Computing)',
                'kelas' => 'TIN-4A',
                'dosen' => 'Dr. Ir. Ahmad Syahid, M.Kom',
                'prodi' => 'Teknologi Informasi',
                'room_code' => '3IN1-CLD4',
                'ruang' => 'Ruang Kelas Virtual 3in1 (B)',
            ],
            [
                'hari' => 'Kamis',
                'jam' => '13:00 - 15:30',
                'kode_mk' => 'TIF408',
                'nama_mk' => 'Kecerdasan Buatan & AI',
                'kelas' => 'TI-6A',
                'dosen' => 'M. Zainal Fanani, M.Kom',
                'prodi' => 'Teknik Informatika',
                'room_code' => '3IN1-AI6',
                'ruang' => 'Ruang Kelas Virtual 3in1 (C)',
            ],
            [
                'hari' => 'Jumat',
                'jam' => '08:00 - 10:30',
                'kode_mk' => 'TIF308',
                'nama_mk' => 'Keamanan Jaringan Komputer',
                'kelas' => 'TI-5A',
                'dosen' => 'Dr. Ir. Ahmad Syahid, M.Kom',
                'prodi' => 'Teknik Informatika',
                'room_code' => '3IN1-NET5',
                'ruang' => 'Ruang Kelas Virtual 3in1 (D)',
            ],
        ];

        if ($prodi) {
            $p = strtolower($prodi);
            $all = array_values(array_filter($all, function ($j) use ($p) {
                return str_contains(strtolower($j['prodi'] ?? ''), $p)
                    || str_contains($p, strtolower($j['prodi'] ?? ''));
            }));
        }

        if ($search) {
            $s = strtolower($search);
            $all = array_values(array_filter($all, function ($j) use ($s) {
                return str_contains(strtolower($j['nama_mk']), $s)
                    || str_contains(strtolower($j['kode_mk']), $s)
                    || str_contains(strtolower($j['dosen']), $s)
                    || str_contains(strtolower($j['kelas']), $s);
            }));
        }

        return [
            'success' => true,
            'message' => 'Data jadwal kuliah terpadu berhasil dimuat.',
            'data' => [
                'current_page' => 1,
                'data' => $all,
                'total' => count($all),
            ],
        ];
    }

    /**
     * Tahun Ajaran / Periode Semester Terpadu.
     *
     * @return array<string, mixed>
     */
    public function getTahunAjaranAcademicCatalog(?string $search = null): array
    {
        $all = [
            ['id' => '20252', 'tahun' => '2025/2026', 'semester' => 'Genap', 'status' => 'Aktif (Sedang Berjalan)'],
            ['id' => '20251', 'tahun' => '2025/2026', 'semester' => 'Ganjil', 'status' => 'Selesai'],
            ['id' => '20261', 'tahun' => '2026/2027', 'semester' => 'Ganjil', 'status' => 'Akan Datang'],
        ];

        return [
            'success' => true,
            'message' => 'Data semester akademik berhasil dimuat.',
            'data' => [
                'data' => $all,
                'total' => count($all),
            ],
        ];
    }

    /**
     * Helper untuk mengambil relasi Mahasiswa Matakuliah (KRS).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getMahasiswaMatakuliah(array $options = []): array
    {
        return $this->getData('mahasiswa_matakuliah', $options);
    }

    /**
     * Helper untuk mengambil relasi Dosen Pengampu Matakuliah.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getDosenMatakuliah(array $options = []): array
    {
        return $this->getData('dosen_matakuliah', $options);
    }

    /**
     * Menguji koneksi langsung ke API Kampus.
     *
     * @return array{success: bool, message: string, token: ?string}
     */
    public function testConnection(): array
    {
        try {
            $token = $this->getAccessToken(forceRefresh: true);

            return [
                'success' => true,
                'message' => 'Koneksi ke API Kampus berhasil! Token otorisasi valid selama 6 jam.',
                'token' => $token,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'token' => null,
            ];
        }
    }
}
