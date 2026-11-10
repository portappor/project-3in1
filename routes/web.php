<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCourseClassController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampusDataController;
use App\Http\Controllers\Dosen\DosenLectureSessionController;
use App\Http\Controllers\VirtualClassroomController;
use Illuminate\Support\Facades\Route;

// Redirect halaman depan
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return auth()->user()->isDosen()
            ? redirect()->route('dosen.dashboard')
            : redirect()->route('mahasiswa.dashboard');
    }

    return redirect()->route('login');
});

// Autentikasi Pengguna
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute Terproteksi Berdasarkan Role (RBAC)
Route::middleware(['auth'])->group(function () {

    // AREA ADMIN (PENGATURAN AKSES & MASTER DATA API KAMPUS)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/users/{user}/update-role', [AdminController::class, 'updateUserRole'])->name('users.update_role');
        Route::get('/campus-data', [CampusDataController::class, 'index'])->name('campus_data');
        Route::post('/campus-data/sync', [CampusDataController::class, 'syncNow'])->name('campus_data.sync');

        // Manajemen Kelas & Ploting Mahasiswa
        Route::resource('classes', AdminCourseClassController::class)->except(['show']);
        Route::get('/classes/{class}/plotting', [AdminCourseClassController::class, 'plotting'])->name('classes.plotting');
        Route::post('/classes/{class}/plotting', [AdminCourseClassController::class, 'savePlotting'])->name('classes.plotting.save');
    });

    // AREA DOSEN (HOST) & ADMIN
    Route::middleware(['role:dosen,admin'])->prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/dashboard', [VirtualClassroomController::class, 'dosenDashboard'])->name('dashboard');
        Route::post('/classroom/create', [VirtualClassroomController::class, 'createRoom'])->name('classroom.create');
        Route::post('/classroom/{room_code}/presentation', [VirtualClassroomController::class, 'uploadPresentation'])->name('classroom.presentation');
        Route::post('/classroom/{room_code}/slide', [VirtualClassroomController::class, 'updateSlide'])->name('classroom.slide');
        Route::post('/classroom/{room_code}/mute-all', [VirtualClassroomController::class, 'toggleMuteAll'])->name('classroom.mute_all');
        Route::get('/classroom/{room_code}/attendance/export', [VirtualClassroomController::class, 'exportAttendance'])->name('classroom.export_attendance');
        Route::get('/classroom/{room_code}/chat/export', [VirtualClassroomController::class, 'exportChat'])->name('classroom.export_chat');

        // Pengaturan Sesi Perkuliahan (Online/Offline) & Presensi
        Route::get('/classes/{class}/setup', [DosenLectureSessionController::class, 'setup'])->name('classes.session_setup');
        Route::post('/classes/{class}/sessions', [DosenLectureSessionController::class, 'store'])->name('classes.sessions.store');
        Route::get('/lectures/{session}/attendance', [DosenLectureSessionController::class, 'showAttendance'])->name('lectures.attendance');
        Route::post('/lectures/{session}/attendance', [DosenLectureSessionController::class, 'saveAttendance'])->name('lectures.attendance.save');
        Route::get('/lectures/{session}/attendance/export', [DosenLectureSessionController::class, 'exportAttendanceCsv'])->name('lectures.attendance.export');
    });

    // AREA MAHASISWA (PARTICIPANT)
    Route::middleware(['role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/dashboard', [VirtualClassroomController::class, 'mahasiswaDashboard'])->name('dashboard');
        Route::post('/classroom/join', [VirtualClassroomController::class, 'joinRoom'])->name('classroom.join');
    });

    // RUANG KELAS VIRTUAL & INTERAKSI SINKRON (Host & Participant)
    Route::get('/room/{room_code}', [VirtualClassroomController::class, 'showRoom'])->name('classroom.room');
    Route::get('/classroom/{room_code}/state', [VirtualClassroomController::class, 'getRoomState'])->name('classroom.state');
    Route::match(['GET', 'OPTIONS'], '/classroom/{room_code}/presentation-file', [VirtualClassroomController::class, 'getPresentationFile'])->name('classroom.presentation.file');
    Route::post('/classroom/{room_code}/chat', [VirtualClassroomController::class, 'sendChat'])->name('classroom.chat');
    Route::post('/classroom/{room_code}/raise-hand', [VirtualClassroomController::class, 'toggleRaiseHand'])->name('classroom.raise_hand');
    Route::post('/classroom/{room_code}/mic', [VirtualClassroomController::class, 'toggleMic'])->name('classroom.mic');

    // DATA REFERENSI KAMPUS (Khusus Role Admin)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/campus-data', [CampusDataController::class, 'index'])->name('campus.data');
        Route::post('/campus-data/sync', [CampusDataController::class, 'syncNow'])->name('campus.sync');
    });
});
