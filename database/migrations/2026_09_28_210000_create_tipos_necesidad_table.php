<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_necesidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::table('necesidades', function (Blueprint $table) {
            $table->foreignId('tipo_necesidad_id')->nullable()->after('comunidad_id')->constrained('tipos_necesidad')->nullOnDelete();
        });

        $ahora = now();
        DB::table('tipos_necesidad')->insert([
            ['nombre' => 'Caminos y accesibilidad', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Salud y emergencias', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Educación', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Agua', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Energía', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Producción y comercialización', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Conectividad y telecomunicaciones', 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::table('necesidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_necesidad_id');
        });
        Schema::dropIfExists('tipos_necesidad');
    }
};