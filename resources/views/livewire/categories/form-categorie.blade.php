<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('categories.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2">Catégories</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                            {{ $this->isEdit ? 'Modifier' : 'Ajouter' }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold text-gray-900">
            {{ $this->isEdit ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Taux et durée conformes au Code Général des Impôts Mauritanie.
        </p>
    </div>

    {{-- Formulaire --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div wire:loading.class="opacity-50 pointer-events-none"
             class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Code --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Code <span class="text-red-500">*</span>
                        <span class="text-xs text-gray-400 font-normal ml-1">(4 caractères, ex: MINF)</span>
                    </label>
                    <input
                        type="text"
                        wire:model="CodeCategorie"
                        maxlength="10"
                        placeholder="Ex: MINF, MOBI, MTRP"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono uppercase @error('CodeCategorie') border-red-300 @enderror"
                        wire:loading.attr="disabled">
                    @error('CodeCategorie')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Libellé --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Libellé <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="Categorie"
                        placeholder="Ex: Matériel Informatique et Logiciels"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('Categorie') border-red-300 @enderror"
                        wire:loading.attr="disabled">
                    @error('Categorie')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Durée --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Durée d'amortissement <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            wire:model.live="duree_amortissement"
                            min="1"
                            max="50"
                            placeholder="Ex: 4"
                            class="block w-full pr-12 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('duree_amortissement') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">ans</span>
                        </div>
                    </div>
                    @error('duree_amortissement')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">La saisie de la durée calcule le taux automatiquement.</p>
                </div>

                {{-- Taux --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Taux d'amortissement <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            wire:model="taux_amortissement"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="Ex: 25"
                            class="block w-full pr-8 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('taux_amortissement') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    @error('taux_amortissement')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Type CGI --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Type CGI
                        <span class="text-xs text-gray-400 font-normal ml-1">(libellé officiel dans le CGI)</span>
                    </label>
                    <input
                        type="text"
                        wire:model="type_cgi"
                        placeholder="Ex: Matériel informatique"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('type_cgi') border-red-300 @enderror"
                        wire:loading.attr="disabled">
                    @error('type_cgi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Récapitulatif --}}
                @if($duree_amortissement && $taux_amortissement)
                <div class="md:col-span-2">
                    <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-4 flex items-center gap-4">
                        <svg class="w-6 h-6 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-indigo-800">
                            Un bien de cette catégorie s'amortit sur
                            <strong>{{ $duree_amortissement }} ans</strong>
                            au taux de <strong>{{ $taux_amortissement }}%</strong> par an.
                            Seuil d'amortissabilité : <strong>50 000 MRU</strong> (CGI Mauritanie).
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Boutons --}}
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 rounded-b-lg shadow-lg -mx-6 -mb-6">
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="cancel" wire:loading.attr="disabled"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Annuler
                </button>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $this->isEdit ? 'Modifier' : 'Enregistrer' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Enregistrement...
                    </span>
                </button>
            </div>
        </div>
    </form>

    @if(session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
             class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
             class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif
</div>
