@extends('layouts.app')

@section('title', 'Detail & Riwayat: ' . $realization->material->name . ' — K-RAB')
@section('page_subtitle', 'Analisis Komprehensif Realisasi Fisik, Riwayat Penerimaan Pembelian (DO), Faktur & Alokasi RAB')

@section('content')
<div class="space-y-6">

    @php
        $actual = (float) $realization->actual_qty;
        $planned = (float) $realization->planned_qty;
        $pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
        $unit = $realization->material->defaultUnit?->code ?? '-';

        if ($realization->status === 'kelebihan' || $actual > $planned) {
            $evalType = 'over';
            $badgeCls = 'bg-rose-100 text-rose-800 border-rose-300';
            $badgeLabel = 'Melebihi Kuota RAB (+' . number_format($realization->variance_pct, 1, ',', '.') . '%)';
            $alertBg = 'bg-gradient-to-r from-rose-50 via-rose-50/70 to-amber-50/40 border-rose-200';
        } elseif ($actual == 0) {
            $evalType = 'pending';
            $badgeCls = 'bg-slate-100 text-slate-700 border-slate-300';
            $badgeLabel = 'Belum Ada Pengiriman (0%)';
            $alertBg = 'bg-slate-50 border-slate-200';
        } elseif ($actual < $planned) {
            $evalType = 'partial';
            $badgeCls = 'bg-blue-100 text-blue-800 border-blue-300';
            $badgeLabel = 'Sebagian Masuk (' . $pct . '%)';
            $alertBg = 'bg-blue-50/50 border-blue-200';
        } else {
            $evalType = 'normal';
            $badgeCls = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $badgeLabel = 'Selesai Sesuai RAB (100%)';
            $alertBg = 'bg-emerald-50/50 border-emerald-200';
        }
    @endphp

    <!-- 1. BREADCRUMB & BACK ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-blue-600 transition-colors bg-white px-3.5 py-2 rounded-xl border border-slate-200 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Dashboard Monitoring
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('variance.index') }}" 
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all flex-1 sm:flex-initial">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Validasi Ganda (DO+Inv)
            </a>
            <a href="{{ route('procurement.index') }}" 
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl shadow-xs transition-all flex-1 sm:flex-initial">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Input Penerimaan / Pesanan
            </a>
        </div>
    </div>

    <!-- 2. HERO MATERIAL IDENTITY BANNER -->
    <div class="card-clean p-4 sm:p-6 lg:p-8 bg-gradient-to-r from-white via-white to-slate-50 relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6 relative z-10">
            <div>
                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-900 text-white rounded-lg shadow-xs">
                        {{ $realization->material->code }}
                    </span>
                    <span class="badge-clean bg-slate-100 text-slate-700 border border-slate-200 uppercase font-bold text-xs tracking-wider">
                        {{ str_replace('_', ' ', $realization->material->category) }}
                    </span>
                    <span class="badge-clean border text-xs font-bold {{ $badgeCls }}">
                        {{ $badgeLabel }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">Proyek: {{ $currentProject->name }}</span>
                </div>

                <!-- Material Name (Sangat Besar & Bold) -->
                <h1 class="text-2xl md:text-3xl lg:text-4xl font-black text-slate-950 tracking-tight leading-tight">
                    {{ $realization->material->name }}
                </h1>

                <p class="text-xs text-slate-500 mt-2 flex items-center gap-3">
                    <span>Satuan Standar: <strong class="text-slate-800 font-semibold">{{ $unit }}</strong></span>
                    <span>•</span>
                    <span>Harga Baseline Master: <strong class="text-slate-800 font-mono font-semibold">Rp {{ number_format($realization->material->standard_price, 0, ',', '.') }}/{{ $unit }}</strong></span>
                    <span>•</span>
                    <span>Terakhir Dihitung: <strong class="text-slate-700">{{ $realization->last_calculated_at ? $realization->last_calculated_at->diffForHumans() : 'Baru saja' }}</strong></span>
                </p>
            </div>

            <!-- Health Status Highlight Pill -->
            <div class="flex-shrink-0 text-right">
                <div class="inline-block p-4 rounded-2xl {{ $alertBg }} border">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-0.5">Persentase Realisasi Fisik</span>
                    <div class="flex items-baseline justify-end gap-1.5">
                        <span class="text-3xl font-black font-mono {{ $evalType === 'over' ? 'text-rose-600' : ($evalType === 'normal' ? 'text-emerald-600' : ($evalType === 'partial' ? 'text-blue-600' : 'text-slate-500')) }}">
                            {{ $pct }}%
                        </span>
                        <span class="text-xs font-bold text-slate-500">terpenuhi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Gauge Track -->
        <div class="mt-6 pt-6 border-t border-slate-100">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-bold text-slate-700">Kemajuan Penerimaan Fisik di Lapangan</span>
                <span class="text-slate-500">Target Kuota RAB: <strong class="font-mono text-slate-800">{{ format_qty($planned) }} {{ $unit }}</strong></span>
            </div>
            <div class="w-full bg-slate-200/80 rounded-full h-4 p-0.5 overflow-hidden flex relative shadow-inner">
                @if($evalType === 'over')
                    <div class="bg-blue-600 h-full rounded-l-full" style="width: 85%;"></div>
                    <div class="h-full rounded-r-full animate-pulse bg-rose-500" 
                         style="width: 15%; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(255,255,255,0.4) 5px, rgba(255,255,255,0.4) 10px);"></div>
                @elseif($pct > 0)
                    <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-full rounded-full transition-all duration-500" 
                         style="width: {{ min($pct, 100) }}%;"></div>
                @else
                    <div class="w-full h-full flex items-center justify-center text-[10px] font-bold text-slate-400">
                        0% Belum Ada Penerimaan Pembelian (DO)
                    </div>
                @endif
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mt-2">
                <span>Total Fisik Masuk: <strong class="font-mono font-bold text-slate-900">{{ format_qty($actual) }} {{ $unit }}</strong></span>
                <span>Selisih: <strong class="font-mono font-bold {{ $realization->variance_qty > 0 ? 'text-rose-600' : ($realization->variance_qty < 0 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $realization->variance_qty > 0 ? '+' : '' }}{{ format_qty($realization->variance_qty) }} {{ $unit }}</strong></span>
            </div>
        </div>
    </div>

    <!-- 3. KPI METRIC BREAKDOWN CARDS -->
    <!-- 3. KPI METRIC BREAKDOWN CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Kuota RAB Rencana -->
        <div class="card-clean p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">1. Rencana RAB</span>
            <div class="text-2xl font-black text-slate-900 font-mono">
                {{ format_qty($planned) }}
            </div>
            <span class="text-xs font-bold text-slate-500">{{ $unit }} BOM Target</span>
        </div>

        <!-- Total Dipesan (Pesanan Pembelian) -->
        <div class="card-clean p-4 border-amber-200/80 bg-gradient-to-b from-amber-50/20 to-white">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 block mb-1">2. Pesanan Pembelian (PO)</span>
            @php
                $totalPoQty = (float) $purchaseOrderItems->sum('qty_ordered');
            @endphp
            <div class="text-2xl font-black font-mono {{ $totalPoQty > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                {{ format_qty($totalPoQty) }}
            </div>
            <span class="text-xs font-bold text-slate-500">{{ $unit }} ({{ $purchaseOrderItems->count() }} PO)</span>
        </div>

        <!-- Realisasi Diterima (Penerimaan Pembelian) -->
        <div class="card-clean p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">3. Penerimaan Pembelian (DO)</span>
            <div class="text-2xl font-black font-mono {{ $actual > 0 ? 'text-blue-700' : 'text-slate-400' }}">
                {{ format_qty($actual) }}
            </div>
            <span class="text-xs font-bold text-slate-500">{{ $unit }} Fisik Lapangan</span>
        </div>

        <!-- Selisih Fisik -->
        <div class="card-clean p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">4. Selisih (Variance)</span>
            <div class="text-2xl font-black font-mono {{ $realization->variance_qty > 0 ? 'text-rose-600' : ($realization->variance_qty < 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                {{ $realization->variance_qty > 0 ? '+' : '' }}{{ format_qty($realization->variance_qty) }}
            </div>
            <span class="text-xs font-bold {{ $realization->variance_qty > 0 ? 'text-rose-600' : ($realization->variance_qty < 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                {{ $unit }} ({{ $realization->variance_pct > 0 ? '+' : '' }}{{ number_format($realization->variance_pct, 1, ',', '.') }}%)
            </span>
        </div>

        <!-- Total Anggaran BOM -->
        <div class="card-clean p-4 col-span-2 md:col-span-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">5. Anggaran RAB</span>
            @php
                $totalRabCost = (float) $rabAllocations->sum('total_price');
            @endphp
            <div class="text-xl font-black text-slate-900 font-mono truncate" title="Rp {{ number_format($totalRabCost > 0 ? $totalRabCost : ($planned * $realization->material->standard_price), 0, ',', '.') }}">
                Rp {{ number_format($totalRabCost > 0 ? $totalRabCost : ($planned * $realization->material->standard_price), 0, ',', '.') }}
            </div>
            <span class="text-xs font-semibold text-slate-500">Estimasi biaya material</span>
        </div>
    </div>

    <!-- 4. RIWAYAT PESANAN PEMBELIAN (PO) PURCHASING -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Riwayat Pesanan Pembelian (PO)</h3>
                    <p class="text-xs text-slate-500">Daftar pesanan resmi ke supplier rekanan beserta referensi uraian pekerjaan RAB</p>
                </div>
            </div>
            <span class="badge-clean bg-amber-100 text-amber-900 border border-amber-200 text-xs font-bold self-start sm:self-auto">{{ $purchaseOrderItems->count() }} Item PO</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse table-clean">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-5">Tanggal PO</th>
                        <th class="py-3 px-5">No. Pesanan Pembelian (PO)</th>
                        <th class="py-3 px-5">Supplier / Rekanan</th>
                        <th class="py-3 px-5">Uraian Pekerjaan RAB</th>
                        <th class="py-3 px-5 text-right">Kuantiti Dipesan</th>
                        <th class="py-3 px-5 text-right">Harga Satuan</th>
                        <th class="py-3 px-5 text-right">Subtotal Biaya</th>
                        <th class="py-3 px-5 text-center">Status PO</th>
                        <th class="py-3 px-5 text-center">Unduh PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchaseOrderItems as $poItem)
                        @php
                            $po = $poItem->purchaseOrder;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-5 font-semibold text-slate-700 text-xs whitespace-nowrap">
                                {{ $po?->po_date ? $po->po_date->format('d M Y') : '—' }}
                            </td>
                            <td class="py-3.5 px-5 font-mono font-bold text-xs text-blue-700 whitespace-nowrap">
                                {{ $po?->po_number ?? '—' }}
                            </td>
                            <td class="py-3.5 px-5 text-xs font-semibold text-slate-800">
                                {{ $po?->supplier->name ?? '—' }}
                            </td>
                            <td class="py-3.5 px-5 text-xs text-slate-700">
                                @if($poItem->rabItem)
                                    <span class="inline-flex items-center gap-1 font-semibold text-blue-800 bg-blue-50 px-2 py-0.5 rounded text-[11px] border border-blue-200">
                                        {{ $poItem->rabItem->rabNode?->name ?? 'RAB' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">— Umum / Non-RAB —</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-black text-xs text-slate-900 whitespace-nowrap">
                                {{ format_qty($poItem->qty_ordered) }} {{ $poItem->unit->code ?? $unit }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono text-xs text-slate-700 whitespace-nowrap">
                                Rp {{ number_format($poItem->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold text-xs text-slate-900 whitespace-nowrap">
                                Rp {{ number_format($poItem->subtotal, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[11px] capitalize">
                                    {{ $po?->status ?? 'Aktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @if($po)
                                    <a href="{{ route('procurement.po.pdf', $po->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors border border-rose-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>PDF</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                <span class="font-semibold text-slate-600 block">Belum Ada Riwayat Pesanan Pembelian (PO)</span>
                                <span class="text-xs text-slate-400 mt-1 block">Material ini belum pernah dicatat dalam dokumen Pesanan Pembelian.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. RIWAYAT PENERIMAAN FISIK PEMBELIAN (DELIVERY ORDERS) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Riwayat Penerimaan Fisik Pembelian (DO Lapangan)</h3>
                    <p class="text-xs text-slate-500">Daftar penerimaan fisik yang telah diterima dan diverifikasi oleh Pengawas Lapangan beserta referensi PO</p>
                </div>
            </div>
            <span class="badge-clean bg-blue-100 text-blue-800 text-xs font-bold self-start sm:self-auto">{{ $deliveryOrderItems->count() }} Pengiriman</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse table-clean">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-5">Tanggal Diterima</th>
                        <th class="py-3 px-5">No. Penerimaan Pembelian (DO)</th>
                        <th class="py-3 px-5">No. Pesanan Pembelian (PO) Ref</th>
                        <th class="py-3 px-5">Supplier / Rekanan</th>
                        <th class="py-3 px-5 text-right">Kuantiti Fisik Diterima</th>
                        <th class="py-3 px-5">Penerima Lapangan</th>
                        <th class="py-3 px-5 text-center">Status DO</th>
                        <th class="py-3 px-5">Catatan / Jembatan Timbang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($deliveryOrderItems as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-5 font-semibold text-slate-700 text-xs whitespace-nowrap">
                                {{ $item->deliveryOrder->do_date ? $item->deliveryOrder->do_date->format('d M Y') : '—' }}
                            </td>
                            <td class="py-3.5 px-5 font-mono font-bold text-xs text-blue-700 whitespace-nowrap">
                                {{ $item->deliveryOrder->do_number }}
                            </td>
                            <td class="py-3.5 px-5 font-mono text-xs whitespace-nowrap">
                                @if($item->purchaseOrder)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                        📑 {{ $item->purchaseOrder->po_number }}
                                    </span>
                                @elseif(!empty($item->purchase_order_id))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                        📑 PO #{{ $item->purchase_order_id }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-emerald-800 bg-emerald-50 border border-emerald-200">
                                        💵 Pembelian Tunai (Non-PO)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-xs font-semibold text-slate-800">
                                {{ $item->deliveryOrder->supplier->name ?? '—' }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-black text-xs text-slate-900 whitespace-nowrap">
                                {{ format_qty($item->qty_received) }} {{ $item->unit->code ?? $unit }}
                            </td>
                            <td class="py-3.5 px-5 text-xs text-slate-600">
                                {{ $item->deliveryOrder->receiver->name ?? 'Pengawas Lapangan' }}
                            </td>
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[11px]">
                                    ✓ Tervalidasi Fisik
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-xs text-slate-500 max-w-xs truncate">
                                {{ $item->deliveryOrder->notes ?: 'Telah diuji dan diterima sesuai spesifikasi' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                <span class="font-semibold text-slate-600 block">Belum Ada Riwayat Penerimaan Pembelian (DO)</span>
                                <span class="text-xs text-slate-400 mt-1 block">Material ini belum memiliki penerimaan fisik yang tercatat di sistem lapangan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. RIWAYAT TAGIHAN FAKTUR (INVOICES) & DUAL APPROVAL AUDIT -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Riwayat Faktur Invoice -->
        <div class="card-clean overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Riwayat Faktur & Invoice Purchasing</h3>
                        <p class="text-[11px] text-slate-500">Penagihan finansial dari supplier rekanan</p>
                    </div>
                </div>
                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[11px] font-bold">{{ $invoices->count() }} Invoice</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse table-clean">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-4">Tanggal</th>
                            <th class="py-2.5 px-4">No. Invoice</th>
                            <th class="py-2.5 px-4">No. PO Ref</th>
                            <th class="py-2.5 px-4">Supplier</th>
                            <th class="py-2.5 px-4 text-right">Nominal Tagihan</th>
                            <th class="py-2.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($invoices as $inv)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—' }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ $inv->invoice_number }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                    @if($inv->purchaseOrder)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                            {{ $inv->purchaseOrder->po_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[10px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ $inv->supplier->name ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format($inv->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">
                                        Tervalidasi
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <span class="text-xs">Belum ada invoice terkait material ini.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Protokol Validasi Ganda Audit -->
        <div class="card-clean overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Protokol Validasi Ganda (Dual Approval)</h3>
                        <p class="text-[11px] text-slate-500">Pintu 1: Pengawas Lapangan • Pintu 2: Purchasing</p>
                    </div>
                </div>
                <a href="{{ route('variance.index') }}" class="text-[11px] font-bold text-blue-600 hover:underline">Buka Konsol &rarr;</a>
            </div>

            <div class="p-3 sm:p-5 space-y-4">
                @if($validations->isNotEmpty())
                    @foreach($validations as $val)
                        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-slate-700">Tiket Validasi #VAL-{{ str_pad($val->id, 4, '0', STR_PAD_LEFT) }}</span>
                                <span class="badge-clean {{ $val->status === 'fully_validated' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} text-[10px] font-bold">
                                    {{ $val->status === 'fully_validated' ? 'Fully Validated' : 'Menunggu Validasi' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 pt-2 border-t border-slate-200/60 text-xs">
                                <!-- Gate 1 -->
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Pintu 1: Pengawas Lapangan</span>
                                    <div class="mt-1 font-semibold text-slate-800">
                                        {{ $val->pengawasUser->name ?? 'Belum Ditandatangani' }}
                                    </div>
                                    <span class="text-[10px] text-slate-500 block mt-0.5">
                                        {{ $val->pengawas_validated_at ? $val->pengawas_validated_at->format('d/m/Y H:i') : 'Menunggu verifikasi fisik' }}
                                    </span>
                                </div>

                                <!-- Gate 2 -->
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Pintu 2: Purchasing</span>
                                    <div class="mt-1 font-semibold text-slate-800">
                                        {{ $val->purchasingUser->name ?? 'Belum Ditandatangani' }}
                                    </div>
                                    <span class="text-[10px] text-slate-500 block mt-0.5">
                                        {{ $val->purchasing_validated_at ? $val->purchasing_validated_at->format('d/m/Y H:i') : 'Menunggu verifikasi faktur' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-6 text-center text-slate-400">
                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <p class="text-xs font-semibold text-slate-600">Material dalam batas toleransi normal</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada anomali atau deviasi yang memerlukan intervensi audit dual approval saat ini.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- 6. RINCIAN ALOKASI POHON RAB PROYEK (BOM TREE ALLOCATION) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-bold flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Alokasi Material pada Pohon Struktur RAB (BOM Level 5)</h3>
                    <p class="text-xs text-slate-500">Rincian seluruh item pekerjaan proyek yang menggunakan material ini beserta volume dan biayanya</p>
                </div>
            </div>
            <a href="{{ route('rab.builder') }}" class="text-xs font-bold text-blue-600 hover:underline">Buka RAB Builder &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse table-clean">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-5">Kategori & Sub-Kategori</th>
                        <th class="py-3 px-5">Item Pekerjaan RAB</th>
                        <th class="py-3 px-5 text-right">Volume Pekerjaan</th>
                        <th class="py-3 px-5 text-right">Kebutuhan Material</th>
                        <th class="py-3 px-5 text-right">Harga Satuan Baseline</th>
                        <th class="py-3 px-5 text-right">Subtotal Anggaran Material</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($rabAllocations as $alloc)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-800 block text-xs">
                                    {{ $alloc->rabItem->rabNode->code ?? '-' }} {{ $alloc->rabItem->rabNode->name ?? '-' }}
                                </span>
                                @if($alloc->rabItem->rabNode->parent)
                                    <span class="text-[11px] text-slate-400">
                                        Parent: {{ $alloc->rabItem->rabNode->parent->code }} {{ $alloc->rabItem->rabNode->parent->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 font-semibold text-slate-900 text-xs">
                                {{ $alloc->rabItem->name }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono text-xs text-slate-700">
                                {{ format_qty($alloc->rabItem->volume) }} {{ $alloc->rabItem->unit->code ?? '' }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold text-xs text-blue-700">
                                {{ format_qty($alloc->volume) }} {{ $alloc->unit->code ?? $unit }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono text-xs text-slate-600">
                                Rp {{ number_format($alloc->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-extrabold text-xs text-slate-900">
                                Rp {{ number_format($alloc->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <span class="text-xs">Tidak ada rincian RAB item tercatat untuk material ini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50/80 font-bold text-xs border-t-2 border-slate-200">
                        <td colspan="3" class="py-3.5 px-5 text-slate-700 uppercase">Total Akumulasi Alokasi RAB</td>
                        <td class="py-3.5 px-5 text-right font-mono font-black text-blue-700">
                            {{ number_format($rabAllocations->sum('volume'), 2, ',', '.') }} {{ $unit }}
                        </td>
                        <td class="py-3.5 px-5"></td>
                        <td class="py-3.5 px-5 text-right font-mono font-black text-slate-900">
                            Rp {{ number_format($rabAllocations->sum('total_price'), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
