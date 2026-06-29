<div>
    {{-- Header avec titre et breadcrumb --}}
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
                        <a href="{{ route('biens.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2">Immobilisations</a>
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
            {{ $this->isEdit ? 'Modifier un bien' : 'Ajouter un bien' }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $this->isEdit ? 'Modifiez les informations du bien' : 'Remplissez le formulaire pour ajouter un nouveau bien à l\'inventaire' }}
        </p>
    </div>

    {{-- Formulaire --}}
    <form wire:submit.prevent="save" class="space-y-6">
        <div 
            wire:loading.class="opacity-50 pointer-events-none"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            
            {{-- Section 1 : Informations générales --}}
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    Informations générales
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Désignation --}}
                    <div>
                        <label for="idDesignation" class="block text-sm font-medium text-gray-700 mb-1">
                            Désignation <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:key="designation-{{ $bienId ?? 'new' }}"
                            wire:model.live.debounce.150ms="idDesignation"
                            :default="$idDesignation"
                            :options="$this->designationOptions"
                            placeholder="Sélectionner une désignation"
                            search-placeholder="Rechercher une désignation..."
                            no-results-text="Aucune désignation trouvée"
                            :allow-clear="true"
                            name="idDesignation"
                        />
                        @error('idDesignation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Catégorie --}}
                    <div>
                        <label for="idCategorie" class="block text-sm font-medium text-gray-700 mb-1">
                            Catégorie <span class="text-red-500">*</span>
                            @if($idDesignation)
                                <span class="text-xs text-gray-500 font-normal ml-2">(automatiquement remplie)</span>
                            @endif
                        </label>
                        <livewire:components.searchable-select
                            wire:key="categorie-{{ $idDesignation }}"
                            wire:model="idCategorie"
                            :options="$this->categorieOptions"
                            placeholder="Sélectionner une catégorie"
                            search-placeholder="Rechercher une catégorie..."
                            no-results-text="Aucune catégorie trouvée"
                            :allow-clear="!$idDesignation"
                            :disabled="!!$idDesignation"
                            name="idCategorie"
                        />
                        @error('idCategorie')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @if($idDesignation)
                            <p class="mt-1 text-xs text-gray-500">
                                La catégorie est automatiquement définie selon la désignation sélectionnée.
                            </p>
                        @endif
                    </div>

                    {{-- État --}}
                    <div>
                        <label for="idEtat" class="block text-sm font-medium text-gray-700 mb-1">
                            État <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:key="etat-{{ $bienId ?? 'new' }}"
                            wire:model="idEtat"
                            :default="$idEtat"
                            :options="$this->etatOptions"
                            placeholder="Sélectionner un état"
                            search-placeholder="Rechercher un état..."
                            no-results-text="Aucun état trouvé"
                            :allow-clear="true"
                            name="idEtat"
                        />
                        @error('idEtat')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Localisation --}}
                    <div>
                        <label for="idLocalisation" class="block text-sm font-medium text-gray-700 mb-1">
                            Localisation <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:model.live.debounce.150ms="idLocalisation"
                            :options="$this->localisationOptions"
                            placeholder="Sélectionner une localisation"
                            search-placeholder="Rechercher une localisation..."
                            no-results-text="Aucune localisation trouvée"
                            :allow-clear="true"
                            name="idLocalisation"
                        />
                        @error('idLocalisation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Affectation --}}
                    <div>
                        <label for="idAffectation" class="block text-sm font-medium text-gray-700 mb-1">
                            Affectation <span class="text-red-500">*</span>
                        </label>
                        @if(empty($idLocalisation))
                            <div class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-500 text-sm italic">
                                Sélectionnez d'abord une localisation
                            </div>
                        @else
                            <livewire:components.searchable-select
                                wire:key="affectation-{{ $idLocalisation }}"
                                wire:model.live.debounce.150ms="idAffectation"
                                :options="$this->affectationOptions"
                                placeholder="Sélectionner une affectation"
                                search-placeholder="Rechercher une affectation..."
                                no-results-text="Aucune affectation pour cette localisation"
                                :allow-clear="true"
                                name="idAffectation"
                            />
                        @endif
                        @error('idAffectation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Emplacement --}}
                    <div>
                        <label for="idEmplacement" class="block text-sm font-medium text-gray-700 mb-1">
                            Emplacement <span class="text-red-500">*</span>
                        </label>
                        @if(empty($idLocalisation) || empty($idAffectation))
                            <div class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-500 text-sm italic">
                                Sélectionnez d'abord une localisation et une affectation
                            </div>
                        @else
                            <livewire:components.searchable-select
                                wire:key="emplacement-{{ $idLocalisation }}-{{ $idAffectation }}"
                                wire:model="idEmplacement"
                                :options="$this->emplacementOptions"
                                placeholder="Sélectionner un emplacement"
                                search-placeholder="Rechercher un emplacement..."
                                no-results-text="Aucun emplacement pour cette affectation"
                                :allow-clear="true"
                                name="idEmplacement"
                            />
                        @endif
                        @error('idEmplacement')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nature Juridique --}}
                    <div>
                        <label for="idNatJur" class="block text-sm font-medium text-gray-700 mb-1">
                            Nature Juridique <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:key="natjur-{{ $bienId ?? 'new' }}"
                            wire:model="idNatJur"
                            :default="$idNatJur"
                            :options="$this->natureJuridiqueOptions"
                            placeholder="Sélectionner une nature juridique"
                            search-placeholder="Rechercher une nature juridique..."
                            no-results-text="Aucune nature juridique trouvée"
                            :allow-clear="true"
                            name="idNatJur"
                        />
                        @error('idNatJur')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Source de Financement --}}
                    <div>
                        <label for="idSF" class="block text-sm font-medium text-gray-700 mb-1">
                            Source de Financement <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:key="sf-{{ $bienId ?? 'new' }}"
                            wire:model="idSF"
                            :default="$idSF"
                            :options="$this->sourceFinancementOptions"
                            placeholder="Sélectionner une source de financement"
                            search-placeholder="Rechercher une source de financement..."
                            no-results-text="Aucune source de financement trouvée"
                            :allow-clear="true"
                            name="idSF"
                        />
                        @error('idSF')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Année d'acquisition --}}
                    <div>
                        <label for="DateAcquisition" class="block text-sm font-medium text-gray-700 mb-1">
                            Année d'acquisition
                        </label>
                        <input 
                            type="number"
                            id="DateAcquisition"
                            wire:model.defer="DateAcquisition"
                            min="1900"
                            max="{{ now()->year + 1 }}"
                            placeholder="Ex: {{ now()->year }}"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('DateAcquisition') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        @error('DateAcquisition')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Saisissez uniquement l'année (ex: 2024)</p>
                    </div>

                    {{-- Valeur d'acquisition --}}
                    <div>
                        <label for="valeur_acquisition" class="block text-sm font-medium text-gray-700 mb-1">
                            Valeur d'acquisition (MRU)
                        </label>
                        <input 
                            type="number"
                            id="valeur_acquisition"
                            wire:model.defer="valeur_acquisition"
                            min="0"
                            step="0.01"
                            placeholder="Ex: 150000"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('valeur_acquisition') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        @error('valeur_acquisition')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Montant en MRU. Les biens &lt; 50 000 MRU sont comptabilisés en charge.</p>
                    </div>

                    {{-- Date de mise en service --}}
                    <div>
                        <label for="date_mise_en_service" class="block text-sm font-medium text-gray-700 mb-1">
                            Date de mise en service
                        </label>
                        <input 
                            type="date"
                            id="date_mise_en_service"
                            wire:model.defer="date_mise_en_service"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('date_mise_en_service') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        @error('date_mise_en_service')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Date à partir de laquelle l'amortissement commence (prorata temporis).</p>
                    </div>

                    {{-- Quantité (uniquement en mode création) --}}
                    @if(!$this->isEdit)
                    <div>
                        <label for="quantite" class="block text-sm font-medium text-gray-700 mb-1">
                            Quantité <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number"
                            id="quantite"
                            wire:model.defer="quantite"
                            min="1"
                            max="1000"
                            placeholder="1"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('quantite') border-red-300 @enderror"
                            wire:loading.attr="disabled">
                        @error('quantite')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Nombre d'immobilisations identiques à créer. Chaque immobilisation aura un NumOrdre unique.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Boutons d'action (sticky footer) --}}
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 rounded-b-lg shadow-lg -mx-6 -mb-6">
            <div class="flex justify-end gap-3">
                <button 
                    type="button"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Annuler
                </button>
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span wire:loading.remove wire:target="save">
                        {{ $this->isEdit ? 'Modifier' : 'Enregistrer' }}
                    </span>
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

    {{-- Select2 pour les champs de recherche --}}
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser Select2 sur tous les selects avec la classe select2-search
            function initSelect2() {
                $('.select2-search').select2({
                    theme: 'default',
                    width: '100%',
                    placeholder: function() {
                        return $(this).find('option[value=""]').text() || 'Rechercher...';
                    },
                    language: {
                        noResults: function() {
                            return "Aucun résultat trouvé";
                        },
                        searching: function() {
                            return "Recherche en cours...";
                        }
                    },
                    minimumResultsForSearch: 0 // Toujours afficher la recherche
                });

                // Synchroniser Select2 avec Livewire
                $('.select2-search').on('change', function(e) {
                    var select = $(this);
                    var wireModel = select.attr('wire:model') || select.attr('wire:model.defer') || select.attr('wire:model.live');
                    if (wireModel) {
                        var propertyName = wireModel.replace('wire:model.defer=', '').replace('wire:model=', '').replace('wire:model.live=', '');
                        var value = select.val();
                        // Utiliser @this pour mettre à jour la propriété Livewire
                        @this.set(propertyName, value);
                    }
                });
            }

            // Initialiser au chargement
            initSelect2();

            // Réinitialiser après les mises à jour Livewire
            document.addEventListener('livewire:update', function() {
                setTimeout(function() {
                    $('.select2-search').select2('destroy');
                    initSelect2();
                    
                    // Mettre à jour l'état disabled du champ catégorie après mise à jour Livewire
                    var categorieSelect = $('#idCategorie');
                    if (categorieSelect.length) {
                        var isDisabled = categorieSelect.prop('disabled') || categorieSelect.attr('disabled') !== undefined;
                        if (isDisabled) {
                            categorieSelect.addClass('bg-gray-50');
                        } else {
                            categorieSelect.removeClass('bg-gray-50');
                        }
                    }
                }, 100);
            });

            // Réinitialiser après les erreurs de validation
            document.addEventListener('livewire:error', function() {
                setTimeout(function() {
                    $('.select2-search').select2('destroy');
                    initSelect2();
                }, 100);
            });
        });
    </script>
    @endpush
</div>
