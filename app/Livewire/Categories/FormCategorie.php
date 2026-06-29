<?php

namespace App\Livewire\Categories;

use App\Models\Categorie;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]

class FormCategorie extends Component
{
    public $categorie = null;
    public $categorieId = null;

    public $CodeCategorie = '';
    public $Categorie = '';
    public $duree_amortissement = '';
    public $taux_amortissement = '';
    public $type_cgi = '';

    public function mount($categorie = null): void
    {
        if ($categorie instanceof Categorie) {
            $this->categorie = $categorie;
            $this->categorieId = $categorie->idCategorie;
            $this->CodeCategorie = $categorie->CodeCategorie ?? '';
            $this->Categorie = $categorie->Categorie ?? '';
            $this->duree_amortissement = $categorie->duree_amortissement ?? '';
            $this->taux_amortissement = $categorie->taux_amortissement ?? '';
            $this->type_cgi = $categorie->type_cgi ?? '';
        }
    }

    public function getIsEditProperty(): bool
    {
        return $this->categorie !== null;
    }

    // Recalcule le taux quand la durée change
    public function updatedDureeAmortissement($value): void
    {
        if (is_numeric($value) && $value > 0) {
            $this->taux_amortissement = round(100 / $value, 2);
        }
    }

    protected function rules(): array
    {
        return [
            'CodeCategorie'       => 'required|string|max:10|unique:categorie,CodeCategorie,' . ($this->categorieId ?? 'NULL') . ',idCategorie',
            'Categorie'           => 'required|string|max:100',
            'duree_amortissement' => 'required|integer|min:1|max:50',
            'taux_amortissement'  => 'required|numeric|min:0|max:100',
            'type_cgi'            => 'nullable|string|max:100',
        ];
    }

    protected function messages(): array
    {
        return [
            'CodeCategorie.required'       => 'Le code est obligatoire.',
            'CodeCategorie.max'            => 'Le code ne peut pas dépasser 10 caractères.',
            'CodeCategorie.unique'         => 'Ce code est déjà utilisé par une autre catégorie.',
            'Categorie.required'           => 'Le libellé est obligatoire.',
            'duree_amortissement.required' => 'La durée est obligatoire.',
            'duree_amortissement.min'      => 'La durée doit être d\'au moins 1 an.',
            'taux_amortissement.required'  => 'Le taux est obligatoire.',
            'taux_amortissement.min'       => 'Le taux ne peut pas être négatif.',
            'taux_amortissement.max'       => 'Le taux ne peut pas dépasser 100%.',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        try {
            $data = [
                'CodeCategorie'       => strtoupper($validated['CodeCategorie']),
                'Categorie'           => $validated['Categorie'],
                'duree_amortissement' => $validated['duree_amortissement'],
                'taux_amortissement'  => $validated['taux_amortissement'],
                'type_cgi'            => $validated['type_cgi'] ?? null,
            ];

            if ($this->isEdit) {
                $this->categorie->update($data);
                session()->flash('success', 'Catégorie modifiée avec succès.');
            } else {
                Categorie::create($data);
                session()->flash('success', 'Catégorie créée avec succès.');
            }

            return redirect()->route('categories.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('categories.index');
    }

    public function render()
    {
        return view('livewire.categories.form-categorie');
    }
}
