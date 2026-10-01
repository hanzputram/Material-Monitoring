<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Material Monitoring & RAB Konstruksi')</title>

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex" x-data="{ sidebarOpen: false, showCalcModal: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden"
         @click="sidebarOpen = false" x-cloak></div>

    <!-- SIDEBAR -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
        
        <!-- Sidebar Brand -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 bg-white">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 font-bold text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <span class="font-extrabold text-slate-900 tracking-tight text-lg">K-RAB</span>
                    <span class="block text-xs font-semibold text-blue-600 tracking-wider uppercase -mt-0.5">Material & Proyek</span>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Project Selector Switcher -->
        @php
            $activeProject = \App\Models\Project::find(session('active_project_id')) ?? \App\Models\Project::first();
            $allProjects = \App\Models\Project::orderBy('name')->get();
        @endphp
        <div class="p-4 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Proyek Aktif</span>
                @if(auth()->user()?->hasModulePermission('projects'))
                    <a href="{{ route('projects.create') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Baru
                    </a>
                @endif
            </div>
            @if($allProjects->isNotEmpty())
                <form action="{{ route('projects.switch') }}" method="POST">
                    @csrf
                    <div>
                        <select name="project_id" onchange="this.form.submit()" 
                                class="w-full text-xs font-bold select-clean py-2.5 pl-3 text-slate-800">
                            @foreach($allProjects as $p)
                                <option value="{{ $p->id }}" {{ $activeProject && $activeProject->id == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->budget_year }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @if($activeProject)
                    <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                        <span>{{ $activeProject->prototype_type ?: 'Standar' }} • {{ $activeProject->floor_count }} Lt</span>
                        @if(auth()->user()?->hasModulePermission('projects'))
                            <a href="{{ route('projects.edit', $activeProject->id) }}" class="text-slate-400 hover:text-slate-700 underline">Pengaturan</a>
                        @endif
                    </div>
                @endif
            @else
                <div class="p-2.5 bg-white rounded-xl border border-slate-200 text-center">
                    <span class="text-[11px] text-slate-500 block mb-1.5 font-medium">Belum ada proyek aktif</span>
                    @if(auth()->user()?->hasModulePermission('projects'))
                        <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white text-[11px] font-bold rounded-lg shadow-sm">
                            + Buat Proyek
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
            <!-- GROUP 1: UTAMA -->
            @if(auth()->user()?->hasAnyModulePermission(['dashboard', 'cost']))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Monitoring & Biaya</p>
                    <div class="space-y-1">
                        @if(auth()->user()?->hasModulePermission('dashboard'))
                            <a href="{{ route('dashboard') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Dashboard Monitoring
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('cost'))
                            <a href="{{ route('cost.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cost.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('cost.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Biaya Realisasi (RAB vs Real)
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- GROUP 2: PERENCANAAN RAB -->
            @if(auth()->user()?->hasAnyModulePermission(['rab', 'projects']))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Manajemen RAB</p>
                    <div class="space-y-1">
                        @if(auth()->user()?->hasModulePermission('rab'))
                            <a href="{{ route('rab.builder') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('rab.builder') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('rab.builder') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Pembuatan RAB
                            </a>
                            <a href="{{ route('rab.import.form') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('rab.import.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('rab.import.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                </svg>
                                Impor Excel & Template
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- GROUP 3: PENGADAAN & LAPANGAN -->
            @if(auth()->user()?->canReadPo() || auth()->user()?->canReadDo() || auth()->user()?->canReadReturn() || auth()->user()?->hasModulePermission('suppliers') || auth()->user()?->hasModulePermission('materials') || auth()->user()?->hasModulePermission('variance'))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pengadaan & Lapangan</p>
                    <div class="space-y-1">
                        <!-- 1. Pesanan Pembelian (PO) -->
                        @if(auth()->user()?->canReadPo())
                            <a href="{{ route('procurement.po.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.po.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('procurement.po.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>Pesanan Pembelian</span>
                            </a>
                        @endif

                        <!-- 2. Penerimaan Barang (DO Lapangan) -->
                        @if(auth()->user()?->canReadDo())
                            <a href="{{ route('procurement.do.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.do.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('procurement.do.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span>Penerimaan Barang</span>
                            </a>
                        @endif

                        <!-- 3. Retur Pembelian -->
                        @if(auth()->user()?->canReadReturn())
                            <a href="{{ route('procurement.returns.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.returns.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('procurement.returns.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                <span>Retur Pembelian</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('suppliers'))
                            <a href="{{ route('suppliers.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('suppliers.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Master Supplier
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('materials') || auth()->user()?->hasModulePermission('suppliers') || auth()->user()?->hasModulePermission('rab'))
                            <a href="{{ route('materials.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('materials.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('materials.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                <span>Master Item & Material</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('variance'))
                            <a href="{{ route('variance.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('variance.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 {{ request()->routeIs('variance.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <span>Validasi Ganda (DO+Inv)</span>
                                </div>
                                @php
                                    $pendingValCount = \App\Models\MaterialVarianceValidation::where('status', '!=', 'fully_validated')->count();
                                @endphp
                                @if($pendingValCount > 0)
                                    <span class="px-2 py-0.5 text-[11px] font-bold bg-amber-100 text-amber-800 rounded-full">{{ $pendingValCount }}</span>
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- GROUP 4: KEUANGAN & PEMBAYARAN -->
            @if(auth()->user()?->canReadDownPayment() || auth()->user()?->canReadInvoice() || auth()->user()?->canReadPayment() || auth()->user()?->hasModulePermission('finance') || auth()->user()?->isSuperAdmin())
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Keuangan & Pembayaran</p>
                    <div class="space-y-1">
                        <!-- 1. Uang Muka Pembelian -->
                        @if(auth()->user()?->canReadDownPayment())
                            <a href="{{ route('finance.down-payments.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('finance.down-payments.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('finance.down-payments.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Uang Muka Pembelian</span>
                            </a>
                        @endif

                        <!-- 2. Faktur Pembelian (Invoice) -->
                        @if(auth()->user()?->canReadInvoice())
                            <a href="{{ route('procurement.invoices.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('procurement.invoices.*') || request()->routeIs('finance.invoices.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('procurement.invoices.*') || request()->routeIs('finance.invoices.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Faktur Pembelian</span>
                            </a>
                        @endif

                        <!-- 3. Pembayaran Pembelian -->
                        @if(auth()->user()?->canReadPayment())
                            <a href="{{ route('finance.payments.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('finance.payments.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('finance.payments.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Pembayaran Pembelian</span>
                            </a>
                        @endif

                        <!-- 4. Summary Pembelian Toko (Lintas Proyek) -->
                        <a href="{{ route('finance.supplier-summary.index') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('finance.supplier-summary.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('finance.supplier-summary.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="flex-1">Rekap Pembelian Toko</span>
                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800 rounded">Lintas</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- GROUP 5: SUMBER DAYA & TENAGA KERJA -->
            @if(auth()->user()?->hasAnyModulePermission(['equipment', 'workers', 'alerts']))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Sumber Daya & Tenaga Kerja</p>
                    <div class="space-y-1">
                        @if(auth()->user()?->hasModulePermission('equipment'))
                            <a href="{{ route('equipment.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('equipment.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('equipment.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Master Alat & Mesin</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('workers'))
                            <a href="{{ route('workers.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('workers.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <svg class="w-5 h-5 {{ request()->routeIs('workers.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Pekerja / Tukang</span>
                            </a>
                        @endif

                        @if(auth()->user()?->hasModulePermission('alerts'))
                            <a href="{{ route('alerts.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('alerts.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 {{ request()->routeIs('alerts.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    <span>Pusat Peringatan</span>
                                </div>
                                @php
                                    $unreadAlerts = \App\Models\Alert::where('status', 'unread')->count();
                                @endphp
                                @if($unreadAlerts > 0)
                                    <span class="px-2 py-0.5 text-[11px] font-bold bg-rose-100 text-rose-700 rounded-full">{{ $unreadAlerts }}</span>
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- GROUP 5: ADMINISTRASI SISTEM -->
            @if(auth()->user()?->hasModulePermission('users'))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Administrasi Sistem</p>
                    <div class="space-y-1">
                        <a href="{{ route('users.index') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Pengguna &amp; Hak Akses</span>
                        </a>
                    </div>
                </div>
            @endif
        </nav>

        <!-- Current User Profile & Real Logout in Sidebar Footer -->
        <div class="p-3.5 border-t border-slate-200 bg-slate-50/70">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-lg {{ auth()->user()?->isSuperAdmin() ? 'bg-purple-600' : 'bg-blue-600' }} text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-xs truncate">{{ auth()->user()?->name ?? 'User' }}</div>
                        <span class="text-[10px] font-bold text-blue-600 truncate block">{{ auth()->user()?->role_label ?? 'Staf' }}</span>
                    </div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')"
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- TOP HEADER NAVBAR -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-3 sm:px-6 z-20">
            <!-- Left Header -->
            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="min-w-0">
                    <h2 class="text-sm sm:text-lg font-bold text-slate-900 leading-tight truncate">@yield('page_title', 'Dashboard')</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 hidden sm:block truncate">@yield('page_subtitle', 'Sistem Monitoring RAB & Pengendalian Material Proyek')</p>
                </div>
            </div>

            <!-- Right Header Actions -->
            <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                <!-- Dimension Calculator Helper Button -->
                <button type="button" @click="showCalcModal = true" 
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors border border-slate-200">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Kalkulator Satuan (M²/M³)
                </button>
                <button type="button" @click="showCalcModal = true" 
                        class="sm:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                        title="Kalkulator Satuan (M²/M³)">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </button>

                <!-- Download Template Button -->
                <a href="{{ route('rab.template.download') }}" 
                   class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 rounded-lg transition-colors border border-slate-300 shadow-sm"
                   title="Unduh Template Excel Resmi">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Template Excel
                </a>

                <!-- Notification Bell -->
                <a href="{{ route('alerts.index') }}" class="relative p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadAlerts > 0)
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white"></span>
                    @endif
                </a>

                <!-- User Avatar / Role Pill & Logout -->
                <div class="pl-2 border-l border-slate-200 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full {{ auth()->user()?->isSuperAdmin() ? 'bg-purple-600 ring-2 ring-purple-300' : 'bg-slate-800' }} text-white font-bold text-xs flex items-center justify-center shadow-sm">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                    </div>
                    <div class="hidden xl:block text-left text-xs leading-none">
                        <div class="font-bold text-slate-800">{{ auth()->user()?->name ?? 'User' }}</div>
                        <span class="text-[10px] text-blue-600 font-semibold">{{ auth()->user()?->role_label ?? 'Staf' }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="ml-1">
                        @csrf
                        <button type="submit" title="Keluar dari Sistem" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE VIEW -->
        <main class="flex-1 overflow-y-auto p-3 sm:p-5 lg:p-6 xl:p-7">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-800 font-bold text-lg">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-rose-500 hover:text-rose-800 font-bold text-lg">&times;</button>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-between shadow-sm" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <span class="text-sm font-medium">{{ session('warning') }}</span>
                    </div>
                    <button @click="show = false" class="text-amber-500 hover:text-amber-800 font-bold text-lg">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- MODAL: DIMENSION CALCULATOR (M2 / M3) -->
    <div x-show="showCalcModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showCalcModal = false" aria-hidden="true"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center relative z-10 pointer-events-none">
            <div class="relative w-full max-w-lg p-6 my-8 text-left bg-white rounded-2xl shadow-2xl border border-slate-200 pointer-events-auto transform transition-all"
                 @click.stop
                 x-data="{
                    mode: 'm2',
                    panjang: '',
                    lebar: '',
                    tinggi: '',
                    get result() {
                        let p = parseFloat(this.panjang) || 0;
                        let l = parseFloat(this.lebar) || 0;
                        let t = parseFloat(this.tinggi) || 0;
                        if (this.mode === 'm2') {
                            return (p * l).toFixed(4);
                        } else {
                            return (p * l * t).toFixed(4);
                        }
                    }
                 }">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Kalkulator Satuan Dimensi</h3>
                    </div>
                    <button @click="showCalcModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Mode Selector -->
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                        <button type="button" @click="mode = 'm2'" :class="mode === 'm2' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-600 font-medium'" class="py-2 text-xs rounded-lg transition-all text-center">
                            Luas (M²) = P × L
                        </button>
                        <button type="button" @click="mode = 'm3'" :class="mode === 'm3' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-600 font-medium'" class="py-2 text-xs rounded-lg transition-all text-center">
                            Volume (M³) = P × L × T
                        </button>
                    </div>

                    <!-- Input Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Panjang (m)</label>
                            <input type="number" step="any" x-model="panjang" placeholder="0.00" class="w-full text-sm p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Lebar (m)</label>
                            <input type="number" step="any" x-model="lebar" placeholder="0.00" class="w-full text-sm p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div x-show="mode === 'm3'">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tinggi/Tebal (m)</label>
                            <input type="number" step="any" x-model="tinggi" placeholder="0.00" class="w-full text-sm p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Result Card -->
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Hasil Perhitungan</span>
                            <span class="text-2xl font-black text-blue-900 font-mono" x-text="result"></span>
                            <span class="text-xs font-bold text-blue-700 ml-1" x-text="mode === 'm2' ? 'M²' : 'M³'"></span>
                        </div>
                        <button type="button" @click="navigator.clipboard.writeText(result); alert('Hasil disalin ke clipboard: ' + result)" 
                                class="px-3 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">
                            Salin Angka
                        </button>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showCalcModal = false" class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- GLOBAL DOCUMENT PREVIEW MODAL (POP-UP) -->
    <div x-data="{
            open: false,
            url: '',
            title: '',
            isPdf: false,
            showPreview(event) {
                this.url = event.detail.url;
                this.title = event.detail.title || 'Bukti Fisik';
                if (event.detail.isPdf !== null && typeof event.detail.isPdf !== 'undefined') {
                    this.isPdf = Boolean(event.detail.isPdf);
                } else {
                    const cleanUrl = this.url.split('?')[0].toLowerCase();
                    this.isPdf = cleanUrl.endsWith('.pdf') || cleanUrl.endsWith('/pdf') || cleanUrl.includes('.pdf') || cleanUrl.includes('/pdf');
                }
                this.open = true;
            },
            closePreview() {
                this.open = false;
                setTimeout(() => {
                    if (!this.open) {
                        this.url = '';
                        this.title = '';
                    }
                }, 300);
            }
        }"
        @open-doc-preview.window="showPreview($event)"
        @keydown.escape.window="closePreview()"
        x-cloak>

        <!-- Backdrop -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closePreview()"
             class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-[9990]"></div>

        <!-- Modal Dialog Container -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="fixed inset-0 z-[9995] flex items-center justify-center p-3 sm:p-5 md:p-8">

            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-5xl h-[88vh] flex flex-col overflow-hidden"
                 @click.outside="closePreview()">

                <!-- Modal Header -->
                <div class="px-5 py-3.5 border-b border-slate-200 bg-white flex items-center justify-between gap-3 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="isPdf ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600'">
                            <template x-if="isPdf">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </template>
                            <template x-if="!isPdf">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900 truncate" x-text="title || 'Pratinjau Bukti Fisik'"></h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider flex-shrink-0"
                                      :class="isPdf ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                      x-text="isPdf ? 'PDF Dokumen' : 'Gambar / Foto'"></span>
                            </div>
                            <p class="text-xs text-slate-500 truncate" x-text="url"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a :href="url" download
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                           title="Unduh Berkas">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span class="hidden sm:inline">Unduh</span>
                        </a>
                        <button type="button" @click="closePreview()"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors text-lg font-bold"
                                title="Tutup (Esc)">
                            &times;
                        </button>
                    </div>
                </div>

                <!-- Modal Body: File Viewer -->
                <div class="flex-1 bg-slate-900/5 relative overflow-hidden flex items-center justify-center">
                    <!-- PDF Viewer -->
                    <template x-if="open && isPdf">
                        <iframe :src="url" 
                                class="w-full h-full border-0 bg-white"
                                frameborder="0"></iframe>
                    </template>

                    <!-- Image Viewer -->
                    <template x-if="open && !isPdf">
                        <div class="w-full h-full overflow-auto flex items-center justify-center p-4">
                            <img :src="url" 
                                 :alt="title"
                                 class="max-h-full max-w-full object-contain rounded-xl shadow-md border border-slate-200/60 bg-white" />
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500 flex-shrink-0">
                    <span class="text-[11px] text-slate-400">Tekan <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono text-[10px]">Esc</kbd> atau klik di luar untuk menutup pop-up.</span>
                    <button type="button" @click="closePreview()" 
                            class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg transition-colors text-xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.openDocPreview = function(url, title = 'Bukti Fisik', isPdf = null) {
            window.dispatchEvent(new CustomEvent('open-doc-preview', {
                detail: { url, title, isPdf }
            }));
        };
    </script>

    @stack('scripts')
</body>
</html>
