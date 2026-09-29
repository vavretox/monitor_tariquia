<x-app-layout>
 <x-slot name="header"><h2 class="font-semibold text-xl">{{ $role->exists?'Permisos de '.ucfirst($role->name):'Crear rol' }}</h2></x-slot>
 <div class="py-6"><div class="max-w-5xl mx-auto px-4"><form method="POST" action="{{ $role->exists?route('admin.roles.update',$role):route('admin.roles.store') }}" class="ui-form-card">@csrf @if($role->exists) @method('PUT') @endif
 @if(!$role->exists)<div class="mb-6"><x-input-label for="name" value="Nombre interno del rol"/><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="ejemplo: coordinador" required/><x-input-error :messages="$errors->get('name')" class="mt-2"/></div>@endif
 @if($role->name==='admin')<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">El administrador conserva acceso total como medida de recuperación. La matriz se guarda para documentar su configuración.</div>@endif
 <div class="overflow-x-auto rounded-xl border border-slate-200"><table class="w-full min-w-[600px] text-sm"><thead><tr><th class="p-3 text-left">Módulo</th><th class="p-3 text-left">Acciones permitidas</th></tr></thead><tbody>
 @foreach($modules as $moduleKey=>$module)<tr class="border-t"><td class="p-4 font-semibold">{{ $module['label'] }}</td><td class="p-4"><div class="flex flex-wrap gap-4">@foreach($module['actions'] as $action=>$label)@php($permission=$moduleKey.'.'.$action)<label class="inline-flex items-center gap-2"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission,old('permissions',$selected)))><span>{{ $label }}</span></label>@endforeach</div></td></tr>@endforeach
 </tbody></table></div><x-input-error :messages="$errors->get('permissions')" class="mt-2"/>
 <div class="mt-6 flex gap-3"><button class="ui-btn-primary">Guardar permisos</button><a href="{{ route('admin.roles.index') }}" class="ui-btn-secondary">Cancelar</a></div>
 </form></div></div>
</x-app-layout>
