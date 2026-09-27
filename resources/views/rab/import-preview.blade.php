@extends('layouts.app')

@section('title', 'Pratinjau & Validasi Impor RAB')
@section('page_title', 'Pratinjau & Laporan Validasi Impor RAB')
@section('page_subtitle', 'Pengecekan integritas hierarki level 1-5, master satuan, dan formula harga sebelum disimpan')

@section('content')
<div class="space-y-6">

    <!-- Status Banner -->
    @if($preview['valid'])
        <div class="p-4 sm:p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
            <div class="flex items-start gap-3 sm:gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-emerald-950">Berkas Valid 100%! Siap Disimpan</h3>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        Seluruh struktur hierarki level 1 sampai 5 telah diverifikasi secara akurat tanpa error sintaks.
                    </p>
                </div>
            </div>
            <form action="{{ route('rab.import.commit') }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto px-6 py-3 text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan RAB ke Database Sekarang &rarr;
                </button>
            </form>
        </div>
    @else
        <div class="p-4 sm:p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm">
            <div class="flex items-start gap-3 sm:gap-4">
                <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-rose-950">Ditemukan Kesalahan Validasi Struktur RAB</h3>
                    <p class="text-xs text-rose-700 mt-0.5">Harap perbaiki baris-baris berikut pada file Excel Anda lalu unggah ulang:</p>
                    <ul class="mt-3 space-y-1 list-disc list-inside text-xs font-semibold text-rose-800">
                        @foreach($preview['errors'] as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4">
                        <a href="{{ route('rab.import.form') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-white text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50">
                            &larr; Unggah Ulang Berkas yang Sudah Diperbaiki
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- STATS SUMMARY -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Total Baris</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['total_rows'] }}</span>
        </div>
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-blue-600 block mb-1">Level 1 (Kategori)</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['level_1_count'] }}</span>
        </div>
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-indigo-600 block mb-1">Level 2 (Sub)</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['level_2_count'] }}</span>
        </div>
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-amber-600 block mb-1">Level 3 (Sub-Sub)</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['level_3_count'] }}</span>
        </div>
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-emerald-600 block mb-1">Level 4 (Item)</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['level_4_count'] }}</span>
        </div>
        <div class="card-clean p-4 text-center">
            <span class="text-[10px] uppercase font-bold text-purple-600 block mb-1">Level 5 (BOM)</span>
            <span class="text-lg font-black text-slate-800 font-mono">{{ $preview['stats']['level_5_count'] }}</span>
        </div>
    </div>

    <!-- SHEET 1: INFO PROYEK PREVIEW -->
    <div class="card-clean p-4 sm:p-5">
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Informasi Identitas Proyek (Sheet "Info Proyek")</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @forelse($preview['project_info'] as $field => $val)
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-0.5">{{ $field }}</span>
                    <span class="text-xs font-bold text-slate-800">{{ $val ?: '-' }}</span>
                </div>
            @empty
                <div class="col-span-6 text-xs text-slate-400 italic">Sheet "Info Proyek" tidak ditemukan atau kosong. Parameter default akan diterapkan.</div>
            @endforelse
        </div>
    </div>

    <!-- SHEET 2: DETAIL ROWS PREVIEW -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <h4 class="text-sm font-bold text-slate-900">Pratinjau Data Uraian RAB (Sheet "Detail RAB")</h4>
            <span class="text-xs font-bold font-mono text-blue-700">
                Estimasi Total: Rp {{ number_format($preview['stats']['total_rab_amount'], 2, ',', '.') }}
            </span>
        </div>

        <div class="overflow-x-auto max-h-[500px]">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 sticky top-0 uppercase tracking-wider text-[11px] z-10">
                    <tr>
                        <th class="py-3 px-3 w-16 text-center">Row</th>
                        <th class="py-3 px-3 w-16 text-center">Level</th>
                        <th class="py-3 px-3 w-20">Kode</th>
                        <th class="py-3 px-5">Uraian Pekerjaan / Material</th>
                        <th class="py-3 px-3 text-right">Vol</th>
                        <th class="py-3 px-3 text-center">Sat</th>
                        <th class="py-3 px-4 text-right">Harga Satuan (Rp)</th>
                        <th class="py-3 px-4 text-right">Jumlah Harga (Rp)</th>
                        <th class="py-3 px-4 text-center">Status Validasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($preview['rows'] as $row)
                        @php
                            $lvl = $row['level'];
                            $hasErr = !empty($row['errors']);
                            $bg = match($lvl) {
                                1 => 'bg-slate-100/90 font-extrabold',
                                2 => 'bg-slate-50/80 font-bold',
                                3 => 'bg-amber-50/40 font-semibold',
                                4 => 'bg-white',
                                5 => 'bg-purple-50/40 text-purple-950',
                                default => ''
                            };
                        @endphp
                        <tr class="{{ $bg }} {{ $hasErr ? '!bg-rose-50/80' : '' }}">
                            <td class="py-2.5 px-3 text-center font-mono text-slate-400">{{ $row['excel_row'] }}</td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="badge-clean text-[10px] {{ $lvl == 1 ? 'bg-blue-600 text-white' : ($lvl == 2 ? 'bg-indigo-100 text-indigo-800' : ($lvl == 3 ? 'bg-amber-100 text-amber-800' : ($lvl == 4 ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800'))) }}">
                                    L{{ $lvl }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold">{{ $row['code'] ?: '-' }}</td>
                            <td class="py-2.5 px-5">
                                <div class="flex items-center gap-2" style="padding-left: {{ ($lvl - 1) * 12 }}px">
                                    @if($lvl == 5) <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> @endif
                                    <span>{{ $row['name'] }}</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ $row['volume'] !== null ? number_format($row['volume'], 2, ',', '.') : '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-semibold">{{ $row['unit'] ?: '-' }}</td>
                            <td class="py-2.5 px-4 text-right font-mono">{{ $row['unit_price'] !== null ? number_format($row['unit_price'], 2, ',', '.') : '-' }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold">{{ $row['total_price'] !== null ? number_format($row['total_price'], 2, ',', '.') : '-' }}</td>
                            <td class="py-2.5 px-4 text-center">
                                @if($hasErr)
                                    <span class="badge-clean bg-rose-100 text-rose-800 text-[10px]">Error</span>
                                @else
                                    <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
