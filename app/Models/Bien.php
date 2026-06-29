<?php

namespace App\Models;

use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Bien extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code_inventaire',
        'designation',
        'date_acquisition',
        'nature',
        'service_usager',
        'localisation_id',
        'valeur_acquisition',
        'etat',
        'qr_code_path',
        'observation',
        'user_id',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'valeur_acquisition' => 'decimal:2',
    ];

    /**
     * RELATIONS
     */

    /**
     * Relation avec la localisation
     */
    public function localisation(): BelongsTo
    {
        return $this->belongsTo(Localisation::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé le bien
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Scope pour filtrer par nature
     */
    public function scopeByNature(Builder $query, string $nature): Builder
    {
        return $query->where('nature', $nature);
    }

    /**
     * Scope pour filtrer par localisation
     */
    public function scopeByLocalisation(Builder $query, int $localisationId): Builder
    {
        return $query->where('localisation_id', $localisationId);
    }

    /**
     * Scope pour filtrer par service
     */
    public function scopeByService(Builder $query, string $service): Builder
    {
        return $query->where('service_usager', $service);
    }

    /**
     * Scope pour filtrer les biens actifs (non supprimés)
     */
    public function scopeActifs(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * ACCESSORS
     */

    /**
     * Retourne le nom complet de la localisation
     */
    public function getLocalisationNomAttribute(): ?string
    {
        return $this->localisation?->full_name;
    }

    /**
     * Calcule l'âge du bien en années
     */
    public function getAgeAttribute(): int
    {
        if (!$this->date_acquisition) {
            return 0;
        }

        return Carbon::parse($this->date_acquisition)->diffInYears(now());
    }

    /**
     * METHODS
     */

    /**
     * Génère un code d'inventaire unique au format INV-ANNEE-XXX
     */
    public static function generateCodeInventaire(): string
    {
        $annee = now()->year;
        $lastBien = self::where('code_inventaire', 'like', "INV-{$annee}-%")
            ->orderBy('code_inventaire', 'desc')
            ->first();

        if ($lastBien) {
            $lastNumber = (int) substr($lastBien->code_inventaire, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('INV-%d-%03d', $annee, $nextNumber);
    }

    /**
     * Génère et sauvegarde le QR code pour ce bien
     * 
     * @return string Le chemin relatif du QR code généré
     */
    public function generateQRCode(): string
    {
        $qrCodeService = app(\App\Services\QRCodeService::class);
        return $qrCodeService->generateForBien($this);
    }
}

