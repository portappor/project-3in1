# Dokumentasi Alur Komprehensif Platform Virtual Classroom 3-in-1

Dokumen ini memuat panduan arsitektur dan alur kerja aplikasi secara menyeluruh, dirancang untuk memudahkan pemahaman teknis, pengujian, presentasi skripsi/proyek, dan pemeliharaan kode program.

---

## 1. Ikhtisar Arsitektur Sistem

Aplikasi **Virtual Classroom 3-in-1** adalah platform pembelajaran berbasis web yang menyatukan tiga media utama ke dalam satu antarmuka tunggal (**Single-Window Interface**):
1. **Layanan Tatap Muka Video Conference** (terintegrasi melalui Jitsi Meet External API SDK).
2. **Penampil Dokumen Interaktif & Sinkronisasi Slide Real-Time** (berbasis PDF.js dan parser presentasi PPTX).
3. **Papan Diskusi & Rekam Jejak Keaktifan Akademik** (papan chat permanen, fitur angkat tangan *raise hand*, kontrol mikrofon *mute all*, serta ekspor rekap presensi CSV).
4. **Manajemen Perkuliahan Fleksibel (Online vs. Offline)**: Dosen dapat memilih membuka sesi daring interaktif 3-in-1 atau sesi tatap muka fisik dengan lembar presensi mandiri.

### Teknologi yang Digunakan:
- **Backend Framework**: Laravel 12 (PHP 8.4)
- **Database Engine**: MySQL 8.0
- **Frontend & Styling**: Blade Templating, Tailwind CSS, FontAwesome 6
- **Document Rendering**: PDF.js (Mozilla) & PHP Presentation parser (`DOMXPath` / XML ZIP extraction)
- **Video Conference**: Jitsi Meet External API (`meet.jit.si`)
- **Testing Suite**: PHPUnit (82 skenario uji Feature & Unit terotomasi, 100% lulus)

---

## 2. Diagram Alur Utama (Global User Flow)

```mermaid
flowchart TD
    Start([Pengguna Mengakses Website]) --> Login[Halaman Login: /login]
    Login --> AuthCheck{Pemeriksaan Kredensial & Role}
    
    AuthCheck -->|Role: Admin| AdminDash[Dashboard Admin: /admin/dashboard]
    AuthCheck -->|Role: Dosen| DosenDash[Dashboard Dosen: /dosen/dashboard]
    AuthCheck -->|Role: Mahasiswa| MhsDash[Dashboard Mahasiswa: /mahasiswa/dashboard]
    
    %% Alur Admin
    AdminDash --> SyncData[Sinkronisasi Data Kampus: /admin/campus-data]
    AdminDash --> Plotting[Ploting Kelas & Mahasiswa: /admin/classes]
    
    %% Alur Dosen
    DosenDash --> FilterSem[Filter Semester Aktif]
    FilterSem --> SetupSession[Pengaturan Sesi Kuliah: /dosen/classes/{id}/setup]
    SetupSession --> ModeChoice{Pilihan Mode Perkuliahan}
    
    ModeChoice -->|Offline / Tatap Muka| OfflineAtt[Lembar Presensi Offline: /dosen/lectures/{id}/attendance]
    OfflineAtt --> SaveAtt[Simpan Status: Hadir/Izin/Sakit/Alpa]
    OfflineAtt --> ExportAtt[Unduh Rekap Presensi CSV]
    
    ModeChoice -->|Online / Daring| VirtualRoom[Ruang Kelas Virtual 3-in-1: /room/{code}]
    
    %% Alur Mahasiswa
    MhsDash --> AdaptCheck{Pemeriksaan Pengaturan Sesi Dosen}
    AdaptCheck -->|Dosen Atur Offline| MhsOfflineView[Lihat Ruang Fisik, Topik & Status Presensi Anda]
    AdaptCheck -->|Dosen Atur Online| VirtualRoom
    AdaptCheck -->|Belum Ada Sesi| MhsWaiting[Status: Menunggu Dosen Memulai Sesi]
    
    %% Di dalam Ruang Virtual 3-in-1
    VirtualRoom --> SyncSlide[Sinkronisasi Slide Presentasi PDF.js/PPTX]
    VirtualRoom --> JitsiVideo[Tatap Muka Video Jitsi Meet SDK]
    VirtualRoom --> ChatBoard[Papan Chat Diskusi & Unduh Log CSV]
    VirtualRoom --> RaiseHand[Interaksi Raise Hand & Mute All Host]
```

---

## 3. Rincian Modul, Alur, dan Lokasi Koding

### 3.1 Modul Autentikasi & Kontrol Akses (RBAC)

#### Alur Kerja:
1. Pengguna membuka URL root (`/`), sistem memeriksa status login di [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L10-L23).
2. Jika belum login, dialihkan ke form login di [`/login`](file:///c:/laragon/www/project-3in1/resources/views/auth/login.blade.php).
3. Pengguna memasukkan `username` (NIM untuk mahasiswa, NIDN/NIP untuk dosen, atau `admin`) dan `password`.
4. Controller memvalidasi kredensial, memulai session, dan mengarahkan pengguna sesuai perannya:
   - `admin` &rarr; `admin.dashboard`
   - `dosen` &rarr; `dosen.dashboard`
   - `mahasiswa` &rarr; `mahasiswa.dashboard`
5. Setiap rute dilindungi oleh middleware `auth` dan `role:admin`, `role:dosen,admin`, atau `role:mahasiswa`.

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L25-L31)
- **Controller**: [`app/Http/Controllers/AuthController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/AuthController.php) (`showLogin`, `login`, `logout`)
- **Model**: [`app/Models/User.php`](file:///c:/laragon/www/project-3in1/app/Models/User.php) (`isAdmin()`, `isDosen()`, `isMahasiswa()`)
- **View**: [`resources/views/auth/login.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/auth/login.blade.php)
- **Pengujian**: [`tests/Feature/VirtualClassroomAuthRbacTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomAuthRbacTest.php)

---

### 3.2 Modul Administrator (Master Data API & Ploting Akademik)

#### Alur Kerja:
1. **Sinkronisasi Data Kampus**: Admin mengakses menu Data Kampus untuk mengambil data master Program Studi, Dosen, Mahasiswa, Matakuliah, dan Jadwal dari API Kampus (atau mock API).
2. **Manajemen Kelas Perkuliahan**: Admin membuat kelas perkuliahan resmi (`CourseClass`), menetapkan Dosen Pengampu, semester, tahun ajaran, hari, jam perkuliahan, dan ruang.
3. **Ploting Mahasiswa**: Admin memilih mahasiswa yang terdaftar pada kelas tersebut (`course_class_students`). Mahasiswa yang di-ploting akan otomatis memiliki akses ke perkuliahan dan tercatat di presensi.

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L34-L44)
- **Controller Ploting Kelas**: [`app/Http/Controllers/AdminCourseClassController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/AdminCourseClassController.php)
- **Controller Sinkronisasi API**: [`app/Http/Controllers/CampusDataController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/CampusDataController.php)
- **Service API Kampus**: [`app/Services/CampusApiService.php`](file:///c:/laragon/www/project-3in1/app/Services/CampusApiService.php)
- **Model & Database**:
  - Model [`CourseClass`](file:///c:/laragon/www/project-3in1/app/Models/CourseClass.php)
  - Migrasi `course_classes` & `course_class_students`
- **Views**:
  - [`resources/views/admin/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/admin/dashboard.blade.php)
  - [`resources/views/admin/classes/index.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/admin/classes/index.blade.php)
  - [`resources/views/admin/classes/create.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/admin/classes/create.blade.php)
  - [`resources/views/admin/classes/plotting.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/admin/classes/plotting.blade.php)
  - [`resources/views/campus_data/index.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/campus_data/index.blade.php)
- **Pengujian**: [`tests/Feature/AdminCourseClassPlottingTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/AdminCourseClassPlottingTest.php)

---

### 3.3 Modul Dosen: Dashboard & Pengaturan Sesi Perkuliahan

#### Alur Kerja:
1. **Dashboard Dosen Ringkas**:
   - Menampilkan filter semester (default semester aktif, misalnya `2026/2027 Ganjil`).
   - Hanya menampilkan kartu kelas yang ditugaskan kepada Dosen bersangkutan hasil ploting Admin.
   - Pada kartu kelas terdapat informasi: Kode MK, Kelas, SKS, Jadwal, Ruang, Jumlah Mahasiswa, dan tombol utama **"Atur & Mulai Perkuliahan"**.
2. **Halaman Pengaturan Sesi (`/dosen/classes/{class}/setup`)**:
   - Dosen menentukan:
     - **Pertemuan ke-** (1 s/d 16, otomatis merekomendasikan nomor pertemuan berikutnya).
     - **Tanggal Pelaksanaan** (default tanggal hari ini).
     - **Topik / Materi Perkuliahan** (opsional).
   - Memilih salah satu dari 2 Mode Perkuliahan:
     - **Mode Online (Daring)**: Membuat/menghubungkan ke `VirtualClassroom` dan mengalihkan Dosen ke Ruang Virtual 3-in-1.
     - **Mode Offline (Tatap Muka)**: Membuat sesi `CourseLectureSession` (mode `offline`), otomatis mengisi data seluruh mahasiswa kelas ke tabel presensi, dan mengalihkan Dosen ke Lembar Presensi Tatap Muka.

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L47-L55)
- **Controller Dashboard**: [`app/Http/Controllers/VirtualClassroomController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L35-L142) (`dosenDashboard`)
- **Controller Pengaturan Sesi**: [`app/Http/Controllers/Dosen/DosenLectureSessionController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/Dosen/DosenLectureSessionController.php#L22-L135) (`setup`, `store`)
- **Model**:
  - [`app/Models/CourseLectureSession.php`](file:///c:/laragon/www/project-3in1/app/Models/CourseLectureSession.php)
  - [`app/Models/CourseClass.php`](file:///c:/laragon/www/project-3in1/app/Models/CourseClass.php#L72-L99) (`nextMeetingNumber()`, `latestLectureSession()`)
- **Views**:
  - [`resources/views/dosen/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/dashboard.blade.php)
  - [`resources/views/dosen/classes/session_setup.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/classes/session_setup.blade.php)
- **Pengujian**:
  - [`tests/Feature/DosenClassIntegrationTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenClassIntegrationTest.php)
  - [`tests/Feature/DosenLectureSessionSetupTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenLectureSessionSetupTest.php)

---

### 3.4 Modul Dosen: Lembar Presensi Tatap Muka (Offline Mode)

#### Alur Kerja:
1. Dosen tiba di URL `/dosen/lectures/{session}/attendance`.
2. Header menampilkan nomor Pertemuan, Tanggal, Ruang Fisik, Nama Mata Kuliah, dan Kelas.
3. Widget statistik ringkasan menghitung jumlah **Hadir, Izin, Sakit, Alpa** secara dinamis.
4. Terdapat tombol cepat **"Tandai Semua Hadir"** untuk memilih radio Hadir pada seluruh mahasiswa dalam 1 klik.
5. Dosen dapat mengubah radio button status tiap mahasiswa (`hadir`, `izin`, `sakit`, `alpa`) serta menambahkan catatan/keterangan jika ada (misalnya: *Surat dokter terlampir*).
6. Mengklik **"Simpan Presensi Perkuliahan"** mengirim data form POST untuk memperbarui database.
7. Mengklik **"Unduh CSV"** mengunduh rekapan presensi terformat lengkap dengan identitas perkuliahan, NIM, nama mahasiswa, status, catatan, dan timestamp kehadiran.

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L53-L55)
- **Controller**: [`app/Http/Controllers/Dosen/DosenLectureSessionController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/Dosen/DosenLectureSessionController.php#L137-L235) (`showAttendance`, `saveAttendance`, `exportAttendanceCsv`)
- **Model**: [`app/Models/CourseLectureAttendance.php`](file:///c:/laragon/www/project-3in1/app/Models/CourseLectureAttendance.php)
- **Migrasi Database**: `database/migrations/2026_11_10_080100_create_course_lecture_attendances_table.php`
- **View**: [`resources/views/dosen/lectures/attendance.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/lectures/attendance.blade.php)
- **Pengujian**: [`tests/Feature/DosenLectureAttendanceTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenLectureAttendanceTest.php)

---

### 3.5 Modul Mahasiswa (Peserta Perkuliahan)

#### Alur Kerja:
1. Mahasiswa login dan diarahkan ke [`/mahasiswa/dashboard`](file:///c:/laragon/www/project-3in1/resources/views/mahasiswa/dashboard.blade.php).
2. Sistem memuat seluruh kelas yang diikuti mahasiswa berdasarkan ploting Admin (`enrolledClasses`).
3. **Kartu Kelas Beradaptasi Otomatis Mengikuti Pengaturan Dosen**:
   - **Jika Dosen Membuka Sesi Offline (Tatap Muka)**:
     - Badge: `🏫 KULIAH TATAP MUKA` &bull; `Pertemuan ke-X`.
     - Tampil detail ruang kelas fisik dan topik yang ditentukan dosen.
     - Menampilkan badge **Status Kehadiran Mahasiswa** (`Hadir`, `Izin`, `Sakit`, `Alpa`, atau `Belum Dicatat`).
     - Tombol aksi menampilkan info: *"Kuliah Tatap Muka di Ruang: [Nama Ruang]"*.
   - **Jika Dosen Membuka Sesi Online (Daring)**:
     - Badge: `🔴 KELAS SEDANG LIVE` &bull; `Pertemuan ke-X (Online)`.
     - Tampil topik materi perkuliahan online.
     - Tombol aksi: **"Masuk ke Kelas"** mengarahkan langsung ke Ruang Virtual 3-in-1.
   - **Jika Belum Ada Sesi yang Dibuka**:
     - Status: `Belum Ada Sesi Aktif` dan tombol `Menunggu Dosen Memulai Sesi`.
4. Tersedia juga form manual input kode room untuk bergabung ke sesi ad-hoc / darurat.

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L58-L62)
- **Controller**: [`app/Http/Controllers/VirtualClassroomController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L145-L182) (`mahasiswaDashboard`, `joinRoom`)
- **View**: [`resources/views/mahasiswa/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/mahasiswa/dashboard.blade.php)
- **Pengujian**: [`tests/Feature/MahasiswaClassIntegrationTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/MahasiswaClassIntegrationTest.php)

---

### 3.6 Modul Ruang Virtual 3-in-1 (Single-Window Interface)

#### Alur Kerja:
Ruang kelas virtual dibuka melalui rute `/room/{room_code}`. Halaman ini adalah antarmuka tunggal (*single-page*) dengan fitur:

1. **Panel Panggung Kiri (Dokumen Presentasi Interaktif)**:
   - Dosen mengunggah materi perkuliahan (PDF, PPT, atau PPTX).
   - Dokumen PPTX dikonversi atau diparsing oleh [`PresentationService`](file:///c:/laragon/www/project-3in1/app/Services/PresentationService.php).
   - Slide materi dirender secara visual ke elemen `<canvas id="pdf-render-canvas">` menggunakan **PDF.js**.
   - Dosen memiliki tombol kontrol: `Next Slide`, `Prev Slide`, dan `Upload Materi`.
   - Perpindahan slide memicu permintaan AJAX POST ke `/classroom/{room_code}/slide`.

2. **Sinkronisasi Dokumen Real-Time**:
   - Seluruh peserta (mahasiswa) melakukan polling otomatis ke `/classroom/{room_code}/state`.
   - Begitu nomor slide pada state berubah, canvas PDF.js di layar mahasiswa langsung me-render halaman slide yang sama secara otomatis tanpa me-refresh halaman.
   - **Tombol Sync Lock**: Mahasiswa dapat mematikan tombol kunci slide (`#sync-lock-btn`) untuk melihat halaman lain secara bebas (*self-paced navigation*), dan dapat kembali mengikuti dosen dengan tombol *"Ikuti Dosen (Slide X)"*.

3. **Panel Tatap Muka Video Conference (Jitsi Meet SDK)**:
   - Menggunakan SDK resmi Jitsi Meet (`meet.jit.si/external_api.js`).
   - Tertanam berdampingan dengan presentasi dokumen.
   - Pengguna dapat memilih mode tampilan panggung:
     - **Split 50:50**: Slide presentasi bersisian dengan video conference.
     - **Presentasi Penuh**: Fokus layar penuh pada dokumen materi.
     - **Tatap Muka Penuh**: Fokus layar penuh pada video conference peserta.
   - Disediakan tombol toolbar sentral: Toggle Mic, Toggle Kamera, dan Share Screen.

4. **Resizable Splitter Bar (`#drag-splitter`)**:
   - Pembatas fleksibel antara area panggung (kiri) dan sidebar (kanan) yang dapat digeser (*drag*) dengan mouse atau touch screen untuk menyesuaikan ukuran layar sesuai kebutuhan pengguna.

5. **Panel Sidebar Kanan (Interaksi & Diskusi)**:
   - **Tab Peserta**: Menampilkan daftar peserta yang terhubung secara real-time, status mikrofon, dan indikator angkat tangan.
   - **Tab Diskusi (Chat)**: Papan obrolan kelas permanen dengan riwayat tersimpan di tabel `classroom_chats`.
   - **Fitur Interaksi Raise Hand**: Mahasiswa dapat mengklik tombol angkat tangan, dan notifikasi visual membal langsung di layar Dosen.
   - **Fitur Mute All**: Dosen dapat membisukan mikrofon seluruh peserta sekaligus dengan tombol *"Mute All Peserta"*.
   - **Ekspor Dokumen**: Dosen dapat mengunduh Rekap Presensi Virtual (CSV) dan Rekap Log Chat (CSV).

#### Lokasi Koding Terkait:
- **Rute**: [`routes/web.php`](file:///c:/laragon/www/project-3in1/routes/web.php#L64-L70)
- **Controller Ruang Kelas**: [`app/Http/Controllers/VirtualClassroomController.php`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L280-L678)
  - `showRoom`: Menyiapkan tampilan ruang kelas dan mencatat presensi pertama kali.
  - `getRoomState`: Endpoint JSON state sinkronisasi real-time (slide, peserta, status mic, status angkat tangan).
  - `updateSlide`: Dosen memperbarui nomor slide aktif.
  - `uploadPresentation`: Menyimpan dan memproses file presentasi (PDF/PPT/PPTX).
  - `getPresentationFile`: Streaming file dokumen ke viewer PDF.js.
  - `sendChat`: Menyimpan dan menyebarkan pesan obrolan kelas.
  - `toggleRaiseHand`: Mengubah status angkat tangan mahasiswa.
  - `toggleMic`: Mengubah status mikrofon.
  - `toggleMuteAll`: Host mengaktifkan pembisuan massal.
  - `exportAttendance`: Mengunduh CSV daftar hadir virtual.
  - `exportChat`: Mengunduh CSV transkrip obrolan.
- **Service Presentasi**: [`app/Services/PresentationService.php`](file:///c:/laragon/www/project-3in1/app/Services/PresentationService.php)
- **Model Terkait**:
  - [`app/Models/VirtualClassroom.php`](file:///c:/laragon/www/project-3in1/app/Models/VirtualClassroom.php)
  - [`app/Models/ClassroomAttendance.php`](file:///c:/laragon/www/project-3in1/app/Models/ClassroomAttendance.php)
  - [`app/Models/ClassroomChat.php`](file:///c:/laragon/www/project-3in1/app/Models/ClassroomChat.php)
- **View Ruang Kelas Utama**: [`resources/views/classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php)
- **Pengujian**:
  - [`tests/Feature/VirtualClassroomRoomTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomRoomTest.php)
  - [`tests/Feature/VirtualClassroomAuthRbacTest.php`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomAuthRbacTest.php)

---

## 4. Tabel Referensi Cepat Fitur & Lokasi Kode

| Fitur / Modul | Controller & Method | Model & Migrasi | File View (Blade) | File Pengujian (Test) |
| :--- | :--- | :--- | :--- | :--- |
| **Login & Logout Multi-Role** | [`AuthController`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/AuthController.php) (`login`, `logout`) | [`User`](file:///c:/laragon/www/project-3in1/app/Models/User.php) & `users` | [`auth/login.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/auth/login.blade.php) | [`VirtualClassroomAuthRbacTest`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomAuthRbacTest.php) |
| **Ploting Kelas Admin** | [`AdminCourseClassController`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/AdminCourseClassController.php) | [`CourseClass`](file:///c:/laragon/www/project-3in1/app/Models/CourseClass.php) & `course_classes` | [`admin/classes/`](file:///c:/laragon/www/project-3in1/resources/views/admin/classes/) | [`AdminCourseClassPlottingTest`](file:///c:/laragon/www/project-3in1/tests/Feature/AdminCourseClassPlottingTest.php) |
| **Dashboard Dosen** | [`VirtualClassroomController@dosenDashboard`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L35) | [`CourseClass`](file:///c:/laragon/www/project-3in1/app/Models/CourseClass.php), [`VirtualClassroom`](file:///c:/laragon/www/project-3in1/app/Models/VirtualClassroom.php) | [`dosen/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/dashboard.blade.php) | [`DosenClassIntegrationTest`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenClassIntegrationTest.php) |
| **Pengaturan Sesi (Online vs Offline)** | [`DosenLectureSessionController@setup`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/Dosen/DosenLectureSessionController.php#L22), `store` | [`CourseLectureSession`](file:///c:/laragon/www/project-3in1/app/Models/CourseLectureSession.php) & `course_lecture_sessions` | [`dosen/classes/session_setup.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/classes/session_setup.blade.php) | [`DosenLectureSessionSetupTest`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenLectureSessionSetupTest.php) |
| **Lembar Presensi Offline & CSV** | [`DosenLectureSessionController@showAttendance`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/Dosen/DosenLectureSessionController.php#L137), `saveAttendance`, `exportAttendanceCsv` | [`CourseLectureAttendance`](file:///c:/laragon/www/project-3in1/app/Models/CourseLectureAttendance.php) & `course_lecture_attendances` | [`dosen/lectures/attendance.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/dosen/lectures/attendance.blade.php) | [`DosenLectureAttendanceTest`](file:///c:/laragon/www/project-3in1/tests/Feature/DosenLectureAttendanceTest.php) |
| **Dashboard Mahasiswa** | [`VirtualClassroomController@mahasiswaDashboard`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L145) | [`CourseClass`](file:///c:/laragon/www/project-3in1/app/Models/CourseClass.php), [`CourseLectureSession`](file:///c:/laragon/www/project-3in1/app/Models/CourseLectureSession.php) | [`mahasiswa/dashboard.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/mahasiswa/dashboard.blade.php) | [`MahasiswaClassIntegrationTest`](file:///c:/laragon/www/project-3in1/tests/Feature/MahasiswaClassIntegrationTest.php) |
| **Ruang Virtual 3-in-1 (Single Window)** | [`VirtualClassroomController@showRoom`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L280) | [`VirtualClassroom`](file:///c:/laragon/www/project-3in1/app/Models/VirtualClassroom.php) & `virtual_classrooms` | [`classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php) | [`VirtualClassroomRoomTest`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomRoomTest.php) |
| **Sinkronisasi Dokumen PDF/PPTX** | [`VirtualClassroomController@updateSlide`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L400), `uploadPresentation`, `getPresentationFile` | [`PresentationService`](file:///c:/laragon/www/project-3in1/app/Services/PresentationService.php) | [`classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php) | [`VirtualClassroomRoomTest`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomRoomTest.php) |
| **Tatap Muka Video (Jitsi SDK)** | Jitsi External API SDK di [`classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php#L807) | `config/services.php` (`jitsi.domain`) | [`classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php) | [`VirtualClassroomRoomTest`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomRoomTest.php) |
| **Papan Diskusi Chat & Ekspor CSV** | [`VirtualClassroomController@sendChat`](file:///c:/laragon/www/project-3in1/app/Http/Controllers/VirtualClassroomController.php#L480), `exportChat` | [`ClassroomChat`](file:///c:/laragon/www/project-3in1/app/Models/ClassroomChat.php) & `classroom_chats` | [`classroom/room.blade.php`](file:///c:/laragon/www/project-3in1/resources/views/classroom/room.blade.php) | [`VirtualClassroomRoomTest`](file:///c:/laragon/www/project-3in1/tests/Feature/VirtualClassroomRoomTest.php) |

---

## 5. Ringkasan Status Verifikasi

- **Status Kode & Fungsionalitas**: Seluruh modul bekerja secara utuh dan terintegrasi dari Admin, Dosen, hingga Mahasiswa.
- **Pengujian Terotomasi**: Seluruh **82 skenario pengujian PHPUnit lulus 100%** (`82 passed, 406 assertions`).
- **Gaya Kode**: Terformat rapi sesuai standar resmi Laravel menggunakan Laravel Pint.
