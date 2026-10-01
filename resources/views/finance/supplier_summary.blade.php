@extends('layouts.app')

@section('title', 'Rekap Pembelian per Toko / Supplier (Lintas Proyek)')
@section('page_title', 'Rekap Pembelian Toko Lintas Proyek')
@section('page_subtitle', 'Agregasi terpadu nilai pesanan, penerimaan barang, faktur tagihan, uang muka, dan sisa hutang per supplier di semua proyek')

@section('content')
<div class="space-y-6" x-data="{
    expandedSuppliers: {}
}">

    <!-- Sub-Navigasi Modul 6 Tab & Quick Action -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'summary'])

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl flex items-center gap-1.5 transition-colors shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Rekap</span>
            </button>
        </div>
    </div>

    <!-- FILTER BAR LINTAS PROYEK -->
    <div class="card-clean p-4">
        <form action="{{ route('finance.supplier-summary.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <!-- Search -->
            <div class="sm:col-span-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Cari Toko / Supplier</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama toko, kontak person, no telepon..." class="w-full text-xs border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Filter Proyek -->
            <div class="sm:col-span-4">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Cakupan Proyek</label>
                <select name="project_id" class="select-clean w-full text-xs py-2.5 pl-3">
                    <option value="all" {{ empty($selectedProjectId) || $selectedProjectId === 'all' ? 'selected' : '' }}>
                        -- Semua Proyek (Lintas Proyek) --
                    </option>
                    @foreach($allProjects as $p)
                        <option value="{{ $p->id }}" {{ $selectedProjectId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->budget_year }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Hutang -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Pelunasan</label>
                <select name="status" class="select-clean w-full text-xs py-2.5 pl-3">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ $debtStatus === 'unpaid' ? 'selected' : '' }}>Ada Sisa Hutang</option>
                    <option value="paid" {{ $debtStatus === 'paid' ? 'selected' : '' }}>Lunas Sepenuhnya</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="sm:col-span-1 flex gap-1">
                <button type="submit" class="w-full justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-sm transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- STATS CARDS LINTAS PROYEK -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- 1. Toko Terlibat -->
        <div class="card-clean p-4 border-l-4 border-l-slate-600">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Toko / Supplier</span>
            <p class="text-xl font-black text-slate-900 mt-1 font-mono">
                {{ $globalStats['total_suppliers_active'] }} <span class="text-xs font-semibold text-slate-500">Rekanan</span>
            </p>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                {{ $selectedProjectId && $selectedProjectId !== 'all' ? 'Pada proyek terpilih' : 'Lintas seluruh proyek' }}
            </div>
        </div>

        <!-- 2. Grand Total PO -->
        <div class="card-clean p-4 border-l-4 border-l-blue-600">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Nilai Pesanan (PO)</span>
            <p class="text-lg font-black text-blue-700 mt-1 font-mono whitespace-nowrap">
                Rp {{ number_format($globalStats['grand_po_amount'], 0, ',', '.') }}
            </p>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Komitmen pesanan resmi
            </div>
        </div>

        <!-- 3. Grand Total Faktur -->
        <div class="card-clean p-4 border-l-4 border-l-indigo-600">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Tagihan Masuk (Net)</span>
            <p class="text-lg font-black text-indigo-700 mt-1 font-mono whitespace-nowrap">
                Rp {{ number_format($globalStats['grand_invoice_amount'], 0, ',', '.') }}
            </p>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Faktur resmi setelah potongan DP
            </div>
        </div>

        <!-- 4. Grand Total Bayar -->
        <div class="card-clean p-4 border-l-4 border-l-emerald-600">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Telah Terbayar</span>
            <p class="text-lg font-black text-emerald-700 mt-1 font-mono whitespace-nowrap">
                Rp {{ number_format($globalStats['grand_paid_amount'], 0, ',', '.') }}
            </p>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Realisasi kas/bank keluar
            </div>
        </div>

        <!-- 5. Grand Sisa Hutang (Outstanding AP) -->
        <div class="card-clean p-4 border-l-4 {{ $globalStats['grand_outstanding'] > 0 ? 'border-l-rose-500 bg-rose-50/20' : 'border-l-emerald-500' }}">
            <span class="text-[11px] font-bold uppercase tracking-wider block {{ $globalStats['grand_outstanding'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">Sisa Hutang ke Toko</span>
            <p class="text-lg font-black {{ $globalStats['grand_outstanding'] > 0 ? 'text-rose-600' : 'text-emerald-700' }} mt-1 font-mono whitespace-nowrap">
                Rp {{ number_format($globalStats['grand_outstanding'], 0, ',', '.') }}
            </p>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Hutang dagang belum lunas
            </div>
        </div>
    </div>

    <!-- MAIN PROPORTIONAL SUMMARY TABLE -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Pembelian &amp; Pelunasan per Toko</h3>
                <p class="text-xs text-slate-500">Ringkasan transaksi kumulatif setiap toko beserta rincian breakdown proyek</p>
            </div>
            <div class="text-xs text-slate-400">
                Menampilkan <span class="font-bold text-slate-700">{{ $supplierSummaries->count() }}</span> data toko rekanan
            </div>
        </div>

        @if($supplierSummaries->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Tidak Ada Data Pembelian Toko</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Tidak ditemukan transaksi pembelian atau rekanan yang sesuai dengan filter pencarian.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[1300px]">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-left w-[240px]">Toko / Supplier</th>
                            <th class="py-3.5 px-3 text-center w-[90px]">Proyek</th>
                            <th class="py-3.5 px-4 text-right w-[145px]">Nilai PO (Rp)</th>
                            <th class="py-3.5 px-3 text-center w-[80px]">DO</th>
                            <th class="py-3.5 px-4 text-right w-[135px]">Uang Muka (DP)</th>
                            <th class="py-3.5 px-4 text-right w-[150px]">Faktur Masuk (Net)</th>
                            <th class="py-3.5 px-4 text-right w-[135px]">Telah Dibayar</th>
                            <th class="py-3.5 px-4 text-right w-[145px]">Sisa Hutang</th>
                            <th class="py-3.5 px-3 text-center w-[120px]">Status</th>
                            <th class="py-3.5 px-4 text-center w-[110px]">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($supplierSummaries as $item)
                            @php
                                $s = $item['supplier'];
                                $suppId = $s->id;
                            @endphp
                            <tr class="hover:bg-slate-50/75 transition-colors {{ $item['has_debt'] ? 'bg-amber-50/15' : '' }}">
                                <!-- 1. Toko & Kontak -->
                                <td class="py-3.5 px-4 align-middle">
                                    <div class="font-extrabold text-slate-900 text-sm whitespace-nowrap">
                                        {{ $s->name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                                        @if($s->contact_person)
                                            <span>Kontak: <strong class="text-slate-700">{{ $s->contact_person }}</strong></span>
                                        @endif
                                        @if($s->phone)
                                            <span class="text-slate-300">•</span>
                                            <span class="font-mono text-slate-500">{{ $s->phone }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 2. Proyek -->
                                <td class="py-3.5 px-3 text-center align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $item['projects_count'] }} Proyek
                                    </span>
                                </td>

                                <!-- 3. Nilai PO (Rp) -->
                                <td class="py-3.5 px-4 text-right align-middle whitespace-nowrap font-mono">
                                    <div class="font-bold text-slate-900 text-xs">
                                        Rp {{ number_format($item['total_po_amount'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-sans mt-0.5">
                                        {{ $item['po_count'] }} PO
                                    </div>
                                </td>

                                <!-- 4. DO Diterima -->
                                <td class="py-3.5 px-3 text-center align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold {{ $item['do_count'] > 0 ? 'bg-slate-100 text-slate-800' : 'text-slate-400' }}">
                                        {{ $item['do_count'] }} DO
                                    </span>
                                </td>

                                <!-- 5. Uang Muka (DP) -->
                                <td class="py-3.5 px-4 text-right align-middle whitespace-nowrap font-mono text-xs {{ $item['total_dp_amount'] > 0 ? 'font-bold text-purple-700' : 'text-slate-400' }}">
                                    Rp {{ number_format($item['total_dp_amount'], 0, ',', '.') }}
                                </td>

                                <!-- 6. Faktur Masuk (Net) -->
                                <td class="py-3.5 px-4 text-right align-middle whitespace-nowrap font-mono">
                                    <div class="font-bold text-slate-900 text-xs">
                                        Rp {{ number_format($item['total_invoice_net'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-sans mt-0.5">
                                        {{ $item['invoice_count'] }} Faktur
                                    </div>
                                </td>

                                <!-- 7. Telah Dibayar -->
                                <td class="py-3.5 px-4 text-right align-middle whitespace-nowrap font-mono text-xs {{ $item['total_paid_amount'] > 0 ? 'font-bold text-emerald-700' : 'text-slate-400' }}">
                                    <div>Rp {{ number_format($item['total_paid_amount'], 0, ',', '.') }}</div>
                                    @if($item['total_invoice_net'] > 0 && $item['total_paid_amount'] > 0)
                                        <div class="text-[10px] text-emerald-600 font-sans mt-0.5">
                                            {{ round(($item['total_paid_amount'] / $item['total_invoice_net']) * 100) }}% Lunas
                                        </div>
                                    @endif
                                </td>

                                <!-- 8. Sisa Hutang -->
                                <td class="py-3.5 px-4 text-right align-middle whitespace-nowrap font-mono text-xs font-black {{ $item['has_debt'] ? 'text-rose-600' : 'text-slate-400' }}">
                                    Rp {{ number_format($item['outstanding_balance'], 0, ',', '.') }}
                                </td>

                                <!-- 9. Status -->
                                <td class="py-3.5 px-3 text-center align-middle whitespace-nowrap">
                                    @if($item['has_debt'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            Ada Hutang
                                        </span>
                                    @elseif($item['is_settled'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Lunas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-full bg-slate-100 text-slate-500">
                                            Belum Ada Tagihan
                                        </span>
                                    @endif
                                </td>

                                <!-- 10. Action Button -->
                                <td class="py-3.5 px-4 text-center align-middle whitespace-nowrap">
                                    <button type="button" 
                                            @click="expandedSuppliers[{{ $suppId }}] = !expandedSuppliers[{{ $suppId }}]" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-xl border transition-all shadow-sm"
                                            :class="expandedSuppliers[{{ $suppId }}] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-blue-50 hover:text-blue-700'">
                                        <span x-text="expandedSuppliers[{{ $suppId }}] ? 'Tutup' : 'Rincian Proyek'"></span>
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': expandedSuppliers[{{ $suppId }}] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </td>
                            </tr>

                            <!-- ROW DRILL-DOWN RINCIAN PER PROYEK -->
                            <tr x-show="expandedSuppliers[{{ $suppId }}]" x-cloak class="bg-slate-50/90 border-b border-slate-200">
                                <td colspan="10" class="p-4 sm:p-5">
                                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm space-y-3">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                </div>
                                                <h5 class="text-xs font-bold text-slate-800">
                                                    Breakdown Transaksi Toko <span class="text-blue-700 font-extrabold">{{ $s->name }}</span> per Proyek Individual
                                                </h5>
                                            </div>
                                            <span class="text-[11px] text-slate-400 font-medium">Total {{ count($item['project_breakdowns']) }} Proyek Terlibat</span>
                                        </div>

                                        @if(empty($item['project_breakdowns']))
                                            <p class="text-xs text-slate-400 italic py-2">Belum ada riwayat transaksi pada proyek manapun.</p>
                                        @else
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs min-w-[900px]">
                                                    <thead>
                                                        <tr class="text-[10px] font-bold text-slate-400 uppercase border-b border-slate-100 bg-slate-50/50">
                                                            <th class="py-2.5 px-3 text-left w-[240px]">Nama Proyek</th>
                                                            <th class="py-2.5 px-3 text-right w-[140px]">Pesanan PO</th>
                                                            <th class="py-2.5 px-3 text-center w-[80px]">DO Masuk</th>
                                                            <th class="py-2.5 px-3 text-right w-[120px]">Uang Muka (DP)</th>
                                                            <th class="py-2.5 px-3 text-right w-[140px]">Tagihan Faktur (Net)</th>
                                                            <th class="py-2.5 px-3 text-right w-[130px]">Telah Terbayar</th>
                                                            <th class="py-2.5 px-3 text-right w-[140px]">Sisa Hutang</th>
                                                            <th class="py-2.5 px-3 text-center w-[100px]">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach($item['project_breakdowns'] as $pb)
                                                            <tr class="hover:bg-slate-50/60">
                                                                <td class="py-2.5 px-3 align-middle">
                                                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $pb['project_name'] }}</div>
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap align-middle">
                                                                    <span class="font-bold text-slate-900">Rp {{ number_format($pb['po_total'], 0, ',', '.') }}</span>
                                                                    <span class="text-[10px] text-slate-400 block font-normal font-sans">({{ $pb['po_count'] }} PO)</span>
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center font-mono font-semibold text-slate-600 whitespace-nowrap align-middle">
                                                                    {{ $pb['do_count'] }} DO
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-mono text-purple-700 whitespace-nowrap align-middle">
                                                                    Rp {{ number_format($pb['dp_total'], 0, ',', '.') }}
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap align-middle">
                                                                    <span class="font-bold text-slate-900">Rp {{ number_format($pb['invoice_total'], 0, ',', '.') }}</span>
                                                                    <span class="text-[10px] text-slate-400 block font-normal font-sans">({{ $pb['invoice_count'] }} Faktur)</span>
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700 whitespace-nowrap align-middle">
                                                                    Rp {{ number_format($pb['paid_total'], 0, ',', '.') }}
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-mono font-black whitespace-nowrap align-middle {{ $pb['outstanding'] > 0 ? 'text-rose-600' : 'text-slate-500' }}">
                                                                    Rp {{ number_format($pb['outstanding'], 0, ',', '.') }}
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center whitespace-nowrap align-middle">
                                                                    @if($pb['outstanding'] > 0)
                                                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                                                            Belum Lunas
                                                                        </span>
                                                                    @elseif($pb['invoice_count'] > 0)
                                                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                            Lunas
                                                                        </span>
                                                                    @else
                                                                        <span class="px-2 py-0.5 text-[9px] text-slate-400 bg-slate-100 rounded">
                                                                            Nihil
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-100/90 font-bold border-t-2 border-slate-300 text-xs">
                        <tr>
                            <td class="py-3.5 px-4 text-slate-800 whitespace-nowrap">
                                TOTAL KESELURUHAN ({{ $supplierSummaries->count() }} Toko)
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-400 font-mono">
                                -
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($globalStats['grand_po_amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $supplierSummaries->sum('do_count') }} DO
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-purple-700 whitespace-nowrap">
                                Rp {{ number_format($globalStats['grand_dp_amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($globalStats['grand_invoice_amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-emerald-700 whitespace-nowrap">
                                Rp {{ number_format($globalStats['grand_paid_amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600 whitespace-nowrap">
                                Rp {{ number_format($globalStats['grand_outstanding'], 0, ',', '.') }}
                            </td>
                            <td colspan="2" class="py-3.5 px-4 text-center text-slate-400 font-normal whitespace-nowrap">
                                Rekap Terpadu
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
