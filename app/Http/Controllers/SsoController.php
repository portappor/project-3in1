<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Default TTL untuk token SSO: 2 jam (7200 detik).
     */
    public const DEFAULT_TOKEN_TTL = 7200;

    /**
     * Menghandle otorisasi SSO Si Permata UNUJA dan menyimpan token ke Redis.
     */
    public function authorizeSso(Request $request): RedirectResponse
    {
        $url = (string) config('services.sso_unuja.url');
        $headers = [
            'Accept' => 'application/json',
            'app_id' => (string) config('services.sso_unuja.app_id'),
            'dev_id' => (string) config('services.sso_unuja.dev_id'),
            'x-token' => (string) config('services.sso_unuja.x_token'),
        ];

        $payload = [
            // Parameter tambahan jika diperlukan
        ];

        try {
            $response = Http::withHeaders($headers)->timeout(10)->post($url, $payload);

            if ($response->successful()) {
                $ssoData = $response->json();
                $userData = $ssoData['data'] ?? $ssoData;

                $identifier = (string) ($userData['username'] ?? $userData['nim'] ?? $userData['nidn'] ?? '');

                if (empty($identifier)) {
                    return back()->withErrors(['login' => 'Identitas akun tidak ditemukan dari server SSO.']);
                }

                // Sinkronisasi data SSO dengan database lokal (tabel users)
                $user = User::updateOrCreate(
                    ['username' => $identifier],
                    [
                        'name' => $userData['nama'] ?? $userData['name'] ?? 'Pengguna UNUJA',
                        'email' => $userData['email'] ?? null,
                        'role' => ! empty($userData['nidn']) ? 'dosen' : 'mahasiswa',
                        'password' => bcrypt(Str::random(32)),
                    ]
                );

                // Daftarkan user ke sistem Auth Laravel
                Auth::login($user);
                $request->session()->regenerate();

                // Simpan token SSO ke Redis dengan TTL tertentu (misal 7200 detik)
                if (isset($ssoData['token'])) {
                    $ttl = isset($ssoData['expires_in']) ? (int) $ssoData['expires_in'] : self::DEFAULT_TOKEN_TTL;
                    $this->storeSsoToken($user->id, (string) $ssoData['token'], $ttl);
                    session(['sso_token' => $ssoData['token']]);
                }

                return redirect()->intended(
                    $user->isDosen() ? route('dosen.dashboard') : route('mahasiswa.dashboard')
                )->with('success', 'Otorisasi SSO Si Permata berhasil.');
            }

            Log::warning('SSO Authorization Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return back()->withErrors(['login' => 'Kredensial SSO tidak valid atau ditolak server.']);

        } catch (\Throwable $e) {
            Log::error('SSO Connection Error: '.$e->getMessage());

            return back()->withErrors(['login' => 'Gagal terhubung ke server otorisasi UNUJA.']);
        }
    }

    /**
     * Menyimpan token SSO ke dalam Redis dengan TTL tertentu.
     */
    public function storeSsoToken(int|string $userId, string $token, int $ttlSeconds = self::DEFAULT_TOKEN_TTL): void
    {
        $cacheKey = "sso:token:user:{$userId}";

        Cache::store('redis')->put($cacheKey, [
            'token' => $token,
            'stored_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds($ttlSeconds)->toIso8601String(),
        ], $ttlSeconds);
    }

    /**
     * Mengambil token SSO dari Redis.
     */
    public function getSsoToken(int|string $userId): ?string
    {
        $cacheKey = "sso:token:user:{$userId}";
        $cachedData = Cache::store('redis')->get($cacheKey);

        if (! $cachedData) {
            return null;
        }

        return is_array($cachedData) ? ($cachedData['token'] ?? null) : (string) $cachedData;
    }

    /**
     * Menghapus (revoke) token SSO dari Redis.
     */
    public function revokeSsoToken(int|string $userId): bool
    {
        $cacheKey = "sso:token:user:{$userId}";

        return Cache::store('redis')->forget($cacheKey);
    }
}
