@extends('layouts.app')

@section('title', 'Penerimaan Pembelian (DO Lapangan) — ' . $project->name)
@section('page_title', 'Penerimaan Pembelian (DO Lapangan)')
@section('page_subtitle', 'Penerimaan fisik material di lapangan dan pencatatan kuantiti riil penerimaan pembelian vendor')

@section('content')
<div class="space-y-6" x-data="{
    showDoModal: false,
    selectedDo: null,
    showDetailModal: false
}">

    <!-- Sub-Navigasi Modul & Quick Actions -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <!-- Tab Navigation 6 Modul -->
        @include('procurement.partials.nav_tabs', ['active' => 'do'])

        <!-- Action / Permission Status -->
        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWriteDo())
                <button type="button" @click="showDoModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Input Penerimaan Pembelian</span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Mode Lihat Saja (Read-Only)</span>
                </span>
            @endif
        </div>
    </div>    <!-- DAFTAR PENERIMAAN PEMBELIAN (DO) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Penerimaan Pembelian (DO) Diterima di Lapangan</h3>
                <p class="text-xs text-slate-500">Bukti fisik wajib dilampirkan oleh Pengawas Lapangan untuk verifikasi kuantiti aktual barang tiba</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-clean bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs">Total Penerimaan: {{ $deliveryOrders->count() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean min-w-[900px]">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. Penerimaan (DO)</th>
                        <th class="py-3 px-4">Tanggal Terima</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">No. Pesanan (PO) Ref</th>
                        <th class="py-3 px-5">Material &amp; Kuantiti Diterima</th>
                        <th class="py-3 px-4 text-center">Bukti Fisik</th>
                        <th class="py-3 px-4">Penerima</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        @if(auth()->user()?->canWriteDo())
                            <th class="py-3 px-4 text-center w-20">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveryOrders as $do)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-5 font-bold font-mono text-slate-900">{{ $do->do_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($do->do_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $do->supplier?->name ?? 'Vendor' }}</td>
                            <td class="py-3 px-4 font-mono text-xs">
                                <div class="flex flex-wrap items-center gap-1">
                                    @if($do->purchaseOrders->isNotEmpty())
                                        @foreach($do->purchaseOrders as $lpo)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                                {{ $lpo->po_number }}
                                            </span>
                                        @endforeach
                                    @elseif($do->purchaseOrder)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                            {{ $do->purchaseOrder->po_number }}
                                        </span>
                                    @endif

                                    @if($do->hasNonPoItems())
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200" title="Termasuk material tambahan dari pembelian tunai tanpa PO">
                                            💵 + Tunai
                                        </span>
                                    @elseif($do->purchaseOrders->isEmpty() && !$do->purchaseOrder)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200">
                                            💵 Pembelian Tunai (Non-PO)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-5">
                                <div class="space-y-1">
                                    @foreach($do->items as $it)
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                            <span class="font-bold text-slate-800">{{ $it->material?->name ?? 'Material' }}</span>
                                            <span class="font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded text-[11px] font-semibold">
                                                {{ format_qty($it->qty_received) }} {{ $it->material?->defaultUnit?->code ?? '' }}
                                            </span>
                                            @if($it->purchaseOrder)
                                                <span class="text-[9px] font-mono font-bold text-amber-800 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200" title="Alokasi PO">
                                                    📑 {{ $it->purchaseOrder->po_number }}
                                                </span>
                                            @else
                                                <span class="text-[9px] font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200" title="Material tambahan pembelian tunai">
                                                    💵 Tunai (Non-PO)
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($do->attachment_path)
                                    <button type="button" 
                                            onclick="openDocPreview('{{ asset('storage/' . $do->attachment_path) }}', 'Penerimaan Pembelian (DO): {{ addslashes($do->do_number) }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lihat Scan</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tanpa lampiran</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $do->receiver?->name ?? 'Pengawas Lapangan' }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">Terverifikasi Lapangan</span>
                            </td>
                            @if(auth()->user()?->canWriteDo())
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('procurement.do.destroy', $do->id) }}" method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus Penerimaan Pembelian {{ $do->do_number }}? Seluruh kuota realisasi material terkait akan dikalkulasi ulang.');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors cursor-pointer"
                                                title="Hapus Penerimaan Pembelian">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Penerimaan Pembelian (DO)</div>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    @if(auth()->user()?->canWriteDo())
                                        Klik tombol "Input Penerimaan Pembelian" untuk mencatat kedatangan material fisik di lapangan dan melampirkan berkas scan.
                                    @else
                                        Akun Anda memiliki izin Lihat Saja. Belum ada Penerimaan Pembelian yang dicatat oleh Pengawas Lapangan.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: INPUT PENERIMAAN PEMBELIAN (DO) (Hanya jika memiliki izin Write) -->
    @if(auth()->user()?->canWriteDo())
        <div x-show="showDoModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDoModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
                <div class="relative w-full max-w-3xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                     @click.stop
                     x-data="{
                        materials: {{ Js::from($materials->map(fn($m) => [
                            'id' => $m->id,
                            'name' => $m->name,
                            'unit_code' => $m->defaultUnit?->code ?? '',
                            'default_unit_id' => $m->default_unit_id,
                        ])) }},
                        units: {{ Js::from($units->map(fn($u) => [
                            'id' => $u->id,
                            'code' => $u->code,
                            'name' => $u->name ?? $u->code,
                        ])) }},
                        purchaseOrders: {{ Js::from($purchaseOrders->map(fn($po) => [
                            'id' => $po->id,
                            'po_number' => $po->po_number,
                            'supplier_id' => $po->supplier_id,
                            'supplier_name' => $po->supplier?->name ?? 'Vendor',
                        ])) }},
                        selectedPoIds: [],
                        openMaterialIdx: null,
                        openUnitIdx: null,
                        materialSearch: '',
                        items: [
                            { material_id: '', purchase_order_id: '', qty_received: '', unit_id: '' }
                        ],
                        addItem() {
                            let defaultPo = this.selectedPoIds.length === 1 ? this.selectedPoIds[0] : '';
                            this.items.push({ material_id: '', purchase_order_id: defaultPo, qty_received: '', unit_id: '' });
                        },
                        removeItem(idx) {
                            if (this.items.length > 1) {
                                this.items.splice(idx, 1);
                            }
                            if (this.openMaterialIdx === idx) this.openMaterialIdx = null;
                            if (this.openUnitIdx === idx) this.openUnitIdx = null;
                        },
                        togglePo(id) {
                            let numId = Number(id);
                            let index = this.selectedPoIds.indexOf(numId);
                            if (index > -1) {
                                this.selectedPoIds.splice(index, 1);
                            } else {
                                this.selectedPoIds.push(numId);
                            }
                        },
                        isPoSelected(id) {
                            return this.selectedPoIds.includes(Number(id));
                        },
                        selectMaterial(idx, mat) {
                            this.items[idx].material_id = mat.id;
                            if (mat.default_unit_id) {
                                this.items[idx].unit_id = mat.default_unit_id;
                            }
                            this.openMaterialIdx = null;
                            this.materialSearch = '';
                        },
                        selectUnit(idx, u) {
                            this.items[idx].unit_id = u.id;
                            this.openUnitIdx = null;
                        },
                        getMaterialLabel(id) {
                            if (!id) return '-- Pilih Material --';
                            let m = this.materials.find(x => String(x.id) === String(id));
                            return m ? (m.name + (m.unit_code ? ' (' + m.unit_code + ')' : '')) : '-- Pilih Material --';
                        },
                        getUnitLabel(id) {
                            if (!id) return '-- Satuan --';
                            let u = this.units.find(x => String(x.id) === String(id));
                            return u ? u.code : '-- Satuan --';
                        },
                        filteredMaterials() {
                            if (!this.materialSearch) return this.materials;
                            let q = this.materialSearch.toLowerCase();
                            return this.materials.filter(m => m.name.toLowerCase().includes(q));
                        }
                     }">
                    <form action="{{ route('procurement.do.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div>
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px] mb-1 font-bold">Pengawas Lapangan</span>
                                <h3 class="text-base font-extrabold text-slate-900">Input Penerimaan Pembelian (DO) Diterima</h3>
                                <p class="text-xs text-slate-500">Mencatat barang tiba fisik (bisa untuk 1 atau beberapa Pesanan Pembelian sekaligus)</p>
                            </div>
                            <button type="button" @click="showDoModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nomor Penerimaan / Surat Jalan (DO) <span class="text-rose-500">*</span></label>
                                    <input type="text" name="do_number" required placeholder="Mis. DO/BPS/2026/089" 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tanggal Terima di Lokasi <span class="text-rose-500">*</span></label>
                                    <input type="date" name="do_date" value="{{ date('Y-m-d') }}" required 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Rekanan Supplier <span class="text-rose-500">*</span></label>
                                    <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                        <option value="">-- Pilih Supplier --</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block font-bold text-slate-700">
                                            Referensi Pesanan Pembelian (PO)
                                        </label>
                                        <span class="text-[10px] text-amber-700 font-bold" x-show="selectedPoIds.length > 0">
                                            <span x-text="selectedPoIds.length"></span> Pesanan (PO) Terpilih
                                        </span>
                                    </div>

                                    <!-- Hidden Inputs for multi PO -->
                                    <template x-for="pId in selectedPoIds" :key="pId">
                                        <input type="hidden" name="purchase_order_ids[]" :value="pId">
                                    </template>

                                    <!-- Multi-PO Select List -->
                                    <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl max-h-28 overflow-y-auto custom-scrollbar space-y-1">
                                        @forelse($purchaseOrders as $po)
                                            <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white text-xs cursor-pointer transition-colors"
                                                   :class="isPoSelected({{ $po->id }}) ? 'bg-amber-50 font-bold text-amber-900 border border-amber-200' : 'text-slate-700'">
                                                <input type="checkbox" 
                                                       :checked="isPoSelected({{ $po->id }})"
                                                       @change="togglePo({{ $po->id }})"
                                                       class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                <span class="font-mono">{{ $po->po_number }}</span>
                                                <span class="text-[10px] text-slate-400">({{ $po->supplier?->name ?? 'Vendor' }})</span>
                                            </label>
                                        @empty
                                            <div class="text-[11px] text-slate-400 italic p-1">Belum ada Pesanan Pembelian aktif (Pembelian Langsung / Tunai)</div>
                                        @endforelse
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Centang 1 atau beberapa Pesanan Pembelian (PO) yang barangnya diangkut dalam penerimaan ini.</span>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Upload Bukti Fisik Penerimaan / Foto Lapangan <span class="text-rose-500 font-bold">* Wajib</span></label>
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required 
                                       class="w-full p-2 text-xs bg-slate-50 border border-slate-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <span class="text-[10px] text-slate-400 mt-1 block">Wajib unggah foto fisik dokumen penerimaan / surat jalan bertanda tangan pengawas (PDF, JPG, PNG maks 10MB)</span>
                            </div>

                            <!-- Item Material Repeater -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-bold text-slate-800">Rincian Material Diterima <span class="text-rose-500">*</span></span>
                                        <span class="text-[10px] text-slate-400 block">Pilih material, alokasi PO per baris (opsional), kuantiti, dan satuan</span>
                                    </div>
                                    <button type="button" @click="addItem()" class="text-xs text-blue-600 font-bold hover:underline flex items-center gap-1 cursor-pointer">
                                        + Tambah Baris Material
                                    </button>
                                </div>

                                <div class="space-y-2 border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                                    <template x-for="(it, idx) in items" :key="idx">
                                        <div class="grid grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-lg border border-slate-200 shadow-xs">
                                            <!-- Material (col-span-4) -->
                                            <div class="col-span-4 relative">
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
                                                     class="absolute left-0 top-full mt-1.5 w-72 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                     style="display: none;">
                                                    <div class="px-2 pb-1.5 border-b border-slate-100">
                                                        <input type="text" x-model="materialSearch" placeholder="Cari nama material..." 
                                                               @click.stop
                                                               class="w-full px-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    </div>
                                                    <div class="overflow-y-auto max-h-48 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                        <template x-for="mat in filteredMaterials()" :key="mat.id">
                                                            <div @click="selectMaterial(idx, mat)" 
                                                                 class="px-2.5 py-1.5 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                                 :class="it.material_id == mat.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                                <span class="truncate flex-1" x-text="mat.name + (mat.unit_code ? ' (' + mat.unit_code + ')' : '')"></span>
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

                                            <!-- Ref PO per Item (col-span-3) -->
                                            <div class="col-span-3">
                                                <select :name="'items[' + idx + '][purchase_order_id]'" 
                                                        x-model="it.purchase_order_id" 
                                                        class="w-full p-2 bg-white border border-slate-200 rounded-xl text-[11px] font-mono focus:ring-2 focus:ring-blue-500 font-semibold"
                                                        :class="it.purchase_order_id ? 'text-amber-900 bg-amber-50/50 border-amber-300' : 'text-emerald-800 bg-emerald-50/30 border-emerald-300'">
                                                    <option value="">💵 Tunai (Non-PO)</option>
                                                    @foreach($purchaseOrders as $po)
                                                        <option value="{{ $po->id }}">📑 {{ $po->po_number }} ({{ $po->supplier?->name ?? 'Vendor' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Qty Received (col-span-2) -->
                                            <div class="col-span-2">
                                                <input type="number" step="any" :name="'items[' + idx + '][qty_received]'" 
                                                       x-model="it.qty_received"
                                                       placeholder="Kuantiti" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- Satuan (col-span-2) -->
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

                                            <!-- Remove (col-span-1) -->
                                            <div class="col-span-1 text-center">
                                                <button type="button" @click="removeItem(idx)" class="p-1 text-slate-400 hover:text-rose-600 font-bold text-sm cursor-pointer" title="Hapus baris">&times;</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Catatan Kondisi Barang</label>
                                <textarea name="notes" rows="2" placeholder="Kondisi material saat dibongkar, plat nomor truk armada, supir..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="showDoModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Penerimaan Pembelian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
