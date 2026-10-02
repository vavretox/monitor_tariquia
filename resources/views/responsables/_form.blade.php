@csrf @php($r=$responsable??null)
<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Los campos marcados con <strong>*</strong> son obligatorios.</div>
<div class="grid gap-5 md:grid-cols-2">
 <div><x-input-label value="Nombre completo *"/><x-text-input name="nombre_completo" class="mt-1 block w-full" :value="old('nombre_completo',$r?->nombre_completo)" required autocomplete="name"/><x-input-error :messages="$errors->get('nombre_completo')" class="mt-2"/></div>
 <div><x-input-label value="Cargo *"/><x-text-input name="cargo_rol" class="mt-1 block w-full" :value="old('cargo_rol',$r?->cargo_rol)" required/><x-input-error :messages="$errors->get('cargo_rol')" class="mt-2"/></div>
 <div>
  <x-input-label value="Áreas o unidades *"/>
  @php($areasSeleccionadas=collect(old('area_ids',$r?->areas?->pluck('id')->all()??[]))->map(fn($id)=>(int)$id)->all())
  <details class="mt-1 overflow-hidden rounded-xl border border-slate-300 bg-white">
   <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 hover:bg-slate-50"><span id="areas-resumen" class="text-sm font-medium">Seleccionar áreas o unidades</span><span id="areas-contador" class="ui-badge bg-indigo-100 text-indigo-700">0</span></summary>
   <div class="grid max-h-64 gap-2 overflow-y-auto border-t bg-slate-50 p-3 sm:grid-cols-2">@forelse($areas as $area)<label class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2 hover:border-emerald-300"><input class="area-check rounded" type="checkbox" name="area_ids[]" value="{{ $area->id }}" data-nombre="{{ $area->nombre }}" @checked(in_array($area->id,$areasSeleccionadas))><span class="text-sm">{{ $area->nombre }}</span></label>@empty<p class="text-sm text-slate-500">No hay áreas registradas.</p>@endforelse</div>
  </details>
  <div class="mt-3"><x-input-label for="area_nueva" value="Agregar nueva área o unidad"/><x-text-input id="area_nueva" name="area_nueva" class="mt-1 block w-full" :value="old('area_nueva')" placeholder="Opcional: escriba un nuevo nombre"/><p class="mt-1 text-xs text-slate-500">Puedes seleccionar varias existentes y agregar una nueva al mismo tiempo.</p><x-input-error :messages="$errors->get('area_nueva')" class="mt-2"/></div>
  <x-input-error :messages="$errors->get('area_ids')" class="mt-2"/>
 </div>
 <div>
  <x-input-label value="Institución *"/>
  @php($institucionSeleccionada=old('institucion',$r?->institucion))
  <select id="institucion" name="institucion" class="mt-1 block w-full" required>
   <option value="">Seleccione una institución</option>
   @foreach($instituciones as $institucion)<option value="{{ $institucion }}" @selected($institucionSeleccionada===$institucion)>{{ $institucion }}</option>@endforeach
   <option value="__nueva__" @selected($institucionSeleccionada==='__nueva__')>+ Agregar nueva institución</option>
  </select>
  <div id="institucion-nueva-contenedor" class="mt-3 hidden"><x-input-label for="institucion_nueva" value="Nombre de la nueva institución *"/><x-text-input id="institucion_nueva" name="institucion_nueva" class="mt-1 block w-full" :value="old('institucion_nueva')" placeholder="Escriba el nuevo nombre"/><x-input-error :messages="$errors->get('institucion_nueva')" class="mt-2"/></div>
  <x-input-error :messages="$errors->get('institucion')" class="mt-2"/>
 </div>
 <div><x-input-label value="Teléfono"/><x-text-input name="telefono" class="mt-1 block w-full" :value="old('telefono',$r?->telefono)" autocomplete="tel"/><x-input-error :messages="$errors->get('telefono')" class="mt-2"/></div>
 <div><x-input-label value="Correo electrónico"/><x-text-input type="email" name="email" class="mt-1 block w-full" :value="old('email',$r?->email)" autocomplete="email"/><x-input-error :messages="$errors->get('email')" class="mt-2"/></div>
</div>
<div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4"><label class="flex items-start gap-3"><input type="hidden" name="crear_usuario" value="0"><input type="checkbox" name="crear_usuario" value="1" class="mt-1 rounded" @checked(old('crear_usuario'))><span><strong class="block text-indigo-900">Crear cuenta de acceso</strong><small class="text-indigo-700">Requiere un correo electrónico. Se asignará el rol Responsable y se enviará una contraseña temporal al correo indicado.</small></span></label><x-input-error :messages="$errors->get('crear_usuario')" class="mt-2"/></div>
<div class="mt-6 flex gap-3"><x-primary-button>Guardar responsable</x-primary-button><a href="{{ route('responsables.index') }}" class="ui-btn-secondary">Cancelar</a></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const checks=[...document.querySelectorAll('.area-check')],count=document.getElementById('areas-contador'),summary=document.getElementById('areas-resumen');const update=()=>{const selected=checks.filter(item=>item.checked);count.textContent=selected.length;summary.textContent=selected.length?selected.map(item=>item.dataset.nombre).join(', '):'Seleccionar áreas o unidades'};checks.forEach(item=>item.addEventListener('change',update));update()});</script>
<script>document.addEventListener('DOMContentLoaded',()=>{const select=document.getElementById('institucion'),container=document.getElementById('institucion-nueva-contenedor'),input=document.getElementById('institucion_nueva');if(!select||!container||!input)return;const update=()=>{const nueva=select.value==='__nueva__';container.classList.toggle('hidden',!nueva);input.required=nueva;if(nueva)input.focus()};select.addEventListener('change',update);update()});</script>
