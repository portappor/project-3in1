<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CampusApiService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Dashboard Utama Administrator 3in1.
     */
    public function dashboard(CampusApiService $campusApi)
    {
        $users = User::latest()->paginate(15);

        $counts = [
            'total' => User::count(),
            'dosen' => User::where('role', 'dosen')->count(),
            'mahasiswa' => User::where('role', 'mahasiswa')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];

        $connectionTest = $campusApi->testConnection();

        return view('admin.dashboard', compact('users', 'counts', 'connectionTest'));
    }

    /**
     * Mengatur peran (role) dan status aktif pengguna.
     */
    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,dosen,mahasiswa',
            'status_aktif' => 'required|boolean',
        ]);

        $user->update($validated);

        return back()->with('success', "Hak akses untuk pengguna {$user->name} ({$user->username}) berhasil diperbarui menjadi {$validated['role']}!");
    }
}
