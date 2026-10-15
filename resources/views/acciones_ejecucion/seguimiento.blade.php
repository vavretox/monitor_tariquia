<x-app-layout>
 <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-bold">Seguimiento de la acción</h2><a href="{{ route('acciones.index') }}" class="ui-btn-secondary ui-icon-back">Volver a Acciones</a></div></x-slot>
 <div class="followup-page mx-auto max-w-7xl space-y-5 px-4 py-6">
 @if(session('success'))<div class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div>@endif
 <section class="ui-card followup-summary p-6"><div class="flex flex-wrap justify-between gap-3"><div><p class="text-xs font-bold uppercase text-slate-500">Resumen actual</p><h3 class="text-lg font-bold">{{ $accion->titulo }}</h3></div><div><span class="ui-badge bg-slate-100">{{ ucfirst(str_replace('_',' ',$accion->estado)) }}</span> <span class="ui-badge bg-emerald-100 text-emerald-800">{{ $accion->avance_seguimiento }}%</span></div></div><div class="mt-4 grid gap-4 md:grid-cols-3"><div><b class="block text-xs uppercase text-slate-500">Responsables</b>{{ $accion->responsables->pluck('nombre_completo')->join(', ') ?: 'Sin responsables' }}</div><div><b class="block text-xs uppercase text-slate-500">Inicio</b>{{ $accion->fecha_inicio?->format('d/m/Y') ?? 'Sin definir' }}</div><div><b class="block text-xs uppercase text-slate-500">Fecha límite</b>{{ $accion->fecha_limite?->format('d/m/Y') ?? 'Sin definir' }}</div></div><div class="followup-progress" role="progressbar" aria-label="Avance de la acción" aria-valuenow="{{ $accion->avance_seguimiento }}" aria-valuemin="0" aria-valuemax="100"><span style="--progress: {{ max(0, min(100, $accion->avance_seguimiento)) }}%"></span></div></section>
 <div class="followup-columns {{ auth()->user()->can('acciones.edit') ? '' : 'followup-readonly' }}">
 <section class="ui-card followup-history p-6"><h3 class="text-lg font-bold">Bitácoras y observaciones</h3><p class="mb-6 text-sm text-slate-500">Trabajos registrados, observaciones al responsable y respuestas ordenados por fecha.</p>
 @forelse($eventos as $evento)
 <article class="followup-event followup-event-{{ $evento['tipo'] }}" style="--event-delay: {{ min($loop->index, 7) * 55 }}ms"><span class="followup-dot" aria-hidden="true"></span><div class="followup-event-content"><h4 class="font-bold">{{ $evento['titulo'] }} @if($evento['avance']!==null)<span class="ui-badge bg-emerald-100">{{ $evento['avance'] }}%</span>@endif</h4><p class="text-xs text-slate-500">{{ $evento['fecha']?->format('d/m/Y · H:i') }} · {{ $evento['usuario'] }}</p><p class="mt-2 whitespace-pre-line text-sm">{{ $evento['descripcion'] }}</p></div></article>
 @empty
 <div class="followup-empty"><p class="font-semibold">Sin registros de seguimiento</p><p class="mt-2 text-sm text-slate-500">Aquí aparecerán las bitácoras, las observaciones al responsable y sus respuestas.</p></div>
 @endforelse
 </section>
 @can('acciones.edit')
 <section class="ui-card followup-compose p-5"><h3 class="font-bold">{{ auth()->user()->hasRole('responsable') ? 'Responder observación' : 'Observación al responsable' }}</h3><p class="mt-1 text-xs text-slate-500">Quedará en el historial y se notificará a los responsables con cuenta vinculada. Las respuestas también notifican al autor.</p>
 <form id="followup-message-form" method="POST" action="{{ route('acciones.observaciones.store', $accion) }}" class="mt-5 space-y-4">@csrf
 @if($accion->observaciones->isNotEmpty())
 <div><label for="parent_id" class="block text-sm font-medium">Responder a</label><select id="parent_id" name="parent_id" class="w-full" @required(auth()->user()->hasRole('responsable'))><option value="">{{ auth()->user()->hasRole('responsable') ? 'Seleccione una observación' : 'Nueva observación' }}</option>@foreach($accion->observaciones as $observacion)<option value="{{ $observacion->id }}" @selected((string)old('parent_id')===(string)$observacion->id)>#{{ $observacion->id }} · {{ Str::limit($observacion->mensaje, 60) }}</option>@endforeach</select></div>
 @elseif(auth()->user()->hasRole('responsable'))
 <p class="text-sm text-slate-500">No hay observaciones pendientes para responder.</p>
 @endif
 <div><label for="mensaje" class="sr-only">Mensaje</label><textarea id="mensaje" name="mensaje" rows="6" maxlength="5000" required class="w-full" placeholder="Indique la observación, corrección o respuesta…">{{ old('mensaje') }}</textarea><p class="followup-counter"><span id="followup-message-count">0</span> / 5000 caracteres</p></div><button class="ui-btn-primary w-full justify-center ui-icon-send" id="followup-send" @disabled(auth()->user()->hasRole('responsable') && $accion->observaciones->isEmpty())><span>{{ auth()->user()->hasRole('responsable') ? 'Enviar respuesta' : 'Enviar observación' }}</span></button></form></section>
 @endcan
 </div></div>
@push('styles')
<style>
.followup-page{max-width:1600px}
.followup-columns{display:grid;grid-template-columns:minmax(0,2.08fr) minmax(320px,1fr);gap:24px;align-items:stretch}
.followup-columns.followup-readonly{grid-template-columns:minmax(0,1fr)}
.followup-columns>section{min-width:0}
.followup-summary,.followup-history,.followup-compose{animation:followupEnter .45s ease both}
.followup-summary{overflow:hidden}
.followup-history{animation-delay:70ms;padding:24px 20px;min-height:340px}
.followup-compose{animation-delay:130ms;padding:24px 20px;display:flex;flex-direction:column;border-color:#f4d58b;background:linear-gradient(145deg,#fffbedb3,#f3faf2b3)!important}
.followup-compose h3,.followup-history h3{font-size:18px;letter-spacing:-.025em}
.followup-compose select,.followup-compose textarea{margin-top:6px;border-radius:12px;transition:border-color .2s,box-shadow .2s;background:#fff}
.followup-compose select:focus,.followup-compose textarea:focus{border-color:#10b981;box-shadow:0 0 0 3px #10b9811f;outline:none}
.followup-compose form{margin-top:24px;flex:1;display:flex;flex-direction:column}.followup-compose textarea{min-height:160px;resize:vertical}.followup-compose form>button{margin-top:auto}
.followup-compose button{min-height:46px;gap:8px;transition:transform .2s,box-shadow .2s}
.followup-compose button:hover{transform:translateY(-1px);box-shadow:0 6px 18px #05966926}
.followup-compose button:disabled{opacity:.7;cursor:wait;transform:none}
.followup-counter{text-align:right;margin-top:6px;font-size:11px;color:#64748b;opacity:0;transition:opacity .2s}.followup-compose form:focus-within .followup-counter{opacity:1}
.followup-event{--event-color:#059669;position:relative;margin-left:9px;padding:0 0 22px 20px;border-left:1px solid var(--event-color);animation:followupEnter .4s ease both;animation-delay:var(--event-delay)}
.followup-event:last-child{padding-bottom:4px}
.followup-event-observacion{--event-color:#d97706}
.followup-event-creacion{--event-color:#6366f1}
.followup-event-cambio{--event-color:#0284c7}
.followup-dot{position:absolute;left:-6px;top:6px;width:10px;height:10px;border:2px solid #fff;border-radius:50%;background:var(--event-color);box-shadow:0 0 0 4px color-mix(in srgb,var(--event-color) 12%,transparent)}
.followup-event-content{padding:0 0 0 0;border:0;border-radius:8px;background:transparent;transition:border-color .2s,box-shadow .2s}
.followup-event-content:hover{background:#f8fafb80}
.followup-empty{padding:32px 20px;border:1px dashed #d4e3dc;border-radius:12px;text-align:center;background:#f8fbf9}.followup-event h4{font-size:14px}
.followup-event p{overflow-wrap:anywhere}
.followup-progress{height:4px;margin-top:18px;border-radius:999px;background:#e2eee9;overflow:hidden}
.followup-progress span{display:block;height:100%;width:var(--progress);border-radius:inherit;background:linear-gradient(90deg,#059669,#34d399);animation:followupFill .8s ease-out both}
@keyframes followupFill{from{width:0}to{width:var(--progress)}}
@keyframes followupEnter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:800px){.followup-columns{grid-template-columns:minmax(0,1fr);gap:18px}.followup-compose{position:static}.followup-history,.followup-compose{padding:20px}.followup-history{min-height:0}}
@media(prefers-reduced-motion:reduce){.followup-page *{animation:none!important;transition:none!important}}
@media print{.followup-columns{display:block}.followup-compose{display:none}.followup-event{break-inside:avoid}.followup-page *{animation:none!important}}
</style>
@endpush
@push('scripts')
<script>
(()=>{
 const form=document.getElementById('followup-message-form');if(!form)return;
 const message=form.querySelector('#mensaje'),count=document.getElementById('followup-message-count'),send=document.getElementById('followup-send');
 const initialLabel=send.querySelector('span').textContent,initialDisabled=send.disabled;
 const update=()=>{count.textContent=message.value.length};message.addEventListener('input',update);update();
 form.addEventListener('submit',()=>{send.disabled=true;send.querySelector('span').textContent='Enviando…'});
 window.addEventListener('pageshow',()=>{send.disabled=initialDisabled;send.querySelector('span').textContent=initialLabel});
})();
</script>
@endpush
</x-app-layout>
