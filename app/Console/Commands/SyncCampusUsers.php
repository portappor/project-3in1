<?php

namespace App\Console\Commands;

use App\Services\CampusSyncService;
use Illuminate\Console\Command;

class SyncCampusUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campus:sync-users
                            {--type=all : Tipe data yang disinkronkan: all, dosen, atau mahasiswa}
                            {--limit= : Batas maksimal data yang ditarik}
                            {--search= : Kata kunci pencarian nama atau kode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data Dosen dan Mahasiswa dari API Kampus ke tabel users lokal.';

    /**
     * Execute the console command.
     */
    public function handle(CampusSyncService $syncService): int
    {
        $type = strtolower((string) $this->option('type'));
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $search = $this->option('search') ? (string) $this->option('search') : '';

        if (! in_array($type, ['all', 'dosen', 'mahasiswa'], true)) {
            $this->error('Opsi --type tidak valid! Pilih: all, dosen, atau mahasiswa.');

            return self::FAILURE;
        }

        $this->info('Memulai sinkronisasi data pengguna dari API Kampus...');
        $this->line("Tipe: <comment>{$type}</comment>".($limit ? " | Limit: <comment>{$limit}</comment>" : '').($search ? " | Search: <comment>{$search}</comment>" : ''));

        $summary = [];

        try {
            if ($type === 'all' || $type === 'dosen') {
                $this->info("\n[1/2] Menyinkronkan data Dosen...");
                $options = array_filter(['search' => $search]);
                $dosenStats = $syncService->syncDosen(
                    progressCallback: fn ($role, $count, $name) => $this->output->write("\r Memproses: <comment>{$count}</comment> Dosen ({$name})"),
                    options: $options
                );
                $this->output->writeln('');
                $summary[] = ['Dosen', $dosenStats['total'], $dosenStats['created'], $dosenStats['updated'], $dosenStats['failed']];
            }

            if ($type === 'all' || $type === 'mahasiswa') {
                $stepLabel = ($type === 'all') ? '[2/2]' : '[1/1]';
                $this->info("\n{$stepLabel} Menyinkronkan data Mahasiswa...");
                $options = array_filter([
                    'limit' => $limit,
                    'search' => $search,
                ]);
                $mhsStats = $syncService->syncMahasiswa(
                    progressCallback: fn ($role, $count, $name) => $this->output->write("\r Memproses: <comment>{$count}</comment> Mahasiswa ({$name})"),
                    options: $options
                );
                $this->output->writeln('');
                $summary[] = ['Mahasiswa', $mhsStats['total'], $mhsStats['created'], $mhsStats['updated'], $mhsStats['failed']];
            }

            $this->newLine();
            $this->info('✓ Sinkronisasi selesai!');
            $this->table(['Kategori', 'Total Data', 'Dibuat (Baru)', 'Diperbarui', 'Gagal / Dilewati'], $summary);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("\nTerjadi kesalahan saat sinkronisasi: ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
