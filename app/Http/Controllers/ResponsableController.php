<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Responsable;
use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ResponsableController extends Controller
{
    public function index(Request $request)
    {
        $responsables = Responsable::withCount('accionesEjecucion')
            ->when($request->buscar, fn ($query, $value) => $query->where(fn ($query) => $query
                ->where('nombre_completo', 'like', "%$value%")
                ->orWhere('cargo_rol', 'like', "%$value%")
                ->orWhere('institucion', 'like', "%$value%")))
            ->orderBy('nombre_completo')->paginate(15)->withQueryString();

        return view('responsables.index', compact('responsables'));
    }

    public function create()
    {
        return view('responsables.create', ['areas' => $this->catalogoAreas(), 'instituciones' => $this->catalogoInstituciones()]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $crearUsuario = $request->boolean('crear_usuario');
        $temporaryPassword = $crearUsuario ? Str::password(14) : null;
        $user = DB::transaction(function () use (&$data, $crearUsuario, $temporaryPassword) {
            $areaIds = $this->resolverAreas($data);
            unset($data['crear_usuario']);
            $user = null;
            if ($crearUsuario) {
                Role::findOrCreate('responsable', 'web');
                $user = User::create(['name' => $data['nombre_completo'], 'email' => Str::lower($data['email']), 'password' => $temporaryPassword, 'is_active' => true, 'must_change_password' => true, 'email_verified_at' => now()]);
                $user->assignRole('responsable');
                $data['user_id'] = $user->id;
            }
            $responsable = Responsable::create($data);
            $responsable->areas()->sync($areaIds);

            return $user;
        });
        if ($user) {
            try {
                $user->notify(new UserCredentialsNotification($temporaryPassword));
                $user->update(['credentials_sent_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()->route('responsables.index')
                    ->with('warning', 'El responsable y su cuenta fueron creados, pero el correo no pudo enviarse. Use reenviar credenciales desde Usuarios.');
            }
        }

        return redirect()->route('responsables.index')->with('success', $user ? 'Responsable y cuenta de acceso creados.' : 'Responsable registrado.');
    }

    public function show(Responsable $responsable)
    {
        $responsable->load('accionesEjecucion.demanda.comunidades');

        return view('responsables.show', compact('responsable'));
    }

    public function edit(Responsable $responsable)
    {
        $responsable->load('areas');

        return view('responsables.edit', ['responsable' => $responsable, 'areas' => $this->catalogoAreas(), 'instituciones' => $this->catalogoInstituciones()]);
    }

    public function update(Request $request, Responsable $responsable)
    {
        $data = $this->validar($request, $responsable);
        DB::transaction(function () use ($responsable, &$data) {
            $areaIds = $this->resolverAreas($data);
            $responsable->update($data);
            $responsable->areas()->sync($areaIds);
        });

        return redirect()->route('responsables.index')->with('success', 'Responsable actualizado.');
    }

    public function destroy(Responsable $responsable)
    {
        DB::transaction(function () use ($responsable) {
            $responsable->user?->update(['is_active' => false]);
            $responsable->delete();
        });

        return back()->with('success', 'Responsable eliminado y su acceso fue desactivado.');
    }

    private function validar(Request $request, ?Responsable $responsable = null): array
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'cargo_rol' => ['required', 'string', 'max:255'],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'distinct', 'exists:areas,id'],
            'area_nueva' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'crear_usuario' => ['nullable', 'boolean'],
            'email' => ['nullable', Rule::requiredIf($request->boolean('crear_usuario')), 'email:rfc', 'max:255', Rule::unique('responsables')->ignore($responsable), Rule::unique('users')->ignore($responsable?->user_id)],
            'institucion' => ['required', 'string', 'max:255'],
            'institucion_nueva' => ['nullable', 'required_if:institucion,__nueva__', 'string', 'max:255'],
        ]);

        if ($data['institucion'] === '__nueva__') {
            $data['institucion'] = trim($data['institucion_nueva']);
        }
        unset($data['institucion_nueva']);

        return $data;
    }

    private function catalogoAreas()
    {
        return Area::orderBy('nombre')->get();
    }

    private function resolverAreas(array &$data): array
    {
        $ids = collect($data['area_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        if (! empty($data['area_nueva'])) {
            $area = Area::firstOrCreate(['nombre' => trim($data['area_nueva'])]);
            $ids->push($area->id);
        }
        $ids = $ids->unique()->values();
        $data['area'] = $ids->isEmpty() ? null : Area::whereIn('id', $ids)->orderBy('nombre')->pluck('nombre')->join(', ');
        unset($data['area_ids'], $data['area_nueva']);

        return $ids->all();
    }

    private function catalogoInstituciones()
    {
        return Responsable::query()
            ->whereNotNull('institucion')
            ->where('institucion', '<>', '')
            ->distinct()
            ->orderBy('institucion')
            ->pluck('institucion');
    }
}
