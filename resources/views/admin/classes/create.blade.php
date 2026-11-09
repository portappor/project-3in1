@extends('layouts.app')

@section('title', 'Tambah Kelas Perkuliahan Baru - 3in1')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3 text-xs font-semibold text-slate-500 mb-2">
            <a href="{{ route('admin.classes.index') }}" class="hover:text-blue-600 transition flex items-center space-x-1">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar Kelas
            </a>
            <span>&bull;</span>
            <span class="text-blue-600">Tambah Kelas Baru</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Formulir Kelas Perkuliahan Baru</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Pilih matakuliah dari katalog 795 matakuliah API Kampus, tetapkan dosen pengampu, serta waktu jadwal pertemuan.
        </p>

        @if($errors->any())
            <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-xs text-red-800">
                <span class="font-bold block mb-1">Periksa kembali isian formulir:</span>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.classes.store') }}" class="space-y-6" id="class-form">
            @csrf

            <!-- Section 1: Pemilihan Matakuliah API Kampus -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        <i class="fa-solid fa-book-bookmark text-blue-600 mr-1.5"></i> 1. Pilih Matakuliah (Katalog API Kampus)
                    </label>
                    <span class="text-[11px] text-slate-500">
                        {{ count($matakuliahList) }} Matakuliah Tersedia
                    </span>
                </div>

                <div class="relative">
                    <input list="matakuliah-datalist" id="matakuliah_selector" type="text"
                        placeholder="Ketik untuk mencari nama atau kode matakuliah dari API kampus..."
                        class="w-full px-4 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-slate-50">
                    <datalist id="matakuliah-datalist">
                        @foreach($matakuliahList as $mk)
                            <option value="{{ $mk['kode'] }} - {{ $mk['nama'] }} ({{ $mk['prodi'] ?? '-' }})"
                                data-kode="{{ $mk['kode'] }}"
                                data-nama="{{ $mk['nama'] }}"
                                data-sks="{{ $mk['sks'] ?? 3 }}"
                                data-prodi="{{ $mk['prodi'] ?? 'Teknik Informatika' }}"
                                data-semester="{{ $mk['semester'] ?? 1 }}">
                        @endforeach
                    </datalist>
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5">
                    <i class="fa-solid fa-circle-info mr-1 text-blue-500"></i> Memilih salah satu matakuliah dari daftar akan mengisi kolom kode, nama, SKS, prodi, dan semester secara otomatis.
                </p>
            </div>

            <!-- Auto-filled / Manual Course Details -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Matakuliah <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_mk" id="kode_mk" value="{{ old('kode_mk') }}" required
                        placeholder="Contoh: TIF201"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="sm:col-span-8">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Matakuliah <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_mk" id="nama_mk" value="{{ old('nama_mk') }}" required
                        placeholder="Contoh: Pemrograman Web Lanjut"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1">SKS <span class="text-red-500">*</span></label>
                    <input type="number" name="sks" id="sks" value="{{ old('sks', 3) }}" min="1" max="10" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Semester <span class="text-red-500">*</span></label>
                    <input type="number" name="semester" id="semester" value="{{ old('semester', 1) }}" min="1" max="8" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi <span class="text-red-500">*</span></label>
                    <select name="prodi" id="prodi" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih Program Studi</option>
                        @foreach($prodiList as $prodi)
                            <option value="{{ $prodi }}" {{ old('prodi') === $prodi ? 'selected' : '' }}>
                                {{ $prodi }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Section 2: Kelas & Dosen Pengampu -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-chalkboard-user text-purple-600 mr-1.5"></i> 2. Identitas Kelas & Dosen Pengampu
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama / Kode Kelas <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_kelas" value="{{ old('nama_kelas', 'Kelas A') }}" required
                            placeholder="Contoh: Kelas A, Kelas B, atau Pagi/Sore"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Dosen Pengampu (Host) <span class="text-red-500">*</span></label>
                        <select name="dosen_id" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Dosen Pengampu</option>
                            @foreach($dosenList as $dosen)
                                <option value="{{ $dosen->id }}" {{ old('dosen_id') == $dosen->id ? 'selected' : '' }}>
                                    {{ $dosen->name }} ({{ $dosen->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-12">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', '2026/2027 Ganjil') }}"
                            placeholder="Contoh: 2026/2027 Ganjil"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Waktu & Tempat Perkuliahan -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">
                    <i class="fa-regular fa-clock text-emerald-600 mr-1.5"></i> 3. Jadwal & Ruang Perkuliahan
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Hari Kuliah <span class="text-red-500">*</span></label>
                        <select name="hari" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h)
                                <option value="{{ $h }}" {{ old('hari', 'Senin') === $h ? 'selected' : '' }}>
                                    {{ $h }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai <span class="text-red-500">*</span></label>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', '08:00') }}" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai <span class="text-red-500">*</span></label>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', '10:30') }}" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="sm:col-span-12">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ruang Kuliah / Lab (Opsional)</label>
                        <input type="text" name="ruang" value="{{ old('ruang', 'Lab Komputer 1') }}"
                            placeholder="Contoh: Lab Komputer 1, Ruang F.204, atau Ruang Virtual"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('admin.classes.index') }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>

                <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Simpan & Lanjut Ploting Mahasiswa</span>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- JavaScript Auto-fill Handler -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selector = document.getElementById('matakuliah_selector');
        const datalist = document.getElementById('matakuliah-datalist');
        const kodeInput = document.getElementById('kode_mk');
        const namaInput = document.getElementById('nama_mk');
        const sksInput = document.getElementById('sks');
        const prodiSelect = document.getElementById('prodi');
        const semesterInput = document.getElementById('semester');

        selector.addEventListener('input', function () {
            const val = this.value;
            const options = Array.from(datalist.options);
            const matched = options.find(opt => opt.value === val);

            if (matched) {
                if (matched.dataset.kode) kodeInput.value = matched.dataset.kode;
                if (matched.dataset.nama) namaInput.value = matched.dataset.nama;
                if (matched.dataset.sks) sksInput.value = matched.dataset.sks;
                if (matched.dataset.semester) semesterInput.value = matched.dataset.semester;

                if (matched.dataset.prodi) {
                    const targetProdi = matched.dataset.prodi.toLowerCase();
                    for (let opt of prodiSelect.options) {
                        if (opt.value && (targetProdi.includes(opt.value.toLowerCase()) || opt.value.toLowerCase().includes(targetProdi))) {
                            prodiSelect.value = opt.value;
                            break;
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
