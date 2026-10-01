@extends('layouts.app')

@section('title', 'Pesanan Pembelian (PO) — ' . $project->name)
@section('page_title', 'Pesanan Pembelian (PO)')
@section('page_subtitle', 'Pengelolaan dan penerbitan pesanan pembelian material resmi ke rekanan vendor')

@section('content')
<div class="space-y-6" x-data="{
    showPoModal: false,
    selectedPo: null,
    showDetailModal: false
}">

    <!-- Sub-Navigasi Modul & Quick Actions -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <!-- Tab Navigation 6 Modul -->
        @include('procurement.partials.nav_tabs', ['active' => 'po'])

        <!-- Action / Permission Status -->
        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWritePo())
                <button type="button" @click="showPoModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Buat Pesanan Pembelian</span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Mode Lihat Saja (Read-Only)</span>
                </span>
            @endif
        </div>
    </div>

    <!-- DAFTAR PESANAN PEMBELIAN (PO) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Arsip Pesanan Pembelian (PO)</h3>
                <p class="text-xs text-slate-500">Daftar pesanan pembelian resmi yang diterbitkan untuk vendor rekanan proyek</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-clean bg-blue-50 text-blue-700 border border-blue-200 text-xs">Total Pesanan: {{ $purchaseOrders->count() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean min-w-[850px]">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. Pesanan (PO)</th>
                        <th class="py-3 px-4">Tanggal Pesanan</th>
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
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" 
                                            @click="selectedPo = {{ Js::from($po) }}; showDetailModal = true"
                                            class="px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer">
                                        Detail &rarr;
                                    </button>
                                    <a href="{{ route('procurement.po.pdf', $po->id) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 rounded-lg transition-colors"
                                       title="Unduh Dokumen PO Resmi (PDF)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Unduh PDF</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Pesanan Pembelian (PO)</div>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    @if(auth()->user()?->canWritePo())
                                        Klik tombol "Buat Pesanan Pembelian" di atas untuk menerbitkan pesanan pembelian material pertama ke rekanan supplier.
                                    @else
                                        Akun Anda memiliki izin Lihat Saja. Belum ada Pesanan Pembelian yang dibuat oleh Purchasing Officer.
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
            <div class="relative w-full max-w-3xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
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
                                    <th class="py-2.5 px-3">Material & Spesifikasi</th>
                                    <th class="py-2.5 px-3">Uraian Pekerjaan (RAB)</th>
                                    <th class="py-2.5 px-3 text-right">Kuantiti</th>
                                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(it, i) in (selectedPo ? selectedPo.items : [])" :key="i">
                                    <tr>
                                        <td class="py-2.5 px-3 text-slate-500" x-text="i + 1"></td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">
                                            <div x-text="it.material ? (it.material.code + ' - ' + it.material.name) : '-'"></div>
                                            <template x-if="it.material && it.material.specification">
                                                <div class="text-[10px] text-slate-400 font-normal" x-text="'Spek: ' + it.material.specification"></div>
                                            </template>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-700">
                                            <template x-if="it.rab_item">
                                                <div>
                                                    <div class="font-bold text-blue-700" x-text="'[' + (it.rab_item.item_no || '-') + '] ' + it.rab_item.name"></div>
                                                    <template x-if="it.rab_item.rab_node">
                                                        <div class="text-[10px] text-slate-400 font-mono" x-text="(it.rab_item.rab_node.code || '') + ' - ' + (it.rab_item.rab_node.name || '')"></div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!it.rab_item">
                                                <span class="text-slate-400 italic text-[11px]">- Non-RAB / Umum -</span>
                                            </template>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800" x-text="parseFloat(it.qty_ordered).toLocaleString('id-ID', { maximumFractionDigits: 4 }) + ' ' + (it.unit ? it.unit.code : '')"></td>
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

                <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                    <div class="flex items-center gap-2">
                        <a :href="'/procurement/po/' + (selectedPo ? selectedPo.id : '') + '/pdf'" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl shadow-sm transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh Dokumen PDF</span>
                        </a>
                        <button type="button"
                                @click="openDocPreview('/procurement/po/' + (selectedPo ? selectedPo.id : '') + '/pdf?preview=1', 'Pesanan Pembelian (PO): ' + (selectedPo ? selectedPo.po_number : ''), true)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Pratinjau PDF</span>
                        </button>
                    </div>
                    <button type="button" @click="showDetailModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BUAT PESANAN PEMBELIAN (PO) BARU (Hanya jika memiliki izin Write) -->
    @if(auth()->user()?->canWritePo())
        <div x-show="showPoModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPoModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
                <div class="relative w-full max-w-3xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                     @click.stop
                     x-data="{
                        materials: {{ Js::from($materials->map(fn($m) => [
                            'id' => $m->id,
                            'code' => $m->code,
                            'name' => $m->name,
                            'default_unit_id' => $m->default_unit_id,
                        ])) }},
                        units: {{ Js::from($units->map(fn($u) => [
                            'id' => $u->id,
                            'code' => $u->code,
                            'name' => $u->name ?? $u->code,
                        ])) }},
                        rabItems: {{ Js::from($rabItems->map(fn($r) => [
                            'id' => $r->id,
                            'item_no' => $r->item_no,
                            'name' => $r->name,
                            'node_code' => $r->rabNode?->code ?? '',
                            'node_name' => $r->rabNode?->name ?? '',
                        ])) }},
                        openMaterialIdx: null,
                        openUnitIdx: null,
                        openRabIdx: null,
                        materialSearch: '',
                        rabSearch: '',
                        poItems: [
                            { rab_item_id: '', material_id: '', qty_ordered: 1, unit_id: '', unit_price: 0 }
                        ],
                        addPoItem() {
                            this.poItems.push({ rab_item_id: '', material_id: '', qty_ordered: 1, unit_id: '', unit_price: 0 });
                        },
                        removePoItem(idx) {
                            if (this.poItems.length > 1) {
                                this.poItems.splice(idx, 1);
                            }
                            if (this.openMaterialIdx === idx) this.openMaterialIdx = null;
                            if (this.openUnitIdx === idx) this.openUnitIdx = null;
                            if (this.openRabIdx === idx) this.openRabIdx = null;
                        },
                        selectMaterial(idx, mat) {
                            this.poItems[idx].material_id = mat.id;
                            if (mat.default_unit_id) {
                                this.poItems[idx].unit_id = mat.default_unit_id;
                            }
                            this.openMaterialIdx = null;
                            this.materialSearch = '';
                        },
                        selectRabItem(idx, rab) {
                            this.poItems[idx].rab_item_id = rab ? rab.id : '';
                            this.openRabIdx = null;
                            this.rabSearch = '';
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
                        getRabLabel(id) {
                            if (!id) return '-- Pilih Uraian Pekerjaan (RAB) --';
                            let r = this.rabItems.find(x => String(x.id) === String(id));
                            return r ? ('[' + (r.item_no || '-') + '] ' + r.name + (r.node_code ? ' (' + r.node_code + ')' : '')) : '-- Pilih Uraian Pekerjaan (RAB) --';
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
                        filteredRabItems() {
                            if (!this.rabSearch) return this.rabItems;
                            let q = this.rabSearch.toLowerCase();
                            return this.rabItems.filter(r => 
                                (r.item_no && r.item_no.toLowerCase().includes(q)) || 
                                (r.name && r.name.toLowerCase().includes(q)) ||
                                (r.node_code && r.node_code.toLowerCase().includes(q)) ||
                                (r.node_name && r.node_name.toLowerCase().includes(q))
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
                                <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1 font-bold">Form Pembuatan Pesanan</span>
                                <h3 class="text-base font-extrabold text-slate-900">Buat Pesanan Pembelian (PO) Baru</h3>
                                <p class="text-xs text-slate-500">Penerbitan pesanan pembelian material resmi untuk rekanan vendor</p>
                            </div>
                            <button type="button" @click="showPoModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nomor Pesanan Pembelian (PO) <span class="text-rose-500">*</span></label>
                                    <input type="text" name="po_number" required value="PO/{{ date('Y') }}/PRJ/{{ str_pad($purchaseOrders->count() + 1, 3, '0', STR_PAD_LEFT) }}"
                                           placeholder="Mis. PO/2026/PRJ/001" 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pesanan <span class="text-rose-500">*</span></label>
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
                                    <div>
                                        <span class="font-bold text-slate-800">Daftar Material yang Dipesan <span class="text-rose-500">*</span></span>
                                        <p class="text-[11px] text-slate-500">Pilih Uraian Pekerjaan dari RAB awal proyek untuk memudahkan tracing realisasi</p>
                                    </div>
                                    <button type="button" @click="addPoItem()" class="text-xs text-blue-600 font-bold hover:underline flex items-center gap-1">
                                        + Tambah Baris Material
                                    </button>
                                </div>

                                <div class="space-y-3 border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                                    <template x-for="(it, idx) in poItems" :key="idx">
                                        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
                                            <!-- Row 1: Uraian Pekerjaan (RAB) Selector & Tombol Hapus -->
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-800 font-mono font-bold text-xs flex items-center justify-center flex-shrink-0" x-text="idx + 1"></span>
                                                    <div class="flex-1 relative">
                                                        <input type="hidden" :name="'items[' + idx + '][rab_item_id]'" :value="it.rab_item_id">
                                                        <button type="button" 
                                                                @click="openRabIdx = (openRabIdx === idx ? null : idx); openMaterialIdx = null; openUnitIdx = null"
                                                                class="w-full flex items-center justify-between text-left font-medium bg-slate-50 hover:bg-slate-100/90 border border-slate-300 rounded-lg text-slate-800 py-1.5 px-2.5 text-xs transition-all cursor-pointer">
                                                            <div class="truncate flex items-center gap-1.5 flex-1 mr-2">
                                                                <span class="text-[10px] uppercase font-bold text-slate-400 flex-shrink-0">Pekerjaan (RAB):</span>
                                                                <span class="truncate font-semibold text-xs" :class="it.rab_item_id ? 'text-blue-700 font-bold' : 'text-slate-400'" 
                                                                      x-text="getRabLabel(it.rab_item_id)"></span>
                                                            </div>
                                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-200" 
                                                                 :class="openRabIdx === idx ? 'rotate-180 text-blue-600' : ''"
                                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
                                                            </svg>
                                                        </button>
                                                        <!-- Dropdown List Uraian Pekerjaan -->
                                                        <div x-show="openRabIdx === idx" 
                                                             @click.outside="if (openRabIdx === idx) openRabIdx = null"
                                                             x-transition:enter="transition ease-out duration-150"
                                                             x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                             x-transition:leave="transition ease-in duration-100"
                                                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                             x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                             class="absolute left-0 top-full mt-1 w-full bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                             style="display: none;">
                                                            <div class="px-2 pb-1.5 border-b border-slate-100">
                                                                <input type="text" x-model="rabSearch" placeholder="Cari uraian pekerjaan atau sub-kategori RAB..." 
                                                                       @click.stop
                                                                       class="w-full px-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                            </div>
                                                            <div class="overflow-y-auto max-h-48 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                                <div @click="selectRabItem(idx, null)" 
                                                                     class="px-2.5 py-1.5 text-xs rounded-lg cursor-pointer flex items-center justify-between hover:bg-slate-100 text-slate-500 italic">
                                                                    <span>-- Tanpa Alokasi RAB (Pengadaan Umum) --</span>
                                                                </div>
                                                                <template x-for="rab in filteredRabItems()" :key="rab.id">
                                                                    <div @click="selectRabItem(idx, rab)" 
                                                                         class="px-2.5 py-1.5 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                                         :class="it.rab_item_id == rab.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                                        <div class="truncate flex-1">
                                                                            <div class="font-bold text-slate-800" x-text="'[' + (rab.item_no || '-') + '] ' + rab.name"></div>
                                                                            <div class="text-[10px] text-slate-400 font-mono" x-text="rab.node_code + ' - ' + rab.node_name"></div>
                                                                        </div>
                                                                        <template x-if="it.rab_item_id == rab.id">
                                                                            <svg class="w-3.5 h-3.5 text-blue-600 flex-shrink-0 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                            </svg>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <button type="button" @click="removePoItem(idx)" 
                                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer flex-shrink-0" 
                                                        title="Hapus baris item">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>

                                            <!-- Row 2: Material, Qty, Satuan, Harga Satuan -->
                                            <div class="grid grid-cols-12 gap-2 items-center pt-2 border-t border-slate-100">
                                                <!-- Material Dropdown (5 cols) -->
                                                <div class="col-span-12 sm:col-span-5 relative">
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5 sm:hidden">Pilih Material</label>
                                                    <input type="hidden" :name="'items[' + idx + '][material_id]'" :value="it.material_id">
                                                    <button type="button" 
                                                            @click="openMaterialIdx = (openMaterialIdx === idx ? null : idx); openUnitIdx = null; openRabIdx = null"
                                                            class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-lg shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-1.5 px-2.5 text-xs transition-all cursor-pointer">
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

                                                <!-- Qty Ordered (2 cols) -->
                                                <div class="col-span-4 sm:col-span-2">
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5 sm:hidden">Kuantiti</label>
                                                    <input type="number" step="any" :name="'items[' + idx + '][qty_ordered]'" 
                                                           x-model="it.qty_ordered"
                                                           placeholder="Kuantiti" required class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                                </div>

                                                <!-- Satuan (2 cols) -->
                                                <div class="col-span-4 sm:col-span-2 relative">
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5 sm:hidden">Satuan</label>
                                                    <input type="hidden" :name="'items[' + idx + '][unit_id]'" :value="it.unit_id">
                                                    <button type="button" 
                                                            @click="openUnitIdx = (openUnitIdx === idx ? null : idx); openMaterialIdx = null; openRabIdx = null"
                                                            class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-lg shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-1.5 px-2 text-xs transition-all cursor-pointer">
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

                                                <!-- Unit Price (3 cols) -->
                                                <div class="col-span-4 sm:col-span-3">
                                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5 sm:hidden">Harga Satuan (Rp)</label>
                                                    <input type="number" step="any" :name="'items[' + idx + '][unit_price]'" 
                                                           x-model="it.unit_price"
                                                           placeholder="Harga Satuan (Rp)" required class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                                </div>
                                            </div>

                                            <!-- Baris Subtotal Item -->
                                            <div class="flex items-center justify-between px-1 text-[11px] text-slate-500 font-mono">
                                                <span>Subtotal Baris:</span>
                                                <span class="font-bold text-slate-800" x-text="'Rp ' + (Number(it.qty_ordered || 0) * Number(it.unit_price || 0)).toLocaleString('id-ID')"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-3 p-3 bg-blue-50 rounded-xl border border-blue-200 flex items-center justify-between font-mono">
                                    <span class="text-xs font-bold text-blue-900">Total Estimasi Nilai Pesanan:</span>
                                    <span class="text-sm font-extrabold text-blue-800" x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="showPoModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Terbitkan Pesanan Pembelian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
