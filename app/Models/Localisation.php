<?php

namespace App\Models;

use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Localisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'designation',
        'batiment',
        'etage',
        'service_rattache',
        'responsable',
        'qr_code_path',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'etage' => 'integer',
    ];

    /**
     * RELATIONS
     */

    /**
     * Relation avec les biens
     */
    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }

    /**
     * Relation avec les inventaire_localisations
     */
    public function inventaireLocalisations(): HasMany
    {
        return $this->hasMany(InventaireLocalisation::class);
    }

    /**
     * SCOPES
     */

    /**
     * Scope pour filtrer les localisations actives
     */
    public function scopeActives(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    /**
     * Scope pour filtrer par bâtiment
     */
    public function scopeByBatiment(Builder $query, string $batiment): Builder
    {
        return $query->where('batiment', $batiment);
    }

    /**
     * Scope pour filtrer par service
     */
    public function scopeByService(Builder $query, string $service): Builder
    {
        return $query->where('service_rattache', $service);
    }

    /**
     * ACCESSORS
     */

    /**
     * Retourne le nom complet : "Code - Désignation"
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->designation}";
    }

    /**
     * Retourne le nombre de biens dans cette localisation
     */
    public function getNombreBiensAttribute(): int
    {
        return $this->biens()->count();
    }

    /**
     * METHODS
     */

    /**
     * Génère et sauvegarde le QR code pour cette localisation
     * 
     * @return string Le chemin relatif du QR code généré
     */
    public function generateQRCode(): string
    {
        $qrCodeService = app(\App\Services\QRCodeService::class);
        return $qrCodeService->generateForLocalisation($this);
    }
}

