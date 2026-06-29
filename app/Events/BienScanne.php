<?php

namespace App\Events;

use App\Models\InventaireScan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un bien est scanné
 */
class BienScanne
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Le scan qui a été enregistré
     */
    public InventaireScan $scan;

    /**
     * Create a new event instance.
     */
    public function __construct(InventaireScan $scan)
    {
        $this->scan = $scan;
    }
}

