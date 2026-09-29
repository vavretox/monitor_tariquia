<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial de cambios</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4">
            <div class="bg-white rounded-xl shadow p-6 animate-fade-in">
                <ul class="divide-y">
                    @forelse ($activities as $a)
                        <li class="py-3">
                            <p class="font-medium">{{ $a->description }} — {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}</p>
                            <p class="text-sm text-gray-500">Por {{ $a->causer->name ?? 'sistema' }} el {{ $a->created_at->format('d/m/Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-gray-500">Sin registros.</li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $activities->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
