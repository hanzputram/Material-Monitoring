@extends('layouts.app')

@section('title', 'Pekerja & Tukang — ' . $project->name)
@section('page_title', 'Master Pekerja & Tukang')
@section('page_subtitle', 'Katalog master tenaga kerja lapangan (mandor, tukang spesialis, kenek) dan manajemen alokasi upah di proyek aktif')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab ?? 'project' }}',
    // 1. Master Worker Modal
    showMasterModal: false,
    masterModalTitle: 'Tambah Tenaga Kerja Baru',
    masterFormAction: '{{ route('workers.master.store') }}',
    masterFormMethod: 'POST',
    masterForm: {
        id: null,
        name: '',
        trade: 'Mandor',
        code: '',
        phone: '',
        nik: '',
        daily_rate: 150000,
        address: '',
        notes: '',
        is_active: true
    },
    // 2. Assign Single Worker Modal
    showAssignModal: false,
    assignFormAction: '{{ route('workers.assign') }}',
    assignForm: {
        project_id: '{{ $project->id }}',
        worker_id: '',
        name: '',
        phone: '',
        assigned_trade: '{{ $tradeOptions[0] ?? 'Mandor' }}',
        daily_wage: 160000,
        status: 'active',
        start_date: '{{ now()->toDateString() }}',
        end_date: '',
        notes: ''
    },
    // 3. Edit Project Worker Modal
    showEditAssignModal: false,
    editAssignFormAction: '',
    editAssignForm: {
        id: null,
        worker_name: '',
        worker_code: '',
        assigned_trade: '',
        daily_wage: 0,
        status: 'active',
        start_date: '',
        end_date: '',
        notes: ''
    },
    // Fast bulk input filter
    fastSearch: '',
    fastTrade: 'all',
    filterFastItem(item) {
        let matchesTrade = this.fastTrade === 'all' || item.trade === this.fastTrade;
        let matchesSearch = item.name.toLowerCase().includes(this.fastSearch.toLowerCase()) || 
                            item.code.toLowerCase().includes(this.fastSearch.toLowerCase()) ||
                            item.trade.toLowerCase().includes(this.fastSearch.toLowerCase());
        return matchesTrade && matchesSearch;
    },
    openAddMaster() {
        this.masterModalTitle = 'Tambah Tenaga Kerja Master Baru';
        this.masterFormAction = '{{ route('workers.master.store') }}';
        this.masterFormMethod = 'POST';
        this.masterForm = {
            id: null,
            name: '',
            trade: 'Mandor',
            code: '',
            phone: '',
            nik: '',
            daily_rate: 160000,
            address: '',
            notes: '',
            is_active: true
        };
        this.showMasterModal = true;
    },
    openEditMaster(w) {
        this.masterModalTitle = 'Edit Data Master Pekerja';
        this.masterFormAction = '/workers/master/' + w.id;
        this.masterFormMethod = 'PUT';
        this.masterForm = {
            id: w.id,
            name: w.name || '',
            trade: w.trade || 'Mandor',
            code: w.code || '',
            phone: w.phone || '',
            nik: w.nik || '',
            daily_rate: w.daily_rate || 0,
            address: w.address || '',
            notes: w.notes || '',
            is_active: Boolean(w.is_active)
        };
        this.showMasterModal = true;
    },
    openAssignSingle(w = null) {
        this.assignFormAction = '{{ route('workers.assign') }}';
        if (w) {
            this.assignForm.worker_id = w.id;
            this.assignForm.name = w.name;
            this.assignForm.phone = w.phone || '';
            this.assignForm.assigned_trade = w.trade;
            this.assignForm.daily_wage = w.daily_rate;
        } else {
            this.assignForm.worker_id = '';
            this.assignForm.name = '';
            this.assignForm.phone = '';
            this.assignForm.assigned_trade = '{{ $tradeOptions[0] ?? 'Mandor' }}';
            this.assignForm.daily_wage = 160000;
        }
        this.assignForm.status = 'active';
        this.assignForm.start_date = '{{ now()->toDateString() }}';
        this.assignForm.end_date = '';
        this.assignForm.notes = '';
        this.showAssignModal = true;
    },
    openEditAssign(pw) {
        this.editAssignFormAction = '/workers/project/' + pw.id;
        this.editAssignForm = {
            id: pw.id,
            worker_name: pw.worker ? pw.worker.name : '',
            worker_code: pw.worker ? pw.worker.code : '',
            assigned_trade: pw.assigned_trade || (pw.worker ? pw.worker.trade : ''),
            daily_wage: pw.daily_wage || 0,
            status: pw.status || 'active',
            start_date: pw.start_date ? pw.start_date.substring(0, 10) : '',
            end_date: pw.end_date ? pw.end_date.substring(0, 10) : '',
            notes: pw.notes || ''
        };
        this.showEditAssignModal = true;
    },
    onNameInput(typedName) {
        let workers = {{ Js::from($catalog) }};
        let found = workers.find(item => item.name.toLowerCase() === typedName.trim().toLowerCase());
        if (found) {
            this.assignForm.worker_id = found.id;
            this.assignForm.assigned_trade = found.trade;
            this.assignForm.daily_wage = found.daily_rate;
            if (found.phone) this.assignForm.phone = found.phone;
        } else {
            this.assignForm.worker_id = '';
        }
    }
}">

    <!-- 1. KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Pekerja Aktif di Proyek Ini -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pekerja di Proyek Ini</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $stats['active_in_project'] }} <span class="text-xs font-semibold text-slate-400">Orang Aktif</span>
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-semibold truncate" title="{{ $project->name }}">
                <span>Proyek: {{ Str::limit($project->name, 24) }}</span>
            </div>
        </div>

        <!-- Mandor & Tukang Spesialis -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Mandor & Tukang Ahli</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-blue-900 tracking-tight font-mono">
                {{ $stats['mandor_tukang_count'] }} <span class="text-xs font-semibold text-blue-400">Orang</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Mandor, pembesian, bekisting, batu, ME</span>
            </div>
        </div>

        <!-- Pekerja Standby / Cadangan -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pekerja Standby</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-900 tracking-tight font-mono">
                {{ $stats['standby_in_project'] }} <span class="text-xs font-semibold text-amber-500">Orang</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                <span>Siap rotasi / cadangan kebutuhan cor</span>
            </div>
        </div>

        <!-- Estimasi Beban Upah Harian -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Beban Upah Harian</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-900 tracking-tight font-mono">
                Rp {{ number_format($stats['daily_payroll_estimate'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-purple-700 font-semibold">
                <span>Per hari kerja untuk {{ $stats['active_in_project'] }} pekerja aktif</span>
            </div>
        </div>
    </div>

    <!-- 2. TAB SWITCHER -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <!-- TAB 1: Pekerja di Proyek Terkait -->
            <button type="button" @click="activeTab = 'project'"
                    :class="activeTab === 'project' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Pekerja di Proyek Ini</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'project' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $projectWorkers->count() }}</span>
            </button>

            <!-- TAB 2: Alokasi Cepat (Fast Bulk Assign) -->
            <button type="button" @click="activeTab = 'fast_assign'"
                    :class="activeTab === 'fast_assign' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Alokasi Cepat ke Proyek (Fast Input)</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'fast_assign' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $catalog->count() }}</span>
            </button>

            <!-- TAB 3: Master Katalog Tenaga Kerja -->
            <button type="button" @click="activeTab = 'master'"
                    :class="activeTab === 'master' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Katalog Master Tenaga Kerja</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'master' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $stats['total_master'] }}</span>
            </button>
        </nav>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: PEKERJA DI PROYEK TERKAIT (ACTIVE / ASSIGNED) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'project'" class="space-y-4">
        <div class="card-clean overflow-hidden">
            <!-- Header Panel -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Daftar Tenaga Kerja Proyek Aktif</h3>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ $project->name }}</span>
                        </div>
                        <p class="text-xs text-slate-500">Monitor mandor, tukang dan pembantu lapangan yang bertugas dengan kesepakatan upah harian kerja</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" @click="activeTab = 'fast_assign'"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Alokasi Massal</span>
                    </button>
                    <button type="button" @click="openAssignSingle()"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Tambah Pekerja / Tukang</span>
                    </button>
                </div>
            </div>

            <!-- Project Worker Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-5">Nama Tenaga Kerja</th>
                            <th class="py-3 px-4">Kontak & NIK</th>
                            <th class="py-3 px-4">Bidang / Posisi</th>
                            <th class="py-3 px-4 text-right">Upah Harian</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-5">Mulai Bekerja & Penempatan</th>
                            <th class="py-3 px-4 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($projectWorkers as $idx => $pw)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-xs">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                            {{ strtoupper(substr($pw->worker?->name ?? 'P', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-xs">{{ $pw->worker?->name ?? 'Pekerja Tidak Ditemukan' }}</div>
                                            <div class="font-mono text-[11px] text-slate-500 font-semibold">{{ $pw->worker?->code ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-mono text-xs text-slate-800">{{ $pw->worker?->phone ?: '-' }}</div>
                                    @if($pw->worker?->nik)
                                        <div class="font-mono text-[10px] text-slate-400">NIK: {{ $pw->worker->nik }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $pw->worker?->trade_color ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $pw->effective_trade }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 text-xs">
                                    {{ $pw->formatted_daily_wage }} <span class="text-[10px] font-medium text-slate-400">/ hari</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $pw->status_badge }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $pw->status === 'active' ? 'bg-emerald-500' : ($pw->status === 'standby' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                        {{ $pw->status_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="text-xs font-semibold text-slate-800">
                                        {{ $pw->start_date ? $pw->start_date->isoFormat('D MMM Y') : 'Mulai Langsung' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" title="{{ $pw->notes }}">
                                        {{ $pw->notes ?: 'Tidak ada catatan zona/lantai' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Assignment -->
                                        <button type="button" @click="openEditAssign({{ json_encode($pw) }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Penugasan Proyek">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Remove from Project -->
                                        <form action="{{ route('workers.project.destroy', $pw->id) }}" method="POST" onsubmit="return confirm('Keluarkan {{ addslashes($pw->worker?->name ?? 'pekerja') }} dari proyek ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus dari Proyek">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <svg class="w-12 h-12 mb-3 stroke-current opacity-30" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <p class="font-bold text-sm text-slate-700">Belum ada pekerja atau tukang yang ditugaskan di proyek ini.</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">Anda dapat menugaskan pekerja satu-per-satu atau menggunakan fitur Alokasi Massal dari katalog master.</p>
                                        <div class="mt-4 flex items-center gap-3">
                                            <button type="button" @click="activeTab = 'fast_assign'" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20">
                                                Alokasi Massal dari Master
                                            </button>
                                            <button type="button" @click="openAssignSingle()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl">
                                                + Tugaskan Satu Pekerja
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: ALOKASI CEPAT KE PROYEK (FAST BULK ASSIGN) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'fast_assign'" class="card-clean overflow-hidden" x-cloak>
        <form action="{{ route('workers.fast_bulk') }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <!-- Header & Filter Toolbar -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="fastTrade = 'all'" 
                            :class="fastTrade === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                            class="px-3 py-1.5 rounded-xl text-xs transition-colors cursor-pointer shadow-2xs">
                        Semua Keahlian ({{ $catalog->count() }})
                    </button>
                    @foreach($tradeOptions as $tr)
                        @php
                            $cnt = $catalog->where('trade', $tr)->count();
                        @endphp
                        @if($cnt > 0)
                            <button type="button" @click="fastTrade = '{{ $tr }}'" 
                                    :class="fastTrade === '{{ $tr }}' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-200'" 
                                    class="px-3 py-1.5 rounded-xl text-xs transition-colors cursor-pointer shadow-2xs">
                                {{ $tr }} ({{ $cnt }})
                            </button>
                        @endif
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-auto">
                        <input type="text" x-model="fastSearch" placeholder="Cari nama / kode pekerja..." 
                               class="w-full sm:w-60 text-xs pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 flex-shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Pekerja Terpilih
                    </button>
                </div>
            </div>

            <!-- Fast Input Checklist Table -->
            <div class="overflow-x-auto max-h-[600px]">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 sticky top-0 uppercase tracking-wider text-[11px] z-10">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">Pilih</th>
                            <th class="py-3 px-4 w-28">Kode</th>
                            <th class="py-3 px-5">Nama Tenaga Kerja</th>
                            <th class="py-3 px-4">Keahlian Master</th>
                            <th class="py-3 px-4 w-44">Posisi Proyek Ini</th>
                            <th class="py-3 px-4 w-36 text-right">Upah Harian (Rp)</th>
                            <th class="py-3 px-4 w-36">Status</th>
                            <th class="py-3 px-5">Catatan Penempatan / Zona</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($catalog as $idx => $worker)
                            @php
                                $assigned = $projectWorkers->firstWhere('worker_id', $worker->id);
                            @endphp
                            <tr x-show="filterFastItem({ name: '{{ addslashes($worker->name) }}', code: '{{ $worker->code }}', trade: '{{ $worker->trade }}' })"
                                class="transition-colors hover:bg-slate-50 {{ $assigned ? 'bg-blue-50/20' : '' }}">
                                <input type="hidden" name="items[{{ $idx }}][worker_id]" value="{{ $worker->id }}">
                                
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" name="items[{{ $idx }}][selected]" value="1" 
                                           {{ $assigned ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-500 text-[11px]">{{ $worker->code }}</td>
                                <td class="py-3 px-5">
                                    <div class="font-bold text-slate-900">{{ $worker->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $worker->phone ?: ($worker->address ?: '-') }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $worker->trade_color }}">
                                        {{ $worker->trade }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" name="items[{{ $idx }}][assigned_trade]" 
                                           value="{{ $assigned ? $assigned->assigned_trade : $worker->trade }}" 
                                           placeholder="Posisi..."
                                           class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium">
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <input type="number" name="items[{{ $idx }}][daily_wage]" 
                                           value="{{ $assigned ? (int)$assigned->daily_wage : (int)$worker->daily_rate }}" min="0" step="5000"
                                           class="w-full text-right font-mono font-bold p-1.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-xs">
                                </td>
                                <td class="py-3 px-4">
                                    <select name="items[{{ $idx }}][status]" class="select-clean select-clean-sm w-full text-xs p-1.5 bg-white border border-slate-200 rounded-lg font-medium text-slate-800">
                                        <option value="active" {{ !$assigned || $assigned->status === 'active' ? 'selected' : '' }}>Aktif</option>
                                        <option value="standby" {{ $assigned && $assigned->status === 'standby' ? 'selected' : '' }}>Standby</option>
                                        <option value="completed" {{ $assigned && $assigned->status === 'completed' ? 'selected' : '' }}>Selesai</option>
                                    </select>
                                </td>
                                <td class="py-3 px-5">
                                    <input type="text" name="items[{{ $idx }}][notes]" 
                                           value="{{ $assigned ? $assigned->notes : '' }}" 
                                           placeholder="Zona Lantai / Catatan..." 
                                           class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Toolbar -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">
                    Centang tenaga kerja yang akan ditugaskan ke proyek <strong>{{ $project->name }}</strong> lalu klik tombol Simpan.
                </span>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Pekerja Terpilih
                </button>
            </div>
        </form>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 3: KATALOG MASTER TENAGA KERJA (CRUD GLOBAL) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'master'" class="space-y-4" x-cloak>
        <div class="card-clean overflow-hidden">
            <!-- Header Panel -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Katalog Master Tenaga Kerja Konstruksi</h3>
                        <p class="text-xs text-slate-500">Database menyeluruh mandor, tukang spesialis, dan helper beserta standar tarif upah harian kerja</p>
                    </div>
                </div>

                <!-- Single Action Button -->
                <button type="button" @click="openAddMaster()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer whitespace-nowrap self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Pekerja Baru</span>
                </button>
            </div>

            <!-- Filter Toolbar -->
            <div class="p-3 sm:p-4 bg-slate-50/70 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form action="{{ route('workers.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <input type="hidden" name="tab" value="master">

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ $search }}" 
                               placeholder="Cari nama, NIK, kode, keahlian..." 
                               class="w-full text-xs pl-8 pr-7 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @if($search)
                            <a href="{{ route('workers.index', ['tab' => 'master']) }}" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold">&times;</a>
                        @endif
                    </div>

                    <!-- Trade Filter -->
                    <div class="w-full sm:w-56">
                        <select name="trade" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="all">Semua Bidang Keahlian</option>
                            @foreach($tradeOptions as $tr)
                                <option value="{{ $tr }}" {{ $tradeFilter === $tr ? 'selected' : '' }}>{{ $tr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full sm:w-36">
                        <select name="status" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="">Semua Status</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                        </select>
                    </div>

                    @if($search || ($tradeFilter && $tradeFilter !== 'all') || $statusFilter)
                        <a href="{{ route('workers.index', ['tab' => 'master']) }}" class="text-xs text-rose-600 font-semibold hover:underline px-1 py-1">
                            Reset Filter
                        </a>
                    @endif
                </form>
            </div>

            <!-- Master Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 w-28">Kode</th>
                            <th class="py-3 px-5">Nama Tenaga Kerja</th>
                            <th class="py-3 px-4">Bidang Keahlian</th>
                            <th class="py-3 px-4">Kontak / NIK</th>
                            <th class="py-3 px-4 text-right">Tarif Standar</th>
                            <th class="py-3 px-4 text-center">Alokasi Proyek</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($masterWorkers as $idx => $w)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-xs">
                                    {{ $masterWorkers->firstItem() + $idx }}
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-700 text-[11px]">
                                    {{ $w->code }}
                                </td>
                                <td class="py-3 px-5">
                                    <div class="font-bold text-slate-900 text-xs">{{ $w->name }}</div>
                                    @if($w->address)
                                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" title="{{ $w->address }}">{{ $w->address }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $w->trade_color }}">
                                        {{ $w->trade }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-xs text-slate-800">{{ $w->phone ?: '-' }}</div>
                                    @if($w->nik)
                                        <div class="font-mono text-[10px] text-slate-400">NIK: {{ $w->nik }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 text-xs">
                                    {{ $w->formatted_daily_rate }} <span class="text-[10px] font-medium text-slate-400">/ hari</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($w->project_workers_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $w->project_workers_count }} Proyek
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($w->is_active)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Quick assign to active project button -->
                                        <button type="button" @click="openAssignSingle({{ json_encode($w) }})"
                                                class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer"
                                                title="Tugaskan ke Proyek Ini">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>

                                        <!-- Edit Master -->
                                        <button type="button" @click="openEditMaster({{ json_encode($w) }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Data Pekerja">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Master -->
                                        <form action="{{ route('workers.master.destroy', $w->id) }}" method="POST" onsubmit="return confirm('Hapus pekerja {{ addslashes($w->name) }} dari katalog master?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Master">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <svg class="w-10 h-10 mb-2 stroke-current opacity-40" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <p class="font-medium text-sm text-slate-700">Tidak ada tenaga kerja yang sesuai filter.</p>
                                        <button type="button" @click="openAddMaster()" class="mt-3 text-xs text-blue-600 font-bold hover:underline">
                                            + Tambah Pekerja Baru Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($masterWorkers->hasPages())
                <div class="p-4 border-t border-slate-100 bg-white">
                    {{ $masterWorkers->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: TAMBAH / EDIT MASTER TENAGA KERJA -->
    <!-- ======================================================== -->
    <div x-show="showMasterModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" x-cloak>
        
        <div @click.away="showMasterModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden my-8">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="font-bold text-slate-900 text-sm" x-text="masterModalTitle"></h3>
                <button type="button" @click="showMasterModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="masterFormAction" method="POST">
                @csrf
                <template x-if="masterFormMethod === 'PUT'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Bidang Keahlian & Kode -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Bidang Keahlian <span class="text-rose-500">*</span></label>
                            <select name="trade" x-model="masterForm.trade" required 
                                    class="select-clean w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @foreach($tradeOptions as $tr)
                                    <option value="{{ $tr }}">{{ $tr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Kode Pekerja</label>
                            <input type="text" name="code" x-model="masterForm.code" 
                                   placeholder="Otomatis (e.g. WKR-TKG-010)"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-slate-400 mt-1 block">Biarkan kosong untuk penomoran otomatis</span>
                        </div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Nama Lengkap Pekerja <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="masterForm.name" required 
                               placeholder="Contoh: Bambang Sutrisno"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- NIK & No HP/WhatsApp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Nomor KTP / NIK</label>
                            <input type="text" name="nik" x-model="masterForm.nik" 
                                   placeholder="16 digit NIK"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">No. Handphone / WA</label>
                            <input type="text" name="phone" x-model="masterForm.phone" 
                                   placeholder="0812-xxxx-xxxx"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Tarif Upah Standar Master -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Tarif Upah Standar Master (Rp/Hari) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="daily_rate" x-model="masterForm.daily_rate" required min="0" step="5000"
                                   placeholder="150000"
                                   class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Akan menjadi acuan upah awal saat dialokasikan ke proyek</span>
                    </div>

                    <!-- Alamat Asal / Domisili -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Alamat Asal / Domisili</label>
                        <input type="text" name="address" x-model="masterForm.address" 
                               placeholder="Contoh: Banyumas, Jawa Tengah"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Catatan Keahlian / Sertifikasi -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Catatan Keahlian / Pengalaman / Sertifikat</label>
                        <textarea name="notes" x-model="masterForm.notes" rows="2" 
                                  placeholder="Contoh: Pengalaman bore pile, sertifikat las 3G, pemegang sertifikat K3"
                                  class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>

                    <!-- Status Aktif Checkbox -->
                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="masterForm.is_active"
                                   class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-700">Tenaga Kerja Aktif & Siap Ditugaskan ke Proyek</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showMasterModal = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        Simpan Data Tenaga Kerja
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: TAMBAH PEKERJA / TUKANG KE PROYEK (INPUT NAMA BIASA) -->
    <!-- ======================================================== -->
    <div x-show="showAssignModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" x-cloak>
        
        <div @click.away="showAssignModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden my-8">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Tambah Pekerja / Tukang ke Proyek</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Input data pekerja langsung untuk proyek <strong>{{ $project->name }}</strong></p>
                </div>
                <button type="button" @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="assignFormAction" method="POST">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="worker_id" x-model="assignForm.worker_id">

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Input Nama Pekerja (Text Biasa) -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Nama Lengkap Pekerja / Tukang <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="assignForm.name" @input="onNameInput($event.target.value)" required list="worker-suggestions"
                               placeholder="Ketik nama pekerja / tukang (contoh: Bambang Sutrisno, Pak Slamet, dll)"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <datalist id="worker-suggestions">
                            @foreach($catalog as $catW)
                                <option value="{{ $catW->name }}">{{ $catW->trade }} • {{ $catW->formatted_daily_rate }}/hari</option>
                            @endforeach
                        </datalist>
                        <span class="text-[10px] text-slate-400 mt-1 block">Ketik langsung nama baru, atau pilih saran nama jika sudah terdaftar di master</span>
                    </div>

                    <!-- Bidang Keahlian & No Handphone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Bidang Keahlian / Posisi <span class="text-rose-500">*</span></label>
                            <select name="assigned_trade" x-model="assignForm.assigned_trade" required 
                                    class="select-clean w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @foreach($tradeOptions as $tr)
                                    <option value="{{ $tr }}">{{ $tr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">No. Handphone / WA (Opsional)</label>
                            <input type="text" name="phone" x-model="assignForm.phone" 
                                   placeholder="0812-xxxx-xxxx"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Upah Harian & Status Penugasan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Upah Harian Proyek (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="daily_wage" x-model="assignForm.daily_wage" required min="0" step="5000"
                                       placeholder="160000"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Status Penugasan <span class="text-rose-500">*</span></label>
                            <select name="status" x-model="assignForm.status" required 
                                    class="select-clean w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="active">Aktif Bekerja</option>
                                <option value="standby">Standby / Cadangan</option>
                                <option value="completed">Selesai Penugasan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tanggal Mulai Bekerja -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Tanggal Mulai Bekerja</label>
                        <input type="date" name="start_date" x-model="assignForm.start_date" 
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Penempatan Zona / Lantai / Catatan -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Penempatan Zona / Lantai / Catatan Penugasan</label>
                        <textarea name="notes" x-model="assignForm.notes" rows="2" 
                                  placeholder="Contoh: Zona Lantai 2, Pengecoran Balok & Plat"
                                  class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showAssignModal = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        Simpan Pekerja ke Proyek
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 3: EDIT PENUGASAN PEKERJA DI PROYEK -->
    <!-- ======================================================== -->
    <div x-show="showEditAssignModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" x-cloak>
        
        <div @click.away="showEditAssignModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden my-8">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Edit Penugasan Pekerja Proyek</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        <span class="font-bold text-slate-800" x-text="editAssignForm.worker_name"></span> 
                        (<span class="font-mono" x-text="editAssignForm.worker_code"></span>)
                    </p>
                </div>
                <button type="button" @click="showEditAssignModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="editAssignFormAction" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Posisi Proyek & Upah Disepakati -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Posisi / Tugas di Proyek Ini</label>
                            <input type="text" name="assigned_trade" x-model="editAssignForm.assigned_trade" 
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Upah Harian Proyek (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="daily_wage" x-model="editAssignForm.daily_wage" required min="0" step="5000"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Status Penugasan -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Status Penugasan <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="editAssignForm.status" required 
                                class="select-clean w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="active">Aktif Bekerja</option>
                            <option value="standby">Standby / Cadangan</option>
                            <option value="completed">Selesai Penugasan</option>
                        </select>
                    </div>

                    <!-- Tanggal Mulai & Tanggal Selesai -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Tanggal Mulai Bekerja</label>
                            <input type="date" name="start_date" x-model="editAssignForm.start_date" 
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Tanggal Selesai (Opsional)</label>
                            <input type="date" name="end_date" x-model="editAssignForm.end_date" 
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Penempatan Zona / Lantai / Catatan -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Penempatan Zona / Lantai / Catatan Penugasan</label>
                        <textarea name="notes" x-model="editAssignForm.notes" rows="2" 
                                  placeholder="Contoh: Zona Lantai 2, Pengecoran Balok & Plat"
                                  class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showEditAssignModal = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
