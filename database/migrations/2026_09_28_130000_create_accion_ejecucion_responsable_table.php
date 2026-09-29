<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accion_ejecucion_responsable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('responsables')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['accion_ejecucion_id', 'responsable_id'], 'accion_ejecucion_responsable_unique');
        });

        DB::table('acciones_ejecucion')->whereNotNull('responsable_id')->orderBy('id')->each(function ($accion) {
            DB::table('accion_ejecucion_responsable')->insertOrIgnore([
                'accion_ejecucion_id' => $accion->id,
                'responsable_id' => $accion->responsable_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accion_ejecucion_responsable');
    }
};
