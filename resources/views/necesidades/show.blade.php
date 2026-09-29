<x-app-layout>
 <x-slot name="header">
  <div class="flex flex-wrap items-start justify-between gap-3">
   <div><p class="text-sm font-semibold text-amber-700">Necesidad</p><h2 class="text-xl font-bold">{{ $necesidad->titulo }}</h2><div class="mt-2 flex flex-wrap gap-1"><span class="ui-badge bg-teal-100 text-teal-800">{{ $necesidad->tipo?->nombre ?? 'Sin clasificar' }}</span>@foreach($necesidad->comunidades as $comunidad)<a href="{{ route('comunidades.show', $comunidad) }}" class="ui-badge bg-emerald-100 text-emerald-700">{{ $comunidad->nombre }}</a>@endforeach</div></div>
   <div class="flex gap-2">@can('compromisos.create')<a href="{{ route('compromisos.create', ['necesidad_id' => $necesidad->id]) }}" class="ui-btn-primary">+ Compromiso</a>@endcan @can('necesidades.edit')<a href="{{ route('necesidades.edit', $necesidad) }}" class="ui-btn-info">Editar</a>@endcan</div>
  </div>
 </x-slot>
 <div class="py-6"><div class="mx-auto max-w-6xl space-y-5 px-4">
  @if(session('success'))<div class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
  <div class="ui-form-card"><div class="grid gap-5 md:grid-cols-4">
   <div><p class="text-xs font-bold uppercase text-slate-500">Tipo</p><p>{{ $necesidad->tipo?->nombre ?? 'Sin clasificar' }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Prioridad</p><p>{{ ucfirst($necesidad->prioridad) }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Estado</p><p>{{ ucfirst(str_replace('_', ' ', $necesidad->estado)) }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Comunidades</p><p>{{ $necesidad->comunidades->count() }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Ubicación específica</p><p>{{ $necesidad->ubicacion_especifica ?: 'Sin definir' }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Fuente</p><p>{{ $necesidad->fuente ?: 'Sin registrar' }}</p></div>
   <div><p class="text-xs font-bold uppercase text-slate-500">Fecha de identificación</p><p>{{ $necesidad->fecha_identificacion?->format('d/m/Y') ?: 'Sin registrar' }}</p></div>
   <div class="md:col-span-4"><p class="text-xs font-bold uppercase text-slate-500">Descripción</p><p>{{ $necesidad->descripcion ?: 'No registrada' }}</p></div>
  </div></div>
  <div class="ui-table-shell"><div class="border-b p-5"><h3 class="text-lg font-bold">Compromisos relacionados</h3></div><div class="space-y-3 p-5">
   @forelse($necesidad->compromisos as $compromiso)<a href="{{ route('compromisos.show', $compromiso) }}" class="block rounded-xl border p-4 hover:bg-slate-50"><div class="flex flex-wrap justify-between gap-2"><b>{{ $compromiso->titulo }}</b><span class="ui-badge bg-slate-100 text-slate-700">{{ ucfirst(str_replace('_', ' ', $compromiso->estado)) }}</span></div><p class="mt-1 text-sm text-slate-500">{{ $compromiso->area }} · {{ $compromiso->accionesEjecucion->count() }} acciones</p></a>@empty<p class="text-slate-500">Esta necesidad todavía no tiene compromisos.</p>@endforelse
  </div></div>
 </div></div>
</x-app-layout>