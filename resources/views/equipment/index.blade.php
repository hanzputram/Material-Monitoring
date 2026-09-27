@extends('layouts.app')

@section('title', 'Master Alat & Mesin — ' . $project->name)
@section('page_title', 'Master Alat & Mesin')
@section('page_subtitle', 'Katalog peralatan, mesin konstruksi & alat berat lengkap dengan input harga/tarif serta alokasi ke proyek')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab ?? 'master' }}',
    showMasterModal: false,
    masterModalTitle: 'Tambah Alat / Mesin Baru',
    masterFormAction: '{{ route('equipment.master.store') }}',
    masterFormMethod: 'POST',
    masterForm: {
        id: null,
        equipment_category_id: '{{ $categories->first()?->id ?? '' }}',
        code: '',
        name: '',
        default_unit_id: '{{ $units->where('code', 'Unit')->first()?->id ?? ($units->first()?->id ?? '') }}',
        price: 0,
        spec: '',
        is_active: true
    },
    showCategoryModal: false,
    showAllocModal: false,
    allocFormAction: '',
    allocForm: {
        id: null,
        name: '',
        qty: 1,
        rental_rate: 0,
        source: 'milik_sendiri',
        condition: 'layak_pakai',
        notes: ''
    },
    openAddMaster() {
        this.masterModalTitle = 'Tambah Alat & Mesin Baru';
        this.masterFormAction = '{{ route('equipment.master.store') }}';
        this.masterFormMethod = 'POST';
        this.masterForm = {
            id: null,
            equipment_category_id: '{{ $categories->first()?->id ?? '' }}',
            code: '',
            name: '',
            default_unit_id: '{{ $units->where('code', 'Unit')->first()?->id ?? ($units->first()?->id ?? '') }}',
            price: 0,
            spec: '',
            is_active: true
        };
        this.showMasterModal = true;
    },
    openEditMaster(eq) {
        this.masterModalTitle = 'Edit Data Alat / Mesin';
        this.masterFormAction = '/equipment/master/' + eq.id;
        this.masterFormMethod = 'PUT';
        this.masterForm = {
            id: eq.id,
            equipment_category_id: eq.equipment_category_id,
            code: eq.code || '',
            name: eq.name || '',
            default_unit_id: eq.default_unit_id || '',
            price: eq.price || 0,
            spec: eq.spec || '',
            is_active: Boolean(eq.is_active)
        };
        this.showMasterModal = true;
    },
    openEditAlloc(ae) {
        this.allocFormAction = '/equipment/project/' + ae.id;
        this.allocForm = {
            id: ae.id,
            name: ae.equipment_master.name,
            qty: ae.qty,
            rental_rate: ae.rental_rate || ae.equipment_master.price || 0,
            source: ae.source,
            condition: ae.condition,
            notes: ae.notes || ''
        };
        this.showAllocModal = true;
    },
    // Filter for fast input tab
    fastSearch: '',
    fastCategory: 'all',
    filterFastItem(item) {
        let matchesCat = this.fastCategory === 'all' || item.catId == this.fastCategory;
        let matchesSearch = item.name.toLowerCase().includes(this.fastSearch.toLowerCase()) || item.code.toLowerCase().includes(this.fastSearch.toLowerCase());
        return matchesCat && matchesSearch;
    }
}">

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Total Alat & Mesin -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Master Alat</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['total_equipment'] }} <span class="text-xs font-semibold text-slate-400">Item</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span>Katalog terdaftar dengan harga & spek</span>
            </div>
        </div>

        <!-- Mesin Konstruksi -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Mesin Konstruksi</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['total_mesin'] }} <span class="text-xs font-semibold text-slate-400">Unit</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Genset, Molen, Pompa, Stamper, dll.</span>
            </div>
        </div>

        <!-- Alat Berat -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Alat Berat</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-900 tracking-tight font-mono">
                {{ $stats['total_alat_berat'] }} <span class="text-xs font-semibold text-purple-400">Unit</span>
            </div>
            <div class="mt-2 text-xs text-purple-700">
                <span>Excavator, Crane, Dump Truck, Bored Pile</span>
            </div>
        </div>

        <!-- Alat di Proyek Aktif -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Aktif di Proyek Ini</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['active_in_project'] }} <span class="text-xs font-semibold text-slate-400">Alat</span>
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-semibold truncate" title="{{ $project->name }}">
                <span>Proyek: {{ Str::limit($project->name, 22) }}</span>
            </div>
        </div>
    </div>

    <!-- 2. TAB SWITCHER -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <!-- TAB 1: CRUD Master Katalog Alat & Mesin -->
            <button type="button" @click="activeTab = 'master'"
                    :class="activeTab === 'master' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Katalog & Master Alat Mesin</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'master' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $stats['total_equipment'] }}</span>
            </button>

            <!-- TAB 2: Fast Bulk Input -->
            <button type="button" @click="activeTab = 'fast_input'"
                    :class="activeTab === 'fast_input' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Alokasi Cepat ke Proyek (Fast Input)</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'fast_input' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $catalog->count() }}</span>
            </button>

            <!-- TAB 3: Alat Aktif di Proyek -->
            <button type="button" @click="activeTab = 'active'"
                    :class="activeTab === 'active' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Alat Aktif di Proyek</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ $stats['active_in_project'] }}</span>
            </button>
        </nav>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: MASTER ALAT & MESIN (CRUD & HARGA) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'master'" class="space-y-4">
        <div class="card-clean overflow-hidden">
            <!-- Header Panel: Title & Single Action Button -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Katalog Master Alat & Mesin Konstruksi</h3>
                        <p class="text-xs text-slate-500">Kelola database peralatan, mesin, alat berat, spesifikasi teknis dan harga/tarif sewa standar</p>
                    </div>
                </div>

                <!-- Single Action Button -->
                <button type="button" @click="openAddMaster()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer whitespace-nowrap self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Alat / Mesin Baru</span>
                </button>
            </div>

            <!-- Filter Toolbar (Clean, Horizontal, Live onchange) -->
            <div class="p-3 sm:p-4 bg-slate-50/70 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form action="{{ route('equipment.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <input type="hidden" name="tab" value="master">

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ $search }}" 
                               placeholder="Cari nama / kode / spek alat..." 
                               class="w-full text-xs pl-8 pr-7 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @if($search)
                            <a href="{{ route('equipment.index', ['tab' => 'master']) }}" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold">&times;</a>
                        @endif
                    </div>

                    <!-- Category Filter -->
                    <div class="w-full sm:w-48">
                        <select name="category_id" onchange="this.form.submit()" class="no-custom w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="all">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full sm:w-36">
                        <select name="status" onchange="this.form.submit()" class="no-custom w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="">Semua Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                        </select>
                    </div>

                    @if($search || ($categoryId && $categoryId !== 'all') || $status)
                        <a href="{{ route('equipment.index', ['tab' => 'master']) }}" class="text-xs text-rose-600 font-semibold hover:underline px-1 py-1">
                            Reset Filter
                        </a>
                    @endif
                </form>

                <div class="text-xs text-slate-500 font-medium whitespace-nowrap self-end md:self-center">
                    Total <strong>{{ $masterEquipment->total() }}</strong> alat & mesin
                </div>
            </div>

            <!-- Master Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No.</th>
                            <th class="py-3 px-4 w-32">Kode Alat</th>
                            <th class="py-3 px-5">Nama Alat / Mesin</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-center">Satuan</th>
                            <th class="py-3 px-4 text-right">Harga Baseline (Rp)</th>
                            <th class="py-3 px-4 text-center">Di Proyek</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($masterEquipment as $idx => $eq)
                            @php
                                $catName = strtolower($eq->category->name ?? '');
                                $badgeClass = 'bg-slate-100 text-slate-700';
                                if (str_contains($catName, 'berat')) {
                                    $badgeClass = 'bg-purple-100 text-purple-800';
                                } elseif (str_contains($catName, 'mesin')) {
                                    $badgeClass = 'bg-amber-100 text-amber-800';
                                } elseif (str_contains($catName, 'peralatan')) {
                                    $badgeClass = 'bg-blue-100 text-blue-800';
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-slate-400">
                                    {{ $masterEquipment->firstItem() + $idx }}
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-700 text-[11px]">
                                    {{ $eq->code }}
                                </td>
                                <td class="py-3 px-5">
                                    <div class="font-bold text-slate-900 text-xs">{{ $eq->name }}</div>
                                    @if($eq->spec)
                                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" title="{{ $eq->spec }}">{{ $eq->spec }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge-clean {{ $badgeClass }} text-[10px]">
                                        {{ $eq->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                        {{ $eq->defaultUnit?->code ?? 'Unit' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 text-xs">
                                    Rp {{ number_format($eq->price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($eq->project_equipments_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $eq->project_equipments_count }} Proyek
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($eq->is_active)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Button -->
                                        <button type="button" @click="openEditMaster({{ json_encode($eq) }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Alat/Mesin">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <form action="{{ route('equipment.master.destroy', $eq->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alat {{ addslashes($eq->name) }} dari katalog?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Alat">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <svg class="w-10 h-10 mb-2 stroke-current opacity-40" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <p class="font-medium text-sm">Tidak ada alat atau mesin yang sesuai kriteria pencarian.</p>
                                        <button type="button" @click="openAddMaster()" class="mt-3 text-xs text-blue-600 font-bold hover:underline">
                                            + Tambah Alat / Mesin Baru Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($masterEquipment->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $masterEquipment->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: FAST BULK INPUT CHECKLIST -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'fast_input'" class="card-clean overflow-hidden" x-cloak>
        <form action="{{ route('equipment.fast_bulk') }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <!-- Header & Filter Toolbar -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="fastCategory = 'all'" 
                            :class="fastCategory === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                            class="px-3 py-1.5 rounded-xl text-xs transition-colors cursor-pointer shadow-2xs">
                        Semua Kategori ({{ $catalog->count() }})
                    </button>
                    @foreach($categories as $cat)
                        <button type="button" @click="fastCategory = '{{ $cat->id }}'" 
                                :class="fastCategory == '{{ $cat->id }}' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-colors cursor-pointer shadow-2xs">
                            {{ $cat->name }} ({{ $cat->equipment_count }})
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-auto">
                        <input type="text" x-model="fastSearch" placeholder="Cari di katalog cepat..." 
                               class="w-full sm:w-60 text-xs pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 flex-shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Alat Terpilih ke Proyek
                    </button>
                </div>
            </div>

            <!-- Fast Input Checklist Table -->
            <div class="overflow-x-auto max-h-[600px]">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 sticky top-0 uppercase tracking-wider text-[11px] z-10">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">Pilih</th>
                            <th class="py-3 px-4 w-28">Kode</th>
                            <th class="py-3 px-5">Nama Alat / Mesin</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-right">Harga Baseline</th>
                            <th class="py-3 px-4 w-24 text-center">Qty</th>
                            <th class="py-3 px-4 w-20 text-center">Satuan</th>
                            <th class="py-3 px-4 w-36">Kepemilikan</th>
                            <th class="py-3 px-4 w-36">Kondisi</th>
                            <th class="py-3 px-5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($catalog as $idx => $eq)
                            @php
                                $assigned = $activeEquipment->firstWhere('equipment_master_id', $eq->id);
                            @endphp
                            <tr x-show="filterFastItem({ name: '{{ addslashes($eq->name) }}', code: '{{ $eq->code }}', catId: '{{ $eq->equipment_category_id }}' })"
                                class="transition-colors hover:bg-slate-50 {{ $assigned ? 'bg-blue-50/20' : '' }}">
                                <input type="hidden" name="items[{{ $idx }}][equipment_master_id]" value="{{ $eq->id }}">
                                
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" name="items[{{ $idx }}][selected]" value="1" 
                                           {{ $assigned ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-500 text-[11px]">{{ $eq->code }}</td>
                                <td class="py-3 px-5">
                                    <div class="font-bold text-slate-900">{{ $eq->name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $eq->spec ?: '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge-clean bg-slate-100 text-slate-700 text-[10px]">{{ $eq->category->name }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700 font-semibold text-xs">
                                    Rp {{ number_format($eq->price, 0, ',', '.') }}
                                    <input type="hidden" name="items[{{ $idx }}][rental_rate]" value="{{ $eq->price }}">
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <input type="number" name="items[{{ $idx }}][qty]" value="{{ $assigned ? $assigned->qty : 1 }}" min="1" 
                                           class="w-16 text-center font-mono font-bold p-1 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-xs">
                                </td>
                                <td class="py-3 px-4 text-center font-semibold text-slate-600">{{ $eq->defaultUnit?->code ?? 'Unit' }}</td>
                                <td class="py-3 px-4">
                                    <select name="items[{{ $idx }}][source]" class="no-custom w-full text-xs p-1.5 bg-white border border-slate-200 rounded-lg font-medium text-slate-800">
                                        <option value="milik_sendiri" {{ $assigned && $assigned->source === 'milik_sendiri' ? 'selected' : '' }}>Milik Sendiri</option>
                                        <option value="sewa" {{ $assigned && $assigned->source === 'sewa' ? 'selected' : '' }}>Sewa Vendor</option>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <select name="items[{{ $idx }}][condition]" class="no-custom w-full text-xs p-1.5 bg-white border border-slate-200 rounded-lg font-medium text-slate-800">
                                        <option value="baru" {{ $assigned && $assigned->condition === 'baru' ? 'selected' : '' }}>Baru</option>
                                        <option value="layak_pakai" {{ !$assigned || $assigned->condition === 'layak_pakai' ? 'selected' : '' }}>Layak Pakai</option>
                                        <option value="perlu_perbaikan" {{ $assigned && $assigned->condition === 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                    </select>
                                </td>
                                <td class="py-3 px-5">
                                    <input type="text" name="items[{{ $idx }}][notes]" value="{{ $assigned ? $assigned->notes : '' }}" placeholder="Catatan..." 
                                           class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Save -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs text-slate-500">Centang kotak pada baris alat yang ingin ditambahkan ke proyek, sesuaikan kuantiti, lalu simpan.</span>
                <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 cursor-pointer">
                    Simpan Semua Alat Terpilih ke Proyek
                </button>
            </div>
        </form>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 3: ACTIVE EQUIPMENT IN PROJECT -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'active'" class="card-clean overflow-hidden" x-cloak>
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-white">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Peralatan & Mesin Aktif di {{ $project->name }}</h3>
                <p class="text-xs text-slate-500">Daftar alat berat, mesin dan peralatan yang dialokasikan di lapangan</p>
            </div>
            <span class="badge-clean bg-emerald-100 text-emerald-800 text-xs font-bold">{{ $activeEquipment->count() }} Alat Terpasang</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-4 w-28">Kode</th>
                        <th class="py-3 px-5">Nama Alat / Mesin</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-center">Kuantiti</th>
                        <th class="py-3 px-4 text-right">Tarif / Harga</th>
                        <th class="py-3 px-4">Kepemilikan</th>
                        <th class="py-3 px-4">Kondisi</th>
                        <th class="py-3 px-4">Dialokasikan Oleh</th>
                        <th class="py-3 px-5">Catatan</th>
                        <th class="py-3 px-4 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeEquipment as $ae)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 font-mono text-[11px] font-semibold text-slate-600">{{ $ae->equipmentMaster->code }}</td>
                            <td class="py-3 px-5 font-bold text-slate-900">{{ $ae->equipmentMaster->name }}</td>
                            <td class="py-3 px-4">
                                <span class="badge-clean bg-slate-100 text-slate-700 text-[10px]">{{ $ae->equipmentMaster->category->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-blue-700 text-sm">
                                {{ $ae->qty }} {{ $ae->equipmentMaster->defaultUnit?->code ?? 'Unit' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 text-xs">
                                Rp {{ number_format($ae->rental_rate ?: $ae->equipmentMaster->price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="badge-clean {{ $ae->source === 'milik_sendiri' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }} text-[10px]">
                                    {{ $ae->source === 'milik_sendiri' ? 'Milik Sendiri' : 'Sewa' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="badge-clean {{ $ae->condition === 'baru' ? 'bg-emerald-100 text-emerald-800' : ($ae->condition === 'layak_pakai' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800') }} text-[10px]">
                                    {{ ucwords(str_replace('_', ' ', $ae->condition)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500">{{ $ae->user?->name ?? 'Admin' }}</td>
                            <td class="py-3 px-5 text-slate-500">{{ $ae->notes ?: '-' }}</td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openEditAlloc({{ json_encode($ae) }})"
                                            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Alokasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <form action="{{ route('equipment.project.destroy', $ae->id) }}" method="POST" onsubmit="return confirm('Hapus alat ini dari proyek?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Alokasi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <p class="font-medium text-sm">Belum ada alat yang dialokasikan ke proyek {{ $project->name }}.</p>
                                <button type="button" @click="activeTab = 'fast_input'" class="mt-2 text-xs text-blue-600 font-bold hover:underline">
                                    + Buka Alokasi Cepat (Fast Input)
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: TAMBAH / EDIT ALAT & MESIN (MASTER CRUD) -->
    <!-- ======================================================== -->
    <div x-show="showMasterModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-cloak 
         @keydown.escape.window="showMasterModal = false">
        
        <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200"
             @click.away="showMasterModal = false">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-900 text-base" x-text="masterModalTitle"></h4>
                        <p class="text-xs text-slate-500">Lengkapi informasi alat/mesin & input harga baseline</p>
                    </div>
                </div>
                <button type="button" @click="showMasterModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="masterFormAction" method="POST">
                @csrf
                <template x-if="masterFormMethod === 'PUT'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Kategori & Kode -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-semibold text-slate-700 text-xs">Kategori Alat <span class="text-rose-500">*</span></label>
                                <button type="button" @click="showCategoryModal = true" class="text-[11px] text-blue-600 font-bold hover:underline cursor-pointer">+ Kategori Baru</button>
                            </div>
                            <select name="equipment_category_id" x-model="masterForm.equipment_category_id" required 
                                    class="no-custom w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Kode Alat / Mesin</label>
                            <input type="text" name="code" x-model="masterForm.code" 
                                   placeholder="Otomatis (contoh: EQP-MSN-011)"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-slate-400 mt-1 block">Biarkan kosong untuk penomoran otomatis</span>
                        </div>
                    </div>

                    <!-- Nama Alat / Mesin -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Nama Alat / Mesin <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="masterForm.name" required 
                               placeholder="Contoh: Mesin Molen Beton 500L / Excavator PC200"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Satuan & Input Harga Baseline -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Satuan Standar <span class="text-rose-500">*</span></label>
                            <select name="default_unit_id" x-model="masterForm.default_unit_id" required 
                                    class="no-custom w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->code }} - {{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Harga / Tarif Baseline (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="price" x-model="masterForm.price" required min="0" step="1000"
                                       placeholder="0"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Harga beli atau estimasi tarif sewa standar</span>
                        </div>
                    </div>

                    <!-- Spesifikasi Teknis / Kapasitas -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Spesifikasi Teknis / Kapasitas</label>
                        <textarea name="spec" x-model="masterForm.spec" rows="2" 
                                  placeholder="Contoh: Diesel 8 PK / Kapasitas 1 Sak Semen / Bucket 0.93 m3"
                                  class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>

                    <!-- Status Aktif Checkbox -->
                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="masterForm.is_active"
                                   class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-700">Alat Aktif & Tersedia untuk Alokasi Proyek</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showMasterModal = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        Simpan Data Alat & Mesin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: EDIT ALOKASI PROYEK (TAB 3) -->
    <!-- ======================================================== -->
    <div x-show="showAllocModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-cloak 
         @keydown.escape.window="showAllocModal = false">
        
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200"
             @click.away="showAllocModal = false">
            
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div>
                    <h4 class="font-extrabold text-slate-900 text-base">Edit Alokasi Alat di Proyek</h4>
                    <p class="text-xs text-slate-500" x-text="allocForm.name"></p>
                </div>
                <button type="button" @click="showAllocModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="allocFormAction" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Kuantiti (Qty) <span class="text-rose-500">*</span></label>
                            <input type="number" name="qty" x-model="allocForm.qty" required min="1"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Tarif / Harga (Rp)</label>
                            <input type="number" name="rental_rate" x-model="allocForm.rental_rate" min="0" step="1000"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Kepemilikan</label>
                            <select name="source" x-model="allocForm.source" class="no-custom w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium">
                                <option value="milik_sendiri">Milik Sendiri</option>
                                <option value="sewa">Sewa Vendor</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Kondisi Fisik</label>
                            <select name="condition" x-model="allocForm.condition" class="no-custom w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium">
                                <option value="baru">Baru</option>
                                <option value="layak_pakai">Layak Pakai</option>
                                <option value="perlu_perbaikan">Perlu Perbaikan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Catatan Khusus Lapangan</label>
                        <input type="text" name="notes" x-model="allocForm.notes" placeholder="Lokasi penempatan, operator, dll."
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showAllocModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        Perbarui Alokasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: TAMBAH KATEGORI ALAT BARU -->
    <!-- ======================================================== -->
    <div x-show="showCategoryModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-cloak 
         @keydown.escape.window="showCategoryModal = false">
        
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200"
             @click.away="showCategoryModal = false">
            
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <h4 class="font-extrabold text-slate-900 text-sm">Tambah Kategori Alat Baru</h4>
                <button type="button" @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('equipment.categories.store') }}" method="POST">
                @csrf
                <div class="p-5 space-y-3">
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Alat Ukur & Survey / Scaffolding"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showCategoryModal = false" class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg cursor-pointer shadow-sm">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
