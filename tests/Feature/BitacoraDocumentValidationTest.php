<?php

namespace Tests\Feature;

use App\Http\Controllers\AccionBitacoraController;
use App\Models\AccionEjecucion;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BitacoraDocumentValidationTest extends TestCase
{
    public function test_invalid_documents_are_rejected_before_saving_the_log(): void
    {
        foreach ([
            ['texto.pdf', 'Esto es texto, no un documento PDF.', 100, 'Formato no aceptado. Solo se permiten documentos PDF.'],
            ['documento.txt', "%PDF-1.4\n%%EOF", 100, 'Formato no aceptado. El documento debe tener la extensión .pdf.'],
            ['grande.pdf', "%PDF-1.4\n%%EOF", 20 * 1024 * 1024 + 1, 'Cada documento PDF puede pesar como máximo 20 MB.'],
        ] as [$name, $content, $size, $message]) {
            $path = tempnam(sys_get_temp_dir(), 'bitacora-pdf-');
            try {
                file_put_contents($path, $content);
                $handle = fopen($path, 'r+');
                ftruncate($handle, $size);
                fclose($handle);
                $request = Request::create('/acciones/1/bitacoras', 'POST', [
                    'fecha_hora' => '2026-10-08T16:33',
                    'descripcion' => 'Trabajo con documentación.',
                ], [], ['documentos' => [new UploadedFile($path, $name, null, UPLOAD_ERR_OK, true)]]);
                try {
                    app(AccionBitacoraController::class)->store($request, new AccionEjecucion);
                    $this->fail('El documento inválido debe rechazarse antes de guardar.');
                } catch (ValidationException $exception) {
                    $this->assertSame([$message], $exception->errors()['documentos.0']);
                }
            } finally {
                unlink($path);
            }
        }
    }
}
