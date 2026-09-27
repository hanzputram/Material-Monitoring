@extends('layouts.app')

@section('title', 'Buat Proyek Konstruksi Baru')
@section('page_title', 'Wizard Pembuatan Proyek Baru')
@section('page_subtitle', 'Langkah 1: Setup Identitas Teknis Proyek & Toleransi Ambang Batas Alert')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card-clean p-4 sm:p-8">
        <form action="{{ route('projects.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="border-b border-slate-100 pb-4">
                <span class="badge-clean bg-blue-100 text-blue-800 text-xs mb-1">Tahap 1 dari 2</span>
                <h3 class="text-base font-bold text-slate-900">Identitas Teknis Bangunan</h3>
                <p class="text-xs text-slate-500">Parameter ini menentukan struktur dasar penghitungan RAB dan baseline pengawasan material.</p>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Proyek Konstruksi <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Mis. Review Rumah Susun (Prototipe)" 
                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-semibold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe Prototype</label>
                        <input type="text" name="prototype_type" placeholder="Mis. Barak Rembunai / Tipe 36" 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jumlah Lantai <span class="text-rose-500">*</span></label>
                        <input type="number" name="floor_count" value="3" min="1" required 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                        <input type="text" name="budget_year" value="2026" required 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe Pondasi</label>
                        <input type="text" name="foundation_type" placeholder="Mis. Bored Pile / Tiang Pancang" 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lokasi KDS / Site Plan</label>
                    <input type="text" name="location_kds" placeholder="Mis. Kawasan Barak Rembunai Blok B" 
                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                </div>

                <!-- Alert Thresholds -->
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Konfigurasi Batas Alert Deviasi Material</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Threshold Kelebihan Material (%) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.1" name="alert_over_threshold_pct" value="10.0" required 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                            <span class="text-[10px] text-slate-400 mt-1 block">Memicu alert bila aktual material melebihi > 10% dari RAB.</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Threshold Kekurangan Material (%) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.1" name="alert_under_threshold_pct" value="5.0" required 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                            <span class="text-[10px] text-slate-400 mt-1 block">Memicu alert bila aktual material kurang > 5% dari target.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3">
                <a href="{{ route('projects.index') }}" class="px-5 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-center">Batal</a>
                <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 text-center">
                    Simpan & Lanjut ke RAB Builder &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
