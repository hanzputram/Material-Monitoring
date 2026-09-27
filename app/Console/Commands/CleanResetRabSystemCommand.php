<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CleanResetRabSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rab:clean-reset {--password=SuperAdmin2026! : Password untuk akun Super Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus semua data dummy dan inisialisasi hanya 1 akun Super Administrator dengan hak akses penuh';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==============================================================");
        $this->info("  PEMBERSIHAN TOTAL DATA DUMMY & SETUP SINGLE SUPER ADMIN     ");
        $this->info("==============================================================");

        // 1. Truncate / Delete all operational project and dummy data
        $tablesToClean = [
            'cost_realizations',
            'alerts',
            'material_variance_validations',
            'material_realizations',
            'invoices',
            'delivery_order_items',
            'delivery_orders',
            'purchase_order_items',
            'purchase_orders',
            'suppliers',
            'rab_item_materials',
            'rab_items',
            'rab_nodes',
            'project_equipment',
            'project_user',
            'projects',
        ];

        foreach ($tablesToClean as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->delete();
                $this->line(" - Tabel '{$table}': {$count} data dummy dihapus.");
            }
        }

        // 2. Wipe all existing users and role assignments
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->delete();
        }
        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->delete();
        }

        $userCount = User::count();
        User::query()->delete();
        $this->line(" - Tabel 'users': {$userCount} akun lama dihapus.");

        // 3. Ensure Spatie permissions and roles are established
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = array_keys(User::availableModules());
        foreach ($modules as $mod) {
            Permission::firstOrCreate(['name' => $mod, 'guard_name' => 'web']);
        }
        Permission::firstOrCreate(['name' => '*', 'guard_name' => 'web']);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 4. Create the SINGLE Super Administrator user
        $password = $this->option('password') ?: 'SuperAdmin2026!';

        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'admin@konstruksi.id',
            'password' => Hash::make($password),
            'role' => 'Super Admin',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        $superAdmin->assignRole($superAdminRole);

        $this->newLine();
        $this->info("==============================================================");
        $this->info("  BERHASIL DISETUP: HANYA 1 AKUN SUPER ADMINISTRATOR          ");
        $this->info("==============================================================");
        $this->table(
            ['Parameter', 'Kredensial Akses'],
            [
                ['Nama Lengkap', $superAdmin->name],
                ['Username', $superAdmin->username],
                ['Email', $superAdmin->email],
                ['Password', $password],
                ['Peran (Role)', 'Super Admin'],
                ['Hak Akses (Permissions)', '["*"] (Akses Penuh Semua Modul)'],
                ['Status Akun', 'Aktif'],
                ['Total Proyek', '0 (Bersih Total dari Data Dummy)'],
                ['Total Pengguna', '1 Akun (Hanya Super Admin)'],
            ]
        );

        $this->info("Sistem telah bersih 100%. Mulai sekarang pengguna harus login terlebih dahulu.");
        return Command::SUCCESS;
    }
}
