@extends('layouts.app')

@section('title', 'Mandor & Borongan (LS) — ' . $project->name)
@section('page_title', 'Mandor & Pekerjaan Borongan (Lump Sum / LS)')
@section('page_subtitle', 'Pencatatan paket pekerjaan borongan per mandor (LS), realisasi termin pembayaran, dan pemantauan progres fisik')

@section('content')
<script>
function workerManagementData() {
    return {
        activeTab: '{{ $activeTab ?? 'borongan' }}',

        // 0. Mandor Borongan (LS) Modal
        boronganList: @json($borongans),
        showBoronganModal: false,
        isEditBorongan: false,
        boronganModalTitle: 'Tambah Kontrak Borongan Mandor (LS)',
        boronganFormAction: '{{ route('workers.borongan.store') }}',
        boronganFormMethod: 'POST',
        boronganData: {
            id: null,
            project_id: '{{ $project->id }}',
            mandor_name: '',
            mandor_phone: '',
            work_scope: '',
            spk_number: '',
            contract_amount: 0,
            panjar_amount: 0,
            paid_amount: 0,
            progress_percent: 0,
            status: 'active',
            start_date: '{{ now()->toDateString() }}',
            end_date: '',
            notes: ''
        },

        // Panjar Modal
        showPanjarModal: false,
        selectedBoronganForPanjar: null,
        panjarData: {
            mandor_borongan_id: '',
            amount: '',
            panjar_date: '{{ now()->toDateString() }}',
            reference_no: '',
            notes: ''
        },

        formatRupiah(val) {
            if (!val || isNaN(val)) return '0';
            return Number(val).toLocaleString('id-ID');
        },

        openPanjarModal(borongan = null) {
            if (borongan) {
                this.selectedBoronganForPanjar = borongan;
                this.panjarData.mandor_borongan_id = borongan.id;
            } else {
                const first = (this.boronganList && this.boronganList.length > 0) ? this.boronganList[0] : null;
                this.selectedBoronganForPanjar = first;
                this.panjarData.mandor_borongan_id = first ? first.id : '';
            }
            this.panjarData.amount = '';
            this.panjarData.panjar_date = '{{ now()->toDateString() }}';
            this.panjarData.reference_no = '';
            this.panjarData.notes = '';
            this.showPanjarModal = true;
        },

        onPanjarMandorChange(id) {
            this.selectedBoronganForPanjar = this.boronganList.find(b => String(b.id) === String(id)) || null;
        },

        openAddBorongan() {
            this.isEditBorongan = false;
            this.boronganModalTitle = 'Tambah Kontrak Borongan Mandor (LS)';
            this.boronganFormAction = '{{ route('workers.borongan.store') }}';
            this.boronganFormMethod = 'POST';
            this.boronganData = {
                id: null,
                project_id: '{{ $project->id }}',
                mandor_name: '',
                mandor_phone: '',
                work_scope: '',
                spk_number: '',
                contract_amount: 0,
                panjar_amount: 0,
                paid_amount: 0,
                progress_percent: 0,
                status: 'active',
                start_date: '{{ now()->toDateString() }}',
                end_date: '',
                notes: ''
            };
            this.showBoronganModal = true;
        },

        openEditBorongan(b) {
            this.isEditBorongan = true;
            this.boronganModalTitle = 'Edit Kontrak Borongan: ' + b.mandor_name;
            this.boronganFormAction = '/workers/borongan/' + b.id;
            this.boronganFormMethod = 'POST';
            this.boronganData = {
                id: b.id,
                project_id: b.project_id,
                mandor_name: b.mandor_name || '',
                mandor_phone: b.mandor_phone || '',
                work_scope: b.work_scope || '',
                spk_number: b.spk_number || '',
                contract_amount: b.contract_amount || 0,
                panjar_amount: b.panjar_amount || 0,
                paid_amount: b.paid_amount || 0,
                progress_percent: b.progress_percent || 0,
                status: b.status || 'active',
                start_date: b.start_date ? b.start_date.substring(0, 10) : '',
                end_date: b.end_date ? b.end_date.substring(0, 10) : '',
                notes: b.notes || ''
            };
            this.showBoronganModal = true;
        },

        // 1. Master Worker Modal (Legacy)
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
            let workers = @json($catalog);
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
    };
}
</script>

<div class="space-y-6" x-data="workerManagementData()">

    <!-- 1. KPI SUMMARY CARDS: MANDOR & BORONGAN (LS) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- 1. Total Kontrak Borongan -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Paket Borongan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">
                {{ $boronganStats['total_contracts'] }} <span class="text-xs font-semibold text-slate-400">Kontrak LS</span>
            </div>
            <div class="mt-2 text-xs text-blue-700 font-semibold truncate" title="{{ $project->name }}">
                <span>Proyek: {{ Str::limit($project->name, 22) }}</span>
            </div>
        </div>

        <!-- 2. Total Nilai Kontrak (LS) -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Nilai Kontrak Borongan</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-xl font-black text-purple-900 tracking-tight font-mono truncate" title="Rp {{ number_format($boronganStats['total_contract_amount'], 0, ',', '.') }}">
                Rp {{ number_format($boronganStats['total_contract_amount'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs font-semibold flex items-center justify-between text-purple-700">
                <span>Total Komitmen (LS)</span>
                @if(($boronganStats['total_panjar_amount'] ?? 0) > 0)
                    <span class="text-[11px] bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded font-mono font-bold" title="Total Panjar Dipotong">
                        Panjar: Rp {{ number_format($boronganStats['total_panjar_amount'], 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- 3. Realisasi Terbayar -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Realisasi Terbayar</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-xl font-black text-emerald-800 tracking-tight font-mono truncate" title="Rp {{ number_format($boronganStats['total_paid_amount'], 0, ',', '.') }}">
                Rp {{ number_format($boronganStats['total_paid_amount'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-semibold">
                <span>Akumulasi Pembayaran Mandor</span>
            </div>
        </div>

        <!-- 4. Sisa Kewajiban Pembayaran -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sisa Hutang Borongan</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-xl font-black text-amber-900 tracking-tight font-mono truncate" title="Rp {{ number_format($boronganStats['total_remaining_amount'], 0, ',', '.') }}">
                Rp {{ number_format($boronganStats['total_remaining_amount'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-amber-700 font-semibold flex items-center justify-between">
                <span>Sisa Kontrak Belum Dibayar</span>
                @if(($boronganStats['total_panjar_amount'] ?? 0) > 0)
                    <span class="text-[10px] text-amber-800 font-semibold bg-amber-100 px-1.5 py-0.5 rounded">Net Panjar</span>
                @endif
            </div>
        </div>

        <!-- 5. Rata-rata Progres Fisik -->
        <div class="card-clean p-4 sm:p-5 card-clean-hover">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Progres</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-indigo-900 tracking-tight font-mono">
                {{ $boronganStats['avg_progress'] }}%
            </div>
            <div class="mt-2 text-xs text-indigo-700 font-semibold">
                <span>Pencapaian Fisik Lapangan</span>
            </div>
        </div>
    </div>

    <!-- 2. TAB SWITCHER -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <!-- TAB 1: Mandor & Pekerjaan Borongan (LS) -->
            <button type="button" @click="activeTab = 'borongan'"
                    :class="activeTab === 'borongan' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Mandor & Pekerjaan Borongan (LS)</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'borongan' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $borongans->count() }}</span>
            </button>

            <!-- TAB 2: Pekerja Harian di Proyek (Opsional) -->
            <button type="button" @click="activeTab = 'project'"
                    :class="activeTab === 'project' 
                        ? 'border-blue-600 text-blue-600 font-bold border-b-2 pb-3.5' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium pb-3.5 border-b-2'"
                    class="inline-flex items-center gap-2 text-sm transition-all cursor-pointer focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Pekerja Harian (Opsional)</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="activeTab === 'project' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">{{ $projectWorkers->count() }}</span>
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
    <!-- TAB 0: MANDOR & PEKERJAAN BORONGAN (LUMP SUM / LS) -->
    <!-- ======================================================== -->
    <div x-show="activeTab === 'borongan'" class="space-y-4">
        <div class="card-clean overflow-hidden"
             x-data="window.tableColumnHandler('borongan_table', ['no', 'mandor', 'work_scope', 'spk', 'contract_amount', 'panjar_amount', 'paid_amount', 'remaining_amount', 'progress', 'status', 'actions'])">
            <!-- Header Panel -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-md shadow-orange-500/20 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Daftar Paket Kontrak Borongan Mandor (LS)</h3>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">{{ $project->name }}</span>
                        </div>
                        <p class="text-xs text-slate-500">Pekerjaan borongan konstruksi (Lump Sum/LS) per mandor tanpa perlu rincian pekerja perorangan</p>
                    </div>
                </div>

                <!-- Action Button (Where circled XXX by user) -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" @click="openPanjarModal()"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-xl shadow-md shadow-amber-500/20 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Potong Panjar Mandor</span>
                    </button>

                    <button type="button" @click="openAddBorongan()"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Kontrak Borongan (LS)</span>
                    </button>
                </div>
            </div>

            <!-- Toolbar & Column Filter -->
            <div class="p-3 sm:p-4 bg-slate-50/70 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form action="{{ route('workers.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <input type="hidden" name="tab" value="borongan">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ $search }}" 
                               placeholder="Cari mandor / pekerjaan / SPK..." 
                               class="w-full text-xs pl-8 pr-7 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @if($search)
                            <a href="{{ route('workers.index', ['tab' => 'borongan', 'project_id' => $project->id]) }}" class="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 font-bold">&times;</a>
                        @endif
                    </div>

                    <div class="w-full sm:w-40">
                        <select name="status" onchange="this.form.submit()" class="select-clean w-full text-xs py-2 pl-3 pr-8 bg-white border border-slate-200 rounded-xl font-medium text-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                            <option value="">Semua Status</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Aktif Berjalan</option>
                            <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="hold" {{ $statusFilter === 'hold' ? 'selected' : '' }}>Ditahan / Hold</option>
                        </select>
                    </div>

                    @if($search || $statusFilter)
                        <a href="{{ route('workers.index', ['tab' => 'borongan', 'project_id' => $project->id]) }}" class="text-xs text-rose-600 font-semibold hover:underline px-1 py-1">
                            Reset Filter
                        </a>
                    @endif
                </form>

                <div class="flex items-center gap-3 self-end md:self-center">
                    <span class="text-xs text-slate-500 font-medium whitespace-nowrap">
                        Total <strong>{{ $borongans->count() }}</strong> kontrak borongan
                    </span>

                    <!-- Column Visibility Toggle -->
                    <x-table-column-toggle storageKey="borongan_table" :columns="[
                        ['key' => 'no', 'label' => 'No.'],
                        ['key' => 'mandor', 'label' => 'Mandor / PJ'],
                        ['key' => 'work_scope', 'label' => 'Lingkup Borongan (LS)'],
                        ['key' => 'spk', 'label' => 'No. SPK / Kontrak'],
                        ['key' => 'contract_amount', 'label' => 'Nilai Kontrak'],
                        ['key' => 'panjar_amount', 'label' => 'Pemotongan Panjar'],
                        ['key' => 'paid_amount', 'label' => 'Realisasi Terbayar'],
                        ['key' => 'remaining_amount', 'label' => 'Sisa Pembayaran'],
                        ['key' => 'progress', 'label' => 'Progres Fisik (%)'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'actions', 'label' => 'Aksi']
                    ]" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-clean min-w-[1020px]">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center" x-show="isColVisible('no')">No.</th>
                            <th class="py-3 px-4" x-show="isColVisible('mandor')">Mandor / PJ</th>
                            <th class="py-3 px-4" x-show="isColVisible('work_scope')">Lingkup Pekerjaan Borongan (LS)</th>
                            <th class="py-3 px-4" x-show="isColVisible('spk')">No. SPK / Kontrak</th>
                            <th class="py-3 px-4 text-right" x-show="isColVisible('contract_amount')">Nilai Kontrak (LS)</th>
                            <th class="py-3 px-4 text-right" x-show="isColVisible('panjar_amount')">Pemotongan Panjar</th>
                            <th class="py-3 px-4 text-right" x-show="isColVisible('paid_amount')">Realisasi Terbayar</th>
                            <th class="py-3 px-4 text-right" x-show="isColVisible('remaining_amount')">Sisa Pembayaran</th>
                            <th class="py-3 px-4 text-center" x-show="isColVisible('progress')">Progres Fisik</th>
                            <th class="py-3 px-4 text-center" x-show="isColVisible('status')">Status</th>
                            <th class="py-3 px-4 text-center" x-show="isColVisible('actions')">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($borongans as $index => $borongan)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 text-center font-mono text-slate-400" x-show="isColVisible('no')">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3.5 px-4" x-show="isColVisible('mandor')">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-xs">
                                            {{ strtoupper(substr($borongan->mandor_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 text-sm block">{{ $borongan->mandor_name }}</span>
                                            @if($borongan->mandor_phone)
                                                <span class="font-mono text-[11px] text-slate-400 block">{{ $borongan->mandor_phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4" x-show="isColVisible('work_scope')">
                                    <div class="font-bold text-slate-800 text-xs">{{ $borongan->work_scope }}</div>
                                    @if($borongan->notes)
                                        <span class="text-[11px] text-slate-400 italic block mt-0.5 line-clamp-1">{{ $borongan->notes }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4" x-show="isColVisible('spk')">
                                    <span class="font-mono font-bold text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200">
                                        {{ $borongan->spk_number ?: '-' }}
                                    </span>
                                    @if($borongan->start_date)
                                        <span class="block text-[10px] text-slate-400 mt-1">Mulai: {{ $borongan->start_date->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900 text-sm whitespace-nowrap" x-show="isColVisible('contract_amount')">
                                    <div>Rp {{ number_format($borongan->contract_amount, 0, ',', '.') }}</div>
                                    @if($borongan->panjar_amount > 0)
                                        <span class="block text-[10px] text-slate-400 font-semibold" title="Nilai Bersih Setelah Panjar">
                                            Bersih: Rp {{ number_format($borongan->net_contract_amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap" x-show="isColVisible('panjar_amount')">
                                    @if($borongan->panjar_amount > 0)
                                        <div class="text-amber-700 font-bold">Rp {{ number_format($borongan->panjar_amount, 0, ',', '.') }}</div>
                                        <button type="button" @click='openPanjarModal(@json($borongan))' class="text-[10px] text-amber-600 hover:text-amber-800 font-semibold underline cursor-pointer">
                                            {{ $borongan->panjars->count() > 0 ? $borongan->panjars->count() . 'x potongan' : 'Kelola panjar' }}
                                        </button>
                                    @else
                                        <button type="button" @click='openPanjarModal(@json($borongan))' class="text-[11px] text-slate-400 hover:text-amber-600 font-medium cursor-pointer">
                                            + Potong Panjar
                                        </button>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap" x-show="isColVisible('paid_amount')">
                                    <div>Rp {{ number_format($borongan->paid_amount, 0, ',', '.') }}</div>
                                    <span class="text-[10px] text-emerald-600 font-semibold">({{ $borongan->payment_percent }}%)</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-700 whitespace-nowrap" x-show="isColVisible('remaining_amount')">
                                    Rp {{ number_format($borongan->remaining_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center" x-show="isColVisible('progress')">
                                    <div class="inline-flex flex-col items-center gap-1 min-w-[70px]">
                                        <span class="font-mono font-black text-xs text-blue-700">{{ $borongan->progress_percent }}%</span>
                                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ min(100, $borongan->progress_percent) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap" x-show="isColVisible('status')">
                                    @php
                                        $statusBadge = match($borongan->status) {
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'hold' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-blue-50 text-blue-700 border-blue-200',
                                        };
                                        $statusLabel = match($borongan->status) {
                                            'completed' => 'Selesai',
                                            'hold' => 'Ditahan',
                                            default => 'Aktif',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusBadge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap" x-show="isColVisible('actions')">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Quick Panjar Button -->
                                        <button type="button" @click='openPanjarModal(@json($borongan))'
                                                class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition-colors cursor-pointer"
                                                title="Catat Pemotongan Panjar Mandor">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </button>
                                        <button type="button" @click='openEditBorongan(@json($borongan))'
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Kontrak Borongan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form action="{{ route('workers.borongan.destroy', $borongan->id) }}" method="POST" onsubmit="return confirm('Hapus kontrak borongan {{ addslashes($borongan->mandor_name) }} ({{ addslashes($borongan->work_scope) }})?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Borongan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-slate-400">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <p class="text-xs font-semibold text-slate-600">Belum ada paket pekerjaan borongan (LS) yang dicatat.</p>
                                        <button type="button" @click="openAddBorongan()" class="text-xs text-blue-600 font-bold hover:underline cursor-pointer">
                                            + Tambah Kontrak Borongan Mandor Sekarang
                                        </button>
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
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 flex items-center justify-center min-h-screen" x-cloak>
        
        <div @click.away="showMasterModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="font-bold text-slate-900 text-sm" x-text="masterModalTitle"></h3>
                <button type="button" @click="showMasterModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="masterFormAction" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                @csrf
                <template x-if="masterFormMethod === 'PUT'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
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
                            <input type="number" name="daily_rate" x-model="masterForm.daily_rate" required min="0" step="any"
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
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
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
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 flex items-center justify-center min-h-screen" x-cloak>
        
        <div @click.away="showAssignModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Tambah Pekerja / Tukang ke Proyek</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Input data pekerja langsung untuk proyek <strong>{{ $project->name }}</strong></p>
                </div>
                <button type="button" @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="assignFormAction" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="worker_id" x-model="assignForm.worker_id">

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
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
                                <input type="number" name="daily_wage" x-model="assignForm.daily_wage" required min="0" step="any"
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
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
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
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 flex items-center justify-center min-h-screen" x-cloak>
        
        <div @click.away="showEditAssignModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
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
            <form :action="editAssignFormAction" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
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
                                <input type="number" name="daily_wage" x-model="editAssignForm.daily_wage" required min="0" step="any"
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
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
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

    <!-- ======================================================== -->
    <!-- MODAL 4: TAMBAH / EDIT KONTRAK BORONGAN MANDOR (LS) -->
    <!-- ======================================================== -->
    <div x-show="showBoronganModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 flex items-center justify-center min-h-screen" x-cloak>
        
        <div @click.away="showBoronganModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto overflow-hidden">
            
            <form :action="boronganFormAction" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                @csrf
                <template x-if="isEditBorongan">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-500 text-white font-bold flex items-center justify-center shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm" x-text="boronganModalTitle"></h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kontrak Borongan Lump Sum (LS) Proyek <strong>{{ $project->name }}</strong></p>
                        </div>
                    </div>
                    <button type="button" @click="showBoronganModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <!-- Mandor & No Telp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Nama Mandor / PIC Borongan <span class="text-rose-500">*</span></label>
                            <input type="text" name="mandor_name" x-model="boronganData.mandor_name" required 
                                   placeholder="Contoh: Mandor Budi / Mandor Ali / Mandor Rudi"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">No. Handphone / WhatsApp Mandor</label>
                            <input type="text" name="mandor_phone" x-model="boronganData.mandor_phone" 
                                   placeholder="0812-xxxx-xxxx"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Lingkup Pekerjaan Borongan (LS) -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Lingkup Paket Pekerjaan Borongan (LS) <span class="text-rose-500">*</span></label>
                        <input type="text" name="work_scope" x-model="boronganData.work_scope" required 
                               placeholder="Contoh: Pekerjaan Borongan Aspal / Borongan Beton Struktur / Borongan Plafon"
                               class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- No SPK & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Nomor SPK / Surat Perjanjian Kerja</label>
                            <input type="text" name="spk_number" x-model="boronganData.spk_number" 
                                   placeholder="Mis. SPK/2026/BOR-001"
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Status Pekerjaan <span class="text-rose-500">*</span></label>
                            <select name="status" x-model="boronganData.status" required
                                    class="select-clean w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="active">Aktif Berjalan</option>
                                <option value="completed">Selesai / Lunas</option>
                                <option value="hold">Ditahan / Hold</option>
                            </select>
                        </div>
                    </div>

                    <!-- Nilai Kontrak (LS), Pemotongan Panjar & Realisasi Terbayar -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Nilai Kontrak (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="contract_amount" x-model.number="boronganData.contract_amount" required min="0" step="any"
                                       placeholder="100000000"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-black text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Total komitmen borongan</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-amber-700 text-xs mb-1">Pemotongan Panjar (Rp)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-amber-500">Rp</span>
                                <input type="number" name="panjar_amount" x-model.number="boronganData.panjar_amount" min="0" step="any"
                                       placeholder="0"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-amber-50/50 border border-amber-200 rounded-xl font-mono font-bold text-amber-800 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <span class="text-[10px] text-amber-600 mt-1 block">Kasbon / uang muka dipotong</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Realisasi Terbayar (Rp)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="paid_amount" x-model.number="boronganData.paid_amount" min="0" step="any"
                                       placeholder="0"
                                       class="w-full text-xs pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Termin yang sudah dibayar</span>
                        </div>
                    </div>

                    <!-- Live Calculation Badge -->
                    <div class="p-3 bg-gradient-to-r from-slate-50 to-amber-50/50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="text-slate-500 text-[11px] block">Hasil Bersih Kontrak Mandor:</span>
                            <span class="font-mono font-black text-slate-900" x-text="'Rp ' + formatRupiah(Math.max(0, (boronganData.contract_amount || 0) - (boronganData.panjar_amount || 0)))"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500 text-[11px] block">Sisa Hutang Kontrak:</span>
                            <span class="font-mono font-black text-amber-700" x-text="'Rp ' + formatRupiah(Math.max(0, (boronganData.contract_amount || 0) - (boronganData.panjar_amount || 0) - (boronganData.paid_amount || 0)))"></span>
                        </div>
                    </div>

                    <!-- Progres Fisik & Periode -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Progres Fisik (%)</label>
                            <div class="relative">
                                <input type="number" name="progress_percent" x-model="boronganData.progress_percent" min="0" max="100"
                                       placeholder="70"
                                       class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono font-bold text-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400">%</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" x-model="boronganData.start_date" 
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 text-xs mb-1">Target Selesai</label>
                            <input type="date" name="end_date" x-model="boronganData.end_date" 
                                   class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-xs mb-1">Catatan Tambahan / Ketentuan Khusus</label>
                        <textarea name="notes" x-model="boronganData.notes" rows="2" 
                                  placeholder="Contoh: Borongan termasuk material bantu, alat kerja, dan perapian sisa puing"
                                  class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="showBoronganModal = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20 transition-all cursor-pointer"
                            x-text="isEditBorongan ? 'Perbarui Kontrak Borongan' : 'Simpan Kontrak Borongan'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 5: POTONG PANJAR MANDOR (HASIL KONTRAK LS) -->
    <!-- ======================================================== -->
    <div x-show="showPanjarModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 md:p-6 flex items-center justify-center min-h-screen" 
         x-cloak>
        
        <div @click.away="showPanjarModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] my-auto overflow-hidden"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white font-bold flex items-center justify-center shadow-lg shadow-amber-500/25 shrink-0 ring-4 ring-amber-500/10">
                        <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-slate-900 text-sm sm:text-base">Pemotongan Panjar Hasil Kontrak Mandor</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">Potong SPK</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pencatatan kasbon uang muka yang langsung memotong hasil bersih kontrak borongan (LS)</p>
                    </div>
                </div>
                <button type="button" @click="showPanjarModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 space-y-5 flex-1 overflow-y-auto">
                <!-- 1. Pemilihan Mandor & Ringkasan Kontrak -->
                <div class="bg-slate-50/80 rounded-2xl border border-slate-200/80 p-4 sm:p-4.5 space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <label class="block font-bold text-slate-800 text-xs uppercase tracking-wide">Pilih Paket Kontrak Mandor Borongan <span class="text-rose-500">*</span></label>
                        </div>
                        <template x-if="selectedBoronganForPanjar">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="selectedBoronganForPanjar.status === 'completed' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                      x-text="selectedBoronganForPanjar.status === 'completed' ? 'Selesai / Lunas' : 'Aktif Berjalan'"></span>
                                <span class="text-xs text-slate-500 font-medium font-mono" x-text="selectedBoronganForPanjar.spk_number ? 'SPK: ' + selectedBoronganForPanjar.spk_number : ''"></span>
                            </div>
                        </template>
                    </div>

                    @if($borongans->count() > 0)
                        <div>
                            <select x-model="panjarData.mandor_borongan_id" 
                                    @change="onPanjarMandorChange($event.target.value)"
                                    class="w-full text-xs font-semibold text-slate-800 bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 pr-10 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-none shadow-2xs transition-all cursor-pointer">
                                @foreach($borongans as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->mandor_name }} &mdash; {{ $b->work_scope }} (Nilai Kontrak: Rp {{ number_format($b->contract_amount, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Belum ada paket kontrak borongan di proyek ini. Silakan buat kontrak borongan terlebih dahulu.</span>
                        </div>
                    @endif

                    <!-- Financial Status KPI Cards -->
                    <template x-if="selectedBoronganForPanjar">
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 pt-1">
                            <!-- 1. Nilai Kontrak (LS) -->
                            <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="flex items-center justify-between text-slate-400 mb-1">
                                    <span class="text-[10px] font-semibold text-slate-500">Nilai Kontrak (LS)</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="text-xs sm:text-sm font-black text-slate-900 font-mono tracking-tight" x-text="'Rp ' + formatRupiah(selectedBoronganForPanjar.contract_amount)"></div>
                                <span class="text-[10px] text-slate-400 mt-0.5 block truncate" x-text="selectedBoronganForPanjar.mandor_name + ' • ' + selectedBoronganForPanjar.work_scope"></span>
                            </div>

                            <!-- 2. Total Panjar Dipotong -->
                            <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-200/70 shadow-2xs">
                                <div class="flex items-center justify-between text-amber-600 mb-1">
                                    <span class="text-[10px] font-semibold text-amber-800">Panjar Dipotong</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="text-xs sm:text-sm font-black text-amber-800 font-mono tracking-tight" x-text="'Rp ' + formatRupiah(selectedBoronganForPanjar.panjar_amount || 0)"></div>
                                <span class="text-[10px] font-medium text-amber-700 mt-0.5 block" 
                                      x-text="(selectedBoronganForPanjar.contract_amount > 0 ? ((selectedBoronganForPanjar.panjar_amount || 0) / selectedBoronganForPanjar.contract_amount * 100).toFixed(1) : 0) + '% dari kontrak'"></span>
                            </div>

                            <!-- 3. Hasil Bersih Kontrak -->
                            <div class="bg-blue-50/70 p-3 rounded-xl border border-blue-200/70 shadow-2xs">
                                <div class="flex items-center justify-between text-blue-600 mb-1">
                                    <span class="text-[10px] font-semibold text-blue-800">Hasil Bersih Kontrak</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="text-xs sm:text-sm font-black text-blue-900 font-mono tracking-tight" x-text="'Rp ' + formatRupiah(Math.max(0, (selectedBoronganForPanjar.contract_amount || 0) - (selectedBoronganForPanjar.panjar_amount || 0)))"></div>
                                <span class="text-[10px] text-blue-600 mt-0.5 block">Kontrak dikurangi kasbon</span>
                            </div>

                            <!-- 4. Sisa Hutang Kontrak -->
                            <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-200/70 shadow-2xs">
                                <div class="flex items-center justify-between text-emerald-600 mb-1">
                                    <span class="text-[10px] font-semibold text-emerald-800">Sisa Belum Cair</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="text-xs sm:text-sm font-black text-emerald-800 font-mono tracking-tight" x-text="'Rp ' + formatRupiah(Math.max(0, (selectedBoronganForPanjar.contract_amount || 0) - (selectedBoronganForPanjar.panjar_amount || 0) - (selectedBoronganForPanjar.paid_amount || 0)))"></div>
                                <span class="text-[10px] text-emerald-600 mt-0.5 block">Sisa komitmen mandor</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- 2. Form Input Panjar Baru & Riwayat Panjar (2 Kolom) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    
                    <!-- KOLOM KIRI (7 Cols): FORM INPUT PEMOTONGAN PANJAR BARU -->
                    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 sm:p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide">Input Pemotongan Panjar Baru</h4>
                                    <p class="text-[11px] text-slate-500">Catat uang muka kasbon yang diserahkan ke mandor</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-semibold text-rose-500 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">* Wajib diisi</span>
                        </div>

                        <form action="{{ route('workers.borongan.panjar.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="mandor_borongan_id" :value="panjarData.mandor_borongan_id">

                            <!-- Nominal Pemotongan Panjar -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block font-bold text-slate-700 text-xs">Nominal Pemotongan Panjar (Rp) <span class="text-rose-500">*</span></label>
                                    <template x-if="panjarData.amount > 0">
                                        <span class="text-[11px] font-mono font-bold text-amber-700" x-text="'Rp ' + formatRupiah(panjarData.amount)"></span>
                                    </template>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-2.5 text-xs font-black text-amber-600">Rp</span>
                                    <input type="number" name="amount" x-model.number="panjarData.amount" required min="1" step="any"
                                           placeholder="Contoh: 5000000"
                                           class="w-full text-xs pl-10 pr-3 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl font-mono font-black text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-none transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>

                                <!-- Quick Amount Chips -->
                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                    <span class="text-[10px] font-semibold text-slate-400 mr-0.5">Preset:</span>
                                    <button type="button" @click="panjarData.amount = 1000000" class="text-[10px] font-mono font-bold px-2.5 py-1 bg-slate-50 hover:bg-amber-500 hover:text-white border border-slate-200 hover:border-amber-500 rounded-lg text-slate-600 transition-all cursor-pointer shadow-2xs">+1 Jt</button>
                                    <button type="button" @click="panjarData.amount = 2000000" class="text-[10px] font-mono font-bold px-2.5 py-1 bg-slate-50 hover:bg-amber-500 hover:text-white border border-slate-200 hover:border-amber-500 rounded-lg text-slate-600 transition-all cursor-pointer shadow-2xs">+2 Jt</button>
                                    <button type="button" @click="panjarData.amount = 5000000" class="text-[10px] font-mono font-bold px-2.5 py-1 bg-slate-50 hover:bg-amber-500 hover:text-white border border-slate-200 hover:border-amber-500 rounded-lg text-slate-600 transition-all cursor-pointer shadow-2xs">+5 Jt</button>
                                    <button type="button" @click="panjarData.amount = 10000000" class="text-[10px] font-mono font-bold px-2.5 py-1 bg-slate-50 hover:bg-amber-500 hover:text-white border border-slate-200 hover:border-amber-500 rounded-lg text-slate-600 transition-all cursor-pointer shadow-2xs">+10 Jt</button>
                                    <button type="button" @click="panjarData.amount = ''" class="text-[10px] font-semibold px-2 py-1 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer">Reset</button>
                                </div>
                            </div>

                            <!-- Tanggal & No. Bukti / Ref -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block font-bold text-slate-700 text-xs mb-1.5">Tanggal Panjar / Kasbon <span class="text-rose-500">*</span></label>
                                    <input type="date" name="panjar_date" x-model="panjarData.panjar_date" required 
                                           class="w-full text-xs p-2.5 bg-slate-50/50 border border-slate-200 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-none transition-all">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 text-xs mb-1.5">No. Bukti / Kasbon / Ref</label>
                                    <input type="text" name="reference_no" x-model="panjarData.reference_no" 
                                           placeholder="Mis. KSB/2026/001 atau Transfer Mandor"
                                           class="w-full text-xs p-2.5 bg-slate-50/50 border border-slate-200 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-none transition-all">
                                </div>
                            </div>

                            <!-- Keterangan / Keperluan Panjar -->
                            <div>
                                <label class="block font-bold text-slate-700 text-xs mb-1.5">Keperluan / Keterangan Panjar</label>
                                <input type="text" name="notes" x-model="panjarData.notes" 
                                       placeholder="Mis. Panjar uang makan tukang / operasional mandor"
                                       class="w-full text-xs p-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-none transition-all">
                            </div>

                            <!-- Live Impact Simulation -->
                            <template x-if="selectedBoronganForPanjar && panjarData.amount > 0">
                                <div class="p-3 bg-gradient-to-r from-amber-50 to-orange-50/60 rounded-xl border border-amber-200/80 space-y-1.5">
                                    <div class="flex items-center justify-between text-xs font-semibold text-amber-900">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Simulasi Hasil Bersih Baru:</span>
                                        </span>
                                        <span class="font-mono font-black text-amber-900" x-text="'Rp ' + formatRupiah(Math.max(0, (selectedBoronganForPanjar.contract_amount || 0) - (selectedBoronganForPanjar.panjar_amount || 0) - Number(panjarData.amount)))"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-amber-800 font-medium">
                                        <span>Sisa Hutang Kontrak Baru:</span>
                                        <span class="font-mono font-bold text-amber-800" x-text="'Rp ' + formatRupiah(Math.max(0, (selectedBoronganForPanjar.contract_amount || 0) - (selectedBoronganForPanjar.panjar_amount || 0) - (selectedBoronganForPanjar.paid_amount || 0) - Number(panjarData.amount)))"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="pt-1">
                                <button type="submit" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-extrabold bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-xl shadow-md shadow-amber-500/25 transition-all cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Simpan Pemotongan Panjar</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- KOLOM KANAN (5 Cols): RIWAYAT PEMOTONGAN PANJAR MANDOR TERPILIH -->
                    <div class="lg:col-span-5 bg-slate-50/70 rounded-2xl border border-slate-200/90 shadow-2xs p-4 sm:p-4.5 flex flex-col">
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-3 mb-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-slate-200/70 text-slate-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide">Riwayat Panjar</h4>
                                    <p class="text-[11px] text-slate-500">Catatan kasbon mandor ini</p>
                                </div>
                            </div>
                            <template x-if="selectedBoronganForPanjar && selectedBoronganForPanjar.panjars">
                                <span class="text-[10px] font-bold text-slate-600 font-mono bg-white border border-slate-200 px-2 py-0.5 rounded-full" x-text="selectedBoronganForPanjar.panjars.length + ' catatan'"></span>
                            </template>
                        </div>

                        <!-- List Catatan Panjar -->
                        <div class="max-h-[380px] overflow-y-auto space-y-2.5 pr-1">
                            <template x-if="selectedBoronganForPanjar && selectedBoronganForPanjar.panjars && selectedBoronganForPanjar.panjars.length > 0">
                                <div class="space-y-2">
                                    <template x-for="(p, pIdx) in selectedBoronganForPanjar.panjars" :key="p.id">
                                        <div class="p-3 bg-white rounded-xl border border-slate-200/80 shadow-2xs hover:border-amber-300 transition-all space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700" x-text="p.panjar_date ? p.panjar_date.substring(0, 10) : '-'"></span>
                                                    <span class="text-[10px] font-mono font-semibold text-slate-500 truncate max-w-[90px]" x-text="p.reference_no || '-'"></span>
                                                </div>
                                                <form :action="'/workers/borongan/panjar/' + p.id" method="POST" 
                                                      onsubmit="return confirm('Hapus catatan pemotongan panjar ini? Saldo panjar mandor akan dikembalikan.')" 
                                                      class="inline">
                                                    @csrf
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Panjar">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="flex items-baseline justify-between">
                                                <span class="text-[11px] text-slate-600 line-clamp-1 italic" x-text="p.notes || 'Tanpa keterangan'"></span>
                                                <span class="font-mono font-black text-amber-700 text-xs shrink-0 ml-2" x-text="'Rp ' + formatRupiah(p.amount)"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!selectedBoronganForPanjar || !selectedBoronganForPanjar.panjars || selectedBoronganForPanjar.panjars.length === 0">
                                <div class="py-10 px-4 bg-white border border-dashed border-slate-200 rounded-xl text-center space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">Belum Ada Riwayat Panjar</p>
                                    <p class="text-[11px] text-slate-400 max-w-[200px] mx-auto">Belum ada catatan pemotongan panjar / kasbon untuk mandor ini.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50/80 border-t border-slate-200/80 flex items-center justify-between shrink-0">
                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 hidden sm:flex">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Pemotongan panjar otomatis mengurangi nilai tagihan akhir kontrak borongan.</span>
                </div>
                <button type="button" @click="showPanjarModal = false" 
                        class="px-5 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-200/70 rounded-xl transition-all cursor-pointer ml-auto">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
