# Design Document: Dosen Lecture Mode Selection (Online vs. Offline) & Offline Attendance System

## 1. Context & Motivation
Currently, when a Dosen clicks on a class card on the dashboard, it directly attempts to create or enter a virtual classroom (`VirtualClassroom`). However, academic courses at the institution consist of both **Online (Daring)** and **Offline (Tatap Muka di Kelas Fisik)** lectures.

The user requires:
1. Dosen clicks their scheduled course class on the dashboard.
2. Dosen is directed to a lecture session setup page to configure the meeting details (Pertemuan ke-1 s/d 16, Tanggal, Topik) and select the lecture mode: **Online** or **Offline**.
3. If **Offline** is selected, Dosen is redirected to a dedicated **Attendance-Only Page** (*Halaman Presensi Tatap Muka*) where Dosen records student attendance (Hadir, Sakit, Izin, Alpa) for enrolled students, saves attendance, and exports CSV.
4. If **Online** is selected, Dosen proceeds to the existing 3-in-1 Virtual Classroom with interactive slides, WebRTC, and real-time chat.

---

## 2. Architecture & Data Model

### 2.1 Database Schema

#### A. Table `course_lecture_sessions`
Represents an individual lecture meeting (e.g., Pertemuan 1, Pertemuan 2) for a course class:
- `id`: unsigned big integer, primary key, auto increment.
- `course_class_id`: foreign key referencing `course_classes(id)` on delete cascade.
- `dosen_id`: foreign key referencing `users(id)` on delete cascade.
- `pertemuan_ke`: unsigned tinyint (1–16).
- `tanggal`: date.
- `mode`: enum(`'online'`, `'offline'`).
- `topik`: string, nullable (topic / subject of the lecture).
- `ruang`: string, nullable (physical room or virtual room label).
- `virtual_classroom_id`: foreign key referencing `virtual_classrooms(id)` on delete set null, nullable.
- `status`: enum(`'aktif'`, `'selesai'`), default `'aktif'`.
- `created_at`, `updated_at`: timestamps.
- Index: `[course_class_id, pertemuan_ke]`.

#### B. Table `course_lecture_attendances`
Represents the attendance record of each enrolled student in a lecture session:
- `id`: unsigned big integer, primary key, auto increment.
- `lecture_session_id`: foreign key referencing `course_lecture_sessions(id)` on delete cascade.
- `student_id`: foreign key referencing `users(id)` on delete cascade.
- `status`: enum(`'hadir'`, `'izin'`, `'sakit'`, `'alpa'`), default `'hadir'`.
- `catatan`: string, nullable.
- `waktu_presensi`: timestamp, nullable.
- `created_at`, `updated_at`: timestamps.
- Unique Index: `[lecture_session_id, student_id]`.

### 2.2 Eloquent Models
1. **`App\Models\CourseLectureSession`**:
   - `belongsTo(CourseClass::class, 'course_class_id')`
   - `belongsTo(User::class, 'dosen_id')`
   - `belongsTo(VirtualClassroom::class, 'virtual_classroom_id')`
   - `hasMany(CourseLectureAttendance::class, 'lecture_session_id')`
   - Methods: `isOnline()`, `isOffline()`, `activeSession()`.

2. **`App\Models\CourseLectureAttendance`**:
   - `belongsTo(CourseLectureSession::class, 'lecture_session_id')`
   - `belongsTo(User::class, 'student_id')`

3. **Updates to `App\Models\CourseClass`**:
   - `hasMany(CourseLectureSession::class, 'course_class_id')`
   - Method `nextMeetingNumber()`: returns the next meeting number between 1 and 16.

---

## 3. User Flow & Routing

```
Dashboard Dosen
     │
     ▼ (Click "Atur Perkuliahan" on Class Card)
Halaman Pengaturan Sesi Perkuliahan (/dosen/classes/{class}/setup)
     │
     ├── Pilih Pertemuan (1-16), Tanggal, Topik
     │
     ├── [PILIH ONLINE] ──► Create CourseLectureSession (mode=online)
     │                     & link VirtualClassroom 
     │                     ──► Redirect ke Ruang Virtual 3-in-1 (/room/{room_code})
     │
     └── [PILIH OFFLINE] ─► Create CourseLectureSession (mode=offline)
                           & auto-populate CourseLectureAttendance
                           ──► Redirect ke Halaman Presensi Offline (/dosen/lectures/{id}/attendance)
```

### 3.1 Route Endpoints (`routes/web.php` under `role:dosen,admin`)
1. `GET /dosen/classes/{class}/setup` -> `name('dosen.classes.session_setup')`
   Renders the lecture session setup view for a specific course class.
2. `POST /dosen/classes/{class}/sessions` -> `name('dosen.classes.sessions.store')`
   Validates inputs, stores `CourseLectureSession`, and handles branching (Online vs. Offline).
3. `GET /dosen/lectures/{session}/attendance` -> `name('dosen.lectures.attendance')`
   Renders the offline attendance sheet for that lecture session.
4. `POST /dosen/lectures/{session}/attendance` -> `name('dosen.lectures.attendance.save')`
   Saves student attendance statuses (`hadir`, `izin`, `sakit`, `alpa`) and optional notes.
5. `GET /dosen/lectures/{session}/attendance/export` -> `name('dosen.lectures.attendance.export')`
   Streams CSV export of attendance with columns: No, NIM, Nama Mahasiswa, Status Kehadiran, Catatan, Tanggal & Waktu.

---

## 4. UI/UX Design

### 4.1 Class Card on Dosen Dashboard (`dosen.dashboard`)
- Preserves the simplified, clean card design approved in previous turns.
- Action Buttons:
  - Primary button: **"Atur Perkuliahan"** (navigates to `dosen.classes.session_setup`).
  - If a live online session is currently running: Displays **"Masuk Kembali ke Ruang Kelas"** and **"Presensi"** for quick access.

### 4.2 Lecture Setup Page (`dosen.classes.session_setup`)
- **Breadcrumbs**: Dashboard Dosen > Atur Perkuliahan.
- **Card Ringkasan Kelas**:
  - Kode MK & Nama Mata Kuliah
  - Nama Kelas & SKS & Semester
  - Jadwal (Hari, Jam) & Ruang
  - Jumlah Mahasiswa Terdaftar
- **Form Konfigurasi Pertemuan**:
  - Nomor Pertemuan: Dropdown (Pertemuan 1 s/d 16, default: nomor pertemuan berikutnya).
  - Tanggal: Input Date (default: `today`).
  - Topik Bahasan: Input Text nullable.
- **Kartu Pilihan Mode (Visual Mode Selector)**:
  - **Opsi A: Perkuliahan Online (Daring)**
    - Icon: Video camera & interactive screen.
    - Description: "Sesi interaktif virtual 3-in-1 dengan sinkronisasi slide materi presentasi (PPT/PDF), WebRTC, mic, chat, dan presensi otomatis."
    - Tombol: **"Mulai Kuliah Online"** (warna ungu solid).
  - **Opsi B: Perkuliahan Offline (Tatap Muka)**
    - Icon: Classroom chalkboard & physical attendance clipboard.
    - Description: "Perkuliahan langsung di ruang kelas fisik. Dosen langsung diarahkan ke lembar presensi untuk mencatat kehadiran mahasiswa."
    - Tombol: **"Buka Presensi Offline"** (warna emerald solid).
- **Tabel Riwayat Sesi Perkuliahan Kelas Ini**:
  - Menampilkan daftar pertemuan yang telah dibuat sebelumnya: Pertemuan ke, Tanggal, Mode (Badge Online/Offline), Topik, dan Aksi (Lihat Presensi / Masuk Room).

### 4.3 Offline Attendance Sheet (`dosen.lectures.attendance`)
- **Header Info**:
  - Pertemuan ke-X &bull; Tanggal pelaksanaan.
  - Mata Kuliah, Kelas, Ruang Fisik, Dosen Pengampu.
- **Kartu Statistik Kehadiran (Real-Time Counter)**:
  - Total Mahasiswa: N
  - Hadir: X (hijau)
  - Sakit: Y (kuning)
  - Izin: Z (biru)
  - Alpa: W (merah)
- **Tombol Pintas**:
  - *"Tandai Semua Hadir"* (JavaScript 1-klik untuk memilih radio 'Hadir' pada semua baris).
- **Tabel Mahasiswa**:
  - No | NIM | Nama Mahasiswa | Status Kehadiran (Radio Button Pills: `Hadir`, `Izin`, `Sakit`, `Alpa`) | Catatan.
- **Footer Actions**:
  - Tombol **"Simpan Presensi"** (POST form).
  - Tombol **"Unduh Rekap Presensi (CSV)"** (unduh CSV).
  - Link **"Kembali ke Pengaturan Kelas"** / **"Dashboard"**.

---

## 5. Security & Validation (RBAC)
- Only the assigned Dosen (`class->dosen_id === auth()->id()`) or an Admin can configure sessions and save attendance for the class.
- Status presensi enum validation: `['hadir', 'izin', 'sakit', 'alpa']`.
- Validations on session creation: `pertemuan_ke` between 1 and 16, `tanggal` required date format, `mode` in `['online', 'offline']`.

---

## 6. Testing Strategy
- Feature tests in `tests/Feature/DosenLectureModeTest.php`:
  1. Dosen can view the session setup page for their assigned class.
  2. Dosen cannot view session setup for another Dosen's class (403 Forbidden).
  3. Dosen can create an Online lecture session and is redirected to the virtual classroom room.
  4. Dosen can create an Offline lecture session and is redirected to the offline attendance page.
  5. Enrolled students are automatically populated in the offline attendance list.
  6. Dosen can update and save offline attendance statuses.
  7. Dosen can export offline attendance to CSV.
  8. Full test suite remains 100% passing (all 70 existing tests pass).

