<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'responsable', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $permissions = [
            'dashboard.view', 'compromisos.view', 'compromisos.create', 'compromisos.edit',
            'acciones.view', 'acciones.create', 'acciones.edit',
        ];

        foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'responsable')->value('id');
        if ($roleId) {
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('model_has_roles')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
