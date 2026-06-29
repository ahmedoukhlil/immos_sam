<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Inventaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'annee',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
        'closed_by',
        'observation',
    ];

    protected $casts = [
        'annee' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * RELATIONS
     */

    /**
     * Relation avec l'utilisateur qui a créé l'inventaire
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a clôturé l'inventaire
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Relation avec les localisations inventoriées
     */
    public function inventaireLocalisations(): HasMany
    {
        return $this->hasMany(InventaireLocalisation::class);
    }

    /**
     * Relation avec les scans d'inventaire
     */
    public function inventaireScans(): HasMany
    {
        return $this->hasMany(InventaireScan::class);
    }

    /**
     * SCOPES
     */

    /**
     * Scope pour filtrer les inventaires en cours
     */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', 'en_cours');
    }

    /**
     * Scope pour filtrer les inventaires terminés
     */
    public function scopeTermines(Builder $query): Builder
    {
        return $query->where('statut', 'termine');
    }

    /**
     * Scope pour filtrer par année
     */
    public function scopeByAnnee(Builder $query, int $annee): Builder
    {
        return $query->where('annee', $annee);
    }

    /**
     * ACCESSORS
     */

    /**
     * Calcule le pourcentage de progression global
     */
    public function getProgressionAttribute(): float
    {
        $totalLocalisations = $this->inventaireLocalisations()->count();
        
        if ($totalLocalisations === 0) {
            return 0;
        }

        $localisationsTerminees = $this->inventaireLocalisations()
            ->where('statut', 'termine')
            ->count();

        return round(($localisationsTerminees / $totalLocalisations) * 100, 2);
    }

    /**
     * Calcule le taux de conformité (% de biens présents)
     */
    public function getTauxConformiteAttribute(): float
    {
        $totalScans = $this->inventaireScans()->count();
        
        if ($totalScans === 0) {
            return 0;
        }

        $scansPresents = $this->inventaireScans()
            ->where('statut_scan', 'present')
            ->count();

        return round(($scansPresents / $totalScans) * 100, 2);
    }

    /**
     * Calcule la durée de l'inventaire en jours
     */
    public function getDureeAttribute(): ?int
    {
        if (!$this->date_debut) {
            return null;
        }

        $dateFin = $this->date_fin ?? now();
        
        return Carbon::parse($this->date_debut)->diffInDays($dateFin);
    }

    /**
     * METHODS
     */

    /**
     * Démarre l'inventaire (passe le statut à 'en_cours')
     */
    public function demarrer(): bool
    {
        return $this->update([
            'statut' => 'en_cours',
            'date_debut' => now(),
        ]);
    }

    /**
     * Clôture l'inventaire (passe le statut à 'cloture' et définit la date de fin)
     */
    public function cloturer(?int $closedBy = null): bool
    {
        return $this->update([
            'statut' => 'cloture',
            'date_fin' => now(),
            'closed_by' => $closedBy ?? auth()->id(),
        ]);
    }

    /**
     * Retourne un tableau avec les statistiques complètes de l'inventaire
     */
    public function getStatistiques(): array
    {
        $totalLocalisations = $this->inventaireLocalisations()->count();
        $localisationsTerminees = $this->inventaireLocalisations()
            ->where('statut', 'termine')
            ->count();

        $totalScans = $this->inventaireScans()->count();
        $scansPresents = $this->inventaireScans()->where('statut_scan', 'present')->count();
        $scansDeplaces = $this->inventaireScans()->where('statut_scan', 'deplace')->count();
        $scansAbsents = $this->inventaireScans()->where('statut_scan', 'absent')->count();
        $scansDeteriores = $this->inventaireScans()->where('statut_scan', 'deteriore')->count();

        return [
            'progression' => $this->progression,
            'taux_conformite' => $this->taux_conformite,
            'duree' => $this->duree,
            'total_localisations' => $totalLocalisations,
            'localisations_terminees' => $localisationsTerminees,
            'total_scans' => $totalScans,
            'scans_presents' => $scansPresents,
            'scans_deplaces' => $scansDeplaces,
            'scans_absents' => $scansAbsents,
            'scans_deteriores' => $scansDeteriores,
        ];
    }
}

