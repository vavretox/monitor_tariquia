<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('area_responsable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('responsables')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['area_id', 'responsable_id']);
        });

        DB::table('responsables')->whereNotNull('area')->where('area', '<>', '')->orderBy('id')->each(function ($responsable) {
            foreach (array_filter(array_map('trim', preg_split('/[,;]+/', $responsable->area))) as $nombre) {
                $areaId = DB::table('areas')->where('nombre', $nombre)->value('id');
                if (! $areaId) {
                    $areaId = DB::table('areas')->insertGetId(['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('area_responsable')->insertOrIgnore(['area_id' => $areaId, 'responsable_id' => $responsable->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_responsable');
        Schema::dropIfExists('areas');
    }
};
