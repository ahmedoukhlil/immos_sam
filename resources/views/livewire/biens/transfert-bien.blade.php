<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Transfert d'immobilisations</h1>
                    <p class="text-gray-500 mt-1">Déplacer une ou plusieurs immobilisations vers un nouvel emplacement</p>
                </div>
                <a href="{{ route('biens.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour à la liste
                </a>
            </div>
        </div>

        <!-- Messages Flash -->
        @if (session()->has('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="transferer">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Colonne gauche : Sélection des immobilisations -->
                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900">1. Sélectionner les immobilisations</h2>
                    
                    <!-- Champ de recherche -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Rechercher une immobilisation
                        </label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchBien"
                            placeholder="Rechercher par NumOrdre (ex: 1001), désignation, emplacement ou localisation..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        @if(!empty($searchBien))
                            <p class="mt-1 text-xs text-gray-500">
                                Recherche: "{{ $searchBien }}" - {{ count($this->bienOptions) }} résultat(s) trouvé(s)
                            </p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">
                                Tapez pour rechercher (limite: 100 résultats). Affichage des 50 premiers par défaut.
                            </p>
                        @endif
                    </div>

                    <!-- Liste des immobilisations disponibles -->
                    <div class="border border-gray-200 rounded-lg" style="max-height: 400px; overflow-y: auto;">
                        @forelse($this->bienOptions as $option)
                            @php
                                $estSelectionne = in_array($option['value'], $bienIds);
                                // Utiliser les informations supplémentaires si disponibles
                                $numOrdre = $option['numOrdre'] ?? $option['value'];
                                $designation = $option['designation'] ?? 'N/A';
                                $emplacement = $option['emplacement'] ?? 'Sans emplacement';
                                $affectation = $option['affectation'] ?? 'N/A';
                                $localisation = $option['localisation'] ?? 'N/A';
                            @endphp
                            <div class="p-3 border-b border-gray-100 hover:bg-gray-50 transition-colors {{ $estSelectionne ? 'bg-indigo-50 border-indigo-200' : '' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <!-- NumOrdre en évidence -->
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs font-bold">
                                                Ordre: {{ $numOrdre }}
                                            </span>
                                        </div>
                                        <!-- Désignation (nom) -->
                                        <p class="text-sm font-semibold text-gray-900 mb-2">
                                            {{ $designation }}
                                        </p>
                                        <!-- Localisation, Affectation et Emplacement -->
                                        <div class="space-y-1 text-xs text-gray-600">
                                            @if($localisation != 'N/A')
                                                <p class="flex items-center">
                                                    <span class="mr-1">🏢</span>
                                                    <span class="font-medium">Localisation:</span>
                                                    <span class="ml-1">{{ $localisation }}</span>
                                                </p>
                                            @endif
                                            @if($affectation != 'N/A')
                                                <p class="flex items-center">
                                                    <span class="mr-1">🏛️</span>
                                                    <span class="font-medium">Affectation:</span>
                                                    <span class="ml-1">{{ $affectation }}</span>
                                                </p>
                                            @endif
                                            <p class="flex items-center">
                                                <span class="mr-1">📍</span>
                                                <span class="font-medium">Emplacement:</span>
                                                <span class="ml-1">{{ $emplacement }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    @if($estSelectionne)
                                        <button 
                                            type="button"
                                            wire:click="retirerBien({{ $option['value'] }})"
                                            class="ml-3 px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium transition-colors">
                                            Retirer
                                        </button>
                                    @else
                                        <button 
                                            type="button"
                                            wire:click="ajouterBien({{ $option['value'] }})"
                                            class="ml-3 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 text-sm font-medium transition-colors">
                                            Ajouter
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <p>Aucune immobilisation trouvée</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Liste des immobilisations sélectionnées -->
                    @if(!empty($bienIds))
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-indigo-900 mb-2">
                                Immobilisations sélectionnées ({{ count($bienIds) }})
                            </h3>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach($biensSelectionnes as $bien)
                                    <div class="flex items-center justify-between bg-white rounded p-2 text-sm">
                                        <span class="font-medium text-gray-900">
                                            Ordre: {{ $bien['NumOrdre'] }}
                                        </span>
                                        <button 
                                            type="button"
                                            wire:click="retirerBien({{ $bien['NumOrdre'] }})"
                                            class="text-red-600 hover:text-red-800">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @error('bienIds')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Colonne droite : Nouvel emplacement -->
                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900">2. Nouvel emplacement</h2>

                    <!-- Localisation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Localisation <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:model.live="idLocalisation"
                            :options="$this->localisationOptions"
                            placeholder="Sélectionner une localisation"
                            search-placeholder="Rechercher une localisation..."
                            no-results-text="Aucune localisation trouvée"
                            :key="'localisation-select-transfer'"
                        />
                        @error('idLocalisation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Affectation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Affectation <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:model.live="idAffectation"
                            :options="$this->affectationOptions"
                            placeholder="Sélectionner une affectation"
                            search-placeholder="Rechercher une affectation..."
                            no-results-text="Aucune affectation trouvée"
                            :disabled="empty($idLocalisation)"
                            :container-class="empty($idLocalisation) && !empty($idAffectation) ? 'ring-2 ring-yellow-300' : ''"
                            :key="'affectation-select-transfer-' . ($idLocalisation ?? 'none')"
                        />
                        @if(empty($idLocalisation) && !empty($idAffectation))
                            <p class="mt-1 text-xs text-yellow-600">
                                Sélectionnez d'abord une localisation
                            </p>
                        @endif
                        @error('idAffectation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Emplacement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Emplacement <span class="text-red-500">*</span>
                        </label>
                        <livewire:components.searchable-select
                            wire:model.live="idEmplacement"
                            :options="$this->emplacementOptions"
                            placeholder="Sélectionner un emplacement"
                            search-placeholder="Rechercher un emplacement..."
                            no-results-text="Aucun emplacement trouvé"
                            :disabled="empty($idAffectation)"
                            :container-class="empty($idAffectation) && !empty($idEmplacement) ? 'ring-2 ring-yellow-300' : ''"
                            :key="'emplacement-select-transfer-' . ($idLocalisation ?? 'none') . '-' . ($idAffectation ?? 'none')"
                        />
                        @if(empty($idAffectation) && !empty($idEmplacement))
                            <p class="mt-1 text-xs text-yellow-600">
                                Sélectionnez d'abord une affectation
                            </p>
                        @endif
                        @error('idEmplacement')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Raison du transfert (optionnel) -->
                    <div>
                        <label for="raison" class="block text-sm font-medium text-gray-700 mb-1">
                            Raison du transfert (optionnel)
                        </label>
                        <textarea 
                            id="raison"
                            wire:model="raison" 
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Ex: Réorganisation, déménagement, correction..."></textarea>
                        @error('raison')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Résumé -->
                    @if(!empty($bienIds) && !empty($idEmplacement))
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-green-900 mb-2">Résumé du transfert</h3>
                            <ul class="text-sm text-green-800 space-y-1">
                                <li>• <strong>{{ count($bienIds) }}</strong> immobilisation(s) à transférer</li>
                                <li>• Vers: <strong>{{ $this->emplacementOptions[array_search($idEmplacement, array_column($this->emplacementOptions, 'value'))]['text'] ?? 'N/A' }}</strong></li>
                            </ul>
                        </div>
                    @endif

                    <!-- Boutons d'action -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                        <button type="button" 
                                wire:click="cancel"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center"
                                :disabled="empty($bienIds) || empty($idEmplacement)">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Effectuer le transfert ({{ count($bienIds) }})
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
