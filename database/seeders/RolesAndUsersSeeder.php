<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = collect(config('access.modules'))
            ->flatMap(fn ($module, $key) => collect($module['actions'])->keys()->map(fn ($action) => $key.'.'.$action))
            ->values();
        $permissions->each(fn ($permission) => Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));

        foreach (['admin', 'tecnico', 'observador', 'responsable'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
        Role::findByName('admin')->syncPermissions($permissions);
        Role::findByName('tecnico')->syncPermissions($permissions->reject(fn ($permission) => str_starts_with($permission, 'users.') || str_starts_with($permission, 'roles.')));
        Role::findByName('observador')->syncPermissions([
            'dashboard.view', 'comunidades.view', 'demandas.view', 'acciones.view',
            'responsables.view',
        ]);
        Role::findByName('responsable')->syncPermissions(['dashboard.view', 'demandas.view', 'demandas.create', 'demandas.edit', 'acciones.view', 'acciones.create', 'acciones.edit']);
        $adminEmail = config('app.seed_admin_email');
        $adminPassword = config('app.seed_admin_password');
        if (app()->environment('testing')) {
            $adminEmail ??= 'admin@tariquia.test';
            $adminPassword ??= 'testing-password';
        }
        if ($adminEmail && $adminPassword) {
            $admin = User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make($adminPassword),
                    'email_verified_at' => now(),
                    'must_change_password' => ! app()->environment('testing'),
                ],
            );
            $admin->assignRole('admin');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
