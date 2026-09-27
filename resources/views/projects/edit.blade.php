@extends('layouts.app')

@section('title', 'Edit Proyek — ' . $project->name)
@section('page_title', 'Pengaturan & Identitas Proyek')
@section('page_subtitle', 'Perbarui parameter teknis, lokasi, dan ambang batas toleransi alert')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card-clean p-4 sm:p-8">
        <form action="{{ route('projects.update', $project->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900">Perbarui Parameter Proyek</h3>
                <p class="text-xs text-slate-500">Ubah data identitas atau sesuaikan batas persentase alert deviasi.</p>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Proyek Konstruksi <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $project->name) }}" required 
                           class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-semibold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe Prototype</label>
                        <input type="text" name="prototype_type" value="{{ old('prototype_type', $project->prototype_type) }}" 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jumlah Lantai <span class="text-rose-500">*</span></label>
                        <input type="number" name="floor_count" value="{{ old('floor_count', $project->floor_count) }}" min="1" required 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                        <input type="text" name="budget_year" value="{{ old('budget_year', $project->budget_year) }}" required 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe Pondasi</label>
                        <input type="text" name="foundation_type" value="{{ old('foundation_type', $project->foundation_type) }}" 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Lokasi KDS / Site Plan</label>
                        <input type="text" name="location_kds" value="{{ old('location_kds', $project->location_kds) }}" 
                               class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status Proyek</label>
                        <select name="status" class="w-full select-clean p-2.5 bg-white text-slate-800">
                            <option value="draft" {{ $project->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="closed" {{ $project->status === 'closed' ? 'selected' : '' }}>Selesai / Ditutup</option>
                        </select>
                    </div>
                </div>

                <!-- Alert Thresholds -->
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Konfigurasi Batas Alert Deviasi Material</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Threshold Kelebihan Material (%) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.1" name="alert_over_threshold_pct" value="{{ old('alert_over_threshold_pct', $project->alert_over_threshold_pct) }}" required 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Threshold Kekurangan Material (%) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.1" name="alert_under_threshold_pct" value="{{ old('alert_under_threshold_pct', $project->alert_under_threshold_pct) }}" required 
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-center">Batal</a>
                <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 text-center">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
