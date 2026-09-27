<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')->withPivot('role_in_project')->withTimestamps();
    }

    /**
     * Cek apakah pengguna adalah Super Administrator
     */
    public function isSuperAdmin(): bool
    {
        $roleLower = strtolower(str_replace([' ', '-', '_'], '', (string)$this->role));
        if (in_array($roleLower, ['superadmin', 'superadministrator', 'root'])) {
            return true;
        }

        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah pengguna memiliki izin membaca / melihat Purchase Order
     */
    public function canReadPo(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('po_read') 
            || $this->hasRawPermission('po_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Cek apakah pengguna memiliki izin membuat / mengelola Purchase Order
     */
    public function canWritePo(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('po_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Cek apakah pengguna memiliki izin membaca / melihat Surat Jalan (DO)
     */
    public function canReadDo(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('do_read') 
            || $this->hasRawPermission('do_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Cek apakah pengguna memiliki izin menginput / mengunggah Surat Jalan (DO)
     */
    public function canWriteDo(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('do_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Cek apakah pengguna memiliki izin membaca / melihat Faktur Invoice
     */
    public function canReadInvoice(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('invoice_read') 
            || $this->hasRawPermission('invoice_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Cek apakah pengguna memiliki izin menginput / memvalidasi Faktur Invoice
     */
    public function canWriteInvoice(): bool
    {
        return $this->isSuperAdmin() 
            || $this->hasRawPermission('invoice_write') 
            || $this->hasRawPermission('procurement');
    }

    /**
     * Pengecekan dasar string permission
     */
    protected function hasRawPermission(string $perm): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $perms = (array) ($this->permissions ?? []);
        if (in_array('*', $perms, true) || in_array($perm, $perms, true)) {
            return true;
        }

        try {
            if ($this->hasPermissionTo($perm)) {
                return true;
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Cek izin hak akses ke modul tertentu (mendukung granular read/write)
     */
    public function hasModulePermission(string $module): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return match ($module) {
            'po_read', 'po.read' => $this->canReadPo(),
            'po_write', 'po.write' => $this->canWritePo(),
            'do_read', 'do.read' => $this->canReadDo(),
            'do_write', 'do.write' => $this->canWriteDo(),
            'invoice_read', 'invoice.read' => $this->canReadInvoice(),
            'invoice_write', 'invoice.write' => $this->canWriteInvoice(),
            'procurement' => $this->canReadPo() || $this->canReadDo() || $this->canReadInvoice(),
            default => $this->hasRawPermission($module),
        };
    }

    /**
     * Cek apakah memiliki salah satu izin modul
     */
    public function hasAnyModulePermission(array $modules): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($modules as $mod) {
            if ($this->hasModulePermission($mod)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Label representasi peran untuk tampilan UI
     */
    public function getRoleLabelAttribute(): string
    {
        return match (strtolower(str_replace([' ', '-', '_'], '', (string)$this->role))) {
            'superadmin' => 'Super Administrator',
            'projectmanager' => 'Project Manager',
            'pengawaslapangan', 'pengawas' => 'Pengawas Lapangan',
            'purchasing' => 'Purchasing Officer',
            'financedireksi', 'direksi', 'finance' => 'Finance / Direksi',
            default => ucfirst($this->role ?? 'Custom'),
        };
    }

    /**
     * Daftar modul sistem dan keterangan untuk form pendaftaran user
     */
    public static function availableModules(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard Monitoring',
                'category' => 'Utama & Monitoring',
                'description' => 'Melihat ringkasan eksekutif, status deviasi material, dan grafik proyek.',
            ],
            'cost' => [
                'name' => 'Biaya Realisasi (RAB vs Real)',
                'category' => 'Utama & Monitoring',
                'description' => 'Membandingkan anggaran RAB dasar dengan total realisasi pengadaan & biaya aktual.',
            ],
            'projects' => [
                'name' => 'Manajemen Proyek',
                'category' => 'Perencanaan & Master',
                'description' => 'Membuat, mengedit, menghapus identitas proyek, dan mengatur parameter lantai.',
            ],
            'rab' => [
                'name' => 'RAB Tree Builder & BOM',
                'category' => 'Perencanaan & Master',
                'description' => 'Menyusun hierarki pekerjaan (Level 1-4) dan memecah material dasar (Level 5 BOM).',
            ],
            // Pemisahan Form PO, DO, dan Invoice dengan Hak Akses Read vs Write
            'po_read' => [
                'name' => 'Purchase Order (PO) — Lihat Saja (Read)',
                'category' => 'Pengadaan & PO',
                'description' => 'Melihat arsip Purchase Order, nomor PO, item material yang dipesan, dan rekanan supplier.',
            ],
            'po_write' => [
                'name' => 'Purchase Order (PO) — Buat & Kelola (Write)',
                'category' => 'Pengadaan & PO',
                'description' => 'Membuat Purchase Order baru ke supplier, menetapkan kuantiti, harga satuan, dan catatan PO.',
            ],
            'do_read' => [
                'name' => 'Surat Jalan (DO) — Lihat Saja (Read)',
                'category' => 'Penerimaan Lapangan (DO)',
                'description' => 'Melihat riwayat Surat Jalan (DO) penerimaan barang di lapangan dan mengunduh scan bukti fisik.',
            ],
            'do_write' => [
                'name' => 'Surat Jalan (DO) — Input & Upload (Write)',
                'category' => 'Penerimaan Lapangan (DO)',
                'description' => 'Mencatat surat jalan fisik barang masuk di lapangan, kuantiti actual, dan mengunggah lampiran foto/PDF.',
            ],
            'invoice_read' => [
                'name' => 'Faktur Tagihan (Invoice) — Lihat Saja (Read)',
                'category' => 'Keuangan & Invoice',
                'description' => 'Melihat daftar faktur tagihan invoice dari rekanan, status pembayaran, dan lampiran faktur pajak.',
            ],
            'invoice_write' => [
                'name' => 'Faktur Tagihan (Invoice) — Input & Validasi (Write)',
                'category' => 'Keuangan & Invoice',
                'description' => 'Menginput faktur tagihan supplier baru, mencatat nominal rupiah, dan mengunggah berkas faktur asli.',
            ],
            'suppliers' => [
                'name' => 'Master Supplier & Rekanan',
                'category' => 'Pengadaan & PO',
                'description' => 'Mengelola daftar vendor penyedia material, kontak rekanan, dan alamat supplier.',
            ],
            'variance' => [
                'name' => 'Validasi Ganda (Dual Approval)',
                'category' => 'Keuangan & Invoice',
                'description' => 'Melakukan verifikasi Surat Jalan oleh Pengawas dan verifikasi Faktur oleh Purchasing.',
            ],
            'equipment' => [
                'name' => 'Alat & Mesin (Fast Bulk Input)',
                'category' => 'Sumber Daya & Lapangan',
                'description' => 'Mengalokasikan armada alat berat dan peralatan kerja ke proyek melalui form cepat.',
            ],
            'alerts' => [
                'name' => 'Pusat Peringatan (Alerts)',
                'category' => 'Sumber Daya & Lapangan',
                'description' => 'Menerima dan menyelesaikan notifikasi peringatan deviasi over/under material.',
            ],
            'users' => [
                'name' => 'Manajemen Pengguna & Hak Akses',
                'category' => 'Administrasi Sistem',
                'description' => 'Mendaftarkan staf baru, mengubah peran (role), dan mengatur granular permission.',
            ],
        ];
    }

    /**
     * Preset permission default berdasarkan role
     */
    public static function defaultRolePermissions(string $role): array
    {
        return match (strtolower(str_replace([' ', '-', '_'], '', $role))) {
            'superadmin' => ['*'],
            'projectmanager' => [
                'dashboard', 'cost', 'projects', 'rab', 'po_read', 'po_write', 'do_read', 'do_write', 'invoice_read', 'invoice_write', 'suppliers', 'variance', 'equipment', 'alerts'
            ],
            'pengawaslapangan', 'pengawas' => [
                'dashboard', 'projects', 'rab', 'po_read', 'do_read', 'do_write', 'invoice_read', 'variance', 'equipment', 'alerts'
            ],
            'purchasing' => [
                'dashboard', 'cost', 'rab', 'po_read', 'po_write', 'do_read', 'invoice_read', 'invoice_write', 'suppliers', 'variance', 'alerts'
            ],
            'financedireksi', 'direksi', 'finance' => [
                'dashboard', 'cost', 'po_read', 'do_read', 'invoice_read', 'variance', 'alerts'
            ],
            default => ['dashboard'],
        };
    }
}
