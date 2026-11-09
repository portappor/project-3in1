<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VirtualClassroomUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Dosen (Host)
        User::updateOrCreate(
            ['username' => '198001012010121001'],
            [
                'name' => 'Dr. Ir. Ahmad Syahid, M.Kom',
                'email' => 'ahmad.syahid@kampus.ac.id',
                'role' => 'dosen',
                'prodi' => 'Teknik Informatika',
                'fakultas' => 'Fakultas Teknik',
                'status_aktif' => true,
                'password' => Hash::make('password'),
            ]
        );

        // 2. Akun Mahasiswa (Participant)
        User::updateOrCreate(
            ['username' => '2023001'],
            [
                'name' => 'Muhammad Rifqi',
                'email' => 'rifqi2023001@mhs.kampus.ac.id',
                'role' => 'mahasiswa',
                'prodi' => 'Teknik Informatika',
                'fakultas' => 'Fakultas Teknik',
                'status_aktif' => true,
                'password' => Hash::make('password'),
            ]
        );

        // 3. Akun Administrator (Master Akses API & RBAC)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator 3in1',
                'email' => 'admin@kampus.ac.id',
                'role' => 'admin',
                'prodi' => 'Biro Sistem Informasi',
                'fakultas' => 'Pusat Komputer',
                'status_aktif' => true,
                'password' => Hash::make('password'),
            ]
        );
    }
}
