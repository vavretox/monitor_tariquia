<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder {
    public function run(): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = collect(config('access.modules'))
            ->flatMap(fn ($module, $key) => collect($module['actions'])->keys()->map(fn ($action) => $key.'.'.$action))
            ->values();
        $permissions->each(fn ($permission) => Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));

        foreach (['admin','tecnico','observador','responsable'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
        Role::findByName('admin')->syncPermissions($permissions);
        Role::findByName('tecnico')->syncPermissions($permissions->reject(fn ($permission) => str_starts_with($permission, 'users.') || str_starts_with($permission, 'roles.')));
        Role::findByName('observador')->syncPermissions([
            'dashboard.view', 'proyectos.view', 'comunidades.view', 'compromisos.view', 'acciones.view',
            'responsables.view',
        ]);
        Role::findByName('responsable')->syncPermissions(['dashboard.view', 'compromisos.view', 'compromisos.create', 'compromisos.edit', 'acciones.view', 'acciones.create', 'acciones.edit']);
        $admin = User::firstOrCreate(['email'=>'admin@tariquia.test'], ['name'=>'Administrador','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        $admin->assignRole('admin');
        $tec = User::firstOrCreate(['email'=>'tecnico@tariquia.test'], ['name'=>'Técnico','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        $tec->assignRole('tecnico');
        $obs = User::firstOrCreate(['email'=>'observador@tariquia.test'], ['name'=>'Observador','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        $obs->assignRole('observador');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
