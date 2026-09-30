<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('demandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_necesidad_id')->nullable()->constrained('tipos_necesidad')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('ubicacion_especifica')->nullable();
            $table->string('fuente')->nullable();
            $table->date('fecha_identificacion')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->string('prioridad')->default('media');
            $table->string('estado')->default('identificada');
            $table->text('resultado_esperado')->nullable();
            $table->text('proximo_paso')->nullable();
            $table->text('problema_bloqueo')->nullable();
            $table->timestamps();
        });

        Schema::create('comunidad_demanda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunidad_id')->constrained('comunidades')->cascadeOnDelete();
            $table->foreignId('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comunidad_id', 'demanda_id']);
        });

        // El cambio es intencionalmente limpio: los registros de necesidades,
        // compromisos y acciones anteriores pertenecen al flujo sustituido.
        Schema::disableForeignKeyConstraints();
        DB::table('historial_avances')->delete();
        DB::table('accion_ejecucion_archivos')->delete();
        DB::table('accion_ejecucion_responsable')->delete();
        DB::table('acciones_ejecucion')->delete();
        DB::table('accion_compromiso_responsable')->delete();
        DB::table('comunidad_necesidad')->delete();
        DB::table('acciones_compromisos')->delete();
        DB::table('necesidades')->delete();
        Schema::enableForeignKeyConstraints();

        Schema::table('acciones_ejecucion', function (Blueprint $table) {
            $table->dropForeign(['accion_compromiso_id']);
            $table->dropColumn('accion_compromiso_id');
            $table->foreignId('demanda_id')->first()->constrained('demandas')->cascadeOnDelete();
        });

        Schema::dropIfExists('accion_compromiso_responsable');
        Schema::dropIfExists('comunidad_necesidad');
        Schema::dropIfExists('acciones_compromisos');
        Schema::dropIfExists('necesidades');

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $newId = DB::table('permissions')->insertGetId([
                'name' => "demandas.$action", 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $oldIds = DB::table('permissions')->whereIn('name', ["necesidades.$action", "compromisos.$action"])->pluck('id');
            $roleIds = DB::table('role_has_permissions')->whereIn('permission_id', $oldIds)->pluck('role_id')->unique();
            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $newId, 'role_id' => $roleId]);
            }
        }

        $oldPermissionIds = DB::table('permissions')->where(fn ($query) => $query
            ->where('name', 'like', 'necesidades.%')->orWhere('name', 'like', 'compromisos.%'))->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $oldPermissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $oldPermissionIds)->delete();
        DB::table('permissions')->whereIn('id', $oldPermissionIds)->delete();
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración elimina datos del flujo anterior y no admite reversión automática.');
    }
};
