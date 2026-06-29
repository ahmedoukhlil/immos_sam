<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    use HasFactory;

    protected $table = 'affectation';
    protected $primaryKey = 'idAffectation';
    public $timestamps = false;

    protected $fillable = ['Affectation', 'CodeAffectation', 'idLocalisation'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec la localisation
     */
    public function localisation(): BelongsTo
    {
        return $this->belongsTo(LocalisationImmo::class, 'idLocalisation', 'idLocalisation');
    }

    /**
     * Relation avec les emplacements
     */
    public function emplacements(): HasMany
    {
        return $this->hasMany(Emplacement::class, 'idAffectation', 'idAffectation');
    }
}
