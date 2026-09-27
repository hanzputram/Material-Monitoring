@extends('layouts.app')

@section('title', 'Alat & Mesin Fast Input — ' . $project->name)
@section('page_title', 'Master Alat & Mesin (Fast Bulk Input)')
@section('page_subtitle', 'Katalog pre-seeded peralatan & mesin konstruksi dengan form checklist multi-select untuk alokasi cepat per proyek')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'fast_input',
    search: '',
    selectedCategory: 'all',
    filterCatalog(item) {
        let matchesCat = this.selectedCategory === 'all' || item.catId == this.selectedCategory;
        let matchesSearch = item.name.toLowerCase().includes(this.search.toLowerCase()) || item.code.toLowerCase().includes(this.search.toLowerCase());
        return matchesCat && matchesSearch;
    }
}">

    <!-- Header Actions & Tabs -->
    <div class="card-clean p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl tab-scroll-container">
            <button type="button" @click="activeTab = 'fast_input'" 
                    :class="activeTab === 'fast_input' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 font-semibold'"
                    class="px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="hidden sm:inline">Fast Input Checklist (Katalog Global)</span>
                <span class="sm:hidden">Katalog</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $catalog->count() }}</span>
            </button>
            <button type="button" @click="activeTab = 'active'" 
                    :class="activeTab === 'active' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 font-semibold'"
                    class="px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="hidden sm:inline">Alat Dialokasikan di Proyek</span>
                <span class="sm:hidden">Aktif</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-emerald-100 text-emerald-800 rounded-full font-mono">{{ $activeEquipment->count() }}</span>
            </button>
        </div>

        <span class="text-xs text-slate-500 font-medium hidden sm:block">
            Proyek: <strong class="text-slate-800">{{ $project->name }}</strong>
        </span>
    </div>

    <!-- TAB 1: FAST BULK INPUT FORM -->
    <div x-show="activeTab === 'fast_input'" class="card-clean overflow-hidden">
        <form action="{{ route('equipment.fast_bulk') }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <!-- Filter Toolbar -->
            <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="selectedCategory = 'all'" 
                            :class="selectedCategory === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                            class="px-3 py-1.5 rounded-lg text-xs transition-colors">
                        Semua Kategori ({{ $catalog->count() }})
                    </button>
                    @foreach($categories as $cat)
                        <button type="button" @click="selectedCategory = '{{ $cat->id }}'" 
                                :class="selectedCategory == '{{ $cat->id }}' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                                class="px-3 py-1.5 rounded-lg text-xs transition-colors">
                            {{ $cat->name }} ({{ $cat->equipment_count }})
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-auto">
                        <input type="text" x-model="search" placeholder="Cari alat / mesin..." 
                               class="w-full sm:w-60 text-xs pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md shadow-blue-500/20 flex items-center gap-2 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Semua Alat Terpilih
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
                            <th class="py-3 px-4 w-28 text-center">Jumlah (Qty)</th>
                            <th class="py-3 px-4 w-24 text-center">Satuan</th>
                            <th class="py-3 px-4 w-36">Kepemilikan</th>
                            <th class="py-3 px-4 w-36">Kondisi Fisik</th>
                            <th class="py-3 px-5">Catatan Khusus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($catalog as $idx => $eq)
                            @php
                                $assigned = $activeEquipment->firstWhere('equipment_master_id', $eq->id);
                            @endphp
                            <tr x-show="filterCatalog({ name: '{{ addslashes($eq->name) }}', code: '{{ $eq->code }}', catId: '{{ $eq->equipment_category_id }}' })"
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
                                <td class="py-3 px-4 text-center">
                                    <input type="number" name="items[{{ $idx }}][qty]" value="{{ $assigned ? $assigned->qty : 1 }}" min="1" 
                                           class="w-20 text-center font-mono font-bold p-1.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-xs">
                                </td>
                                <td class="py-3 px-4 text-center font-semibold text-slate-600">{{ $eq->defaultUnit?->code ?? 'Unit' }}</td>
                                <td class="py-3 px-4">
                                    <select name="items[{{ $idx }}][source]" class="w-full select-clean select-clean-sm bg-white font-medium text-slate-800">
                                        <option value="milik_sendiri" {{ $assigned && $assigned->source === 'milik_sendiri' ? 'selected' : '' }}>Milik Sendiri</option>
                                        <option value="sewa" {{ $assigned && $assigned->source === 'sewa' ? 'selected' : '' }}>Sewa Vendor</option>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <select name="items[{{ $idx }}][condition]" class="w-full select-clean select-clean-sm bg-white font-medium text-slate-800">
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
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Centang kotak pada baris alat yang ingin ditambahkan ke proyek, sesuaikan kuantiti, lalu simpan.</span>
                <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20">
                    Simpan Semua Alat Terpilih ke Proyek
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: ACTIVE EQUIPMENT TABLE -->
    <div x-show="activeTab === 'active'" class="card-clean overflow-hidden" x-cloak>
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Peralatan & Mesin Aktif di {{ $project->name }}</h3>
            <span class="badge-clean bg-emerald-100 text-emerald-800 text-xs">{{ $activeEquipment->count() }} Alat Terpasang</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">Kode</th>
                        <th class="py-3 px-5">Nama Alat / Mesin</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-center">Kuantiti</th>
                        <th class="py-3 px-4">Kepemilikan</th>
                        <th class="py-3 px-4">Kondisi</th>
                        <th class="py-3 px-4">Dialokasikan Oleh</th>
                        <th class="py-3 px-5">Catatan</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeEquipment as $ae)
                        <tr>
                            <td class="py-3 px-5 font-mono text-[11px] font-semibold text-slate-500">{{ $ae->equipmentMaster->code }}</td>
                            <td class="py-3 px-5 font-bold text-slate-800">{{ $ae->equipmentMaster->name }}</td>
                            <td class="py-3 px-4">
                                <span class="badge-clean bg-slate-100 text-slate-700 text-[10px]">{{ $ae->equipmentMaster->category->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-blue-700 text-sm">
                                {{ $ae->qty }} {{ $ae->equipmentMaster->defaultUnit?->code ?? 'Unit' }}
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
                                <form action="{{ route('equipment.project.destroy', $ae->id) }}" method="POST" onsubmit="return confirm('Hapus alat ini dari proyek?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded" title="Hapus Alokasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">Belum ada alat yang dialokasikan ke proyek ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
