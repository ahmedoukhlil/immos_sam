<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';
    protected $primaryKey = 'idProduit';
    public $timestamps = false;

    protected $fillable = ['Produit', 'Unite'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec les entrées de stock
     */
    public function entrees(): HasMany
    {
        return $this->hasMany(Entree::class, 'idProduit', 'idProduit');
    }

    /**
     * Relation avec les sorties de stock
     */
    public function sorties(): HasMany
    {
        return $this->hasMany(Sortie::class, 'idProduit', 'idProduit');
    }

    /**
     * ACCESSORS
     */

    /**
     * Calcule le stock disponible (somme des entrées - somme des sorties)
     */
    public function getStockDisponibleAttribute(): float
    {
        $totalEntree = $this->entrees()->sum('Quantite');
        $totalSortie = $this->sorties()->sum('Quantite');
        return $totalEntree - $totalSortie;
    }
}
