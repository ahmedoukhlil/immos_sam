<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Profil extends Component
{
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => 'required|string|min:1',
            'new_password' => 'required|string|min:4|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'new_password.required' => 'Le nouveau mot de passe est obligatoire.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 4 caractères.',
            'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    public function updatePassword(): void
    {
        $this->validate();

        $user = Auth::user();
        $storedPassword = $user->mdp;

        // Vérifier le mot de passe actuel (hashé ou en clair)
        if (str_starts_with($storedPassword, '$2y$')) {
            $passwordValid = Hash::check($this->current_password, $storedPassword);
        } else {
            $passwordValid = ($this->current_password === $storedPassword);
        }

        if (!$passwordValid) {
            $this->addError('current_password', 'Le mot de passe actuel est incorrect.');
            return;
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if ($this->current_password === $this->new_password) {
            $this->addError('new_password', 'Le nouveau mot de passe doit être différent de l\'ancien.');
            return;
        }

        // Sauvegarder le nouveau mot de passe hashé
        $user->mdp = Hash::make($this->new_password);
        $user->save();

        // Réinitialiser les champs
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Mot de passe modifié avec succès.');
    }

    public function render()
    {
        return view('livewire.profil');
    }
}
