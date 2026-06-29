<?php

namespace App\Livewire\Inventaires;

use App\Models\Inventaire;
use App\Models\InventaireScan;
use App\Services\InventaireService;
use App\Services\RapportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RapportInventaire extends Component
{
    /**
     * Instance de l'inventaire
     */
    public Inventaire $inventaire;

    /**
     * Onglet actif
     */
    public $activeTab = 'resume';

    /**
     * Filtres pour les tableaux
     */
    public $filterEmplacement = 'all';
    public $filterStatut = 'all';
    public $showPhotos = false;

    /**
     * Initialisation du composant
     */
    public function mount(Inventaire $inventaire): void
    {
        // Vérifier que l'inventaire est terminé ou clôturé
        if (!in_array($inventaire->statut, ['termine', 'cloture'])) {
            session()->flash('error', 'Le rapport n\'est disponible que pour les inventaires terminés ou clôturés.');
            redirect()->route('inventaires.show', $inventaire);
            return;
        }

        // Eager load des relations nécessaires
        $this->inventaire = $inventaire->load([
            'creator',
            'closer',
            'inventaireLocalisations.localisation',
            'inventaireLocalisations.agent',
            'inventaireScans.bien.designation',
            'inventaireScans.bien.categorie',
            'inventaireScans.bien.emplacement.localisation',
            'inventaireScans.localisationReelle',
            'inventaireScans.agent',
        ]);
    }

    /**
     * Propriété calculée : Retourne les statistiques complètes
     */
    public function getStatistiquesProperty(): array
    {
        $service = app(InventaireService::class);
        return $service->calculerStatistiques($this->inventaire);
    }

    /**
     * Propriété calculée : Détail des immobilisations par emplacement
     */
    public function getDetailParEmplacementProperty(): array
    {
        return app(RapportService::class)->getDetailParEmplacement($this->inventaire);
    }

    /**
     * Propriété calculée : Retourne les biens présents (compatible PWA: gesimmo)
     */
    public function getBiensPresentsProperty()
    {
        $query = $this->inventaire->inventaireScans()
            ->where('statut_scan', 'present')
            ->with(['bien.designation', 'bien.emplacement.localisation', 'agent']);

        if ($this->filterEmplacement !== 'all') {
            $query->whereHas('bien', fn ($q) => $q->where('idEmplacement', $this->filterEmplacement));
        }

        return $query->orderBy('date_scan', 'desc')->get();
    }

    /**
     * Propriété calculée : Retourne les biens déplacés (compatible PWA: gesimmo)
     */
    public function getBiensDeplacesProperty()
    {
        $query = $this->inventaire->inventaireScans()
            ->where('statut_scan', 'deplace')
            ->with(['bien.emplacement.localisation', 'bien.designation', 'localisationReelle', 'agent']);

        if ($this->filterEmplacement !== 'all') {
            $query->whereHas('bien', fn ($q) => $q->where('idEmplacement', $this->filterEmplacement));
        }

        return $query->orderBy('date_scan', 'desc')->get();
    }

    /**
     * Propriété calculée : Retourne les biens absents (compatible PWA: gesimmo)
     */
    public function getBiensAbsentsProperty()
    {
        $query = $this->inventaire->inventaireScans()
            ->where('statut_scan', 'absent')
            ->with(['bien.emplacement.localisation', 'bien.designation', 'agent']);

        if ($this->filterEmplacement !== 'all') {
            $query->whereHas('bien', fn ($q) => $q->where('idEmplacement', $this->filterEmplacement));
        }

        return $query->orderBy('date_scan', 'desc')->get();
    }

    /**
     * Propriété calculée : Retourne les biens détériorés (compatible PWA: gesimmo)
     */
    public function getBiensDeterioresProperty()
    {
        return $this->inventaire->inventaireScans()
            ->where('statut_scan', 'deteriore')
            ->with(['bien.designation', 'bien.emplacement.localisation', 'agent'])
            ->orderBy('date_scan', 'desc')
            ->get();
    }

    /**
     * Propriété calculée : Retourne les biens défectueux (etat_constate = defectueux, signalés via PWA)
     */
    public function getBiensDefectueuxProperty()
    {
        $query = $this->inventaire->inventaireScans()
            ->where('etat_constate', 'defectueux')
            ->with(['bien.designation', 'bien.emplacement.localisation', 'localisationReelle', 'agent']);

        if ($this->filterEmplacement !== 'all') {
            $query->whereHas('bien', fn ($q) => $q->where('idEmplacement', $this->filterEmplacement));
        }

        return $query->orderBy('date_scan', 'desc')->get();
    }

    /**
     * Propriété calculée : Retourne les biens non scannés
     */
    public function getBiensNonScannesProperty()
    {
        // Récupérer tous les biens attendus dans les localisations inventoriées
        $localisationIds = $this->inventaire->inventaireLocalisations()
            ->pluck('localisation_id')
            ->toArray();

        // Emplacements liés à ces localisations
        $emplacementIds = \App\Models\Emplacement::whereIn('idLocalisation', $localisationIds)
            ->pluck('idEmplacement')
            ->toArray();

        // Récupérer les NumOrdre déjà scannés
        $biensScannesIds = $this->inventaire->inventaireScans()
            ->pluck('bien_id')
            ->toArray();

        // Biens Gesimmo attendus mais non scannés
        return \App\Models\Gesimmo::whereIn('idEmplacement', $emplacementIds)
            ->whereNotIn('NumOrdre', $biensScannesIds)
            ->with(['designation', 'categorie', 'emplacement.localisation'])
            ->orderBy('NumOrdre')
            ->get();
    }

    /**
     * Propriété calculée : Retourne les anomalies détectées
     */
    public function getAnomaliesProperty(): array
    {
        $service = app(InventaireService::class);
        return $service->detecterAnomalies($this->inventaire);
    }

    /**
     * Change l'onglet actif
     */
    public function setActiveTab($tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Exporte le rapport en PDF
     * Redirection vers la route d'export (évite que Livewire tente de sérialiser le PDF en JSON)
     */
    public function exportPDF()
    {
        return redirect()->route('inventaires.export-pdf', $this->inventaire);
    }

    /**
     * Exporte le rapport en Excel
     */
    public function exportExcel()
    {
        try {
            $service = app(InventaireService::class);
            $filePath = $service->genererRapportExcel($this->inventaire);
            
            return response()->download(storage_path('app/public/' . $filePath));
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la génération de l\'Excel: ' . $e->getMessage());
        }
    }

    /**
     * Ouvre le dialogue d'impression
     */
    public function imprimerRapport(): void
    {
        $this->dispatch('print-report');
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.inventaires.rapport-inventaire');
    }
}

