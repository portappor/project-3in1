@extends('layouts.app')

@section('title', 'Manajemen Kelas & Ploting Mahasiswa - 3in1')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200 mb-2">
                    <i class="fa-solid fa-graduation-cap mr-1.5 text-blue-600"></i> Akademik Fakultas Teknik
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Manajemen Kelas & Ploting Mahasiswa</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
                    Kelola kelas perkuliahan yang terintegrasi langsung dengan 795 matakuliah API Kampus, tentukan dosen pengampu, dan atur ploting mahasiswa peserta kelas untuk pembelajaran virtual 3-in-1.
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.classes.create') }}"
                    class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Kelas Baru</span>
                </a>
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

        @if(session('error'))
            <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-xs sm:text-sm text-red-800 flex items-start space-x-2">
                <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                <div>
                    <span class="font-bold">Gagal!</span> {{ session('error') }}
                </div>
            </div>
        @endif
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.classes.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Search Text -->
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari matakuliah, kode, nama kelas, atau nama dosen..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Prodi Filter -->
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

            <!-- Submit & Reset Buttons -->
            <div class="md:col-span-2 flex space-x-2">
                <button type="submit"
                    class="w-full px-3 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filter</span>
                </button>
                @if($search !== '' || $selectedProdi !== '')
                    <a href="{{ route('admin.classes.index') }}"
                        class="px-3 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition flex items-center justify-center"
                        title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Classes Grid List -->
    @if($classes->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($classes as $item)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <!-- Header Card: Prodi & Kelas Badge -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                {{ $item->prodi }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $item->nama_kelas }}
                            </span>
                        </div>

                        <!-- Matakuliah Info -->
                        <h3 class="text-base font-bold text-slate-900 hover:text-blue-600 transition">
                            {{ $item->nama_mk }}
                        </h3>
                        <div class="flex items-center space-x-2 text-xs text-slate-500 mt-1">
                            <span class="font-mono font-semibold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">
                                {{ $item->kode_mk }}
                            </span>
                            <span>&bull;</span>
                            <span>{{ $item->sks }} SKS</span>
                            <span>&bull;</span>
                            <span>Semester {{ $item->semester }}</span>
                        </div>

                        <!-- Dosen & Jadwal Info -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                                <span class="font-medium text-slate-800 truncate" title="{{ $item->dosen->name ?? 'Belum Ditentukan' }}">
                                    {{ $item->dosen->name ?? 'Belum Ditentukan' }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2 text-slate-500">
                                <i class="fa-regular fa-clock text-blue-500 w-4 text-center"></i>
                                <span>{{ $item->hari }}, {{ $item->jam_mulai }} - {{ $item->jam_selesai }}</span>
                                @if($item->ruang)
                                    <span>&bull;</span>
                                    <span>{{ $item->ruang }}</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div class="flex items-center space-x-1.5 text-slate-700">
                                    <i class="fa-solid fa-users text-emerald-600"></i>
                                    <span class="font-bold text-slate-900">{{ $item->students->count() }}</span>
                                    <span class="text-slate-500">Mahasiswa</span>
                                </div>

                                @if($item->activeSession())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Sesi Live Aktif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="{{ route('admin.classes.plotting', $item) }}"
                            class="flex-1 px-3 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition text-center flex items-center justify-center space-x-1.5">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Ploting Mahasiswa</span>
                        </a>

                        <div class="flex items-center space-x-1">
                            <a href="{{ route('admin.classes.edit', $item) }}"
                                class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 flex items-center justify-center transition"
                                title="Edit Kelas">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </a>

                            <form action="{{ route('admin.classes.destroy', $item) }}" method="POST"
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas perkuliahan {{ $item->nama_mk }} ({{ $item->nama_kelas }})? Data ploting mahasiswa pada kelas ini juga akan terhapus.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-300 hover:bg-red-50 flex items-center justify-center transition"
                                    title="Hapus Kelas">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $classes->links() }}
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mx-auto mb-4">
                <i class="fa-solid fa-chalkboard"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Belum Ada Data Kelas</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                @if($search !== '' || $selectedProdi !== '')
                    Tidak ditemukan kelas yang cocok dengan kata kunci pencarian atau filter yang dipilih. Silakan coba kata kunci lain.
                @else
                    Mulai dengan membuat kelas perkuliahan baru menggunakan data matakuliah yang terintegrasi dari API Kampus.
                @endif
            </p>
            <div class="mt-5">
                <a href="{{ route('admin.classes.create') }}"
                    class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i>
                    <span>Buat Kelas Pertama</span>
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
