<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\AccionEjecucionArchivo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenciaArchivoController extends Controller
{
    public function accion(AccionEjecucionArchivo $archivo): StreamedResponse
    {
        AccionEjecucion::findOrFail($archivo->accion_ejecucion_id);
        abort_unless(Storage::disk('local')->exists($archivo->ruta), 404);

        return Storage::disk('local')->download($archivo->ruta, $archivo->nombre_original, ['Content-Type' => $archivo->tipo_mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']);
    }
}
