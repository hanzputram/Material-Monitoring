@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Hak Akses — K-RAB')
@section('page_title', 'Manajemen Pengguna & Role')
@section('page_subtitle', 'Kelola staf, tentukan peran kerja (role), dan konfigurasi hak akses modular sistem.')

@section('content')
<div x-data="{
    showPermModal: false,
    activeUser: null,
    allModules: {{ Js::from($modules) }},
    openPermModal(user) {
        this.activeUser = user;
        this.showPermModal = true;
    },
    hasPermission(key) {
        if (!this.activeUser) return false;
        if (this.activeUser.is_super) return true;
        const perms = this.activeUser.permissions || [];
        return perms.includes('*') || perms.includes(key);
    },
    getActiveCount() {
        if (!this.activeUser) return 0;
        if (this.activeUser.is_super) return Object.keys(this.allModules).length;
        const perms = this.activeUser.permissions || [];
        if (perms.includes('*')) return Object.keys(this.allModules).length;
        return perms.length;
    },
    get groupedModules() {
        const groups = {};
        for (const [key, mod] of Object.entries(this.allModules)) {
            const cat = mod.category || 'Lainnya';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push({ key, ...mod });
        }
        return groups;
    }
}" class="space-y-6">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold shadow-xs">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold shadow-xs">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Top Summary & Action Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pengguna</div>
                <div class="text-xl font-extrabold text-slate-900">{{ $users->count() }} <span class="text-xs font-medium text-slate-400">Akun</span></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status Aktif</div>
                <div class="text-xl font-extrabold text-emerald-600">{{ $users->where('is_active', true)->count() }} <span class="text-xs font-medium text-slate-400">Staf</span></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Modul Sistem</div>
                <div class="text-xl font-extrabold text-purple-700">{{ count($modules) }} <span class="text-xs font-medium text-slate-400">Granular</span></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi Cepat</div>
                <div class="text-xs text-slate-500 mt-0.5">Daftarkan akun staf baru</div>
            </div>
            <a href="{{ route('users.create') }}" 
               class="inline-flex items-center justify-center gap-1.5 h-10 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Staf</span>
            </a>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-4 sm:px-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/40">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Pengguna & Hak Akses</h3>
                <p class="text-xs text-slate-500 mt-0.5">Semua staf terdaftar dengan granular permission modular.</p>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Total: <span class="font-bold text-slate-800">{{ $users->count() }} Pengguna</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4 align-middle">Nama Staf</th>
                        <th class="py-3.5 px-4 align-middle">Username & Email</th>
                        <th class="py-3.5 px-4 align-middle">Peran (Role)</th>
                        <th class="py-3.5 px-4 align-middle">Hak Akses Modul</th>
                        <th class="py-3.5 px-4 align-middle">Status</th>
                        <th class="py-3.5 px-4 align-middle">Terdaftar</th>
                        <th class="py-3.5 px-4 align-middle text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($users as $user)
                        @php
                            $perms = (array) ($user->permissions ?? []);
                            $isSuper = $user->isSuperAdmin();
                            $hasAll = $isSuper || in_array('*', $perms);
                            $userPayload = [
                                'id' => $user->id,
                                'name' => $user->name,
                                'username' => $user->username ?: '-',
                                'email' => $user->email,
                                'role' => $user->role_label,
                                'is_super' => $isSuper,
                                'permissions' => $perms,
                                'edit_url' => route('users.edit', $user->id),
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- 1. Nama Staf -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl {{ $isSuper ? 'bg-gradient-to-tr from-purple-700 to-indigo-600 ring-2 ring-purple-300' : 'bg-gradient-to-tr from-blue-600 to-sky-500' }} text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0 shadow-xs">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if($isSuper)
                                                <span title="Super Administrator" class="text-amber-500 text-xs">★</span>
                                            @endif
                                            @if(auth()->id() === $user->id)
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Akun Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Username & Email -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                <div class="font-mono text-slate-900 font-semibold text-[11px]">{{ $user->username ?: '-' }}</div>
                                <div class="text-slate-500 text-[11px]">{{ $user->email }}</div>
                            </td>

                            <!-- 3. Peran (Role) -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                @if($isSuper)
                                    <span class="inline-flex items-center gap-1.5 h-7 px-3 rounded-lg text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200 shadow-2xs">
                                        <span>👑</span>
                                        <span>Super Admin</span>
                                    </span>
                                @elseif($user->role === 'Project Manager')
                                    <span class="inline-flex items-center h-7 px-3 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Project Manager
                                    </span>
                                @elseif(str_contains(strtolower($user->role), 'pengawas'))
                                    <span class="inline-flex items-center h-7 px-3 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Pengawas Lapangan
                                    </span>
                                @elseif(str_contains(strtolower($user->role), 'purchasing'))
                                    <span class="inline-flex items-center h-7 px-3 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Purchasing Officer
                                    </span>
                                @elseif(str_contains(strtolower($user->role), 'finance') || str_contains(strtolower($user->role), 'direksi'))
                                    <span class="inline-flex items-center h-7 px-3 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                        Finance / Direksi
                                    </span>
                                @else
                                    <span class="inline-flex items-center h-7 px-3 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $user->role_label }}
                                    </span>
                                @endif
                            </td>

                            <!-- 4. Hak Akses Modul (Uniform Single-Line Button, No +2 lainnya!) -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                @if($hasAll)
                                    <button type="button" 
                                            @click="openPermModal({{ json_encode($userPayload) }})"
                                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl text-xs font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/90 shadow-2xs transition-all cursor-pointer"
                                            title="Klik untuk melihat seluruh modul yang dapat diakses">
                                        <span>👑</span>
                                        <span>Akses Penuh (Semua 15 Modul)</span>
                                        <svg class="w-3.5 h-3.5 text-purple-400 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                @elseif(count($perms) === 0)
                                    <button type="button" 
                                            @click="openPermModal({{ json_encode($userPayload) }})"
                                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl text-xs font-medium bg-slate-100 text-slate-500 hover:bg-slate-200 border border-slate-200 transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        <span>0 Modul Diizinkan</span>
                                        <span class="text-slate-400">·</span>
                                        <span class="text-[11px] font-bold text-blue-600">Atur &rarr;</span>
                                    </button>
                                @else
                                    <button type="button" 
                                            @click="openPermModal({{ json_encode($userPayload) }})"
                                            class="group inline-flex items-center gap-2 h-8 px-3 rounded-xl text-xs font-semibold bg-slate-50 hover:bg-blue-50 text-slate-700 hover:text-blue-700 border border-slate-200/90 hover:border-blue-300 transition-all shadow-2xs cursor-pointer"
                                            title="Klik untuk rincian modul yang aktif">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 font-extrabold text-[11px] group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                            {{ count($perms) }}
                                        </span>
                                        <span class="font-semibold">Modul Diizinkan</span>
                                        <span class="text-slate-300 group-hover:text-blue-300">·</span>
                                        <span class="text-[11px] font-bold text-blue-600 group-hover:text-blue-700 flex items-center gap-0.5">
                                            Lihat Detail
                                            <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </button>
                                @endif
                            </td>

                            <!-- 5. Status -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Terdaftar -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap text-slate-500 text-xs font-medium">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            <!-- 7. Aksi (Proportional, Balanced h-8 Buttons) -->
                            <td class="py-3.5 px-4 align-middle text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('users.edit', $user->id) }}" 
                                       class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-xl text-xs font-bold text-blue-700 bg-blue-50/90 hover:bg-blue-600 hover:text-white border border-blue-200/80 transition-all duration-150 shadow-xs active:scale-95"
                                       title="Edit Data Pengguna & Hak Akses">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    @if(auth()->id() !== $user->id && !$isSuper)
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')" class="inline m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-xl text-xs font-bold text-rose-700 bg-rose-50/90 hover:bg-rose-600 hover:text-white border border-rose-200/80 transition-all duration-150 shadow-xs active:scale-95 cursor-pointer"
                                                    title="Hapus Pengguna">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-xl text-[11px] font-semibold text-slate-400 bg-slate-100/70 border border-slate-200/70 select-none" 
                                              title="{{ $isSuper ? 'Akun Super Admin Utama dilindungi dari penghapusan' : 'Anda tidak dapat menghapus akun Anda sendiri' }}">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>Terkunci</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <div class="font-bold text-slate-700">Belum ada pengguna terdaftar</div>
                                    <p class="text-xs text-slate-400">Daftarkan akun staf baru untuk memberikan hak akses modular.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alpine.js Permissions Detail Modal -->
    <div x-show="showPermModal" 
         x-cloak
         @keydown.escape.window="showPermModal = false"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop with Blur -->
        <div x-show="showPermModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" 
             @click="showPermModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showPermModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-2xl border border-slate-200">
                
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/70 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-extrabold flex items-center justify-center shadow-xs text-sm"
                             x-text="activeUser ? activeUser.name.substring(0, 2).toUpperCase() : 'US'">
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-slate-900" x-text="activeUser ? activeUser.name : ''"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="activeUser?.is_super ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                      x-text="activeUser ? activeUser.role : ''"></span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <span x-text="activeUser ? activeUser.email : ''"></span>
                                <span class="mx-1 text-slate-300">·</span>
                                <span class="font-mono text-slate-600 font-semibold" x-text="activeUser ? activeUser.username : ''"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showPermModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body: Categorized Checklist -->
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-5">
                    
                    <div class="flex items-center justify-between p-3.5 rounded-xl border"
                         :class="activeUser?.is_super ? 'bg-purple-50/70 border-purple-200 text-purple-900' : 'bg-blue-50/70 border-blue-200 text-blue-900'">
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg" x-text="activeUser?.is_super ? '👑' : '🛡️'"></span>
                            <div>
                                <div class="text-xs font-bold" x-text="activeUser?.is_super ? 'Akun Super Administrator' : 'Status Otoritas Modul Staf'"></div>
                                <div class="text-[11px] opacity-80" x-text="activeUser?.is_super ? 'Memiliki akses tak terbatas ke seluruh modul dan operasi sistem.' : 'Akses dibatasi sesuai modul yang diberi tanda checklist hijau di bawah.'"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-extrabold px-2.5 py-1 rounded-lg"
                                  :class="activeUser?.is_super ? 'bg-purple-200/80 text-purple-900' : 'bg-blue-200/80 text-blue-900'"
                                  x-text="getActiveCount() + ' / ' + Object.keys(allModules).length + ' Modul'">
                            </span>
                        </div>
                    </div>

                    <!-- Categories Loop -->
                    <template x-for="(items, category) in groupedModules" :key="category">
                        <div class="space-y-2">
                            <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span x-text="category"></span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="item in items" :key="item.key">
                                    <div :class="hasPermission(item.key) ? 'bg-emerald-50/60 border-emerald-200/90 text-emerald-950' : 'bg-slate-50/50 border-slate-200/70 text-slate-400 opacity-60'"
                                         class="p-3 rounded-xl border flex items-start gap-2.5 transition-all">
                                        
                                        <!-- Icon Check or Cross -->
                                        <div class="mt-0.5 flex-shrink-0">
                                            <template x-if="hasPermission(item.key)">
                                                <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-xs">
                                                    <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </template>
                                            <template x-if="!hasPermission(item.key)">
                                                <div class="w-5 h-5 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center">
                                                    <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1.5">
                                                <span class="text-xs font-bold truncate" :class="hasPermission(item.key) ? 'text-slate-900' : 'text-slate-500'" x-text="item.name"></span>
                                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded flex-shrink-0"
                                                      :class="hasPermission(item.key) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400'"
                                                      x-text="hasPermission(item.key) ? 'Diizinkan' : 'Terkunci'"></span>
                                            </div>
                                            <p class="text-[11px] mt-0.5 line-clamp-2 leading-relaxed" :class="hasPermission(item.key) ? 'text-slate-600' : 'text-slate-400'" x-text="item.description"></p>
                                        </div>

                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Total diizinkan: <strong class="text-slate-800" x-text="getActiveCount()"></strong> dari <span x-text="Object.keys(allModules).length"></span> modul
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showPermModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 bg-white border border-slate-300 rounded-xl transition-colors">
                            Tutup
                        </button>
                        <a :href="activeUser ? activeUser.edit_url : '#'" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Ubah Hak Akses</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
