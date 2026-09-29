<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProyectoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $proyectos = Proyecto::query()->latest()->paginate(min($request->integer('por_pagina', 15), 100));

        return response()->json($proyectos);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data['user_id'] = $request->user()->id;
        $proyecto = Proyecto::create($data);

        return response()->json($proyecto, 201);
    }

    public function show(Proyecto $proyecto): JsonResponse
    {
        return response()->json($proyecto);
    }

    public function update(Request $request, Proyecto $proyecto): JsonResponse
    {
        $proyecto->update($request->validate($this->rules(partial: true)));

        return response()->json($proyecto->fresh());
    }

    public function destroy(Proyecto $proyecto): JsonResponse
    {
        $proyecto->delete();

        return response()->json(null, 204);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes'] : ['required'];

        return [
            'nombre' => [...$required, 'string', 'max:255'],
            'tipo' => [...$required, Rule::in(['salud', 'infraestructura_caminos'])],
            'estado' => [...$required, Rule::in(['planificado', 'en_ejecucion', 'completado', 'suspendido'])],
            'latitud' => [...$required, 'numeric', 'between:-90,90'],
            'longitud' => [...$required, 'numeric', 'between:-180,180'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'fecha_inicio' => ['sometimes', 'nullable', 'date'],
            'fecha_fin_estimada' => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            'presupuesto' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}