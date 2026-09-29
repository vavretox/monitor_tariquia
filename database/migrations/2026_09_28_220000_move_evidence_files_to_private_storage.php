<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->move('public', 'local');
    }

    public function down(): void
    {
        $this->move('local', 'public');
    }

    private function move(string $from, string $to): void
    {
        foreach (['proyecto_archivos', 'accion_ejecucion_archivos'] as $table) {
            DB::table($table)->orderBy('id')->each(function ($archivo) use ($from, $to): void {
                if (! Storage::disk($from)->exists($archivo->ruta)) {
                    return;
                }

                if (! Storage::disk($to)->exists($archivo->ruta)) {
                    Storage::disk($to)->put($archivo->ruta, Storage::disk($from)->get($archivo->ruta));
                }

                Storage::disk($from)->delete($archivo->ruta);
            });
        }
    }
};