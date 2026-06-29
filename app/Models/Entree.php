<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entree extends Model
{
    use HasFactory;

    protected $table = 'entree';
    protected $primaryKey = 'idEntree';
    public $timestamps = false;

    protected $fillable = ['idProduit', 'idEmplacement', 'DateEntree', 'Quantite'];

    protected $casts = [
        'DateEntree' => 'date',
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

    /**
     * Relation avec l'emplacement
     */
    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement');
    }
}
