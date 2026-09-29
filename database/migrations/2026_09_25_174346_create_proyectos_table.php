<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['salud', 'infraestructura_caminos']);
            $table->enum('estado', ['planificado', 'en_ejecucion', 'completado', 'suspendido'])->default('planificado');
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->decimal('presupuesto', 14, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tipo');
            $table->index('estado');
        });
    }
    public function down(): void { Schema::dropIfExists('proyectos'); }
};
