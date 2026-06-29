<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etat extends Model
{
    use HasFactory;

    protected $table = 'etat';
    protected $primaryKey = 'idEtat';
    public $timestamps = false;

    protected $fillable = ['Etat', 'CodeEtat'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idEtat', 'idEtat');
    }
}
