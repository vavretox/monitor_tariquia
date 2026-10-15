<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl">Responsables</h2></x-slot><div class="py-6"><div class="max-w-7xl mx-auto px-4 space-y-4">
@if(session('success'))<div class="bg-emerald-100 text-emerald-800 p-3 rounded">{{ session('success') }}</div>@endif
<div class="ui-toolbar"><form class="flex flex-wrap gap-3"><input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar responsable, cargo o institución" class="border-gray-300 rounded-lg flex-1 min-w-48"><button class="ui-btn-secondary ui-icon-search">Buscar</button><a href="{{ route('responsables.create') }}" class="ui-btn-primary ui-icon-plus">Agregar</a></form></div>
<div class="ui-table-shell responsible-table-wrap"><table class="responsible-table w-full table-fixed text-sm"><colgroup><col style="width:20%"><col style="width:18%"><col style="width:10%"><col style="width:16%"><col style="width:10%"><col style="width:26%"></colgroup><thead class="bg-gray-100"><tr><th class="p-3 text-left">Nombre</th><th class="p-3 text-left">Cargo / institución</th><th class="p-3 text-left">Área</th><th class="p-3 text-left">Contacto</th><th class="p-3 text-center">Compromisos</th><th class="p-3 text-center">Acciones</th></tr></thead><tbody>
@forelse($responsables as $r)<tr class="border-t align-top hover:bg-gray-50"><td class="p-3 font-semibold break-words">{{ $r->nombre_completo }}</td><td class="p-3 break-words">{{ $r->cargo_rol ?: 'No registrado' }}<small class="block text-gray-500 mt-1">{{ $r->institucion }}</small></td><td class="p-3 break-words">{{ $r->area ?: '—' }}</td><td class="p-3 break-all">{{ $r->telefono }}<small class="block mt-1">{{ $r->email }}</small></td><td class="p-3 text-center"><span class="inline-flex bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full font-bold">{{ $r->acciones->count() }}</span></td><td class="p-3"><div class="ui-actions">
<a href="{{ route('responsables.show',$r) }}" class="ui-btn-secondary" aria-label="Ver a {{ $r->nombre_completo }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><span>Ver</span></a>
<a href="{{ route('responsables.edit',$r) }}" class="ui-btn-info" aria-label="Editar a {{ $r->nombre_completo }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6L16 3Z"/><path d="m13 6 5 5"/></svg><span>Editar</span></a>
<form method="POST" action="{{ route('responsables.destroy',$r) }}" onsubmit="return confirm('¿Eliminar este responsable?')">@csrf @method('DELETE')<button type="submit" class="ui-btn-danger" aria-label="Eliminar a {{ $r->nombre_completo }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg><span>Eliminar</span></button></form>
</div></td></tr>
@empty<tr><td colspan="6" class="p-8 text-center text-gray-500">No hay responsables registrados.</td></tr>@endforelse
</tbody></table><div class="p-4 border-t">{{ $responsables->links() }}</div></div>
</div></div>@push('styles')
<style>
.responsible-table-wrap{overflow-x:auto}
.responsible-table{min-width:1200px}

.ui-actions svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex:none}
</style>
@endpush
</x-app-layout>