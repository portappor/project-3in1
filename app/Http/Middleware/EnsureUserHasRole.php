<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Membatasi akses rute berdasarkan role pengguna (dosen atau mahasiswa).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->withErrors(['auth' => 'Silakan login terlebih dahulu.']);
        }

        if (! in_array($request->user()->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden: Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
            }

            // Redirect pengguna ke dashboard yang sesuai dengan perannya disertai pesan error
            if ($request->user()->isAdmin()) {
                $targetRoute = 'admin.dashboard';
            } elseif ($request->user()->isDosen()) {
                $targetRoute = 'dosen.dashboard';
            } else {
                $targetRoute = 'mahasiswa.dashboard';
            }

            return redirect()->route($targetRoute)->with('error', 'Akses Ditolak: Halaman ini hanya dapat diakses oleh peran: '.implode(', ', $roles).'.');
        }

        return $next($request);
    }
}
