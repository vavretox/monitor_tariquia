<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->index(['estado', 'prioridad'], 'demandas_estado_prioridad_index');
            $table->index('fecha_limite', 'demandas_fecha_limite_index');
        });
        Schema::table('acciones_ejecucion', function (Blueprint $table) {
            $table->index(['estado', 'fecha_limite'], 'acciones_estado_fecha_limite_index');
            $table->index('ultima_actualizacion_avance', 'acciones_ultima_actualizacion_index');
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropIndex('demandas_estado_prioridad_index');
            $table->dropIndex('demandas_fecha_limite_index');
        });
        Schema::table('acciones_ejecucion', function (Blueprint $table) {
            $table->dropIndex('acciones_estado_fecha_limite_index');
            $table->dropIndex('acciones_ultima_actualizacion_index');
        });
    }
};
