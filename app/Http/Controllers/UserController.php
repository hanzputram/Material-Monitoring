<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Pastikan pengguna memiliki izin modul 'users'
     */
    protected function authorizeModule()
    {
        $currentUser = Auth::user();
        if (! $currentUser || ! $currentUser->hasModulePermission('users')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Pengguna & Hak Akses sistem.');
        }
    }

    /**
     * Tampilkan daftar seluruh pengguna
     */
    public function index()
    {
        $this->authorizeModule();

        $users = User::orderByDesc('created_at')->get();
        $modules = User::availableModules();

        return view('users.index', compact('users', 'modules'));
    }

    /**
     * Tampilkan form registrasi pengguna baru
     */
    public function create()
    {
        $this->authorizeModule();

        $modules = User::availableModules();
        $roles = [
            'Super Admin' => 'Super Administrator (Akses Penuh Semua Modul)',
            'Project Manager' => 'Project Manager (RAB, Proyek, Pengadaan, Monitoring Biaya)',
            'Pengawas Lapangan' => 'Pengawas Lapangan (Surat Jalan DO, Validasi Lapangan, Alat)',
            'Purchasing' => 'Purchasing Officer (Katalog Supplier, PO, Faktur Invoice, Validasi Finansial)',
            'Finance/Direksi' => 'Finance / Direksi (Monitoring Biaya Realisasi vs RAB, Laporan)',
            'Custom' => 'Staf Kustom (Hak Akses Ditentukan Bebas Per Modul)',
        ];

        return view('users.create', compact('modules', 'roles'));
    }

    /**
     * Simpan pengguna baru ke database
     */
    public function store(Request $request)
    {
        $this->authorizeModule();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar di sistem.',
            'username.unique' => 'Username ini sudah digunakan, silakan pilih yang lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'role.required' => 'Pilih peran (role) untuk pengguna.',
        ]);

        $role = $validated['role'];
        $permissions = $request->input('permissions', []);

        // Jika role adalah Super Admin, beri wildcard '*'
        if (strtolower(str_replace([' ', '-', '_'], '', $role)) === 'superadmin') {
            $permissions = ['*'];
        } elseif (empty($permissions)) {
            // Gunakan preset default jika tidak dicentang manual
            $permissions = User::defaultRolePermissions($role);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?: null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'permissions' => $permissions,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Sync Spatie role jika ada
        $spatieRole = Role::where('name', $role)->first();
        if ($spatieRole) {
            $user->assignRole($spatieRole);
        }

        return redirect()->route('users.index')->with('success', "Akun pengguna {$user->name} berhasil didaftarkan dengan role {$user->role_label}.");
    }

    /**
     * Tampilkan form edit pengguna
     */
    public function edit(User $user)
    {
        $this->authorizeModule();

        $modules = User::availableModules();
        $roles = [
            'Super Admin' => 'Super Administrator (Akses Penuh Semua Modul)',
            'Project Manager' => 'Project Manager (RAB, Proyek, Pengadaan, Monitoring Biaya)',
            'Pengawas Lapangan' => 'Pengawas Lapangan (Surat Jalan DO, Validasi Lapangan, Alat)',
            'Purchasing' => 'Purchasing Officer (Katalog Supplier, PO, Faktur Invoice, Validasi Finansial)',
            'Finance/Direksi' => 'Finance / Direksi (Monitoring Biaya Realisasi vs RAB, Laporan)',
            'Custom' => 'Staf Kustom (Hak Akses Ditentukan Bebas Per Modul)',
        ];

        return view('users.edit', compact('user', 'modules', 'roles'));
    }

    /**
     * Perbarui data pengguna dan hak akses modul
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeModule();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
            'username.unique' => 'Username ini sudah digunakan.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $role = $validated['role'];
        $permissions = $request->input('permissions', []);

        // Proteksi: Tidak boleh menonaktifkan diri sendiri
        $isActive = $request->boolean('is_active', true);
        if (Auth::id() === $user->id && ! $isActive) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang Anda gunakan saat ini.']);
        }

        if (strtolower(str_replace([' ', '-', '_'], '', $role)) === 'superadmin') {
            $permissions = ['*'];
        }

        $user->name = $validated['name'];
        $user->username = $validated['username'] ?: null;
        $user->email = $validated['email'];
        $user->role = $role;
        $user->permissions = $permissions;
        $user->is_active = $isActive;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync Spatie role
        $spatieRole = Role::where('name', $role)->first();
        if ($spatieRole) {
            $user->syncRoles([$spatieRole]);
        }

        return redirect()->route('users.index')->with('success', "Akun pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Hapus pengguna
     */
    public function destroy(User $user)
    {
        $this->authorizeModule();

        if (Auth::id() === $user->id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login.']);
        }

        if ($user->isSuperAdmin()) {
            $superAdminCount = User::where('role', 'Super Admin')->count();
            if ($superAdminCount <= 1) {
                return back()->withErrors(['error' => 'Tidak dapat menghapus Super Administrator terakhir di sistem.']);
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun pengguna {$name} telah berhasil dihapus.");
    }
}
