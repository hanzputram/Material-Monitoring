@extends('layouts.app')

@section('title', 'Master Item & Material — K-RAB')
@section('page_title', 'Master Item & Material')
@section('page_subtitle', 'Katalog Bahan Konstruksi, Kategori Hasil Jadi/Dasar & Master Satuan Terpusat')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab }}',
    showMaterialModal: false,
    isEditMaterial: false,
    materialModalTitle: 'Tambah Material / Item Baru',
    materialFormAction: '{{ route('materials.store') }}',
    materialFormMethod: 'POST',
    materialData: {
        id: null,
        code: '',
        name: '',
        category: 'bahan_dasar',
        default_unit_id: '{{ $units->first()?->id }}',
        standard_price: 0,
        specification: '',
        notes: '',
        is_active: true
    },

    showUnitModal: false,
    isEditUnit: false,
    unitModalTitle: 'Tambah Satuan Baru',
    unitFormAction: '{{ route('materials.units.store') }}',
    unitFormMethod: 'POST',
    unitData: {
        id: null,
        code: '',
        name: '',
        group: 'satuan_hitung',
        is_base_unit: false
    },

    openAddMaterial() {
        this.isEditMaterial = false;
        this.materialModalTitle = 'Tambah Material / Item Baru';
        this.materialFormAction = '{{ route('materials.store') }}';
        this.materialFormMethod = 'POST';
        this.materialData = {
            id: null,
            code: '',
            name: '',
            category: 'bahan_dasar',
            default_unit_id: '{{ $units->first()?->id }}',
            standard_price: 0,
            specification: '',
            notes: '',
            is_active: true
        };
        this.showMaterialModal = true;
    },

    openEditMaterial(mat) {
        this.isEditMaterial = true;
        this.materialModalTitle = 'Edit Data Material: ' + mat.name;
        this.materialFormAction = '/materials/' + mat.id;
        this.materialFormMethod = 'POST';
        this.materialData = {
            id: mat.id,
            code: mat.code || '',
            name: mat.name || '',
            category: mat.category || 'bahan_dasar',
            default_unit_id: mat.default_unit_id || '',
            standard_price: mat.standard_price || 0,
            specification: mat.specification || '',
            notes: mat.notes || '',
            is_active: Boolean(mat.is_active)
        };
        this.showMaterialModal = true;
    },

    openAddUnit() {
        this.isEditUnit = false;
        this.unitModalTitle = 'Tambah Satuan Pengukuran Baru';
        this.unitFormAction = '{{ route('materials.units.store') }}';
        this.unitFormMethod = 'POST';
        this.unitData = {
            id: null,
            code: '',
            name: '',
            group: 'satuan_hitung',
            is_base_unit: false
        };
        this.showUnitModal = true;
    },

    openEditUnit(u) {
        this.isEditUnit = true;
        this.unitModalTitle = 'Edit Satuan: ' + u.code;
        this.unitFormAction = '/materials/units/' + u.id;
        this.unitFormMethod = 'POST';
        this.unitData = {
            id: u.id,
            code: u.code || '',
            name: u.name || '',
            group: u.group || 'satuan_hitung',
            is_base_unit: Boolean(u.is_base_unit)
        };
        this.showUnitModal = true;
    }
}">

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Total Material -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Master Material</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['total_materials'] }} <span class="text-xs font-semibold text-slate-400">Item</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ $stats['active_materials'] }} material aktif digunakan</span>
            </div>
        </div>

        <!-- Bahan Dasar / Mentah -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Bahan Dasar / Mentah</span>
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['total_basic'] }} <span class="text-xs font-semibold text-slate-400">Item</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Semen, Pasir, Besi, Cat, Paku, dll.</span>
            </div>
        </div>

        <!-- Bahan Jadi / Komposit -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Bahan Jadi / Komposit</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-900 tracking-tight font-mono">
                {{ $stats['total_composite'] }} <span class="text-xs font-semibold text-purple-400">Item</span>
            </div>
            <div class="mt-2 text-xs text-purple-700">
                <span>Dapat di-breakdown rincian oleh Purchasing</span>
            </div>
        </div>

        <!-- Total Satuan -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Master Satuan (Units)</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['total_units'] }} <span class="text-xs font-semibold text-slate-400">Satuan</span>
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-semibold">
                <span>Kg, M3, M2, Btg, Lbr, Zak, dll.</span>
            </div>
        </div>
    </div>

    <!-- 2. ELEGANT UNDERLINE TAB SWITCHER -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <button type="button" @click="activeTab = 'materials'"
                    :class="activeTab === 'materials' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Katalog Material & Item</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'materials' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $stats['total_materials'] }}</span>
            </button>

            <button type="button" @click="activeTab = 'units'"
                    :class="activeTab === 'units' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>Master Satuan (Units)</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'units' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $stats['total_units'] }}</span>
            </button>
        </nav>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: KATALOG ITEM & MATERIAL -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'materials'" class="space-y-4">
        <!-- Single Unified Table Card with Toolbar -->
        <div class="card-clean overflow-hidden">
            <!-- Header Panel: Title & Single Add Button -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Katalog Master Material & Item</h3>
                        <p class="text-xs text-slate-500">Database lengkap spesifikasi bahan, kategori komposit/dasar & estimasi harga baseline</p>
                    </div>
                </div>

                <!-- Single Add Button -->
                <button type="button" @click="openAddMaterial()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer whitespace-nowrap self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Material Baru</span>
                </button>
            </div>

            <!-- Filter & Search Toolbar (Spacious, Clean, Never Stacked!) -->
            <div class="p-3 sm:p-4 bg-slate-50/70 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form action="{{ route('materials.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <input type="hidden" name="tab" value="materials">

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ $search }}" 
                               placeholder="Cari nama / kode / spek..." 
                               class="w-full text-xs pl-8 pr-7 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @if($search)
                            <a href="{{ route('materials.index', ['tab' => 'materials']) }}" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold">&times;</a>
                        @endif
                    </div>

                    <!-- Category Filter Dropdown -->
                    <div class="w-full sm:w-48">
                        <select name="category" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="all">Semua Kategori</option>
                            @foreach($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}" {{ $category === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div class="w-full sm:w-36">
                        <select name="status" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="">Semua Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                        </select>
                    </div>

                    @if($search || ($category && $category !== 'all') || $status)
                        <a href="{{ route('materials.index', ['tab' => 'materials']) }}" class="text-xs text-rose-600 font-semibold hover:underline px-1 py-1">
                            Reset Filter
                        </a>
                    @endif
                </form>

                <div class="text-xs text-slate-500 font-medium whitespace-nowrap self-end md:self-center">
                    Total <strong>{{ $materials->total() }}</strong> material
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No.</th>
                            <th class="py-3 px-4">Kode & Nama Material</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-3 text-center">Satuan</th>
                            <th class="py-3 px-4">Spesifikasi Teknis</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($materials as $index => $mat)
                            <tr class="hover:bg-slate-50/70 transition-colors {{ !$mat->is_active ? 'opacity-60 bg-slate-50/40' : '' }}">
                                <td class="py-3 px-4 text-center font-mono text-slate-400">
                                    {{ $materials->firstItem() + $index }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                        <span>{{ $mat->name }}</span>
                                    </div>
                                    <span class="font-mono text-[11px] text-slate-400 font-semibold block mt-0.5">
                                        {{ $mat->code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $catColor = match($mat->category) {
                                            'bahan_dasar' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'bahan_jadi' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'alat_bantu' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'finishing' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'sanitasi' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                            'elektrikal' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $catColor }}">
                                        {{ $categories[$mat->category] ?? ucfirst(str_replace('_', ' ', $mat->category)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="font-mono font-bold px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs border border-slate-200/80">
                                        {{ $mat->defaultUnit?->code ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($mat->specification)
                                        <span class="text-slate-700 block font-medium">{{ $mat->specification }}</span>
                                    @endif
                                    @if($mat->notes)
                                        <span class="text-[11px] text-slate-400 italic block">{{ $mat->notes }}</span>
                                    @endif
                                    @if(!$mat->specification && !$mat->notes)
                                        <span class="text-slate-300 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('materials.toggle_status', $mat->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold transition-all border cursor-pointer {{ $mat->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200' }}"
                                                title="Klik untuk ubah status aktif/non-aktif">
                                            {{ $mat->is_active ? 'Aktif' : 'Non-aktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Material Button -->
                                        <button type="button" @click="openEditMaterial({{ json_encode($mat) }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Material">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Material Button -->
                                        <form action="{{ route('materials.destroy', $mat->id) }}" method="POST" onsubmit="return confirm('Hapus material {{ addslashes($mat->name) }} dari Master?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Material">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <p class="text-xs font-semibold text-slate-600">Tidak ada material yang sesuai pencarian atau filter.</p>
                                        <button type="button" @click="openAddMaterial()" class="text-xs text-blue-600 font-bold hover:underline cursor-pointer">
                                            + Tambah Material Baru Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination & Info -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                <span class="text-xs text-slate-500 font-medium">
                    Menampilkan <strong>{{ $materials->firstItem() ?? 0 }} - {{ $materials->lastItem() ?? 0 }}</strong> dari total <strong>{{ $materials->total() }}</strong> material
                </span>
                @if($materials->hasPages())
                    <div>
                        {{ $materials->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: MASTER SATUAN (UNITS) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'units'" class="space-y-4" x-cloak>
        <div class="card-clean overflow-hidden">
            <!-- Header Toolbar: Title & Single Add Button -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Daftar Satuan Pengukuran Standar</h3>
                        <p class="text-xs text-slate-500">Satuan resmi yang digunakan pada kuantiti material, RAB tree, Purchase Order, dan Surat Jalan (DO)</p>
                    </div>
                </div>

                <!-- Single Add Satuan Button -->
                <button type="button" @click="openAddUnit()" 
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md shadow-emerald-500/20 transition-all cursor-pointer whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Satuan Baru</span>
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No.</th>
                            <th class="py-3 px-4">Simbol Satuan</th>
                            <th class="py-3 px-4">Nama Lengkap Satuan</th>
                            <th class="py-3 px-4">Kelompok Besaran</th>
                            <th class="py-3 px-4 text-center">Tipe Acuan Dasar</th>
                            <th class="py-3 px-4 text-center">Material Terkait</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($units as $idx => $u)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-mono font-bold text-sm text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                        {{ $u->code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    {{ $u->name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge-clean bg-slate-100 text-slate-700 text-[11px] font-medium capitalize">
                                        {{ str_replace('_', ' ', $u->group) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($u->is_base_unit)
                                        <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] font-bold">Base Unit</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-600">
                                    {{ $u->materials_count }} Item
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="openEditUnit({{ json_encode($u) }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Satuan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form action="{{ route('materials.units.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus satuan {{ $u->code }}?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Satuan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: ADD / EDIT MATERIAL ITEM -->
    <!-- ======================================================== -->
    <div x-show="showMaterialModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showMaterialModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form :action="materialFormAction" method="POST">
                    @csrf
                    <template x-if="isEditMaterial">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1 font-bold">Katalog Terpusat</span>
                            <h3 class="text-sm font-bold text-slate-900" x-text="materialModalTitle"></h3>
                        </div>
                        <button type="button" @click="showMaterialModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-1">
                                <label class="block font-semibold text-slate-700 mb-1">Kode Material</label>
                                <input type="text" name="code" x-model="materialData.code" placeholder="Mis. MAT-BSI-001" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                                <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan untuk auto-generate.</span>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Nama Material / Item <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" x-model="materialData.name" required placeholder="Mis. Besi Beton Ulir D16 / Cat Dulux Exterior" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none font-medium">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kategori Material <span class="text-rose-500">*</span></label>
                                <select name="category" x-model="materialData.category" required class="w-full select-clean p-2.5 bg-white text-slate-800 font-medium">
                                    @foreach($categories as $catKey => $catLabel)
                                        <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-semibold text-slate-700">Satuan Standar <span class="text-rose-500">*</span></label>
                                    <button type="button" @click="showMaterialModal = false; openAddUnit()" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">+ Satuan Baru</button>
                                </div>
                                <select name="default_unit_id" x-model="materialData.default_unit_id" required class="w-full select-clean p-2.5 bg-white text-slate-800 font-medium">
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Spesifikasi Teknis / Dimensi / Merk</label>
                            <input type="text" name="specification" x-model="materialData.specification" placeholder="Mis. SNI Krakatau Steel, tebal 12mm, mutu K-300" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea name="notes" x-model="materialData.notes" rows="2" placeholder="Keterangan pengadaan, ketentuan vendor, dll." 
                                      class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="mat_is_active" name="is_active" value="1" x-model="materialData.is_active" 
                                   class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                            <label for="mat_is_active" class="font-semibold text-slate-700 cursor-pointer">
                                Status Aktif (Dapat dipilih di form RAB & Pengadaan)
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showMaterialModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm cursor-pointer" x-text="isEditMaterial ? 'Perbarui Material' : 'Simpan Material Baru'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: ADD / EDIT SATUAN (UNIT) -->
    <!-- ======================================================== -->
    <div x-show="showUnitModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showUnitModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form :action="unitFormAction" method="POST">
                    @csrf
                    <template x-if="isEditUnit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900" x-text="unitModalTitle"></h3>
                        <button type="button" @click="showUnitModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Simbol / Kode Satuan <span class="text-rose-500">*</span></label>
                            <input type="text" name="code" x-model="unitData.code" required placeholder="Mis. Kg, M3, Btg, Lbr, Zak, Bh" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono font-bold uppercase">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Satuan <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="unitData.name" required placeholder="Mis. Kilogram, Meter Kubik, Batang 6m" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none font-medium">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kelompok Besaran Satuan <span class="text-rose-500">*</span></label>
                            <select name="group" x-model="unitData.group" required class="w-full select-clean p-2.5 bg-white text-slate-800 font-medium">
                                <option value="berat">Berat (Kg, Ton, Gram)</option>
                                <option value="volume">Volume (M3, Liter, Drum)</option>
                                <option value="luas">Luas (M2, Ha)</option>
                                <option value="panjang">Panjang (M', Btg, Rol)</option>
                                <option value="satuan_hitung">Satuan Hitung (Bh, Lbr, Zak, Set, Dus)</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="unit_is_base" name="is_base_unit" value="1" x-model="unitData.is_base_unit" 
                                   class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                            <label for="unit_is_base" class="font-semibold text-slate-700 cursor-pointer">
                                Tandai sebagai Satuan Dasar (Base Unit Acuan Konversi)
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showUnitModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm cursor-pointer" x-text="isEditUnit ? 'Perbarui Satuan' : 'Simpan Satuan'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
