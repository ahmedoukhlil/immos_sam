<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Tableau de bord
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                </p>
            </div>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('inventaires.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Démarrer un inventaire
                </a>
            @endif
        </div>
    </x-slot>

    @livewire('dashboard')
</x-app-layout>
