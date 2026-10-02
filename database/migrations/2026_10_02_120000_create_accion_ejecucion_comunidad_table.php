<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accion_ejecucion_comunidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('comunidad_id')->constrained('comunidades')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['accion_ejecucion_id', 'comunidad_id'], 'accion_comunidad_unique');
        });

        $now = now();
        DB::table('acciones_ejecucion')
            ->join('comunidad_demanda', 'acciones_ejecucion.demanda_id', '=', 'comunidad_demanda.demanda_id')
            ->select('acciones_ejecucion.id as accion_ejecucion_id', 'comunidad_demanda.comunidad_id')
            ->orderBy('acciones_ejecucion.id')
            ->get()
            ->each(fn ($row) => DB::table('accion_ejecucion_comunidad')->insert([
                'accion_ejecucion_id' => $row->accion_ejecucion_id,
                'comunidad_id' => $row->comunidad_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('accion_ejecucion_comunidad');
    }
};
