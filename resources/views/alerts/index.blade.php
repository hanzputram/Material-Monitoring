@extends('layouts.app')

@section('title', 'Pusat Peringatan & Alert — ' . $project->name)
@section('page_title', 'Pusat Peringatan & Notifikasi (Alert Center)')
@section('page_subtitle', 'Peringatan deviasi material dan biaya melebihi ambang batas (threshold)')

@section('content')
<div class="space-y-6">

    <!-- KPI Counts -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="card-clean p-3.5 sm:p-4 card-clean-hover">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Peringatan</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{ $counts['total'] }}</span>
        </div>
        <div class="card-clean p-3.5 sm:p-4 card-clean-hover">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 block mb-1">Peringatan Kritis</span>
            <span class="text-xl sm:text-2xl font-black text-rose-600 font-mono">{{ $counts['critical'] }}</span>
        </div>
        <div class="card-clean p-3.5 sm:p-4 card-clean-hover">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 block mb-1">Peringatan Warning</span>
            <span class="text-xl sm:text-2xl font-black text-amber-600 font-mono">{{ $counts['warning'] }}</span>
        </div>
        <div class="card-clean p-3.5 sm:p-4 card-clean-hover">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block mb-1">Belum Dibaca (Unread)</span>
            <span class="text-xl sm:text-2xl font-black text-blue-600 font-mono">{{ $counts['unread'] }}</span>
        </div>
    </div>

    <!-- Alert List Card -->
    <div class="card-clean overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Riwayat Peringatan Deviasi Proyek</h3>
                <p class="text-xs text-slate-500">Memicu notifikasi saat actual kuantiti menyimpang di atas/bawah batas toleransi</p>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl overflow-x-auto tab-scroll-container max-w-full">
                <a href="{{ route('alerts.index') }}" class="whitespace-nowrap px-3 py-1.5 text-xs rounded-lg font-bold transition-all {{ !request()->has('severity') && !request()->has('status') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua
                </a>
                <a href="{{ route('alerts.index', ['status' => 'unread']) }}" class="whitespace-nowrap px-3 py-1.5 text-xs rounded-lg font-bold transition-all {{ request('status') === 'unread' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Belum Dibaca
                </a>
                <a href="{{ route('alerts.index', ['severity' => 'critical']) }}" class="whitespace-nowrap px-3 py-1.5 text-xs rounded-lg font-bold transition-all {{ request('severity') === 'critical' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Kritis Saja
                </a>
            </div>
        </div>

        <div class="divide-y divide-slate-100 bg-white">
            @forelse($alerts as $al)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 transition-colors hover:bg-slate-50/80 {{ $al->status === 'unread' ? 'bg-blue-50/30' : 'bg-white' }}">
                    <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $al->severity === 'critical' ? 'bg-rose-50 text-rose-600 border border-rose-200/60' : 'bg-amber-50 text-amber-600 border border-amber-200/60' }}">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-1">
                                <span class="badge-clean {{ $al->severity === 'critical' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }} text-[10px]">
                                    {{ ucfirst($al->severity) }}
                                </span>
                                <span class="badge-clean bg-slate-100 text-slate-700 text-[10px]">
                                    {{ str_replace('_', ' ', strtoupper($al->type)) }}
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium">{{ $al->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs font-bold text-slate-800 leading-snug break-words">{{ $al->message }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 flex-shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 justify-end sm:justify-start">
                        @if($al->status === 'unread')
                            <form action="{{ route('alerts.mark_read', $al->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 transition-colors shadow-xs">
                                    Tandai Dibaca
                                </button>
                            </form>
                        @endif

                        @if($al->status !== 'resolved')
                            <form action="{{ route('alerts.mark_resolved', $al->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-colors">
                                    Selesaikan
                                </button>
                            </form>
                        @else
                            <span class="badge-clean bg-emerald-100 text-emerald-800 text-xs">Terselesaikan</span>
                        @endif

                        <a href="{{ route('variance.index') }}" class="px-3 py-1.5 text-xs font-bold bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors">
                            Validasi &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400 text-xs">
                    Tidak ada peringatan pada filter ini.
                </div>
            @endforelse
        </div>

        @if($alerts->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
