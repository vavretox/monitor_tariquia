<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('necesidades', function (Blueprint $table) {
            $table->string('ubicacion_especifica')->nullable()->after('descripcion');
            $table->string('fuente')->nullable()->after('ubicacion_especifica');
            $table->date('fecha_identificacion')->nullable()->after('fuente');
        });

        Schema::table('acciones_ejecucion', function (Blueprint $table) {
            $table->text('resultado_esperado')->nullable()->after('descripcion');
            $table->timestamp('ultima_actualizacion_avance')->nullable()->after('avance');
        });

        Schema::create('historial_avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('avance');
            $table->string('estado');
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->index(['accion_ejecucion_id', 'created_at']);
        });

        Schema::table('necesidades', function (Blueprint $table) {
            $table->dropForeign(['comunidad_id']);
            $table->foreignId('comunidad_id')->nullable()->change();
            $table->foreign('comunidad_id')->references('id')->on('comunidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('necesidades', function (Blueprint $table) {
            $table->dropForeign(['comunidad_id']);
            $table->foreign('comunidad_id')->references('id')->on('comunidades')->cascadeOnDelete();
        });
        Schema::dropIfExists('historial_avances');
        Schema::table('acciones_ejecucion', fn (Blueprint $table) => $table->dropColumn(['resultado_esperado', 'ultima_actualizacion_avance']));
        Schema::table('necesidades', fn (Blueprint $table) => $table->dropColumn(['ubicacion_especifica', 'fuente', 'fecha_identificacion']));
    }
};
