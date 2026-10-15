<x-app-layout>
<x-slot name="header">
 <div class="flex flex-wrap items-start justify-between gap-3">
  <div><p class="text-sm font-semibold text-emerald-700">Acciones / Bitácora</p><h2 class="text-xl font-bold">{{ $accion->titulo }}</h2><p class="text-sm text-slate-500">{{ $accion->demanda?->titulo }} · {{ $accion->comunidades->pluck('nombre')->join(', ') }}</p></div>
  <div class="flex gap-2"><a href="{{ route('acciones.index') }}" class="ui-btn-secondary ui-icon-back">Volver a acciones</a>@can('acciones.edit')<a href="{{ route('acciones.edit',$accion) }}" class="ui-btn-info ui-icon-edit">Editar acción</a>@endcan</div>
 </div>
</x-slot>
<div class="py-6"><div class="mx-auto max-w-6xl space-y-5 px-4">
 @if(session('success'))<div class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
 @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700"><b>No se pudo guardar la bitácora.</b><ul class="mt-2 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm"><b>Control mensual:</b> cada responsable debe presentar una bitácora propia con descripción y porcentaje de avance por mes. Si no hubo avance, mantenga el porcentaje y explique el motivo. <a href="{{ route('reportes.control-mensual') }}" class="font-semibold text-emerald-700 underline">Consultar mis informes</a></div>
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
      @php($fotografias = $registro->fotos->reject(fn ($archivo) => $archivo->tipo_mime === 'application/pdf'))
      @php($documentos = $registro->fotos->filter(fn ($archivo) => $archivo->tipo_mime === 'application/pdf'))
      @if($fotografias->isNotEmpty())<div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">@foreach($fotografias as $foto)<a href="{{ route('evidencias.bitacoras.foto',$foto) }}" target="_blank" class="group overflow-hidden rounded-lg border bg-slate-100"><img src="{{ route('evidencias.bitacoras.foto',$foto) }}" alt="Evidencia: {{ $foto->nombre_original }}" loading="lazy" class="h-28 w-full object-cover transition group-hover:scale-105"><span class="block truncate px-2 py-1 text-xs text-slate-600">{{ $foto->nombre_original }}</span></a>@endforeach</div>@endif
      @if($documentos->isNotEmpty())<div class="mt-3 space-y-2"><h4 class="text-sm font-semibold text-slate-700">Documentación PDF</h4>@foreach($documentos as $documento)<a href="{{ route('evidencias.bitacoras.foto',$documento) }}" target="_blank" rel="noopener" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-emerald-700 hover:underline">{{ $documento->nombre_original }} <span class="text-xs text-slate-500">(PDF · {{ number_format($documento->tamano / 1048576, 2) }} MB)</span></a>@endforeach</div>@endif
      @can('acciones.edit')
       @php($editing = (string) old('registro_id') === (string) $registro->id)
       <details class="mt-3" @if($editing) open @endif>
        <summary class="cursor-pointer text-xs font-semibold text-emerald-700 hover:underline">Editar registro</summary>
        <form method="POST" action="{{ route('acciones.bitacoras.update',[$accion,$registro]) }}" class="mt-3 space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
         @csrf @method('PUT')
         <input type="hidden" name="registro_id" value="{{ $registro->id }}">
         <div><label for="log-date-{{ $registro->id }}" class="block text-sm font-medium text-gray-700">Fecha y hora del trabajo</label><input id="log-date-{{ $registro->id }}" type="datetime-local" name="fecha_hora" class="w-full" value="{{ $editing ? old('fecha_hora') : $registro->fecha_hora->format('Y-m-d\TH:i') }}" required></div>
         <div><label for="log-description-{{ $registro->id }}" class="block text-sm font-medium text-gray-700">Descripción del trabajo realizado</label><textarea id="log-description-{{ $registro->id }}" name="descripcion" rows="4" maxlength="5000" class="w-full" required>{{ $editing ? old('descripcion') : $registro->descripcion }}</textarea></div>
         <div><label for="log-progress-{{ $registro->id }}" class="block text-sm font-medium text-gray-700">Avance alcanzado (opcional)</label><input id="log-progress-{{ $registro->id }}" type="number" name="avance" min="0" max="100" class="w-full" value="{{ $editing ? old('avance') : $registro->avance }}"></div>
         <p class="text-xs text-slate-500">Las fotografías y los documentos adjuntos se conservan.</p>
         <div class="flex flex-wrap gap-2"><x-primary-button icon="save">Guardar cambios</x-primary-button><button type="button" class="ui-btn-secondary ui-icon-close" onclick="this.closest('details').open=false">Cancelar</button></div>
        </form>
       </details>
      @endcan
      @can('acciones.edit')<form method="POST" action="{{ route('acciones.bitacoras.destroy',[$accion,$registro]) }}" class="mt-2" onsubmit="return confirm('¿Eliminar este registro y sus archivos adjuntos?')">@csrf @method('DELETE')<button class="ui-btn-danger ui-icon-delete text-xs font-semibold text-red-600 hover:underline">Eliminar registro</button></form>@endcan
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
     <div><x-input-label value="Avance alcanzado"/><div class="flex items-center gap-2"><x-text-input type="number" name="avance" min="0" max="100" class="w-full" :value="old('avance')" :required="auth()->user()->hasRole('responsable')"/><span class="font-bold text-slate-500">%</span></div></div>
     <div><x-input-label value="Fotografías de evidencia"/><input type="file" name="fotografias[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Hasta 10 imágenes; máximo 8 MB cada una.</p></div>
     <div><x-input-label for="documentos-bitacora" value="Documentación PDF"/><input id="documentos-bitacora" type="file" name="documentos[]" multiple accept=".pdf,application/pdf" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm" aria-describedby="documentos-ayuda documentos-error"><p id="documentos-ayuda" class="mt-1 text-xs text-slate-500">Hasta 10 documentos; solo PDF, máximo 20 MB cada uno.</p><p id="documentos-error" role="alert" class="mt-1 text-sm text-red-600"></p></div>
     <x-primary-button icon="save" class="w-full justify-center">Guardar registro</x-primary-button>
    </div>
   </form>
  @endcan
 </section>
</div></div>
<script>
(() => {
 const input = document.getElementById('documentos-bitacora');
 if (!input) return;
 const error = document.getElementById('documentos-error');
 const validate = () => {
  let message = '';
  if (input.files.length > 10) message = 'Puedes adjuntar hasta 10 documentos por registro.';
  for (const file of input.files) {
   if (!/\.pdf$/i.test(file.name) || (file.type && file.type !== 'application/pdf')) { message = 'Formato no aceptado. Solo se permiten documentos PDF.'; break; }
   if (file.size > 20 * 1024 * 1024) { message = 'Cada documento PDF puede pesar como máximo 20 MB.'; break; }
  }
  input.setCustomValidity(message);
  error.textContent = message;
  return !message;
 };
 input.addEventListener('change', validate);
 input.form.addEventListener('submit', event => { if (!validate()) { event.preventDefault(); input.reportValidity(); } });
})();
</script>
</x-app-layout>
