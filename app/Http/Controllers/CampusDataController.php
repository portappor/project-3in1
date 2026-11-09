<?php

namespace App\Http\Controllers;

use App\Services\CampusApiService;
use App\Services\CampusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampusDataController extends Controller
{
    public function __construct(
        protected CampusApiService $apiService,
        protected CampusSyncService $syncService
    ) {}

    /**
     * Halaman Utama Data Referensi Akademik Kampus (7 Kategori).
     */
    public function index(Request $request)
    {
        $currentTab = $request->input('tab', 'dosen');
        $search = trim((string) $request->input('search', ''));
        $selectedProdi = trim((string) $request->input('prodi', ''));
        $data = [];
        $isLive = false;
        $errorMessage = null;

        $prodiList = [
            'Teknik Informatika',
            'Teknik Elektro',
            'Teknologi Informasi',
            'Teknik Industri Pertanian',
        ];

        $connectionTest = $this->apiService->testConnection();

        try {
            switch ($currentTab) {
                case 'dosen':
                    $data = $this->apiService->getDosen(array_filter(['search' => $search, 'prodi' => $selectedProdi]));
                    $isLive = true;
                    break;

                case 'mahasiswa':
                    $data = $this->apiService->getMahasiswa(array_filter(['search' => $search, 'prodi' => $selectedProdi, 'pagination' => 'on']));
                    $isLive = true;
                    break;

                case 'prodi_fakultas':
                    $prodi = $this->apiService->getProdi();
                    $fakultas = $this->apiService->getFakultas();
                    $data = [
                        'prodi' => $prodi['data']['data'] ?? $prodi['data'] ?? [],
                        'fakultas' => $fakultas['data']['data'] ?? $fakultas['data'] ?? [],
                    ];
                    $isLive = true;
                    break;

                case 'matakuliah':
                    $data = $this->apiService->getMatakuliah(array_filter(['search' => $search, 'prodi' => $selectedProdi]));
                    $isLive = true;
                    break;

                case 'jadwal':
                    $data = $this->apiService->getJadwal(array_filter(['search' => $search, 'prodi' => $selectedProdi]));
                    $isLive = true;
                    break;

                case 'semester':
                    $data = $this->apiService->getTahunAjaran(array_filter(['search' => $search]));
                    $isLive = true;
                    break;

                default:
                    $currentTab = 'dosen';
                    $data = $this->apiService->getDosen();
                    $isLive = true;
                    break;
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::info("Campus API live request for [{$currentTab}] returned: ".$errorMessage);
            $data = $this->getFallbackData($currentTab, $selectedProdi);
            $isLive = false;
        }

        return view('campus_data.index', compact(
            'currentTab',
            'search',
            'selectedProdi',
            'prodiList',
            'data',
            'isLive',
            'errorMessage',
            'connectionTest'
        ));
    }

    /**
     * Sinkronisasi data Mahasiswa dan Dosen dari tombol di Web UI.
     */
    public function syncNow(Request $request)
    {
        try {
            $type = $request->input('type', 'all');
            $summary = [];

            if ($type === 'all' || $type === 'dosen') {
                $summary['dosen'] = $this->syncService->syncDosen();
            }

            if ($type === 'all' || $type === 'mahasiswa') {
                $summary['mahasiswa'] = $this->syncService->syncMahasiswa(options: ['limit' => 50]);
            }

            $msgParts = [];
            if (isset($summary['dosen'])) {
                $msgParts[] = "Dosen: {$summary['dosen']['total']} data ({$summary['dosen']['created']} baru, {$summary['dosen']['updated']} diperbarui)";
            }
            if (isset($summary['mahasiswa'])) {
                $msgParts[] = "Mahasiswa: {$summary['mahasiswa']['total']} data ({$summary['mahasiswa']['created']} baru, {$summary['mahasiswa']['updated']} diperbarui)";
            }

            $detailMsg = ! empty($msgParts) ? ' ['.implode(', ', $msgParts).']' : '';

            return back()->with('success', 'Sinkronisasi data ke database lokal users berhasil diselesaikan!'.$detailMsg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal melakukan sinkronisasi: '.$e->getMessage());
        }
    }

    /**
     * Data mock/fallback agar UI tetap dapat dilihat dengan rapi jika API kampus sedang offline atau kredensial masih disensor.
     */
    protected function getFallbackData(string $tab, ?string $prodi = null): array
    {
        $res = match ($tab) {
            'dosen' => [
                'data' => [
                    ['nidn' => '0712038001', 'nama' => 'Dr. Ir. Ahmad Syahid, M.Kom', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Fakultas Teknik', 'email' => 'ahmad@kampus.ac.id', 'status' => 'Aktif'],
                    ['nidn' => '0715058502', 'nama' => 'Nurul Hidayati, M.T', 'nama_prodi' => 'Sistem Informasi', 'nama_fakultas' => 'Fakultas Teknik', 'email' => 'nurul@kampus.ac.id', 'status' => 'Aktif'],
                    ['nidn' => '0722088803', 'nama' => 'M. Zainal Fanani, M.Kom', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Fakultas Teknik', 'email' => 'zainal@kampus.ac.id', 'status' => 'Aktif'],
                    ['nidn' => '0701019004', 'nama' => 'Siti Aminah, M.Pd', 'nama_prodi' => 'Pendidikan Bahasa Inggris', 'nama_fakultas' => 'FKIP', 'email' => 'siti@kampus.ac.id', 'status' => 'Aktif'],
                ],
            ],
            'mahasiswa' => [
                'data' => [
                    ['nim' => '2023001', 'nama' => 'Muhammad Rifqi', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Fakultas Teknik', 'angkatan' => '2023', 'status' => 'Aktif'],
                    ['nim' => '2023012', 'nama' => 'Fatimatuzzahra', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Fakultas Teknik', 'angkatan' => '2023', 'status' => 'Aktif'],
                    ['nim' => '2023045', 'nama' => 'Dimas Maulana', 'nama_prodi' => 'Sistem Informasi', 'nama_fakultas' => 'Fakultas Teknik', 'angkatan' => '2023', 'status' => 'Aktif'],
                    ['nim' => '2022089', 'nama' => 'Laila Nur Azizah', 'nama_prodi' => 'Teknik Elektro', 'nama_fakultas' => 'Fakultas Teknik', 'angkatan' => '2022', 'status' => 'Aktif'],
                ],
            ],
            'prodi_fakultas' => [
                'fakultas' => [
                    ['id' => '1', 'kode' => 'FT', 'nama' => 'Fakultas Teknik'],
                    ['id' => '2', 'kode' => 'FSH', 'nama' => 'Fakultas Syariah & Hukum'],
                    ['id' => '3', 'kode' => 'FKIP', 'nama' => 'Fakultas Keguruan & Ilmu Pendidikan'],
                    ['id' => '4', 'kode' => 'FKes', 'nama' => 'Fakultas Kesehatan'],
                ],
                'prodi' => [
                    ['id' => '101', 'kode' => 'TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik'],
                    ['id' => '102', 'kode' => 'SI', 'nama' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik'],
                    ['id' => '103', 'kode' => 'TE', 'nama' => 'Teknik Elektro', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik'],
                    ['id' => '104', 'kode' => 'PBI', 'nama' => 'Pendidikan Bahasa Inggris', 'jenjang' => 'S1', 'fakultas' => 'FKIP'],
                ],
            ],
            'matakuliah' => [
                'data' => [
                    ['kode' => 'TIF201', 'nama' => 'Pemrograman Web Lanjut', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknik Informatika'],
                    ['kode' => 'TIF302', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester' => 5, 'prodi' => 'Teknik Informatika'],
                    ['kode' => 'TIF105', 'nama' => 'Struktur Data & Algoritma', 'sks' => 3, 'semester' => 2, 'prodi' => 'Teknik Informatika'],
                    ['kode' => 'TIF408', 'nama' => 'Kecerdasan Buatan (AI)', 'sks' => 3, 'semester' => 6, 'prodi' => 'Teknik Informatika'],
                    ['kode' => 'TEL101', 'nama' => 'Rangkaian Listrik & Elektronika', 'sks' => 3, 'semester' => 2, 'prodi' => 'Teknik Elektro'],
                    ['kode' => 'TEL205', 'nama' => 'Sistem Mikroprosesor & IoT', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknik Elektro'],
                    ['kode' => 'TIN201', 'nama' => 'Komputasi Awan (Cloud Computing)', 'sks' => 3, 'semester' => 4, 'prodi' => 'Teknologi Informasi'],
                ],
            ],
            'jadwal' => [
                'data' => [
                    ['kode_mk' => 'TIF201', 'nama_mk' => 'Pemrograman Web Lanjut', 'hari' => 'Senin', 'jam' => '08:00 - 10:30', 'dosen' => 'Dr. Ir. Ahmad Syahid, M.Kom', 'prodi' => 'Teknik Informatika', 'ruang' => 'Ruang Kelas Virtual 3in1 (A)', 'kelas' => 'TI-4A'],
                    ['kode_mk' => 'TEL101', 'nama_mk' => 'Rangkaian Listrik & Elektronika', 'hari' => 'Senin', 'jam' => '10:30 - 13:00', 'dosen' => 'Ahmad Muhtadi, M.Kom', 'prodi' => 'Teknik Elektro', 'ruang' => 'Ruang Kelas Virtual 3in1 (B)', 'kelas' => 'TE-2A'],
                    ['kode_mk' => 'TIF302', 'nama_mk' => 'Rekayasa Perangkat Lunak', 'hari' => 'Selasa', 'jam' => '10:30 - 13:00', 'dosen' => 'Nurul Hidayati, M.T', 'prodi' => 'Teknik Informatika', 'ruang' => 'Ruang Kelas Virtual 3in1 (C)', 'kelas' => 'TI-5B'],
                    ['kode_mk' => 'TEL205', 'nama_mk' => 'Sistem Mikroprosesor & IoT', 'hari' => 'Selasa', 'jam' => '10:30 - 13:00', 'dosen' => 'Fuad Hasan, M.Kom', 'prodi' => 'Teknik Elektro', 'ruang' => 'Ruang Kelas Virtual 3in1 (D)', 'kelas' => 'TE-4A'],
                    ['kode_mk' => 'TIF408', 'nama_mk' => 'Kecerdasan Buatan (AI)', 'hari' => 'Kamis', 'jam' => '13:00 - 15:30', 'dosen' => 'M. Zainal Fanani, M.Kom', 'prodi' => 'Teknik Informatika', 'ruang' => 'Ruang Kelas Virtual 3in1 (A)', 'kelas' => 'TI-6A'],
                ],
            ],
            'semester' => [
                'data' => [
                    ['id' => '20251', 'tahun' => '2025/2026', 'semester' => 'Ganjil', 'status' => 'Selesai', 'tgl_mulai' => '01-09-2025', 'tgl_selesai' => '15-01-2026'],
                    ['id' => '20252', 'tahun' => '2025/2026', 'semester' => 'Genap', 'status' => 'Aktif (Sedang Berjalan)', 'tgl_mulai' => '01-02-2026', 'tgl_selesai' => '30-06-2026'],
                    ['id' => '20261', 'tahun' => '2026/2027', 'semester' => 'Ganjil', 'status' => 'Akan Datang', 'tgl_mulai' => '01-09-2026', 'tgl_selesai' => '15-01-2027'],
                ],
            ],
            default => ['data' => []],
        };

        if ($prodi && isset($res['data'])) {
            $p = strtolower($prodi);
            $res['data'] = array_values(array_filter($res['data'], function ($item) use ($p) {
                return str_contains(strtolower($item['prodi'] ?? $item['nama_prodi'] ?? ''), $p);
            }));
        }

        return $res;
    }
}
