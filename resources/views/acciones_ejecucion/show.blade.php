<x-app-layout>
<x-slot name="header">
 <div class="flex flex-wrap items-start justify-between gap-3">
  <div><p class="text-sm font-semibold text-emerald-700">Acciones / Bitácora</p><h2 class="text-xl font-bold">{{ $accion->titulo }}</h2><p class="text-sm text-slate-500">{{ $accion->demanda?->titulo }} · {{ $accion->comunidades->pluck('nombre')->join(', ') }}</p></div>
  <div class="flex gap-2"><a href="{{ route('acciones.index') }}" class="ui-btn-secondary">Volver a acciones</a>@can('acciones.edit')<a href="{{ route('acciones.edit',$accion) }}" class="ui-btn-info">Editar acción</a>@endcan</div>
 </div>
</x-slot>
<div class="py-6"><div class="mx-auto max-w-6xl space-y-5 px-4">
 @if(session('success'))<div class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
 @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700"><b>No se pudo guardar la bitácora.</b><ul class="mt-2 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <section class="ui-form-card">
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Información de la acción</p><h3 class="text-lg font-bold text-slate-900">{{ $accion->titulo }}</h3></div><div class="flex gap-2"><span class="ui-badge bg-slate-100 text-slate-700">{{ ucfirst(str_replace('_',' ',$accion->estado)) }}</span><span class="ui-badge bg-emerald-100 text-emerald-700">{{ $accion->avance }}%</span></div></div>
  <div class="grid gap-4 text-sm md:grid-cols-4">
   <div><b class="block text-xs uppercase text-slate-500">Responsables</b>{{ $accion->responsables->pluck('nombre_completo')->join(', ')?:'Sin responsable' }}</div>
   <div><b class="block text-xs uppercase text-slate-500">Comunidades involucradas</b>{{ $accion->comunidades->pluck('nombre')->join(', ')?:'Sin comunidad' }}</div>
   <div><b class="block text-xs uppercase text-slate-500">Inicio</b>{{ $accion->fecha_inicio?->format('d/m/Y')??'Sin definir' }}</div>
   <div><b class="block text-xs uppercase text-slate-500">Fecha límite</b>{{ $accion->fecha_limite?->format('d/m/Y')??'Sin definir' }}</div>
   <div><b class="block text-xs uppercase text-slate-500">Registros</b>{{ $accion->bitacoras->count() }} entradas</div>
   @if($accion->descripcion)<div class="md:col-span-4"><b class="block text-xs uppercase text-slate-500">Descripción</b>{{ $accion->descripcion }}</div>@endif
  </div>
 </section>
 <section id="bitacora" class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
   <div class="mb-5"><h3 class="text-lg font-bold text-slate-900">Bitácora de trabajos</h3><p class="text-sm text-slate-500">Historial de actividades realizadas para cumplir esta acción.</p></div>
   <div class="space-y-5">
    @forelse($accion->bitacoras as $registro)
     <article class="relative border-l-2 border-emerald-300 pl-5">
      <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full border-2 border-white bg-emerald-600 ring-1 ring-emerald-300"></span>
      <div class="flex flex-wrap items-start justify-between gap-2"><div><time class="text-sm font-bold text-slate-800">{{ $registro->fecha_hora->format('d/m/Y · H:i') }}</time><p class="text-xs text-slate-500">{{ $registro->usuario?->name??'Usuario eliminado' }}</p></div>@if($registro->avance!==null)<span class="ui-badge bg-emerald-100 text-emerald-700">Avance: {{ $registro->avance }}%</span>@endif</div>
      <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $registro->descripcion }}</p>
      @if($registro->fotos->isNotEmpty())<div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">@foreach($registro->fotos as $foto)<a href="{{ route('evidencias.bitacoras.foto',$foto) }}" target="_blank" class="group overflow-hidden rounded-lg border bg-slate-100"><img src="{{ route('evidencias.bitacoras.foto',$foto) }}" alt="Evidencia: {{ $foto->nombre_original }}" loading="lazy" class="h-28 w-full object-cover transition group-hover:scale-105"><span class="block truncate px-2 py-1 text-xs text-slate-600">{{ $foto->nombre_original }}</span></a>@endforeach</div>@endif
      @can('acciones.edit')<form method="POST" action="{{ route('acciones.bitacoras.destroy',[$accion,$registro]) }}" class="mt-2" onsubmit="return confirm('¿Eliminar este registro y sus fotografías?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600 hover:underline">Eliminar registro</button></form>@endcan
     </article>
    @empty<div class="rounded-xl border border-dashed p-8 text-center text-sm text-slate-500">Esta acción todavía no tiene registros de bitácora.</div>@endforelse
   </div>
  </div>
  @can('acciones.edit')
   <form method="POST" action="{{ route('acciones.bitacoras.store',$accion) }}" enctype="multipart/form-data" class="h-fit rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
    @csrf
    <h3 class="font-bold text-slate-900">Registrar trabajo</h3><p class="mb-4 text-xs text-slate-500">Agregue una entrada independiente a la bitácora.</p>
    <div class="space-y-4">
     <div><x-input-label value="Fecha y hora del trabajo"/><x-text-input type="datetime-local" name="fecha_hora" class="w-full" :value="old('fecha_hora',now()->format('Y-m-d\TH:i'))" required/></div>
     <div><x-input-label value="Descripción del trabajo realizado"/><textarea name="descripcion" rows="6" class="w-full" placeholder="Indique qué se hizo, dónde y cuál fue el resultado." required>{{ old('descripcion') }}</textarea></div>
     <div><x-input-label value="Avance alcanzado (opcional)"/><div class="flex items-center gap-2"><x-text-input type="number" name="avance" min="0" max="100" class="w-full" :value="old('avance')"/><span class="font-bold text-slate-500">%</span></div></div>
     <div><x-input-label value="Fotografías de evidencia"/><input type="file" name="fotografias[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Hasta 10 imágenes; máximo 8 MB cada una.</p></div>
     <x-primary-button class="w-full justify-center">Guardar registro</x-primary-button>
    </div>
   </form>
  @endcan
 </section>
</div></div>
</x-app-layout>
