# Dosen Lecture Mode Selection & Offline Attendance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow Dosen to select a course class from their dashboard, configure the lecture session (Pertemuan 1–16, date, topic), choose between Online (Daring) or Offline (Tatap Muka) mode, and record/export student attendance on an offline attendance sheet when Offline mode is selected.

**Architecture:** A new `CourseLectureSession` model tracks meetings (1–16) for a course class. Choosing Online mode attaches/initiates a `VirtualClassroom` instance and redirects to the virtual room. Choosing Offline mode auto-populates `CourseLectureAttendance` records for all plotted students and redirects to an offline attendance view with bulk-marking and CSV export.

**Tech Stack:** Laravel 12 / PHP 8.4, Eloquent ORM, Blade Templates, Tailwind CSS, PHPUnit.

## Global Constraints
- Strictly adhere to RBAC: only the assigned Dosen (`courseClass->dosen_id === auth()->id()`) or an Admin can configure sessions and edit attendance.
- All 70 existing feature tests must continue to pass 100%.
- Format all PHP files using `vendor/bin/pint --dirty --format agent`.
- Use forward slashes in file links.

---

### Task 1: Database Migrations & Eloquent Models

**Files:**
- Create: `database/migrations/2026_11_10_080000_create_course_lecture_sessions_table.php`
- Create: `database/migrations/2026_11_10_080100_create_course_lecture_attendances_table.php`
- Create: `app/Models/CourseLectureSession.php`
- Create: `app/Models/CourseLectureAttendance.php`
- Modify: `app/Models/CourseClass.php`
- Test: `tests/Feature/CourseLectureSessionModelTest.php`

**Interfaces:**
- Produces:
  - `CourseLectureSession`: `courseClass()`, `dosen()`, `virtualClassroom()`, `attendances()`, `nextMeetingNumber()`
  - `CourseLectureAttendance`: `lectureSession()`, `student()`
  - `CourseClass::lectureSessions()`

- [ ] **Step 1: Write the failing model & migration tests**

Create `tests/Feature/CourseLectureSessionModelTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLectureSessionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lecture_session_and_link_to_course_class_and_dosen(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'topik' => 'Pengenalan Algoritma',
            'ruang' => 'Ruang 201',
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('course_lecture_sessions', [
            'id' => $session->id,
            'course_class_id' => $class->id,
            'pertemuan_ke' => 1,
            'mode' => 'offline',
        ]);

        $this->assertEquals($class->id, $session->courseClass->id);
        $this->assertEquals($dosen->id, $session->dosen->id);
        $this->assertTrue($class->lectureSessions->contains($session));
    }

    public function test_can_create_lecture_attendance_for_students(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        $attendance = CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'catatan' => 'Tepat waktu',
        ]);

        $this->assertDatabaseHas('course_lecture_attendances', [
            'id' => $attendance->id,
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $this->assertEquals($session->id, $attendance->lectureSession->id);
        $this->assertEquals($student->id, $attendance->student->id);
    }

    public function test_course_class_calculates_next_meeting_number(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Algoritma Pemrograman',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 1,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $this->assertEquals(1, $class->nextMeetingNumber());

        CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        $this->assertEquals(2, $class->fresh()->nextMeetingNumber());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/CourseLectureSessionModelTest.php`
Expected: Fails because table and classes do not exist.

- [ ] **Step 3: Create migrations and models**

1. Create migration `database/migrations/2026_11_10_080000_create_course_lecture_sessions_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lecture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan_ke')->default(1);
            $table->date('tanggal');
            $table->enum('mode', ['online', 'offline'])->default('offline');
            $table->string('topik', 255)->nullable();
            $table->string('ruang', 100)->nullable();
            $table->foreignId('virtual_classroom_id')->nullable()->constrained('virtual_classrooms')->nullOnDelete();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();

            $table->index(['course_class_id', 'pertemuan_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lecture_sessions');
    }
};
```

2. Create migration `database/migrations/2026_11_10_080100_create_course_lecture_attendances_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lecture_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_session_id')->constrained('course_lecture_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('catatan', 255)->nullable();
            $table->timestamp('waktu_presensi')->nullable();
            $table->timestamps();

            $table->unique(['lecture_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lecture_attendances');
    }
};
```

3. Create model `app/Models/CourseLectureSession.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLectureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_class_id',
        'dosen_id',
        'pertemuan_ke',
        'tanggal',
        'mode',
        'topik',
        'ruang',
        'virtual_classroom_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pertemuan_ke' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function virtualClassroom(): BelongsTo
    {
        return $this->belongsTo(VirtualClassroom::class, 'virtual_classroom_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseLectureAttendance::class, 'lecture_session_id');
    }

    public function isOnline(): bool
    {
        return $this->mode === 'online';
    }

    public function isOffline(): bool
    {
        return $this->mode === 'offline';
    }
}
```

4. Create model `app/Models/CourseLectureAttendance.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLectureAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecture_session_id',
        'student_id',
        'status',
        'catatan',
        'waktu_presensi',
    ];

    protected function casts(): array
    {
        return [
            'waktu_presensi' => 'datetime',
        ];
    }

    public function lectureSession(): BelongsTo
    {
        return $this->belongsTo(CourseLectureSession::class, 'lecture_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
```

5. Add relationships to `app/Models/CourseClass.php`:
```php
    public function lectureSessions(): HasMany
    {
        return $this->hasMany(CourseLectureSession::class, 'course_class_id')->orderBy('pertemuan_ke');
    }

    public function nextMeetingNumber(): int
    {
        $maxMeeting = $this->lectureSessions()->max('pertemuan_ke') ?? 0;
        return min(16, $maxMeeting + 1);
    }
```

- [ ] **Step 4: Run migration and test to verify it passes**

Run: `php artisan migrate`
Run: `php artisan test tests/Feature/CourseLectureSessionModelTest.php`
Expected: PASS with 3 tests passing.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ tests/Feature/CourseLectureSessionModelTest.php
git commit -m "feat(lecture): add migrations and models for lecture sessions and attendances"
```

---

### Task 2: Controller & Endpoints for Session Setup & Creation

**Files:**
- Create: `app/Http/Controllers/Dosen/DosenLectureSessionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DosenLectureSessionSetupTest.php`

**Interfaces:**
- Consumes: `CourseClass`, `CourseLectureSession`, `CourseLectureAttendance`, `VirtualClassroom`
- Produces:
  - `GET /dosen/classes/{class}/setup` -> `dosen.classes.session_setup`
  - `POST /dosen/classes/{class}/sessions` -> `dosen.classes.sessions.store`

- [ ] **Step 1: Write failing feature test for session setup and branching**

Create `tests/Feature/DosenLectureSessionSetupTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenLectureSessionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_can_view_session_setup_for_assigned_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.classes.session_setup', $class->id));

        $response->assertStatus(200);
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('Kelas A');
        $response->assertSee('Pertemuan ke-');
    }

    public function test_dosen_cannot_access_session_setup_for_other_dosen_class(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosenA->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $response = $this->actingAs($dosenB)->get(route('dosen.classes.session_setup', $class->id));
        $response->assertStatus(403);
    }

    public function test_dosen_can_create_offline_session_and_is_redirected_to_attendance(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $class->students()->attach([$student1->id, $student2->id]);

        $response = $this->actingAs($dosen)->post(route('dosen.classes.sessions.store', $class->id), [
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'topik' => 'Kontrak Kuliah & Pengenalan',
        ]);

        $this->assertDatabaseHas('course_lecture_sessions', [
            'course_class_id' => $class->id,
            'pertemuan_ke' => 1,
            'mode' => 'offline',
        ]);

        $session = CourseLectureSession::where('course_class_id', $class->id)->first();
        $this->assertNotNull($session);

        // Check both students auto-populated in attendances table
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);

        $response->assertRedirect(route('dosen.lectures.attendance', $session->id));
    }

    public function test_dosen_can_create_online_session_and_is_redirected_to_room(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $response = $this->actingAs($dosen)->post(route('dosen.classes.sessions.store', $class->id), [
            'pertemuan_ke' => 2,
            'tanggal' => '2026-11-17',
            'mode' => 'online',
            'topik' => 'Sinkronisasi Realtime',
        ]);

        $session = CourseLectureSession::where('course_class_id', $class->id)->where('pertemuan_ke', 2)->first();
        $this->assertNotNull($session);
        $this->assertNotNull($session->virtual_classroom_id);

        $response->assertRedirect(route('classroom.room', $session->virtualClassroom->room_code));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/DosenLectureSessionSetupTest.php`
Expected: Fails because routes and controller do not exist.

- [ ] **Step 3: Implement `DosenLectureSessionController` and routes**

1. Create `app/Http/Controllers/Dosen/DosenLectureSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassroomAttendance;
use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\VirtualClassroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DosenLectureSessionController extends Controller
{
    /**
     * Halaman pengaturan sesi perkuliahan (Online / Offline).
     */
    public function setup(CourseClass $class): View
    {
        $this->authorizeClassAccess($class);

        $class->load(['students', 'lectureSessions.attendances']);
        $nextMeeting = $class->nextMeetingNumber();
        $historySessions = $class->lectureSessions()->latest('pertemuan_ke')->get();

        return view('dosen.classes.session_setup', compact('class', 'nextMeeting', 'historySessions'));
    }

    /**
     * Simpan sesi perkuliahan baru dan arahkan berdasarkan mode (Online/Offline).
     */
    public function store(Request $request, CourseClass $class): RedirectResponse
    {
        $this->authorizeClassAccess($class);

        $validated = $request->validate([
            'pertemuan_ke' => 'required|integer|min:1|max:16',
            'tanggal' => 'required|date',
            'mode' => 'required|in:online,offline',
            'topik' => 'nullable|string|max:255',
        ]);

        $mode = $validated['mode'];
        $virtualClassroomId = null;

        if ($mode === 'online') {
            // Cek jika kelas sudah memiliki sesi live yang aktif
            $existingLiveRoom = $class->activeSession();
            if ($existingLiveRoom) {
                $virtualClassroomId = $existingLiveRoom->id;
            } else {
                do {
                    $roomCode = '3IN1-'.Str::upper(Str::random(5));
                } while (VirtualClassroom::where('room_code', $roomCode)->exists());

                $newRoom = VirtualClassroom::create([
                    'room_code' => $roomCode,
                    'title' => "{$class->nama_mk} ({$class->nama_kelas}) - Pertemuan {$validated['pertemuan_ke']}",
                    'dosen_id' => Auth::id(),
                    'course_class_id' => $class->id,
                    'status' => 'live',
                    'current_slide' => 1,
                    'total_slides' => 1,
                ]);

                ClassroomAttendance::create([
                    'virtual_classroom_id' => $newRoom->id,
                    'user_id' => Auth::id(),
                    'role' => 'host',
                    'joined_at' => now(),
                    'last_seen_at' => now(),
                    'status' => 'hadir',
                ]);

                $virtualClassroomId = $newRoom->id;
            }
        }

        // Buat atau perbarui CourseLectureSession
        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => Auth::id(),
            'pertemuan_ke' => $validated['pertemuan_ke'],
            'tanggal' => $validated['tanggal'],
            'mode' => $mode,
            'topik' => $validated['topik'] ?? null,
            'ruang' => $mode === 'offline' ? ($class->ruang ?? 'Ruang Kelas') : 'Ruang Virtual 3in1',
            'virtual_classroom_id' => $virtualClassroomId,
            'status' => 'aktif',
        ]);

        // Jika mode offline, inisialisasi daftar kehadiran mahasiswa terploting
        if ($mode === 'offline') {
            $studentIds = $class->students()->pluck('users.id');
            foreach ($studentIds as $studentId) {
                CourseLectureAttendance::firstOrCreate([
                    'lecture_session_id' => $session->id,
                    'student_id' => $studentId,
                ], [
                    'status' => 'hadir',
                    'waktu_presensi' => now(),
                ]);
            }

            return redirect()->route('dosen.lectures.attendance', $session->id)
                ->with('success', "Sesi perkuliahan Tatap Muka (Pertemuan {$session->pertemuan_ke}) berhasil dibuka. Silakan catat presensi mahasiswa.");
        }

        // Jika mode online, arahkan ke ruang kelas virtual
        $room = VirtualClassroom::find($virtualClassroomId);
        return redirect()->route('classroom.room', $room->room_code)
            ->with('success', "Ruang kelas virtual (Pertemuan {$session->pertemuan_ke}) aktif! Kode: {$room->room_code}");
    }

    private function authorizeClassAccess(CourseClass $class): void
    {
        if ($class->dosen_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola perkuliahan kelas ini.');
        }
    }
}
```

2. Add routes to `routes/web.php` under `role:dosen,admin`:
```php
        // Pengaturan Sesi Kuliah & Presensi Terpadu
        Route::get('/classes/{class}/setup', [DosenLectureSessionController::class, 'setup'])->name('classes.session_setup');
        Route::post('/classes/{class}/sessions', [DosenLectureSessionController::class, 'store'])->name('classes.sessions.store');
        Route::get('/lectures/{session}/attendance', [DosenLectureSessionController::class, 'showAttendance'])->name('lectures.attendance');
        Route::post('/lectures/{session}/attendance', [DosenLectureSessionController::class, 'saveAttendance'])->name('lectures.attendance.save');
        Route::get('/lectures/{session}/attendance/export', [DosenLectureSessionController::class, 'exportAttendanceCsv'])->name('lectures.attendance.export');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/DosenLectureSessionSetupTest.php`
Expected: PASS with 4 tests passing.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dosen/ routes/web.php tests/Feature/DosenLectureSessionSetupTest.php
git commit -m "feat(lecture): add controller and endpoints for lecture session setup and branching"
```

---

### Task 3: Controller & Endpoints for Offline Attendance Management & CSV Export

**Files:**
- Modify: `app/Http/Controllers/Dosen/DosenLectureSessionController.php`
- Test: `tests/Feature/DosenLectureAttendanceTest.php`

**Interfaces:**
- Produces:
  - `GET /dosen/lectures/{session}/attendance`
  - `POST /dosen/lectures/{session}/attendance`
  - `GET /dosen/lectures/{session}/attendance/export`

- [ ] **Step 1: Write failing feature test for attendance saving and CSV export**

Create `tests/Feature/DosenLectureAttendanceTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\CourseLectureAttendance;
use App\Models\CourseLectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenLectureAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_can_view_offline_attendance_sheet(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Budi Santoso', 'username' => '2023001']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $class->students()->attach($student->id);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.lectures.attendance', $session->id));

        $response->assertStatus(200);
        $response->assertSee('Lembar Presensi Tatap Muka');
        $response->assertSee('Budi Santoso');
        $response->assertSee('2023001');
    }

    public function test_dosen_can_save_updated_attendance_statuses(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $class->students()->attach([$student1->id, $student2->id]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
        ]);
        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($dosen)->post(route('dosen.lectures.attendance.save', $session->id), [
            'attendances' => [
                $student1->id => [
                    'status' => 'sakit',
                    'catatan' => 'Surat dokter terlampir',
                ],
                $student2->id => [
                    'status' => 'hadir',
                    'catatan' => '',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'sakit',
            'catatan' => 'Surat dokter terlampir',
        ]);
        $this->assertDatabaseHas('course_lecture_attendances', [
            'lecture_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'hadir',
        ]);
    }

    public function test_dosen_can_export_offline_attendance_to_csv(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Siti Nurhaliza', 'username' => '2023002']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknologi Informasi',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $session = CourseLectureSession::create([
            'course_class_id' => $class->id,
            'dosen_id' => $dosen->id,
            'pertemuan_ke' => 1,
            'tanggal' => '2026-11-10',
            'mode' => 'offline',
            'status' => 'aktif',
        ]);

        CourseLectureAttendance::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'catatan' => 'Hadir tepat waktu',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.lectures.attendance.export', $session->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('NIM/Username', $content);
        $this->assertStringContainsString('Siti Nurhaliza', $content);
        $this->assertStringContainsString('HADIR', $content);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/DosenLectureAttendanceTest.php`
Expected: Fails with unimplemented methods.

- [ ] **Step 3: Implement methods in `DosenLectureSessionController`**

Add to `app/Http/Controllers/Dosen/DosenLectureSessionController.php`:
```php
    /**
     * Tampilkan lembar presensi perkuliahan offline.
     */
    public function showAttendance(CourseLectureSession $session): View
    {
        $this->authorizeSessionAccess($session);

        $session->load(['courseClass.students', 'attendances.student']);

        // Pastikan semua mahasiswa terdaftar memiliki baris presensi
        $existingStudentIds = $session->attendances->pluck('student_id')->toArray();
        $classStudents = $session->courseClass->students;

        foreach ($classStudents as $student) {
            if (! in_array($student->id, $existingStudentIds)) {
                CourseLectureAttendance::create([
                    'lecture_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => 'hadir',
                    'waktu_presensi' => now(),
                ]);
            }
        }

        $session->load('attendances.student');
        $attendances = $session->attendances->sortBy(fn ($att) => $att->student->name ?? '');

        // Hitung statistik kehadiran
        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpa' => $attendances->where('status', 'alpa')->count(),
        ];

        return view('dosen.lectures.attendance', compact('session', 'attendances', 'stats'));
    }

    /**
     * Simpan status presensi mahasiswa.
     */
    public function saveAttendance(Request $request, CourseLectureSession $session): RedirectResponse
    {
        $this->authorizeSessionAccess($session);

        $data = $request->input('attendances', []);

        foreach ($data as $studentId => $row) {
            $status = in_array($row['status'] ?? '', ['hadir', 'izin', 'sakit', 'alpa']) ? $row['status'] : 'hadir';
            $catatan = isset($row['catatan']) ? substr(trim($row['catatan']), 0, 255) : null;

            CourseLectureAttendance::updateOrCreate([
                'lecture_session_id' => $session->id,
                'student_id' => $studentId,
            ], [
                'status' => $status,
                'catatan' => $catatan,
                'waktu_presensi' => now(),
            ]);
        }

        return redirect()->route('dosen.lectures.attendance', $session->id)
            ->with('success', 'Data presensi perkuliahan berhasil diperbarui!');
    }

    /**
     * Ekspor lembar presensi offline ke file CSV.
     */
    public function exportAttendanceCsv(CourseLectureSession $session)
    {
        $this->authorizeSessionAccess($session);

        $session->load(['courseClass', 'attendances.student']);
        $filename = 'Presensi-'.$session->courseClass->kode_mk.'-Pertemuan-'.$session->pertemuan_ke.'-'.$session->tanggal->format('Ymd').'.csv';

        $callback = function () use ($session) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Mata Kuliah', $session->courseClass->nama_mk]);
            fputcsv($handle, ['Kode MK', $session->courseClass->kode_mk]);
            fputcsv($handle, ['Kelas', $session->courseClass->nama_kelas]);
            fputcsv($handle, ['Pertemuan Ke', $session->pertemuan_ke]);
            fputcsv($handle, ['Tanggal', $session->tanggal->format('d/m/Y')]);
            fputcsv($handle, ['Mode Perkuliahan', strtoupper($session->mode)]);
            fputcsv($handle, ['Topik', $session->topik ?? '-']);
            fputcsv($handle, []);
            fputcsv($handle, ['No', 'NIM/Username', 'Nama Mahasiswa', 'Status Kehadiran', 'Catatan', 'Waktu Presensi']);

            $no = 1;
            foreach ($session->attendances as $att) {
                fputcsv($handle, [
                    $no++,
                    $att->student->username ?? '-',
                    $att->student->name ?? '-',
                    strtoupper($att->status),
                    $att->catatan ?? '-',
                    $att->waktu_presensi ? $att->waktu_presensi->format('d/m/Y H:i') : '-',
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeSessionAccess(CourseLectureSession $session): void
    {
        if ($session->dosen_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk sesi perkuliahan ini.');
        }
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/DosenLectureAttendanceTest.php`
Expected: PASS with 3 tests passing.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Dosen/ tests/Feature/DosenLectureAttendanceTest.php
git commit -m "feat(lecture): add offline attendance sheet saving and csv export"
```

---

### Task 4: Blade Views & Dosen Dashboard Integration

**Files:**
- Create: `resources/views/dosen/classes/session_setup.blade.php`
- Create: `resources/views/dosen/lectures/attendance.blade.php`
- Modify: `resources/views/dosen/dashboard.blade.php`
- Test: All tests passing (`php artisan test`)

**Interfaces:**
- Updates Dosen Dashboard class cards with a prominent primary button **"Atur Perkuliahan"** linking to `dosen.classes.session_setup`.
- Clean, responsive UI for `session_setup.blade.php` with Online / Offline cards.
- Clean, interactive UI for `attendance.blade.php` with bulk select "Tandai Semua Hadir", counters, and CSV export button.

- [ ] **Step 1: Create `session_setup.blade.php`**

Create `resources/views/dosen/classes/session_setup.blade.php`:
- Breadcrumbs navigating back to `dosen.dashboard`.
- Class summary card (Kode MK, Nama MK, Kelas, SKS, Jadwal, Ruang, Mahasiswa Terdaftar).
- Form with meeting number (1–16), date input, optional topic.
- Two distinct mode cards:
  - Online (Purple): Launches 3-in-1 virtual classroom with slides and WebRTC.
  - Offline (Emerald): Launches offline attendance sheet directly.
- History table of previous sessions for this class.

- [ ] **Step 2: Create `attendance.blade.php`**

Create `resources/views/dosen/lectures/attendance.blade.php`:
- Header banner with meeting number, date, room, class.
- Attendance counter summary pills (Total, Hadir, Izin, Sakit, Alpa).
- Quick action button: "Tandai Semua Hadir".
- Table with radio pills per student (`Hadir`, `Izin`, `Sakit`, `Alpa`) and note input.
- Footer with "Simpan Presensi", "Unduh Rekap Presensi (CSV)", and "Kembali".

- [ ] **Step 3: Update `resources/views/dosen/dashboard.blade.php`**

Update the action area in the class card:
- Include primary button: **"Atur Perkuliahan"** (`route('dosen.classes.session_setup', $class->id)`).
- If `$activeSession` is live, also provide the shortcut button: **"Masuk Kembali ke Ruang Kelas"** and **"Presensi"**.
- Keep existing assertion strings: `Buka Sesi Kelas Baru`, `Mulai Sesi Virtual` (or accessible within form), `SESI VIRTUAL SEDANG BERLANGSUNG`.

- [ ] **Step 4: Run full test suite & code styling**

Run: `php artisan test`
Run: `vendor/bin/pint --dirty --format agent`
Expected: All tests pass (70+ tests passing, 0 failures), Pint clean.

- [ ] **Step 5: Commit & Push**

```bash
git add resources/views/
git commit -m "feat(lecture): add blade views for session setup and offline attendance"
git push origin main
```
