<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('informes_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('responsables')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('periodo', 7);
            $table->foreignId('accion_bitacora_id')->nullable()->constrained('accion_bitacoras')->nullOnDelete();
            $table->string('estado')->default('pendiente');
            $table->timestamp('presentado_at')->nullable();
            $table->timestamp('ultima_entrega_at')->nullable();
            $table->foreignId('revisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->text('comentario_revision')->nullable();
            $table->timestamp('recordatorio_at')->nullable();
            $table->timestamp('alerta_at')->nullable();
            $table->timestamps();
            $table->unique(['accion_ejecucion_id', 'responsable_id', 'periodo'], 'informe_mensual_accion_responsable_periodo');
            $table->index(['periodo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informes_mensuales');
    }
};
