@extends('layouts.app')

@section('title', 'Faktur Tagihan (Invoice) — ' . $project->name)
@section('page_title', 'Faktur Tagihan (Invoice)')
@section('page_subtitle', 'Verifikasi berkas tagihan supplier, nominal faktur pajak, dan validasi termin pembayaran')

@section('content')
<div class="space-y-6" x-data="{
    showInvModal: false
}">

    <!-- Sub-Navigasi Modul & Quick Actions -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <!-- Tab Navigation antara PO, DO, dan Invoice -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl tab-scroll-container overflow-x-auto max-w-full">
            <!-- Tab PO (Jika memiliki izin PO) -->
            @if(auth()->user()?->canReadPo())
                <a href="{{ route('procurement.po.index') }}" 
                   class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Purchase Order (PO)</span>
                    <span class="px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700 rounded-full font-mono">{{ $poCount }}</span>
                </a>
            @endif

            <!-- Tab DO (Jika memiliki izin DO) -->
            @if(auth()->user()?->canReadDo())
                <a href="{{ route('procurement.do.index') }}" 
                   class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Surat Jalan (DO Lapangan)</span>
                    <span class="px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700 rounded-full font-mono">{{ $doCount }}</span>
                </a>
            @endif

            <!-- Tab Invoice (Active) -->
            <a href="{{ route('procurement.invoices.index') }}" 
               class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 bg-white shadow-sm text-blue-700 font-bold">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Faktur Tagihan (Invoice)</span>
                <span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 rounded-full font-mono">{{ $invoices->count() }}</span>
            </a>
        </div>

        <!-- Action / Permission Status -->
        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWriteInvoice())
                <button type="button" @click="showInvModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Input Invoice (Tagihan)</span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Mode Lihat Saja (Read-Only)</span>
                </span>
            @endif
        </div>
    </div>

    <!-- SUMMARY METRICS -->
    @php
        $totalTagihan = $invoices->sum('amount');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card-clean p-4 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Faktur</span>
                <span class="text-lg font-extrabold text-slate-900 font-mono">{{ $invoices->count() }} Dokumen</span>
            </div>
        </div>

        <div class="card-clean p-4 flex items-center gap-3.5 sm:col-span-2">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Akumulasi Tagihan</span>
                <span class="text-lg font-extrabold text-blue-700 font-mono">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- DAFTAR FAKTUR TAGIHAN (INVOICE) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Arsip Faktur Tagihan (Invoice) Vendor</h3>
                <p class="text-xs text-slate-500">Berkas tagihan resmi dari supplier beserta bukti scan fisik untuk rekonsiliasi pembayaran</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-clean bg-purple-50 text-purple-700 border border-purple-200 text-xs">Wajib Lampiran Faktur</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean min-w-[850px]">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">No. Faktur / Invoice</th>
                        <th class="py-3 px-4">Tanggal Faktur</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">No. PO Ref</th>
                        <th class="py-3 px-5 text-right">Nilai Tagihan (Rp)</th>
                        <th class="py-3 px-4 text-center">Bukti Faktur</th>
                        <th class="py-3 px-4">Validator</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-5 font-bold font-mono text-purple-700">{{ $inv->invoice_number }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $inv->supplier?->name ?? 'Vendor' }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $inv->purchaseOrder?->po_number ?? '-' }}</td>
                            <td class="py-3 px-5 font-mono font-extrabold text-slate-900 text-right">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($inv->attachment_path)
                                    <button type="button" 
                                            onclick="openDocPreview('{{ asset('storage/' . $inv->attachment_path) }}', 'Faktur Tagihan (Invoice): {{ addslashes($inv->invoice_number) }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lihat Faktur</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tanpa lampiran</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $inv->validator?->name ?? 'Purchasing' }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">Tervalidasi Purchasing</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Belum Ada Faktur Tagihan (Invoice)</div>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    @if(auth()->user()?->canWriteInvoice())
                                        Klik tombol "Input Invoice (Tagihan)" untuk menginput tagihan vendor dan melampirkan berkas bukti faktur asli.
                                    @else
                                        Akun Anda memiliki izin Lihat Saja. Belum ada Faktur Tagihan yang dicatat oleh Purchasing Officer.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: INPUT INVOICE (Hanya jika memiliki izin Write) -->
    @if(auth()->user()?->canWriteInvoice())
        <div x-show="showInvModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showInvModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
                <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                     @click.stop>
                    <form action="{{ route('procurement.invoice.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div>
                                <span class="badge-clean bg-purple-100 text-purple-800 text-[10px] mb-1 font-bold">Purchasing / Keuangan</span>
                                <h3 class="text-base font-extrabold text-slate-900">Input Tagihan &amp; Faktur (Invoice)</h3>
                                <p class="text-xs text-slate-500">Mencatat nomor faktur, nominal tagihan, dan bukti berkas</p>
                            </div>
                            <button type="button" @click="showInvModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nomor Faktur / Invoice <span class="text-rose-500">*</span></label>
                                <input type="text" name="invoice_number" required placeholder="Mis. INV/2026/BPS/0442" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tanggal Invoice <span class="text-rose-500">*</span></label>
                                    <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Supplier <span class="text-rose-500">*</span></label>
                                    <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                        <option value="">-- Pilih Supplier --</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Referensi Purchase Order (PO)</label>
                                <select name="purchase_order_id" class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                    <option value="">-- Tanpa PO (Pembelian Langsung) --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->supplier?->name ?? 'Vendor' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Total Nilai Tagihan (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" name="amount" required placeholder="Mis. 132600000" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Scan Dokumen Invoice / Faktur Pajak <span class="text-rose-500 font-bold">* Wajib</span></label>
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required 
                                       class="w-full p-2 text-xs bg-slate-50 border border-slate-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <span class="text-[10px] text-slate-400 mt-1 block">Wajib unggah scan faktur asli atau faktur pajak dari supplier (PDF, JPG, PNG maks 10MB)</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Catatan Termin / Keterangan Pembayaran</label>
                                <textarea name="notes" rows="2" placeholder="Catatan tagihan, rekening transfer, termin pelunasan..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="showInvModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Faktur Tagihan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
