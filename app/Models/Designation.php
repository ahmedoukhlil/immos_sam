<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'designation';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['designation', 'CodeDesignation', 'idCat'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec la catégorie
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'idCat', 'idCategorie');
    }

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idDesignation', 'id');
    }
}
