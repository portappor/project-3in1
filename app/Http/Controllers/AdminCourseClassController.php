<?php

namespace App\Http\Controllers;

use App\Models\CourseClass;
use App\Models\User;
use App\Services\CampusApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCourseClassController extends Controller
{
    /**
     * Inisialisasi controller dengan layanan API Kampus.
     */
    public function __construct(protected CampusApiService $campusApi) {}

    /**
     * Menampilkan daftar seluruh kelas kuliah beserta jumlah mahasiswa terploting.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $selectedProdi = trim((string) $request->input('prodi', ''));

        $query = CourseClass::with(['dosen', 'students', 'virtualClassrooms'])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mk', 'like', "%{$search}%")
                    ->orWhere('kode_mk', 'like', "%{$search}%")
                    ->orWhere('nama_kelas', 'like', "%{$search}%")
                    ->orWhereHas('dosen', function ($d) use ($search) {
                        $d->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($selectedProdi !== '') {
            $query->where('prodi', $selectedProdi);
        }

        $classes = $query->paginate(12)->withQueryString();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        return view('admin.classes.index', compact('classes', 'search', 'selectedProdi', 'prodiList'));
    }

    /**
     * Menampilkan form pembuatan kelas baru yang terintegrasi dengan 795 matakuliah API Kampus.
     */
    public function create(): View
    {
        $dosenList = User::where('role', 'dosen')->orderBy('name')->get();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        $mkResult = $this->campusApi->getMatakuliah(['pagination' => 'off']);
        $matakuliahList = $mkResult['data']['data'] ?? $mkResult['data'] ?? [];

        return view('admin.classes.create', compact('dosenList', 'prodiList', 'matakuliahList'));
    }

    /**
     * Menyimpan kelas perkuliahan baru ke database dan lanjut ke halaman ploting mahasiswa.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mk' => 'required|string|max:50',
            'nama_mk' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:10',
            'nama_kelas' => 'required|string|max:100',
            'prodi' => 'required|string|max:100',
            'semester' => 'required|integer|min:1|max:8',
            'tahun_ajaran' => 'nullable|string|max:50',
            'dosen_id' => 'required|exists:users,id',
            'hari' => 'required|string|max:20',
            'jam_mulai' => 'required|string|max:10',
            'jam_selesai' => 'required|string|max:10',
            'ruang' => 'nullable|string|max:100',
        ]);

        if (empty($validated['tahun_ajaran'])) {
            $validated['tahun_ajaran'] = '2026/2027 Ganjil';
        }

        $courseClass = CourseClass::create($validated);

        return redirect()->route('admin.classes.plotting', $courseClass)
            ->with('success', "Kelas {$courseClass->nama_mk} ({$courseClass->nama_kelas}) berhasil dibuat! Silakan plot mahasiswa untuk kelas ini.");
    }

    /**
     * Menampilkan form edit data kelas perkuliahan.
     */
    public function edit(CourseClass $class): View
    {
        $dosenList = User::where('role', 'dosen')->orderBy('name')->get();
        $prodiList = array_keys(CampusApiService::TEKNIK_PRODI_SMS);

        $mkResult = $this->campusApi->getMatakuliah(['pagination' => 'off']);
        $matakuliahList = $mkResult['data']['data'] ?? $mkResult['data'] ?? [];

        return view('admin.classes.edit', compact('class', 'dosenList', 'prodiList', 'matakuliahList'));
    }

    /**
     * Memperbarui informasi kelas perkuliahan.
     */
    public function update(Request $request, CourseClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mk' => 'required|string|max:50',
            'nama_mk' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:10',
            'nama_kelas' => 'required|string|max:100',
            'prodi' => 'required|string|max:100',
            'semester' => 'required|integer|min:1|max:8',
            'tahun_ajaran' => 'nullable|string|max:50',
            'dosen_id' => 'required|exists:users,id',
            'hari' => 'required|string|max:20',
            'jam_mulai' => 'required|string|max:10',
            'jam_selesai' => 'required|string|max:10',
            'ruang' => 'nullable|string|max:100',
        ]);

        if (empty($validated['tahun_ajaran'])) {
            $validated['tahun_ajaran'] = '2026/2027 Ganjil';
        }

        $class->update($validated);

        return redirect()->route('admin.classes.index')
            ->with('success', "Data kelas {$class->nama_mk} ({$class->nama_kelas}) berhasil diperbarui!");
    }

    /**
     * Menampilkan antarmuka ploting mahasiswa massal ke kelas perkuliahan.
     */
    public function plotting(Request $request, CourseClass $class): View
    {
        $class->load(['dosen', 'students']);
        $currentStudentIds = $class->students->pluck('id')->toArray();

        $search = trim((string) $request->input('search', ''));
        $selectedProdi = trim((string) $request->input('prodi', ''));

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

    /**
     * Menyimpan data ploting mahasiswa (sinkronisasi bulk).
     */
    public function savePlotting(Request $request, CourseClass $class): RedirectResponse
    {
        $studentIds = $request->input('students', []);
        if (! is_array($studentIds)) {
            $studentIds = [];
        }

        $class->students()->sync($studentIds);

        $count = count($studentIds);

        return redirect()->route('admin.classes.index')
            ->with('success', "Berhasil mem-plot {$count} mahasiswa ke kelas {$class->nama_mk} ({$class->nama_kelas})!");
    }

    /**
     * Menghapus kelas perkuliahan beserta riwayat plotingnya.
     */
    public function destroy(CourseClass $class): RedirectResponse
    {
        $name = "{$class->nama_mk} ({$class->nama_kelas})";
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', "Kelas {$name} berhasil dihapus beserta data plotingnya.");
    }
}
