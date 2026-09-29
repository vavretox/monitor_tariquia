<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Listado de Proyectos</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="font-semibold">Todos los proyectos</h3>
                    <a href="{{ route('proyectos.create') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition">+ Nuevo</a>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                            <th class="px-4 py-3 text-left">Fechas</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proyectos as $p)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ $p->nombre }}</td>
                                <td class="px-4 py-3">{{ $p->tipo_label }}</td>
                                <td class="px-4 py-3">{{ $p->estado_label }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ optional($p->fecha_inicio)->format('d/m/Y') ?? '—' }} → {{ optional($p->fecha_fin_estimada)->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('proyectos.show', $p) }}" class="text-gray-700 hover:underline">Ver</a>
                                    <a href="{{ route('proyectos.edit', $p) }}" class="text-blue-600 hover:underline">Editar</a>
                                    <form action="{{ route('proyectos.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('¿Archivar?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Archivar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $proyectos->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
