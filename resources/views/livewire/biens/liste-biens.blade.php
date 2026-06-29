<div>
    @php
        $isAdmin = auth()->user()->isAdmin();
    @endphp

    <div class="space-y-6">
        {{-- Header avec titre et actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestion des Immobilisations</h1>
                <p class="mt-1 text-sm text-gray-500">Liste et gestion de toutes les immobilisations de l'inventaire</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                @if(count($selectedBiens) > 0)
                    <button 
                        wire:click="exportSelected"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exporter sélection ({{ count($selectedBiens) }})
                    </button>
                    
                    <a 
                        href="{{ route('biens.imprimer-etiquettes') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                        onclick="event.preventDefault(); document.getElementById('print-form').submit();">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Imprimer étiquettes sélectionnées
                    </a>
                    <form id="print-form" action="{{ route('biens.imprimer-etiquettes') }}" method="POST" class="hidden">
                        @csrf
                        @foreach($selectedBiens as $id)
                            <input type="hidden" name="biens[]" value="{{ $id }}">
                        @endforeach
                    </form>
                @endif

                <a 
                    href="{{ route('biens.export-excel') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter en Excel
                </a>

            </div>
        </div>

        {{-- Barre de filtres (collapsible) --}}
        <div 
            x-data="{ open: false }"
            class="bg-white rounded-lg shadow-sm border border-gray-200">
            <button 
                @click="open = !open"
                class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
                <span class="font-medium text-gray-900">Filtres de recherche</span>
                <svg 
                    class="w-5 h-5 text-gray-500 transition-transform"
                    :class="{ 'rotate-180': open }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div 
                x-show="open"
                x-collapse
                class="border-t border-gray-200 p-4"
                style="overflow: visible !important;">
                <div class="space-y-4">
                    {{-- Première ligne : Recherche globale et filtres principaux --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Recherche globale --}}
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Recherche globale
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="NumOrdre (ex: 1001), désignation, emplacement..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        {{-- Filtre Désignation --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Désignation
                            </label>
                            <livewire:components.searchable-select
                                wire:model.live="filterDesignation"
                                :options="$this->designations->map(fn($d) => [
                                    'value' => (string)$d->id,
                                    'text' => $d->designation . ($d->categorie ? ' (' . $d->categorie->Categorie . ')' : '')
                                ])->prepend(['value' => '', 'text' => 'Toutes les désignations'])->toArray()"
                                placeholder="Toutes les désignations"
                                :key="'filter-designation'"
                            />
                        </div>

                        {{-- Filtre Catégorie (select natif - peu d'options) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Catégorie
                            </label>
                            <select
                                wire:model.live="filterCategorie"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Toutes les catégories</option>
                                @foreach($this->categories as $c)
                                    <option value="{{ $c->idCategorie }}">{{ $c->Categorie }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Deuxième ligne : Filtres hiérarchiques Localisation → Affectation → Emplacement --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Filtrage hiérarchique par emplacement
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Filtre Localisation --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Localisation
                                </label>
                                <div class="relative">
                                    <div wire:loading wire:target="filterLocalisation" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
                                        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <livewire:components.searchable-select
                                        wire:model.live="filterLocalisation"
                                        :options="$this->localisations->map(fn($l) => [
                                            'value' => (string)$l->idLocalisation,
                                            'text' => $l->Localisation . ($l->CodeLocalisation ? ' (' . $l->CodeLocalisation . ')' : '')
                                        ])->prepend(['value' => '', 'text' => 'Toutes les localisations'])->toArray()"
                                        placeholder="Toutes les localisations"
                                        :key="'localisation-select-static'"
                                    />
                                </div>
                            </div>

                            {{-- Filtre Affectation --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Affectation
                                    @if($filterLocalisation)
                                        <span class="text-xs text-gray-500 font-normal">(filtrée)</span>
                                    @endif
                                    <span wire:loading wire:target="filterLocalisation" class="text-xs text-indigo-600 ml-2">
                                        <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </label>
                                <div class="relative">
                                    <div wire:loading wire:target="filterLocalisation,filterAffectation" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
                                        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <livewire:components.searchable-select
                                        wire:model.live="filterAffectation"
                                        :options="$this->affectationOptions"
                                        placeholder="Toutes les affectations"
                                        search-placeholder="Rechercher une affectation..."
                                        no-results-text="Aucune affectation trouvée"
                                        :allow-clear="true"
                                        :disabled="empty($filterLocalisation)"
                                        :container-class="empty($filterLocalisation) && !empty($filterAffectation) ? 'ring-2 ring-yellow-300' : ''"
                                        :key="'affectation-select-' . ($filterLocalisation ?? 'none')"
                                    />
                                </div>
                                @if(empty($filterLocalisation) && !empty($filterAffectation))
                                    <p class="mt-1 text-xs text-yellow-600">
                                        Sélectionnez une localisation pour filtrer les affectations disponibles
                                    </p>
                                @endif
                            </div>

                            {{-- Filtre Emplacement --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Emplacement
                                    @if($filterLocalisation || $filterAffectation)
                                        <span class="text-xs text-gray-500 font-normal">(filtré)</span>
                                    @endif
                                    <span wire:loading wire:target="filterLocalisation, filterAffectation" class="text-xs text-indigo-600 ml-2">
                                        <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </label>
                                <div class="flex flex-col gap-2">
                                    <div class="relative">
                                        <div wire:loading wire:target="filterAffectation,filterEmplacement" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
                                            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <livewire:components.searchable-select
                                            wire:model.live="filterEmplacement"
                                            :options="$this->emplacementOptions"
                                            placeholder="Tous les emplacements"
                                            search-placeholder="Rechercher un emplacement..."
                                            no-results-text="Aucun emplacement trouvé"
                                            :allow-clear="true"
                                            :disabled="empty($filterAffectation)"
                                            :container-class="empty($filterAffectation) && !empty($filterEmplacement) ? 'ring-2 ring-yellow-300' : ''"
                                            :key="'emplacement-select-' . ($filterLocalisation ?? 'none') . '-' . ($filterAffectation ?? 'none')"
                                        />
                                    </div>
                                    @if(empty($filterAffectation) && !empty($filterEmplacement))
                                        <p class="text-xs text-yellow-600">
                                            Sélectionnez une affectation pour filtrer les emplacements disponibles
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($filterEmplacement)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <form 
                                    action="{{ route('biens.imprimer-etiquettes-par-emplacement') }}" 
                                    method="POST" 
                                    class="inline-block">
                                    @csrf
                                    <input type="hidden" name="idEmplacement" value="{{ $filterEmplacement }}">
                                    <button 
                                        type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm"
                                        title="Imprimer toutes les étiquettes de cet emplacement (33 par page A4)">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Imprimer les étiquettes de cet emplacement
                                    </button>
                                </form>
                                <p class="mt-2 text-xs text-gray-500">
                                    <svg class="w-4 h-4 inline-block mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Format: 33 étiquettes par page A4
                                </p>
                            </div>
                        @endif
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <form
                                action="{{ route('biens.imprimer-etiquettes-tous-emplacements') }}"
                                method="POST"
                                class="inline-block">
                                @csrf
                                <input type="hidden" name="filterLocalisation" value="{{ $filterLocalisation }}">
                                <input type="hidden" name="filterAffectation" value="{{ $filterAffectation }}">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm"
                                    title="Imprimer toutes les étiquettes groupées emplacement par emplacement dans un seul fichier">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Imprimer toutes les étiquettes (par emplacement)
                                </button>
                            </form>
                            <p class="mt-2 text-xs text-gray-500">
                                Génère un seul fichier PDF avec les étiquettes regroupées emplacement par emplacement
                                @if($filterLocalisation || $filterAffectation)
                                    (selon les filtres hiérarchiques actifs).
                                @else
                                    (tous les emplacements).
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Troisième ligne : Intervalle NumOrdre + Impression étiquettes --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3m6 0c0 1.657-1.343 3-3 3m0-6V5m0 9v5m7-8h-3M8 12H5" />
                            </svg>
                            Filtrage et impression par intervalle NumOrdre
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    NumOrdre de début
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    wire:model.live.debounce.300ms="filterNumOrdreMin"
                                    placeholder="Ex: 1000"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    NumOrdre de fin
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    wire:model.live.debounce.300ms="filterNumOrdreMax"
                                    placeholder="Ex: 1200"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Année d'acquisition
                                </label>
                                <input
                                    type="number"
                                    wire:model.live.debounce.300ms="filterDateAcquisition"
                                    min="1900"
                                    max="{{ now()->year + 1 }}"
                                    placeholder="Ex: {{ now()->year }}"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        @if($filterNumOrdreMin !== '' && $filterNumOrdreMax !== '')
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <form
                                    action="{{ route('biens.imprimer-etiquettes-par-intervalle') }}"
                                    method="POST"
                                    class="inline-block">
                                    @csrf
                                    <input type="hidden" name="numOrdreMin" value="{{ $filterNumOrdreMin }}">
                                    <input type="hidden" name="numOrdreMax" value="{{ $filterNumOrdreMax }}">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm"
                                        title="Imprimer toutes les étiquettes de cet intervalle (33 par page A4)">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Imprimer les étiquettes de cet intervalle
                                    </button>
                                </form>
                                <p class="mt-2 text-xs text-gray-500">
                                    <svg class="w-4 h-4 inline-block mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Format: 33 étiquettes par page A4
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <button 
                        wire:click="resetFilters"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Réinitialiser filtres
                    </button>

                    <div class="text-sm text-gray-600">
                        <span class="font-medium">{{ $biens->total() }}</span> immobilisation(s) trouvée(s)
                    </div>
                </div>
            </div>
        </div>

        {{-- Tableau des biens --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input 
                                    type="checkbox"
                                    wire:click="toggleSelectAll"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @if($this->allSelected) checked @endif>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('NumOrdre')">
                                <div class="flex items-center">
                                    NumOrdre
                                    @if($sortField === 'NumOrdre')
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Désignation
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Catégorie
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                État
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Emplacement
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Année d'acquisition
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($biens as $bien)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <input 
                                        type="checkbox"
                                        wire:model="selectedBiens"
                                        value="{{ $bien->NumOrdre }}"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $bien->NumOrdre }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-mono text-gray-600">{{ $bien->code_formate ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900">
                                        {{ $bien->designation ? Str::limit($bien->designation->designation, 50) : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($bien->categorie)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $bien->categorie->Categorie }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($bien->etat)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $bien->etat->Etat }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($bien->emplacement)
                                            {{ $bien->emplacement->Emplacement }}
                                            @if($bien->emplacement->affectation)
                                                <br><span class="text-xs text-gray-500">{{ $bien->emplacement->affectation->Affectation }}</span>
                                            @endif
                                            @if($bien->emplacement->localisation)
                                                <br><span class="text-xs text-gray-500">{{ $bien->emplacement->localisation->Localisation }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($bien->DateAcquisition && $bien->DateAcquisition > 1970)
                                            {{ $bien->DateAcquisition }}
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            href="{{ route('biens.show', $bien) }}"
                                            class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                            title="Voir">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        @if($isAdmin)
                                            <a 
                                                href="{{ route('biens.edit', $bien) }}"
                                                class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                                title="Modifier">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                        @endif

                                        <a 
                                            href="{{ route('biens.qr-code', $bien) }}"
                                            class="text-blue-600 hover:text-blue-900 transition-colors"
                                            title="Code-barres">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                            </svg>
                                        </a>

                                        @if($isAdmin)
                                            <button 
                                                wire:click="deleteBien({{ $bien->NumOrdre }})"
                                                wire:confirm="Êtes-vous sûr de vouloir supprimer ce bien ?"
                                                class="text-red-600 hover:text-red-900 transition-colors"
                                                title="Supprimer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun bien trouvé</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        @if($search || $filterDesignation || $filterCategorie || $filterLocalisation || $filterAffectation || $filterEmplacement || $filterNumOrdreMin || $filterNumOrdreMax || $filterDateAcquisition)
                                            Essayez de modifier vos critères de recherche.
                                        @else
                                            Commencez par créer une nouvelle immobilisation.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($biens->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:px-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Par page :</label>
                            <select 
                                wire:model.live="perPage"
                                class="block px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div>
                            {{ $biens->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Messages flash --}}
    @if(session()->has('success'))
        <div 
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            x-transition
            class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div 
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            x-transition
            class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif

    @if(session()->has('warning'))
        <div 
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition
            class="fixed bottom-4 right-4 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('warning') }}
        </div>
    @endif

</div>

