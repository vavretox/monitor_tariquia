<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResourceRouteOrderTest extends TestCase
{
    #[DataProvider('createRoutes')]
    public function test_create_urls_are_not_captured_as_model_ids(string $path, string $expectedName): void
    {
        $route = Route::getRoutes()->match(Request::create($path, 'GET'));

        $this->assertSame($expectedName, $route->getName());
    }

    public static function createRoutes(): array
    {
        return [
            ['/comunidades/create', 'comunidades.create'],
            ['/proyectos/create', 'proyectos.create'],
            ['/compromisos/create', 'compromisos.create'],
            ['/responsables/create', 'responsables.create'],
            ['/acciones/create', 'acciones.create'],
            ['/necesidades/create', 'necesidades.create'],
        ];
    }
}
