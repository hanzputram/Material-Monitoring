@extends('layouts.app')

@section('title', 'Impor RAB dari File Excel')
@section('page_title', 'Impor File Excel RAB')
@section('page_subtitle', 'Unggah berkas BQ/RAB terstandar untuk diproses otomatis oleh parser akurat 100%')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Card Instruksi & Download Template -->
    <div class="card-clean p-4 sm:p-6 bg-gradient-to-r from-blue-50/60 to-white border-blue-200/70">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="badge-clean bg-blue-100 text-blue-800 text-xs mb-1">Akurasi 100% Berstandar</span>
                <h3 class="text-base font-bold text-slate-900">Format Template Baku Terkunci</h3>
                <p class="text-xs text-slate-600 mt-1 max-w-xl">
                    Sistem menggunakan template Excel baku dengan 2 sheet: <strong>"Info Proyek"</strong> dan <strong>"Detail RAB"</strong> (kolom Level 1–5, Kode, Uraian, Volume, Satuan, Harga). Unduh template resmi sebelum mengunggah.
                </p>
            </div>
            <a href="{{ route('rab.template.download') }}" 
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh Template Resmi (.xlsx)
            </a>
        </div>
    </div>

    <!-- Form Upload & Preview -->
    <div class="card-clean p-4 sm:p-8">
        <form action="{{ route('rab.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Target Project Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Tujuan Impor Proyek</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="radio" name="target_choice" value="new" checked 
                               onclick="document.getElementById('existingProjectSelect').classList.add('hidden')"
                               class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Buat Proyek Baru dari Data Sheet "Info Proyek"</span>
                            <span class="text-[11px] text-slate-500">Nama proyek, tipe prototype, dan lantai akan dibaca otomatis dari file Excel.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="radio" name="target_choice" value="existing" 
                               onclick="document.getElementById('existingProjectSelect').classList.remove('hidden')"
                               class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Impor ke Proyek yang Sudah Ada</span>
                            <span class="text-[11px] text-slate-500">Tambahkan atau gantikan struktur RAB pada proyek terpilih.</span>
                        </div>
                    </label>
                </div>

                <div id="existingProjectSelect" class="mt-3 hidden">
                    <select name="target_project_id" class="w-full select-clean text-xs font-semibold p-2.5 bg-white text-slate-800">
                        <option value="">-- Pilih Proyek Terdaftar --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ $project && $project->id == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->budget_year }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Drag & Drop Upload File -->
            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Berkas Excel (.xlsx / .xls)</label>
                <div class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-6 sm:p-8 text-center transition-colors bg-slate-50/50"
                     x-data="{ fileName: '' }">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div class="text-xs font-bold text-slate-700 mb-1" x-text="fileName ? fileName : 'Pilih atau Tarik Berkas Excel ke Sini'"></div>
                    <p class="text-[11px] text-slate-400 mb-4">Mendukung format Microsoft Excel .xlsx atau .xls (Maks. 20MB)</p>
                    <label class="inline-flex items-center px-4 py-2 text-xs font-bold bg-white text-blue-600 border border-blue-200 rounded-xl hover:bg-blue-50 cursor-pointer shadow-sm">
                        Pilih Berkas
                        <input type="file" name="file" accept=".xlsx,.xls" required class="hidden" 
                               @change="fileName = $event.target.files[0]?.name">
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('rab.builder') }}" class="px-5 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-center">Batal</a>
                <button type="submit" class="px-6 py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 text-center">
                    Lanjutkan ke Pratinjau & Validasi &rarr;
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
