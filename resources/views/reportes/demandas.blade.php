<x-app-layout>
 <x-slot name="header">
  <div class="flex flex-wrap items-center justify-between gap-3">
   <div><p class="text-sm font-semibold text-emerald-700">Monitoreo integral</p><h2 class="text-2xl font-extrabold">Matriz general de seguimiento</h2><p class="text-sm text-slate-500">Demanda → acción → evidencia</p></div>
   <div class="flex flex-wrap gap-2"><a href="{{ route('reportes.alertas') }}" class="ui-btn-secondary">Alertas</a><a href="{{ route('reportes.seguimiento.csv', request()->query()) }}" class="ui-btn-primary">Exportar CSV</a><button onclick="window.print()" class="ui-btn-secondary">Imprimir / PDF</button></div>
  </div>
 </x-slot>
 <div class="py-6"><div class="mx-auto max-w-[1700px] space-y-4 px-4">
  <form method="GET" action="{{ route('reportes.seguimiento') }}" class="ui-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-5">
   <div class="xl:col-span-2"><x-input-label for="buscar" value="Buscar"/><input id="buscar" name="buscar" value="{{ request('buscar') }}" class="mt-1 w-full" placeholder="Demanda, acción o responsable"></div>
   <div><x-input-label for="tipo_necesidad_id" value="Tipo o sector"/><select id="tipo_necesidad_id" name="tipo_necesidad_id" class="mt-1 w-full"><option value="">Todos los tipos</option><option value="__sin_clasificar__" @selected(request('tipo_necesidad_id')==='__sin_clasificar__')>Sin clasificar</option>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" @selected((string)request('tipo_necesidad_id')===(string)$tipo->id)>{{ $tipo->nombre }}</option>@endforeach</select></div>
   <div><x-input-label for="comunidad_id" value="Comunidad"/><select id="comunidad_id" name="comunidad_id" class="mt-1 w-full"><option value="">Todas las comunidades</option>@foreach($comunidades as $comunidad)<option value="{{ $comunidad->id }}" @selected((string)request('comunidad_id')===(string)$comunidad->id)>{{ $comunidad->nombre }}</option>@endforeach</select></div>
   <div><x-input-label for="estado" value="Estado de acción"/><select id="estado" name="estado" class="mt-1 w-full"><option value="">Todos los estados</option>@foreach(['pendiente'=>'Pendiente','en_ejecucion'=>'En ejecución','completada'=>'Completada','bloqueada'=>'Bloqueada'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected(request('estado')===$valor)>{{ $etiqueta }}</option>@endforeach</select></div>
   <div class="flex flex-wrap items-end gap-2 xl:col-span-5"><button class="ui-btn-primary">Aplicar filtros</button>@if(request()->hasAny(['buscar','tipo_necesidad_id','comunidad_id','estado']))<a href="{{ route('reportes.seguimiento') }}" class="ui-btn-secondary">Limpiar filtros</a>@endif<span class="ml-auto text-sm text-slate-500">{{ $demandas->count() }} demanda(s) encontrada(s)</span></div>
  </form>
  <div class="ui-card overflow-x-auto"><table class="min-w-[1250px] w-full text-sm"><thead class="bg-slate-100 text-left text-xs uppercase text-slate-600"><tr><th class="p-3">Comunidades</th><th class="p-3">Tipo o sector</th><th class="p-3">Demanda</th><th class="p-3">Acción</th><th class="p-3">Responsables</th><th class="p-3">Estado</th><th class="p-3">Fecha límite</th><th class="p-3">Próximo paso</th><th class="p-3">Bitácora</th></tr></thead><tbody class="divide-y">
   @forelse($demandas as $demanda)
    @forelse($demanda->acciones as $accion)
     @php($cantidadBitacoras = $accion->bitacoras->count())
     <tr class="hover:bg-emerald-50/40"><td class="p-3">{{ $demanda->comunidades->pluck('nombre')->join(', ') ?: 'Sin comunidad' }}</td><td class="p-3"><span class="ui-badge bg-teal-100 text-teal-800">{{ $demanda->tipo?->nombre ?? 'Sin clasificar' }}</span></td><td class="p-3"><a href="{{ route('demandas.show',$demanda) }}" class="font-semibold text-emerald-700 hover:underline">{{ $demanda->titulo }}</a></td><td class="p-3">{{ $accion->titulo }}</td><td class="p-3">{{ $accion->responsables->pluck('nombre_completo')->join(', ') ?: 'Sin responsable' }}</td><td class="p-3"><span class="ui-badge bg-slate-100 text-slate-700">{{ ucfirst(str_replace('_',' ',$accion->estado)) }}</span></td><td class="p-3 whitespace-nowrap">{{ $accion->fecha_limite?->format('d/m/Y') ?? 'Sin fecha' }}</td><td class="p-3">{{ $accion->proximo_paso ?: 'Sin definir' }}</td><td class="p-3 text-center"><button type="button" class="js-report-log ui-btn !px-3 !py-2 text-white {{ $cantidadBitacoras > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}" data-url="{{ route('acciones.bitacoras.index',$accion) }}" data-title="{{ $accion->titulo }}" title="{{ $cantidadBitacoras > 0 ? $cantidadBitacoras.' registro(s) en la bitácora' : 'Bitácora vacía' }}"><span class="inline-block h-2 w-2 rounded-full bg-white/90"></span>Ver bitácora ({{ $cantidadBitacoras }})</button></td></tr>
    @empty
     <tr><td class="p-3">{{ $demanda->comunidades->pluck('nombre')->join(', ') ?: 'Sin comunidad' }}</td><td class="p-3"><span class="ui-badge bg-teal-100 text-teal-800">{{ $demanda->tipo?->nombre ?? 'Sin clasificar' }}</span></td><td class="p-3"><a href="{{ route('demandas.show',$demanda) }}" class="font-semibold text-emerald-700 hover:underline">{{ $demanda->titulo }}</a></td><td colspan="6" class="p-3 text-slate-500">Demanda sin acciones registradas.</td></tr>
    @endforelse
   @empty
    <tr><td colspan="9" class="p-8 text-center text-slate-500">No existen registros que coincidan con los filtros.</td></tr>
   @endforelse
  </tbody></table></div>
 </div></div>
 @include('reportes._bitacora_drawer')
 @push('styles')<style>@media print{form,nav,header button,header a{display:none!important}body{background:#fff}.ui-card{box-shadow:none!important}table{font-size:9px}}</style>@endpush
</x-app-layout>
