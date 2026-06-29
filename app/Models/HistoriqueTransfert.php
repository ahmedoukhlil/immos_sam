<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueTransfert extends Model
{
    use HasFactory;

    protected $table = 'historique_transferts';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'NumOrdre',
        'ancien_idEmplacement',
        'nouveau_idEmplacement',
        'ancien_emplacement_libelle',
        'nouveau_emplacement_libelle',
        'ancien_affectation_libelle',
        'nouveau_affectation_libelle',
        'ancien_localisation_libelle',
        'nouveau_localisation_libelle',
        'transfert_par',
        'date_transfert',
        'raison',
        'groupe_transfert_id', // Pour regrouper les transferts effectués ensemble
    ];

    protected $casts = [
        'date_transfert' => 'datetime',
    ];

    /**
     * Relation avec l'immobilisation
     */
    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Gesimmo::class, 'NumOrdre', 'NumOrdre');
    }

    /**
     * Relation avec l'ancien emplacement
     */
    public function ancienEmplacement(): BelongsTo
    {
        return $this->belongsTo(Emplacement::class, 'ancien_idEmplacement', 'idEmplacement');
    }

    /**
     * Relation avec le nouveau emplacement
     */
    public function nouveauEmplacement(): BelongsTo
    {
        return $this->belongsTo(Emplacement::class, 'nouveau_idEmplacement', 'idEmplacement');
    }

    /**
     * Relation avec l'utilisateur qui a effectué le transfert
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfert_par', 'idUser');
    }
}
