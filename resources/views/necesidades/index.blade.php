<x-app-layout>
 <x-slot name="header">
  <div class="flex flex-wrap items-center justify-between gap-3">
   <div><h2 class="text-xl font-bold">Necesidades</h2><p class="text-sm text-slate-500">Necesidades compartidas por una o varias comunidades</p></div>
   @can('necesidades.create')<a href="{{ route('necesidades.create') }}" class="ui-btn-primary">+ Nueva necesidad</a>@endcan
  </div>
 </x-slot>
 <div class="py-6"><div class="mx-auto max-w-7xl space-y-4 px-4">
  @if(session('success'))<div class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
  <div class="ui-toolbar">
   <form class="flex flex-1 flex-wrap gap-3">
    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar necesidad" class="min-w-48 flex-1">
    <select name="tipo_necesidad_id"><option value="">Todos los tipos</option>@foreach($tiposNecesidad as $tipo)<option value="{{ $tipo->id }}" @selected(request('tipo_necesidad_id') == $tipo->id)>{{ $tipo->nombre }}</option>@endforeach</select>
    <select name="comunidad_id"><option value="">Todas las comunidades</option>@foreach($comunidades as $comunidad)<option value="{{ $comunidad->id }}" @selected(request('comunidad_id') == $comunidad->id)>{{ $comunidad->nombre }}</option>@endforeach</select>
    <select name="estado"><option value="">Todos los estados</option>@foreach(['identificada' => 'Identificada', 'en_atencion' => 'En atención', 'resuelta' => 'Resuelta', 'postergada' => 'Postergada'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>@endforeach</select>
    <button class="ui-btn-secondary !bg-slate-800 !text-white">Filtrar</button>
   </form>
  </div>
  <div class="ui-table-shell overflow-x-auto"><table class="w-full min-w-[1000px] text-sm">
   <thead><tr><th class="p-3 text-left">Necesidad</th><th class="p-3 text-left">Tipo</th><th class="p-3 text-left">Comunidades involucradas</th><th class="p-3 text-left">Estado</th><th class="p-3 text-center">Compromisos</th><th class="p-3 text-center">Gestión</th></tr></thead>
   <tbody>
    @forelse($necesidades as $necesidad)
     <tr class="border-t align-top">
      <td class="p-3"><b>{{ $necesidad->titulo }}</b><small class="mt-1 block text-slate-500">Prioridad {{ $necesidad->prioridad }}</small></td>
      <td class="p-3"><span class="ui-badge bg-teal-100 text-teal-800">{{ $necesidad->tipo?->nombre ?? 'Sin clasificar' }}</span></td>
      <td class="p-3">@foreach($necesidad->comunidades as $comunidad)<span class="ui-badge mb-1 mr-1 bg-emerald-100 text-emerald-700">{{ $comunidad->nombre }}</span>@endforeach</td>
      <td class="p-3"><span class="ui-badge bg-slate-100 text-slate-700">{{ ucfirst(str_replace('_', ' ', $necesidad->estado)) }}</span></td>
      <td class="p-3 text-center"><span class="ui-badge bg-blue-100 text-blue-700">{{ $necesidad->compromisos_count }}</span></td>
      <td class="p-3"><div class="flex justify-center gap-2"><a href="{{ route('necesidades.show', $necesidad) }}" class="ui-btn-secondary !px-3 !py-2">Ver</a>@can('necesidades.edit')<a href="{{ route('necesidades.edit', $necesidad) }}" class="ui-btn-info !px-3 !py-2">Editar</a>@endcan</div></td>
     </tr>
    @empty<tr><td colspan="6" class="p-8 text-center text-slate-500">No hay necesidades registradas.</td></tr>@endforelse
   </tbody>
  </table><div class="border-t p-4">{{ $necesidades->links() }}</div></div>
 </div></div>
</x-app-layout>