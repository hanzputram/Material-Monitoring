@extends('layouts.app')

@section('title', 'Faktur Pembelian (Invoice) — ' . $project->name)
@section('page_title', 'Faktur Pembelian (Invoice)')
@section('page_subtitle', 'Verifikasi berkas tagihan supplier, nominal faktur, potongan uang muka, dan validasi pelunasan')

@section('content')
<div class="space-y-6" x-data="{
    showInvModal: false
}">

    <!-- Sub-Navigasi Modul 6 Tab & Quick Action -->
    <div class="card-clean p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        @include('procurement.partials.nav_tabs', ['active' => 'invoice'])

        <!-- Action / Permission Status -->
        <div class="flex items-center gap-2">
            @if(auth()->user()?->canWriteInvoice())
                <button type="button" @click="showInvModal = true" class="w-full sm:w-auto justify-center px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Input Faktur Pembelian</span>
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
        $totalTagihanGross = $invoices->sum('amount');
        $totalDpPotongan = $invoices->sum('down_payment_amount');
        $totalTagihanNet = $invoices->sum(fn($i) => $i->net_amount);
        $totalPaid = $invoices->sum('paid_amount');
        $totalSisaHutang = max(0, $totalTagihanNet - $totalPaid);
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card-clean p-4 border-l-4 border-l-purple-500">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Faktur</span>
            <span class="text-xl font-extrabold text-slate-900 font-mono mt-1 block">{{ $invoices->count() }} Dokumen</span>
            <div class="text-[11px] text-slate-500 mt-1">Bruto: Rp {{ number_format($totalTagihanGross, 0, ',', '.') }}</div>
        </div>

        <div class="card-clean p-4 border-l-4 border-l-indigo-500">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Potongan Uang Muka (DP)</span>
            <span class="text-xl font-extrabold text-indigo-700 font-mono mt-1 block">Rp {{ number_format($totalDpPotongan, 0, ',', '.') }}</span>
            <div class="text-[11px] text-slate-500 mt-1">Mengurangi kewajiban faktur</div>
        </div>

        <div class="card-clean p-4 border-l-4 border-l-blue-500">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Tagihan Net</span>
            <span class="text-xl font-extrabold text-blue-700 font-mono mt-1 block">Rp {{ number_format($totalTagihanNet, 0, ',', '.') }}</span>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">Terbayar: Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
        </div>

        <div class="card-clean p-4 border-l-4 {{ $totalSisaHutang > 0 ? 'border-l-rose-500' : 'border-l-emerald-500' }}">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sisa Hutang Faktur</span>
            <span class="text-xl font-extrabold {{ $totalSisaHutang > 0 ? 'text-rose-600' : 'text-emerald-700' }} font-mono mt-1 block">
                Rp {{ number_format($totalSisaHutang, 0, ',', '.') }}
            </span>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $invoices->whereIn('payment_status', ['unpaid', 'partial'])->count() }} faktur belum lunas
            </div>
        </div>
    </div>

    <!-- DAFTAR FAKTUR PEMBELIAN (INVOICE) -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Arsip Faktur Pembelian (Invoice) Vendor</h3>
                <p class="text-xs text-slate-500">Berkas tagihan resmi supplier dengan pelacakan potongan DP dan status pembayaran</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge-clean bg-purple-50 text-purple-700 border border-purple-200 text-xs">Wajib Lampiran Faktur</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean min-w-[1050px]">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-4 w-[160px]">No. Faktur</th>
                        <th class="py-3 px-4 w-[140px]">Tanggal &amp; Jatuh Tempo</th>
                        <th class="py-3 px-4 w-[200px]">Toko / Supplier</th>
                        <th class="py-3 px-4 w-[130px]">Ref PO</th>
                        <th class="py-3 px-4 text-right w-[140px]">Nilai Bruto</th>
                        <th class="py-3 px-4 text-right w-[130px]">Potongan DP</th>
                        <th class="py-3 px-4 text-right w-[140px]">Net Tagihan</th>
                        <th class="py-3 px-4 text-center w-[130px]">Status Bayar</th>
                        <th class="py-3 px-4 text-center w-[120px]">Bukti Faktur</th>
                        <th class="py-3 px-4 text-right w-[70px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold font-mono text-purple-700">{{ $inv->invoice_number }}</div>
                                @if($inv->validator)
                                    <span class="text-[10px] text-slate-400">Validator: {{ $inv->validator->name }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                <div class="font-medium">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}</div>
                                @if($inv->due_date)
                                    <span class="text-[10px] text-slate-400 block">Jatuh Tempo: {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">{{ $inv->supplier?->name ?? 'Vendor' }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                @if($inv->purchaseOrder)
                                    <span class="px-2 py-0.5 font-mono text-[10px] font-bold bg-blue-50 text-blue-700 rounded border border-blue-200">
                                        {{ $inv->purchaseOrder->po_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Tanpa PO</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-slate-600 text-right whitespace-nowrap">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-indigo-700 text-right whitespace-nowrap">
                                @if($inv->down_payment_amount > 0)
                                    -Rp {{ number_format($inv->down_payment_amount, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-extrabold text-slate-900 text-right whitespace-nowrap">
                                Rp {{ number_format($inv->net_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($inv->payment_status === 'paid')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Lunas
                                    </span>
                                @elseif($inv->payment_status === 'partial')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                        Sebagian (Rp {{ number_format($inv->paid_amount, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                        Belum Dibayar
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($inv->attachment_path)
                                    <a href="{{ route('storage.fallback', $inv->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lihat Faktur</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tanpa lampiran</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if(auth()->user()?->canWriteInvoice() && $inv->paid_amount == 0)
                                    <form action="{{ route('procurement.invoices.destroy', $inv->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Faktur {{ $inv->invoice_number }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Faktur">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="font-bold text-slate-800 text-sm block">Belum Ada Faktur Pembelian</span>
                                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                                    Belum ada faktur pembelian yang tercatat untuk proyek ini.
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
        <div x-show="showInvModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showInvModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
                <div class="relative w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                     @click.stop>
                    <form action="{{ route('procurement.invoice.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div>
                                <span class="badge-clean bg-purple-100 text-purple-800 text-[10px] mb-1 font-bold">Keuangan &amp; Purchasing</span>
                                <h3 class="text-base font-extrabold text-slate-900">Input Faktur Pembelian (Invoice)</h3>
                                <p class="text-xs text-slate-500">Mencatat nomor faktur, nominal, potongan DP, dan berkas asli</p>
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
                                    <label class="block font-bold text-slate-700 mb-1">Jatuh Tempo (Opsional)</label>
                                    <input type="date" name="due_date" 
                                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Supplier / Toko Rekanan <span class="text-rose-500">*</span></label>
                                <select name="supplier_id" required class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Referensi Pesanan Pembelian (PO)</label>
                                <select name="purchase_order_id" class="w-full select-clean p-2.5 bg-white text-slate-800 border border-slate-300 rounded-xl">
                                    <option value="">-- Tanpa Pesanan Pembelian (Pembelian Langsung / Tunai) --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->supplier?->name ?? 'Vendor' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Potongan Uang Muka DP -->
                            @if(isset($availableDownPayments) && $availableDownPayments->isNotEmpty())
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Potong dari Uang Muka / DP (Opsional)</label>
                                    <select name="down_payment_id" class="w-full select-clean p-2.5 bg-indigo-50/50 text-indigo-900 border border-indigo-200 rounded-xl">
                                        <option value="">-- Tanpa Potongan DP --</option>
                                        @foreach($availableDownPayments as $dp)
                                            <option value="{{ $dp->id }}">
                                                DP {{ $dp->dp_number }} - {{ $dp->supplier?->name }} (Rp {{ number_format($dp->amount, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Total Nilai Tagihan Bruto (Rp) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" name="amount" required placeholder="Mis. 132600000" 
                                       class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono font-bold">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Scan Dokumen Invoice / Faktur Pajak <span class="text-rose-500 font-bold">* Wajib</span></label>
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" required 
                                       class="w-full p-2 text-xs bg-slate-50 border border-slate-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <span class="text-[10px] text-slate-400 mt-1 block">Wajib unggah scan faktur asli atau kuitansi (PDF, JPG, PNG maks 10MB)</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Catatan Termin / Keterangan Pembayaran</label>
                                <textarea name="notes" rows="2" placeholder="Catatan tagihan, rekening transfer, termin pelunasan..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="showInvModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Simpan Faktur Pembelian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
