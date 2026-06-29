<?php

namespace App\Livewire\Emplacements;

use App\Models\Emplacement;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]

class DetailEmplacement extends Component
{
    public Emplacement $emplacement;

    /**
     * Initialisation du composant
     */
    public function mount(Emplacement $emplacement): void
    {
        $this->emplacement = $emplacement->load([
            'localisation',
            'affectation',
            'immobilisations.designation',
            'immobilisations.categorie',
            'immobilisations.etat',
        ]);
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.emplacements.detail-emplacement');
    }
}
