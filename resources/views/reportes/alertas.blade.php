<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold text-red-700">Monitoreo operativo</p><h2 class="text-2xl font-extrabold text-slate-900">Alertas y pendientes</h2></div><a href="{{ route('reportes.seguimiento') }}" class="ui-btn-secondary">Ver matriz general</a></div></x-slot>
    <div class="py-6"><div class="mx-auto max-w-7xl space-y-6 px-4">
        @php($grupos = [
            ['Acciones vencidas', $vencidas, 'red', 'La fecha límite ya pasó.'],
            ['Próximas a vencer', $proximas, 'amber', 'Vencen durante los próximos 15 días.'],
            ['Sin actualización reciente', $sinActualizar, 'blue', 'No registran cambios de estado durante 30 días.'],
            ['Completadas sin evidencia', $completadasSinEvidencia, 'violet', 'Deben adjuntar respaldo antes de cerrar el seguimiento.'],
        ])
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach([['Vencidas',$vencidas->count(),'red'],['Por vencer',$proximas->count(),'amber'],['Sin actualizar',$sinActualizar->count(),'blue'],['Sin compromiso',$sinCompromiso->count(),'slate'],['Sin evidencia',$completadasSinEvidencia->count(),'violet']] as [$titulo,$total,$color])
                <div class="ui-card border-t-4 border-{{ $color }}-500 p-4"><p class="text-sm font-semibold text-slate-500">{{ $titulo }}</p><p class="mt-1 text-3xl font-extrabold">{{ $total }}</p></div>
            @endforeach
        </div>
        @foreach($grupos as [$titulo,$items,$color,$descripcion])
            <section class="ui-card overflow-hidden">
                <div class="border-b border-slate-200 p-5"><h3 class="text-lg font-bold">{{ $titulo }} <span class="ui-badge bg-{{ $color }}-100 text-{{ $color }}-700">{{ $items->count() }}</span></h3><p class="text-sm text-slate-500">{{ $descripcion }}</p></div>
                <div class="divide-y divide-slate-100">
                    @forelse($items as $accion)
                        <a href="{{ route('compromisos.show', $accion->accion_compromiso_id) }}" class="grid gap-2 p-4 hover:bg-slate-50 md:grid-cols-4"><b>{{ $accion->titulo }}</b><span>{{ $accion->compromiso?->necesidad?->comunidades?->pluck('nombre')->join(', ') ?: 'Sin comunidad' }}</span><span>{{ ucfirst(str_replace('_', ' ', $accion->estado)) }}</span><span>{{ $accion->fecha_limite?->format('d/m/Y') ?: 'Sin fecha límite' }} @if($accion->dias_retraso)<strong class="block text-red-700">{{ $accion->dias_retraso }} día(s) tarde</strong><small class="block text-slate-500">{{ $accion->responsables->pluck('nombre_completo')->join(', ') ?: 'Sin responsable' }}</small>@endif</span></a>
                    @empty
                        <p class="p-5 text-sm text-slate-500">No existen registros en esta alerta.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
        <section class="ui-card overflow-hidden"><div class="border-b border-slate-200 p-5"><h3 class="text-lg font-bold">Necesidades sin compromiso <span class="ui-badge bg-slate-100 text-slate-700">{{ $sinCompromiso->count() }}</span></h3></div><div class="divide-y divide-slate-100">@forelse($sinCompromiso as $necesidad)<a href="{{ route('necesidades.show', $necesidad) }}" class="grid gap-2 p-4 hover:bg-slate-50 md:grid-cols-3"><b>{{ $necesidad->titulo }}</b><span>{{ $necesidad->comunidades->pluck('nombre')->join(', ') }}</span><span>{{ $necesidad->tipo?->nombre ?: 'Sin tipo' }}</span></a>@empty<p class="p-5 text-sm text-slate-500">Todas las necesidades tienen al menos un compromiso.</p>@endforelse</div></section>
    </div></div>
</x-app-layout>
