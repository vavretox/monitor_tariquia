@csrf @php($x=$accionEjecucion??null)
<div class="grid md:grid-cols-2 gap-5">
<div class="md:col-span-2"><x-input-label value="Demanda"/><select name="demanda_id" class="w-full" required>@foreach($demandas as $c)<option value="{{ $c->id }}" @selected(old('demanda_id',$x?->demanda_id??$demandaId??null)==$c->id)>{{ $c->titulo }} — {{ $c->comunidades->pluck('nombre')->join(', ') }}</option>@endforeach</select></div>
<div><x-input-label value="Acción a ejecutar"/><x-text-input name="titulo" class="w-full" :value="old('titulo',$x?->titulo)" required/></div>
<div class="md:col-span-2">
 <x-input-label value="Responsables de la acción"/><p class="mb-2 text-xs text-slate-500">Selecciona uno o varios responsables.</p>
 @php($seleccionados=collect(old('responsable_ids',$x?->responsables?->pluck('id')->all()??[]))->map(fn($id)=>(int)$id)->all())
 <details id="selector-responsables" class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
  <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50">
   <span><strong id="responsables-resumen" class="text-sm text-slate-700">Seleccionar responsables</strong><small id="responsables-nombres" class="mt-0.5 block max-w-xl truncate text-slate-500"></small></span>
   <div class="flex items-center gap-2"><span id="responsables-contador" class="ui-badge bg-indigo-100 text-indigo-700">0</span><svg class="h-4 w-4 text-slate-500 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg></div>
  </summary>
  <div class="border-t border-slate-200 bg-slate-50 p-3">
   <div class="mb-3 flex flex-wrap gap-2"><input id="buscar-responsable" type="search" placeholder="Buscar por nombre, cargo o institución..." class="min-w-56 flex-1 text-sm"><button id="limpiar-responsables" type="button" class="ui-btn-secondary !min-h-0 !px-3 !py-2">Limpiar selección</button></div>
   <div class="mb-2 flex justify-between text-xs text-slate-500"><span id="responsables-resultados"></span><span>Puedes marcar varios</span></div>
   <div id="lista-responsables" class="grid max-h-80 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
    @forelse($responsables as $r)
     <label class="responsable-opcion flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-emerald-300 hover:bg-emerald-50/40" data-busqueda="{{ mb_strtolower($r->nombre_completo.' '.$r->cargo_rol.' '.$r->institucion) }}">
      <input class="responsable-check mt-1 rounded" type="checkbox" name="responsable_ids[]" value="{{ $r->id }}" data-nombre="{{ $r->nombre_completo }}" @checked(in_array($r->id,$seleccionados))>
      <span class="min-w-0"><strong class="block text-sm text-slate-700">{{ $r->nombre_completo }}</strong><small class="block text-slate-500">{{ collect([$r->cargo_rol,$r->institucion])->filter()->join(' · ')?:'Sin cargo o institución' }}</small></span>
     </label>
    @empty<p class="text-sm text-slate-500">No hay responsables registrados.</p>@endforelse
    <p id="responsables-sin-resultados" class="hidden p-5 text-center text-sm text-slate-500 sm:col-span-2">No se encontraron responsables.</p>
   </div>
  </div>
 </details>
 <x-input-error :messages="$errors->get('responsable_ids')" class="mt-2"/>
</div>
<div><x-input-label value="Estado"/><select name="estado" class="w-full">@foreach(['pendiente'=>'Pendiente','en_ejecucion'=>'En ejecución','completada'=>'Completada','bloqueada'=>'Bloqueada'] as $v=>$l)<option value="{{ $v }}" @selected(old('estado',$x?->estado??'pendiente')===$v)>{{ $l }}</option>@endforeach</select></div>
<div><x-input-label value="Fecha de inicio"/><x-text-input type="date" name="fecha_inicio" class="w-full" :value="old('fecha_inicio',$x?->fecha_inicio?->format('Y-m-d'))"/></div>
<div><x-input-label value="Fecha límite"/><x-text-input type="date" name="fecha_limite" class="w-full" :value="old('fecha_limite',$x?->fecha_limite?->format('Y-m-d'))"/></div>
@foreach(['descripcion'=>'Descripción','resultado_esperado'=>'Resultado esperado','proximo_paso'=>'Próximo paso','resultado'=>'Resultado obtenido'] as $campo=>$label)
<div class="md:col-span-2">
 <x-input-label :value="$label"/>
 <textarea name="{{ $campo }}" rows="3" class="w-full">{{ old($campo,$x?->{$campo}) }}</textarea>
 <x-input-error :messages="$errors->get($campo)" class="mt-2"/>
</div>
@endforeach
<div class="md:col-span-2">
 <x-input-label value="Evidencias / observaciones"/>
 <textarea name="evidencias" rows="3" class="w-full" placeholder="Describe aquí las evidencias o agrega observaciones relevantes.">{{ old('evidencias',$x?->evidencias) }}</textarea>
 <x-input-error :messages="$errors->get('evidencias')" class="mt-2"/>
</div>
<div class="md:col-span-2"><x-input-label value="Comentario de esta actualización"/><textarea name="comentario_avance" rows="2" class="w-full" placeholder="Explique brevemente qué cambió en el estado o seguimiento.">{{ old('comentario_avance') }}</textarea><p class="mt-1 text-xs text-slate-500">Al cambiar el estado, esta actualización quedará guardada en el historial.</p></div>
<div class="md:col-span-2 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/50 p-4">
 <x-input-label value="Documentos de respaldo"/>
 <p class="mb-3 text-xs text-slate-600">Puedes seleccionar hasta 10 archivos JPG, JPEG, PNG, PDF, DOC o DOCX. Máximo 10 MB por archivo.</p>
 <input type="file" name="documentos[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full rounded-lg border border-slate-300 bg-white p-2 text-sm">
 <x-input-error :messages="$errors->get('documentos')" class="mt-2"/>
 @foreach($errors->get('documentos.*') as $mensajes)
  <x-input-error :messages="$mensajes" class="mt-2"/>
 @endforeach
 @if($x?->archivos?->isNotEmpty())
  <div class="mt-4 border-t border-emerald-200 pt-4">
   <p class="mb-2 text-sm font-semibold text-slate-700">Archivos cargados</p>
   <div class="space-y-2">
    @foreach($x->archivos as $archivo)
     <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
      <span class="min-w-0">
       <a href="{{ route('evidencias.acciones.descargar', $archivo) }}" class="block truncate text-sm font-semibold text-blue-700 hover:underline">{{ $archivo->nombre_original }}</a>
       <small class="text-slate-500">{{ number_format(($archivo->tamano ?? 0) / 1024, 1) }} KB</small>
      </span>
      <span class="flex shrink-0 items-center gap-2 text-xs font-semibold text-red-700">
       <input type="checkbox" name="eliminar_documentos[]" value="{{ $archivo->id }}" class="rounded border-slate-300 text-red-600">
       Eliminar
      </span>
     </label>
    @endforeach
   </div>
  </div>
 @endif
</div>
</div><div class="mt-6 flex gap-3"><x-primary-button>Guardar acción</x-primary-button><a href="{{ $x ? route('demandas.show',$x->demanda_id) : route('acciones.index') }}" class="ui-btn-secondary">Cancelar</a></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const search=document.getElementById('buscar-responsable'),options=[...document.querySelectorAll('.responsable-opcion')],checks=[...document.querySelectorAll('.responsable-check')],count=document.getElementById('responsables-contador'),summary=document.getElementById('responsables-resumen'),names=document.getElementById('responsables-nombres'),results=document.getElementById('responsables-resultados'),empty=document.getElementById('responsables-sin-resultados');
 const normalize=value=>(value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 const update=()=>{const selected=checks.filter(item=>item.checked);count.textContent=selected.length;summary.textContent=selected.length?selected.length+' responsable'+(selected.length===1?' seleccionado':'s seleccionados'):'Seleccionar responsables';names.textContent=selected.map(item=>item.dataset.nombre).join(', ');options.forEach(option=>option.classList.toggle('ring-2',option.querySelector('input').checked));};
 const filter=()=>{const term=normalize(search.value.trim());let visible=0;options.forEach(option=>{const show=!term||normalize(option.dataset.busqueda).includes(term);option.classList.toggle('hidden',!show);if(show)visible++});results.textContent=visible+' resultado'+(visible===1?'':'s');empty.classList.toggle('hidden',visible!==0);};
 search?.addEventListener('input',filter);checks.forEach(item=>item.addEventListener('change',update));document.getElementById('limpiar-responsables')?.addEventListener('click',()=>{checks.forEach(item=>item.checked=false);update()});update();filter();
});
</script>

