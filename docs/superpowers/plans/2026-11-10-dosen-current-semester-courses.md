# Dosen Current Semester Courses Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menampilkan hanya mata kuliah dan kelas yang diampu Dosen pada semester/tahun ajaran aktif saat ini di dashboard Dosen, terintegrasi penuh dengan data kelas dan ploting mahasiswa yang dibuat Admin (`course_classes`), dilengkapi filter pemilih semester.

**Architecture:** Mengubah `VirtualClassroomController::dosenDashboard` untuk meresolusi semester aktif (default `2026/2027 Ganjil` atau input `tahun_ajaran`), memfilter relasi `$user->teachingClasses()`, dan menurunkan `$jadwalMengajar` serta `$matakuliah` langsung dari data `CourseClass` riil. Memperbarui `resources/views/dosen/dashboard.blade.php` dengan filter semester, dropdown pemilihan kelas terplot pada form pembukaan sesi kelas virtual, serta widget jadwal & matakuliah terintegrasi.

**Tech Stack:** Laravel 12 (PHP 8.4), Eloquent ORM, Blade Templates, Tailwind CSS, PHPUnit Feature Testing.

## Global Constraints
- Seluruh data mata kuliah dan jadwal di dashboard Dosen harus bersumber dari tabel `course_classes` yang dibuat Admin.
- Dosen hanya memiliki hak akses untuk melihat dan memulai sesi kelas yang ditugaskan kepada dirinya (`dosen_id === auth()->id()`).
- Setiap perubahan kode PHP harus lolos format Pint (`vendor/bin/pint --format agent`).
- Seluruh test di `tests/Feature/` harus lolos 100%.

---

### Task 1: Backend Controller Integration for Dosen Dashboard

**Files:**
- Modify: `app/Http/Controllers/VirtualClassroomController.php:25-135`
- Test: `tests/Feature/DosenClassIntegrationTest.php`

**Interfaces:**
- Consumes: `CourseClass` model, `User::teachingClasses()`, `Request $request`
- Produces: View variables `availableSemesters`, `selectedTahunAjaran`, `teachingClasses`, `jadwalMengajar`, `matakuliah`

- [ ] **Step 1: Write the failing test for dosenDashboard semester filtering**

```php
// In tests/Feature/DosenClassIntegrationTest.php
public function test_dosen_dashboard_filters_classes_by_current_semester(): void
{
    $dosen = User::factory()->create(['role' => 'dosen']);
    
    // Kelas Semester Aktif
    $activeClass = CourseClass::create([
        'kode_mk' => 'MK-AKTIF',
        'nama_mk' => 'Mata Kuliah Semester Aktif',
        'sks' => 3,
        'nama_kelas' => 'Kelas A',
        'prodi' => 'Teknologi Informasi',
        'semester' => 7,
        'tahun_ajaran' => '2026/2027 Ganjil',
        'dosen_id' => $dosen->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:30',
        'ruang' => 'Ruang 101',
    ]);

    // Kelas Semester Lalu
    $pastClass = CourseClass::create([
        'kode_mk' => 'MK-LALU',
        'nama_mk' => 'Mata Kuliah Semester Lalu',
        'sks' => 2,
        'nama_kelas' => 'Kelas B',
        'prodi' => 'Teknologi Informasi',
        'semester' => 6,
        'tahun_ajaran' => '2025/2026 Genap',
        'dosen_id' => $dosen->id,
        'hari' => 'Selasa',
        'jam_mulai' => '10:30',
        'jam_selesai' => '12:00',
        'ruang' => 'Ruang 102',
    ]);

    // 1. Default request (tanpa query param) harus menampilkan semester aktif
    $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));
    $response->assertStatus(200);
    $response->assertViewHas('selectedTahunAjaran', '2026/2027 Ganjil');
    $response->assertSee('Mata Kuliah Semester Aktif');
    $response->assertDontSee('Mata Kuliah Semester Lalu');

    // 2. Request dengan filter semester lalu
    $responsePast = $this->actingAs($dosen)->get(route('dosen.dashboard', ['tahun_ajaran' => '2025/2026 Genap']));
    $responsePast->assertStatus(200);
    $responsePast->assertViewHas('selectedTahunAjaran', '2025/2026 Genap');
    $responsePast->assertSee('Mata Kuliah Semester Lalu');
    $responsePast->assertDontSee('Mata Kuliah Semester Aktif');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_dosen_dashboard_filters_classes_by_current_semester`
Expected: FAIL (view variables or semester filter logic not implemented yet)

- [ ] **Step 3: Implement semester filtering and Admin integration in `VirtualClassroomController::dosenDashboard`**

```php
    public function dosenDashboard(Request $request, CampusApiService $campusApi)
    {
        $user = Auth::user();

        // 1. Ambil daftar semester/tahun ajaran yang tersedia untuk Dosen ini
        $availableSemesters = CourseClass::where('dosen_id', $user->id)
            ->select('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->filter()
            ->values()
            ->toArray();

        if (empty($availableSemesters)) {
            $availableSemesters = ['2026/2027 Ganjil'];
        }

        // Tentukan semester terpilih (prioritas: query param valid, jika tidak: default semester aktif pertama)
        $defaultSemester = in_array('2026/2027 Ganjil', $availableSemesters, true) ? '2026/2027 Ganjil' : $availableSemesters[0];
        $selectedTahunAjaran = $request->input('tahun_ajaran', $defaultSemester);
        if (! in_array($selectedTahunAjaran, $availableSemesters, true)) {
            $selectedTahunAjaran = $defaultSemester;
        }

        // 2. Ambil kelas yang diampu Dosen pada semester terpilih
        $teachingClasses = $user->teachingClasses()
            ->where('tahun_ajaran', $selectedTahunAjaran)
            ->with(['students', 'virtualClassrooms'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        // 3. Bangun data Jadwal Mengajar langsung dari CourseClass Admin
        $jadwalMengajar = $teachingClasses->map(function ($class) {
            return [
                'id' => $class->id,
                'hari' => $class->hari ?? 'Senin',
                'jam' => ($class->jam_mulai ?? '08:00') . ' - ' . ($class->jam_selesai ?? '10:00'),
                'nama_mk' => $class->nama_mk,
                'kode_mk' => $class->kode_mk,
                'kelas' => $class->nama_kelas,
                'sks' => $class->sks,
                'ruang' => $class->ruang ?? 'Ruang Kelas Virtual 3in1',
                'prodi' => $class->prodi,
                'course_class_id' => $class->id,
            ];
        })->toArray();

        // 4. Bangun data Matakuliah yang Diampu langsung dari CourseClass Admin
        $matakuliah = $teachingClasses->unique('kode_mk')->map(function ($class) {
            return [
                'kode' => $class->kode_mk,
                'nama' => $class->nama_mk,
                'sks' => $class->sks,
                'semester' => $class->semester,
                'kelas' => $class->nama_kelas,
                'prodi' => $class->prodi,
                'course_class_id' => $class->id,
            ];
        })->values()->toArray();

        $recentRooms = VirtualClassroom::where('dosen_id', $user->id)
            ->with(['courseClass'])
            ->withCount('attendances')
            ->latest()
            ->take(10)
            ->get();

        return view('dosen.dashboard', compact(
            'user',
            'recentRooms',
            'matakuliah',
            'jadwalMengajar',
            'teachingClasses',
            'availableSemesters',
            'selectedTahunAjaran'
        ));
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_dosen_dashboard_filters_classes_by_current_semester`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/VirtualClassroomController.php tests/Feature/DosenClassIntegrationTest.php
git commit -m "feat(dosen): filter teaching classes and schedule by active semester"
```

---

### Task 2: Update Dosen Dashboard Blade Views

**Files:**
- Modify: `resources/views/dosen/dashboard.blade.php:20-365`

- [ ] **Step 1: Write test asserting UI elements on Dosen Dashboard**

```php
// In tests/Feature/DosenClassIntegrationTest.php
public function test_dosen_dashboard_renders_semester_filter_and_assigned_class_select(): void
{
    $dosen = User::factory()->create(['role' => 'dosen']);
    $class = CourseClass::create([
        'kode_mk' => 'TIF-701',
        'nama_mk' => 'Arsitektur Enterprise',
        'sks' => 3,
        'nama_kelas' => 'TI-7A',
        'prodi' => 'Teknologi Informasi',
        'semester' => 7,
        'tahun_ajaran' => '2026/2027 Ganjil',
        'dosen_id' => $dosen->id,
        'hari' => 'Rabu',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:30',
        'ruang' => 'Lab Komputer 3',
    ]);

    $response = $this->actingAs($dosen)->get(route('dosen.dashboard'));
    $response->assertStatus(200);
    $response->assertSee('name="tahun_ajaran"', false);
    $response->assertSee('name="course_class_id"', false);
    $response->assertSee('TIF-701 - Arsitektur Enterprise');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_dosen_dashboard_renders_semester_filter_and_assigned_class_select`
Expected: FAIL (form inputs still use old format)

- [ ] **Step 3: Update `resources/views/dosen/dashboard.blade.php`**
  - Add semester dropdown filter form in the header with auto-submit `onchange="this.form.submit()"`.
  - Update Form *"Buka Sesi Kelas Baru"* to `<select name="course_class_id" required>` populated with `$teachingClasses`.
  - Update *"Jadwal Mengajar Anda"* and *"Matakuliah yang Diampu"* widgets to reflect Admin-plotted classes with badges for semester and active room.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_dosen_dashboard_renders_semester_filter_and_assigned_class_select`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/dosen/dashboard.blade.php tests/Feature/DosenClassIntegrationTest.php
git commit -m "feat(dosen): add semester filter and class selector on dosen dashboard"
```

---

### Task 3: Comprehensive Integration Tests & Linting

**Files:**
- Modify: `tests/Feature/DosenClassIntegrationTest.php`

- [ ] **Step 1: Write test for class isolation and room creation from assigned class**

```php
public function test_dosen_cannot_see_other_dosen_classes_and_can_create_room_from_assigned_class(): void
{
    $dosenA = User::factory()->create(['role' => 'dosen']);
    $dosenB = User::factory()->create(['role' => 'dosen']);

    $classA = CourseClass::create([
        'kode_mk' => 'MK-A',
        'nama_mk' => 'Kelas Dosen A',
        'sks' => 3,
        'nama_kelas' => 'A1',
        'prodi' => 'Teknologi Informasi',
        'semester' => 7,
        'tahun_ajaran' => '2026/2027 Ganjil',
        'dosen_id' => $dosenA->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
    ]);

    $classB = CourseClass::create([
        'kode_mk' => 'MK-B',
        'nama_mk' => 'Kelas Dosen B',
        'sks' => 3,
        'nama_kelas' => 'B1',
        'prodi' => 'Teknologi Informasi',
        'semester' => 7,
        'tahun_ajaran' => '2026/2027 Ganjil',
        'dosen_id' => $dosenB->id,
        'hari' => 'Senin',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
    ]);

    // Dosen A tidak boleh melihat kelas Dosen B
    $response = $this->actingAs($dosenA)->get(route('dosen.dashboard'));
    $response->assertSee('Kelas Dosen A');
    $response->assertDontSee('Kelas Dosen B');

    // Dosen A membuat room dengan course_class_id
    $postResponse = $this->actingAs($dosenA)->post(route('dosen.classroom.create'), [
        'course_class_id' => $classA->id,
    ]);
    $postResponse->assertRedirect();

    $this->assertDatabaseHas('virtual_classrooms', [
        'dosen_id' => $dosenA->id,
        'course_class_id' => $classA->id,
        'title' => 'Kelas Dosen A (A1)',
        'status' => 'live',
    ]);
}
```

- [ ] **Step 2: Run all tests in `DosenClassIntegrationTest`**

Run: `php artisan test tests/Feature/DosenClassIntegrationTest.php`
Expected: PASS

- [ ] **Step 3: Run full classroom test suite**

Run: `php artisan test tests/Feature/VirtualClassroomAuthRbacTest.php tests/Feature/VirtualClassroomRoomTest.php tests/Feature/AdminCourseClassPlottingTest.php`
Expected: PASS

- [ ] **Step 4: Format code with Pint**

Run: `vendor/bin/pint --format agent`
Expected: Clean code styling

- [ ] **Step 5: Commit and push**

```bash
git add tests/Feature/DosenClassIntegrationTest.php
git commit -m "test(dosen): add integration tests for dosen class semester and plotting"
git push origin main
```

