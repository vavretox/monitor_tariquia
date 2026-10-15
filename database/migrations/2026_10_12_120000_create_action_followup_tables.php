<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accion_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accion_observaciones')->nullOnDelete();
            $table->text('mensaje');
            $table->timestamps();
            $table->index(['accion_ejecucion_id', 'created_at']);
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('accion_observaciones');
    }
};
