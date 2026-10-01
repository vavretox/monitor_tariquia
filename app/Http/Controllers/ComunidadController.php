<?php

namespace App\Http\Controllers;

use App\Models\Comunidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComunidadController extends Controller
{
    public function index(Request $r)
    {
        $comunidades = Comunidad::when($r->filled('buscar'), fn ($q) => $q->where(fn ($x) => $x->where('nombre', 'like', '%'.$r->buscar.'%')->orWhere('territorio', 'like', '%'.$r->buscar.'%')))->orderBy('territorio')->orderBy('nombre')->paginate(15)->withQueryString();

        return view('comunidades.index', compact('comunidades'));
    }

    public function show(Comunidad $comunidad)
    {
        return view('comunidades.show', compact('comunidad'));
    }

    public function create()
    {
        return view('comunidades.create');
    }

    public function store(Request $r)
    {
        Comunidad::create($this->validar($r));

        return redirect()->route('comunidades.index')->with('success', 'Comunidad agregada correctamente.');
    }

    public function edit(Comunidad $comunidad)
    {
        return view('comunidades.edit', compact('comunidad'));
    }

    public function update(Request $r, Comunidad $comunidad)
    {
        $comunidad->update($this->validar($r, $comunidad));

        return redirect()->route('comunidades.index')->with('success', 'Comunidad actualizada correctamente.');
    }

    public function destroy(Comunidad $comunidad)
    {
        $comunidad->delete();

        return back()->with('success', 'Comunidad eliminada.');
    }

    private function validar(Request $r, ?Comunidad $c = null): array
    {
        return $r->validate(['nombre' => ['required', 'string', 'max:255', Rule::unique('comunidades')->ignore($c)], 'territorio' => 'nullable|string|max:255', 'latitud' => 'required|numeric|between:-90,90', 'longitud' => 'required|numeric|between:-180,180']);
    }
}
