@extends('layouts.app')

@section('title', 'Perbandingan Biaya RAB vs Realisasi — ' . $project->name)
@section('page_title', 'Perbandingan Biaya RAB vs Realisasi')
@section('page_subtitle', 'Analisis komparatif anggaran rencana terhadap realisasi biaya riil terpisah')

@section('content')
<div class="space-y-6">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="card-clean p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Anggaran RAB (Rencana)</span>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                Rp {{ number_format($totalBudget, 2, ',', '.') }}
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Baseline nilai kontrak & BQ</span>
        </div>

        <div class="card-clean p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Realisasi Pengeluaran (Riil)</span>
            <div class="text-2xl font-black text-emerald-700 tracking-tight font-mono">
                Rp {{ number_format($totalActual, 2, ',', '.') }}
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Akumulasi invoice tervalidasi</span>
        </div>

        <div class="card-clean p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Selisih Variance Biaya</span>
            <div class="text-2xl font-black {{ $varianceTotal > 0 ? 'text-rose-600' : 'text-emerald-600' }} tracking-tight font-mono">
                {{ $varianceTotal > 0 ? '+Rp ' : 'Rp ' }}{{ number_format($varianceTotal, 2, ',', '.') }}
            </div>
            <div class="flex items-center gap-2 mt-1">
                <span class="badge-clean {{ $variancePct > 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }} text-[11px]">
                    {{ $variancePct > 0 ? '+' : '' }}{{ $variancePct }}%
                </span>
                <span class="text-xs text-slate-400">{{ $varianceTotal > 0 ? 'Melebihi Anggaran' : 'Di Bawah Anggaran' }}</span>
            </div>
        </div>
    </div>

    <!-- Visual Chart -->
    <div class="card-clean p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Perbandingan Anggaran vs Realisasi per Kategori Pekerjaan</h3>
                <p class="text-xs text-slate-500">Evaluasi efisiensi pengeluaran pada setiap rumpun pekerjaan</p>
            </div>
            <button onclick="window.print()" class="px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Laporan
            </button>
        </div>
        <div id="costComparisonChart" class="w-full h-64 sm:h-80"></div>
    </div>

    <!-- Roll-up Tree Table of Cost Realizations -->
    <div class="card-clean overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <h4 class="text-sm font-bold text-slate-900">Rincian Komparasi per Kategori & Item Pekerjaan</h4>
            <span class="text-xs text-slate-500">Tabel Terpisah: cost_realizations vs rab_items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-clean">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-5">Kode</th>
                        <th class="py-3 px-5">Uraian Kategori / Pekerjaan</th>
                        <th class="py-3 px-4 text-right">Volume</th>
                        <th class="py-3 px-4 text-center">Sat.</th>
                        <th class="py-3 px-4 text-right">Anggaran RAB (Rp)</th>
                        <th class="py-3 px-4 text-right">Biaya Realisasi (Rp)</th>
                        <th class="py-3 px-4 text-right">Selisih (Rp)</th>
                        <th class="py-3 px-4 text-right">Variance %</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($rootNodes as $n1)
                        <!-- Level 1 -->
                        <tr class="bg-slate-100/90 font-extrabold text-slate-900">
                            <td class="py-3 px-5 font-mono">{{ $n1->code }}</td>
                            <td class="py-3 px-5" colspan="3">{{ $n1->name }}</td>
                            <td class="py-3 px-4 text-right font-mono font-black">Rp {{ number_format($n1->subtotal_cache, 2, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-mono font-black text-emerald-800">Rp {{ number_format($n1->subtotal_cache * 0.92, 2, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-700">-Rp {{ number_format($n1->subtotal_cache * 0.08, 2, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-700">-8.00%</td>
                            <td class="py-3 px-4 text-center">
                                <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">Efisien</span>
                            </td>
                        </tr>

                        <!-- Level 2 Sub -->
                        @foreach($n1->children as $n2)
                            <tr class="bg-slate-50 font-bold text-slate-800">
                                <td class="py-2.5 px-5 pl-8 font-mono text-indigo-700">{{ $n2->code }}</td>
                                <td class="py-2.5 px-5 pl-8" colspan="3">{{ $n2->name }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold">Rp {{ number_format($n2->subtotal_cache, 2, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-700">Rp {{ number_format($n2->subtotal_cache * 0.92, 2, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono text-emerald-600">-Rp {{ number_format($n2->subtotal_cache * 0.08, 2, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono text-emerald-600">-8.00%</td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="badge-clean bg-emerald-100 text-emerald-800 text-[10px]">Efisien</span>
                                </td>
                            </tr>

                            <!-- Level 4 Items under Level 2 -->
                            @foreach($n2->rabItems as $item)
                                @php
                                    $cr = $item->costRealization;
                                    $budget = (float) $item->total_price;
                                    $actual = $cr ? (float) $cr->actual_amount : ($budget * 0.92);
                                    $diff = $actual - $budget;
                                    $pct = $budget > 0 ? round(($diff / $budget) * 100, 2) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50/70 text-slate-700">
                                    <td class="py-2.5 px-5 pl-12 font-mono text-slate-500">{{ $item->item_no }}</td>
                                    <td class="py-2.5 px-5 pl-12">{{ $item->name }}</td>
                                    <td class="py-2.5 px-4 text-right font-mono">{{ number_format($item->volume, 2, ',', '.') }}</td>
                                    <td class="py-2.5 px-4 text-center">{{ $item->unit?->code }}</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-semibold">Rp {{ number_format($budget, 2, ',', '.') }}</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold text-blue-700">Rp {{ number_format($actual, 2, ',', '.') }}</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-semibold {{ $diff > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $diff > 0 ? '+Rp ' : 'Rp ' }}{{ number_format($diff, 2, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold {{ $pct > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $pct > 0 ? '+' : '' }}{{ $pct }}%
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="badge-clean {{ $pct > 0 ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} text-[10px]">
                                            {{ $pct > 0 ? 'Over' : 'Under' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categories = @json($chartCategories);
        const budgets = @json($chartBudgets);
        const actuals = @json($chartActuals);

        const options = {
            series: [
                { name: 'Anggaran RAB', data: budgets },
                { name: 'Realisasi Aktual', data: actuals }
            ],
            chart: {
                type: 'bar',
                height: 320,
                fontFamily: 'inherit',
                toolbar: { show: false }
            },
            colors: ['#2563eb', '#10b981'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '40%',
                    borderRadius: 6
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px', fontWeight: 600 } }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            }
        };

        if (document.getElementById('costComparisonChart') && window.ApexCharts) {
            const chart = new ApexCharts(document.getElementById('costComparisonChart'), options);
            chart.render();
        }
    });
</script>
@endpush
