# Desain Integrasi Mata Kuliah Dosen Semester Aktif dengan Admin Plotting

- **Tanggal Dokumen:** 2026-11-10
- **Status:** Disetujui (Approved)
- **Penulis:** Antigravity AI Assistant & Engineering Team

---

## 1. Latar Belakang & Masalah
Sebelumnya, pada dashboard Dosen ([`resources/views/dosen/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/dashboard.blade.php)):
1. Bagian *"Matakuliah yang Diampu"* dan modal *"Buka Sesi Kelas Baru"* mengambil seluruh data matakuliah mentah dari API kampus (`$campusApi->getMatakuliah()`) atau fallback statis dummy. Akibatnya, Dosen melihat ratusan mata kuliah dari seluruh program studi tanpa filter pengampu dan tanpa filter semester aktif.
2. Kelas yang telah dibuat dan di-plot mahasiswanya oleh Admin di panel Admin (`CourseClass` & `course_class_students`) belum terhubung secara langsung pada form pembuatan ruang kelas virtual Dosen, sehingga ruang kelas baru seringkali dibuat dengan `course_class_id = null`.
3. Jadwal mengajar Dosen belum sepenuhnya mencerminkan jadwal yang telah ditetapkan Admin di data kelas perkuliahan.

## 2. Tujuan & Kriteria Keberhasilan (Success Criteria)
1. **Semester Saat Ini Terpilih Otomatis:** Dashboard Dosen secara *default* hanya menampilkan kelas dan mata kuliah yang diampu Dosen bersangkutan pada semester/tahun ajaran aktif (`2026/2027 Ganjil`).
2. **Filter Semester Dinamis:** Menyediakan pemilih semester di dashboard sehingga Dosen dapat beralih ke semester lain jika ingin meninjau riwayat kelas terdahulu.
3. **Integrasi Penuh dengan Data Admin (`course_classes`):**
   - Kartu kelas ditugaskan (`$teachingClasses`) hanya memuat kelas Dosen pada semester terpilih.
   - Form *"Buka Sesi Kelas Baru"* berupa dropdown `<select name="course_class_id">` yang berisi daftar kelas Dosen pada semester tersebut, otomatis mengaitkan `course_class_id` dan ploting mahasiswa.
   - Bagian *"Jadwal Mengajar"* bersumber langsung dari hari, jam, dan ruang yang diisi Admin.
   - Bagian *"Matakuliah yang Diampu"* mencerminkan beban mata kuliah riil Dosen pada semester tersebut.
4. **Keamanan & RBAC:** Dosen hanya bisa melihat dan membuka kelas miliknya sendiri (`dosen_id = auth()->id()`).
5. **Pengujian Otomatis:** Seluruh skenario diuji melalui Automated Feature Tests dan lolos 100%.

---

## 3. Arsitektur & Alur Data

### 3.1 Resolusi Tahun Ajaran / Semester Aktif
Di `VirtualClassroomController::dosenDashboard(Request $request, CampusApiService $campusApi)`:
- Menentukan daftar semester yang tersedia:
  ```php
  $availableSemesters = CourseClass::where('dosen_id', $user->id)
      ->select('tahun_ajaran')
      ->distinct()
      ->pluck('tahun_ajaran')
      ->filter()
      ->values()
      ->toArray();
  ```
- Jika kosong atau belum ada kelas, sertakan opsi default `2026/2027 Ganjil`.
- Menentukan semester aktif saat ini:
  - Prioritas 1: Input query `request('tahun_ajaran')` jika valid.
  - Prioritas 2: Semester dengan status aktif dari sistem / default `'2026/2027 Ganjil'`.

### 3.2 Query Kelas Dosen Berdasarkan Semester Terpilih
```php
$teachingClasses = $user->teachingClasses()
    ->where('tahun_ajaran', $selectedTahunAjaran)
    ->with(['students', 'virtualClassrooms'])
    ->orderBy('hari')
    ->orderBy('jam_mulai')
    ->get();
```

### 3.3 Penyelarasan `$jadwalMengajar` dan `$matakuliah`
1. **`$jadwalMengajar`**:
   Dibangun langsung dari koleksi `$teachingClasses`:
   ```php
   $jadwalMengajar = $teachingClasses->map(function (CourseClass $c) {
       return [
           'id' => $c->id,
           'hari' => $c->hari,
           'jam' => $c->jam_mulai . ' - ' . $c->jam_selesai,
           'nama_mk' => $c->nama_mk,
           'kode_mk' => $c->kode_mk,
           'kelas' => $c->nama_kelas,
           'sks' => $c->sks,
           'ruang' => $c->ruang ?? 'Ruang Kelas Virtual 3in1',
           'course_class_id' => $c->id,
       ];
   })->toArray();
   ```
2. **`$matakuliah`**:
   Dihasilkan dari daftar mata kuliah unik pada `$teachingClasses`:
   ```php
   $matakuliah = $teachingClasses->unique('kode_mk')->map(function (CourseClass $c) {
       return [
           'kode' => $c->kode_mk,
           'nama' => $c->nama_mk,
           'sks' => $c->sks,
           'semester' => $c->semester,
           'kelas' => $c->nama_kelas,
           'prodi' => $c->prodi,
       ];
   })->values()->toArray();
   ```

---

## 4. Perubahan Antarmuka Pengguna (UI/Blade)

1. **Header Dashboard Dosen:**
   - Menambahkan dropdown filter semester:
     ```html
     <form method="GET" action="{{ route('dosen.dashboard') }}" class="flex items-center space-x-2">
         <select name="tahun_ajaran" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 py-1.5 px-3 bg-white font-semibold text-slate-700">
             @foreach($availableSemesters as $sem)
                 <option value="{{ $sem }}" {{ $selectedTahunAjaran === $sem ? 'selected' : '' }}>{{ $sem }}</option>
             @endforeach
         </select>
     </form>
     ```
   - Badge penanda semester aktif.

2. **Form Buka Sesi Kelas Baru:**
   - Mengganti input teks bebas dengan:
     ```html
     <select name="course_class_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-purple-600 focus:border-purple-600">
         <option value="">-- Pilih Kelas Kuliah Ditugaskan --</option>
         @foreach($teachingClasses as $tc)
             <option value="{{ $tc->id }}">{{ $tc->kode_mk }} - {{ $tc->nama_mk }} ({{ $tc->nama_kelas }} - {{ $tc->sks }} SKS)</option>
         @endforeach
     </select>
     ```
   - Jika belum ada kelas ditugaskan di semester tersebut, menampilkan panduan informatif bahwa Admin perlu menugaskan kelas terlebih dahulu.

3. **Widget "Jadwal Mengajar Anda":**
   - Menampilkan card jadwal harian berdasarkan jadwal kelas yang dibuat Admin.
   - Tombol *"Mulai Sesi Ini"* langsung mem-post `course_class_id`.

4. **Widget "Matakuliah yang Diampu":**
   - Menampilkan ringkasan beban mata kuliah semester ini dari data riil Admin.

---

## 5. Rencana Pengujian (Testing Plan)

1. **Uji Filter Semester:**
   - Dosen A memiliki kelas di `2026/2027 Ganjil` dan `2025/2026 Genap`.
   - Mengakses dashboard default menampilkan kelas `2026/2027 Ganjil`.
   - Mengakses `?tahun_ajaran=2025/2026 Genap` menampilkan kelas yang sesuai.
2. **Uji Isolasi Dosen:**
   - Kelas yang ditugaskan ke Dosen B tidak boleh muncul di dashboard Dosen A.
3. **Uji Pembuatan Ruang Kelas Virtual:**
   - Memilih kelas dari dropdown membuat `VirtualClassroom` dengan `course_class_id` yang valid dan menautkan mahasiswa yang di-plot.
