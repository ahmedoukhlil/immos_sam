<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Code extends Model
{
    use HasFactory;

    protected $table = 'codes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['idGesimmo', 'barcode'];

    /**
     * RELATIONS
     */

    /**
     * Relation avec l'immobilisation
     */
    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Gesimmo::class, 'idGesimmo', 'NumOrdre');
    }
}
