@extends('layouts.app')

@section('title', 'Validasi Ganda Variance Material — ' . $project->name)
@section('page_title', 'Validasi Ganda Variance Material')
@section('page_subtitle', 'Alur persetujuan ganda wajib DO (Pengawas Lapangan) + Invoice (Purchasing) untuk setiap selisih material')

@section('content')
<div class="space-y-6" x-data="{
    showPengawasModal: false,
    showPurchasingModal: false,
    activeValId: null,
    valData: {},
    search: '',
    filterTab: 'all',
    filterRow(row) {
        let matchesSearch = row.name.toLowerCase().includes(this.search.toLowerCase()) || 
                            row.code.toLowerCase().includes(this.search.toLowerCase()) ||
                            row.category.toLowerCase().includes(this.search.toLowerCase());
        let matchesTab = true;
        if (this.filterTab === 'over') matchesTab = row.isOver;
        else if (this.filterTab === 'pending') matchesTab = !row.isFullyValidated && row.actual > 0;
        else if (this.filterTab === 'validated') matchesTab = row.isFullyValidated;
        else if (this.filterTab === 'empty') matchesTab = row.actual == 0;
        return matchesSearch && matchesTab;
    }
}">

    @php
        $overCount = $realizations->where('status', 'kelebihan')->count();
        $validatedCount = $realizations->filter(fn($r) => $r->varianceValidations->isNotEmpty() && $r->varianceValidations->first()->status === 'fully_validated')->count();
        $inProgressCount = $realizations->filter(fn($r) => $r->varianceValidations->isNotEmpty() && $r->varianceValidations->first()->status !== 'fully_validated')->count();
        $emptyCount = $realizations->filter(fn($r) => (float)$r->actual_qty == 0)->count();
    @endphp

    <!-- 1. EXECUTIVE KPI SUMMARY SCORECARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Terlacak -->
        <div class="card-clean p-4 border border-slate-200/80 bg-white shadow-2xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Material Terlacak</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                    {{ $realizations->count() }}
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2 font-mono">{{ $realizations->count() }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Material BOM aktif dalam proyek</span>
        </div>

        <!-- Card 2: Kelebihan (Over) -->
        <div class="card-clean p-4 border {{ $overCount > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200/80 bg-white' }} shadow-2xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider {{ $overCount > 0 ? 'text-rose-600 font-extrabold' : 'text-slate-400' }}">Melebihi RAB (Over)</span>
                <div class="w-8 h-8 rounded-lg {{ $overCount > 0 ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-bold text-xs">
                    ⚠️
                </div>
            </div>
            <div class="text-2xl font-black {{ $overCount > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-2 font-mono">{{ $overCount }}</div>
            <span class="text-[11px] {{ $overCount > 0 ? 'text-rose-700 font-semibold' : 'text-slate-400' }} mt-1 block">
                {{ $overCount > 0 ? 'Wajib verifikasi fisik & audit' : 'Tidak ada material over' }}
            </span>
        </div>

        <!-- Card 3: Menunggu Validasi -->
        <div class="card-clean p-4 border border-slate-200/80 bg-white shadow-2xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Dalam Proses Approval</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                    ⏳
                </div>
            </div>
            <div class="text-2xl font-black text-amber-700 mt-2 font-mono">{{ $inProgressCount }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Menunggu salah satu validator</span>
        </div>

        <!-- Card 4: Selesai Tervalidasi -->
        <div class="card-clean p-4 border border-slate-200/80 bg-white shadow-2xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Fully Validated</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                    ✓
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700 mt-2 font-mono">{{ $validatedCount }}</div>
            <span class="text-[11px] text-emerald-700 font-semibold mt-1 block">Tervalidasi ganda lengkap</span>
        </div>
    </div>

    <!-- 2. SOP PIPELINE STEPPER BANNER (HIGH CONTRAST DARK THEME) -->
    <div class="p-4 sm:p-6 rounded-2xl bg-slate-900 text-white shadow-xl border border-slate-800 relative overflow-hidden" style="background-color: #0f172a !important;">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-6">
            <div class="max-w-xl">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-400/40">
                        Protokol Validasi Ganda (Dual Approval)
                    </span>
                    <span class="text-xs text-slate-300 font-medium">Threshold: +{{ $project->alert_over_threshold_pct }}% / -{{ $project->alert_under_threshold_pct }}%</span>
                </div>
                <h3 class="text-lg sm:text-xl font-black tracking-tight text-white">Alur Persetujuan Bertahap Deviasi Material</h3>
                <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                    Setiap selisih kuantiti fisik material wajib melalui 2 pintu validasi independen: <strong class="text-white">Pengawas Lapangan</strong> (validasi fisik Surat Jalan / DO) dan <strong class="text-white">Purchasing</strong> (validasi finansial Faktur / Invoice).
                </p>
            </div>

            <!-- Stepper Diagram with Solid High Contrast Backgrounds -->
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs font-bold flex-wrap sm:flex-nowrap">
                <div class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 sm:py-3 rounded-xl bg-slate-800 border border-slate-700 text-center shadow-sm">
                    <span class="block text-[9px] sm:text-[10px] text-blue-400 uppercase font-extrabold">Tahap 1</span>
                    <span class="text-white font-black text-[11px] sm:text-xs">Fisik DO Lapangan</span>
                </div>
                <span class="text-slate-400 text-base sm:text-xl font-bold">&rarr;</span>
                <div class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 sm:py-3 rounded-xl bg-slate-800 border border-slate-700 text-center shadow-sm">
                    <span class="block text-[9px] sm:text-[10px] text-purple-400 uppercase font-extrabold">Tahap 2</span>
                    <span class="text-white font-black text-[11px] sm:text-xs">Faktur & Pajak</span>
                </div>
                <span class="text-slate-400 text-base sm:text-xl font-bold hidden sm:inline">&rarr;</span>
                <div class="w-full sm:w-auto px-3 sm:px-4 py-2 sm:py-3 rounded-xl bg-emerald-950/80 border border-emerald-500/50 text-center shadow-sm">
                    <span class="block text-[9px] sm:text-[10px] text-emerald-400 uppercase font-extrabold">Selesai</span>
                    <span class="text-emerald-300 font-black text-[11px] sm:text-xs">Fully Validated</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. DATAGRID: MONITORING & STATUS VALIDASI PER MATERIAL -->
    <div class="card-clean overflow-hidden shadow-xs border border-slate-200">
        <!-- Header Bar with Title, Search, and Live Filter Pills -->
        <div class="p-4 sm:p-5 border-b border-slate-100 space-y-3 sm:space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Monitoring & Rekonsiliasi Approval Material</h3>
                    <p class="text-xs text-slate-500">Daftar material terperinci dengan komparasi kuantiti dan status approval 2 pintu</p>
                </div>

                <!-- Live Search Box -->
                <div class="relative w-full md:w-auto">
                    <input type="text" x-model="search" placeholder="Cari nama atau kode material..." 
                           class="w-full md:w-64 text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Live Filter Tabs -->
            <div class="flex items-center gap-2 pt-1 border-t border-slate-100 overflow-x-auto tab-scroll-container pb-1">
                <button type="button" @click="filterTab = 'all'" 
                        :class="filterTab === 'all' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold'"
                        class="whitespace-nowrap px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5 flex-shrink-0">
                    Semua Material
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="filterTab === 'all' ? 'bg-slate-700 text-slate-100' : 'bg-slate-200 text-slate-600'">{{ $realizations->count() }}</span>
                </button>

                <button type="button" @click="filterTab = 'over'" 
                        :class="filterTab === 'over' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-semibold'"
                        class="whitespace-nowrap px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5 flex-shrink-0">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Melebihi RAB (Over)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="filterTab === 'over' ? 'bg-rose-800 text-white' : 'bg-rose-200 text-rose-900'">{{ $overCount }}</span>
                </button>

                <button type="button" @click="filterTab = 'validated'" 
                        :class="filterTab === 'validated' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold'"
                        class="whitespace-nowrap px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5 flex-shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Fully Validated
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="filterTab === 'validated' ? 'bg-emerald-800 text-white' : 'bg-emerald-200 text-emerald-900'">{{ $validatedCount }}</span>
                </button>

                <button type="button" @click="filterTab = 'empty'" 
                        :class="filterTab === 'empty' ? 'bg-slate-700 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold'"
                        class="whitespace-nowrap px-3 py-1.5 text-xs rounded-xl transition-all flex items-center gap-1.5 flex-shrink-0">
                    Menunggu Pengiriman DO
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="filterTab === 'empty' ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-600'">{{ $emptyCount }}</span>
                </button>
            </div>
        </div>

        <!-- The Modern Datagrid Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <table class="w-full text-left text-xs table-clean min-w-[1040px]">
                <thead class="bg-slate-50/90 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px] whitespace-nowrap">
                    <tr>
                        <th class="py-3.5 px-6 w-72 whitespace-nowrap">Material & Spesifikasi</th>
                        <th class="py-3.5 px-5 w-72 whitespace-nowrap">Analisis Kuantitas (RAB vs DO)</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Status Fisik</th>
                        <th class="py-3.5 px-6 text-center whitespace-nowrap">Alur Persetujuan Ganda (Pengawas ➔ Purchasing)</th>
                        <th class="py-3.5 px-5 text-center whitespace-nowrap">Status Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($realizations as $rel)
                        @php
                            $validation = $rel->varianceValidations->first();
                            $hasValidation = $validation !== null;
                            $isFullyValidated = $validation && $validation->status === 'fully_validated';
                            $actual = (float) $rel->actual_qty;
                            $planned = (float) $rel->planned_qty;
                            $pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
                            $isOver = $rel->status === 'kelebihan' || $actual > $planned;
                            $unit = $rel->material->defaultUnit?->code ?? '-';
                        @endphp
                        <tr x-show="filterRow({ name: '{{ addslashes($rel->material->name) }}', code: '{{ $rel->material->code }}', category: '{{ $rel->material->category }}', actual: {{ $actual }}, isOver: {{ $isOver ? 'true' : 'false' }}, isFullyValidated: {{ $isFullyValidated ? 'true' : 'false' }} })"
                            class="{{ $isOver ? 'border-l-4 border-l-rose-500 bg-rose-50/20' : '' }} transition-colors hover:bg-slate-50/80">
                            
                            <!-- 1. MATERIAL & IDENTIFIKASI -->
                            <td class="py-4 px-6 align-middle">
                                <div class="text-base font-black text-slate-950 tracking-tight leading-snug">
                                    {{ $rel->material->name }}
                                </div>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $rel->material->code }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold text-slate-500 bg-slate-50 border border-slate-200 uppercase">
                                        {{ str_replace('_', ' ', $rel->material->category) }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400">
                                        Satuan: <strong class="text-slate-600">{{ $unit }}</strong>
                                    </span>
                                </div>
                            </td>

                            <!-- 2. ANALISIS KUANTITAS (KOMPARASI LENGKAP & PROGRESS) -->
                            <td class="py-4 px-5 align-middle">
                                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/80 space-y-2">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-600">Realisasi Fisik:</span>
                                        <span class="font-black font-mono {{ $isOver ? 'text-rose-600' : ($actual == 0 ? 'text-slate-400' : 'text-blue-700') }}">
                                            {{ number_format($actual, 2, ',', '.') }} / {{ number_format($planned, 2, ',', '.') }} {{ $unit }}
                                            <span class="ml-1 text-[10px] px-1 py-0.2 rounded font-extrabold {{ $isOver ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700' }}">{{ $pct }}%</span>
                                        </span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden flex shadow-inner">
                                        @if($isOver)
                                            <div class="bg-blue-600 h-full" style="width: 80%"></div>
                                            <div class="bg-rose-500 h-full animate-pulse" style="width: 20%"></div>
                                        @elseif($pct > 0)
                                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-full" style="width: {{ min($pct, 100) }}%"></div>
                                        @endif
                                    </div>

                                    <!-- Selisih Fisik Badge -->
                                    <div class="flex items-center justify-between text-[11px] pt-0.5 border-t border-slate-200/60">
                                        <span class="text-slate-400 font-medium">Deviasi Fisik:</span>
                                        @if($isOver)
                                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 font-extrabold text-[11px]">
                                                +{{ number_format($rel->variance_qty, 2, ',', '.') }} {{ $unit }} (+{{ number_format($rel->variance_pct, 1, ',', '.') }}%)
                                            </span>
                                        @elseif($actual == 0)
                                            <span class="text-slate-400 font-mono font-semibold">-{{ number_format($planned, 2, ',', '.') }} {{ $unit }}</span>
                                        @else
                                            <span class="text-amber-700 font-mono font-bold">{{ number_format($rel->variance_qty, 2, ',', '.') }} {{ $unit }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 3. STATUS FISIK LAPANGAN -->
                            <td class="py-4 px-4 text-center align-middle whitespace-nowrap">
                                @if($isOver)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                                        <span>Melebihi RAB</span>
                                    </span>
                                @elseif($actual == 0)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                                        <span>Menunggu DO</span>
                                    </span>
                                @elseif($actual < $planned)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                                        <span>Masuk Sebagian</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs whitespace-nowrap">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span>Sesuai RAB</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 4. ALUR PERSETUJUAN GANDA (CONNECTED 2-STAGE GATEKEEPERS) -->
                            <td class="py-4 px-6 align-middle">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Gate 1: Pengawas Lapangan -->
                                    <div class="p-2.5 rounded-xl border text-left w-44 transition-all {{ $validation && $validation->pengawas_validated_at ? 'bg-emerald-50/70 border-emerald-200 shadow-2xs' : 'bg-white border-slate-200 shadow-2xs' }}">
                                        <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">
                                            <span>1. Fisik (DO)</span>
                                            @if($validation && $validation->pengawas_validated_at)
                                                <span class="text-emerald-700 font-extrabold flex items-center gap-0.5">✓ OK</span>
                                            @endif
                                        </div>
                                        @if($validation && $validation->pengawas_validated_at)
                                            <div class="text-xs font-bold text-slate-900 truncate">{{ $validation->pengawas?->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $validation->deliveryOrder?->do_number }} • {{ $validation->pengawas_validated_at->format('d/m/Y') }}</div>
                                        @else
                                            @if(!$validation)
                                                <form action="{{ route('variance.create') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="material_realization_id" value="{{ $rel->id }}">
                                                    <button type="submit" class="w-full py-1.5 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 transition-colors text-center block">
                                                        Siapkan Validasi
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" 
                                                        @click="activeValId = {{ $validation->id }}; valData = { matName: '{{ addslashes($rel->material->name) }}' }; showPengawasModal = true"
                                                        class="w-full py-1.5 text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition-all text-center block">
                                                    + Validasi DO Fisik
                                                </button>
                                            @endif
                                        @endif
                                    </div>

                                    <!-- Connecting Stepper Arrow -->
                                    <div class="text-slate-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    </div>

                                    <!-- Gate 2: Purchasing Officer -->
                                    <div class="p-2.5 rounded-xl border text-left w-44 transition-all {{ $validation && $validation->purchasing_validated_at ? 'bg-emerald-50/70 border-emerald-200 shadow-2xs' : 'bg-white border-slate-200 shadow-2xs' }}">
                                        <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">
                                            <span>2. Tagihan (Inv)</span>
                                            @if($validation && $validation->purchasing_validated_at)
                                                <span class="text-emerald-700 font-extrabold flex items-center gap-0.5">✓ OK</span>
                                            @endif
                                        </div>
                                        @if($validation && $validation->purchasing_validated_at)
                                            <div class="text-xs font-bold text-slate-900 truncate">{{ $validation->purchasing?->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $validation->invoice?->invoice_number }} • {{ $validation->purchasing_validated_at->format('d/m/Y') }}</div>
                                        @else
                                            @if($validation)
                                                <button type="button" 
                                                        @click="activeValId = {{ $validation->id }}; valData = { matName: '{{ addslashes($rel->material->name) }}' }; showPurchasingModal = true"
                                                        class="w-full py-1.5 text-[11px] font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-sm transition-all text-center block">
                                                    + Validasi Invoice
                                                </button>
                                            @else
                                                <span class="text-[10px] text-slate-400 block text-center py-1 font-medium">Menunggu Tahap 1</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 5. STATUS AKHIR (OVERALL VALIDATION STATE) -->
                            <td class="py-4 px-5 text-center align-middle">
                                @if($isFullyValidated)
                                    <div class="inline-flex flex-col items-center gap-0.5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-black shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            Fully Validated
                                        </span>
                                        <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider">Dual Approval Sah</span>
                                    </div>
                                @elseif($validation)
                                    <div class="inline-flex flex-col items-center gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold">
                                            ⏳ Pending Review
                                        </span>
                                        <span class="text-[9px] text-amber-700 font-medium">Belum Lengkap 2 Pihak</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 text-xs font-medium">
                                        Belum Diajukan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 text-xs">Belum ada data realisasi material di proyek ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: VALIDASI PENGAWAS (BUKTI DO FISIK) -->
    <div x-show="showPengawasModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPengawasModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form :action="'{{ url('/variance') }}/' + activeValId + '/pengawas'" method="POST">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-amber-100 text-amber-800 text-[10px] mb-1">Verifikasi Fisik Lapangan</span>
                            <h3 class="text-sm font-bold text-slate-900">Validasi Pengawas Lapangan</h3>
                        </div>
                        <button type="button" @click="showPengawasModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Material:</label>
                            <div class="p-2.5 bg-slate-50 rounded-lg font-bold text-slate-900" x-text="valData.matName"></div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Bukti Surat Jalan (DO) Fisik yang Sesuai <span class="text-rose-500">*</span></label>
                            <select name="delivery_order_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                <option value="">-- Pilih DO Tervalidasi --</option>
                                @foreach($availableDos as $ado)
                                    <option value="{{ $ado->id }}">
                                        {{ $ado->do_number }} • {{ $ado->supplier->name }} ({{ \Carbon\Carbon::parse($ado->do_date)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan Pemeriksaan Lapangan</label>
                            <textarea name="notes" rows="3" required placeholder="Jelaskan kondisi fisik barang, penimbangan jembatan timbang, atau alasan selisih..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showPengawasModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Persetujuan Pengawas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: VALIDASI PURCHASING (BUKTI INVOICE & HARGA) -->
    <div x-show="showPurchasingModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPurchasingModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form :action="'{{ url('/variance') }}/' + activeValId + '/purchasing'" method="POST">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-purple-100 text-purple-800 text-[10px] mb-1">Verifikasi Finansial & Faktur</span>
                            <h3 class="text-sm font-bold text-slate-900">Validasi Purchasing Officer</h3>
                        </div>
                        <button type="button" @click="showPurchasingModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Material:</label>
                            <div class="p-2.5 bg-slate-50 rounded-lg font-bold text-slate-900" x-text="valData.matName"></div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Bukti Faktur Tagihan (Invoice) <span class="text-rose-500">*</span></label>
                            <select name="invoice_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                <option value="">-- Pilih Faktur Terkait --</option>
                                @foreach($availableInvoices as $ainv)
                                    <option value="{{ $ainv->id }}">
                                        {{ $ainv->invoice_number }} • Rp {{ number_format($ainv->amount, 0, ',', '.') }} ({{ $ainv->supplier->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan Purchasing & Pajak</label>
                            <textarea name="notes" rows="3" required placeholder="Konfirmasi kesesuaian harga satuan, faktur pajak, dan approval pembayaran..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showPurchasingModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-sm">Simpan Persetujuan Purchasing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
