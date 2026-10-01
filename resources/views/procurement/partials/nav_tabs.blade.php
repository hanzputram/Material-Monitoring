@props(['active' => ''])

@php
    $user = auth()->user();
    $activeProjectId = session('active_project_id');
    $query = $activeProjectId ? ['project_id' => $activeProjectId] : [];
@endphp

<div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl tab-scroll-container overflow-x-auto max-w-full">
    <!-- 1. Pesanan Pembelian -->
    @if($user?->canReadPo())
        <a href="{{ route('procurement.po.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'po' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'po' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Pesanan Pembelian</span>
        </a>
    @endif

    <!-- 2. Penerimaan Barang -->
    @if($user?->canReadDo())
        <a href="{{ route('procurement.do.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'do' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'do' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Penerimaan Barang</span>
        </a>
    @endif

    <!-- 3. Uang Muka Pembelian -->
    @if($user?->canReadDownPayment())
        <a href="{{ route('finance.down-payments.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'down_payment' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'down_payment' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Uang Muka Pembelian</span>
        </a>
    @endif

    <!-- 4. Faktur Pembelian -->
    @if($user?->canReadInvoice())
        <a href="{{ route('procurement.invoices.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'invoice' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'invoice' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Faktur Pembelian</span>
        </a>
    @endif

    <!-- 5. Pembayaran Pembelian -->
    @if($user?->canReadPayment())
        <a href="{{ route('finance.payments.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'payment' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'payment' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Pembayaran Pembelian</span>
        </a>
    @endif

    <!-- 6. Retur Pembelian -->
    @if($user?->canReadReturn())
        <a href="{{ route('procurement.returns.index', $query) }}" 
           class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'return' ? 'bg-white shadow-sm text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold hover:bg-slate-200/60' }}">
            <svg class="w-4 h-4 {{ $active === 'return' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            <span>Retur Pembelian</span>
        </a>
    @endif

    <!-- 7. Rekap Pembelian Toko Lintas Proyek -->
    <a href="{{ route('finance.supplier-summary.index') }}" 
       class="whitespace-nowrap px-3 sm:px-4 py-2 text-xs rounded-lg transition-all flex items-center gap-2 flex-shrink-0 {{ $active === 'summary' ? 'bg-white shadow-sm text-emerald-700 font-bold' : 'text-emerald-700/80 hover:text-emerald-900 font-semibold hover:bg-emerald-50' }}">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Rekap Toko Lintas Proyek</span>
    </a>
</div>
