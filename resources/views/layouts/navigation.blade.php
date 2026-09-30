<nav x-data="{ open: false }" class="app-navigation bg-white border-b border-gray-100">
 <div class="mx-auto w-full max-w-[1700px] px-4 sm:px-6 lg:px-8">
  <div class="flex justify-between h-16">
   <div class="flex min-w-0 flex-1">
    <div class="shrink-0 flex items-center"><a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="brand-logo-link" aria-label="Tarija es su gente"><x-application-logo class="brand-logo-image"/></a></div>
    @auth
    <div class="hidden min-w-0 flex-1 items-stretch justify-evenly gap-3 sm:-my-px sm:ms-8 sm:flex">
     @can('dashboard.view')<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Mapa</x-nav-link>@endcan
     @can('comunidades.view')<x-nav-link :href="route('comunidades.index')" :active="request()->routeIs('comunidades.*')">Comunidades</x-nav-link>@endcan
     @can('demandas.view')<x-nav-link :href="route('demandas.index')" :active="request()->routeIs('demandas.*')">Demandas</x-nav-link>@endcan
     @can('acciones.view')<x-nav-link :href="route('acciones.index')" :active="request()->routeIs('acciones.*')">Acciones</x-nav-link>@endcan
     @can('responsables.view')<x-nav-link :href="route('responsables.index')" :active="request()->routeIs('responsables.*')">Responsables</x-nav-link>@endcan
@can('dashboard.view')<x-nav-link :href="route('reportes.seguimiento')" :active="request()->routeIs('reportes.*')">Reportes</x-nav-link>@endcan
     @can('users.view')<x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">Usuarios</x-nav-link>@endcan
    </div>
    @endauth
   </div>
   @auth
   <div class="hidden sm:flex sm:items-center sm:ms-6"><x-dropdown align="right" width="48"><x-slot name="trigger"><button class="menu-user-trigger inline-flex items-center gap-1 px-4 py-2 text-sm font-bold rounded-xl"><span>{{ Auth::user()->name }}</span><svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button></x-slot><x-slot name="content"><x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link><form method="POST" action="{{ route('logout') }}">@csrf<x-dropdown-link :href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();">Cerrar sesión</x-dropdown-link></form></x-slot></x-dropdown></div>
   <div class="-me-2 flex items-center sm:hidden"><button @click="open=!open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:bg-gray-100"><svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden':open,'inline-flex':!open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/><path :class="{'hidden':!open,'inline-flex':open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
   @else
   <div class="flex items-center"><button type="button" @click="$dispatch('open-login-modal')" class="ui-btn-primary">Iniciar sesión</button></div>
   @endauth
  </div>
 </div>
 @auth
 <div :class="{'block':open,'hidden':!open}" class="hidden sm:hidden">
  <div class="pt-2 pb-3 space-y-1">
   @can('dashboard.view')<x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Mapa</x-responsive-nav-link>@endcan
   @can('comunidades.view')<x-responsive-nav-link :href="route('comunidades.index')" :active="request()->routeIs('comunidades.*')">Comunidades</x-responsive-nav-link>@endcan
   @can('demandas.view')<x-responsive-nav-link :href="route('demandas.index')" :active="request()->routeIs('demandas.*')">Demandas</x-responsive-nav-link>@endcan
   @can('acciones.view')<x-responsive-nav-link :href="route('acciones.index')" :active="request()->routeIs('acciones.*')">Acciones</x-responsive-nav-link>@endcan
   @can('responsables.view')<x-responsive-nav-link :href="route('responsables.index')" :active="request()->routeIs('responsables.*')">Responsables</x-responsive-nav-link>@endcan
@can('dashboard.view')<x-responsive-nav-link :href="route('reportes.seguimiento')" :active="request()->routeIs('reportes.*')">Reportes</x-responsive-nav-link>@endcan
   @can('users.view')<x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">Usuarios</x-responsive-nav-link>@endcan
  </div>
  <div class="pt-4 pb-1 border-t border-gray-200"><div class="px-4"><div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div><div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div></div><div class="mt-3 space-y-1"><x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link><form method="POST" action="{{ route('logout') }}">@csrf<x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();">Cerrar sesión</x-responsive-nav-link></form></div></div>
 </div>
 @endauth
</nav>

