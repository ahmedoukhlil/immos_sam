<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NatureJuridique extends Model
{
    use HasFactory;

    protected $table = 'naturejurdique';
    protected $primaryKey = 'idNatJur';
    public $timestamps = false;

    protected $fillable = ['NatJur', 'CodeNatJur'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idNatJur', 'idNatJur');
    }
}
