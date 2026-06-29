<?php

namespace App\Livewire\Biens;

use App\Models\Gesimmo;
use App\Models\LocalisationImmo;
use App\Models\Affectation;
use App\Models\Emplacement;
use App\Models\HistoriqueTransfert;
use App\Livewire\Traits\WithCachedOptions;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app')]
class TransfertBien extends Component
{
    use WithCachedOptions;

    // Sélection des immobilisations (multiple)
    public $bienIds = []; // Tableau d'IDs sélectionnés
    public $biensSelectionnes = []; // Tableau d'objets Gesimmo chargés
    public $searchBien = ''; // Recherche dynamique pour les immobilisations
    public $raison = ''; // Raison du transfert (optionnel)

    // Nouveau emplacement (hiérarchique)
    public $idLocalisation = '';
    public $idAffectation = '';
    public $idEmplacement = '';

    /**
     * Vérification des permissions
     */
    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->canManageInventaire()) {
            abort(403, 'Accès non autorisé.');
        }
    }

    /**
     * Ajouter une immobilisation à la sélection
     */
    public function ajouterBien($bienId)
    {
        $bienId = (int)$bienId;
        
        if (!in_array($bienId, $this->bienIds)) {
            $this->bienIds[] = $bienId;
            $this->chargerBiensSelectionnes();
        }
    }

    /**
     * Retirer une immobilisation de la sélection
     */
    public function retirerBien($bienId)
    {
        $this->bienIds = array_values(array_filter($this->bienIds, function($id) use ($bienId) {
            return $id != $bienId;
        }));
        $this->chargerBiensSelectionnes();
    }

    /**
     * Mise à jour automatique quand bienIds change
     */
    public function updatedBienIds()
    {
        $this->chargerBiensSelectionnes();
    }

    /**
     * Charger les détails des immobilisations sélectionnées
     */
    public function chargerBiensSelectionnes()
    {
        if (empty($this->bienIds)) {
            $this->biensSelectionnes = [];
            return;
        }

        $biens = Gesimmo::with([
            'designation',
            'emplacement.affectation.localisation'
        ])
        ->whereIn('NumOrdre', $this->bienIds)
        ->get();

        // Convertir en tableau associatif avec NumOrdre comme clé
        $this->biensSelectionnes = [];
        foreach ($biens as $bien) {
            $this->biensSelectionnes[$bien->NumOrdre] = [
                'NumOrdre' => $bien->NumOrdre,
                'designation' => $bien->designation ? $bien->designation->designation : 'N/A',
                'emplacement' => $bien->emplacement ? $bien->emplacement->Emplacement : 'Sans emplacement',
            ];
        }
    }

    /**
     * Réagit au changement de localisation
     */
    public function updatedIdLocalisation($value)
    {
        if (empty($value)) {
            $this->idAffectation = '';
            $this->idEmplacement = '';
        } else {
            if (!empty($this->idAffectation)) {
                $affectation = Affectation::find($this->idAffectation);
                if (!$affectation || $affectation->idLocalisation != $value) {
                    $this->idAffectation = '';
                    $this->idEmplacement = '';
                }
            } else {
                $this->idEmplacement = '';
            }
        }
    }

    /**
     * Réagit au changement d'affectation
     */
    public function updatedIdAffectation($value)
    {
        if (empty($value)) {
            $this->idEmplacement = '';
        } else {
            if (!empty($this->idEmplacement)) {
                $emplacement = Emplacement::find($this->idEmplacement);
                if (!$emplacement || $emplacement->idAffectation != $value) {
                    $this->idEmplacement = '';
                }
            }
        }
    }

    /**
     * Options pour le select Immobilisations (recherche dynamique)
     */
    public function getBienOptionsProperty()
    {
        $query = Gesimmo::with(['designation', 'categorie', 'emplacement.affectation.localisation'])
            ->orderBy('NumOrdre');

        // Si une recherche est en cours, filtrer les résultats
        if (!empty($this->searchBien)) {
            $search = trim($this->searchBien);
            
            // Si la recherche est un nombre, prioriser la recherche exacte par NumOrdre
            if (is_numeric($search)) {
                $query->where(function ($q) use ($search) {
                    // Recherche exacte en priorité
                    $q->where('NumOrdre', '=', (int)$search)
                        // Puis recherche partielle sur NumOrdre
                        ->orWhere('NumOrdre', 'like', '%' . $search . '%')
                        // Et aussi sur les autres champs
                        ->orWhereHas('designation', function ($q2) use ($search) {
                            $q2->where('designation', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement', function ($q2) use ($search) {
                            $q2->where('Emplacement', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement.affectation.localisation', function ($q2) use ($search) {
                            $q2->where('Localisation', 'like', '%' . $search . '%');
                        });
                });
                // Trier pour mettre les résultats exacts en premier
                $query->orderByRaw("CASE WHEN NumOrdre = ? THEN 0 ELSE 1 END", [(int)$search]);
            } else {
                // Recherche textuelle
                $query->where(function ($q) use ($search) {
                    $q->where('NumOrdre', 'like', '%' . $search . '%')
                        ->orWhereHas('designation', function ($q2) use ($search) {
                            $q2->where('designation', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement', function ($q2) use ($search) {
                            $q2->where('Emplacement', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement.affectation.localisation', function ($q2) use ($search) {
                            $q2->where('Localisation', 'like', '%' . $search . '%');
                        });
                });
            }
        } else {
            $query->limit(50);
        }

        $biens = $query->limit(100)->get();

        // Ajouter les biens déjà sélectionnés s'ils ne sont pas dans les résultats
        if (!empty($this->bienIds)) {
            $biensManquants = Gesimmo::with(['designation', 'categorie', 'emplacement.affectation.localisation'])
                ->whereIn('NumOrdre', $this->bienIds)
                ->whereNotIn('NumOrdre', $biens->pluck('NumOrdre'))
                ->get();
            $biens = $biens->merge($biensManquants);
        }

        return $biens->map(function ($bien) {
            $designation = $bien->designation ? $bien->designation->designation : 'N/A';
            $emplacement = $bien->emplacement ? $bien->emplacement->Emplacement : 'Sans emplacement';
            $affectation = $bien->emplacement && $bien->emplacement->affectation
                ? $bien->emplacement->affectation->Affectation
                : 'N/A';
            $localisation = $bien->emplacement && $bien->emplacement->affectation && $bien->emplacement->affectation->localisation
                ? $bien->emplacement->affectation->localisation->Localisation
                : 'N/A';
            
            $estSelectionne = in_array($bien->NumOrdre, $this->bienIds);
            
            return [
                'value' => (string)$bien->NumOrdre,
                'text' => "{$designation} (Ordre: {$bien->NumOrdre}) - {$emplacement} [{$localisation}]",
                'selected' => $estSelectionne,
                // Informations supplémentaires pour l'affichage
                'numOrdre' => $bien->NumOrdre,
                'designation' => $designation,
                'emplacement' => $emplacement,
                'affectation' => $affectation,
                'localisation' => $localisation,
            ];
        })->toArray();
    }

    /**
     * Options pour le select Localisations
     */
    public function getLocalisationOptionsProperty()
    {
        return $this->getCachedLocalisationOptions(300, true);
    }

    /**
     * Options pour le select Affectations
     */
    public function getAffectationOptionsProperty()
    {
        $options = [[
            'value' => '',
            'text' => 'Sélectionner une affectation',
        ]];

        if (empty($this->idLocalisation)) {
            return $options;
        }

        $affectations = $this->getCachedAffectationOptions($this->idLocalisation, 300);
        return array_merge($options, $affectations);
    }

    /**
     * Options pour le select Emplacements
     */
    public function getEmplacementOptionsProperty()
    {
        $options = [[
            'value' => '',
            'text' => 'Sélectionner un emplacement',
        ]];

        if (empty($this->idAffectation)) {
            return $options;
        }

        $emplacements = $this->getCachedEmplacementOptions(
            $this->idLocalisation,
            $this->idAffectation,
            300,
            true
        );
        return array_merge($options, $emplacements);
    }

    /**
     * Règles de validation
     */
    protected function rules()
    {
        return [
            'bienIds' => 'required|array|min:1',
            'bienIds.*' => 'required|exists:gesimmo,NumOrdre',
            'idLocalisation' => 'required|exists:localisation,idLocalisation',
            'idAffectation' => 'required|exists:affectation,idAffectation',
            'idEmplacement' => 'required|exists:emplacement,idEmplacement',
            'raison' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    protected function messages()
    {
        return [
            'bienIds.required' => 'Veuillez sélectionner au moins une immobilisation.',
            'bienIds.min' => 'Veuillez sélectionner au moins une immobilisation.',
            'idLocalisation.required' => 'La localisation de destination est obligatoire.',
            'idAffectation.required' => 'L\'affectation de destination est obligatoire.',
            'idEmplacement.required' => 'L\'emplacement de destination est obligatoire.',
        ];
    }

    /**
     * Effectuer le transfert groupé avec historique
     */
    public function transferer()
    {
        $validated = $this->validate();

        // Vérifier que l'emplacement appartient bien à l'affectation et la localisation
        $emplacement = Emplacement::with(['affectation.localisation'])
            ->find($validated['idEmplacement']);

        if (!$emplacement) {
            session()->flash('error', 'Emplacement introuvable.');
            return;
        }

        if ($emplacement->idAffectation != $validated['idAffectation']) {
            session()->flash('error', 'L\'emplacement ne correspond pas à l\'affectation sélectionnée.');
            return;
        }

        if ($emplacement->affectation->idLocalisation != $validated['idLocalisation']) {
            session()->flash('error', 'L\'affectation ne correspond pas à la localisation sélectionnée.');
            return;
        }

        // Générer un ID de groupe pour ce transfert
        $groupeId = 'TRF-' . date('YmdHis') . '-' . Str::random(6);

        // Informations du nouvel emplacement
        $nouveauEmplacementLibelle = $emplacement->Emplacement;
        $nouvelleAffectationLibelle = $emplacement->affectation->Affectation ?? 'N/A';
        $nouvelleLocalisationLibelle = $emplacement->affectation->localisation->Localisation ?? 'N/A';

        // Utiliser une transaction pour garantir la cohérence
        DB::beginTransaction();
        try {
            $transfertsReussis = 0;
            $transfertsEchoues = 0;

            foreach ($validated['bienIds'] as $bienId) {
                $bien = Gesimmo::with(['emplacement.affectation.localisation'])
                    ->find($bienId);

                if (!$bien) {
                    $transfertsEchoues++;
                    continue;
                }

                // Vérifier que le nouvel emplacement est différent
                if ($bien->idEmplacement == $validated['idEmplacement']) {
                    $transfertsEchoues++;
                    continue;
                }

                // Sauvegarder l'ancien emplacement
                $ancienEmplacement = $bien->emplacement;
                $ancienEmplacementLibelle = $ancienEmplacement ? $ancienEmplacement->Emplacement : 'Sans emplacement';
                $ancienneAffectationLibelle = $ancienEmplacement && $ancienEmplacement->affectation 
                    ? $ancienEmplacement->affectation->Affectation 
                    : 'N/A';
                $ancienneLocalisationLibelle = $ancienEmplacement && $ancienEmplacement->affectation && $ancienEmplacement->affectation->localisation
                    ? $ancienEmplacement->affectation->localisation->Localisation
                    : 'N/A';

                // Effectuer le transfert
                $bien->idEmplacement = $validated['idEmplacement'];
                $bien->save();

                // Enregistrer dans l'historique
                HistoriqueTransfert::create([
                    'NumOrdre' => $bien->NumOrdre,
                    'ancien_idEmplacement' => $ancienEmplacement ? $ancienEmplacement->idEmplacement : null,
                    'nouveau_idEmplacement' => $validated['idEmplacement'],
                    'ancien_emplacement_libelle' => $ancienEmplacementLibelle,
                    'nouveau_emplacement_libelle' => $nouveauEmplacementLibelle,
                    'ancien_affectation_libelle' => $ancienneAffectationLibelle,
                    'nouveau_affectation_libelle' => $nouvelleAffectationLibelle,
                    'ancien_localisation_libelle' => $ancienneLocalisationLibelle,
                    'nouveau_localisation_libelle' => $nouvelleLocalisationLibelle,
                    'transfert_par' => auth()->user()->idUser,
                    'date_transfert' => now(),
                    'raison' => $validated['raison'] ?? null,
                    'groupe_transfert_id' => $groupeId,
                ]);

                $transfertsReussis++;
            }

            DB::commit();

            // Message de succès
            $message = "Transfert effectué : {$transfertsReussis} immobilisation(s) transférée(s) vers '{$nouveauEmplacementLibelle}'.";
            if ($transfertsEchoues > 0) {
                $message .= " {$transfertsEchoues} transfert(s) échoué(s) (déjà dans cet emplacement ou introuvable).";
            }
            session()->flash('success', $message);

            // Réinitialiser le formulaire
            $this->reset(['bienIds', 'biensSelectionnes', 'idLocalisation', 'idAffectation', 'idEmplacement', 'searchBien', 'raison']);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors du transfert : ' . $e->getMessage());
        }
    }

    /**
     * Annuler et retourner à la liste
     */
    public function cancel()
    {
        return redirect()->route('biens.index');
    }

    public function render()
    {
        return view('livewire.biens.transfert-bien');
    }
}
