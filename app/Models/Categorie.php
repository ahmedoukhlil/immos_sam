<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categorie';
    protected $primaryKey = 'idCategorie';
    public $timestamps = false;

    protected $fillable = ['Categorie', 'CodeCategorie', 'duree_amortissement', 'taux_amortissement', 'type_cgi'];

    public function getRouteKeyName(): string
    {
        return 'idCategorie';
    }

    /**
     * RELATIONS
     */

    /**
     * Relation avec les désignations
     */
    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class, 'idCat', 'idCategorie');
    }

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idCategorie', 'idCategorie');
    }
}
