<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emplacement extends Model
{
    use HasFactory;

    protected $table = 'emplacement';
    protected $primaryKey = 'idEmplacement';
    public $timestamps = false;

    protected $fillable = ['Emplacement', 'CodeEmplacement', 'idAffectation', 'idLocalisation'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec l'affectation
     */
    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class, 'idAffectation', 'idAffectation');
    }

    /**
     * Relation avec la localisation
     */
    public function localisation(): BelongsTo
    {
        return $this->belongsTo(LocalisationImmo::class, 'idLocalisation', 'idLocalisation');
    }

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idEmplacement', 'idEmplacement');
    }

    /**
     * Relation avec les entrées de stock
     */
    public function entrees(): HasMany
    {
        return $this->hasMany(Entree::class, 'idEmplacement', 'idEmplacement');
    }
}
