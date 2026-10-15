<?php

namespace Tests\Feature;

use App\Http\Controllers\AccionEjecucionController;
use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\TipoNecesidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AccionesFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_use_action_communities_and_demand_types_and_combine_with_existing_filters(): void
    {
        $primera = Comunidad::create(['nombre' => 'Primera']);
        $segunda = Comunidad::create(['nombre' => 'Segunda']);
        $tipo = TipoNecesidad::create(['nombre' => 'Salud']);
        $otroTipo = TipoNecesidad::create(['nombre' => 'Educación']);
        $demanda = Demanda::create(['titulo' => 'Compartida', 'tipo_necesidad_id' => $tipo->id, 'prioridad' => 'media', 'estado' => 'identificada']);
        $demanda->comunidades()->attach([$primera->id, $segunda->id]);
        $otraDemanda = Demanda::create(['titulo' => 'Otra', 'tipo_necesidad_id' => $otroTipo->id, 'prioridad' => 'media', 'estado' => 'identificada']);
        $acciones = collect([
            [$demanda, $primera, 'Visita local', 'pendiente'],
            [$demanda, $segunda, 'Visita ajena', 'pendiente'],
            [$otraDemanda, $primera, 'Visita educativa', 'pendiente'],
            [$demanda, $primera, 'Visita completada', 'completada'],
            [$demanda, $primera, 'Entrega local', 'pendiente'],
        ])->map(function ($datos) {
            [$demanda, $comunidad, $titulo, $estado] = $datos;
            $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => $titulo, 'estado' => $estado]);
            $accion->comunidades()->attach($comunidad);

            return $accion;
        });
        $controller = app(AccionEjecucionController::class);
        $list = fn (array $filters) => $controller->index(Request::create('/acciones', 'GET', $filters))->getData()['acciones'];

        $this->assertCount(5, $list([]));
        $this->assertCount(4, $list(['comunidad_id' => $primera->id]));
        $this->assertCount(4, $list(['tipo_necesidad_id' => $tipo->id]));
        $this->assertSame([$acciones[0]->id], $list([
            'comunidad_id' => $primera->id,
            'tipo_necesidad_id' => $tipo->id,
            'estado' => 'pendiente',
            'buscar' => 'Visita',
        ])->pluck('id')->all());
        $this->assertCount(0, $list(['comunidad_id' => $segunda->id, 'tipo_necesidad_id' => $otroTipo->id]));
        $html = $controller->index(Request::create('/acciones'))->render();
        $this->assertStringContainsString('Todas las comunidades', $html);
        $this->assertStringContainsString('Todos los tipos', $html);
    }
}
