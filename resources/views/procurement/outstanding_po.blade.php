@extends('layouts.app')

@section('title', 'Monitoring PO Menggantung — ' . $project->name)
@section('page_title', 'Monitoring PO Menggantung')
@section('page_subtitle', 'Pantau Purchase Order yang barangnya belum diterima sepenuhnya di lapangan serta tindak lanjut nomor Surat Jalan (SJ) susulan')

@section('content')
<div class="space-y-6" x-data="{
    showEditDoModal: false,
    selectedPo: null,
    editDoNumber: '',
    editDoAction: '',
    openEditDo(po) {
        this.selectedPo = po;
        this.editDoNumber = po.delivery_order_number || '';
        this.editDoAction = '/procurement/po/' + po.id + '/update-do';
        this.showEditDoModal = true;
    }
}">

    <!-- Sub-Navigasi Modul & Project Selector -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'outstanding_po', 'pendingPoCount' => $totalPendingPos])

        <div class="flex items-center gap-2">
            <a href="{{ route('procurement.do.index') }}" 
               class="px-3.5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs transition-all flex items-center gap-1.5 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Catat DO Lapangan</span>
            </a>
        </div>
    </div>


    <!-- KPI METRICS SUMMARY -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- 1. Total PO Menggantung -->
        <div class="card-clean p-4 sm:p-5 flex flex-col justify-between border-l-4 border-l-amber-500">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total PO Menggantung</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black font-mono text-slate-900">{{ $totalPendingPos }}</span>
                    <span class="text-xs font-semibold text-slate-500">Pesanan Pembelian</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
                {{ $totalPartialPos }} sebagian diterima &bull; {{ $totalPendingPos - $totalPartialPos }} belum tiba
            </p>
        </div>

        <!-- 2. Sebagian Diterima (Parsial) -->
        <div class="card-clean p-4 sm:p-5 flex flex-col justify-between border-l-4 border-l-blue-500">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sebagian Diterima</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black font-mono text-blue-700">{{ $totalPartialPos }}</span>
                    <span class="text-xs font-semibold text-blue-600">PO Aktif Berjalan</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
                Sudah ada SJ masuk namun barang belum lengkap
            </p>
        </div>

        <!-- 3. Butuh Input SJ Baru -->
        <div class="card-clean p-4 sm:p-5 flex flex-col justify-between border-l-4 border-l-rose-500">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Perlu Nomor SJ Baru</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black font-mono text-rose-600">{{ $totalNeedsDo }}</span>
                    <span class="text-xs font-semibold text-rose-600">Menunggu Pembuat PO</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
                SJ awal sudah terpakai di DO lapangan
            </p>
        </div>

        <!-- 4. Total Item Belum Tuntas -->
        <div class="card-clean p-4 sm:p-5 flex flex-col justify-between border-l-4 border-l-indigo-500">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Item Belum Tiba</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black font-mono text-indigo-700">{{ $totalPendingItemsCount }}</span>
                    <span class="text-xs font-semibold text-slate-500">Baris Material</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
                Nilai sisa: <strong class="text-slate-700 font-mono">Rp {{ number_format($totalPendingValue, 0, ',', '.') }}</strong>
            </p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card-clean p-4">
        <form action="{{ route('procurement.outstanding-po') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <!-- Search -->
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari No. PO, Supplier, Material, atau No. SJ..."
                       class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Status Filter -->
            <div class="w-full sm:w-56">
                <select name="status" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 bg-white border border-slate-200 rounded-xl font-medium">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua PO Menggantung</option>
                    <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Sebagian Diterima (Parsial)</option>
                    <option value="needs_do" {{ $statusFilter === 'needs_do' ? 'selected' : '' }}>Perlu Input SJ Baru</option>
                    <option value="unfulfilled" {{ $statusFilter === 'unfulfilled' ? 'selected' : '' }}>Belum Ada Pengiriman (0%)</option>
                </select>
            </div>

            <!-- Supplier Filter -->
            <div class="w-full sm:w-56">
                <select name="supplier_id" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 bg-white border border-slate-200 rounded-xl font-medium">
                    <option value="">-- Semua Supplier --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ $supplierFilter == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($search || $statusFilter !== 'all' || $supplierFilter)
                <a href="{{ route('procurement.outstanding-po', ['project_id' => $project->id]) }}" 
                   class="px-3 py-2 text-xs text-rose-600 font-bold hover:underline shrink-0 text-center">
                    Reset Filter
                </a>
            @endif
        </form>
    </div>

    <!-- LIST OF OUTSTANDING PO CARDS -->
    <div class="space-y-4">
        @forelse($outstandingPos as $po)
            <div class="card-clean overflow-hidden border border-slate-200">
                <!-- Card Header -->
                <div class="p-4 sm:p-5 bg-slate-50/80 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold font-mono text-xs shrink-0 shadow-2xs">
                            PO
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h3 class="text-sm sm:text-base font-extrabold font-mono text-slate-900">{{ $po->po_number }}</h3>
                                <span class="badge-clean text-[10px] font-bold px-2 py-0.5 rounded-full {{ $po->fulfillment['is_partial'] ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $po->fulfillment['status_label'] }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                                <span>Rekanan: <strong class="text-slate-800">{{ $po->supplier?->name ?? 'Supplier' }}</strong></span>
                                <span>&bull;</span>
                                <span>Tgl PO: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($po->po_date)->format('d/m/Y') }}</strong></span>
                                <span>&bull;</span>
                                <span>Pembuat PO: <strong class="text-slate-700">{{ $po->creator?->name ?? 'User' }}</strong></span>
                            </div>
                            <!-- Progress Bar Penerimaan Kuantiti -->
                            <div class="flex items-center gap-2.5 mt-2 w-52 sm:w-64">
                                <span class="text-[11px] font-bold text-slate-600 whitespace-nowrap">{{ $po->fulfillment['percentage'] }}% Diterima</span>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: {{ $po->fulfillment['percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        @if(auth()->user()?->canWritePo())
                            <button type="button" @click='openEditDo(@json($po))'
                                    class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 flex items-center gap-1.5 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Perbarui Nomor SJ</span>
                            </button>
                        @endif

                        @if(auth()->user()?->canWriteDo())
                            <a href="{{ route('procurement.do.index', ['open_po_id' => $po->id]) }}"
                               class="px-3 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Catat Penerimaan (DO)</span>
                            </a>
                        @endif

                        @if(auth()->user()?->canReadPo())
                            <a href="{{ route('procurement.po.pdf', $po->id) }}" target="_blank"
                               class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors" title="Unduh Berkas PO PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Detail Tabel Rincian Material PO -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs table-clean min-w-[700px]">
                        <thead class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-100 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="py-2.5 px-5">Material / Item</th>
                                <th class="py-2.5 px-4 text-center">Satuan</th>
                                <th class="py-2.5 px-4 text-right">Kuantiti Dipesan</th>
                                <th class="py-2.5 px-4 text-right">Sudah Diterima</th>
                                <th class="py-2.5 px-4 text-right">Sisa Belum Tiba</th>
                                <th class="py-2.5 px-4 text-center">Status Pemenuhan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($po->fulfillment['items'] as $item)
                                <tr class="{{ $item['is_fulfilled'] ? 'bg-slate-50/40 text-slate-500' : 'hover:bg-amber-50/30' }}">
                                    <td class="py-3 px-5">
                                        <div class="font-bold text-slate-800">{{ $item['material_name'] }}</div>
                                        <div class="font-mono text-[10px] text-slate-400">{{ $item['material_code'] }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold text-slate-600">
                                        {{ $item['unit_code'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-slate-800">
                                        {{ number_format($item['qty_ordered'], 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold {{ $item['qty_received'] > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ number_format($item['qty_received'], 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold {{ $item['qty_remaining'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                        {{ number_format($item['qty_remaining'], 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($item['is_fulfilled'])
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Lengkap (100%)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-300">
                                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Belum Diterima ({{ number_format($item['percentage'], 0) }}%)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Riwayat Surat Jalan (DO) yang Pernah Diterima untuk PO ini -->
                @php
                    $linkedDos = $po->deliveryOrders->merge($po->directDeliveryOrders)->unique('id');
                @endphp
                @if($linkedDos->isNotEmpty())
                    <div class="p-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center gap-3 text-xs flex-wrap">
                        <span class="font-semibold text-slate-500">Riwayat DO Tiba Sebelumnya:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @foreach($linkedDos as $ldo)
                                <span class="inline-flex items-center gap-1 font-mono font-bold text-[11px] bg-white border border-slate-200 px-2 py-0.5 rounded-lg text-slate-800 shadow-2xs">
                                    <span>📄 {{ $ldo->do_number }}</span>
                                    <span class="text-slate-400 font-sans font-normal">({{ \Carbon\Carbon::parse($ldo->do_date)->format('d/m/Y') }})</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="card-clean p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800">Tidak Ada PO Menggantung</h4>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                    Semua Purchase Order pada proyek ini telah diterima 100% lengkap di lapangan atau belum ada pesanan aktif.
                </p>
                <div class="mt-4">
                    <a href="{{ route('procurement.po.index') }}" class="px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors inline-block">
                        Lihat Daftar Pesanan Pembelian
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- MODAL INPUT / UPDATE NOMOR SURAT JALAN (DO/SJ) -->
    <div x-show="showEditDoModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" 
         x-cloak 
         @keydown.escape.window="showEditDoModal = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-6 my-4 sm:my-8 max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100" @click.stop>
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Perbarui Nomor Surat Jalan (DO/SJ)</h4>
                    <p class="text-xs text-slate-500" x-text="'Untuk ' + (selectedPo ? selectedPo.po_number : '')"></p>
                </div>
                <button type="button" @click="showEditDoModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">&times;</button>
            </div>

            <form :action="editDoAction" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Nomor Surat Jalan (SJ/DO) Baru <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="delivery_order_number" x-model="editDoNumber" required 
                           placeholder="Mis. SJ-BPS/2026/095 atau DO/..."
                           class="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono font-bold text-xs text-slate-800">
                    <span class="text-[10px] text-amber-700 block mt-1.5 font-medium leading-relaxed">
                        ⚠️ Pastikan menggunakan nomor Surat Jalan baru dari vendor. Nomor yang sudah pernah diterima (misal SJ-BPS/2026/094) tidak dapat diinput ulang.
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600 space-y-1">
                    <p class="font-bold text-slate-800">Informasi Tindak Lanjut:</p>
                    <p>Setelah nomor SJ disimpan, Pengawas Lapangan dapat langsung memilih nomor ini pada formulir <strong>Input Penerimaan Pembelian (DO)</strong> saat sisa material tiba.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showEditDoModal = false" 
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-xl shadow-xs transition-colors cursor-pointer">
                        Simpan Nomor SJ Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
