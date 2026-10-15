<section id="notificaciones" class="ui-card p-6">
 <h2 class="text-lg font-bold">Notificaciones <span class="ui-badge bg-amber-100">{{ $pendientes }} sin leer</span></h2>
 <div class="mt-4 divide-y">@forelse($notificaciones as $notificacion)
 <article class="py-4 {{ $notificacion->read_at ? '' : 'rounded-lg bg-emerald-50 px-3' }}"><h3 class="font-semibold">{{ $notificacion->data['titulo'] ?? 'Notificación' }}</h3><p class="text-xs text-slate-500">{{ $notificacion->created_at->format('d/m/Y · H:i') }} · {{ $notificacion->data['autor'] ?? 'Sistema' }}</p><p class="mt-2 whitespace-pre-line text-sm">{{ $notificacion->data['mensaje'] ?? '' }}</p><div class="mt-3 flex flex-wrap gap-2">@if(isset($notificacion->data['periodo']))<a href="{{ route('reportes.control-mensual', ['periodo' => $notificacion->data['periodo']]) }}" class="ui-btn-secondary ui-icon-chart">Ver control mensual</a>@endif
 @if(isset($notificacion->data['accion_id']))<a href="{{ route('acciones.seguimiento', $notificacion->data['accion_id']) }}" class="ui-btn-secondary ui-icon-chart">Abrir seguimiento</a>@endif
 @unless($notificacion->read_at)<form method="POST" action="{{ route('profile.notifications.read', $notificacion->id) }}">@csrf<button class="ui-btn-primary ui-icon-check">Marcar como leída</button></form>@endunless</div></article>
 @empty<p class="py-4 text-slate-500">No tienes notificaciones.</p>@endforelse</div>{{ $notificaciones->fragment('notificaciones')->links() }}
</section>
