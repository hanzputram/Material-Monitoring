@extends('layouts.app')

@section('title', 'Daftar Proyek Konstruksi')
@section('page_title', 'Kelola Proyek Konstruksi')
@section('page_subtitle', 'Daftar seluruh proyek aktif dan siklus RAB yang dikelola')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-slate-800">Total: {{ $projects->count() }} Proyek Terdaftar</h3>
        <a href="{{ route('projects.create') }}" class="px-4 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Proyek Baru
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @forelse($projects as $p)
            @php
                $isActive = session('active_project_id') == $p->id;
            @endphp
            <div class="card-clean p-4 sm:p-6 card-clean-hover flex flex-col justify-between {{ $isActive ? 'ring-2 ring-blue-500 border-transparent shadow-md' : '' }}">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge-clean {{ $p->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }} text-[10px]">
                            {{ ucfirst($p->status) }}
                        </span>
                        @if($isActive)
                            <span class="badge-clean bg-blue-100 text-blue-800 text-[10px] font-bold">Sedang Aktif</span>
                        @endif
                    </div>

                    <h4 class="text-base font-extrabold text-slate-900 leading-snug">{{ $p->name }}</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $p->prototype_type ?: 'Standar' }} • {{ $p->floor_count }} Lantai • Th {{ $p->budget_year }}
                    </p>

                    <div class="mt-4 p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Total RAB:</span>
                            <span class="font-bold font-mono text-slate-900">Rp {{ number_format($p->total_rab, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Realisasi Biaya:</span>
                            <span class="font-bold font-mono text-emerald-700">Rp {{ number_format($p->total_realization, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Surat Jalan (DO):</span>
                            <span class="font-semibold">{{ $p->delivery_orders_count }} Dokumen</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <form action="{{ route('projects.switch') }}" method="POST">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $p->id }}">
                        <button type="submit" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Pilih Proyek Ini &rarr;
                        </button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.edit', $p->id) }}" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        @if($projects->count() > 1)
                            <form action="{{ route('projects.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus proyek ini beserta seluruh data RAB-nya?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 card-clean p-12 text-center text-slate-400 text-xs">
                Belum ada proyek yang dibuat.
            </div>
        @endforelse
    </div>

</div>
@endsection
