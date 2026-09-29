@csrf
@php($n = $necesidad ?? null)
@php($seleccionadas = collect(old('comunidad_ids', $n?->comunidades?->pluck('id')->all() ?? ($comunidadId ? [$comunidadId] : [])))->map(fn ($id) => (int) $id)->all())
@php($tipoSeleccionado = old('tipo_necesidad_id', $n?->tipo_necesidad_id))

<div class="grid gap-5 md:grid-cols-2">
 <div class="md:col-span-2">
  <x-input-label value="Comunidades involucradas"/>
  <p class="mb-2 text-sm text-slate-500">Seleccione una o varias comunidades relacionadas con esta necesidad.</p>
  <div class="grid max-h-64 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-3">
   @foreach($comunidades as $comunidad)
    <label class="flex cursor-pointer items-start gap-2 rounded-lg bg-white p-3 shadow-sm hover:bg-emerald-50">
     <input type="checkbox" name="comunidad_ids[]" value="{{ $comunidad->id }}" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(in_array($comunidad->id, $seleccionadas))>
     <span><b class="block text-sm text-slate-800">{{ $comunidad->nombre }}</b><small class="text-slate-500">{{ $comunidad->territorio }}</small></span>
    </label>
   @endforeach
  </div>
  <x-input-error :messages="$errors->get('comunidad_ids')" class="mt-2"/>
  <x-input-error :messages="$errors->get('comunidad_ids.*')" class="mt-2"/>
 </div>

 <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
  <x-input-label for="tipo_necesidad_id" value="Tipo de necesidad"/>
  <select id="tipo_necesidad_id" name="tipo_necesidad_id" class="mt-1 w-full" required>
   <option value="">Seleccione un tipo</option>
   @foreach($tiposNecesidad as $tipo)
    <option value="{{ $tipo->id }}" @selected((string) $tipoSeleccionado === (string) $tipo->id)>{{ $tipo->nombre }}</option>
   @endforeach
   <option value="__nuevo__" @selected($tipoSeleccionado === '__nuevo__')>+ Agregar un nuevo tipo</option>
  </select>
  <x-input-error :messages="$errors->get('tipo_necesidad_id')" class="mt-2"/>
  <div id="tipo-necesidad-nuevo-contenedor" class="mt-3 hidden">
   <x-input-label for="tipo_necesidad_nuevo" value="Nombre del nuevo tipo"/>
   <x-text-input id="tipo_necesidad_nuevo" name="tipo_necesidad_nuevo" class="mt-1 w-full" :value="old('tipo_necesidad_nuevo')" placeholder="Ej.: Vivienda e infraestructura"/>
   <p class="mt-1 text-xs text-slate-500">El nuevo tipo quedará disponible para futuros registros.</p>
   <x-input-error :messages="$errors->get('tipo_necesidad_nuevo')" class="mt-2"/>
  </div>
 </div>

 <div>
  <x-input-label value="Título de la necesidad"/>
  <x-text-input name="titulo" class="w-full" :value="old('titulo', $n?->titulo)" required/>
  <x-input-error :messages="$errors->get('titulo')" class="mt-2"/>
 </div>
 <div>
  <x-input-label value="Prioridad"/>
  <select name="prioridad" class="w-full">
   @foreach(['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica'] as $valor => $etiqueta)
    <option value="{{ $valor }}" @selected(old('prioridad', $n?->prioridad ?? 'media') === $valor)>{{ $etiqueta }}</option>
   @endforeach
  </select>
 </div>
 <div>
  <x-input-label value="Estado"/>
  <select name="estado" class="w-full">
   @foreach(['identificada' => 'Identificada', 'en_atencion' => 'En atención', 'resuelta' => 'Resuelta', 'postergada' => 'Postergada'] as $valor => $etiqueta)
    <option value="{{ $valor }}" @selected(old('estado', $n?->estado ?? 'identificada') === $valor)>{{ $etiqueta }}</option>
   @endforeach
  </select>
 </div>
 <div><x-input-label value="Ubicación específica"/><x-text-input name="ubicacion_especifica" class="w-full" :value="old('ubicacion_especifica', $n?->ubicacion_especifica)" placeholder="Ej.: tramo, sector o punto de referencia"/></div>
 <div><x-input-label value="Fecha de identificación"/><x-text-input type="date" name="fecha_identificacion" class="w-full" :value="old('fecha_identificacion', $n?->fecha_identificacion?->format('Y-m-d'))"/></div>
 <div class="md:col-span-2"><x-input-label value="Fuente de información"/><x-text-input name="fuente" class="w-full" :value="old('fuente', $n?->fuente)" placeholder="Ej.: reunión comunal, visita técnica o documento"/></div>
 <div class="md:col-span-2">
  <x-input-label value="Descripción"/>
  <textarea name="descripcion" rows="5" class="w-full">{{ old('descripcion', $n?->descripcion) }}</textarea>
 </div>
</div>

@if($returnTo ?? false)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
<div class="mt-6 flex gap-3"><x-primary-button>Guardar necesidad</x-primary-button><a href="{{ $n ? route('necesidades.show', $n) : route('necesidades.index') }}" class="ui-btn-secondary">Cancelar</a></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
 const select = document.getElementById('tipo_necesidad_id');
 const container = document.getElementById('tipo-necesidad-nuevo-contenedor');
 const input = document.getElementById('tipo_necesidad_nuevo');
 if (!select || !container || !input) return;
 const update = () => {
  const nuevo = select.value === '__nuevo__';
  container.classList.toggle('hidden', !nuevo);
  input.required = nuevo;
  if (nuevo) input.focus();
 };
 select.addEventListener('change', update);
 update();
});
</script>