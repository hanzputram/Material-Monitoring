@extends('layouts.app')

@section('title', 'Uang Muka Pembelian (DP) — ' . ($project ? $project->name : 'Keuangan'))
@section('page_title', 'Uang Muka Pembelian')
@section('page_subtitle', 'Pencatatan uang muka pembelian (DP) ke supplier atau toko sebelum tagihan faktur terbit')

@section('content')
<div class="space-y-6" x-data="{
    showDpModal: false
}">

    <!-- Sub-Navigasi Modul 6 Tab & Quick Action -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'down_payment'])

        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWriteDownPayment())
                <button type="button" @click="showDpModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Catat Uang Muka (DP)</span>
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
        <!-- 1. Total Uang Muka -->
        <div class="card-clean p-4 border-l-4 border-l-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total DP Dicatat</span>
                    <p class="text-xl font-black text-slate-900 mt-1 font-mono">
                        Rp {{ number_format($stats['total_dp_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                {{ $stats['total_count'] }} transaksi pembayaran DP
            </div>
        </div>

        <!-- 2. DP Siap Diaplikasikan -->
        <div class="card-clean p-4 border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">DP Siap Digunakan</span>
                    <p class="text-xl font-black text-emerald-600 mt-1 font-mono">
                        Rp {{ number_format($stats['available_dp_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Dapat dipotongkan ke faktur baru
            </div>
        </div>

        <!-- 3. DP Teraplikasi ke Faktur -->
        <div class="card-clean p-4 border-l-4 border-l-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">DP Telah Terpotong</span>
                    <p class="text-xl font-black text-indigo-600 mt-1 font-mono">
                        Rp {{ number_format($stats['applied_dp_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Telah memotong beban faktur vendor
            </div>
        </div>

        <!-- 4. Ringkasan Proyek Aktif -->
        <div class="card-clean p-4 border-l-4 border-l-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Proyek Terkait</span>
                    <p class="text-base font-bold text-slate-900 mt-1 truncate max-w-[180px]">
                        {{ $project->name }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">
                Tahun Anggaran {{ $project->budget_year }}
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR UANG MUKA PEMBELIAN -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Uang Muka Pembelian (DP)</h3>
                <p class="text-xs text-slate-500">Catatan pembayaran down payment resmi kepada toko / rekanan pada proyek aktif</p>
            </div>
        </div>

        @if($downPayments->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Belum Ada Uang Muka Pembelian</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Belum ada transaksi pembayaran uang muka yang dicatat untuk proyek ini.</p>
                @if(auth()->user()?->canWriteDownPayment())
                    <button type="button" @click="showDpModal = true" class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Catat Uang Muka Pertama</span>
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[900px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">No. Bukti / DP</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Toko / Supplier</th>
                            <th class="py-3 px-4">Referensi PO</th>
                            <th class="py-3 px-4">Metode Bayar</th>
                            <th class="py-3 px-4 text-right">Nominal DP</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Bukti Bayar</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($downPayments as $dp)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-mono font-bold text-blue-700">{{ $dp->dp_number }}</div>
                                    @if($dp->creator)
                                        <span class="text-[10px] text-slate-400">Oleh: {{ $dp->creator->name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $dp->dp_date ? $dp->dp_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $dp->supplier?->name ?? 'Non-Supplier' }}</div>
                                    @if($dp->supplier?->phone)
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $dp->supplier->phone }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($dp->purchaseOrder)
                                        <span class="px-2 py-0.5 font-mono text-[11px] font-bold bg-blue-50 text-blue-700 rounded border border-blue-200">
                                            {{ $dp->purchaseOrder->po_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">Tanpa PO</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-700">{{ $dp->payment_method }}</span>
                                    @if($dp->bank_name)
                                        <span class="text-[10px] text-slate-400 block">{{ $dp->bank_name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    Rp {{ number_format($dp->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($dp->status === 'applied')
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 inline-flex items-center gap-1">
                                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Terpotong Faktur
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Siap Digunakan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($dp->attachment_path)
                                        <a href="{{ route('storage.fallback', $dp->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Bukti Kuitansi
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if(auth()->user()?->canWriteDownPayment() && $dp->status !== 'applied')
                                        <form action="{{ route('finance.down-payments.destroy', $dp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Uang Muka {{ $dp->dp_number }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Uang Muka">
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

    <!-- MODAL CATAT UANG MUKA PEMBELIAN (DP) -->
    <div x-show="showDpModal" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" 
         style="display: none;">
        
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showDpModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Catat Uang Muka Pembelian (DP)</h3>
                        <p class="text-xs text-slate-500">Proyek: <span class="font-semibold text-slate-700">{{ $project->name }}</span></p>
                    </div>
                </div>
                <button type="button" @click="showDpModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('finance.down-payments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <!-- Supplier -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Supplier / Toko Penerima DP <span class="text-red-500">*</span></label>
                    <select name="supplier_id" required class="select-clean w-full text-xs font-semibold py-2.5 pl-3">
                        <option value="">-- Pilih Rekanan / Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} {{ $s->phone ? "({$s->phone})" : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Referensi Purchase Order (Opsional) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Terkait Pesanan Pembelian / PO (Opsional)</label>
                    <select name="purchase_order_id" class="select-clean w-full text-xs py-2.5 pl-3">
                        <option value="">-- Tanpa PO (Uang Muka Pembelian Langsung) --</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}">PO {{ $po->po_number }} - {{ $po->supplier?->name }} (Rp {{ number_format($po->total_amount, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- No. DP & Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">No. Bukti / Kuitansi DP <span class="text-red-500">*</span></label>
                        <input type="text" name="dp_number" required placeholder="Contoh: DP-2026/001" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran DP <span class="text-red-500">*</span></label>
                        <input type="date" name="dp_date" required value="{{ date('Y-m-d') }}" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Nominal DP & Metode Bayar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nominal Uang Muka (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="1" name="amount" required placeholder="0" class="w-full text-xs font-mono font-bold border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Metode Bayar <span class="text-red-500">*</span></label>
                        <select name="payment_method" required class="select-clean w-full text-xs py-2.5 pl-3">
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Kas / Tunai">Kas / Tunai</option>
                            <option value="Cek / Giro">Cek / Giro</option>
                        </select>
                    </div>
                </div>

                <!-- Bank Name & Bukti Lampiran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Bank / Rekening (Opsional)</label>
                        <input type="text" name="bank_name" placeholder="Misal: BCA Rek. 123456" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Bukti Kuitansi / Transfer</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan peruntukan uang muka atau perjanjian retensi/termin..." class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showDpModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        Simpan Uang Muka
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
