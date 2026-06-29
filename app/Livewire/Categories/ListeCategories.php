<?php

namespace App\Livewire\Categories;

use App\Models\Categorie;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]

class ListeCategories extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'Categorie';
    public $sortDirection = 'asc';
    public $perPage = 20;

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function deleteCategorie($categorieId): void
    {
        $categorie = Categorie::withCount('immobilisations')->find($categorieId);

        if (!$categorie) {
            session()->flash('error', 'Catégorie introuvable.');
            return;
        }

        if ($categorie->immobilisations_count > 0) {
            session()->flash('error', "Impossible de supprimer : {$categorie->immobilisations_count} immobilisation(s) utilisent cette catégorie.");
            return;
        }

        if ($categorie->designations()->count() > 0) {
            session()->flash('error', 'Impossible de supprimer : des désignations utilisent cette catégorie.');
            return;
        }

        $categorie->delete();
        session()->flash('success', 'Catégorie supprimée avec succès.');
    }

    public function render()
    {
        $query = Categorie::withCount(['immobilisations', 'designations']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('Categorie', 'like', '%' . $this->search . '%')
                  ->orWhere('CodeCategorie', 'like', '%' . $this->search . '%')
                  ->orWhere('type_cgi', 'like', '%' . $this->search . '%');
            });
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $categories = $query->paginate($this->perPage);
        $total = Categorie::count();

        return view('livewire.categories.liste-categories', [
            'categories' => $categories,
            'total' => $total,
        ]);
    }
}
