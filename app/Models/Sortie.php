<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sortie extends Model
{
    use HasFactory;

    protected $table = 'sortie';
    protected $primaryKey = 'idSortie';
    public $timestamps = false;

    protected $fillable = [
        'idProduit', 'Quantite', 'DateSortie', 
        'SrvcDmndr', 'Observations'
    ];

    protected $casts = [
        'DateSortie' => 'datetime',
        'Quantite' => 'decimal:2',
    ];

    /**
     * RELATIONS
     */

    /**
     * Relation avec le produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'idProduit', 'idProduit');
    }
}
