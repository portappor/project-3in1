@extends('layouts.app')

@section('title', 'Ploting Mahasiswa Kelas ' . $class->nama_mk . ' - 3in1')

@section('content')
<div class="space-y-6">

    <!-- Header & Class Info Banner -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3 text-xs font-semibold text-slate-500 mb-2">
            <a href="{{ route('admin.classes.index') }}" class="hover:text-blue-600 transition flex items-center space-x-1">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar Kelas
            </a>
            <span>&bull;</span>
            <span class="text-blue-600">Ploting Mahasiswa</span>
        </div>

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        {{ $class->nama_kelas }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ $class->prodi }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 text-slate-800">
                        {{ $class->kode_mk }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Ploting Mahasiswa: {{ $class->nama_mk }}
                </h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-2">
                    <span class="flex items-center space-x-1">
                        <i class="fa-solid fa-user-tie text-purple-600"></i>
                        <span>Dosen: <strong class="text-slate-800">{{ $class->dosen->name ?? 'Belum Ditentukan' }}</strong></span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center space-x-1">
                        <i class="fa-regular fa-clock text-blue-500"></i>
                        <span>{{ $class->hari }}, {{ $class->jam_mulai }} - {{ $class->jam_selesai }}</span>
                    </span>
                    @if($class->ruang)
                        <span>&bull;</span>
                        <span>Ruang: {{ $class->ruang }}</span>
                    @endif
                    <span>&bull;</span>
                    <span>{{ $class->sks }} SKS &bull; Semester {{ $class->semester }}</span>
                </div>
            </div>

            <!-- Total Plotted Counter Badge -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-3 text-center min-w-[160px]">
                <span class="text-xs font-semibold text-blue-700 block">Mahasiswa Terploting</span>
                <span class="text-2xl font-black text-blue-900 mt-0.5 block" id="header-plotted-count">
                    {{ count($currentStudentIds) }}
                </span>
                <span class="text-[11px] text-blue-600">Peserta Terdaftar</span>
            </div>
        </div>

        @if(session('success'))
            <div class="mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs sm:text-sm text-emerald-800 flex items-start space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                <div>
                    <span class="font-bold">Berhasil!</span> {{ session('success') }}
                </div>
            </div>
        @endif
    </div>

    <!-- Filter & Search Mahasiswa (GET) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.classes.plotting', $class) }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari berdasarkan nama mahasiswa atau NIM / Username..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="md:col-span-4">
                <select name="prodi"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option value="">Semua Program Studi</option>
                    @foreach($prodiList as $prodi)
                        <option value="{{ $prodi }}" {{ $selectedProdi === $prodi ? 'selected' : '' }}>
                            {{ $prodi }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 flex space-x-2">
                <button type="submit"
                    class="w-full px-3 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>Cari</span>
                </button>
                @if($search !== '' || $selectedProdi !== '')
                    <a href="{{ route('admin.classes.plotting', $class) }}"
                        class="px-3 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition flex items-center justify-center"
                        title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Student Selection & Plotting Form (POST) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('admin.classes.plotting.save', $class) }}" id="plotting-form">
            @csrf

            <!-- Bulk Selection Action Bar -->
            <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-2">
                    <button type="button" id="btn-select-all"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 transition flex items-center space-x-1.5 shadow-sm">
                        <i class="fa-solid fa-check-double text-blue-600"></i>
                        <span>Pilih Semua</span>
                    </button>

                    <button type="button" id="btn-deselect-all"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition flex items-center space-x-1.5 shadow-sm">
                        <i class="fa-solid fa-xmark text-red-600"></i>
                        <span>Batal Pilih Semua</span>
                    </button>

                    <span class="text-xs text-slate-500 pl-2 border-l border-slate-200 hidden sm:inline">
                        <span id="selected-count" class="font-bold text-slate-800">0</span> mahasiswa terpilih
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('admin.classes.index') }}"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                        Batal
                    </a>

                    <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition flex items-center space-x-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Ploting Kelas</span>
                    </button>
                </div>
            </div>

            <!-- Table of Students -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100/80 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">
                                <input type="checkbox" id="master-checkbox"
                                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer">
                            </th>
                            <th class="py-3 px-4">NIM / Username</th>
                            <th class="py-3 px-4">Nama Mahasiswa</th>
                            <th class="py-3 px-4">Program Studi</th>
                            <th class="py-3 px-4 text-center">Status Ploting</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($students as $student)
                            @php
                                $isPlotted = in_array($student->id, $currentStudentIds);
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition cursor-pointer student-row {{ $isPlotted ? 'bg-blue-50/30' : '' }}"
                                data-student-id="{{ $student->id }}">
                                <td class="py-3 px-4 text-center" onclick="event.stopPropagation();">
                                    <input type="checkbox" name="students[]" value="{{ $student->id }}"
                                        {{ $isPlotted ? 'checked' : '' }}
                                        class="student-checkbox w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer">
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-800">
                                    {{ $student->username }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-[11px]">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ $student->name }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $student->email ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $student->prodi ?? 'Fakultas Teknik' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($isPlotted)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-check mr-1 text-emerald-600"></i> Terploting
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                            Belum Terploting
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-user-slash text-2xl text-slate-300 mb-2 block"></i>
                                    Tidak ada data mahasiswa yang ditemukan dengan kriteria filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Save Bar -->
            <div class="p-4 sm:p-5 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Menampilkan total {{ $students->count() }} mahasiswa
                </span>

                <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Ploting Kelas</span>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- JavaScript Bulk Checkbox Handler -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const masterCheckbox = document.getElementById('master-checkbox');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        const selectedCount = document.getElementById('selected-count');
        const btnSelectAll = document.getElementById('btn-select-all');
        const btnDeselectAll = document.getElementById('btn-deselect-all');
        const rows = document.querySelectorAll('.student-row');

        function updateCount() {
            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });
            if (selectedCount) selectedCount.textContent = count;
            if (masterCheckbox) {
                masterCheckbox.checked = (checkboxes.length > 0 && count === checkboxes.length);
                masterCheckbox.indeterminate = (count > 0 && count < checkboxes.length);
            }
        }

        if (masterCheckbox) {
            masterCheckbox.addEventListener('change', function () {
                checkboxes.forEach(cb => {
                    cb.checked = masterCheckbox.checked;
                    toggleRowBg(cb);
                });
                updateCount();
            });
        }

        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function () {
                checkboxes.forEach(cb => {
                    cb.checked = true;
                    toggleRowBg(cb);
                });
                updateCount();
            });
        }

        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', function () {
                checkboxes.forEach(cb => {
                    cb.checked = false;
                    toggleRowBg(cb);
                });
                updateCount();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                toggleRowBg(cb);
                updateCount();
            });
        });

        // Click row to toggle checkbox
        rows.forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.tagName.toLowerCase() !== 'input') {
                    const cb = row.querySelector('.student-checkbox');
                    if (cb) {
                        cb.checked = !cb.checked;
                        toggleRowBg(cb);
                        updateCount();
                    }
                }
            });
        });

        function toggleRowBg(cb) {
            const row = cb.closest('tr');
            if (row) {
                if (cb.checked) {
                    row.classList.add('bg-blue-50/40');
                } else {
                    row.classList.remove('bg-blue-50/40');
                }
            }
        }

        // Initialize count on load
        updateCount();
    });
</script>
@endsection
