# Spesifikasi Desain: Fitur Ploting Kelas Mahasiswa & Integrasi API Matakuliah Kampus

## 1. Konteks & Tujuan
Sistem **Platform Virtual Classroom 3-in-1** memerlukan manajemen kelas perkuliahan terstruktur yang menghubungkan Dosen (Pengampu) dan Mahasiswa (Peserta) berbasis matakuliah resmi dari API Kampus UNUJA.

Tujuan utama dari fitur ini adalah:
1. **Integrasi Data API Kampus Lengkap (795 Matakuliah):** Mengambil seluruh data matakuliah resmi dari 4 program studi Fakultas Teknik (Informatika, Elektro, TI, TIP) menggunakan parameter `id_sms` dengan caching performa tinggi.
2. **Manajemen Ploting Kelas oleh Admin (`/admin/classes`):** Administrator dapat membuat kelas baru (Kode MK, Nama MK, SKS, Nama Kelas, Hari, Jam, Ruang, Dosen Pengampu) dan mem-plot banyak mahasiswa sekaligus (bulk checkbox dengan fitur pencarian dan filter prodi).
3. **Integrasi 1-Klik Dosen:** Dosen melihat daftar kelas yang diampunya di Dashboard Dosen dan dapat membuka sesi Virtual Classroom 3-in-1 langsung dengan 1 klik.
4. **Integrasi 1-Klik & Proteksi Mahasiswa:** Mahasiswa melihat daftar matakuliah yang diambil di Dashboard Mahasiswa. Ketika kelas sedang *Live*, muncul badge berkedip dan tombol *Masuk Kelas* tanpa perlu mengetik kode ruang manual. Mahasiswa yang tidak terdaftar akan dibatasi hak aksesnya.

---

## 2. Arsitektur Data & Skema Database

### 2.1. Tabel `course_classes`
Menyimpan data kelas perkuliahan aktif:
```sql
CREATE TABLE course_classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(50) NOT NULL,
    nama_mk VARCHAR(255) NOT NULL,
    sks INT NOT NULL DEFAULT 3,
    nama_kelas VARCHAR(100) NOT NULL, -- Contoh: "Kelas A", "TI-4A"
    prodi VARCHAR(100) NOT NULL,
    semester INT NOT NULL DEFAULT 1,
    tahun_ajaran VARCHAR(50) DEFAULT '2026/2027 Ganjil',
    dosen_id BIGINT UNSIGNED NOT NULL,
    hari VARCHAR(20) NOT NULL, -- Contoh: "Senin"
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruang VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2.2. Tabel `course_class_students` (Pivot Ploting)
Menghubungkan mahasiswa dengan kelas yang diambil:
```sql
CREATE TABLE course_class_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_class_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (course_class_id) REFERENCES course_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_student (course_class_id, student_id)
);
```

### 2.3. Pembaruan Tabel `virtual_classrooms`
Menambahkan kolom relasi ke kelas yang di-plot:
```sql
ALTER TABLE virtual_classrooms 
ADD COLUMN course_class_id BIGINT UNSIGNED NULL AFTER dosen_id,
ADD CONSTRAINT fk_virtual_classrooms_course_class 
    FOREIGN KEY (course_class_id) REFERENCES course_classes(id) ON DELETE SET NULL;
```

---

## 3. Integrasi API Matakuliah Kampus (`CampusApiService`)

### 3.1. Penyesuaian `CampusApiService::getMatakuliah()`
Server API UNUJA pada endpoint `/data/{token}` dengan filter `matakuliah` mewajibkan parameter `id_sms` (ID Program Studi PDDIKTI).

Solusi implementasi:
1. Mendefinisikan pemetaan Program Studi Fakultas Teknik:
   - **Teknik Elektro (TE):** `0f6bb17f-efdd-493c-8939-30c215bd9fb1`
   - **Teknik Informatika (IF):** `1319bfef-2188-4240-b7a3-7a507bec2e64`
   - **Teknologi Informasi (TI):** `6bb7c485-08a3-465c-b549-e5223c2affe2`
   - **Teknik Industri Pertanian (TIP):** `6dc3cb7a-fc03-4c1a-a66f-faa4de36d6e8`
2. Jika parameter `id_sms` atau `prodi` dikirim, tarik matakuliah khusus prodi tersebut.
3. Jika tidak ada filter, kumpulkan seluruh matakuliah dari seluruh prodi di atas (**795 matakuliah**) dan simpan di cache Laravel (`campus_api_all_matakuliah`) selama 24 jam.
4. Mendukung pencarian instan berbasis teks (`search`).

---

## 4. Alur & Antarmuka Admin (`/admin/classes`)

### 4.1. Fitur Utama
1. **Daftar Kelas Perkuliahan:**
   - Tabel menampilkan: Kode MK, Nama Matakuliah, SKS, Nama Kelas, Program Studi, Dosen Pengampu, Jadwal (Hari & Jam), Jumlah Mahasiswa Terdaftar, Status Sesi Virtual (Live / Tidak Aktif).
   - Tombol Aksi: *Detail / Mahasiswa Terdaftar*, *Edit Kelas*, *Hapus Kelas*.
2. **Form Tambah & Edit Kelas Perkuliahan:**
   - **Pilih Matakuliah:** Dropdown dinamis searchable dari 795 matakuliah API Kampus. Memilih MK otomatis mengisi *Kode MK*, *Nama MK*, *SKS*, dan *Prodi*.
   - **Pilih Dosen Pengampu:** Dropdown semua pengguna dengan `role = 'dosen'`.
   - **Nama Kelas:** Input teks (contoh: "Kelas A").
   - **Jadwal & Ruang:** Input hari (Senin - Sabtu), jam mulai, jam selesai, ruang.
3. **Modal / Tab Ploting Mahasiswa:**
   - Menampilkan daftar seluruh pengguna dengan `role = 'mahasiswa'`.
   - Filter dropdown Program Studi dan kotak pencarian Nama / NIM.
   - Tombol cepat: **Pilih Semua (Select All)** dan **Batal Pilih (Deselect All)**.
   - Checkbox untuk memilih mahasiswa yang masuk ke kelas tersebut.
   - Simpan perubahan ploting secara atomik.

---

## 5. Integrasi Dashboard Dosen & Mahasiswa

### 5.1. Dashboard Dosen (`/dosen/dashboard`)
1. Menampilkan kartu kelas yang diampu oleh dosen yang sedang login (`$user->teachingClasses`).
2. Terdapat tombol: **"Mulai Sesi Virtual 3-in-1"**.
3. Ketika diklik, sistem membuat sesi `VirtualClassroom` yang terhubung dengan `course_class_id` dan langsung mengarahkan Dosen ke ruang kelas virtual (`/room/{room_code}`).

### 5.2. Dashboard Mahasiswa (`/mahasiswa/dashboard`)
1. Menampilkan daftar kelas yang diambil oleh mahasiswa (`$user->enrolledClasses`).
2. Setiap kartu kelas menampilkan:
   - Nama Matakuliah, Dosen Pengampu, Hari, Jam, Ruang.
   - Status Sesi:
     - Jika Dosen sedang *Live*, muncul badge berdenyut **"🔴 SEDANG BERLANGSUNG"** dan tombol **"Masuk ke Kelas"** (1 klik langsung join).
     - Jika belum ada sesi, status menampilkan *"Menunggu Dosen Memulai Sesi"*.
3. Tetap mempertahankan input manual Room Code untuk fleksibilitas.

### 5.3. Proteksi Akses & RBAC Ruang Kelas
- Pada saat Mahasiswa mengakses `/room/{room_code}` atau rute join kelas:
  - Jika ruang kelas terikat pada `course_class_id`:
    - Sistem memeriksa apakah `student_id` ada dalam `course_class_students`.
    - Jika tidak terdaftar, akses ditolak dengan pesan error yang jelas (HTTP 403 / Redirect dengan Flash Message: *"Anda belum terdaftar dalam kelas matakuliah ini. Silakan hubungi Administrator untuk ploting kelas."*).

---

## 6. Rencana Pengujian Otomatis (Feature Tests)
Menambahkan pengujian fitur pada `tests/Feature/CourseClassPlottingTest.php`:
1. `test_admin_can_view_classes_index_and_see_api_courses`
2. `test_admin_can_create_course_class_with_api_course_and_dosen`
3. `test_admin_can_plot_students_into_course_class`
4. `test_dosen_can_see_assigned_classes_and_start_session`
5. `test_enrolled_student_can_see_class_and_join_live_session`
6. `test_non_enrolled_student_is_forbidden_from_joining_protected_class`
7. `test_campus_api_service_fetches_and_caches_all_795_courses`
