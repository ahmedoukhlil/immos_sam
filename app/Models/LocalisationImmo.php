<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalisationImmo extends Model
{
    use HasFactory;

    protected $table = 'localisation';
    protected $primaryKey = 'idLocalisation';
    public $timestamps = false;

    protected $fillable = ['Localisation', 'CodeLocalisation'];

    /**
     * Get the route key for the model.
     * Permet d'utiliser idLocalisation dans les routes au lieu de 'id'
     */
    public function getRouteKeyName()
    {
        return 'idLocalisation';
    }

    /**
     * RELATIONS
     */

    /**
     * Alias pour CodeLocalisation (compatibilité vues)
     */
    public function getCodeAttribute(): string
    {
        return $this->CodeLocalisation ?? $this->Localisation ?? '';
    }

    /**
     * Alias pour Localisation (compatibilité vues)
     */
    public function getDesignationAttribute(): string
    {
        return $this->Localisation ?? $this->CodeLocalisation ?? '';
    }

    /**
     * Relation avec les emplacements
     */
    public function emplacements(): HasMany
    {
        return $this->hasMany(Emplacement::class, 'idLocalisation', 'idLocalisation');
    }
}
