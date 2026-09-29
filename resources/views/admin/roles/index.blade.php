<x-app-layout>
 <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-semibold text-xl">Roles y permisos</h2><p class="text-sm text-slate-500">Define qué módulos puede ver y modificar cada rol.</p></div>@can('roles.edit')<a href="{{ route('admin.roles.create') }}" class="ui-btn-primary">+ Crear rol</a>@endcan</div></x-slot>
 <div class="py-6"><div class="max-w-5xl mx-auto px-4 space-y-4">
 @if(session('success'))<div class="bg-emerald-100 text-emerald-800 p-3 rounded">{{ session('success') }}</div>@endif
 @foreach($roles as $role)<div class="ui-card p-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-lg font-bold">{{ ucfirst($role->name) }} @if($role->name==='admin')<span class="ui-badge bg-emerald-100 text-emerald-700">Acceso total protegido</span>@endif</h3><p class="text-sm text-slate-500">{{ $role->users_count }} usuario(s) · {{ $role->permissions->count() }} permiso(s)</p></div>@can('roles.edit')<a href="{{ route('admin.roles.edit',$role) }}" class="ui-btn-info">Editar permisos</a>@endcan</div>@endforeach
 <a href="{{ route('admin.users.index') }}" class="ui-btn-secondary">Volver a usuarios</a>
 </div></div>
</x-app-layout>
