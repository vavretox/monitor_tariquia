<x-app-layout>
<x-slot name="header"><div><p class="text-sm font-semibold text-emerald-700">{{ auth()->check() ? 'Panel ejecutivo' : 'Información territorial' }}</p><h2 class="font-extrabold text-2xl text-slate-900">{{ auth()->check() ? 'Bienvenido, '.Auth::user()->name : 'Monitoreo de Tariquía' }}</h2><p class="text-sm text-slate-500 mt-1">Seguimiento territorial de Tariquía</p></div></x-slot>
<div class="py-6"><div class="max-w-7xl mx-auto px-4 space-y-6">
<section class="ui-card overflow-hidden w-full">
 <div class="map-toolbar">
  <div class="map-heading"><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Explorador territorial</p><h3 class="font-bold text-xl text-slate-900">Mapa territorial interactivo</h3><p class="text-sm text-slate-500">Explore comunidades y filtre sus demandas por territorio, tipo y situación.</p></div>
  <div class="map-search-wrap"><span aria-hidden="true">⌕</span><input id="map-search" type="search" placeholder="Buscar comunidad, demanda o tipo..." aria-label="Buscar comunidad"></div>
 </div>
 <div class="map-filters" aria-label="Filtros principales del mapa">
  <label><span>Territorio</span><select id="territory-filter"><option value="">Todos los territorios</option>@foreach($porTerritorio as $territorio=>$total)<option value="{{ $territorio }}">{{ $territorio }} ({{ $total }})</option>@endforeach</select></label>
  <label><span>Tipo de demanda</span><select id="type-filter"><option value="">Todos los tipos</option>@foreach($tiposDemanda as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>@endforeach</select></label>
  <button id="advanced-filter-toggle" type="button" class="map-filter-button" aria-expanded="false" aria-controls="advanced-map-filters"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10M14 4v6M6 14v6"/></svg><span>Más filtros</span><b id="advanced-filter-count" class="hidden">0</b></button>
  <button id="clear-map-filters" type="button" class="map-clear-button">Limpiar</button>
 </div>
 <div id="advanced-map-filters" class="map-advanced-filters hidden" aria-label="Filtros avanzados">
  <label><span>Estado de demanda</span><select id="status-filter"><option value="">Todos los estados</option>@foreach(['identificada','priorizada','en_gestion','en_ejecucion','resuelta','postergada'] as $estado)<option value="{{ $estado }}">{{ ucfirst(str_replace('_',' ',$estado)) }}</option>@endforeach</select></label>
  <label><span>Prioridad</span><select id="priority-filter"><option value="">Todas las prioridades</option>@foreach(['alta','media','baja'] as $prioridad)<option value="{{ $prioridad }}">{{ ucfirst($prioridad) }}</option>@endforeach</select></label>
  <label class="map-check"><input id="overdue-filter" type="checkbox"><span>Solo comunidades con acciones vencidas</span></label>
 </div>
 <div class="map-meta"><div id="active-map-filters" class="map-chips" aria-live="polite"></div><p id="map-results" class="map-results"></p></div>
 <div class="map-stage">
  <div id="map" aria-label="Mapa de comunidades de Tariquía"></div>
  <div class="map-legend">
   <button id="legend-toggle" type="button" aria-expanded="false" aria-controls="legend-content"><span class="legend-help">?</span><b>Leyenda</b><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg></button>
   <div id="legend-content" class="legend-content hidden">
    <p class="legend-title">Prioridad de demandas</p>
    <span><i class="legend-dot priority-alta"></i>Alta</span><span><i class="legend-dot priority-media"></i>Media</span><span><i class="legend-dot priority-baja"></i>Baja</span><span><i class="legend-dot priority-none"></i>Sin demandas registradas</span>
    <p class="legend-title">Símbolos</p>
    <span><i class="legend-pin"></i>Comunidad; el dibujo interior indica el tipo</span><span><i class="legend-stack"><i></i><i></i><i></i></i>Varias comunidades próximas</span>
   </div>
  </div>
  <div id="map-empty" class="map-empty hidden"><b>Sin coincidencias</b><span>Pruebe cambiando o limpiando los filtros.</span></div>
 </div></section>

<div class="kpi-grid">@foreach([['Comunidades',$stats['comunidades'],'emerald'],['Demandas',$stats['demandas'],'emerald'],['Acciones',$stats['acciones'],'blue'],['Responsables',$stats['responsables'],'violet']] as [$label,$value,$color])<div class="ui-card ui-card-hover p-5 border-t-4 border-{{ $color }}-500"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="text-3xl font-extrabold text-slate-900 mt-1">{{ $value }}</p></div>@endforeach</div>

@auth<section class="ui-card overflow-hidden"><div class="flex flex-wrap items-center justify-between gap-3 border-b p-5"><div><h3 class="font-bold text-lg text-red-800">Responsables con acciones retrasadas</h3><p class="text-sm text-slate-500">Acciones que superaron su fecha límite y aún no están completadas.</p></div><a href="{{ route('reportes.alertas') }}" class="ui-btn-danger">Ver detalle</a></div><div class="divide-y">@forelse($incumplimientosPorResponsable as $incumplimiento)<div class="grid gap-2 p-4 text-sm md:grid-cols-4"><b>{{ $incumplimiento['responsable'] }}</b><span>{{ $incumplimiento['accion'] }}</span><span>{{ ucfirst(str_replace('_',' ',$incumplimiento['estado'])) }}</span><strong class="text-red-700">{{ $incumplimiento['dias'] }} día(s) de retraso</strong></div>@empty<p class="p-5 text-sm text-emerald-700">No existen acciones vencidas pendientes.</p>@endforelse</div></section>@endauth

@auth<section class="ui-card p-5 w-full"><div class="flex justify-between items-center"><div><h3 class="font-bold text-lg">Próximos vencimientos</h3><p class="text-sm text-slate-500">Acciones que requieren atención prioritaria</p></div><span class="ui-badge bg-red-50 text-red-700">{{ $stats['bloqueadas'] }} bloqueadas</span></div><div class="mt-4 upcoming-grid">@forelse($proximas as $a)<a href="{{ route('demandas.show',$a->demanda_id) }}" class="block p-4 border rounded-xl hover:bg-slate-50 transition"><div class="flex justify-between gap-2"><b class="text-sm">{{ $a->titulo }}</b><span class="text-xs text-slate-500 whitespace-nowrap">{{ $a->fecha_limite->format('d/m/Y') }}</span></div><p class="text-xs text-slate-500 mt-2">{{ $a->demanda?->comunidades?->pluck('nombre')->join(', ') }} · {{ $a->responsable?->nombre_completo??'Sin responsable' }}</p></a>@empty<p class="text-slate-500 text-sm">No hay fechas límite registradas.</p>@endforelse</div></section>@endauth

@auth
<section class="ui-card p-5"><div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-bold text-lg">Estado del monitoreo</h3><p class="text-sm text-slate-500">Situaciones que requieren revisión</p></div><a href="{{ route('reportes.alertas') }}" class="ui-btn-primary">Revisar alertas</a></div><div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-red-50 p-4"><p class="text-sm font-semibold text-red-700">Acciones vencidas</p><p class="text-3xl font-extrabold text-red-800">{{ $stats['vencidas'] }}</p></div><div class="rounded-xl bg-amber-50 p-4"><p class="text-sm font-semibold text-amber-700">Demandas sin acciones</p><p class="text-3xl font-extrabold text-amber-800">{{ $stats['sin_acciones'] }}</p></div><div class="rounded-xl bg-blue-50 p-4"><p class="text-sm font-semibold text-blue-700">Sin actualización en 30 días</p><p class="text-3xl font-extrabold text-blue-800">{{ $stats['sin_actualizar'] }}</p></div></div></section>
@endauth
<div class="grid lg:grid-cols-3 gap-5">
 <section class="ui-card p-5 lg:col-span-2"><div class="flex justify-between items-center"><div><h3 class="font-bold text-lg">Acciones por estado</h3><p class="text-sm text-slate-500">Distribución operativa del seguimiento</p></div><span class="ui-badge bg-emerald-100 text-emerald-700">{{ $stats['cumplidas'] }} completadas</span></div><div class="chart-box"><canvas id="estadoChart"></canvas></div></section>
 <section class="ui-card p-5"><h3 class="font-bold text-lg">Sectores de intervención</h3><p class="text-sm text-slate-500">Compromisos por sector temático</p><div class="chart-box"><canvas id="areaChart"></canvas></div></section>
</div>
</div></div>
@guest
<div x-data="{ open: {{ $errors->has('email') || $errors->has('password') ? 'true' : 'false' }} }" @open-login-modal.window="open=true" x-show="open" x-cloak class="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title" @keydown.escape.window="open=false">
 <div class="login-backdrop" @click="open=false"></div><div class="login-panel" @click.stop>
  <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Acceso a la plataforma</p><h2 id="login-title" class="mt-1 text-2xl font-extrabold text-slate-900">Iniciar sesión</h2><p class="mt-1 text-sm text-slate-500">Ingrese con las credenciales de su cuenta institucional.</p></div><button type="button" @click="open=false" class="ui-btn-secondary !px-3" aria-label="Cerrar">×</button></div>
  @if(session('status'))<div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
  <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">@csrf
   <div><x-input-label for="login-email" value="Correo electrónico"/><x-text-input id="login-email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"/><x-input-error :messages="$errors->get('email')" class="mt-2"/></div>
   <div><x-input-label for="login-password" value="Contraseña"/><x-text-input id="login-password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password"/><x-input-error :messages="$errors->get('password')" class="mt-2"/></div>
   <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" name="remember"> Recordar sesión</label>
   <div class="flex flex-wrap items-center justify-between gap-3">@if(Route::has('password.request'))<a class="text-sm font-semibold text-emerald-700 hover:underline" href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>@endif<x-primary-button>Ingresar</x-primary-button></div>
  </form>
 </div>
</div>
@endguest
<div id="community-modal" class="map-drawer-shell hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
 <div class="map-drawer-backdrop" data-close-modal></div>
 <aside class="map-drawer">
  <div class="map-drawer-head"><div><p class="text-xs uppercase tracking-wide font-bold text-emerald-700">Ficha territorial</p><h2 id="modal-title" class="font-extrabold text-2xl text-slate-900"></h2><p id="modal-territory" class="text-sm text-slate-500"></p></div><button type="button" data-close-modal class="drawer-close" aria-label="Cerrar ficha">×</button></div>
  <div id="modal-content" class="map-drawer-body"></div>
 </aside>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<style>
.map-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem 1.25rem .9rem}.map-heading{min-width:260px}.map-search-wrap{display:flex;align-items:center;gap:.55rem;width:min(330px,100%);padding-left:.8rem;border:1px solid #cbd5e1;border-radius:.7rem;background:#fff;color:#64748b;box-shadow:0 1px 3px #0f172a0d}.map-search-wrap:focus-within{border-color:#10b981;box-shadow:0 0 0 4px #10b9811f}.map-search-wrap input{width:100%;border:0!important;box-shadow:none!important;background:transparent}.map-filters{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr)) auto auto;align-items:end;gap:.7rem;padding:.85rem 1.25rem;background:#f8fafc;border-block:1px solid #e2e8f0}.map-filters label>span:first-child{display:block;margin-bottom:.3rem;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.045em;color:#64748b}.map-filters select{width:100%;min-height:38px;padding-block:.4rem;font-size:.8rem}.map-check{display:flex!important;align-items:center;gap:.45rem;min-height:38px;padding:.45rem .7rem;border:1px solid #cbd5e1;border-radius:.65rem;background:white;white-space:nowrap}.map-check span{margin:0!important;font-size:.78rem!important;text-transform:none!important;letter-spacing:0!important;color:#334155!important}.map-meta{display:flex;align-items:center;justify-content:space-between;gap:.75rem;min-height:44px;padding:.55rem 1.25rem}.map-chips{display:flex;flex-wrap:wrap;gap:.4rem}.map-chip{display:inline-flex;padding:.25rem .55rem;border-radius:999px;background:#ecfdf5;color:#047857;font-size:.72rem;font-weight:700}.map-results{margin-left:auto;white-space:nowrap;font-size:.78rem;font-weight:700;color:#475569}.map-stage{position:relative;margin:0 1.25rem 1.25rem;border-radius:14px;overflow:hidden;border:1px solid #d8e4df}#map{height:580px;border-radius:14px}.map-legend{position:absolute;z-index:500;left:12px;bottom:12px;display:flex;align-items:center;flex-wrap:wrap;gap:.55rem;padding:.55rem .7rem;border:1px solid #dbe5e1;border-radius:.65rem;background:#fffffff2;box-shadow:0 5px 18px #0f172a24;font-size:.68rem;color:#475569}.map-legend b{color:#1e293b}.map-legend span{display:flex;align-items:center;gap:.25rem}.legend-dot{width:.62rem;height:.62rem;border-radius:50%;display:inline-block}.priority-alta{background:#dc2626}.priority-media{background:#f59e0b}.priority-baja{background:#059669}.priority-none{background:#64748b}.map-empty{position:absolute;z-index:600;inset:50% auto auto 50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;padding:1rem 1.25rem;border-radius:.8rem;background:#fffffff2;box-shadow:0 12px 30px #0f172a2e;color:#475569}.map-marker{position:relative;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:3px solid #fff;border-radius:50%;box-shadow:0 4px 12px #0f172a59;color:#fff;background:var(--marker-color,#64748b)}.map-marker::after{content:"";position:absolute;left:50%;bottom:-7px;transform:translateX(-50%);border-left:6px solid transparent;border-right:6px solid transparent;border-top:8px solid var(--marker-color,#64748b);filter:drop-shadow(0 2px 1px #0f172a38)}.map-marker>span{position:relative;z-index:1;display:flex;align-items:center;justify-content:center}.map-marker svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.map-marker.priority-alta{--marker-color:#dc2626}.map-marker.priority-media{--marker-color:#d97706}.map-marker.priority-baja{--marker-color:#059669}.map-marker.priority-none{--marker-color:#475569}.community-label{padding:.28rem .48rem!important;border:1px solid #a7d7c5!important;border-radius:.45rem!important;background:#fffffff5!important;color:#065f46!important;font-size:.72rem!important;font-weight:800!important;box-shadow:0 2px 8px #0f172a24!important}.community-label:before{display:none}.cluster-tooltip{min-width:150px;line-height:1.45}.cluster-tooltip b{display:block;margin-bottom:.25rem;color:#064e3b}.cluster-hint{display:block;margin-top:.35rem;padding-top:.3rem;border-top:1px solid #d1fae5;color:#64748b;font-weight:600}.marker-cluster{background:#a7f3d099!important;border:1px solid #ffffffcc;border-radius:50%;box-shadow:0 5px 16px #064e3b4d}.marker-cluster div{width:34px!important;height:34px!important;margin:4px!important;display:flex;align-items:center;justify-content:center;border:2px solid #fff;border-radius:50%;background:linear-gradient(145deg,#10b981,#047857)!important}.cluster-symbol{position:relative;width:20px!important;height:20px!important;margin:0!important;border:0!important;background:transparent!important}.cluster-symbol i{position:absolute;width:10px;height:10px;border:2px solid #fff;border-radius:4px;background:#34d399}.cluster-symbol i:nth-child(1){left:1px;top:5px}.cluster-symbol i:nth-child(2){right:1px;top:5px}.cluster-symbol i:nth-child(3){left:5px;top:1px;background:#059669}.map-drawer-shell{position:fixed;inset:0;z-index:2100}.map-drawer-shell.hidden{display:none}.map-drawer-backdrop{position:absolute;inset:0;background:#0214108c;backdrop-filter:blur(3px)}.map-drawer{position:absolute;right:0;top:0;height:100%;width:min(620px,94vw);display:flex;flex-direction:column;background:#f8fafc;box-shadow:-20px 0 60px #0004;animation:drawerIn .28s ease-out}.map-drawer-head{display:flex;justify-content:space-between;gap:1rem;padding:1.25rem 1.4rem;background:#fff;border-bottom:1px solid #e2e8f0}.drawer-close{flex:0 0 auto;width:40px;height:40px;border:1px solid #cbd5e1;border-radius:.65rem;background:#fff;color:#475569;font-size:1.6rem;line-height:1}.drawer-close:hover{background:#f1f5f9}.map-drawer-body{padding:1.25rem;overflow-y:auto}.territory-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;margin-bottom:1rem}.summary-item{padding:.75rem;border:1px solid #dce7e2;border-radius:.75rem;background:#fff}.summary-item b{display:block;font-size:1.35rem;color:#0f172a}.summary-item span{font-size:.68rem;color:#64748b}.demand-card{margin-bottom:.8rem;padding:1rem;border:1px solid #dce7e2;border-radius:.85rem;background:#fff;box-shadow:0 2px 8px #0f172a0d}.demand-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}.demand-badges{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.55rem}.demand-description{display:-webkit-box;overflow:hidden;-webkit-line-clamp:3;-webkit-box-orient:vertical;margin-top:.45rem;color:#475569;font-size:.82rem;line-height:1.5}.action-list{margin-top:.8rem;border-top:1px solid #e2e8f0}.action-row{padding:.7rem 0;border-bottom:1px solid #eef2f1}.action-row:last-child{border-bottom:0;padding-bottom:0}.action-head{display:flex;justify-content:space-between;gap:.6rem;font-size:.78rem}.progress-track{height:6px;margin-top:.45rem;overflow:hidden;border-radius:999px;background:#e2e8f0}.progress-bar{height:100%;border-radius:inherit;background:#10b981}.drawer-link{display:inline-flex;margin-top:.7rem;color:#047857;font-size:.78rem;font-weight:800}.badge-priority-alta{background:#fee2e2;color:#b91c1c}.badge-priority-media{background:#fef3c7;color:#b45309}.badge-priority-baja{background:#d1fae5;color:#047857}.badge-type{background:#e0f2fe;color:#0369a1}.badge-status{background:#f1f5f9;color:#475569}.badge-overdue{background:#fee2e2;color:#b91c1c}@keyframes drawerIn{from{opacity:.4;transform:translateX(35px)}to{opacity:1;transform:none}}
.upcoming-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}.kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem}.chart-box{height:290px;margin-top:1rem}.login-modal{position:fixed;inset:0;z-index:2000;display:flex;justify-content:center;align-items:flex-start;overflow-y:auto;padding:2rem 1rem}.login-backdrop{position:fixed;inset:0;background:rgba(2,20,17,.68);backdrop-filter:blur(4px)}.login-panel{position:relative;flex:none;width:min(460px,96vw);max-height:calc(100dvh - 4rem);overflow-y:auto;background:#fff;border-radius:18px;padding:1.5rem;box-shadow:0 30px 80px #0005;animation:pageEnter .28s ease-out}
@media(max-width:1100px){.upcoming-grid{grid-template-columns:repeat(2,1fr)}.kpi-grid{grid-template-columns:repeat(3,1fr)}.map-filters{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){.map-toolbar{align-items:stretch;flex-direction:column}.map-search-wrap{width:100%}.map-filters{grid-template-columns:1fr 1fr;padding:.75rem}.map-check{white-space:normal}.map-meta{align-items:flex-start;flex-direction:column;padding-inline:.75rem}.map-results{margin-left:0}.map-stage{margin:0 .75rem .75rem}#map{height:500px}.map-legend{right:8px;left:8px;bottom:8px}.territory-summary{grid-template-columns:1fr 1fr}.map-drawer{top:auto;bottom:0;width:100%;height:min(82dvh,720px);border-radius:1rem 1rem 0 0}.upcoming-grid{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.login-modal{padding:.5rem}.login-panel{width:100%;max-height:calc(100dvh - 1rem);border-radius:14px}.chart-box{height:250px}}
/* Mapa: controles progresivos, leyenda y selección */
.map-filters{grid-template-columns:minmax(190px,1fr) minmax(220px,1fr) auto auto;align-items:end}
.map-filter-button,.map-clear-button{min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:.45rem;padding:.5rem .8rem;border:1px solid #cbd5e1;border-radius:.65rem;background:#fff;color:#334155;font-size:.8rem;font-weight:800;transition:.2s}
.map-filter-button:hover,.map-clear-button:hover{border-color:#94a3b8;background:#fff}.map-filter-button svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round}.map-filter-button b{display:inline-flex;min-width:19px;height:19px;align-items:center;justify-content:center;border-radius:999px;background:#047857;color:#fff;font-size:.65rem}.map-filter-button.is-active{border-color:#10b981;background:#ecfdf5;color:#047857}.map-clear-button{color:#64748b}
.map-advanced-filters{display:grid;grid-template-columns:minmax(180px,1fr) minmax(160px,.7fr) auto;align-items:end;gap:.7rem;padding:.8rem 1.25rem;border-bottom:1px solid #dce7e2;background:#f0fdf7}.map-advanced-filters.hidden{display:none}.map-advanced-filters label>span:first-child{display:block;margin-bottom:.3rem;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.045em;color:#64748b}.map-advanced-filters select{width:100%;min-height:38px;padding-block:.4rem;font-size:.8rem}
.map-legend{left:12px;bottom:12px;display:block;padding:0;overflow:hidden;font-size:.72rem}.map-legend>button{display:flex;align-items:center;gap:.4rem;width:100%;padding:.55rem .7rem;background:#fff;border:0;color:#334155}.map-legend>button svg{width:16px;height:16px;margin-left:auto;fill:none;stroke:currentColor;stroke-width:2;transition:transform .2s}.map-legend>button[aria-expanded=true] svg{transform:rotate(180deg)}.legend-help{width:19px;height:19px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:#047857;color:#fff;font-size:.7rem;font-weight:900}.legend-content{width:250px;display:grid;grid-template-columns:1fr 1fr;gap:.45rem .65rem;padding:.65rem .75rem .75rem;border-top:1px solid #dce7e2}.legend-content.hidden{display:none}.legend-content .legend-title{grid-column:1/-1;margin-top:.15rem;color:#0f172a;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em}.legend-content>span{display:flex;align-items:center;gap:.35rem}.legend-content>span:nth-last-child(-n+2){grid-column:1/-1}.legend-pin{position:relative;width:16px;height:16px;border:2px solid #fff;border-radius:50%;background:#0d9488;box-shadow:0 0 0 1px #0d9488}.legend-pin::after{content:"";position:absolute;left:50%;bottom:-5px;transform:translateX(-50%);border-left:3px solid transparent;border-right:3px solid transparent;border-top:5px solid #0d9488}.legend-stack{position:relative;width:20px;height:16px}.legend-stack i{position:absolute;width:10px;height:10px;border:2px solid #fff;border-radius:3px;background:#10b981;box-shadow:0 0 0 1px #047857}.legend-stack i:nth-child(1){left:0;top:5px}.legend-stack i:nth-child(2){right:0;top:5px}.legend-stack i:nth-child(3){left:5px;top:0}
.priority-none{background:#0d9488}.map-marker.priority-none{--marker-color:#0d9488}
.leaflet-marker-icon{transition:opacity .2s,filter .2s}.leaflet-marker-icon.is-dimmed{opacity:.38;filter:saturate(.35)}.leaflet-marker-icon.is-selected{opacity:1!important;filter:none!important;z-index:1000!important}.leaflet-marker-icon.is-selected .map-marker{animation:selectedPulse 1.8s ease-in-out infinite;box-shadow:0 0 0 7px #10b98138,0 7px 18px #064e3b73}
@keyframes selectedPulse{0%,100%{box-shadow:0 0 0 6px #10b98130,0 7px 18px #064e3b73}50%{box-shadow:0 0 0 11px #10b98112,0 7px 18px #064e3b73}}
@media(max-width:700px){.map-filters{grid-template-columns:1fr 1fr}.map-filter-button,.map-clear-button{width:100%}.map-advanced-filters{grid-template-columns:1fr;padding:.75rem}.map-legend{right:auto;max-width:calc(100% - 16px)}.legend-content{width:min(250px,calc(100vw - 48px))}}</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const palette=['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#14b8a6','#64748b'];
new Chart(document.getElementById('estadoChart'),{type:'doughnut',data:{labels:@json($porEstado->keys()->map(fn($x)=>ucfirst(str_replace('_',' ',$x)))),datasets:[{data:@json($porEstado->values()),backgroundColor:palette,borderWidth:3,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('areaChart'),{type:'bar',data:{labels:@json($porArea->keys()),datasets:[{label:'Demandas',data:@json($porArea->values()),backgroundColor:'#059669',borderRadius:7}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{grid:{display:false}}}}});

const base=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'});
const sat=L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19,attribution:'© Esri'});
const relief=L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',{maxZoom:17,attribution:'© OpenTopoMap'});
const map=L.map('map',{layers:[base],zoomControl:true}).setView([-22.02,-64.32],10);
L.control.layers({'Calles':base,'Satélite':sat,'Relieve':relief},null,{collapsed:true}).addTo(map);

const communityModal=document.getElementById('community-modal');
document.body.appendChild(communityModal);
const loginModal=document.querySelector('.login-modal');
if(loginModal)document.body.appendChild(loginModal);
const isPublic=@json($publico);
const communities=@json($mapaComunidades);
const controls={search:document.getElementById('map-search'),territory:document.getElementById('territory-filter'),type:document.getElementById('type-filter'),status:document.getElementById('status-filter'),priority:document.getElementById('priority-filter'),overdue:document.getElementById('overdue-filter')};
const advancedToggle=document.getElementById('advanced-filter-toggle'),advancedPanel=document.getElementById('advanced-map-filters'),advancedCount=document.getElementById('advanced-filter-count');
const legendToggle=document.getElementById('legend-toggle'),legendContent=document.getElementById('legend-content');
const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const label=value=>String(value||'Sin estado').replaceAll('_',' ').replace(/^./,char=>char.toUpperCase());
const normalized=value=>String(value||'').normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase();
const mapSvg=paths=>'<svg viewBox="0 0 24 24" aria-hidden="true">'+paths+'</svg>';
const typeIcon=type=>{const value=normalized(type);
 if(value.includes('salud'))return mapSvg('<path d="M12 5v14M5 12h14"/>');
 if(value.includes('agua'))return mapSvg('<path d="M12 3s6 6.2 6 11a6 6 0 0 1-12 0c0-4.8 6-11 6-11Z"/>');
 if(value.includes('camino')||value.includes('acces'))return mapSvg('<path d="M8 21 11 3M16 21 13 3M12 7v3M12 14v3"/>');
 if(value.includes('educ'))return mapSvg('<path d="m3 10 9-5 9 5-9 5-9-5Z"/><path d="M7 13v4c3 2 7 2 10 0v-4"/>');
 if(value.includes('produ'))return mapSvg('<path d="M5 20c8 0 14-5 14-14C10 6 5 11 5 20Z"/><path d="M5 20c3-5 6-8 11-11"/>');
 if(value.includes('energ'))return mapSvg('<path d="m13 2-7 12h6l-1 8 7-12h-6l1-8Z"/>');
 if(value.includes('ambiente'))return mapSvg('<path d="M12 21V9M6 13c-2-5 2-9 6-10 1 5-1 9-6 10ZM18 16c2-4-1-7-5-8-1 4 1 7 5 8Z"/>');
 return mapSvg('<path d="M12 21s7-6 7-12A7 7 0 0 0 5 9c0 6 7 12 7 12Z"/><circle cx="12" cy="9" r="2"/>');
};
const demandPriority=demands=>demands.some(d=>d.prioridad==='alta')?'alta':demands.some(d=>d.prioridad==='media')?'media':demands.length?'baja':'none';
const markerIcon=demands=>{const main=demands[0],priority=demandPriority(demands);return L.divIcon({className:'',html:'<div class="map-marker priority-'+priority+'"><span>'+typeIcon(main?.tipo)+'</span></div>',iconSize:[34,42],iconAnchor:[17,41],tooltipAnchor:[0,-34]})};
const clusterOptions={showCoverageOnHover:false,maxClusterRadius:42,spiderfyOnMaxZoom:true,iconCreateFunction:()=>L.divIcon({html:'<div><span class="cluster-symbol" aria-hidden="true"><i></i><i></i><i></i></span></div>',className:'marker-cluster marker-cluster-small',iconSize:L.point(44,44)})};
const layer=typeof L.markerClusterGroup==='function'?L.markerClusterGroup(clusterOptions):L.layerGroup();
map.addLayer(layer);
if(typeof L.markerClusterGroup==='function'){
 layer.on('clustermouseover',event=>{
  const names=event.layer.getAllChildMarkers().map(marker=>marker.communityName).filter(Boolean).sort((a,b)=>a.localeCompare(b,'es'));
  event.layer.bindTooltip('<b>Comunidades agrupadas</b><br>'+names.map(esc).join('<br>')+'<small class="cluster-hint">Pulse para acercar</small>',{direction:'top',className:'community-label cluster-tooltip',sticky:true}).openTooltip();
 });
 layer.on('clustermouseout',event=>event.layer.closeTooltip());
}
const markers=communities.filter(c=>Number.isFinite(Number(c.latitud))&&Number.isFinite(Number(c.longitud))).map(c=>{
 const marker=L.marker([Number(c.latitud),Number(c.longitud)],{icon:markerIcon(c.demandas||[]),title:c.nombre});
 marker.communityName=c.nombre;
 marker.bindTooltip(esc(c.nombre),{direction:'top',offset:[0,-23],className:'community-label'});
 marker.on('click',()=>openCommunity(c.id));
 return {marker,c,demands:c.demandas||[]};
});

function matchingDemands(community){
 const type=String(controls.type.value||''),status=normalized(controls.status.value),priority=normalized(controls.priority.value),overdue=controls.overdue.checked;
 return (community.demandas||[]).filter(d=>(!type||String(d.tipo_id??'')===type)&&(!status||normalized(d.estado)===status)&&(!priority||normalized(d.prioridad)===priority)&&(!overdue||(d.acciones||[]).some(a=>Boolean(a.vencida))));
}
function activeDemandFilters(){return Boolean(controls.type.value||controls.status.value||controls.priority.value||controls.overdue.checked)}
function selectedText(select){return select.options[select.selectedIndex]?.text||''}
function displayState(community){
 const query=normalized(controls.search.value.trim());
 const territoryMatches=!controls.territory.value||normalized(community.territorio)===normalized(controls.territory.value);
 const communityMatches=!query||normalized([community.nombre,community.territorio].join(' ')).includes(query);
 let demands=matchingDemands(community);
 const demandMatches=d=>normalized([d.titulo,d.descripcion,d.tipo,d.estado,d.prioridad].join(' ')).includes(query);
 const matchingByText=query?demands.filter(demandMatches):demands;
 if(query&&!communityMatches)demands=matchingByText;
 const queryMatches=communityMatches||matchingByText.length>0;
 const demandFilterMatches=!activeDemandFilters()||demands.length>0;
 return {visible:territoryMatches&&queryMatches&&demandFilterMatches,demands};
}
function updateFilterMeta(visibleCommunities,visibleDemands){
 const chips=[];
 if(controls.territory.value)chips.push(selectedText(controls.territory));
 if(controls.type.value)chips.push(selectedText(controls.type));
 if(controls.status.value)chips.push(selectedText(controls.status));
 if(controls.priority.value)chips.push('Prioridad '+selectedText(controls.priority).toLowerCase());
 if(controls.overdue.checked)chips.push('Con acciones vencidas');
 if(controls.search.value.trim())chips.push('Búsqueda: '+controls.search.value.trim());
 document.getElementById('active-map-filters').innerHTML=chips.map(chip=>'<span class="map-chip">'+esc(chip)+'</span>').join('');
 document.getElementById('map-results').textContent=visibleCommunities+' de '+markers.length+' comunidades · '+visibleDemands+' demandas visibles';
 const advancedTotal=[controls.status.value,controls.priority.value].filter(Boolean).length+(controls.overdue.checked?1:0);
 advancedCount.textContent=advancedTotal;
 advancedCount.classList.toggle('hidden',advancedTotal===0);
 advancedToggle.classList.toggle('is-active',advancedTotal>0);
 document.getElementById('map-empty').classList.toggle('hidden',visibleCommunities!==0);
}
function filtersAreActive(){return Boolean(controls.search.value.trim()||controls.territory.value||activeDemandFilters())}
function filterMap(){
 layer.clearLayers();
 const points=[];
 let visibleDemands=0;
 markers.forEach(item=>{
  const state=displayState(item.c);
  item.demands=state.demands;
  if(state.visible){
   visibleDemands+=state.demands.length;
   item.marker.setIcon(markerIcon(state.demands));
   item.marker.setTooltipContent('<b>'+esc(item.c.nombre)+'</b><br>'+(state.demands.length?state.demands.length+' demanda(s)':'Sin demandas registradas'));
   layer.addLayer(item.marker);
   points.push(item.marker.getLatLng());
  }
 });
 updateFilterMeta(points.length,visibleDemands);
 if(points.length&&filtersAreActive())map.fitBounds(L.latLngBounds(points),{padding:[55,55],maxZoom:13});
 if(!communityModal.classList.contains('hidden')&&communityModal.dataset.communityId)openCommunity(Number(communityModal.dataset.communityId));
}
let searchTimer;
controls.search.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(filterMap,160)});
[controls.territory,controls.type,controls.status,controls.priority,controls.overdue].forEach(control=>control.addEventListener('change',filterMap));
advancedToggle.addEventListener('click',()=>{const opening=advancedPanel.classList.contains('hidden');advancedPanel.classList.toggle('hidden',!opening);advancedToggle.setAttribute('aria-expanded',String(opening))});
legendToggle.addEventListener('click',()=>{const opening=legendContent.classList.contains('hidden');legendContent.classList.toggle('hidden',!opening);legendToggle.setAttribute('aria-expanded',String(opening))});
document.getElementById('clear-map-filters').addEventListener('click',()=>{controls.search.value='';controls.territory.value='';controls.type.value='';controls.status.value='';controls.priority.value='';controls.overdue.checked=false;filterMap();map.setView([-22.02,-64.32],10)});
filterMap();
function clearMapSelection(){
 markers.forEach(item=>{const element=item.marker.getElement();element?.classList.remove('is-selected','is-dimmed');item.marker.setZIndexOffset(0)});
}
function highlightCommunity(item){
 markers.forEach(entry=>{const element=entry.marker.getElement();element?.classList.toggle('is-selected',entry===item);element?.classList.toggle('is-dimmed',entry!==item);entry.marker.setZIndexOffset(entry===item?1000:0)});
 const target=item.marker.getLatLng(),zoom=Math.max(map.getZoom(),12);
 map.flyTo(target,zoom,{animate:true,duration:.55});
 if(window.innerWidth>700)setTimeout(()=>map.panBy([150,0],{animate:true,duration:.35}),600);
}
function openCommunity(id){
 const item=markers.find(entry=>entry.c.id===id),c=item?.c;
 if(!c)return;
 highlightCommunity(item);
 const demands=item.demands??(c.demandas||[]);
 const actions=demands.flatMap(d=>d.acciones||[]);
 const overdue=actions.filter(a=>a.vencida).length;
 const completed=actions.filter(a=>a.estado==='completada').length;
 document.getElementById('modal-title').textContent=c.nombre;
 document.getElementById('modal-territory').textContent=(c.territorio||'Territorio sin definir')+' · '+c.latitud+', '+c.longitud;
 const summary='<div class="territory-summary"><div class="summary-item"><b>'+demands.length+'</b><span>Demandas</span></div><div class="summary-item"><b>'+actions.length+'</b><span>Acciones</span></div><div class="summary-item"><b>'+completed+'</b><span>Completadas</span></div><div class="summary-item"><b>'+overdue+'</b><span>Vencidas</span></div></div>';
 const cards=demands.map(d=>{
  const actionRows=(d.acciones||[]).map(a=>'<div class="action-row"><div class="action-head"><span><b>'+esc(a.titulo)+'</b><small class="block text-slate-500">'+esc(label(a.estado))+(a.fecha_limite?' · vence '+esc(a.fecha_limite):'')+'</small></span>'+(a.vencida?'<span class="ui-badge badge-overdue">Vencida</span>':'<b>'+a.avance+'%</b>')+'</div><div class="progress-track"><div class="progress-bar" style="width:'+Math.max(0,Math.min(100,a.avance))+'%"></div></div>'+(!isPublic?'<small class="mt-1 block text-slate-500">Responsables: '+esc((a.responsables||[]).map(r=>r.nombre_completo).join(', ')||'Sin responsables')+'</small>':'')+'</div>').join('');
  return '<article class="demand-card"><div class="demand-badges"><span class="ui-badge badge-type">'+esc(d.tipo)+'</span><span class="ui-badge badge-priority-'+esc(d.prioridad||'media')+'">Prioridad '+esc(d.prioridad||'media')+'</span><span class="ui-badge badge-status">'+esc(label(d.estado))+'</span></div><div class="demand-card-head"><h3 class="font-bold text-slate-900">'+esc(d.titulo)+'</h3></div><p class="demand-description">'+esc(d.descripcion||'Sin descripción registrada.')+'</p>'+(actionRows?'<div class="action-list">'+actionRows+'</div>':'<p class="mt-3 text-sm text-slate-500">Sin acciones registradas.</p>')+(d.url?'<a class="drawer-link" href="'+esc(d.url)+'">Ver demanda completa →</a>':'')+'</article>';
 }).join('');
 const communityLink=c.url?'<a class="ui-btn-primary w-full mt-2" href="'+esc(c.url)+'">Ver ficha completa de la comunidad</a>':'';
 document.getElementById('modal-content').innerHTML=summary+(cards||'<div class="rounded-xl border bg-white p-5 text-center text-slate-500">No hay demandas que coincidan con los filtros activos.</div>')+communityLink;
 communityModal.dataset.communityId=String(c.id);
 communityModal.classList.remove('hidden');
 document.body.style.overflow='hidden';
}
function closeCommunity(){communityModal.classList.add('hidden');delete communityModal.dataset.communityId;document.body.style.overflow='';clearMapSelection()}
communityModal.querySelectorAll('[data-close-modal]').forEach(element=>element.addEventListener('click',closeCommunity));
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!communityModal.classList.contains('hidden'))closeCommunity()});
</script>
@endpush
</x-app-layout>