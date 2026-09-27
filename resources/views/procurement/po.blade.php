@extends('layouts.app')

@section('title', 'Purchase Order (PO) — ' . $project->name)
@section('page_title', 'Purchase Order (PO)')
@section('page_subtitle', 'Pengelolaan dan penerbitan pesanan pengadaan material resmi ke rekanan vendor')

@section('content')
<div class="space-y-6" x-data="{
    showPoModal: false,
    selectedPo: null,
    showDetailModal: false
}">

    <!-- Sub-Navigasi Modul & Quick Actions -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <!-- Tab Navigation antara PO, DO, dan Invoice -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl tab-scroll-container overflow-x-auto max-w-full">
            <!-- Tab PO (Active) -->
            <a href="{{ route('procurement.po.index') }}" 
               class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 bg-white shadow-sm text-blue-700 font-bold">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Purchase Order (PO)</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $purchaseOrders->count() }}</span>
            </a>

            <!-- Tab DO (Jika memiliki izin DO) -->
            @if(auth()->user()?->canReadDo())
                <a href="{{ route('procurement.do.index') }}" 
                   class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Surat Jalan (DO Lapangan)</span>
                    <span class="px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700 rounded-full font-mono">{{ $doCount }}</span>
                </a>
            @endif

            <!-- Tab Invoice (Jika memiliki izin Invoice) -->
            @if(auth()->user()?->canReadInvoice())
                <a href="{{ route('procurement.invoices.index') }}" 
                   class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Faktur Tagihan (Invoice)</span>
                    <span class="px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700 rounded-full font-mono">{{ $invCount }}</span>
                </a>
            @endif
        </div>

        <!-- Action / Permission Status -->
        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWritePo())
                <button type="button" @click="showPoModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Buat PO Baru</span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Mode Lihat Saja (Read-Only)</span>
                </span>
            @endif
        </div>
    </div>

    <!-- DAFTAR PURCHASE ORDER (PO) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Arsip Surat Pesanan Pembelian (Purchase Orders)</h3>
                <p class="text-xs text-slate-500">Daftar Purchase Order resmi yang diterbitkan untuk vendor rekanan proyek</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-clean bg-blue-50 text-blue-700 border border-blue-200 text-xs">Total PO: {{ $purchaseOrders->count() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean min-w-[850px]">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. PO</th>
                        <th class="py-3 px-4">Tanggal PO</th>
                        <th class="py-3 px-4">Rekanan Supplier</th>
                        <th class="py-3 px-4">Jumlah Item</th>
                        <th class="py-3 px-5">Total Estimasi Nilai (Rp)</th>
                        <th class="py-3 px-4">Dibuat Oleh</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchaseOrders as $po)
                        @php
                            $totalVal = $po->items->sum(fn($it) => $it->qty_ordered * $it->unit_price);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-5 font-bold font-mono text-blue-700">{{ $po->po_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ \Carbon\Carbon::parse($po->po_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $po->supplier?->name ?? 'Vendor Umum' }}</td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold">{{ $po->items->count() }} Item</span>
                            </td>
                            <td class="py-3 px-5 font-mono font-bold text-slate-900">Rp {{ number_format($totalVal, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-slate-500 font-medium">{{ $po->creator?->name ?? 'Purchasing' }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">Terkirim / Aktif</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" 
                                        @click="selectedPo = {{ Js::from($po) }}; showDetailModal = true"
                                        class="px-2.5 py-1 text-[11px] font-bold text-blue-700 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                    Detail Item &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Purchase Order (PO)</div>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    @if(auth()->user()?->canWritePo())
                                        Klik tombol "Buat PO Baru" di atas untuk menerbitkan purchase order material pertama ke rekanan supplier.
                                    @else
                                        Akun Anda memiliki izin Lihat Saja. Belum ada PO yang dibuat oleh Purchasing Officer.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: DETAIL PO -->
    <div x-show="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDetailModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-2xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1 font-bold">Rincian Dokumen PO</span>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="selectedPo ? selectedPo.po_number : ''"></h3>
                        <p class="text-xs text-slate-500" x-text="'Supplier: ' + (selectedPo && selectedPo.supplier ? selectedPo.supplier.name : '-')"></p>
                    </div>
                    <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">No</th>
                                    <th class="py-2.5 px-3">Material</th>
                                    <th class="py-2.5 px-3 text-right">Kuantiti</th>
                                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(it, i) in (selectedPo ? selectedPo.items : [])" :key="i">
                                    <tr>
                                        <td class="py-2.5 px-3 text-slate-500" x-text="i + 1"></td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800" x-text="it.material ? (it.material.code + ' - ' + it.material.name) : '-'"></td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800" x-text="it.qty_ordered + ' ' + (it.unit ? it.unit.code : '')"></td>
                                        <td class="py-2.5 px-3 text-right font-mono text-slate-600" x-text="'Rp ' + Number(it.unit_price).toLocaleString('id-ID')"></td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'Rp ' + (Number(it.qty_ordered) * Number(it.unit_price)).toLocaleString('id-ID')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <template x-if="selectedPo && selectedPo.notes">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <span class="font-bold text-slate-700 block mb-0.5">Catatan PO:</span>
                            <span class="text-slate-600" x-text="selectedPo.notes"></span>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showDetailModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BUAT PURCHASE ORDER (PO) BARU (Hanya jika memiliki izin Write) -->
    @if(auth()->user()?->canWritePo())
        <div x-show="showPoModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPoModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
                <div class="relative w-full max-w-2xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                     @click.stop
                     x-data="{
                        materials: {{ Js::from($materials->map(fn($m) => [
                            'id' => $m->id,
                            'code' => $m->code,
                            'name' => $m->name,
                            'default_unit_id' => $m->default_unit_id,
                            'standard_price' => (float)$m->standard_price,
                        ])) }},
                        units: {{ Js::from($units->map(fn($u) => [
                            'id' => $u->id,
                            'code' => $u->code,
                            'name' => $u->name ?? $u->code,
                        ])) }},
                        openMaterialIdx: null,
                        openUnitIdx: null,
                        materialSearch: '',
                        poItems: [
                            { material_id: '', qty_ordered: 1, unit_id: '', unit_price: 0 }
                        ],
                        addPoItem() {
                            this.poItems.push({ material_id: '', qty_ordered: 1, unit_id: '', unit_price: 0 });
                        },
                        removePoItem(idx) {
                            if (this.poItems.length > 1) {
                                this.poItems.splice(idx, 1);
                            }
                            if (this.openMaterialIdx === idx) this.openMaterialIdx = null;
                            if (this.openUnitIdx === idx) this.openUnitIdx = null;
                        },
                        selectMaterial(idx, mat) {
                            this.poItems[idx].material_id = mat.id;
                            if (mat.default_unit_id) {
                                this.poItems[idx].unit_id = mat.default_unit_id;
                            }
                            if (mat.standard_price) {
                                this.poItems[idx].unit_price = parseFloat(mat.standard_price) || 0;
                            }
                            this.openMaterialIdx = null;
                            this.materialSearch = '';
                        },
                        selectUnit(idx, u) {
                            this.poItems[idx].unit_id = u.id;
                            this.openUnitIdx = null;
                        },
                        getMaterialLabel(id) {
                            if (!id) return '-- Pilih Material --';
                            let m = this.materials.find(x => String(x.id) === String(id));
                            return m ? (m.code + ' - ' + m.name) : '-- Pilih Material --';
                        },
                        getUnitLabel(id) {
                            if (!id) return '-- Satuan --';
                            let u = this.units.find(x => String(x.id) === String(id));
                            return u ? u.code : '-- Satuan --';
                        },
                        filteredMaterials() {
                            if (!this.materialSearch) return this.materials;
                            let q = this.materialSearch.toLowerCase();
                            return this.materials.filter(m => 
                                (m.code && m.code.toLowerCase().includes(q)) || 
                                (m.name && m.name.toLowerCase().includes(q))
                            );
                        },
                        get grandTotal() {
                            return this.poItems.reduce((acc, it) => {
                                let q = parseFloat(it.qty_ordered) || 0;
                                let p = parseFloat(it.unit_price) || 0;
                                return acc + (q * p);
                            }, 0);
                        }
                     }">
                    <form action="{{ route('procurement.po.store') }}" method="POST" @submit="if (poItems.some(it => !it.material_id)) { alert('Mohon pilih material untuk setiap baris.'); $event.preventDefault(); }">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div>
                                <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1 font-bold">Form Pembuatan PO</span>
                                <h3 class="text-base font-extrabold text-slate-900">Buat Purchase Order (PO) Baru</h3>
                                <p class="text-xs text-slate-500">Penerbitan pesanan pembelian material resmi untuk rekanan vendor</p>
                            </div>
                            <button type="button" @click="showPoModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nomor Purchase Order (PO) <span class="text-rose-500">*</span></label>
                                    <input type="text" name="po_number" required value="PO/{{ date('Y') }}/PRJ/{{ str_pad($purchaseOrders->count() + 1, 3, '0', STR_PAD_LEFT) }}"
                                           placeholder="Mis. PO/2026/PRJ/001" 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tanggal PO <span class="text-rose-500">*</span></label>
                                    <input type="date" name="po_date" value="{{ date('Y-m-d') }}" required 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Pilih Rekanan Supplier <span class="text-rose-500">*</span></label>
                                <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                    <option value="">-- Pilih Rekanan Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->category ?? 'Supplier' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan / Syarat Pembayaran</label>
                                <textarea name="notes" rows="2" placeholder="Catatan termin, jadwal pengiriman, lokasi serah terima..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"></textarea>
                            </div>

                            <!-- Item Material Repeater -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-slate-800">Daftar Material yang Dipesan <span class="text-rose-500">*</span></span>
                                    <button type="button" @click="addPoItem()" class="text-xs text-blue-600 font-bold hover:underline flex items-center gap-1">
                                        + Tambah Baris Material
                                    </button>
                                </div>

                                <div class="space-y-2 border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                                    <template x-for="(it, idx) in poItems" :key="idx">
                                        <div class="grid grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-lg border border-slate-200 shadow-xs">
                                            <!-- Material Search Dropdown -->
                                            <div class="col-span-5 relative">
                                                <input type="hidden" :name="'items[' + idx + '][material_id]'" :value="it.material_id">
                                                <button type="button" 
                                                        @click="openMaterialIdx = (openMaterialIdx === idx ? null : idx); openUnitIdx = null"
                                                        class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-xl shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-2 px-2.5 text-xs transition-all cursor-pointer">
                                                    <span class="truncate block flex-1 font-semibold" :class="it.material_id ? 'text-slate-800' : 'text-slate-400'" 
                                                          x-text="getMaterialLabel(it.material_id)"></span>
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1.5 transition-transform duration-200" 
                                                         :class="openMaterialIdx === idx ? 'rotate-180 text-blue-600' : ''"
                                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="openMaterialIdx === idx" 
                                                     @click.outside="if (openMaterialIdx === idx) openMaterialIdx = null"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                     x-transition:leave="transition ease-in duration-100"
                                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                     x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                     class="absolute left-0 top-full mt-1.5 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                     style="display: none;">
                                                    <div class="px-2 pb-1.5 border-b border-slate-100">
                                                        <input type="text" x-model="materialSearch" placeholder="Cari material..." 
                                                               @click.stop
                                                               class="w-full px-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    </div>
                                                    <div class="overflow-y-auto max-h-48 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                        <template x-for="mat in filteredMaterials()" :key="mat.id">
                                                            <div @click="selectMaterial(idx, mat)" 
                                                                 class="px-2.5 py-1.5 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                                 :class="it.material_id == mat.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                                <span class="truncate flex-1" x-text="mat.code + ' - ' + mat.name"></span>
                                                                <template x-if="it.material_id == mat.id">
                                                                    <svg class="w-3.5 h-3.5 text-blue-600 flex-shrink-0 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Qty Ordered -->
                                            <div class="col-span-2">
                                                <input type="number" step="any" :name="'items[' + idx + '][qty_ordered]'" 
                                                       x-model="it.qty_ordered"
                                                       placeholder="Kuantiti" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- Satuan -->
                                            <div class="col-span-2 relative">
                                                <input type="hidden" :name="'items[' + idx + '][unit_id]'" :value="it.unit_id">
                                                <button type="button" 
                                                        @click="openUnitIdx = (openUnitIdx === idx ? null : idx); openMaterialIdx = null"
                                                        class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-xl shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-2 px-2 text-xs transition-all cursor-pointer">
                                                    <span class="truncate block flex-1 font-semibold" :class="it.unit_id ? 'text-slate-800' : 'text-slate-400'" 
                                                          x-text="getUnitLabel(it.unit_id)"></span>
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1 transition-transform duration-200" 
                                                         :class="openUnitIdx === idx ? 'rotate-180 text-blue-600' : ''"
                                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="openUnitIdx === idx" 
                                                     @click.outside="if (openUnitIdx === idx) openUnitIdx = null"
                                                     class="absolute left-0 top-full mt-1.5 w-32 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                     style="display: none;">
                                                    <div class="overflow-y-auto max-h-48 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                        <template x-for="u in units" :key="u.id">
                                                            <div @click="selectUnit(idx, u)" 
                                                                 class="px-2.5 py-1.5 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                                 :class="it.unit_id == u.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                                <span class="truncate flex-1 font-semibold" x-text="u.code"></span>
                                                                <template x-if="it.unit_id == u.id">
                                                                    <svg class="w-3.5 h-3.5 text-blue-600 flex-shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Unit Price -->
                                            <div class="col-span-2">
                                                <input type="number" step="any" :name="'items[' + idx + '][unit_price]'" 
                                                       x-model="it.unit_price"
                                                       placeholder="Harga (Rp)" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- Remove -->
                                            <div class="col-span-1 text-center">
                                                <button type="button" @click="removePoItem(idx)" class="p-1 text-slate-400 hover:text-rose-600 font-bold text-sm" title="Hapus baris">&times;</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-3 p-3 bg-blue-50 rounded-xl border border-blue-200 flex items-center justify-between font-mono">
                                    <span class="text-xs font-bold text-blue-900">Total Estimasi Nilai PO:</span>
                                    <span class="text-sm font-extrabold text-blue-800" x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="showPoModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Terbitkan PO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
