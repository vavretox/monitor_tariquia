<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Proyecto</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4">
            <form method="POST" action="{{ route('proyectos.update', $proyecto) }}" class="bg-white rounded-xl shadow p-6 space-y-5 animate-fade-in">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">Nombre *</label>
                    <input name="nombre" value="{{ old('nombre', $proyecto->nombre) }}" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo *</label>
                        <select name="tipo" class="w-full border rounded-lg px-3 py-2">
                            <option value="salud" @selected($proyecto->tipo==='salud')>Salud</option>
                            <option value="infraestructura_caminos" @selected($proyecto->tipo==='infraestructura_caminos')>Infraestructura de Caminos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Estado *</label>
                        <select name="estado" class="w-full border rounded-lg px-3 py-2">
                            @foreach (['planificado','en_ejecucion','completado','suspendido'] as $e)
                                <option value="{{ $e }}" @selected($proyecto->estado===$e)>{{ ucfirst(str_replace('_',' ', $e)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Latitud *</label>
                        <input name="latitud" id="lat" value="{{ old('latitud', $proyecto->latitud) }}" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Longitud *</label>
                        <input name="longitud" id="lng" value="{{ old('longitud', $proyecto->longitud) }}" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', optional($proyecto->fecha_inicio)->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha fin estimada</label>
                        <input type="date" name="fecha_fin_estimada" value="{{ old('fecha_fin_estimada', optional($proyecto->fecha_fin_estimada)->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Presupuesto (Bs)</label>
                        <input type="number" step="0.01" name="presupuesto" value="{{ old('presupuesto', $proyecto->presupuesto) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('descripcion', $proyecto->descripcion) }}</textarea>
                </div>
                <div id="picker-map" style="height: 320px;" class="rounded-lg shadow"></div>
                <div class="flex gap-3">
                    <button class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition shadow">Actualizar</button>
                    <a href="{{ route('dashboard') }}" class="px-5 py-2 rounded-lg border hover:bg-gray-50">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    @push('styles')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />@endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const lat = {{ $proyecto->latitud }}, lng = {{ $proyecto->longitud }};
            const map = L.map('picker-map').setView([lat, lng], 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            let marker = L.marker([lat, lng]).addTo(map);
            map.on('click', e => {
                document.getElementById('lat').value = e.latlng.lat.toFixed(7);
                document.getElementById('lng').value = e.latlng.lng.toFixed(7);
                marker.setLatLng(e.latlng);
            });
        </script>
    @endpush
</x-app-layout>
