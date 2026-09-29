<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucionArchivo;
use App\Models\ProyectoArchivo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenciaArchivoController extends Controller
{
    public function proyecto(ProyectoArchivo $archivo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($archivo->ruta), 404);

        return Storage::disk('local')->download(
            $archivo->ruta,
            $archivo->nombre_original,
            ['Content-Type' => $archivo->tipo_mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']
        );
    }

    public function accion(AccionEjecucionArchivo $archivo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($archivo->ruta), 404);

        return Storage::disk('local')->download(
            $archivo->ruta,
            $archivo->nombre_original,
            ['Content-Type' => $archivo->tipo_mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']
        );
    }
}