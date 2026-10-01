@extends('layouts.app')

@section('title', 'Procurement & Pengadaan — ' . $project->name)
@section('page_title', 'Procurement & Administrasi Lapangan')
@section('page_subtitle', 'Pengelolaan Purchase Order (PO), Surat Jalan (DO - Pengawas), dan Faktur (Invoice - Purchasing)')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'do',
    showDoModal: false,
    showInvModal: false,
    showPoModal: false
}">

    <!-- Tab Header & Quick Actions -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl tab-scroll-container overflow-x-auto max-w-full">
            <button type="button" @click="activeTab = 'do'" 
                    :class="activeTab === 'do' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 font-semibold'"
                    class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="hidden sm:inline">Surat Jalan (DO — Pengawas)</span>
                <span class="sm:hidden">DO</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $deliveryOrders->count() }}</span>
            </button>

            <button type="button" @click="activeTab = 'invoice'" 
                    :class="activeTab === 'invoice' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 font-semibold'"
                    class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">Faktur (Invoice — Purchasing)</span>
                <span class="sm:hidden">Invoice</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $invoices->count() }}</span>
            </button>

            <button type="button" @click="activeTab = 'po'" 
                    :class="activeTab === 'po' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 font-semibold'"
                    class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span class="hidden sm:inline">Purchase Orders (PO)</span>
                <span class="sm:hidden">PO</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $purchaseOrders->count() }}</span>
            </button>
        </div>

        <!-- Action Button based on Tab -->
        <div>
            <template x-if="activeTab === 'do'">
                <button type="button" @click="showDoModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Input Surat Jalan (DO)
                </button>
            </template>
            <template x-if="activeTab === 'invoice'">
                <button type="button" @click="showInvModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Input Invoice (Tagihan)
                </button>
            </template>
            <template x-if="activeTab === 'po'">
                <button type="button" @click="showPoModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Buat PO Baru
                </button>
            </template>
        </div>
    </div>

    <!-- TAB 1: DELIVERY ORDERS (DO) -->
    <div x-show="activeTab === 'do'" class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Surat Jalan (DO) Diterima di Lapangan</h3>
                <p class="text-xs text-slate-500">Bukti fisik wajib dilampirkan oleh Pengawas Lapangan untuk verifikasi kuantiti actual</p>
            </div>
            <span class="badge-clean bg-slate-100 text-slate-600 text-xs">Wajib Lampiran Surat Jalan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. DO</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">No. PO Ref</th>
                        <th class="py-3 px-5">Material & Kuantiti Diterima</th>
                        <th class="py-3 px-4 text-center">Bukti Fisik</th>
                        <th class="py-3 px-4">Penerima</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveryOrders as $do)
                        <tr>
                            <td class="py-3 px-5 font-bold font-mono text-slate-800">{{ $do->do_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ \Carbon\Carbon::parse($do->do_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-mono text-xs">
                                @if($do->purchaseOrders->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($do->purchaseOrders as $lpo)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                                {{ $lpo->po_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif($do->purchaseOrder)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200">
                                        {{ $do->purchaseOrder->po_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">— Non-PO —</span>
                                @endif
                            </td>
                            <td class="py-3 px-5">
                                <div class="space-y-1">
                                    @foreach($do->items as $it)
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            <span class="font-bold text-slate-800">{{ $it->material->name }}:</span>
                                            <span class="font-mono font-semibold text-blue-700">{{ format_qty($it->qty_received) }} {{ $it->unit?->code }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($do->attachment_path)
                                    <button type="button" 
                                            onclick="openDocPreview('{{ asset('storage/' . $do->attachment_path) }}', 'Surat Jalan (DO): {{ addslashes($do->do_number) }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Buka Lampiran
                                    </button>
                                @else
                                    <span class="text-rose-500 text-[10px] font-bold">Tanpa Bukti</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $do->receiver->name }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">Tervalidasi</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada data Surat Jalan (DO).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: INVOICES -->
    <div x-show="activeTab === 'invoice'" class="card-clean overflow-hidden" x-cloak>
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Tagihan & Faktur (Invoice — Purchasing)</h3>
                <p class="text-xs text-slate-500">Bukti invoice wajib diunggah oleh Purchasing untuk validasi realisasi biaya</p>
            </div>
            <span class="badge-clean bg-slate-100 text-slate-600 text-xs">Wajib Lampiran Invoice</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. Invoice</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">No. PO Ref</th>
                        <th class="py-3 px-4 text-right">Nilai Tagihan (Rp)</th>
                        <th class="py-3 px-4 text-center">Lampiran</th>
                        <th class="py-3 px-4">Divalidasi Oleh</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="py-3 px-5 font-bold font-mono text-slate-800">{{ $inv->invoice_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $inv->supplier->name }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $inv->purchaseOrder?->po_number ?? '-' }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">Rp {{ number_format($inv->amount, 2, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($inv->attachment_path)
                                    <button type="button" 
                                            onclick="openDocPreview('{{ asset('storage/' . $inv->attachment_path) }}', 'Faktur Tagihan (Invoice): {{ addslashes($inv->invoice_number) }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Faktur Scan
                                    </button>
                                @else
                                    <span class="text-rose-500 text-[10px] font-bold">Tanpa Bukti</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $inv->validator->name }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">Tervalidasi</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada data Faktur / Invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: PURCHASE ORDERS -->
    <div x-show="activeTab === 'po'" class="card-clean overflow-hidden" x-cloak>
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <h3 class="text-sm font-bold text-slate-900">Daftar Purchase Orders (PO) Proyek</h3>
            <span class="text-xs text-slate-500">Total: {{ $purchaseOrders->count() }} PO</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. PO</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Catatan</th>
                        <th class="py-3 px-4 text-right">Total Nilai (Rp)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td class="py-3 px-5 font-mono font-bold text-slate-800">{{ $po->po_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ \Carbon\Carbon::parse($po->po_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $po->supplier->name }}</td>
                            <td class="py-3 px-4 text-slate-500 max-w-xs truncate">{{ $po->notes ?: '-' }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rp {{ number_format($po->total_amount, 2, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] uppercase">{{ $po->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data Purchase Order (PO).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: INPUT DELIVERY ORDER (DO) -->
    <div x-show="showDoModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDoModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop
                 x-data="{
                    materials: {{ Js::from($materials->map(fn($m) => [
                        'id' => $m->id,
                        'code' => $m->code,
                        'name' => $m->name,
                        'default_unit_id' => $m->default_unit_id,
                        'unit_code' => $m->defaultUnit?->code ?? '',
                    ])) }},
                    units: {{ Js::from($units->map(fn($u) => [
                        'id' => $u->id,
                        'code' => $u->code,
                    ])) }},
                    openMaterialIdx: null,
                    openUnitIdx: null,
                    materialSearch: '',
                    items: [{ material_id: '', qty_received: 1, unit_id: '' }],
                    addItem() { 
                        this.items.push({ material_id: '', qty_received: 1, unit_id: '' }); 
                    },
                    removeItem(idx) { 
                        if (this.items.length > 1) this.items.splice(idx, 1);
                        if (this.openMaterialIdx === idx) this.openMaterialIdx = null;
                        if (this.openUnitIdx === idx) this.openUnitIdx = null;
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
                        return this.materials.filter(m => 
                            (m.code && m.code.toLowerCase().includes(q)) || 
                            (m.name && m.name.toLowerCase().includes(q))
                        );
                    }
                 }">
                <form action="{{ route('procurement.do.store') }}" method="POST" enctype="multipart/form-data" @submit="if (items.some(it => !it.material_id)) { alert('Mohon pilih material untuk setiap baris.'); $event.preventDefault(); }">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1">Pengawas Lapangan</span>
                            <h3 class="text-sm font-bold text-slate-900">Input Surat Jalan (DO) Diterima</h3>
                        </div>
                        <button type="button" @click="showDoModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nomor Surat Jalan (DO) <span class="text-rose-500">*</span></label>
                                <input type="text" name="do_number" required placeholder="Mis. DO/BPS/2026-0891" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Terima <span class="text-rose-500">*</span></label>
                                <input type="date" name="do_date" value="{{ date('Y-m-d') }}" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Supplier <span class="text-rose-500">*</span></label>
                                <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Referensi PO (Opsional)</label>
                                <select name="purchase_order_id" class="w-full select-clean p-2.5 bg-white text-slate-800">
                                    <option value="">-- Tanpa PO / Langsung --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Mandatory Attachment -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Foto / Scan Bukti Surat Jalan Fisik <span class="text-rose-500 font-bold">* Wajib</span></label>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required 
                                   class="w-full p-2 text-xs bg-slate-50 border border-slate-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <span class="text-[10px] text-slate-400 mt-1 block">Wajib melampirkan foto/scan bertanda tangan asli pengawas & driver.</span>
                        </div>

                        <!-- Dynamic Items Received -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="font-bold text-slate-800">Daftar Material yang Diterima:</label>
                                <button type="button" @click="addItem()" class="text-xs font-bold text-blue-600 hover:text-blue-800">+ Tambah Baris Material</button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(it, idx) in items" :key="idx">
                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-12 gap-2 items-center"
                                         :style="openMaterialIdx === idx || openUnitIdx === idx ? 'position: relative; z-index: 40;' : 'position: relative; z-index: 10;'">
                                        <!-- Material Custom Dropdown -->
                                        <div class="col-span-6 relative">
                                            <input type="hidden" :name="'items[' + idx + '][material_id]'" :value="it.material_id">
                                            <button type="button" 
                                                    @click="openMaterialIdx = (openMaterialIdx === idx ? null : idx); openUnitIdx = null; materialSearch = ''; if (openMaterialIdx === idx) $nextTick(() => $refs['doMatSearch_' + idx]?.focus())"
                                                    class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-xl shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-2 px-2.5 text-xs transition-all cursor-pointer">
                                                <span class="truncate block flex-1 font-semibold" :class="it.material_id ? 'text-slate-800' : 'text-slate-400'" 
                                                      x-text="getMaterialLabel(it.material_id)"></span>
                                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1.5 transition-transform duration-200" 
                                                     :class="openMaterialIdx === idx ? 'rotate-180 text-blue-600' : ''"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>

                                            <!-- Floating Card -->
                                            <div x-show="openMaterialIdx === idx" 
                                                 @click.outside="if (openMaterialIdx === idx) openMaterialIdx = null"
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                 class="absolute left-0 top-full mt-1.5 w-[380px] max-w-[90vw] bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                 style="display: none;">
                                                <div class="p-2 border-b border-slate-100 flex-shrink-0">
                                                    <input type="text" 
                                                           :x-ref="'doMatSearch_' + idx"
                                                           x-model="materialSearch" 
                                                           @click.stop
                                                           placeholder="Cari material..."
                                                           class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                                                </div>
                                                <div class="overflow-y-auto max-h-56 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                    <template x-for="mat in filteredMaterials()" :key="mat.id">
                                                        <div @click="selectMaterial(idx, mat)" 
                                                             class="px-3 py-2 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                             :class="it.material_id == mat.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                            <span class="truncate flex-1" x-text="mat.name + (mat.unit_code ? ' (' + mat.unit_code + ')' : '')"></span>
                                                            <template x-if="it.material_id == mat.id">
                                                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredMaterials().length === 0">
                                                        <div class="px-3 py-4 text-xs text-center text-slate-400 italic">
                                                            Tidak ada material yang cocok
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Qty Received -->
                                        <div class="col-span-3">
                                            <input type="number" step="any" :name="'items[' + idx + '][qty_received]'" 
                                                   x-model="it.qty_received"
                                                   placeholder="Kuantiti" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <!-- Satuan Custom Dropdown -->
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
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                 class="absolute left-0 top-full mt-1.5 w-36 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
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

                                        <!-- Remove Button -->
                                        <div class="col-span-1 text-center">
                                            <button type="button" @click="removeItem(idx)" class="p-1 text-slate-400 hover:text-rose-600 font-bold text-sm" title="Hapus baris">&times;</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showDoModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Surat Jalan (DO)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: INPUT INVOICE -->
    <div x-show="showInvModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showInvModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop>
                <form action="{{ route('procurement.invoice.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <span class="badge-clean bg-purple-100 text-purple-800 text-[10px] mb-1">Purchasing Officer</span>
                            <h3 class="text-sm font-bold text-slate-900">Input Tagihan & Faktur (Invoice)</h3>
                        </div>
                        <button type="button" @click="showInvModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nomor Faktur / Invoice <span class="text-rose-500">*</span></label>
                            <input type="text" name="invoice_number" required placeholder="Mis. INV/2026/BPS/0442" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Invoice <span class="text-rose-500">*</span></label>
                                <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Supplier <span class="text-rose-500">*</span></label>
                                <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Total Nilai Tagihan (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" step="any" name="amount" required placeholder="Mis. 132600000" 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Scan Dokumen Invoice / Faktur Pajak <span class="text-rose-500 font-bold">* Wajib</span></label>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required 
                                   class="w-full p-2 text-xs bg-slate-50 border border-slate-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan</label>
                            <textarea name="notes" rows="2" placeholder="Catatan tagihan atau termin pembayaran..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showInvModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: BUAT PURCHASE ORDER (PO) BARU -->
    <div x-show="showPoModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPoModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-2xl p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
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
                            <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] mb-1 font-bold">Purchasing Officer</span>
                            <h3 class="text-base font-extrabold text-slate-900">Buat Purchase Order (PO) Baru</h3>
                            <p class="text-xs text-slate-500">Penerbitan pesanan pembelian material resmi untuk rekanan vendor</p>
                        </div>
                        <button type="button" @click="showPoModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nomor Purchase Order (PO) <span class="text-rose-500">*</span></label>
                                <input type="text" name="po_number" required value="PO/{{ date('Y') }}/BR/{{ str_pad($purchaseOrders->count() + 1, 3, '0', STR_PAD_LEFT) }}"
                                       placeholder="Mis. PO/2026/BR/002" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono font-bold text-slate-800">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tanggal PO <span class="text-rose-500">*</span></label>
                                <input type="date" name="po_date" value="{{ date('Y-m-d') }}" required 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Supplier Rekanan <span class="text-rose-500">*</span></label>
                                <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white font-semibold text-slate-800 cursor-pointer">
                                    <option value="">-- Pilih Supplier Rekanan --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->contact_person ?? 'PIC' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Catatan / Instruksi Pengiriman</label>
                                <input type="text" name="notes" placeholder="Mis. Pengiriman bertahap Franco proyek..." 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Dynamic Materials Ordered -->
                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-100">
                                <label class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Item Material Dipesan:</label>
                                <button type="button" @click="addPoItem()" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Baris Material
                                </button>
                            </div>

                            <div class="space-y-2.5">
                                <template x-for="(it, idx) in poItems" :key="idx">
                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-12 gap-2 items-center"
                                         :style="openMaterialIdx === idx || openUnitIdx === idx ? 'position: relative; z-index: 40;' : 'position: relative; z-index: 10;'">
                                        <!-- Material Custom Dropdown -->
                                        <div class="col-span-5 relative">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Material <span class="text-rose-500">*</span></label>
                                            <input type="hidden" :name="'items[' + idx + '][material_id]'" :value="it.material_id">
                                            
                                            <button type="button" 
                                                    @click="openMaterialIdx = (openMaterialIdx === idx ? null : idx); openUnitIdx = null; materialSearch = ''; if (openMaterialIdx === idx) $nextTick(() => $refs['poMatSearch_' + idx]?.focus())"
                                                    class="w-full flex items-center justify-between text-left font-semibold bg-white border border-slate-300 rounded-xl shadow-xs text-slate-800 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 py-2 px-2.5 text-xs transition-all cursor-pointer">
                                                <span class="truncate block flex-1 font-semibold" :class="it.material_id ? 'text-slate-800' : 'text-slate-400'" 
                                                      x-text="getMaterialLabel(it.material_id)"></span>
                                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-1.5 transition-transform duration-200" 
                                                     :class="openMaterialIdx === idx ? 'rotate-180 text-blue-600' : ''"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>

                                            <!-- Floating Card -->
                                            <div x-show="openMaterialIdx === idx" 
                                                 @click.outside="if (openMaterialIdx === idx) openMaterialIdx = null"
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                 class="absolute left-0 top-full mt-1.5 w-[380px] max-w-[90vw] bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
                                                 style="display: none;">
                                                <div class="p-2 border-b border-slate-100 flex-shrink-0">
                                                    <input type="text" 
                                                           :x-ref="'poMatSearch_' + idx"
                                                           x-model="materialSearch" 
                                                           @click.stop
                                                           placeholder="Cari kode atau nama material..."
                                                           class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                                                </div>
                                                <div class="overflow-y-auto max-h-56 px-1 py-0.5 space-y-0.5 custom-scrollbar">
                                                    <template x-for="mat in filteredMaterials()" :key="mat.id">
                                                        <div @click="selectMaterial(idx, mat)" 
                                                             class="px-3 py-2 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors"
                                                             :class="it.material_id == mat.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'">
                                                            <div class="truncate flex-1">
                                                                <span class="font-mono text-[11px] font-bold text-slate-500 mr-1.5" x-text="mat.code"></span>
                                                                <span x-text="mat.name"></span>
                                                            </div>
                                                            <template x-if="it.material_id == mat.id">
                                                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                </svg>
                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredMaterials().length === 0">
                                                        <div class="px-3 py-4 text-xs text-center text-slate-400 italic">
                                                            Tidak ada material yang cocok
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Qty Pesan -->
                                        <div class="col-span-2">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Qty Pesan</label>
                                            <input type="number" step="any" :name="'items[' + idx + '][qty_ordered]'" 
                                                   x-model="it.qty_ordered"
                                                   placeholder="Qty" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <!-- Satuan Custom Dropdown -->
                                        <div class="col-span-2 relative">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Satuan <span class="text-rose-500">*</span></label>
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
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-98"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-98"
                                                 class="absolute left-0 top-full mt-1.5 w-36 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 flex flex-col"
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

                                        <!-- Harga (Rp) -->
                                        <div class="col-span-2">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Harga (Rp)</label>
                                            <input type="number" step="any" :name="'items[' + idx + '][unit_price]'" 
                                                   x-model="it.unit_price"
                                                   placeholder="Rp" required class="w-full p-2 bg-white border border-slate-200 rounded-xl text-xs font-mono font-semibold focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <!-- Remove Button -->
                                        <div class="col-span-1 text-center pt-3">
                                            <button type="button" @click="removePoItem(idx)" class="p-1 text-slate-400 hover:text-rose-600 font-bold text-sm" title="Hapus baris">&times;</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Live Grand Total Box -->
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200/80 rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-900 uppercase">Estimasi Total Nilai PO:</span>
                                <span class="text-sm font-black font-mono text-blue-900" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal)"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="showPoModal = false" class="px-4 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan & Terbitkan PO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
