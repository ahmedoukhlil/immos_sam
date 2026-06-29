<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;

class ListeUsers extends Component
{
    use WithPagination;

    /**
     * Propriétés publiques pour les filtres et la recherche
     */
    public $search = '';
    public $filterRole = 'all'; // all, admin, agent, superuser, immobilisation, stock
    public $sortField = 'users';
    public $sortDirection = 'asc';
    public $perPage = 20;
    public $selectedUsers = [];

    /**
     * Initialisation du composant
     */
    public function mount(): void
    {
        $this->resetPage();
    }

    /**
     * Réinitialiser les filtres
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterRole = 'all';
        $this->selectedUsers = [];
        $this->resetPage();
    }

    /**
     * Trier par champ
     */
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

    /**
     * Toggle sélection d'un utilisateur
     */
    public function toggleSelect($userId): void
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_diff($this->selectedUsers, [$userId]);
        } else {
            $this->selectedUsers[] = $userId;
        }
    }

    /**
     * Sélectionner/désélectionner tous les utilisateurs
     */
    public function toggleSelectAll(): void
    {
        if (count($this->selectedUsers) === $this->getUsersQuery()->count()) {
            $this->selectedUsers = [];
        } else {
            $this->selectedUsers = $this->getUsersQuery()->pluck('idUser')->toArray();
        }
    }

    // Note: La table users n'a pas de colonne 'actif', cette fonctionnalité n'est pas disponible

    /**
     * Supprimer un utilisateur
     */
    public function delete($userId): void
    {
        $user = User::findOrFail($userId);

        // Empêcher la suppression de l'utilisateur connecté
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return;
        }

        // Empêcher de supprimer le dernier admin actif
        if ($user->role === 'admin' && $user->actif) {
            $activeAdminsCount = User::where('role', 'admin')
                ->where('actif', true)
                ->where('id', '!=', $userId)
                ->count();
            
            if ($activeAdminsCount === 0) {
                session()->flash('error', 'Impossible de supprimer le dernier administrateur actif.');
                return;
            }
        }

        $userName = $user->name;
        $user->delete();

        session()->flash('success', "L'utilisateur {$userName} a été supprimé avec succès.");
        $this->resetPage();
    }

    /**
     * Requête de base pour les utilisateurs
     */
    protected function getUsersQuery()
    {
        $query = User::query();

        // Recherche
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('service', 'like', '%' . $this->search . '%')
                  ->orWhere('telephone', 'like', '%' . $this->search . '%');
            });
        }

        // Filtre par rôle
        if ($this->filterRole !== 'all') {
            $query->where('role', $this->filterRole);
        }

        // Note: La table users n'a pas de colonne 'actif', ce filtre a été supprimé

        // Tri
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /**
     * Propriété calculée : Liste paginée des utilisateurs
     */
    public function getUsersProperty()
    {
        return $this->getUsersQuery()->paginate($this->perPage);
    }

    /**
     * Propriété calculée : Statistiques
     */
    public function getStatsProperty()
    {
        return [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'agents' => User::where('role', 'agent')->count(),
        ];
    }

    /**
     * Render du composant
     */
    public function render()
    {
        return view('livewire.users.liste-users', [
            'users' => $this->users,
            'stats' => $this->stats,
        ]);
    }
}

