<x-app-layout>
 <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold text-emerald-700">Control de trabajo por responsable</p><h2 class="text-2xl font-extrabold">Control mensual</h2><p class="text-sm text-slate-500">Una bitácora por responsable y acción activa, con revisión del informe.</p></div><a href="{{ route('acciones.index') }}" class="ui-btn-secondary ui-icon-eye">Ver acciones</a></div></x-slot>
 <div class="monthly-page mx-auto max-w-7xl space-y-5 px-4 py-6">
 @if(session('success'))<div role="status" class="rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div>@endif
 <form method="GET" class="ui-card monthly-filters p-5">
 <div><label for="periodo" class="block text-sm font-semibold">Mes de control</label><input type="month" id="periodo" name="periodo" min="{{ config('monthly_reports.starts_at') }}" max="{{ now(config('monthly_reports.timezone'))->format('Y-m') }}" value="{{ $period }}" required class="mt-1 w-full"></div>
 <div><label for="estado" class="block text-sm font-semibold">Estado del informe</label><select id="estado" name="estado" class="mt-1 w-full"><option value="">Todos</option>@foreach($statuses as $code => $label)<option value="{{ $code }}" @selected(request('estado')===$code)>{{ $label }}</option>@endforeach</select></div>
 <button class="ui-btn-primary ui-icon-calendar">Consultar mes</button>
 </form>
 <div class="monthly-stats">@foreach($statuses as $code => $label)<div class="ui-card p-4"><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-extrabold">{{ $all->filter(fn ($record) => $record->estado_control === $code)->count() }}</p></div>@endforeach</div>
 <div class="ui-card p-4 text-sm text-slate-600"><b>Entrega hasta el último día del mes.</b> El porcentaje puede mantenerse si se explica el motivo. Una entrega tardía queda registrada como fuera de plazo. Las acciones completadas dejan de generar obligaciones en los meses siguientes.</div>
 <div class="ui-table-shell overflow-x-auto"><table class="monthly-table w-full text-sm"><thead><tr><th>Acción / demanda</th><th>Responsable</th><th>Informe</th><th>Entrega</th><th>Control y revisión</th></tr></thead><tbody>
 @forelse($records as $record)
 <tr><td><a href="{{ route('acciones.show', $record->accion) }}" class="font-semibold text-emerald-700 hover:underline">{{ $record->accion->titulo }}</a><small class="mt-1 block text-slate-500">{{ $record->accion->demanda?->titulo }}</small><span class="mt-2 block text-xs text-slate-500">{{ ucfirst(str_replace('_',' ',$record->accion->estado)) }}</span></td>
 <td><b>{{ $record->responsable->nombre_completo }}</b>@unless($record->usuario)<p class="mt-1 text-xs text-amber-700">Sin cuenta vinculada</p>@endunless</td>
 <td><span class="monthly-status monthly-status-{{ $record->estado_control }}">{{ $statuses[$record->estado_control] }}</span>@if($record->fuera_de_plazo)<small class="mt-2 block font-semibold text-amber-700">Presentado fuera de plazo</small>@endif
 @if($record->bitacora)<span class="mt-2 block font-semibold">{{ $record->bitacora->avance }}% de avance</span>@endif
 @if($record->comentario_revision)<p class="mt-2 whitespace-pre-line text-xs text-slate-600">{{ $record->comentario_revision }}</p>@endif
 @if($record->revisado_at)<small class="mt-2 block text-slate-500">{{ $record->revisor?->name ?? 'Sistema' }} · {{ $record->revisado_at->timezone(config('monthly_reports.timezone'))->format('d/m/Y H:i') }}</small>@endif</td>
 <td>{{ $record->presentado_at?->timezone(config('monthly_reports.timezone'))->format('d/m/Y H:i') ?? 'Sin entrega' }}
 @if($record->ultima_entrega_at && $record->presentado_at && ! $record->ultima_entrega_at->equalTo($record->presentado_at))<small class="mt-2 block text-slate-500">Último envío: {{ $record->ultima_entrega_at->timezone(config('monthly_reports.timezone'))->format('d/m/Y H:i') }}</small>@endif
 @if($record->bitacora)<details class="mt-3"><summary class="cursor-pointer font-semibold text-emerald-700">Leer informe</summary><p class="mt-2 whitespace-pre-line text-sm">{{ $record->bitacora->descripcion }}</p><small class="mt-2 block text-slate-500">Trabajo del {{ $record->bitacora->fecha_hora->format('d/m/Y H:i') }} · Registrado por {{ $record->bitacora->usuario?->name ?? 'Usuario no disponible' }}</small></details>@endif</td>
 <td><div class="flex flex-wrap gap-2"><button type="button" class="js-report-progress ui-btn-secondary !px-3 !py-2 ui-icon-chart" data-url="{{ route('acciones.seguimiento', $record->accion) }}">Progreso</button><a href="{{ route('acciones.show', $record->accion) }}#bitacora" class="ui-btn-secondary !px-3 !py-2 ui-icon-book">Bitácora</a></div>
 @can('acciones.edit')
 @if((int)$record->user_id === (int)auth()->id() && $record->estado_control !== 'aceptado')
 <details class="monthly-controls"><summary>Presentar / reenviar informe</summary>
 <form method="POST" action="{{ route('informes-mensuales.presentar', $record) }}" class="mt-3 space-y-3">@csrf<label for="log-{{ $record->id }}" class="block text-xs">Bitácora propia de {{ $period }}</label><select id="log-{{ $record->id }}" name="bitacora_id" required class="w-full"><option value="">Seleccione un registro</option>@foreach($availableLogs->get($record->accion_ejecucion_id, collect()) as $log)<option value="{{ $log->id }}">#{{ $log->id }} · {{ $log->fecha_hora->format('d/m/Y') }} · {{ Str::limit($log->descripcion, 45) }}</option>@endforeach</select><button class="ui-btn-primary">Presentar informe</button></form>
 </details>
 @endif
 @endcan
 @if($canReview && (int)$record->user_id !== (int)auth()->id() && $record->estado_control === 'presentado')
 <details class="monthly-controls"><summary>Revisar informe</summary><form method="POST" action="{{ route('informes-mensuales.revisar', $record) }}" class="mt-3 space-y-3">@csrf
 <label for="review-{{ $record->id }}" class="block text-xs">Decisión</label><select id="review-{{ $record->id }}" name="estado" required class="w-full"><option value="aceptado">Aceptar informe</option><option value="correccion">Solicitar corrección</option></select>
 <label for="note-{{ $record->id }}" class="block text-xs">Observación (obligatoria si solicita corrección)</label><textarea id="note-{{ $record->id }}" name="comentario" rows="3" maxlength="5000" class="w-full" placeholder="Indique lo que debe corregir…"></textarea><button class="ui-btn-primary ui-icon-save">Guardar revisión</button></form></details>
 @endif
 </td></tr>
 @empty<tr><td colspan="5" class="p-8 text-center text-slate-500">No hay informes para este mes y filtro.</td></tr>@endforelse
 </tbody></table></div>
 <p class="text-xs text-slate-500">El control comienza en {{ config('monthly_reports.starts_at') }}. Los recordatorios se envían durante los últimos {{ config('monthly_reports.reminder_days') }} días del mes; los incumplimientos se notifican después del cierre.</p>
 </div>
 @include('reportes._progreso_drawer')
 @push('styles')
 <style>
 .monthly-filters{display:flex;align-items:end;gap:16px;flex-wrap:wrap}.monthly-filters>div{flex:1;min-width:180px}.monthly-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.monthly-table{min-width:1100px}.monthly-table th{text-align:left;padding:16px;background:#f1f5f9;color:#64748b;font-size:11px;text-transform:uppercase}.monthly-table td{padding:16px;vertical-align:top;border-top:1px solid #e2e8f0}.monthly-table th:first-child{width:24%}.monthly-table th:last-child{width:27%}.monthly-status{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#f1f5f9;color:#475569}.monthly-status-sin_informe{background:#fee2e2;color:#b91c1c}.monthly-status-correccion{background:#fef3c7;color:#92400e}.monthly-status-presentado{background:#dbeafe;color:#1d4ed8}.monthly-status-aceptado{background:#d1fae5;color:#047857}.monthly-controls{margin-top:12px;padding:10px;border:1px solid #dbe5e1;border-radius:10px;background:#f8fafc}.monthly-controls summary{cursor:pointer;font-size:12px;font-weight:700;color:#047857}.monthly-page{animation:monthlyEnter .3s ease both}@keyframes monthlyEnter{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}@media(max-width:800px){.monthly-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(prefers-reduced-motion:reduce){.monthly-page{animation:none}}
 </style>
 @endpush
</x-app-layout>
