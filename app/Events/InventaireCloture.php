<?php

namespace App\Events;

use App\Models\Inventaire;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un inventaire est clôturé définitivement
 */
class InventaireCloture
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * L'inventaire qui a été clôturé
     */
    public Inventaire $inventaire;

    /**
     * L'utilisateur qui a clôturé l'inventaire
     */
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Inventaire $inventaire, User $user)
    {
        $this->inventaire = $inventaire;
        $this->user = $user;
    }
}

