@extends('layouts.app')

@section('title', 'Retur Pembelian — ' . ($project ? $project->name : 'Pengadaan'))
@section('page_title', 'Retur Pembelian')
@section('page_subtitle', 'Pengelolaan pengembalian material rusak, cacat, atau berlebih kepada toko/supplier dan penyesuaian kuota realisasi')

@section('content')
<div class="space-y-6" x-data="{
    showReturnModal: false,
    materials: {{ Js::from($materials) }},
    units: {{ Js::from($units) }},
    items: [
        { material_id: '', unit_id: '', qty_returned: 1, unit_price: 0, reason: 'Cacat/Rusak' }
    ],
    addItem() {
        this.items.push({ material_id: '', unit_id: '', qty_returned: 1, unit_price: 0, reason: 'Cacat/Rusak' });
    },
    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },
    onMaterialChange(item) {
        const mat = this.materials.find(m => m.id == item.material_id);
        if (mat && mat.default_unit_id) {
            item.unit_id = mat.default_unit_id;
        }
    }
}">

    <!-- Sub-Navigasi Modul 6 Tab & Quick Action -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'return'])

        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWriteReturn())
                <button type="button" @click="showReturnModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Catat Retur Pembelian</span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Mode Lihat Saja</span>
                </span>
            @endif
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Transaksi Retur -->
        <div class="card-clean p-4 border-l-4 border-l-rose-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Retur Tercatat</span>
                    <p class="text-xl font-black text-slate-900 mt-1 font-mono">
                        {{ $stats['total_returns_count'] }} Transaksi
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-rose-50 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Pengembalian barang ke rekanan
            </div>
        </div>

        <!-- 2. Estimasi Nilai Retur -->
        <div class="card-clean p-4 border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estimasi Nilai Retur</span>
                    <p class="text-xl font-black text-amber-600 mt-1 font-mono">
                        Rp {{ number_format($stats['total_returns_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Kompensasi material diretur
            </div>
        </div>

        <!-- 3. Rincian Kompensasi -->
        <div class="card-clean p-4 border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jenis Kompensasi</span>
                    <div class="mt-1 flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <span>{{ $stats['compensation_potong_tagihan'] }} Potong Faktur</span>
                        <span>•</span>
                        <span>{{ $stats['compensation_ganti_barang'] }} Ganti Barang</span>
                    </div>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                {{ $stats['compensation_pengembalian_dana'] }} Pengembalian Kas
            </div>
        </div>

        <!-- 4. Realisasi Proyek Otomatis -->
        <div class="card-clean p-4 border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sinkronisasi Lapangan</span>
                    <p class="text-xs font-bold text-emerald-700 mt-1 flex items-center gap-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Otomatis Mengurangi Aktual
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">
                Material retur tidak dihitung terpakai
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR RETUR PEMBELIAN -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Riwayat Retur Pembelian</h3>
                <p class="text-xs text-slate-500">Daftar pengembalian material rusak atau berlebih kepada toko/supplier proyek</p>
            </div>
        </div>

        @if($returns->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Belum Ada Retur Pembelian</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Belum ada pengembalian material yang dicatat untuk proyek ini.</p>
                @if(auth()->user()?->canWriteReturn())
                    <button type="button" @click="showReturnModal = true" class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Catat Retur Pertama</span>
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[1100px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-[160px]">No. Retur</th>
                            <th class="py-3 px-4 w-[110px]">Tanggal</th>
                            <th class="py-3 px-4 w-[200px]">Toko / Supplier</th>
                            <th class="py-3 px-4 w-[140px]">Referensi DO</th>
                            <th class="py-3 px-4 w-[140px]">Kompensasi</th>
                            <th class="py-3 px-4">Rincian Material Diretur</th>
                            <th class="py-3 px-4 text-right w-[140px]">Nilai Retur</th>
                            <th class="py-3 px-4 text-center w-[120px]">Bukti Retur</th>
                            <th class="py-3 px-4 text-right w-[70px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($returns as $ret)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-mono font-bold text-rose-700">{{ $ret->return_number }}</div>
                                    @if($ret->creator)
                                        <span class="text-[10px] text-slate-400">Oleh: {{ $ret->creator->name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $ret->return_date ? $ret->return_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $ret->supplier?->name ?? 'Non-Supplier' }}</div>
                                    @if($ret->supplier?->phone)
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $ret->supplier->phone }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($ret->deliveryOrder)
                                        <span class="px-2 py-0.5 font-mono text-[10px] font-bold bg-slate-100 text-slate-700 rounded border border-slate-200">
                                            DO: {{ $ret->deliveryOrder->do_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">Tanpa Ref DO</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($ret->compensation_type === 'potong_tagihan')
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                            Potong Tagihan
                                        </span>
                                    @elseif($ret->compensation_type === 'ganti_barang')
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Ganti Barang
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                            Pengembalian Kas
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="space-y-1">
                                        @foreach($ret->items as $item)
                                            <div class="flex items-center gap-1.5 text-[11px]">
                                                <span class="font-bold text-slate-800">{{ $item->material?->name ?? '-' }}</span>
                                                <span class="font-mono text-rose-600 font-semibold whitespace-nowrap">-{{ (float) $item->qty_returned }} {{ $item->unit?->name }}</span>
                                                @if($item->reason)
                                                    <span class="text-[10px] text-slate-400 italic">({{ $item->reason }})</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format($ret->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($ret->attachment_path)
                                        <a href="{{ route('storage.fallback', $ret->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Surat Retur
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if(auth()->user()?->canWriteReturn())
                                        <form action="{{ route('procurement.returns.destroy', $ret->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Retur {{ $ret->return_number }}? Kuota realisasi lapangan akan disinkronkan kembali.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Retur">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL CATAT RETUR PEMBELIAN -->
    <div x-show="showReturnModal" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" 
         style="display: none;">
        
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4" @click.away="showReturnModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-rose-50 text-rose-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Catat Retur Pembelian</h3>
                        <p class="text-xs text-slate-500">Pengembalian barang ke rekanan vendor / supplier</p>
                    </div>
                </div>
                <button type="button" @click="showReturnModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('procurement.returns.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <!-- Supplier & Referensi DO -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Supplier Penerima Retur <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="select-clean w-full text-xs font-semibold py-2.5 pl-3">
                            <option value="">-- Pilih Rekanan / Supplier --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Referensi Surat Jalan DO (Opsional)</label>
                        <select name="delivery_order_id" class="select-clean w-full text-xs py-2.5 pl-3">
                            <option value="">-- Tanpa DO (Retur Bebas) --</option>
                            @foreach($deliveryOrders as $do)
                                <option value="{{ $do->id }}">DO {{ $do->do_number }} - {{ $do->supplier?->name }} ({{ $do->do_date?->format('d/m/Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- No. Retur, Tanggal, & Jenis Kompensasi -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">No. Bukti / Surat Retur <span class="text-red-500">*</span></label>
                        <input type="text" name="return_number" required placeholder="Contoh: RTR-2026/001" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Retur <span class="text-red-500">*</span></label>
                        <input type="date" name="return_date" required value="{{ date('Y-m-d') }}" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kompensasi <span class="text-red-500">*</span></label>
                        <select name="compensation_type" required class="select-clean w-full text-xs py-2.5 pl-3">
                            <option value="potong_tagihan">Potong Tagihan</option>
                            <option value="ganti_barang">Ganti Barang</option>
                            <option value="pengembalian_dana">Pengembalian Kas</option>
                        </select>
                    </div>
                </div>

                <!-- RINCIAN MATERIAL YANG DIRETUR (DYNAMIC ROWS) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700">Rincian Material Diretur <span class="text-red-500">*</span></label>
                        <button type="button" @click="addItem()" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Tambah Baris</span>
                        </button>
                    </div>

                    <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-12 gap-2 items-center">
                                <!-- Material -->
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Material</label>
                                    <select :name="'items[' + index + '][material_id]'" x-model="item.material_id" @change="onMaterialChange(item)" required class="select-clean w-full text-xs py-1.5 pl-2">
                                        <option value="">-- Pilih Material --</option>
                                        <template x-for="m in materials" :key="m.id">
                                            <option :value="m.id" x-text="m.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Satuan -->
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Satuan</label>
                                    <select :name="'items[' + index + '][unit_id]'" x-model="item.unit_id" required class="select-clean w-full text-xs py-1.5 pl-2">
                                        <template x-for="u in units" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Qty Retur -->
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Qty Retur</label>
                                    <input type="number" step="any" min="0.0001" :name="'items[' + index + '][qty_returned]'" x-model.number="item.qty_returned" required class="w-full text-xs font-mono font-bold border border-slate-200 rounded-lg px-2 py-1.5">
                                </div>

                                <!-- Alasan -->
                                <div class="col-span-4 sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Alasan Retur</label>
                                    <select :name="'items[' + index + '][reason]'" x-model="item.reason" class="select-clean w-full text-xs py-1.5 pl-2">
                                        <option value="Cacat/Rusak">Cacat/Rusak</option>
                                        <option value="Tidak Sesuai Spesifikasi">Spesifikasi Beda</option>
                                        <option value="Kuantiti Berlebih">Kuantiti Lebih</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <!-- Remove button -->
                                <div class="col-span-12 sm:col-span-1 flex justify-end sm:pt-4">
                                    <button type="button" @click="removeItem(index)" class="p-1 text-slate-400 hover:text-red-600 rounded-lg" :disabled="items.length <= 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Unggah Berkas & Catatan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Surat Jalan Retur / BA</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" placeholder="Catatan kondisi fisik material..." class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showReturnModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        Simpan Retur Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
