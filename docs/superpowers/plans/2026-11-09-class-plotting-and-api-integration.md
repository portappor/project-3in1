# Class Plotting & API Course Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fitur ploting kelas matakuliah di area Admin yang menghubungkan 795 matakuliah resmi dari API Kampus UNUJA dengan Dosen Pengampu dan Mahasiswa terdaftar, serta mengintegrasikannya dengan Dashboard Dosen (1-klik mulai sesi), Dashboard Mahasiswa (1-klik masuk sesi live), dan pembatasan akses ruang kelas virtual berbasis RBAC terdaftar.

**Architecture:** Menggunakan skema relasional terstruktur `course_classes` dan tabel pivot `course_class_students`, terhubung dengan `virtual_classrooms.course_class_id`. Memperbarui `CampusApiService` untuk menarik matakuliah via parameter `id_sms` per prodi dan menyimpannya pada Cache. Di sisi UI, menyediakan antarmuka modern di Admin (`/admin/classes`) untuk plotting masal mahasiswa, serta widget interaktif di Dashboard Dosen dan Mahasiswa.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent ORM, MySQL/SQLite, Tailwind CSS, Blade Templates, PHPUnit.

## Global Constraints
- Bahasa antarmuka: Bahasa Indonesia yang rapi, profesional, dan ramah pengguna akademik.
- Format kode: Sesuai standar Laravel Pint (`vendor/bin/pint --dirty --format agent`).
- Pengujian: Setiap perubahan logika harus diverifikasi dengan pengujian otomatis PHPUnit (`php artisan test`).
- Integritas data: Menggunakan foreign key cascade untuk relasi mahasiswa dan kelas.

---

### Task 1: API Enhancement for 795 Matakuliah (`CampusApiService`)

**Files:**
- Modify: `app/Services/CampusApiService.php:255-285`
- Test: `tests/Feature/CampusApiMatakuliahTest.php`

**Interfaces:**
- Produces: `CampusApiService::getMatakuliah(array $options = []): array` yang mengembalikan seluruh 795 matakuliah jika tanpa filter, atau difilter berdasarkan `prodi` / `search`.

- [ ] **Step 1: Write the failing test for CampusApiService live matakuliah fetching**

Create `tests/Feature/CampusApiMatakuliahTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Services\CampusApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusApiMatakuliahTest extends TestCase
{
    public function test_campus_api_service_fetches_matakuliah_for_all_engineering_prodi(): void
    {
        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['pagination' => 'off']);

        $this->assertIsArray($result);
        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        // Memastikan lebih dari katalog statis 12 matakuliah (saat live server mencapai 700+)
        $this->assertGreaterThan(20, count($records));

        $first = $records[0];
        $this->assertArrayHasKey('kode', $first);
        $this->assertArrayHasKey('nama', $first);
        $this->assertArrayHasKey('prodi', $first);
    }

    public function test_campus_api_service_can_filter_matakuliah_by_prodi(): void
    {
        $api = app(CampusApiService::class);
        $result = $api->getMatakuliah(['prodi' => 'Teknik Elektro', 'pagination' => 'off']);

        $records = $result['data']['data'] ?? $result['data'] ?? [];
        $this->assertNotEmpty($records);
        foreach ($records as $item) {
            $this->assertStringContainsStringIgnoringCase('Elektro', $item['prodi']);
        }
    }
}
```

- [ ] **Step 2: Run test to verify failure or need for improvement**

Run: `php artisan test --filter=CampusApiMatakuliahTest`
Expected: FAIL (karena tanpa `id_sms`, server merespons 400 dan fallback hanya berisi 12 matakuliah).

- [ ] **Step 3: Implement multi-prodi iteration with `id_sms` and 24-hour cache in `CampusApiService.php`**

Update `app/Services/CampusApiService.php`:
```php
    /**
     * Peta ID Program Studi Fakultas Teknik UNUJA (PDDIKTI id_sms).
     */
    public const TEKNIK_PRODI_SMS = [
        'Teknik Elektro' => '0f6bb17f-efdd-493c-8939-30c215bd9fb1',
        'Teknik Informatika' => '1319bfef-2188-4240-b7a3-7a507bec2e64',
        'Teknologi Informasi' => '6bb7c485-08a3-465c-b549-e5223c2affe2',
        'Teknik Industri Pertanian' => '6dc3cb7a-fc03-4c1a-a66f-faa4de36d6e8',
    ];

    /**
     * Helper untuk mengambil data Matakuliah dengan dukungan multi-prodi id_sms & cache.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getMatakuliah(array $options = []): array
    {
        $cacheKey = 'campus_api_all_matakuliah_' . md5(json_encode($options));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($options): array {
            $prodiFilter = !empty($options['prodi']) ? trim((string) $options['prodi']) : null;
            $searchFilter = !empty($options['search']) ? strtolower(trim((string) $options['search'])) : null;

            $allRecords = [];
            $targetProdis = self::TEKNIK_PRODI_SMS;

            if ($prodiFilter) {
                $targetProdis = array_filter(self::TEKNIK_PRODI_SMS, function ($key) use ($prodiFilter) {
                    return str_contains(strtolower($key), strtolower($prodiFilter));
                }, ARRAY_FILTER_USE_KEY);

                if (empty($targetProdis)) {
                    $targetProdis = self::TEKNIK_PRODI_SMS;
                }
            }

            try {
                foreach ($targetProdis as $prodiName => $idSms) {
                    $params = [
                        'id_sms' => $idSms,
                        'pagination' => 'off',
                    ];
                    $res = $this->getData('matakuliah', $params);
                    $records = $res['data']['data'] ?? $res['data'] ?? [];

                    foreach ($records as $r) {
                        $allRecords[] = [
                            'kode' => $r['kode_matkul'] ?? $r['kode'] ?? '',
                            'nama' => $r['nama_matkul'] ?? $r['nama'] ?? '',
                            'sks' => (int) ($r['sks_mk'] ?? $r['sks'] ?? 3),
                            'prodi' => $r['prodi'] ?? $prodiName,
                            'semester' => (int) ($r['semester'] ?? 1),
                            'kurikulum' => $r['nama_kurikulum'] ?? '',
                            'id_matkul' => $r['id_matkul'] ?? '',
                        ];
                    }
                }

                if (!empty($allRecords)) {
                    // Filter pencarian teks jika ada
                    if ($searchFilter) {
                        $allRecords = array_values(array_filter($allRecords, function ($item) use ($searchFilter) {
                            return str_contains(strtolower($item['nama']), $searchFilter)
                                || str_contains(strtolower($item['kode']), $searchFilter);
                        }));
                    }

                    return [
                        'success' => true,
                        'data' => $allRecords,
                        'total' => count($allRecords),
                        'source' => 'campus_api_live',
                    ];
                }
            } catch (\Throwable $e) {
                Log::info("Koneksi matakuliah API kampus multi-prodi: " . $e->getMessage() . " - Menggunakan kurikulum akademik terpadu.");
            }

            return $this->getMatakuliahAcademicCatalog($options['search'] ?? null, $options['prodi'] ?? null);
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CampusApiMatakuliahTest`
Expected: PASS (mengembalikan 700+ matakuliah secara live dan ter-cache).

- [ ] **Step 5: Commit**

```bash
git add app/Services/CampusApiService.php tests/Feature/CampusApiMatakuliahTest.php
git commit -m "feat: enhance CampusApiService to fetch full 795 matakuliah using id_sms and cache"
```

---

### Task 2: Database Schema & Eloquent Models (`course_classes`, `course_class_students`)

**Files:**
- Create: `database/migrations/2026_11_09_070000_create_course_classes_and_students_tables.php`
- Create: `app/Models/CourseClass.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/VirtualClassroom.php`
- Test: `tests/Unit/CourseClassModelTest.php`

**Interfaces:**
- `CourseClass::class`:
  - `dosen(): BelongsTo` ke `User`
  - `students(): BelongsToMany` ke `User` via `course_class_students`
  - `virtualClassrooms(): HasMany` ke `VirtualClassroom`
  - `activeVirtualClassroom(): ?VirtualClassroom`
- `User::class`:
  - `teachingClasses(): HasMany`
  - `enrolledClasses(): BelongsToMany`
- `VirtualClassroom::class`:
  - `courseClass(): BelongsTo`

- [ ] **Step 1: Write the failing unit test for CourseClass relationships**

Create `tests/Unit/CourseClassModelTest.php`:
```php
<?php

namespace Tests\Unit;

use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseClassModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_class_has_dosen_and_students_relationships(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $courseClass = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'tahun_ajaran' => '2026/2027 Ganjil',
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ]);

        $this->assertEquals($dosen->id, $courseClass->dosen->id);
        $this->assertCount(1, $dosen->teachingClasses);

        // Attach students
        $courseClass->students()->attach([$student1->id, $student2->id]);
        $this->assertCount(2, $courseClass->students);
        $this->assertCount(1, $student1->enrolledClasses);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CourseClassModelTest`
Expected: FAIL (table and class do not exist yet).

- [ ] **Step 3: Create migration and run it**

Create migration `database/migrations/2026_11_09_070000_create_course_classes_and_students_tables.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mk', 50);
            $table->string('nama_mk', 255);
            $table->unsignedInteger('sks')->default(3);
            $table->string('nama_kelas', 100);
            $table->string('prodi', 100);
            $table->unsignedInteger('semester')->default(1);
            $table->string('tahun_ajaran', 50)->default('2026/2027 Ganjil');
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->string('hari', 20);
            $table->string('jam_mulai', 10);
            $table->string('jam_selesai', 10);
            $table->string('ruang', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('course_class_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_class_id', 'student_id']);
        });

        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->foreignId('course_class_id')->nullable()->after('dosen_id')->constrained('course_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->dropForeign(['course_class_id']);
            $table->dropColumn('course_class_id');
        });
        Schema::dropIfExists('course_class_students');
        Schema::dropIfExists('course_classes');
    }
};
```
Run: `php artisan migrate`

- [ ] **Step 4: Create Model `CourseClass` and update `User` & `VirtualClassroom`**

Create `app/Models/CourseClass.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'nama_kelas',
        'prodi',
        'semester',
        'tahun_ajaran',
        'dosen_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang',
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_class_students', 'course_class_id', 'student_id')
            ->withTimestamps();
    }

    public function virtualClassrooms(): HasMany
    {
        return $this->hasMany(VirtualClassroom::class, 'course_class_id');
    }

    public function activeSession(): ?VirtualClassroom
    {
        return $this->virtualClassrooms()
            ->where('status', 'live')
            ->latest()
            ->first();
    }
}
```

Update `app/Models/User.php`:
```php
    public function teachingClasses()
    {
        return $this->hasMany(CourseClass::class, 'dosen_id');
    }

    public function enrolledClasses()
    {
        return $this->belongsToMany(CourseClass::class, 'course_class_students', 'student_id', 'course_class_id')
            ->withTimestamps();
    }
```

Update `app/Models/VirtualClassroom.php`:
Add `'course_class_id'` to `$fillable` and define relation:
```php
    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=CourseClassModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/ app/Models/ tests/Unit/CourseClassModelTest.php
git commit -m "feat: add course_classes and course_class_students tables and models"
```

---

### Task 3: Admin Class Management & Ploting Interface (`AdminCourseClassController` & Views)

**Files:**
- Create: `app/Http/Controllers/AdminCourseClassController.php`
- Create: `resources/views/admin/classes/index.blade.php`
- Create: `resources/views/admin/classes/create.blade.php`
- Create: `resources/views/admin/classes/edit.blade.php`
- Create: `resources/views/admin/classes/plotting.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php` (tambahkan menu navigasi Ploting Kelas)
- Modify: `resources/views/admin/dashboard.blade.php` (tambahkan kartu quick access kelas)
- Test: `tests/Feature/AdminCourseClassPlottingTest.php`

**Interfaces:**
- Routes:
  - `GET /admin/classes` -> `admin.classes.index`
  - `GET /admin/classes/create` -> `admin.classes.create`
  - `POST /admin/classes` -> `admin.classes.store`
  - `GET /admin/classes/{class}/edit` -> `admin.classes.edit`
  - `PUT /admin/classes/{class}` -> `admin.classes.update`
  - `DELETE /admin/classes/{class}` -> `admin.classes.destroy`
  - `GET /admin/classes/{class}/plotting` -> `admin.classes.plotting`
  - `POST /admin/classes/{class}/plotting` -> `admin.classes.plotting.save`

- [ ] **Step 1: Write the failing feature test for Admin Class Management & Ploting**

Create `tests/Feature/AdminCourseClassPlottingTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseClassPlottingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_classes_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.classes.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Kelas & Ploting Mahasiswa');
    }

    public function test_admin_can_store_new_course_class_with_api_course_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $data = [
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'ruang' => 'Lab Komputer 1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.classes.store'), $data);
        $response->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('course_classes', [
            'nama_kelas' => 'Kelas A',
            'dosen_id' => $dosen->id,
        ]);
    }

    public function test_admin_can_plot_students_into_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student1 = User::factory()->create(['role' => 'mahasiswa']);
        $student2 = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.classes.plotting.save', $class), [
            'students' => [$student1->id, $student2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student1->id,
        ]);
        $this->assertDatabaseHas('course_class_students', [
            'course_class_id' => $class->id,
            'student_id' => $student2->id,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminCourseClassPlottingTest`
Expected: FAIL (routes and controller do not exist).

- [ ] **Step 3: Implement `AdminCourseClassController.php`**

Create `app/Http/Controllers/AdminCourseClassController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\CourseClass;
use App\Models\User;
use App\Services\CampusApiService;
use Illuminate\Http\Request;

class AdminCourseClassController extends Controller
{
    public function __construct(protected CampusApiService $campusApi) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $selectedProdi = trim((string) $request->input('prodi', ''));

        $query = CourseClass::with(['dosen', 'students', 'virtualClassrooms'])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mk', 'like', "%{$search}%")
                  ->orWhere('kode_mk', 'like', "%{$search}%")
                  ->orWhere('nama_kelas', 'like', "%{$search}%")
                  ->orWhereHas('dosen', fn($d) => $d->where('name', 'like', "%{$search}%"));
            });
        }

        if ($selectedProdi !== '') {
            $query->where('prodi', $selectedProdi);
        }

        $classes = $query->paginate(12)->withQueryString();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        return view('admin.classes.index', compact('classes', 'search', 'selectedProdi', 'prodiList'));
    }

    public function create()
    {
        $dosenList = User::where('role', 'dosen')->orderBy('name')->get();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        // Ambil data matakuliah API Kampus
        $mkResult = $this->campusApi->getMatakuliah(['pagination' => 'off']);
        $matakuliahList = $mkResult['data']['data'] ?? $mkResult['data'] ?? [];

        return view('admin.classes.create', compact('dosenList', 'prodiList', 'matakuliahList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_mk' => 'required|string|max:50',
            'nama_mk' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:10',
            'nama_kelas' => 'required|string|max:100',
            'prodi' => 'required|string|max:100',
            'semester' => 'required|integer|min:1|max:8',
            'dosen_id' => 'required|exists:users,id',
            'hari' => 'required|string|max:20',
            'jam_mulai' => 'required|string|max:10',
            'jam_selesai' => 'required|string|max:10',
            'ruang' => 'nullable|string|max:100',
        ]);

        $courseClass = CourseClass::create($validated);

        return redirect()->route('admin.classes.plotting', $courseClass)
            ->with('success', "Kelas {$courseClass->nama_mk} ({$courseClass->nama_kelas}) berhasil dibuat! Silakan plot mahasiswa untuk kelas ini.");
    }

    public function plotting(Request $request, CourseClass $class)
    {
        $class->load(['dosen', 'students']);
        $currentStudentIds = $class->students->pluck('id')->toArray();

        $search = trim((string) $request->input('search', ''));
        $selectedProdi = trim((string) $request->input('prodi', $class->prodi));

        $studentQuery = User::where('role', 'mahasiswa')->orderBy('name');

        if ($search !== '') {
            $studentQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($selectedProdi !== '') {
            $studentQuery->where('prodi', 'like', "%{$selectedProdi}%");
        }

        $students = $studentQuery->get();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        return view('admin.classes.plotting', compact('class', 'students', 'currentStudentIds', 'search', 'selectedProdi', 'prodiList'));
    }

    public function savePlotting(Request $request, CourseClass $class)
    {
        $studentIds = $request->input('students', []);
        $class->students()->sync($studentIds);

        $count = count($studentIds);
        return redirect()->route('admin.classes.index')
            ->with('success', "Berhasil mem-plot {$count} mahasiswa ke kelas {$class->nama_mk} ({$class->nama_kelas})!");
    }

    public function destroy(CourseClass $class)
    {
        $name = "{$class->nama_mk} ({$class->nama_kelas})";
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', "Kelas {$name} berhasil dihapus beserta data plotingnya.");
    }
}
```

- [ ] **Step 4: Add routes in `routes/web.php`**

In `routes/web.php` inside `Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(...)`:
```php
        Route::resource('classes', AdminCourseClassController::class)->except(['show']);
        Route::get('/classes/{class}/plotting', [AdminCourseClassController::class, 'plotting'])->name('classes.plotting');
        Route::post('/classes/{class}/plotting', [AdminCourseClassController::class, 'savePlotting'])->name('classes.plotting.save');
```

- [ ] **Step 5: Create Blade views for Admin Classes & Ploting**

Create:
1. `resources/views/admin/classes/index.blade.php`: Modern table/cards of all classes with search, filters, students count badge, and actions.
2. `resources/views/admin/classes/create.blade.php`: Interactive class creation form with searchable datalist / dropdown from 795 API courses.
3. `resources/views/admin/classes/plotting.blade.php`: Bulk checkbox table with "Pilih Semua", "Batal Pilih", filter prodi, and search.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=AdminCourseClassPlottingTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/AdminCourseClassController.php routes/web.php resources/views/admin/classes/ tests/Feature/AdminCourseClassPlottingTest.php
git commit -m "feat: implement Admin class management and bulk student plotting interface"
```

---

### Task 4: Dosen Dashboard Integration (1-Click Start Session from Assigned Class)

**Files:**
- Modify: `app/Http/Controllers/VirtualClassroomController.php:30-80`
- Modify: `resources/views/dosen/dashboard.blade.php`
- Test: `tests/Feature/DosenClassIntegrationTest.php`

**Interfaces:**
- Dosen can submit `course_class_id` when creating a classroom.
- Sesi kelas virtual otomatis terikat dengan `course_class_id`.
- Dashboard Dosen menampilkan kartu kelas-kelas yang diampunya (`teachingClasses`) dengan tombol langsung: **"Mulai Sesi Virtual 3-in-1"**.

- [ ] **Step 1: Write the failing feature test for Dosen class session start**

Create `tests/Feature/DosenClassIntegrationTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenClassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dashboard_displays_assigned_teaching_classes(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('Kelas A');
        $response->assertSee('Mulai Kelas Virtual');
    }

    public function test_dosen_can_start_session_linked_to_course_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);

        $response = $this->actingAs($dosen)->post(route('dosen.classroom.create'), [
            'course_class_id' => $class->id,
            'title' => 'Pemrograman Web Lanjut (Kelas A)',
        ]);

        $this->assertDatabaseHas('virtual_classrooms', [
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        $room = VirtualClassroom::where('course_class_id', $class->id)->first();
        $response->assertRedirect(route('classroom.room', $room->room_code));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DosenClassIntegrationTest`
Expected: FAIL.

- [ ] **Step 3: Update `VirtualClassroomController@dosenDashboard` & `createRoom`**

In `app/Http/Controllers/VirtualClassroomController.php`:
- Load `$teachingClasses = $user->teachingClasses()->with(['students', 'virtualClassrooms'])->get();`
- In `createRoom(Request $request)`:
  - Validate `'course_class_id' => 'nullable|exists:course_classes,id'`.
  - Save `course_class_id` when creating `VirtualClassroom`.

- [ ] **Step 4: Update `resources/views/dosen/dashboard.blade.php`**

Add section: **"Daftar Kelas Perkuliahan Anda"** displaying cards for each assigned class, student count, active live badge, and 1-click **"Mulai Kelas Virtual"** form.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DosenClassIntegrationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/VirtualClassroomController.php resources/views/dosen/dashboard.blade.php tests/Feature/DosenClassIntegrationTest.php
git commit -m "feat: integrate assigned classes into Dosen dashboard with 1-click session starter"
```

---

### Task 5: Mahasiswa Dashboard Integration & RBAC Access Control

**Files:**
- Modify: `app/Http/Controllers/VirtualClassroomController.php:80-140`
- Modify: `resources/views/mahasiswa/dashboard.blade.php`
- Test: `tests/Feature/MahasiswaClassIntegrationTest.php`

**Interfaces:**
- Mahasiswa sees `$enrolledClasses = $user->enrolledClasses()->with(['dosen', 'virtualClassrooms'])->get()`.
- If class has a live virtual session, shows pulsating badge **"🔴 KELAS SEDANG LIVE"** and 1-click **"Masuk ke Kelas"**.
- RBAC Check: When student joins or visits `/room/{room_code}` for a room with `course_class_id`, verify that `auth()->id()` is in `course_class_students`. If not, redirect with error message.

- [ ] **Step 1: Write the failing feature test for Mahasiswa enrollment integration & RBAC protection**

Create `tests/Feature/MahasiswaClassIntegrationTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\User;
use App\Models\VirtualClassroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaClassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_sees_classes_and_can_join_live_session(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);
        $class->students()->attach($student->id);

        // Dosen starts a live session
        $room = VirtualClassroom::create([
            'room_code' => '3IN1-WEB1',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertSee('SEDANG LIVE');
        $response->assertSee(route('classroom.room', $room->room_code));
    }

    public function test_non_enrolled_student_is_blocked_from_protected_class_room(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $enrolledStudent = User::factory()->create(['role' => 'mahasiswa']);
        $outsiderStudent = User::factory()->create(['role' => 'mahasiswa']);

        $class = CourseClass::create([
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Pemrograman Web Lanjut',
            'sks' => 3,
            'nama_kelas' => 'Kelas A',
            'prodi' => 'Teknik Informatika',
            'semester' => 4,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);
        $class->students()->attach($enrolledStudent->id);

        $room = VirtualClassroom::create([
            'room_code' => '3IN1-WEB2',
            'title' => 'Pemrograman Web Lanjut',
            'dosen_id' => $dosen->id,
            'course_class_id' => $class->id,
            'status' => 'live',
        ]);

        // Outsider student attempts to enter
        $response = $this->actingAs($outsiderStudent)->get(route('classroom.room', $room->room_code));
        $response->assertRedirect(route('mahasiswa.dashboard'));
        $response->assertSessionHas('error');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MahasiswaClassIntegrationTest`
Expected: FAIL.

- [ ] **Step 3: Implement Mahasiswa Dashboard query and Room Access RBAC check**

In `app/Http/Controllers/VirtualClassroomController.php`:
- In `mahasiswaDashboard(Request $request)`:
  - Query `$enrolledClasses = $user->enrolledClasses()->with(['dosen', 'virtualClassrooms'])->get();`
  - Pass `$enrolledClasses` to `resources/views/mahasiswa/dashboard.blade.php`.
- In `showRoom(Request $request, $room_code)` & `joinRoom(Request $request)`:
  - Check if `$classroom->course_class_id` is set:
  - If user is `mahasiswa` and `!$classroom->courseClass->students->contains($user->id)`:
    - Redirect to `mahasiswa.dashboard` with error: *"Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas."*

- [ ] **Step 4: Update `resources/views/mahasiswa/dashboard.blade.php`**

Add section: **"Matakuliah & Kelas Terdaftar Anda"** displaying cards with course details, lecturer name, schedule, live badge, and 1-click **"Masuk ke Kelas"** button.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=MahasiswaClassIntegrationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/VirtualClassroomController.php resources/views/mahasiswa/dashboard.blade.php tests/Feature/MahasiswaClassIntegrationTest.php
git commit -m "feat: integrate enrolled classes in Mahasiswa dashboard with live join and RBAC enrollment check"
```

---

### Task 6: End-to-End Verification, Code Formatting & Final Checklist

**Files:**
- Run full test suite: `php artisan test`
- Format code: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 1: Run Pint code formatter**

Run: `vendor/bin/pint --format agent`
Expected: All files properly formatted according to project conventions.

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: All tests pass (including existing SSO, Virtual Classroom, Auth, and new CourseClassPlotting tests).

- [ ] **Step 3: Commit and summarize**

```bash
git add .
git commit -m "feat: complete student class plotting and 795 campus api courses integration"
```
