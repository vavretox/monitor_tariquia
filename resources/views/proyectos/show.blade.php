<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $proyecto->nombre }}</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 space-y-6">
            @if (session('success'))
                <div class="bg-emerald-100 text-emerald-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            <div class="bg-white rounded-xl shadow p-6 space-y-4 animate-fade-in">
                <div class="flex items-center justify-between">
                    <span class="px-3 py-1 text-xs rounded-full text-white" style="background: {{ $proyecto->estado_color }}">{{ $proyecto->estado_label }}</span>
                    <span class="text-sm text-gray-500">{{ $proyecto->tipo_label }}</span>
                </div>
                <p class="text-gray-700">{{ $proyecto->descripcion ?: 'Sin descripción.' }}</p>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><b>Coordenadas:</b> {{ $proyecto->latitud }}, {{ $proyecto->longitud }}</li>
                    <li><b>Inicio:</b> {{ optional($proyecto->fecha_inicio)->format('d/m/Y') ?? '—' }}</li>
                    <li><b>Fin estimado:</b> {{ optional($proyecto->fecha_fin_estimada)->format('d/m/Y') ?? '—' }}</li>
                    <li><b>Presupuesto:</b> Bs {{ number_format($proyecto->presupuesto ?? 0, 2, ',', '.') }}</li>
                    <li><b>Responsable:</b> {{ $proyecto->user->name ?? 'Sin asignar' }}</li>
                </ul>
                <div class="flex gap-3 pt-4">
                    <a href="{{ route('proyectos.edit', $proyecto) }}" class="ui-btn-info hover:bg-blue-700 transition">Editar</a>
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg border hover:bg-gray-50">Volver</a>
                    @role('admin')
                        <form action="{{ route('proyectos.forceDelete', $proyecto) }}" method="POST" onsubmit="return confirm('¿Eliminar definitivamente?')">
                            @csrf @method('DELETE')
                            <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Eliminar definitivo</button>
                        </form>
                    @endrole
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-6 animate-fade-in">
                <h3 class="font-semibold mb-4">Evidencias / Archivos</h3>
                @role('admin|tecnico')
                    <form action="{{ route('proyectos.archivos.store', $proyecto) }}" method="POST" enctype="multipart/form-data" class="flex gap-3 mb-4">
                        @csrf
                        <input type="file" name="archivo" required class="border rounded-lg px-3 py-2 flex-1">
                        <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition">Subir</button>
                    </form>
                @endrole
                <ul class="divide-y">
                    @forelse ($proyecto->archivos as $a)
                        <li class="py-2 flex justify-between items-center">
                            <a href="{{ route('evidencias.proyectos.descargar', $a) }}" class="text-blue-600 hover:underline">{{ $a->nombre_original }}</a>
                            @role('admin|tecnico')
                                <form action="{{ route('archivos.destroy', $a) }}" method="POST" onsubmit="return confirm('¿Eliminar archivo?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-sm">Eliminar</button>
                                </form>
                            @endrole
                        </li>
                    @empty
                        <li class="py-3 text-gray-500 text-sm">Sin archivos cargados.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
