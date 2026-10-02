<?php

namespace App\Http\Controllers;

use App\Models\AccionBitacora;
use App\Models\AccionBitacoraFoto;
use App\Models\AccionEjecucion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AccionBitacoraController extends Controller
{
    public function index(AccionEjecucion $accione): JsonResponse
    {
        $accione->load(['bitacoras.usuario', 'bitacoras.fotos']);

        return response()->json([
            'id' => $accione->id,
            'titulo' => $accione->titulo,
            'estado' => $accione->estado,
            'avance' => $accione->avance,
            'url' => route('acciones.show', $accione),
            'registros' => $accione->bitacoras->map(fn ($registro) => [
                'id' => $registro->id,
                'fecha_hora' => $registro->fecha_hora->format('d/m/Y · H:i'),
                'descripcion' => $registro->descripcion,
                'avance' => $registro->avance,
                'usuario' => $registro->usuario?->name ?? 'Usuario eliminado',
                'fotos' => $registro->fotos->map(fn ($foto) => [
                    'nombre' => $foto->nombre_original,
                    'url' => route('evidencias.bitacoras.foto', $foto),
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request, AccionEjecucion $accione): RedirectResponse
    {
        $data = $request->validate([
            'fecha_hora' => ['required', 'date'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'avance' => ['nullable', 'integer', 'between:0,100'],
            'fotografias' => ['nullable', 'array', 'max:10'],
            'fotografias.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'fotografias.max' => 'Puedes subir hasta 10 fotografías por registro.',
            'fotografias.*.image' => 'Cada evidencia debe ser una imagen válida.',
            'fotografias.*.mimes' => 'Las fotografías deben ser JPG, JPEG, PNG o WEBP.',
            'fotografias.*.max' => 'Cada fotografía puede pesar como máximo 8 MB.',
        ]);

        $fotografias = $request->file('fotografias', []);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($accione, $data, $fotografias, &$storedPaths) {
                $bitacora = $accione->bitacoras()->create([
                    'user_id' => auth()->id(),
                    'fecha_hora' => $data['fecha_hora'],
                    'descripcion' => $data['descripcion'],
                    'avance' => $data['avance'] ?? null,
                ]);

                foreach ($fotografias as $fotografia) {
                    $ruta = $fotografia->store("acciones/{$accione->id}/bitacoras/{$bitacora->id}", 'local');
                    $storedPaths[] = $ruta;
                    $bitacora->fotos()->create([
                        'ruta' => $ruta,
                        'nombre_original' => $fotografia->getClientOriginalName(),
                        'tipo_mime' => $fotografia->getClientMimeType(),
                        'tamano' => $fotografia->getSize(),
                    ]);
                }

                if (isset($data['avance'])) {
                    $accione->update(['avance' => $data['avance'], 'ultima_actualizacion_avance' => now()]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return redirect(route('acciones.show', $accione).'#bitacora')->with('success', 'Registro agregado a la bitácora.');
    }

    public function destroy(AccionEjecucion $accione, AccionBitacora $bitacora): RedirectResponse
    {
        abort_unless($bitacora->accion_ejecucion_id === $accione->id, 404);
        $paths = $bitacora->fotos()->pluck('ruta')->all();
        DB::transaction(fn () => $bitacora->delete());
        Storage::disk('local')->delete($paths);

        return redirect(route('acciones.show', $accione).'#bitacora')->with('success', 'Registro eliminado de la bitácora.');
    }

    public function foto(AccionBitacoraFoto $foto): StreamedResponse
    {
        AccionEjecucion::findOrFail($foto->bitacora()->value('accion_ejecucion_id'));
        abort_unless(Storage::disk('local')->exists($foto->ruta), 404);

        return Storage::disk('local')->response($foto->ruta, $foto->nombre_original, [
            'Content-Type' => $foto->tipo_mime ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $foto->nombre_original).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
