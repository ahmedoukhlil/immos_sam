<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceFinancement extends Model
{
    use HasFactory;

    protected $table = 'sourcefinancement';
    protected $primaryKey = 'idSF';
    public $timestamps = false;

    protected $fillable = ['SourceFin', 'CodeSourceFin'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec les immobilisations
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'idSF', 'idSF');
    }
}
