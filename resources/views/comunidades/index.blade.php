<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Comunidades</h2></x-slot>
<div class="py-6"><div class="max-w-7xl mx-auto px-4 space-y-4">
@if(session('success'))<div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-3 rounded-lg">{{ session('success') }}</div>@endif

<div class="ui-toolbar">
 <form class="flex flex-wrap gap-3 items-center">
  <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar comunidad o territorio" class="border-gray-300 rounded-lg flex-1 min-w-0">
  <button class="ui-btn-secondary !bg-slate-800 !text-white">Buscar</button>
  <a href="{{ route('comunidades.create') }}" class="ui-btn-primary">+ Agregar</a>
 </form>
</div>

<div class="ui-table-shell">
 <table class="w-full table-fixed text-sm">
  <colgroup>
   <col class="w-[25%]"><col class="w-[25%]"><col class="w-[25%]"><col class="w-[25%]">
  </colgroup>
  <thead class="bg-gray-100 text-gray-700">
   <tr>
    <th class="px-3 py-3 text-left">Comunidad</th>
    <th class="px-3 py-3 text-left">Territorio</th>
    <th class="px-3 py-3 text-left">Georreferencia</th>
    <th class="px-3 py-3 text-center">Acciones</th>
   </tr>
  </thead>
  <tbody>
  @forelse($comunidades as $c)
   <tr class="border-t align-top hover:bg-gray-50">
    <td class="px-3 py-4 font-semibold text-gray-900 break-words">{{ $c->nombre }}</td>
    <td class="px-3 py-4 text-gray-700 break-words">{{ $c->territorio ?: '—' }}</td>
    <td class="px-3 py-4 text-gray-700">
     <span class="block break-all font-mono text-xs">{{ number_format($c->latitud,6) }},<br>{{ number_format($c->longitud,6) }}</span>
    </td>
    <td class="px-3 py-4">
     <div class="flex flex-col xl:flex-row justify-center gap-2"><a href="{{ route('comunidades.show',$c) }}" class="ui-btn-secondary !px-3 !py-2">Ver</a>
      <a href="{{ route('comunidades.edit',$c) }}" class="ui-btn-info !px-3 !py-2">Editar</a>
      <form method="POST" action="{{ route('comunidades.destroy',$c) }}" onsubmit="return confirm('¿Eliminar esta comunidad?')" class="m-0">@csrf @method('DELETE')
       <button class="ui-btn-danger w-full !px-3 !py-2">Eliminar</button>
      </form>
     </div>
    </td>
   </tr>
  @empty
   <tr><td colspan="4" class="p-8 text-center text-gray-500">No hay comunidades registradas.</td></tr>
  @endforelse
  </tbody>
 </table>
 <div class="p-4 border-t">{{ $comunidades->links() }}</div>
</div>
</div></div>

@push('styles')
<style>
@media(max-width:700px){
 .community-table-wrap{font-size:11px}
 table th,table td{padding-left:.4rem!important;padding-right:.4rem!important}
}
</style>
@endpush
</x-app-layout>
