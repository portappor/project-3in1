<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CampusSyncService
{
    public function __construct(
        protected CampusApiService $apiService
    ) {}

    /**
     * Menyinkronkan data Dosen ke tabel users.
     *
     * @param  callable|array|null  $progressCallback  Callback atau array options
     * @param  array<string, mixed>  $options
     * @return array{created: int, updated: int, failed: int, total: int}
     */
    public function syncDosen(callable|array|null $progressCallback = null, array $options = []): array
    {
        if (is_array($progressCallback)) {
            $options = $progressCallback;
            $progressCallback = null;
        }

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0];

        $response = $this->apiService->getDosen($options);
        $records = $this->extractRecords($response);

        foreach ($records as $item) {
            $stats['total']++;

            try {
                $username = $item['nidn'] ?? $item['nip'] ?? $item['kode_dosen'] ?? $item['id_penduduk'] ?? $item['username'] ?? null;
                $rawName = $item['nama_penduduk'] ?? $item['nama'] ?? $item['nama_dosen'] ?? $item['name'] ?? null;

                if (! $username || ! $rawName) {
                    $stats['failed']++;
                    continue;
                }

                $gelarDepan = ! empty($item['gelar_depan']) ? trim((string) $item['gelar_depan']) . ' ' : '';
                $gelarBelakang = ! empty($item['gelar_belakang']) ? ', ' . trim((string) $item['gelar_belakang']) : '';
                $name = trim($gelarDepan . $rawName . $gelarBelakang);

                $email = $item['email'] ?? $item['email_kampus'] ?? null;
                $prodi = $item['prodi'] ?? $item['nama_prodi'] ?? $item['homebase'] ?? 'Teknik Informatika';
                $fakultas = $item['fakultas'] ?? $item['nama_fakultas'] ?? 'Fakultas Teknik';
                $statusAktif = isset($item['status']) ? (bool) $item['status'] : true;

                $this->saveUser(
                    username: (string) $username,
                    name: (string) $name,
                    role: 'dosen',
                    email: $email ? (string) $email : null,
                    prodi: $prodi ? (string) $prodi : null,
                    fakultas: $fakultas ? (string) $fakultas : null,
                    statusAktif: $statusAktif,
                    stats: $stats
                );

                if ($progressCallback) {
                    $progressCallback('dosen', $stats['total'], $name);
                }
            } catch (\Throwable $e) {
                Log::warning('Error syncing dosen item', ['item' => $item, 'error' => $e->getMessage()]);
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Menyinkronkan data Mahasiswa ke tabel users dengan dukungan pagination.
     *
     * @param  callable|array|null  $progressCallback  Callback atau array options
     * @param  array{limit?: int, page?: int, search?: string}  $options
     * @return array{created: int, updated: int, failed: int, total: int}
     */
    public function syncMahasiswa(callable|array|null $progressCallback = null, array $options = []): array
    {
        if (is_array($progressCallback)) {
            $options = $progressCallback;
            $progressCallback = null;
        }

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0];
        $limit = $options['limit'] ?? null;
        $currentPage = $options['page'] ?? 1;

        while (true) {
            $params = [
                'pagination' => 'on',
                'page' => $currentPage,
            ];

            if (! empty($options['search'])) {
                $params['search'] = $options['search'];
            }

            $response = $this->apiService->getMahasiswa($params);
            $records = $this->extractRecords($response);

            if (empty($records)) {
                break;
            }

            foreach ($records as $item) {
                $stats['total']++;

                try {
                    $username = $item['nim'] ?? $item['npm'] ?? $item['username'] ?? null;
                    $name = $item['nama'] ?? $item['nama_mahasiswa'] ?? $item['name'] ?? null;

                    if (! $username || ! $name) {
                        $stats['failed']++;
                        continue;
                    }

                    $email = $item['email'] ?? null;
                    $prodi = $item['prodi'] ?? $item['nama_prodi'] ?? null;
                    $fakultas = $item['fakultas'] ?? $item['nama_fakultas'] ?? null;
                    $statusAktif = isset($item['status']) ? (bool) $item['status'] : true;

                    $this->saveUser(
                        username: (string) $username,
                        name: (string) $name,
                        role: 'mahasiswa',
                        email: $email ? (string) $email : null,
                        prodi: $prodi ? (string) $prodi : null,
                        fakultas: $fakultas ? (string) $fakultas : null,
                        statusAktif: $statusAktif,
                        stats: $stats
                    );

                    if ($progressCallback) {
                        $progressCallback('mahasiswa', $stats['total'], $name);
                    }

                    if ($limit && $stats['total'] >= $limit) {
                        return $stats;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error syncing mahasiswa item', ['item' => $item, 'error' => $e->getMessage()]);
                    $stats['failed']++;
                }
            }

            // Periksa jika ada indikator halaman terakhir
            $lastPage = $response['meta']['last_page']
                ?? $response['pagination']['total_pages']
                ?? null;

            if ($lastPage && $currentPage >= (int) $lastPage) {
                break;
            }

            // Jika respons mengembalikan kurang dari 10 (ukuran halaman default), kemungkinan sudah halaman terakhir
            if (count($records) < 10) {
                break;
            }

            $currentPage++;
        }

        return $stats;
    }

    /**
     * Menyimpan atau memperbarui data user secara selektif.
     *
     * @param  array{created: int, updated: int, failed: int, total: int}  $stats
     */
    protected function saveUser(
        string $username,
        string $name,
        string $role,
        ?string $email,
        ?string $prodi,
        ?string $fakultas,
        bool $statusAktif,
        array &$stats
    ): void {
        $defaultPassword = config('services.campus_api.default_password');
        $rawPassword = ! empty($defaultPassword) ? (string) $defaultPassword : $username;

        $user = User::where('username', $username)->first();

        if (! $user) {
            User::create([
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'prodi' => $prodi,
                'fakultas' => $fakultas,
                'status_aktif' => $statusAktif,
                'password' => Hash::make($rawPassword),
                'password_changed_at' => null,
            ]);

            $stats['created']++;
        } else {
            $user->update([
                'name' => $name,
                'email' => $email ?? $user->email,
                'role' => $role,
                'prodi' => $prodi ?? $user->prodi,
                'fakultas' => $fakultas ?? $user->fakultas,
                'status_aktif' => $statusAktif,
            ]);

            $stats['updated']++;
        }
    }

    /**
     * Mengekstrak daftar data array dari berbagai kemungkinan pembungkus respons JSON.
     *
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    protected function extractRecords(array $response): array
    {
        // Format paginator Laravel: response.data.data
        if (isset($response['data']['data']) && is_array($response['data']['data'])) {
            return $response['data']['data'];
        }

        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        if (isset($response['results']) && is_array($response['results'])) {
            return $response['results'];
        }

        if (array_is_list($response)) {
            /** @var array<int, array<string, mixed>> $response */
            return $response;
        }

        return [];
    }
}

