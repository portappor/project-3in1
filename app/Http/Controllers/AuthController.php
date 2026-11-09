<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CampusApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Menampilkan form login.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Memproses autentikasi pengguna.
     */
    public function login(Request $request, CampusApiService $campusApi)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'NIM / NIDN (Username) wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        // Proteksi Brute-Force: Maksimal 10 percobaan salah per menit
        $throttleKey = Str::lower($username).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $remember = $request->boolean('remember');

        // 1. Cari user di database lokal berdasarkan username (NIM / NIDN)
        $user = User::where('username', $username)->first();

        // 2. Jika user belum ada di database lokal, cek langsung ke API Kampus UNUJA (On-Demand Provisioning)
        if (! $user) {
            try {
                // 2a. Cari pada data Dosen di API Kampus (berdasarkan NIDN / NIP / ID Penduduk)
                $dosenResult = $campusApi->getDosen(['search' => $username]);
                $dosenRecords = $dosenResult['data']['data'] ?? $dosenResult['data'] ?? [];
                $matchedDosen = collect($dosenRecords)->first(function ($d) use ($username) {
                    $nidn = (string) ($d['nidn'] ?? '');
                    $nip = (string) ($d['nip'] ?? '');
                    $kodeDosen = (string) ($d['kode_dosen'] ?? '');
                    $idPenduduk = (string) ($d['id_penduduk'] ?? '');
                    $userField = (string) ($d['username'] ?? '');

                    return $nidn === $username
                        || $nip === $username
                        || $kodeDosen === $username
                        || $idPenduduk === $username
                        || $userField === $username;
                });

                if ($matchedDosen) {
                    $rawName = $matchedDosen['nama_penduduk'] ?? $matchedDosen['nama'] ?? $matchedDosen['nama_dosen'] ?? $matchedDosen['name'] ?? 'Dosen UNUJA';
                    $gelarDepan = ! empty($matchedDosen['gelar_depan']) ? trim((string) $matchedDosen['gelar_depan']).' ' : '';
                    $gelarBelakang = ! empty($matchedDosen['gelar_belakang']) ? ', '.trim((string) $matchedDosen['gelar_belakang']) : '';
                    $name = trim($gelarDepan.$rawName.$gelarBelakang);

                    $user = User::create([
                        'username' => (string) ($matchedDosen['nidn'] ?? $matchedDosen['nip'] ?? $username),
                        'name' => $name,
                        'email' => $matchedDosen['email'] ?? $matchedDosen['email_kampus'] ?? null,
                        'role' => 'dosen',
                        'prodi' => $matchedDosen['prodi'] ?? $matchedDosen['nama_prodi'] ?? $matchedDosen['homebase'] ?? 'Teknik Elektro',
                        'fakultas' => $matchedDosen['fakultas'] ?? $matchedDosen['nama_fakultas'] ?? 'Fakultas Teknik',
                        'status_aktif' => true,
                        'password' => Hash::make($password),
                    ]);
                } else {
                    // 2b. Cari pada data Mahasiswa di API Kampus (berdasarkan NIM)
                    $searchResult = $campusApi->getMahasiswa(['search' => $username]);
                    $records = $searchResult['data']['data'] ?? $searchResult['data'] ?? [];
                    $matchedMhs = collect($records)->first(function ($m) use ($username) {
                        return (string) ($m['nim'] ?? '') === $username;
                    });

                    if ($matchedMhs) {
                        $user = User::create([
                            'username' => (string) $matchedMhs['nim'],
                            'name' => $matchedMhs['nama'] ?? 'Mahasiswa UNUJA',
                            'email' => $matchedMhs['email'] ?? null,
                            'role' => 'mahasiswa',
                            'prodi' => $matchedMhs['prodi'] ?? 'Teknik',
                            'fakultas' => $matchedMhs['fakultas'] ?? 'Fakultas Teknik',
                            'status_aktif' => true,
                            'password' => Hash::make($password),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika API kampus sedang tidak merespons
            }
        }

        // 3. Verifikasi Password Pengguna
        if ($user) {
            $defaultPassword = config('services.campus_api.default_password');

            // Pengguna (Dosen & Mahasiswa) dapat masuk menggunakan:
            // a. Password yang tersimpan
            // b. NIDN / NIM masing-masing (NIDN/NIM sebagai password default awal)
            // c. Kata sandi 'password'
            // d. Konfigurasi CAMPUS_DEFAULT_PASSWORD di .env
            $isPasswordValid = Hash::check($password, $user->password)
                || ($password === (string) $user->username)
                || ($password === 'password')
                || (! empty($defaultPassword) && $password === (string) $defaultPassword);

            if ($isPasswordValid) {
                // Sinkronkan hash kata sandi baru jika pengguna masuk dengan kata sandi default yang sah
                if (! Hash::check($password, $user->password)) {
                    $user->password = Hash::make($password);
                    $user->save();
                }

                RateLimiter::clear($throttleKey);
                Auth::login($user, $remember);
                $request->session()->regenerate();

                return $this->redirectBasedOnRole($user);
            }
        }

        RateLimiter::hit($throttleKey, 60);

        return back()
            ->withInput($request->only('username', 'remember'))
            ->withErrors([
                'username' => 'NIM / NIDN atau kata sandi tidak cocok. Gunakan NIDN / NIM Anda sebagai Username dan Password awal.',
            ]);
    }

    /**
     * Logout pengguna dari sesi aplikasi.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem 3in1.');
    }

    /**
     * Mengarahkan pengguna ke dashboard sesuai perannya.
     */
    protected function redirectBasedOnRole($user)
    {
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->isDosen()) {
            return redirect()->intended(route('dosen.dashboard'));
        }

        return redirect()->intended(route('mahasiswa.dashboard'));
    }
}
