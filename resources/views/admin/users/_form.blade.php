@csrf
@if($user->exists) @method('PUT') @endif
<div class="grid gap-5 md:grid-cols-2">
 <div><x-input-label for="name" value="Nombre completo"/><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name',$user->name)" required autofocus/><x-input-error :messages="$errors->get('name')" class="mt-2"/></div>
 <div><x-input-label for="email" value="Correo institucional"/><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email',$user->email)" required/><x-input-error :messages="$errors->get('email')" class="mt-2"/><p class="mt-1 text-xs text-slate-500">Aquí se enviarán el usuario y la contraseña temporal.</p></div>
 <div><x-input-label for="role" value="Rol"/><select id="role" name="role" class="mt-1 block w-full" required><option value="">Seleccione un rol</option>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(old('role',$user->roles->first()?->name)===$role->name)>{{ ucfirst($role->name) }}</option>@endforeach</select><x-input-error :messages="$errors->get('role')" class="mt-2"/></div>
 <div class="flex items-center pt-6"><input type="hidden" name="is_active" value="0"><label class="inline-flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$user->exists?$user->is_active:true))><span><strong class="block text-sm">Usuario activo</strong><small class="text-slate-500">Puede iniciar sesión en el sistema.</small></span></label></div>
</div>
@unless($user->exists)<div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Se generará una contraseña segura y temporal. Las credenciales se enviarán al correo indicado y el usuario deberá cambiar la clave al ingresar.</div>@endunless
<div class="mt-6 flex flex-wrap gap-3"><button class="ui-btn-primary">{{ $user->exists?'Guardar cambios':'Crear y enviar credenciales' }}</button><a href="{{ route('admin.users.index') }}" class="ui-btn-secondary">Cancelar</a></div>
