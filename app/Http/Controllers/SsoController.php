<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function authorizeSso(Request $request)
    {
        $url = config('services.sso_unuja.url');
        $headers = [
            'Accept'  => 'application/json',
            'app_id'  => config('services.sso_unuja.app_id'),
            'dev_id'  => config('services.sso_unuja.dev_id'),
            'x-token' => config('services.sso_unuja.x_token'),
        ];

        $payload = [
            // Isi jika endpoint butuh parameter tambahan
        ];

        try {
            $response = Http::withHeaders($headers)->post($url, $payload);

            if ($response->successful()) {
                $ssoData = $response->json();

                // Ambil objek data user dari response JSON
                $userData = $ssoData['data'] ?? $ssoData;

                // Sinkronisasi data SSO dengan database lokal (tabel users)
                $user = User::updateOrCreate(
                    // Kunci pencarian: gunakan 'username' atau 'nim' dari JSON
                    ['username' => $userData['username'] ?? $userData['nim']],

                    // Data yang diupdate/dibuat
                    [
                        'name'     => $userData['nama'] ?? $userData['name'],
                        'email'    => $userData['email'] ?? null,
                        'password' => bcrypt(Str::random(16)) // Password acak
                    ]
                );

                // Daftarkan user ke sistem Auth Laravel
                Auth::login($user);

                // Simpan token SSO ke session untuk request API kampus lainnya
                if (isset($ssoData['token'])) {
                    session(['sso_token' => $ssoData['token']]);
                }

                return redirect('/dashboard')->with('success', 'Otorisasi SSO berhasil.');
            }

            // Tangani jika otorisasi ditolak oleh server SSO
            Log::warning('SSO Authorization Failed', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            return back()->withErrors(['login' => 'Kredensial SSO tidak valid atau ditolak server.']);

        } catch (\Exception $e) {
            // Tangani error jaringan atau server down
            Log::error('SSO Connection Error: ' . $e->getMessage());
            return back()->withErrors(['login' => 'Gagal terhubung ke server otorisasi UNUJA.']);
        }
    }
}
