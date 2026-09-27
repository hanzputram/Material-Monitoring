<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'project.create',
            'project.edit',
            'project.view',
            'rab.manage',
            'rab.import',
            'material.bom',
            'po.create',
            'po.view',
            'do.input',
            'do.view',
            'invoice.input',
            'invoice.view',
            'variance.validate.pengawas',
            'variance.validate.purchasing',
            'monitoring.view',
            'cost.view',
            'equipment.master',
            'equipment.input',
            'alert.manage',
            'report.export',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $pm = Role::firstOrCreate(['name' => 'Project Manager', 'guard_name' => 'web']);
        $pengawas = Role::firstOrCreate(['name' => 'Pengawas Lapangan', 'guard_name' => 'web']);
        $purchasing = Role::firstOrCreate(['name' => 'Purchasing', 'guard_name' => 'web']);
        $direksi = Role::firstOrCreate(['name' => 'Finance/Direksi', 'guard_name' => 'web']);

        $superAdmin->givePermissionTo(Permission::all());

        $pm->syncPermissions([
            'project.create', 'project.edit', 'project.view',
            'rab.manage', 'rab.import', 'po.view', 'do.view', 'invoice.view',
            'monitoring.view', 'cost.view', 'equipment.master', 'equipment.input',
            'alert.manage', 'report.export'
        ]);

        $pengawas->syncPermissions([
            'project.view', 'rab.manage', 'do.input', 'do.view',
            'variance.validate.pengawas', 'monitoring.view', 'equipment.input'
        ]);

        $purchasing->syncPermissions([
            'project.view', 'material.bom', 'po.create', 'po.view',
            'invoice.input', 'invoice.view', 'variance.validate.purchasing',
            'monitoring.view'
        ]);

        $direksi->syncPermissions([
            'project.view', 'monitoring.view', 'cost.view', 'report.export'
        ]);

        // Users
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'admin@konstruksi.id',
                'password' => Hash::make('password'),
                'role' => 'Super Admin',
            ],
            [
                'name' => 'Budi Santoso, ST (Project Manager)',
                'email' => 'pm@konstruksi.id',
                'password' => Hash::make('password'),
                'role' => 'Project Manager',
            ],
            [
                'name' => 'Agus Priyono (Pengawas Lapangan)',
                'email' => 'pengawas@konstruksi.id',
                'password' => Hash::make('password'),
                'role' => 'Pengawas Lapangan',
            ],
            [
                'name' => 'Dewi Lestari (Purchasing Officer)',
                'email' => 'purchasing@konstruksi.id',
                'password' => Hash::make('password'),
                'role' => 'Purchasing',
            ],
            [
                'name' => 'Hendra Kusuma, SE (Finance & Direksi)',
                'email' => 'direksi@konstruksi.id',
                'password' => Hash::make('password'),
                'role' => 'Finance/Direksi',
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => $u['password'],
                ]
            );
            $user->syncRoles([$u['role']]);
        }
    }
}
