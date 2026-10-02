<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overdue_action_notifications', function (Blueprint $table) {
            $table->string('action_state')->nullable()->after('days_overdue');
        });
    }

    public function down(): void
    {
        Schema::table('overdue_action_notifications', function (Blueprint $table) {
            $table->dropColumn('action_state');
        });
    }
};
