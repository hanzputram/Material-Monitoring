@extends('layouts.app')

@section('title', 'Dashboard Monitoring — ' . $currentProject->name)
@section('page_title', 'Dashboard Monitoring')
@section('page_subtitle', 'Monitoring Siklus RAB, Realisasi Material & Validasi Pengadaan')

@section('content')
<div class="space-y-6">

    <!-- 1. PROJECT HERO IDENTITAS -->
    <div class="card-clean p-6 bg-gradient-to-r from-white via-white to-blue-50/40 relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="badge-clean bg-blue-100 text-blue-800">{{ $currentProject->status === 'active' ? 'Proyek Aktif' : ucfirst($currentProject->status) }}</span>
                    <span class="badge-clean bg-slate-100 text-slate-700">Tahun {{ $currentProject->budget_year }}</span>
                    @if($currentProject->prototype_type)
                        <span class="badge-clean bg-indigo-50 text-indigo-700 border border-indigo-200/60 font-mono">{{ $currentProject->prototype_type }}</span>
                    @endif
                    <span class="text-xs text-slate-400 font-medium">Batas Alert: +{{ $currentProject->alert_over_threshold_pct }}% / -{{ $currentProject->alert_under_threshold_pct }}%</span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $currentProject->name }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ $currentProject->floor_count }} Lantai
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $currentProject->location_kds ?: 'Lokasi Belum Diatur' }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Pondasi: {{ $currentProject->foundation_type ?: 'Bored Pile' }}
                    </span>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('rab.builder') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Kelola RAB
                </a>
                <a href="{{ route('equipment.index') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl shadow-sm transition-all">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Fast Input Alat
                </a>
                <a href="{{ route('procurement.index') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl shadow-sm transition-all">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Pengadaan (DO/Inv)
                </a>
                <a href="{{ route('rab.export', $currentProject->id) }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl transition-all">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Ekspor Excel
                </a>
            </div>
        </div>
    </div>

    <!-- 2. KPI METRICS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <!-- Card 1: Total RAB -->
        <div class="card-clean p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Anggaran RAB</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($stats['total_rab'], 0, ',', '.') }}
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $currentProject->rootRabNodes->count() }} Kategori Utama</span>
                <a href="{{ route('rab.builder') }}" class="text-blue-600 font-semibold hover:underline">Lihat Rincian &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Realisasi Biaya -->
        <div class="card-clean p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Realisasi Biaya (Invoice)</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($stats['total_realization'], 0, ',', '.') }}
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                @if($stats['cost_variance'] > 0)
                    <span class="text-rose-600 font-bold">+Rp {{ number_format($stats['cost_variance'], 0, ',', '.') }} (Over)</span>
                @else
                    <span class="text-emerald-600 font-bold">Rp {{ number_format(abs($stats['cost_variance']), 0, ',', '.') }} Sisa</span>
                @endif
                <span class="text-slate-400">Tervalidasi</span>
            </div>
        </div>

        <!-- Card 3: Material Variance Status -->
        <div class="card-clean p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Material Proyek</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['materials_count'] }}</span>
                <span class="text-xs text-slate-500 font-medium">Material Terdaftar</span>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[11px]">{{ $stats['normal_count'] }} Normal</span>
                @if($stats['over_count'] > 0)
                    <span class="badge-clean bg-rose-100 text-rose-800 text-[11px]">{{ $stats['over_count'] }} Lebih</span>
                @endif
                @if($stats['under_count'] > 0)
                    <span class="badge-clean bg-amber-100 text-amber-800 text-[11px]">{{ $stats['under_count'] }} Kurang</span>
                @endif
            </div>
        </div>

        <!-- Card 4: Validasi & Alert -->
        <div class="card-clean p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Validasi & Alert</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $stats['validations_pending'] }} <span class="text-xs font-semibold text-slate-500">Antrean Validasi</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <a href="{{ route('variance.index') }}" class="text-blue-600 font-bold hover:underline">Dual Approval &rarr;</a>
                <span class="badge-clean {{ $stats['alerts_unread'] > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $stats['alerts_unread'] }} Alert
                </span>
            </div>
        </div>

        <!-- Card 5: Pembelian di Luar RAB (Non-RAB) -->
        <div class="card-clean p-5 card-clean-hover border-purple-200/80 bg-gradient-to-br from-white via-white to-purple-50/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Item di Luar RAB</span>
                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($stats['non_rab_total_cost'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="font-bold {{ ($stats['non_rab_items_count'] ?? 0) > 0 ? 'text-purple-700' : 'text-slate-500' }}">
                    {{ $stats['non_rab_items_count'] ?? 0 }} Material Non-RAB
                </span>
                <a href="#non-rab-monitoring" class="text-purple-600 font-bold hover:underline">Lihat Rekapan &darr;</a>
            </div>
        </div>
    </div>

    <!-- 3. GRAFIK PERBANDINGAN BIAYA PER KATEGORI -->
    <div class="card-clean p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Perbandingan Anggaran RAB vs Realisasi per Kategori</h3>
                <p class="text-xs text-slate-500">Visualisasi alokasi biaya rencana terhadap pengeluaran aktual</p>
            </div>
            <a href="{{ route('cost.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">Buka Detail Laporan &rarr;</a>
        </div>
        <div id="categoryCostChart" class="w-full h-64 sm:h-80"></div>
    </div>

    <!-- 4. REKAPAN MONITORING PEMBELIAN MATERIAL DI LUAR RAB (NON-RAB / UNBUDGETED) -->
    <div id="non-rab-monitoring" class="card-clean overflow-hidden border border-purple-200/80 shadow-xs" x-data="{
        searchNonRab: '',
        filterStatus: 'all',
        viewMode: 'table', // 'table' or 'cards'
        items: {{ Js::from($nonRabPurchases) }},
        matches(item) {
            let s = (this.searchNonRab || '').trim().toLowerCase();
            let matchesSearch = true;
            if (s.length > 0) {
                let poText = (item.pos || []).map(p => (p.po_number || '') + ' ' + (p.supplier ? p.supplier.name : '')).join(' ');
                let doText = (item.dos || []).map(d => d.do_number || '').join(' ');
                let text = [
                    item.material_code || '',
                    item.material_name || '',
                    item.category || '',
                    poText,
                    doText
                ].join(' ').toLowerCase();
                matchesSearch = text.includes(s);
            }

            let matchesStatus = true;
            if (this.filterStatus === 'completed') {
                matchesStatus = item.status_type === 'completed';
            } else if (this.filterStatus === 'partial') {
                matchesStatus = item.status_type === 'partial';
            } else if (this.filterStatus === 'pending') {
                matchesStatus = item.status_type === 'pending';
            } else if (this.filterStatus === 'unplanned_do') {
                matchesStatus = item.status_type === 'unplanned_do';
            }

            return matchesSearch && matchesStatus;
        },
        filteredCount() {
            return this.items.filter(it => this.matches(it)).length;
        }
    }">
        <!-- Header Panel with Title, View Switcher, and Filters -->
        <div class="p-4 sm:p-6 border-b border-purple-100 bg-gradient-to-r from-purple-50/60 via-white to-indigo-50/40 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600 animate-pulse"></span>
                        <h3 class="text-sm sm:text-lg font-extrabold text-slate-900 tracking-tight">
                            Rekapan Monitoring Pembelian Material di Luar RAB
                        </h3>
                        <span class="badge-clean bg-purple-100 text-purple-800 text-[11px] font-mono border border-purple-200 font-bold">
                            Unbudgeted / Non-RAB
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        Pengawasan khusus terhadap pengadaan material, alat bantu, atau item tambahan yang tidak tercantum dalam Bill of Material (BOM) RAB
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" x-model="searchNonRab" placeholder="Cari material non-RAB, PO, supplier..." 
                               class="w-full sm:w-64 text-xs pl-8 pr-7 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all shadow-2xs font-medium">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <button type="button" x-show="searchNonRab.length > 0" @click="searchNonRab = ''" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold cursor-pointer">&times;</button>
                    </div>

                    <!-- View Switcher -->
                    <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/60 shadow-inner">
                        <button type="button" @click="viewMode = 'table'" 
                                :class="viewMode === 'table' ? 'bg-white text-purple-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1z"/></svg>
                            Tabel Detail
                        </button>
                        <button type="button" @click="viewMode = 'cards'" 
                                :class="viewMode === 'cards' ? 'bg-white text-purple-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Kartu Visual
                        </button>
                    </div>

                    @if(auth()->user()?->canWritePo())
                        <a href="{{ route('procurement.po.index') }}" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl shadow-sm transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ Buat PO Non-RAB</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Filter Pills & Summary Metrics -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-purple-100/60">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="filterStatus = 'all'"
                            :class="filterStatus === 'all' ? 'bg-purple-700 text-white shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        Semua (<span x-text="items.length"></span>)
                    </button>
                    <button type="button" @click="filterStatus = 'completed'"
                            :class="filterStatus === 'completed' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-slate-100 hover:bg-emerald-50 text-emerald-800 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        Tiba Lengkap (<span x-text="items.filter(i => i.status_type === 'completed').length"></span>)
                    </button>
                    <button type="button" @click="filterStatus = 'partial'"
                            :class="filterStatus === 'partial' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-slate-100 hover:bg-blue-50 text-blue-800 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        Sebagian Masuk (<span x-text="items.filter(i => i.status_type === 'partial').length"></span>)
                    </button>
                    <button type="button" @click="filterStatus = 'pending'"
                            :class="filterStatus === 'pending' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-slate-100 hover:bg-amber-50 text-amber-800 font-medium'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        Menunggu Kirim (<span x-text="items.filter(i => i.status_type === 'pending').length"></span>)
                    </button>
                </div>

                <div class="flex items-center gap-3 text-xs">
                    <span class="text-slate-500 font-medium">Total Komitmen Belanja Non-RAB:</span>
                    <span class="font-black text-purple-900 bg-purple-100/90 px-3 py-1 rounded-lg border border-purple-200 font-mono text-[13px]">
                        Rp {{ number_format($stats['non_rab_total_cost'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- VIEW A: TABEL DETAIL REKAPAN NON-RAB -->
        <div x-show="viewMode === 'table'" class="overflow-x-auto">
            <template x-if="filteredCount() > 0">
                <table class="w-full text-left text-xs table-clean min-w-[950px]">
                    <thead class="bg-slate-50/80 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-4 w-24">Kode</th>
                            <th class="py-3.5 px-5">Material Non-RAB & Spesifikasi</th>
                            <th class="py-3.5 px-4">Referensi PO & Supplier</th>
                            <th class="py-3.5 px-4 text-right">Dipesan (PO)</th>
                            <th class="py-3.5 px-4 text-right">Harga Satuan</th>
                            <th class="py-3.5 px-4 text-right">Total Biaya (Rp)</th>
                            <th class="py-3.5 px-5 text-left min-w-[180px]">Realisasi Fisik (DO)</th>
                            <th class="py-3.5 px-4 text-center">Status Pemenuhan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="item in items.filter(it => matches(it))" :key="item.material_id">
                            <tr class="hover:bg-purple-50/30 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-800" x-text="item.material_code"></td>
                                <td class="py-3 px-5">
                                    <div class="font-extrabold text-slate-900" x-text="item.material_name"></div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-bold text-purple-700 uppercase" x-text="item.category"></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-[10px] text-slate-400 font-medium">Satuan: <span x-text="item.unit"></span></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <template x-if="item.pos && item.pos.length > 0">
                                        <div>
                                            <template x-for="po in item.pos" :key="po.id">
                                                <div>
                                                    <span class="font-mono font-bold text-purple-700" x-text="po.po_number"></span>
                                                    <div class="text-[11px] text-slate-500" x-text="po.supplier ? po.supplier.name : '-'"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!item.pos || item.pos.length === 0">
                                        <span class="text-slate-400 italic text-[11px]">Tanpa PO (Langsung DO)</span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-800" 
                                    x-text="parseFloat(item.po_qty).toLocaleString('id-ID', { maximumFractionDigits: 4 }) + ' ' + item.unit">
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-600" 
                                    x-text="'Rp ' + Number(item.avg_unit_price).toLocaleString('id-ID')">
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-black text-purple-950" 
                                    x-text="'Rp ' + Number(item.po_cost).toLocaleString('id-ID')">
                                </td>
                                <td class="py-3 px-5">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-bold text-slate-700" x-text="'Masuk: ' + parseFloat(item.do_qty).toLocaleString('id-ID', { maximumFractionDigits: 4 }) + ' ' + item.unit"></span>
                                        <span class="font-mono text-slate-400" x-text="item.po_qty > 0 ? (Math.round((item.do_qty / item.po_qty) * 100) + '%') : '-'"></span>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full bg-purple-600" 
                                             :style="'width: ' + Math.min(100, item.po_qty > 0 ? Math.round((item.do_qty / item.po_qty) * 100) : 0) + '%'"></div>
                                    </div>
                                    <!-- Surat Jalan badges -->
                                    <template x-if="item.dos && item.dos.length > 0">
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <template x-for="d in item.dos" :key="d.id">
                                                <button type="button" 
                                                        @click="d.attachment_path ? openDocPreview('/storage/' + d.attachment_path, 'Surat Jalan: ' + d.do_number) : null"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 hover:bg-purple-100 hover:text-purple-800 transition-colors cursor-pointer"
                                                        :title="d.attachment_path ? 'Klik untuk melihat scan surat jalan' : 'Surat Jalan tanpa lampiran scan'">
                                                    <span x-text="d.do_number"></span>
                                                    <template x-if="d.attachment_path">
                                                        <svg class="w-2.5 h-2.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    </template>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border shadow-2xs" 
                                          :class="item.badge_cls" 
                                          x-text="item.status_label">
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>

        <!-- VIEW B: KARTU VISUAL REKAPAN NON-RAB -->
        <div x-show="viewMode === 'cards'" class="p-6">
            <template x-if="filteredCount() > 0">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="item in items.filter(it => matches(it))" :key="item.material_id">
                        <div class="rounded-2xl border border-purple-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-purple-50 text-purple-700 rounded border border-purple-200" x-text="item.material_code"></span>
                                    <span class="badge-clean text-[10px] font-extrabold border" :class="item.badge_cls" x-text="item.status_label"></span>
                                </div>
                                <h4 class="text-sm font-extrabold text-slate-900 mt-2 line-clamp-1" x-text="item.material_name"></h4>
                                <div class="text-[11px] text-slate-400 capitalize mt-0.5" x-text="item.category"></div>

                                <div class="mt-3 p-3 bg-slate-50/80 rounded-xl space-y-2 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Total Biaya PO:</span>
                                        <span class="font-mono font-black text-purple-900" x-text="'Rp ' + Number(item.po_cost).toLocaleString('id-ID')"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Dipesan vs Tiba:</span>
                                        <span class="font-mono font-bold text-slate-700" 
                                              x-text="parseFloat(item.do_qty).toLocaleString('id-ID') + ' / ' + parseFloat(item.po_qty).toLocaleString('id-ID') + ' ' + item.unit"></span>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full bg-purple-600" 
                                             :style="'width: ' + Math.min(100, item.po_qty > 0 ? Math.round((item.do_qty / item.po_qty) * 100) : 0) + '%'"></div>
                                    </div>
                                </div>

                                <template x-if="item.pos && item.pos.length > 0">
                                    <div class="mt-2.5 text-[11px] text-slate-500">
                                        <span class="font-semibold text-slate-700">No. PO:</span>
                                        <span class="font-mono text-purple-700 font-bold" x-text="item.pos[0].po_number"></span>
                                        <template x-if="item.pos[0].supplier">
                                            <span x-text="' (' + item.pos[0].supplier.name + ')'"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100">
                                    100% Unbudgeted
                                </span>
                                <template x-if="item.dos && item.dos.length > 0 && item.dos[0].attachment_path">
                                    <button type="button" 
                                            @click="openDocPreview('/storage/' + item.dos[0].attachment_path, 'Surat Jalan: ' + item.dos[0].do_number)"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:text-purple-900 cursor-pointer">
                                        <span>Lihat Scan DO</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- EMPTY STATE (JIKA TIDAK ADA / TIDAK COCOK SEARCH) -->
        <div x-show="filteredCount() === 0" class="py-12 text-center" x-cloak>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3 border border-purple-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-bold text-slate-800">
                <template x-if="items.length === 0">
                    <span>Tidak Ada Pembelian di Luar RAB</span>
                </template>
                <template x-if="items.length > 0">
                    <span>Tidak Ada Item Non-RAB yang Cocok</span>
                </template>
            </p>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <template x-if="items.length === 0">
                    <span>Seluruh pengadaan material pada proyek ini berjalan tepat 100% di dalam koridor Bill of Material (BOM) rencana anggaran proyek.</span>
                </template>
                <template x-if="items.length > 0">
                    <span>Pencarian tidak menemukan material atau surat jalan yang sesuai filter.</span>
                </template>
            </p>
            <template x-if="items.length === 0 && {{ auth()->user()?->canWritePo() ? 'true' : 'false' }}">
                <a href="{{ route('procurement.po.index') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Buat Purchase Order (PO)</span>
                </a>
            </template>
        </div>
    </div>

    <!-- 5. EXECUTIVE MONITORING & ASSESSMENT: MATERIAL VS RAB (BASELINE) -->
    <div class="card-clean overflow-hidden" x-data="{
        search: '',
        statusFilter: 'all',
        viewMode: 'cards', // 'cards' (executive cards) or 'table' (analytical table)
        matchesItem(row) {
            let s = (this.search || '').trim().toLowerCase();
            let matchesSearch = true;
            if (s.length > 0) {
                let cleanS = s.replace(/[^a-z0-9]/gi, '');
                let targetText = [
                    row.name || '',
                    row.code || '',
                    row.baseCategory || '',
                    row.rabCategory || '',
                    row.rabCode || '',
                    row.rabFull || '',
                    row.sectionTitle || '',
                    row.sectionSubtitle || ''
                ].join(' ').toLowerCase();

                let cleanTarget = targetText.replace(/[^a-z0-9]/gi, '');

                // 1. Direct substring match or normalized alphanumeric match
                let isDirect = targetText.includes(s) || (cleanS.length > 1 && cleanTarget.includes(cleanS));

                // 2. Tokenized words match (handles typos or partial queries)
                let isTokenMatch = false;
                let words = s.split(/[\s.&,\-_]+/).filter(w => w.length >= 2);
                if (words.length > 0) {
                    let matchedWords = words.filter(w => targetText.includes(w));
                    if (matchedWords.length === words.length || (words.length >= 3 && matchedWords.length >= words.length - 1)) {
                        isTokenMatch = true;
                    }
                }

                matchesSearch = isDirect || isTokenMatch;
            }

            let matchesStatus = true;
            if (this.statusFilter === 'over') {
                matchesStatus = row.evalType === 'over';
            } else if (this.statusFilter === 'pending') {
                matchesStatus = row.evalType === 'pending';
            } else if (this.statusFilter === 'partial') {
                matchesStatus = row.evalType === 'partial';
            } else if (this.statusFilter === 'normal') {
                matchesStatus = row.evalType === 'normal';
            }

            return matchesSearch && matchesStatus;
        },
        hasMatchingItems(items) {
            if (!items || !items.length) return false;
            return items.some(item => this.matchesItem(item));
        },
        countMatchingItems(items) {
            if (!items || !items.length) return 0;
            return items.filter(item => this.matchesItem(item)).length;
        },
        filterItem(row) {
            return this.matchesItem(row);
        }
    }">
        @php
            $overItems = $realizations->where('status', 'kelebihan');
            $pendingItems = $realizations->filter(fn($r) => (float)$r->actual_qty == 0);
            $partialItems = $realizations->filter(fn($r) => (float)$r->actual_qty > 0 && (float)$r->actual_qty < (float)$r->planned_qty);
            $normalItems = $realizations->filter(fn($r) => (float)$r->actual_qty == (float)$r->planned_qty && (float)$r->planned_qty > 0);
        @endphp

        <!-- Header Panel with Title, View Mode Switcher, and Search -->
        <div class="p-4 sm:p-6 border-b border-slate-100 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full {{ $overItems->count() > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500' }}"></span>
                        <h3 class="text-sm sm:text-lg font-extrabold text-slate-900 tracking-tight">Monitoring Realisasi Material vs RAB (Baseline)</h3>
                        <span class="badge-clean bg-blue-50 text-blue-700 text-[11px] font-mono border border-blue-200">BOM Level 5</span>
                    </div>
                    <p class="text-xs text-slate-500">Evaluasi visual kuantiti fisik Surat Jalan (DO) terhadap kuantiti rencana Bill of Material (BOM) secara real-time</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" x-model="search" placeholder="Cari material, kode, atau kategori..." 
                               class="w-full sm:w-64 text-xs pl-8 pr-7 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all shadow-2xs font-medium">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <button type="button" x-show="search.length > 0" @click="search = ''" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold cursor-pointer" title="Hapus Pencarian">&times;</button>
                    </div>

                    <!-- View Switcher (Cards vs Table) -->
                    <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/60 shadow-inner">
                        <button type="button" @click="viewMode = 'cards'" 
                                :class="viewMode === 'cards' ? 'bg-white text-blue-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Kartu Visual
                        </button>
                        <button type="button" @click="viewMode = 'table'" 
                                :class="viewMode === 'table' ? 'bg-white text-blue-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Tabel Detail
                        </button>
                    </div>
                </div>
            </div>

            <!-- Executive Diagnostic Callout (If Any Overages Exist) -->
            @if($overItems->count() > 0)
                <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-50 via-rose-50/70 to-amber-50/50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-extrabold text-rose-950 flex items-center gap-2">
                                <span>Perhatian Manajemen: {{ $overItems->count() }} Material Melebihi Kuota RAB!</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-200/80 text-rose-900 font-mono font-bold">Audit Segera</span>
                            </div>
                            <p class="text-[11px] text-rose-800 mt-0.5">
                                Kuantitas fisik dari Surat Jalan (DO) telah melampaui toleransi rencana BOM. Lakukan verifikasi jembatan timbang fisik & persetujuan dual validation bersama Purchasing.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" @click="statusFilter = 'over'" 
                                class="px-3 py-1.5 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs transition-all">
                            Filter Material Over &rarr;
                        </button>
                    </div>
                </div>
            @endif

            <!-- Interactive Status Filter Tabs with Live Counters -->
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <button type="button" @click="statusFilter = 'all'" 
                        :class="statusFilter === 'all' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold'"
                        class="px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5">
                    Semua Material
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="statusFilter === 'all' ? 'bg-slate-700 text-slate-100' : 'bg-slate-200 text-slate-600'">{{ $realizations->count() }}</span>
                </button>

                <button type="button" @click="statusFilter = 'over'" 
                        :class="statusFilter === 'over' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-semibold'"
                        class="px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Melebihi RAB (Over)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="statusFilter === 'over' ? 'bg-rose-800 text-white' : 'bg-rose-200 text-rose-900'">{{ $overItems->count() }}</span>
                </button>

                <button type="button" @click="statusFilter = 'pending'" 
                        :class="statusFilter === 'pending' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-semibold'"
                        class="px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Belum Diterima (Menunggu DO)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="statusFilter === 'pending' ? 'bg-amber-800 text-white' : 'bg-amber-200 text-amber-900'">{{ $pendingItems->count() }}</span>
                </button>

                <button type="button" @click="statusFilter = 'partial'" 
                        :class="statusFilter === 'partial' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 font-semibold'"
                        class="px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Sebagian Masuk
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="statusFilter === 'partial' ? 'bg-blue-800 text-white' : 'bg-blue-200 text-blue-900'">{{ $partialItems->count() }}</span>
                </button>

                <button type="button" @click="statusFilter = 'normal'" 
                        :class="statusFilter === 'normal' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold'"
                        class="px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sesuai RAB (100%)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="statusFilter === 'normal' ? 'bg-emerald-800 text-white' : 'bg-emerald-200 text-emerald-900'">{{ $normalItems->count() }}</span>
                </button>
            </div>
        </div>

        <!-- 1. VIEW MODE A: EXECUTIVE VISUAL CARDS (HIERARKIS RAB TREE — 4 KOLOM) -->
        <div x-show="viewMode === 'cards'" class="p-6 space-y-8">
            @php
                $allCardsMats = [];
                foreach ($treeRealizations as $cg) {
                    foreach ($cg['sections'] as $s) {
                        foreach ($s['materials'] as $m) {
                            $allCardsMats[] = [
                                'name' => $m->material->name,
                                'code' => $m->material->code,
                                'baseCategory' => $m->material->category,
                                'rabCategory' => $cg['category']->name,
                                'rabCode' => $cg['category']->code,
                                'rabFull' => $cg['category']->code . '. ' . $cg['category']->name,
                                'sectionTitle' => $s['title'],
                                'sectionSubtitle' => $s['subtitle'] ?? '',
                                'evalType' => $m->eval_type,
                            ];
                        }
                    }
                }
            @endphp

            @forelse($treeRealizations as $catGroup)
                @php
                    $catAllMats = [];
                    foreach ($catGroup['sections'] as $sec) {
                        foreach ($sec['materials'] as $m) {
                            $catAllMats[] = [
                                'name' => $m->material->name,
                                'code' => $m->material->code,
                                'baseCategory' => $m->material->category,
                                'rabCategory' => $catGroup['category']->name,
                                'rabCode' => $catGroup['category']->code,
                                'rabFull' => $catGroup['category']->code . '. ' . $catGroup['category']->name,
                                'sectionTitle' => $sec['title'],
                                'sectionSubtitle' => $sec['subtitle'] ?? '',
                                'evalType' => $m->eval_type,
                            ];
                        }
                    }
                @endphp

                <div x-show="hasMatchingItems({{ json_encode($catAllMats) }})" class="space-y-6">
                    <!-- KATEGORI BANNER (SESUAI WIREFRAME USER) -->
                    @php
                        $isUnmappedGroup = $catGroup['is_unmapped'] ?? false;
                    @endphp
                    <div class="rounded-2xl {{ $isUnmappedGroup ? 'bg-gradient-to-r from-amber-950 via-slate-900 to-amber-950 border-amber-500/40' : 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border-slate-700/60' }} text-white p-4 sm:p-5 shadow-md border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl {{ $isUnmappedGroup ? 'bg-amber-500/20 text-amber-400 border-amber-400/30' : 'bg-blue-500/20 text-blue-400 border-blue-400/30' }} border flex items-center justify-center font-black text-sm flex-shrink-0">
                                {{ $catGroup['category']->code }}
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold {{ $isUnmappedGroup ? 'text-amber-400' : 'text-blue-400' }} uppercase tracking-widest block">
                                    {{ $isUnmappedGroup ? 'MATERIAL DI LUAR ANGGARAN RAB (NON-RAB)' : 'KATEGORI UTAMA RAB' }}
                                </span>
                                <h3 class="text-base sm:text-lg font-black text-white tracking-tight leading-tight">
                                    {{ $catGroup['category']->code }}. {{ $catGroup['category']->name }}
                                </h3>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <span class="px-3 py-1 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-700">
                                <span x-text="countMatchingItems({{ json_encode($catAllMats) }})"></span> Material
                            </span>
                        </div>
                    </div>

                    <!-- SUB-KATEGORI SECTIONS -->
                    @foreach($catGroup['sections'] as $section)
                        @php
                            $secMats = [];
                            foreach ($section['materials'] as $m) {
                                $secMats[] = [
                                    'name' => $m->material->name,
                                    'code' => $m->material->code,
                                    'baseCategory' => $m->material->category,
                                    'rabCategory' => $catGroup['category']->name,
                                    'rabCode' => $catGroup['category']->code,
                                    'rabFull' => $catGroup['category']->code . '. ' . $catGroup['category']->name,
                                    'sectionTitle' => $section['title'],
                                    'sectionSubtitle' => $section['subtitle'] ?? '',
                                    'evalType' => $m->eval_type,
                                ];
                            }
                        @endphp

                        <div x-show="hasMatchingItems({{ json_encode($secMats) }})" class="space-y-3.5 pl-1 sm:pl-2">
                            <!-- Sub Kategori Heading Bar -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-1.5 border-b border-slate-200/80">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="w-2 h-2 rounded-full bg-blue-600"></div>
                                    <h4 class="text-sm font-extrabold text-slate-800 tracking-tight">
                                        {{ $section['title'] }}
                                    </h4>
                                    @if(!empty($section['subtitle']))
                                        <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-200/60">
                                            ↳ {{ $section['subtitle'] }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-semibold text-slate-400">
                                    <span x-text="countMatchingItems({{ json_encode($secMats) }})"></span> Item Material
                                </span>
                            </div>

                            <!-- 4-COLUMN RESPONSIVE MATERIAL CARDS GRID -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                @foreach($section['materials'] as $rel)
                                    @php
                                        $actual = (float) $rel->actual_qty;
                                        $planned = (float) $rel->planned_qty;
                                        $pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
                                        $unit = $rel->material->defaultUnit?->code ?? '-';
                                        $evalType = $rel->eval_type;

                                        if ($evalType === 'over') {
                                            $cardBorder = 'border-rose-300 ring-1 ring-rose-200 bg-gradient-to-b from-rose-50/40 via-white to-white';
                                            $badgeCls = 'bg-rose-100 text-rose-800 border-rose-200';
                                            $badgeLabel = 'Melebihi RAB (+' . number_format($rel->variance_pct, 1, ',', '.') . '%)';
                                            $descNote = 'Kuantitas fisik diterima melampaui RAB sebesar +' . format_qty($rel->variance_qty) . ' ' . $unit . '.';
                                        } elseif ($evalType === 'pending') {
                                            $cardBorder = 'border-slate-200 bg-white hover:border-slate-300';
                                            $badgeCls = 'bg-slate-100 text-slate-600 border-slate-200';
                                            $badgeLabel = 'Belum Ada Pengiriman (0%)';
                                            $descNote = 'Belum ada Surat Jalan (DO) fisik diterima di lapangan.';
                                        } elseif ($evalType === 'partial') {
                                            $cardBorder = 'border-blue-200 bg-white hover:border-blue-300';
                                            $badgeCls = 'bg-blue-100 text-blue-800 border-blue-200';
                                            $badgeLabel = 'Sebagian Masuk (' . $pct . '%)';
                                            $descNote = 'Tersisa ' . format_qty(abs($rel->variance_qty)) . ' ' . $unit . ' lagi untuk melengkapi kuota RAB.';
                                        } else {
                                            $cardBorder = 'border-emerald-200 bg-white hover:border-emerald-300';
                                            $badgeCls = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                            $badgeLabel = 'Sesuai RAB (100%)';
                                            $descNote = 'Seluruh kuota material telah terpenuhi 100% tepat sesuai RAB.';
                                        }

                                        $matDescriptor = [
                                            'name' => $rel->material->name,
                                            'code' => $rel->material->code,
                                            'baseCategory' => $rel->material->category,
                                            'rabCategory' => $catGroup['category']->name,
                                            'rabCode' => $catGroup['category']->code,
                                            'rabFull' => $catGroup['category']->code . '. ' . $catGroup['category']->name,
                                            'sectionTitle' => $section['title'],
                                            'sectionSubtitle' => $section['subtitle'] ?? '',
                                            'evalType' => $evalType,
                                        ];
                                    @endphp

                                    <!-- INTERACTIVE CLICKABLE CARD (REDIRECT TO DETAIL & HISTORY) -->
                                    <div x-show="matchesItem({{ json_encode($matDescriptor) }})"
                                         class="h-full">
                                        <a href="{{ route('monitoring.material.show', $rel->id) }}" 
                                            class="group h-full rounded-2xl border p-4 transition-all duration-200 hover:shadow-xl hover:scale-[1.015] hover:border-blue-500 cursor-pointer flex flex-col justify-between {{ $cardBorder }}"
                                           title="Klik untuk melihat riwayat DO, faktur invoice, dan alokasi RAB material ini">
                                            
                                            <div>
                                                <!-- Top Bar: Code & Health Badge -->
                                                <div class="flex items-center justify-between gap-1.5 pb-2 border-b border-slate-100">
                                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-slate-100 text-slate-700 rounded border border-slate-200 group-hover:bg-blue-50 group-hover:text-blue-700 transition-colors">
                                                        {{ $rel->material->code }}
                                                    </span>

                                                    @if($evalType === 'over')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs whitespace-nowrap shrink-0">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                                                            <span>Over RAB</span>
                                                        </span>
                                                    @elseif($evalType === 'pending')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 shadow-2xs whitespace-nowrap shrink-0">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                                            <span>Belum DO</span>
                                                        </span>
                                                    @elseif($evalType === 'partial')
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs whitespace-nowrap shrink-0">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                                            <span>{{ $pct }}%</span>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs whitespace-nowrap shrink-0">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                            <span>100% Sesuai</span>
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- HERO MATERIAL TITLE: SANGAT BESAR & BOLD -->
                                                <div class="pt-2.5 pb-1">
                                                    <h3 class="text-base sm:text-[17px] font-black text-slate-950 tracking-tight leading-snug line-clamp-2 group-hover:text-blue-600 transition-colors">
                                                        {{ $rel->material->name }}
                                                    </h3>
                                                </div>

                                                <!-- Progress Bar Visual Gauge -->
                                                <div class="mt-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1.5">
                                                    <div class="flex items-center justify-between text-[11px]">
                                                        <span class="font-bold text-slate-600">Realisasi Fisik</span>
                                                        <div class="flex items-center gap-1">
                                                            <span class="font-black font-mono {{ $evalType === 'over' ? 'text-rose-600' : ($evalType === 'normal' ? 'text-emerald-700' : ($evalType === 'partial' ? 'text-blue-700' : 'text-slate-500')) }}">
                                                                {{ $pct }}%
                                                            </span>
                                                            @if($evalType === 'over')
                                                                <span class="text-[9px] font-extrabold text-rose-700 bg-rose-100 px-1 py-0.2 rounded border border-rose-200">OVER</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Dual-Color Progress Track -->
                                                    <div class="w-full bg-slate-200/80 rounded-full h-2.5 p-0.5 overflow-hidden flex relative shadow-inner">
                                                        @if($evalType === 'over')
                                                            <div class="bg-blue-600 h-full rounded-l-full" style="width: 85%;"></div>
                                                            <div class="h-full rounded-r-full animate-pulse bg-rose-500" 
                                                                 style="width: 15%; background-image: repeating-linear-gradient(45deg, transparent, transparent 3px, rgba(255,255,255,0.4) 3px, rgba(255,255,255,0.4) 6px);"></div>
                                                        @elseif($pct > 0)
                                                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-full rounded-full transition-all duration-500" 
                                                                 style="width: {{ min($pct, 100) }}%;"></div>
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center text-[8px] font-bold text-slate-400">
                                                                0% Belum Dikirim
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center justify-between text-[10px] text-slate-500 pt-0.5">
                                                        <span>Kirim: <strong class="text-slate-800 font-mono">{{ number_format($actual, 1, ',', '.') }}</strong></span>
                                                        <span>Target: <strong class="text-slate-700 font-mono">{{ number_format($planned, 1, ',', '.') }} {{ $unit }}</strong></span>
                                                    </div>
                                                </div>

                                                <!-- 3-Pill Metrics Grid -->
                                                <div class="grid grid-cols-3 gap-1.5 mt-2.5 text-center">
                                                    <div class="p-1.5 rounded-lg bg-white border border-slate-100 shadow-2xs">
                                                        <span class="block text-[9px] text-slate-400 font-bold uppercase">RAB</span>
                                                        <span class="text-xs font-mono font-bold text-slate-800">{{ format_qty($planned) }}</span>
                                                        <span class="text-[9px] text-slate-400 block">{{ $unit }}</span>
                                                    </div>
                                                    <div class="p-1.5 rounded-lg bg-white border border-slate-100 shadow-2xs">
                                                        <span class="block text-[9px] text-slate-400 font-bold uppercase">DO</span>
                                                        <span class="text-xs font-mono font-extrabold {{ $actual > 0 ? 'text-blue-700' : 'text-slate-400' }}">{{ format_qty($actual) }}</span>
                                                        <span class="text-[9px] text-slate-400 block">{{ $unit }}</span>
                                                    </div>
                                                    <div class="p-1.5 rounded-lg bg-white border border-slate-100 shadow-2xs">
                                                        <span class="block text-[9px] text-slate-400 font-bold uppercase">Selisih</span>
                                                        <span class="text-xs font-mono font-extrabold {{ $rel->variance_qty > 0 ? 'text-rose-600' : ($rel->variance_qty < 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                                            {{ $rel->variance_qty > 0 ? '+' : '' }}{{ format_qty($rel->variance_qty) }}
                                                        </span>
                                                        <span class="text-[9px] text-slate-400 block">{{ $unit }}</span>
                                                    </div>
                                                </div>

                                                <!-- Contextual Insight Box -->
                                                <div class="mt-2.5 p-2 rounded-lg text-[10px] leading-relaxed {{ $evalType === 'over' ? 'bg-rose-50/80 text-rose-900 border border-rose-100 font-medium' : 'bg-slate-50 text-slate-600' }}">
                                                    <span class="font-bold">{{ $evalType === 'over' ? '⚠️ Rekomendasi:' : 'ℹ️ Status:' }}</span> {{ $descNote }}
                                                </div>
                                            </div>

                                            <!-- Card Click-Through Footer -->
                                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                                                <span class="text-[11px] font-bold text-blue-600 group-hover:text-blue-700 flex items-center gap-1 transition-all">
                                                    Lihat Detail & Riwayat
                                                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                </span>

                                                @if($evalType === 'over')
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-600 text-white shadow-2xs">
                                                        Validasi
                                                    </span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 font-medium">
                                                        #{{ $rel->id }}
                                                    </span>
                                                @endif
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="py-12 text-center text-slate-400">
                    Belum ada data realisasi material di proyek ini.
                </div>
            @endforelse

            <!-- Empty state when search or filter yields 0 matches in Cards View -->
            <div x-show="hasMatchingItems({{ json_encode($allCardsMats) }}) === false" class="py-16 text-center" x-cloak>
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <p class="text-sm font-bold text-slate-800">Tidak ada kategori atau material yang cocok</p>
                <p class="text-xs text-slate-500 mt-1">
                    Pencarian "<span x-text="search" class="font-semibold text-slate-700"></span>" tidak menemukan material yang sesuai.
                </p>
                <button type="button" @click="search = ''; statusFilter = 'all'" 
                        class="mt-4 px-4 py-2 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors cursor-pointer">
                    Reset Filter & Tampilkan Semua
                </button>
            </div>
        </div>

        <!-- 2. VIEW MODE B: SMART ANALYTICAL TABLE (DENGAN INDIKATOR VISUAL) -->
        <div x-show="viewMode === 'table'" class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white shadow-xs" x-cloak>
            @php
                $allTableMats = [];
                foreach ($realizations as $rel) {
                    $allTableMats[] = [
                        'name' => $rel->material->name,
                        'code' => $rel->material->code,
                        'baseCategory' => $rel->material->category,
                        'rabCategory' => $rel->rab_category_name ?? '',
                        'rabCode' => $rel->rab_category_code ?? '',
                        'rabFull' => $rel->rab_category_full ?? '',
                        'sectionTitle' => $rel->rab_section_title ?? '',
                        'sectionSubtitle' => $rel->rab_section_subtitle ?? '',
                        'evalType' => $rel->eval_type,
                    ];
                }
            @endphp
            <table class="w-full text-left text-xs table-clean min-w-[1040px]">
                <thead class="bg-slate-50/90 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px] whitespace-nowrap">
                    <tr>
                        <th class="py-3.5 px-4 w-28 whitespace-nowrap">Kode</th>
                        <th class="py-3.5 px-5 min-w-[240px] whitespace-nowrap">Material Dasar & Kategori</th>
                        <th class="py-3.5 px-3 text-center w-20 whitespace-nowrap">Satuan</th>
                        <th class="py-3.5 px-4 text-right min-w-[120px] whitespace-nowrap">Rencana (RAB)</th>
                        <th class="py-3.5 px-5 text-left min-w-[200px] whitespace-nowrap">Realisasi Fisik & Progress</th>
                        <th class="py-3.5 px-4 text-right min-w-[140px] whitespace-nowrap">Selisih (Variance)</th>
                        <th class="py-3.5 px-5 text-center min-w-[190px] whitespace-nowrap">Status Evaluasi</th>
                        <th class="py-3.5 px-5 text-center min-w-[210px] whitespace-nowrap">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($realizations as $rel)
                        @php
                            $actual = (float) $rel->actual_qty;
                            $planned = (float) $rel->planned_qty;
                            $pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
                            $unit = $rel->material->defaultUnit?->code ?? '-';
                            $evalType = $rel->eval_type;

                            if ($evalType === 'over') {
                                $rowAccent = 'border-l-4 border-l-rose-500 bg-rose-50/20';
                            } else {
                                $rowAccent = '';
                            }

                            $rowDescriptor = [
                                'name' => $rel->material->name,
                                'code' => $rel->material->code,
                                'baseCategory' => $rel->material->category,
                                'rabCategory' => $rel->rab_category_name ?? '',
                                'rabCode' => $rel->rab_category_code ?? '',
                                'rabFull' => $rel->rab_category_full ?? '',
                                'sectionTitle' => $rel->rab_section_title ?? '',
                                'sectionSubtitle' => $rel->rab_section_subtitle ?? '',
                                'evalType' => $evalType,
                            ];
                        @endphp
                        <tr x-show="matchesItem({{ json_encode($rowDescriptor) }})" 
                            class="transition-colors hover:bg-slate-50/80 {{ $rowAccent }}">
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 font-mono text-[11px] font-bold bg-slate-100/90 text-slate-700 rounded-md border border-slate-200/80 inline-block shadow-2xs">
                                    {{ $rel->material->code }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-sm font-black text-slate-900 tracking-tight leading-snug line-clamp-1">{{ $rel->material->name }}</div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mt-0.5">
                                    {{ str_replace('_', ' ', $rel->material->category) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 bg-slate-50 border border-slate-200/70 rounded text-[11px] font-bold text-slate-600 font-mono">
                                    {{ $unit }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap text-xs">
                                {{ format_qty($planned) }}
                            </td>
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <div class="w-48">
                                    <div class="flex items-center justify-between font-mono text-xs font-bold mb-1.5">
                                        <span class="{{ $actual > 0 ? 'text-blue-700 font-extrabold' : 'text-slate-400' }}">{{ format_qty($actual) }}</span>
                                        <span class="text-[11px] font-extrabold {{ $evalType === 'over' ? 'text-rose-600' : ($evalType === 'normal' ? 'text-emerald-700' : ($evalType === 'partial' ? 'text-blue-700' : 'text-slate-400')) }}">
                                            {{ $pct }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden flex shadow-inner">
                                        @if($evalType === 'over')
                                            <div class="bg-blue-600 h-full" style="width: 80%"></div>
                                            <div class="bg-rose-500 h-full animate-pulse" style="width: 20%"></div>
                                        @elseif($pct > 0)
                                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-full" style="width: {{ min($pct, 100) }}%"></div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                                @if($evalType === 'over')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 font-black text-xs border border-rose-200 shadow-2xs">
                                        +{{ format_qty($rel->variance_qty) }}
                                    </span>
                                @elseif($actual == 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-slate-400 font-semibold text-xs">
                                        -{{ format_qty($planned) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-amber-700 font-bold text-xs">
                                        {{ format_qty($rel->variance_qty) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @if($evalType === 'over')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                                        <span>Melebihi RAB (+{{ number_format($rel->variance_pct, 1, ',', '.') }}%)</span>
                                    </span>
                                @elseif($evalType === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                                        <span>Belum Diterima (0%)</span>
                                    </span>
                                @elseif($evalType === 'partial')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                                        <span>Sebagian ({{ $pct }}%)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span>Tepat Sesuai (100%)</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-2 whitespace-nowrap">
                                    <a href="{{ route('monitoring.material.show', $rel->id) }}" 
                                       class="group inline-flex items-center gap-1.5 h-8 px-3 text-xs font-bold bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 rounded-lg border border-blue-200/90 hover:border-blue-600 shadow-2xs transition-all duration-150 whitespace-nowrap"
                                       title="Lihat riwayat DO, faktur, dan rincian alokasi RAB">
                                        <span>Detail & Riwayat</span>
                                        <svg class="w-3.5 h-3.5 text-blue-500 group-hover:text-white transition-transform group-hover:translate-x-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('variance.index') }}" 
                                       class="inline-flex items-center justify-center h-8 px-3 text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 rounded-lg border border-slate-200 shadow-2xs hover:border-slate-300 transition-colors whitespace-nowrap">
                                        Validasi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada data realisasi material.</td>
                        </tr>
                    @endforelse

                    <tr x-show="hasMatchingItems({{ json_encode($allTableMats) }}) === false" x-cloak>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            Tidak ada data material yang sesuai filter atau kata kunci pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. DUA KOLOM: RECENT DOS & ALERTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Surat Jalan (DO) Terbaru -->
        <div class="card-clean p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900">Surat Jalan (DO) Diterima di Lapangan</h4>
                </div>
                <a href="{{ route('procurement.index') }}" class="text-xs text-blue-600 font-bold hover:underline">Semua DO &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentDos as $do)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-800">{{ $do->do_number }}</div>
                            <div class="text-[11px] text-slate-500">
                                {{ $do->supplier->name }} • Diterima: {{ $do->receiver->name }} ({{ \Carbon\Carbon::parse($do->do_date)->format('d M Y') }})
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">{{ ucfirst($do->status) }}</span>
                            @if($do->attachment_path)
                                <button type="button" 
                                        onclick="openDocPreview('{{ asset('storage/' . $do->attachment_path) }}', 'Surat Jalan (DO): {{ addslashes($do->do_number) }}')" 
                                        class="text-xs text-blue-600 font-semibold hover:underline cursor-pointer">
                                    Bukti Fisik
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">Belum ada surat jalan yang diinput.</div>
                @endforelse
            </div>
        </div>

        <!-- Alert & Peringatan Terkini -->
        <div class="card-clean p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900">Peringatan Threshold Aktif</h4>
                </div>
                <a href="{{ route('alerts.index') }}" class="text-xs text-blue-600 font-bold hover:underline">Pusat Alert &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentAlerts as $alert)
                    <div class="py-3 flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 {{ $alert->severity === 'critical' ? 'bg-rose-500' : 'bg-amber-500' }}"></span>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-slate-800 leading-snug">{{ $alert->message }}</p>
                            <span class="text-[10px] text-slate-400">{{ $alert->created_at->diffForHumans() }}</span>
                        </div>
                        <span class="badge-clean {{ $alert->severity === 'critical' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }} text-[10px]">
                            {{ ucfirst($alert->severity) }}
                        </span>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-emerald-600 font-medium">Semua material dan biaya masih berada dalam batas threshold aman.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categories = @json($chartCategories);
        const budgets = @json($chartBudgets);
        const actuals = @json($chartActuals);

        const options = {
            series: [
                { name: 'Anggaran RAB', data: budgets },
                { name: 'Realisasi Aktual', data: actuals }
            ],
            chart: {
                type: 'bar',
                height: 320,
                fontFamily: 'inherit',
                toolbar: { show: false },
                animations: { enabled: true }
            },
            colors: ['#2563eb', '#10b981'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 6,
                    borderRadiusApplication: 'end'
                }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px', fontWeight: 500 } }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                fontWeight: 600
            }
        };

        if (document.getElementById('categoryCostChart') && window.ApexCharts) {
            const chart = new ApexCharts(document.getElementById('categoryCostChart'), options);
            chart.render();
        }
    });
</script>
@endpush
