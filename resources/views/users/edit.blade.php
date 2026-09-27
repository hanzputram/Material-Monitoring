@extends('layouts.app')

@section('title', 'Perbarui Pengguna: ' . $user->name . ' — K-RAB')
@section('page_title', 'Perbarui Akun & Hak Akses')
@section('page_subtitle', 'Edit profil, ganti peran kerja, atau sesuaikan izin akses modul untuk staf ' . $user->name . '.')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Back Button -->
    <div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Pengguna</span>
        </a>
    </div>

    <!-- Main Card -->
    <form action="{{ route('users.update', $user->id) }}" method="POST" id="userForm" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. IDENTITAS PENGGUNA -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-extrabold">1</span>
                <span>Informasi Akun Staf</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $user->name) }}" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Username (Opsional)
                    </label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="{{ old('username', $user->username) }}" 
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email', $user->email) }}" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Kata Sandi Baru (Kosongkan jika tidak diubah)
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           minlength="8"
                           placeholder="Minimal 8 karakter baru"
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           minlength="8"
                           placeholder="Ketik ulang kata sandi baru"
                           class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                </div>
            </div>
        </div>

        <!-- 2. PERAN (ROLE) & STATUS -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-extrabold">2</span>
                <span>Peran Kerja (Role)</span>
            </h3>

            <div class="space-y-4">
                <div>
                    <label for="role" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Pilih Peran Utama <span class="text-rose-500">*</span>
                    </label>
                    <select name="role" id="roleSelect" onchange="applyRolePreset(this.value)" 
                            class="w-full px-3.5 py-2.5 text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                        @foreach($roles as $val => $lbl)
                            <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700">Akun Aktif (Dapat Login ke Sistem)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 3. MATRIKS GRANULAR MODULE PERMISSIONS -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-extrabold">3</span>
                    <span>Hak Akses Modul (Module Permissions)</span>
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="selectAllPermissions(true)" class="px-2.5 py-1 text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                        Pilih Semua
                    </button>
                    <button type="button" onclick="selectAllPermissions(false)" class="px-2.5 py-1 text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                        Kosongkan
                    </button>
                </div>
            </div>

            @php
                $grouped = [];
                foreach ($modules as $key => $meta) {
                    $cat = $meta['category'] ?? 'Lainnya';
                    $grouped[$cat][$key] = $meta;
                }
                $userPerms = (array) ($user->permissions ?? []);
                $isAllPerms = in_array('*', $userPerms) || $user->isSuperAdmin();
            @endphp

            <div class="space-y-6">
                @foreach($grouped as $category => $items)
                    <div>
                        <h4 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider mb-2.5 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>{{ $category }}</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($items as $modKey => $modData)
                                @php
                                    $isChecked = $isAllPerms || in_array($modKey, $userPerms);
                                @endphp
                                <label class="relative flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/30 transition-all cursor-pointer select-none">
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="{{ $modKey }}"
                                           {{ $isChecked ? 'checked' : '' }}
                                           class="perm-checkbox mt-0.5 w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">{{ $modData['name'] }}</div>
                                        <div class="text-[11px] text-slate-500 leading-snug mt-0.5">{{ $modData['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('users.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 bg-white border border-slate-300 rounded-xl transition-colors">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-2">
                <span>Perbarui Pengguna</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
    </form>

</div>

<script>
    const rolePresets = {
        'Super Admin': ['*'],
        'Project Manager': ['dashboard', 'cost', 'projects', 'rab', 'po_read', 'po_write', 'do_read', 'do_write', 'invoice_read', 'invoice_write', 'suppliers', 'variance', 'equipment', 'alerts'],
        'Pengawas Lapangan': ['dashboard', 'projects', 'rab', 'po_read', 'do_read', 'do_write', 'invoice_read', 'variance', 'equipment', 'alerts'],
        'Purchasing': ['dashboard', 'cost', 'rab', 'po_read', 'po_write', 'do_read', 'invoice_read', 'invoice_write', 'suppliers', 'variance', 'alerts'],
        'Finance/Direksi': ['dashboard', 'cost', 'po_read', 'do_read', 'invoice_read', 'variance', 'alerts'],
        'Custom': ['dashboard']
    };

    function applyRolePreset(role) {
        if (!confirm('Apakah Anda ingin mereset checklist izin modul sesuai rekomendasi peran ' + role + '?')) {
            return;
        }
        const checkboxes = document.querySelectorAll('.perm-checkbox');
        const targetPerms = rolePresets[role] || [];

        checkboxes.forEach(cb => {
            if (targetPerms.includes('*') || targetPerms.includes(cb.value)) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
    }

    function selectAllPermissions(status) {
        const checkboxes = document.querySelectorAll('.perm-checkbox');
        checkboxes.forEach(cb => cb.checked = status);
    }
</script>
@endsection
