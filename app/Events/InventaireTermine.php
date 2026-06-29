<?php

namespace App\Events;

use App\Models\Inventaire;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un inventaire est terminé
 */
class InventaireTermine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * L'inventaire qui a été terminé
     */
    public Inventaire $inventaire;

    /**
     * Create a new event instance.
     */
    public function __construct(Inventaire $inventaire)
    {
        $this->inventaire = $inventaire;
    }
}

