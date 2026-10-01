@extends('layouts.app')

@section('title', 'Pembayaran Pembelian — ' . ($project ? $project->name : 'Keuangan'))
@section('page_title', 'Pembayaran Pembelian')
@section('page_subtitle', 'Pencatatan realisasi pelunasan tagihan faktur pembelian kepada pihak toko dan supplier rekanan')

@section('content')
<div class="space-y-6" x-data="{
    showPaymentModal: false,
    selectedSupplierId: '',
    invoices: {{ Js::from($unpaidInvoices) }},
    paymentItems: {},
    init() {
        this.$watch('selectedSupplierId', (newVal) => {
            this.paymentItems = {};
            if (newVal) {
                this.invoices.filter(inv => inv.supplier_id == newVal).forEach(inv => {
                    const remaining = Math.max(0, parseFloat(inv.amount || 0) - parseFloat(inv.down_payment_amount || 0) - parseFloat(inv.paid_amount || 0));
                    this.paymentItems[inv.id] = {
                        selected: false,
                        amount: remaining,
                        max: remaining
                    };
                });
            }
        });
    },
    get filteredInvoices() {
        if (!this.selectedSupplierId) return [];
        return this.invoices.filter(inv => inv.supplier_id == this.selectedSupplierId);
    },
    calculateTotal() {
        let total = 0;
        for (let id in this.paymentItems) {
            if (this.paymentItems[id].selected) {
                total += parseFloat(this.paymentItems[id].amount || 0);
            }
        }
        return total;
    },
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num || 0);
    }
}">

    <!-- Sub-Navigasi Modul 6 Tab & Quick Action -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'payment'])

        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWritePayment())
                <button type="button" @click="showPaymentModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Catat Pembayaran</span>
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
        <!-- 1. Total Pembayaran Realisasi -->
        <div class="card-clean p-4 border-l-4 border-l-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pembayaran</span>
                    <p class="text-xl font-black text-slate-900 mt-1 font-mono">
                        Rp {{ number_format($stats['total_payments_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                {{ $stats['total_payments_count'] }} transaksi pembayaran keluar
            </div>
        </div>

        <!-- 2. Sisa Hutang Belum Lunas -->
        <div class="card-clean p-4 border-l-4 {{ $stats['outstanding_amount'] > 0 ? 'border-l-amber-500' : 'border-l-emerald-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sisa Hutang Faktur</span>
                    <p class="text-xl font-black {{ $stats['outstanding_amount'] > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1 font-mono">
                        Rp {{ number_format($stats['outstanding_amount'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl {{ $stats['outstanding_amount'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                {{ $stats['unpaid_count'] }} faktur menunggu pelunasan
            </div>
        </div>

        <!-- 3. Total Tagihan Net -->
        <div class="card-clean p-4 border-l-4 border-l-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Tagihan (Net DP)</span>
                    <p class="text-xl font-black text-indigo-600 mt-1 font-mono">
                        Rp {{ number_format($stats['total_invoiced_net'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium">
                Total beban tagihan sah proyek
            </div>
        </div>

        <!-- 4. Status Pelunasan Proyek -->
        <div class="card-clean p-4 border-l-4 border-l-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Proyek Aktif</span>
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

    <!-- TABEL DAFTAR PEMBAYARAN PEMBELIAN -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Riwayat Pembayaran Pembelian</h3>
                <p class="text-xs text-slate-500">Daftar transaksi pembayaran dan pelunasan faktur tagihan supplier pada proyek aktif</p>
            </div>
        </div>

        @if($payments->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Belum Ada Transaksi Pembayaran</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Belum ada realisasi pembayaran faktur pembelian yang dicatat untuk proyek ini.</p>
                @if(auth()->user()?->canWritePayment())
                    <button type="button" @click="showPaymentModal = true" class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Catat Pembayaran Pertama</span>
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[1050px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-[160px]">No. Pembayaran</th>
                            <th class="py-3 px-4 w-[110px]">Tanggal</th>
                            <th class="py-3 px-4 w-[200px]">Toko / Supplier</th>
                            <th class="py-3 px-4 w-[140px]">Metode Bayar</th>
                            <th class="py-3 px-4">Faktur Terkait</th>
                            <th class="py-3 px-4 text-right w-[150px]">Total Bayar</th>
                            <th class="py-3 px-4 text-center w-[120px]">Bukti Transfer</th>
                            <th class="py-3 px-4 text-right w-[70px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payments as $pay)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-mono font-bold text-blue-700">{{ $pay->payment_number }}</div>
                                    @if($pay->creator)
                                        <span class="text-[10px] text-slate-400">Oleh: {{ $pay->creator->name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $pay->supplier?->name ?? 'Non-Supplier' }}</div>
                                    @if($pay->supplier?->phone)
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $pay->supplier->phone }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-700">{{ $pay->payment_method }}</span>
                                    @if($pay->bank_name)
                                        <span class="text-[10px] text-slate-400 block">{{ $pay->bank_name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($pay->items as $item)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] bg-slate-100 text-slate-700 rounded border border-slate-200 whitespace-nowrap" title="Dibayar: Rp {{ number_format($item->amount_paid, 0, ',', '.') }}">
                                                <span class="font-mono font-bold">{{ $item->invoice?->invoice_number ?? '-' }}</span>
                                                <span class="text-slate-400 font-mono">(Rp {{ number_format($item->amount_paid, 0, ',', '.') }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                    Rp {{ number_format($pay->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($pay->attachment_path)
                                        <a href="{{ route('storage.fallback', $pay->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Bukti Transfer
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if(auth()->user()?->canWritePayment())
                                        <form action="{{ route('finance.payments.destroy', $pay->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Pembayaran {{ $pay->payment_number }}? Status faktur terkait akan dikembalikan.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Pembayaran">
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

    <!-- MODAL CATAT PEMBAYARAN PEMBELIAN -->
    <div x-show="showPaymentModal" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" 
         style="display: none;">
        
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4" @click.away="showPaymentModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Catat Pembayaran Pembelian</h3>
                        <p class="text-xs text-slate-500">Pilih supplier dan checklist faktur yang ingin dilunasi</p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('finance.payments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <!-- Supplier Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Supplier / Rekanan Vendor <span class="text-red-500">*</span></label>
                    <select name="supplier_id" x-model="selectedSupplierId" required class="select-clean w-full text-xs font-semibold py-2.5 pl-3">
                        <option value="">-- Pilih Supplier yang akan Dibayar --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- No. Bukti Bayar & Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">No. Bukti Pembayaran / Voucher <span class="text-red-500">*</span></label>
                        <input type="text" name="payment_number" required placeholder="Contoh: BKK-2026/001" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- DAFTAR FAKTUR YANG BELUM LUNAS DARI SUPPLIER INI -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">Daftar Faktur yang Dibayar <span class="text-red-500">*</span></label>
                    
                    <div x-show="!selectedSupplierId" class="p-4 bg-slate-50 rounded-xl text-center text-xs text-slate-500 border border-dashed border-slate-200">
                        Silakan pilih supplier terlebih dahulu untuk menampilkan daftar faktur tagihan yang belum lunas.
                    </div>

                    <div x-show="selectedSupplierId && filteredInvoices.length === 0" class="p-4 bg-emerald-50 rounded-xl text-center text-xs text-emerald-700 border border-emerald-200 font-medium">
                        Tidak ada tagihan tertunggak untuk supplier ini pada proyek ini (Semua faktur telah lunas).
                    </div>

                    <div x-show="selectedSupplierId && filteredInvoices.length > 0" class="border border-slate-200 rounded-xl overflow-hidden max-h-56 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase">
                                <tr>
                                    <th class="py-2 px-3 w-10 text-center">Pilih</th>
                                    <th class="py-2 px-3">No. Faktur</th>
                                    <th class="py-2 px-3 text-right">Net Tagihan</th>
                                    <th class="py-2 px-3 text-right">Sisa Hutang</th>
                                    <th class="py-2 px-3 text-right w-36">Alokasi Bayar (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="inv in filteredInvoices" :key="inv.id">
                                    <tr class="hover:bg-blue-50/40">
                                        <td class="py-2 px-3 text-center">
                                            <input type="checkbox" x-model="paymentItems[inv.id].selected" class="rounded text-blue-600 focus:ring-blue-500">
                                            <input type="hidden" :name="'items[' + inv.id + '][invoice_id]'" :value="inv.id" :disabled="!paymentItems[inv.id].selected">
                                        </td>
                                        <td class="py-2 px-3">
                                            <span class="font-mono font-bold text-slate-800" x-text="inv.invoice_number"></span>
                                            <span class="text-[10px] text-slate-400 block" x-text="inv.invoice_date"></span>
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono text-slate-600" x-text="'Rp ' + formatRupiah(inv.amount - (inv.down_payment_amount || 0))"></td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-amber-600" x-text="'Rp ' + formatRupiah(paymentItems[inv.id].max)"></td>
                                        <td class="py-2 px-3 text-right">
                                            <input type="number" 
                                                   step="any" 
                                                   min="1" 
                                                   :max="paymentItems[inv.id].max" 
                                                   :name="'items[' + inv.id + '][amount_paid]'" 
                                                   x-model.number="paymentItems[inv.id].amount" 
                                                   :disabled="!paymentItems[inv.id].selected" 
                                                   class="w-full text-right font-mono text-xs border border-slate-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-100 disabled:text-slate-400">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Ringkasan Total Alokasi -->
                    <div x-show="selectedSupplierId && filteredInvoices.length > 0" class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Total Pembayaran Diinput:</span>
                        <span class="text-base font-black font-mono text-blue-700" x-text="'Rp ' + formatRupiah(calculateTotal())"></span>
                    </div>
                </div>

                <!-- Metode Bayar & Bank Name -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Metode Bayar <span class="text-red-500">*</span></label>
                        <select name="payment_method" required class="select-clean w-full text-xs py-2.5 pl-3">
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Kas / Tunai">Kas / Tunai</option>
                            <option value="Cek / Giro">Cek / Giro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Bank / Rekening Pengirim</label>
                        <input type="text" name="bank_name" placeholder="Misal: Mandiri Rek. Operasional" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Unggah Bukti & Catatan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Bukti Transfer / Slip</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" placeholder="Catatan opsional..." class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" :disabled="calculateTotal() <= 0" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
