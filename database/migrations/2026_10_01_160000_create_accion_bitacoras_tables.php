<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accion_bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->text('descripcion');
            $table->unsignedTinyInteger('avance')->nullable();
            $table->timestamps();
            $table->index(['accion_ejecucion_id', 'fecha_hora']);
        });

        Schema::create('accion_bitacora_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_bitacora_id')->constrained('accion_bitacoras')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('tipo_mime', 100)->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accion_bitacora_fotos');
        Schema::dropIfExists('accion_bitacoras');
    }
};
