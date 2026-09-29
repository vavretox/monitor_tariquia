<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunidad_necesidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunidad_id')->constrained('comunidades')->cascadeOnDelete();
            $table->foreignId('necesidad_id')->constrained('necesidades')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comunidad_id', 'necesidad_id']);
        });

        DB::table('necesidades')->whereNotNull('comunidad_id')->orderBy('id')->each(function ($necesidad) {
            DB::table('comunidad_necesidad')->insertOrIgnore([
                'comunidad_id' => $necesidad->comunidad_id,
                'necesidad_id' => $necesidad->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $permissionId = DB::table('permissions')->where('name', 'necesidades.view')->where('guard_name', 'web')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId(['name' => 'necesidades.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }

        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('permissions.name', ['necesidades.create', 'necesidades.edit', 'necesidades.delete'])
            ->pluck('role_has_permissions.role_id')->unique();
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'necesidades.view')->where('guard_name', 'web')->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        Schema::dropIfExists('comunidad_necesidad');
    }
};
