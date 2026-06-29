<?php

namespace App\Livewire\Biens;

use App\Models\Gesimmo;
use App\Models\Categorie;
use App\Services\AmortissementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ListeAmortissements extends Component
{
    use WithPagination;

    public $filterCategorie = '';
    public $filterExercice = '';
    public $sortField = 'NumOrdre';
    public $sortDirection = 'asc';
    public $perPage = 20;
    protected ?bool $amortissementColumnsAvailable = null;

    protected $queryString = [
        'filterCategorie' => ['except' => ''],
        'filterExercice' => ['except' => ''],
    ];

    public function updatingFilterCategorie()
    {
        $this->resetPage();
    }

    public function updatingFilterExercice()
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->filterCategorie = '';
        $this->filterExercice = '';
        $this->resetPage();
    }

    private function hasAmortissementColumns(): bool
    {
        if ($this->amortissementColumnsAvailable !== null) {
            return $this->amortissementColumnsAvailable;
        }

        $this->amortissementColumnsAvailable = Schema::hasColumns('gesimmo', [
            'valeur_acquisition',
            'date_mise_en_service',
        ]);

        return $this->amortissementColumnsAvailable;
    }

    public function getCategoriesProperty()
    {
        return Categorie::whereNotNull('duree_amortissement')
            ->orderBy('Categorie')
            ->get();
    }

    /**
     * Génère la liste des exercices basée sur les données réelles :
     * de la plus ancienne mise en service jusqu'à la fin du dernier amortissement prévu.
     */
    public function getExercicesProperty(): array
    {
        $anneeActuelle = now()->year;

        if (!$this->hasAmortissementColumns()) {
            return [$anneeActuelle];
        }

        $premiereMiseEnService = Gesimmo::whereNotNull('date_mise_en_service')
            ->whereNotNull('valeur_acquisition')
            ->where('valeur_acquisition', '>=', AmortissementService::SEUIL_AMORTISSEMENT)
            ->min('date_mise_en_service');

        if (!$premiereMiseEnService) {
            return [$anneeActuelle];
        }

        $anneeDebut = (int) date('Y', strtotime($premiereMiseEnService));

        $dureeMax = Categorie::whereNotNull('duree_amortissement')->max('duree_amortissement') ?? 10;

        $derniereAcquisition = Gesimmo::whereNotNull('date_mise_en_service')
            ->whereNotNull('valeur_acquisition')
            ->where('valeur_acquisition', '>=', AmortissementService::SEUIL_AMORTISSEMENT)
            ->max('date_mise_en_service');

        $anneeFin = (int) date('Y', strtotime($derniereAcquisition)) + $dureeMax;
        $anneeFin = max($anneeFin, $anneeActuelle);

        $exercices = [];
        for ($i = $anneeFin; $i >= $anneeDebut; $i--) {
            $exercices[] = $i;
        }

        return $exercices;
    }

    /**
     * Calcule les totaux globaux pour les biens amortissables affichés.
     */
    public function getTotauxProperty(): array
    {
        $exercice = !empty($this->filterExercice) ? (int) $this->filterExercice : now()->year;

        if (!$this->hasAmortissementColumns()) {
            return [
                'total_valeur' => 0,
                'total_dotation' => 0,
                'total_vnc' => 0,
                'exercice' => $exercice,
            ];
        }

        $query = Gesimmo::with('categorie')
            ->whereNotNull('valeur_acquisition')
            ->where('valeur_acquisition', '>=', AmortissementService::SEUIL_AMORTISSEMENT)
            ->whereNotNull('date_mise_en_service');

        if (!empty($this->filterCategorie)) {
            $query->where('idCategorie', $this->filterCategorie);
        }

        $biens = $query->get();
        $service = app(AmortissementService::class);

        $totalValeur = 0;
        $totalDotation = 0;
        $totalVNC = 0;

        foreach ($biens as $bien) {
            if (!$service->isAmortissable($bien)) {
                continue;
            }

            $totalValeur += (float) $bien->valeur_acquisition;

            $tableau = $service->calculerTableau($bien);
            if ($tableau) {
                $dotationExercice = 0;
                $vncExercice = (float) $bien->valeur_acquisition;

                foreach ($tableau['lignes'] as $ligne) {
                    if ($ligne['exercice'] == $exercice) {
                        $dotationExercice = $ligne['dotation'];
                    }
                    if ($ligne['exercice'] <= $exercice) {
                        $vncExercice = $ligne['vnc'];
                    }
                }

                $totalDotation += $dotationExercice;
                $totalVNC += $vncExercice;
            }
        }

        return [
            'total_valeur' => $totalValeur,
            'total_dotation' => $totalDotation,
            'total_vnc' => $totalVNC,
            'exercice' => $exercice,
        ];
    }

    public function render()
    {
        $exercice = !empty($this->filterExercice) ? (int) $this->filterExercice : now()->year;
        $amortissementUnavailable = !$this->hasAmortissementColumns();

        if ($amortissementUnavailable) {
            $biens = new LengthAwarePaginator(
                [],
                0,
                $this->perPage,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => request()->url(), 'pageName' => 'page']
            );

            return view('livewire.biens.liste-amortissements', [
                'biens' => $biens,
                'exercice' => $exercice,
                'amortissementUnavailable' => true,
            ]);
        }

        $query = Gesimmo::with(['designation', 'categorie'])
            ->whereNotNull('valeur_acquisition')
            ->where('valeur_acquisition', '>=', AmortissementService::SEUIL_AMORTISSEMENT)
            ->whereNotNull('date_mise_en_service');

        if (!empty($this->filterCategorie)) {
            $query->where('idCategorie', $this->filterCategorie);
        }

        $biens = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $service = app(AmortissementService::class);

        $biensAvecAmortissement = $biens->through(function ($bien) use ($service, $exercice) {
            $tableau = $service->calculerTableau($bien);
            $dotationExercice = 0;
            $cumulExercice = 0;
            $vncExercice = (float) $bien->valeur_acquisition;

            if ($tableau) {
                foreach ($tableau['lignes'] as $ligne) {
                    if ($ligne['exercice'] == $exercice) {
                        $dotationExercice = $ligne['dotation'];
                    }
                    if ($ligne['exercice'] <= $exercice) {
                        $cumulExercice = $ligne['cumul'];
                        $vncExercice = $ligne['vnc'];
                    }
                }
            }

            $bien->dotation_exercice = $dotationExercice;
            $bien->cumul_exercice = $cumulExercice;
            $bien->vnc_exercice = $vncExercice;

            return $bien;
        });

        return view('livewire.biens.liste-amortissements', [
            'biens' => $biensAvecAmortissement,
            'exercice' => $exercice,
            'amortissementUnavailable' => false,
        ]);
    }
}
