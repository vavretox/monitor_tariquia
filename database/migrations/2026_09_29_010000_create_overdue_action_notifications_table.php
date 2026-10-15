<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overdue_action_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accion_ejecucion_id')->constrained('acciones_ejecucion')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('notified_on');
            $table->unsignedInteger('days_overdue');
            $table->timestamps();
            $table->unique(['accion_ejecucion_id', 'user_id', 'notified_on'], 'overdue_action_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdue_action_notifications');
    }
};
