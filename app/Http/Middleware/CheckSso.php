<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSso
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah data user dari SSO belum ada di session
        if (! session()->has('sso_user')) {
            // Tolak akses dan kembalikan ke halaman login disertai pesan error
            return redirect('/login')->withErrors([
                'access' => 'Anda harus melakukan otorisasi SSO terlebih dahulu untuk mengakses halaman ini.',
            ]);
        }

        // Jika ada, izinkan request dilanjutkan ke halaman yang dituju
        return $next($request);
    }
}
